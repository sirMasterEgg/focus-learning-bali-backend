<?php

namespace App\Http\Resources\Share\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'title' => $this->title,
            'avatar' => $this->avatar,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_oauth' => $this->userOauth !== null,
            'oauth_provider' => $this->userOauth?->provider,
            'already_set_password' => $this->password !== null,
        ];
    }
}
