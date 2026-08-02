<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class ShrineSearchRequest extends FormRequest
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
     * @return array<string, ValidatorContract|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'searchType' => ['required', 'string', 'in:Map'],
            'lowLat' => ['required', 'numeric', 'between:-90,90'],
            'highLat' => ['required', 'numeric', 'between:-90,90'],
            'lowLng' => ['required', 'numeric', 'between:-180,180'],
            'highLng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($validator->errors()->hasAny(['lowLat', 'highLat', 'lowLng', 'highLng'])) {
                return;
            }

            $lowLat = (float) $this->input('lowLat');
            $highLat = (float) $this->input('highLat');
            $lowLng = (float) $this->input('lowLng');
            $highLng = (float) $this->input('highLng');

            if ($highLat <= $lowLat) {
                $validator->errors()->add('highLat', 'The highLat must be greater than lowLat.');
            }

            if ($highLng <= $lowLng) {
                $validator->errors()->add('highLng', 'The highLng must be greater than lowLng.');
            }
        });
    }
}
