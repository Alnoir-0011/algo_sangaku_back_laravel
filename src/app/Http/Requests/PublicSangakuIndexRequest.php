<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 未認証で到達できる一覧エンドポイント用の検索条件。
 *
 * 所有者向けの SangakuIndexRequest と違い shrine_id を受け付けない。
 * 公開一覧は URL 側で対象の神社が決まるため、競合するパラメータを
 * 入口で落としてスコープ逸脱の余地をなくす。
 */
class PublicSangakuIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'difficulty' => ['nullable', Rule::in(Difficulty::labels())],
            'title' => ['nullable', 'string', 'max:100'],
            // 巨大な OFFSET を伴うページ送りを防ぐ
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
