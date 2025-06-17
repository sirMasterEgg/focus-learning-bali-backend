<?php

namespace App\Http\Requests\Donate;

use Illuminate\Foundation\Http\FormRequest;

class DonateRequest extends FormRequest
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
            'donation_id' => ['required', 'exists:donations,human_readable_id'],
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:card,qris'],
            'token_id' => ['required_if:payment_method,card', 'string'],
            'save_card' => ['required_if:payment_method,card', 'boolean'],
            'customer_details' => ['required_if:payment_method,card', 'array:first_name,last_name,phone_number'],
            'customer_details.first_name' => ['required_if:payment_method,card', 'string'],
            'customer_details.last_name' => ['required_if:payment_method,card', 'string'],
            'customer_details.phone_number' => ['required_if:payment_method,card', 'string'],
        ];
    }
}
