<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetCodePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:254'],
            'code' => ['required'],
            'password' => ['required', 'min:6', 'confirmed']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
