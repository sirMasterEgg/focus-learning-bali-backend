<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'unique:App\Models\Category,name'],
            'icon' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
