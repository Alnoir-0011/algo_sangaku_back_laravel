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
            'sangaku' => ['required', 'array:title,description,source,difficulty'],
            'sangaku.title' => ['required', 'string', 'max:255'],
            'sangaku.description' => ['required', 'string', 'max:65535'],
            'sangaku.source' => ['required', 'string', 'max:65535'],
            'sangaku.difficulty' => ['required', Rule::enum(Difficulty::class)],
            'fixed_inputs' => ['sometimes', 'array', 'max:50'],
            // content カラムが varchar(255) のため、超過分は INSERT 時に 500 になる。上限を揃える
            'fixed_inputs.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }
}
