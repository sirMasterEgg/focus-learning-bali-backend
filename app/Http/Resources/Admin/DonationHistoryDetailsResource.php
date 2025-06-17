<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationHistoryDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'human_readable_id' => $this->human_readable_id,
            'name' => $this->donation_name,
            'email' => $this->donation_email,
            'program_name' => $this->donation->title,
            'donation_amount' => $this->amount,
            'target_amount' => $this->donation->target,
            'payment' => [
                'method' => $this->payment_method === 'qris' ? 'QRIS' : 'Debit/Credit Card',
                'status' => $this->payment_status,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
