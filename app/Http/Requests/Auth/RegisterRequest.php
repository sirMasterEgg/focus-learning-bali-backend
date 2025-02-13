<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:254', 'unique:App\Models\User,email'],
            'password' => ['required', 'confirmed'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
