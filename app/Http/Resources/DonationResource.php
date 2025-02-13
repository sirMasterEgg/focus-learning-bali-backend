<?php

namespace App\Http\Resources;

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
            'title' => $this->title,
            'description' => $this->description,
            'recipient' => $this->recipient,
            'quote' => $this->quote,
            'target' => $this->target,
            'banner' => $this->banner,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'accept_donation' => $this->accept_donation,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
