<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $provider
 * @property-read string $uid
 * @property-read string $name
 * @property-read string $email
 * @property-read string $nickname
 * @property-read string $role
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
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
            'provider' => $this->provider,
            'uid' => $this->uid,
            'name' => $this->name,
            'email' => $this->email,
            'nickname' => $this->nickname,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
