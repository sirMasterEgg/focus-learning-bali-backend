<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required'],
            'description' => ['required'],
            'recipient' => ['required'],
            'quote' => ['required'],
            'target' => ['required', 'numeric', 'min:1'],
            'banner' => ['required'],
            'accept_donation' => ['required', 'boolean'],
            'category_id' => ['required', 'array', 'min:1'],
            'category_id.*' => ['required', 'exists:categories,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
