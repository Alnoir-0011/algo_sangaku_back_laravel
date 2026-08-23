<?php

namespace App\Http\Resources;

/**
 * 所有者向けの算額リソース。source をそのまま返すため、
 * V1\User 配下のエンドポイントでのみ使用すること。
 */
class SangakuResource extends BaseSangakuResource
{
    protected function resolveSource(): ?string
    {
        return $this->source;
    }
}
