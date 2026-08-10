# CLAUDE.md

このファイルは、Claude Code (claude.ai/code) がこのリポジトリのコードを扱う際のガイダンスを提供する。

## リポジトリの背景

このリポジトリは `Alnoir-0011/algo_sangaku_back` のフォーク／移植版であり、「算額」アプリケーションのバックエンド API (Laravel) である。ユーザーは Google Places API 経由で見つけた神社に紐づけて算額を記録し、Google Sign-In で認証する。

実際の Laravel アプリケーションは `src/` 配下にあり、リポジトリルートには Docker のオーケストレーション設定 (`docker-compose.yml`、`docker/app`、`docker/web`) と、ルート直下の `.env`（コンテナ／ポート設定であり、Laravel 自身の `.env` ではない）のみを置いている。

## 開発環境

- ローカル開発は Docker Compose で動かす: `app` (PHP 8.4-FPM + cron)、`web` (nginx)、`db` (PostgreSQL 18)。以下の `php artisan` / `composer` コマンドはすべて `app` コンテナ内で実行する:
  ```bash
  docker compose exec app <command>
  ```
- **データベースは MySQL ではなく PostgreSQL** — `docker-compose.yml`、`docker/app/Dockerfile` (`pdo_pgsql`)、`src/.env` はいずれも `pgsql`/`postgres` を使っている。`src/.env.example` には古い MySQL 前提の値（`DB_CONNECTION=mysql`、ポート 3306）が残っているため、環境構築時にそのままコピーしないこと。ルートの `.env` に合わせた `pgsql` 設定を使う。

## よく使うコマンド

以下のコマンドはすべて `src/`（または `app` コンテナ内の `/app`）で実行する。

```bash
composer install                # PHP 依存パッケージのインストール
composer test                   # config:clear + php artisan test（全スイート）
php artisan test --filter=Name  # 単一テスト／テストクラスの実行
vendor/bin/pint                 # コードスタイルの自動整形
vendor/bin/pint --test          # 整形せずフォーマットチェックのみ（CI はこちらを実行）
vendor/bin/phpstan analyse      # 静的解析（Larastan, level 5。phpstan.neon 参照）
php artisan migrate             # マイグレーションの実行
```

テストは **Pest** を使う（素の PHPUnit 構文ではない）。Feature テスト (`tests/Feature/`) は `Tests\TestCase` を継承し `RefreshDatabase` を使う（`tests/Pest.php` でグローバルに設定済み）ため、モックだけでなく実際に（マイグレーション済みの）データベースに対して実行される。

CI (`.github/workflows/ci.yml`) は `main` への PR ごとに 2 つのジョブを実行する: `lint`（Pint のフォーマットチェック + Larastan）と `test`（CI では sqlite に対して Pest スイートを実行）。push 前にローカルで同じ状態にしておくこと。

## アーキテクチャ

**API の構成**: すべてのルートは `/api/v1` 配下にバージョニングされている (`routes/api.php`)。コントローラはバージョンごとに名前空間を分けており — `App\Http\Controllers\V1`（`V` は大文字）— ユーザーにスコープされたリソースはさらに `App\Http\Controllers\V1\User` の下にネストする。`V1\User` 配下のものは、`Sangaku::findOrFail(...)` ではなく `$request->user()->sangakus()->findOrFail(...)` を通じて、認証済みユーザー自身のレコードのみを厳密に対象とする。このスコープ *そのもの* がこれらのエンドポイントの認可であり、他人のレコードは 404 として表面化しなければならない。同じ理由から、ここでは Route Model Binding を意図的に使っていない。`V1` 配下のコントローラは `App\Http\Controllers\Controller` を直接継承せず、`V1\BaseController`（`ApiResponse` トレイトを持つ）を継承する。

エンドポイントのグループ:
- 公開: `GET /shrines`、`GET /shrines/{shrine}`、`GET /sangakus/{sangaku}`、`GET /shrines/{shrine}/sangakus`
- `auth:sanctum`: `POST|DELETE /authentication`、`apiResource /user/sangakus`、`POST /user/sangakus/{sangaku}/dedicate`

**認証**: Google Sign-In のみで、パスワード認証はない。`AuthenticationController::store` は `GoogleAuthService`（`Google_Client::verifyIdToken` のラッパー）経由で Google ID トークンを検証し、`['provider' => 'google', 'uid' => $payload['sub']]` をキーに `User` を `firstOrCreate` したうえで、Sanctum のパーソナルアクセストークンを発行する。トークンは JSON ボディではなく `AccessToken` レスポンスヘッダーで返す。保護されたルートは `auth:sanctum` ミドルウェアグループを使う。

**ドメインモデル** (`app/Models`):
- `User` — `Sangaku` を多数持つ。`role` は `App\Enums\Role` enum にキャストされる。
- `Shrine` — `Sangaku` を多数持つ。ユーザーが直接作成するのではなく `PlaceApiService` が投入する。
- `Sangaku` — `User` と `Shrine` に属し、`FixedInput` を多数持つ。`difficulty` は `App\Enums\Difficulty` enum にキャストされる。ローカルスコープ `scopeSearch(Builder, ?array $params)` を持ち、`shrine_id`（「いずれかの神社に紐づいている」を意味するセンチネル値 `'any'` を含む）、`difficulty`、および空白で分割した `title` の LIKE 検索を扱う。これが、他の絞り込み可能な一覧エンドポイントでも踏襲すべきクエリ組み立ての慣習である。

**外部 API 連携**: `PlaceApiService` は緯度経度のバウンディングボックスで範囲を限定して Google Places API の `searchText` エンドポイントを呼び出し、キーワードの拒否リスト（`寺`、`手水舎` など。`神社` というテキストクエリでも寺院に近い施設が返ってくるため）で神社以外の結果を除外し、各結果の形を検証したうえで、`place_id` をキーにローカルの `Shrine` レコードを `updateOrCreate` する。Google Places はライブで素通しする先ではなく、同期元として扱う。

**エラーハンドリング**: すべての API エラーレスポンスは `App\Exceptions\ApiExceptionRenderer::render(status, message, details?, extras?)` を通して JSON の形に整えられる。コントローラ内で場当たり的に `response()->json([...], $status)` のエラーペイロードを組み立ててはならない。エラーの形を一貫させるため、以下 2 つの経路のどちらかを必ず通すこと:

- **横断的な関心事として例外で送出されるエラー**（404 / 401 / 429 / 502 など）→ `bootstrap/app.php` の `withExceptions` に `$exceptions->render(...)` のマッピングを追加する。障害ではなく想定内の結果を表す例外であれば `dontReport` にも登録する。既存の主なマッピング: `ValidationException` → 400（Laravel 標準の 422 ではない）、Postgres の一意制約違反コード `23505` を伴う `QueryException` → 409 Conflict、独自例外 `GooglePlacesApiException` → 502、独自例外 `InvalidGoogleTokenException` → 401。
- **コントローラの分岐から返す業務ルール上の失敗**（400 / 409 など）→ `App\Http\Controllers\Concerns\ApiResponse` トレイト（`V1\BaseController` が既に `use` 済み）の `return $this->render400(...)` / `render409(...)` / `renderError(status, message, errors?)` を使う。メッセージごとに使い捨ての例外クラスを作ると `bootstrap/app.php` が肥大するため、こちらを優先する。これは移植元リポジトリの Rails の `Api::ExceptionHandler` concern に対応する。例: `DedicateController` は、既に奉納済みの算額に対して 409 を、与えられた座標が神社から遠すぎる場合に 400 を返す。

どちらの経路も最終的に `ApiExceptionRenderer` に集約されるため、レスポンスの形は同一になる。

**API Resource**: 算額には 2 つの形があり、どちらを使うかはスタイルの好みではなくドメイン上のルールである。`SangakuResource` は `attributes.source` を保存されているまま返し、`V1\User` 配下の所有者スコープのエンドポイント (`SangakusController`、`DedicateController`) でのみ使う。`PublicSangakuResource` は `attributes.source` が `null` 固定である点を除いて同一で、未認証のエンドポイント (`V1\SangakusController`、`V1\ShrineSangakusController`) が使う。`source` はレコードを所有していない相手には伏せるため、新しく公開エンドポイントを追加する場合も必ずこちらを使うこと。なおキー自体は省略されず、値が `null` で存在する点に注意。両者とも JSON:API 風の `{id, type, attributes, relationships}` の形を返し、まだ奉納されていない算額では `relationships.shrine.data` が `null` になる。

**Enum**: backed enum (`Difficulty: int`、`Role: int`) は、生の int として手作業で扱うのではなく、`casts(): array` メソッドで Eloquent モデルに直接キャストする（統一されており、`protected $casts` プロパティを使っているモデルは 1 つもない）。`Difficulty` はさらに `label()` / `fromLabel()` / `labels()` を公開しており、API が難易度を名前で受け取り検証するのはこの仕組みによる（書き込みは `Rule::enum()`、検索フィルタは `Rule::in(Difficulty::labels())`）。
