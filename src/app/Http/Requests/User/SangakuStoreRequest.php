<?php

namespace App\Http\Requests\User;

use App\Enums\Difficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SangakuStoreRequest extends FormRequest
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
            'sangaku.title' => ['required', 'string', 'max:255'],
            'sangaku.description' => ['required', 'string'],
            'sangaku.source' => ['required', 'string'],
            'sangaku.difficulty' => ['required', Rule::enum(Difficulty::class)],
            'fixed_inputs' => ['sometimes', 'array'],
            'fixed_inputs.*' => ['string'],
        ];
    }
}
