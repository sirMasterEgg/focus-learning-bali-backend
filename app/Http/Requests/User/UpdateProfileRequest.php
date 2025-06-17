<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'required', 'email', 'max:254', 'unique:App\Models\User,email'],
            'name' => ['sometimes', 'required', 'string', 'max:254'],
            'title' => ['sometimes', 'required', 'string', 'in:Mr.,Mrs.,Miss'],
        ];
    }
}
