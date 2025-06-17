<?php

namespace App\Http\Resources\Admin;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Donation */
class DonationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'human_readable_id' => $this->human_readable_id,
            'title' => $this->title,
            'recipient' => $this->recipient,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'program_image' => $this->program_image,
            'current_donation' => $this->current_donation,
            'target' => $this->target,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
