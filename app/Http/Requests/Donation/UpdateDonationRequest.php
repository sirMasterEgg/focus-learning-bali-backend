<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDonationRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string'],
            'recipient' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'required'],
            'thumbnail' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png', 'max:10000'],
            'program_image' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png', 'max:10000'],
            'target' => ['sometimes', 'required', 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
