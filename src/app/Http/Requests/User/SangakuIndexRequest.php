<?php

namespace App\Http\Requests\User;

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
            'shrine_id' => ['nullable', 'string'],
            'difficulty' => ['nullable', Rule::in(Difficulty::labels())],
            'title' => ['nullable', 'string'],
        ];
    }
}
