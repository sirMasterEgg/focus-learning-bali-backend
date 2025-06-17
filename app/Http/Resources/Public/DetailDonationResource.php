<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailDonationResource extends JsonResource
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
            'recipient' => $this->recipient,
            'title' => $this->title,
            'description' => $this->description,
            'program_image' => $this->program_image,
            'current_donation' => $this->current_donation,
            'target' => $this->target,
            'total_donors' => $this->users()->where('payment_status', 'success')->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
