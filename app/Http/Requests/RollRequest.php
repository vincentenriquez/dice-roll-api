<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RollRequest extends FormRequest
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
            'guess' => ['required', 'integer', 'min:1', 'max:6'],
            'stake' => ['required', 'integer', 'min:1', 'max:'. $this->user()->balance],
        ];
    }

    public function messages(): array
    {
        return [
            'guess.required' => 'The guess field is required.',
            'guess.integer' => 'The guess must be an integer.',
            'guess.min' => 'The guess must be at least 1.',
            'guess.max' => 'The guess must be at most 6.',
            'stake.required' => 'The stake field is required.',
            'stake.integer' => 'The stake must be an integer.',
            'stake.min' => 'The stake must be at least 1.',
            'stake.max' => 'The stake must not exceed your balance.',
        ];
    }
}
