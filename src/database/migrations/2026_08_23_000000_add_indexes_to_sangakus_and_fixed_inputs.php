<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 外部キー列にインデックスを追加する。
 *
 * PostgreSQL は外部キー制約を張っても子テーブル側の列にインデックスを作らない
 * （MySQL/InnoDB とは異なる）。user_id / shrine_id / sangaku_id はいずれも
 * 一覧取得と eager load の絞り込みに使うため、明示的に張っておく。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sangakus', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('shrine_id');
        });

        Schema::table('fixed_inputs', function (Blueprint $table) {
            $table->index('sangaku_id');
        });
    }

    public function down(): void
    {
        Schema::table('sangakus', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['shrine_id']);
        });

        Schema::table('fixed_inputs', function (Blueprint $table) {
            $table->dropIndex(['sangaku_id']);
        });
    }
};
