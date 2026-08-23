<?php

namespace App\Http\Requests\User;

use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SangakuUpdateRequest extends FormRequest
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
            'sangaku' => ['required', 'array'],
            'sangaku.title' => ['sometimes', 'string', 'max:255'],
            'sangaku.description' => ['sometimes', 'string', 'max:65535'],
            'sangaku.source' => ['sometimes', 'string', 'max:65535'],
            'sangaku.difficulty' => ['sometimes', Rule::enum(Difficulty::class)],
            'fixed_inputs' => ['sometimes', 'array', 'max:50'],
            'fixed_inputs.*' => ['required', 'string', 'max:65535', 'distinct'],
        ];
    }
}
