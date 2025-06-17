<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;

class CreateDonationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string',],
            'recipient' => ['required', 'string'],
            'description' => ['required'],
            'thumbnail' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10000'],
            'program_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10000'],
            'target' => ['required', 'numeric', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
