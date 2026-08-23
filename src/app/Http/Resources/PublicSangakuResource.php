<?php

namespace App\Http\Resources;

/**
 * 公開用の算額リソース。source（解答）は所有者以外に見せないため常に null を返す。
 * 未認証で到達できるエンドポイントは必ずこちらを使うこと。
 */
class PublicSangakuResource extends BaseSangakuResource
{
    protected function resolveSource(): ?string
    {
        return null;
    }
}
