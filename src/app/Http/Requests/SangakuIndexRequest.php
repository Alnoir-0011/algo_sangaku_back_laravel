<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SangakuIndexRequest extends FormRequest
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
            // 'any'（奉納済みすべて）か神社 ID のみ受け付ける。
            // 空文字は ConvertEmptyStringsToNull により null で届き、未奉納の絞り込みを意味する。
            'shrine_id' => ['nullable', 'string', 'regex:/^(any|\d+)$/'],
            'difficulty' => ['nullable', Rule::in(Difficulty::labels())],
            'title' => ['nullable', 'string', 'max:100'],
        ];
    }
}
