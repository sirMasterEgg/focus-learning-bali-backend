<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['unique:App\Models\Category,name'],
            'icon' => [],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
