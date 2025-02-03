<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'name' => $this->name,
            'email_verify_at' => $this->email_verified_at,
            'meta' => $this->when($this->name == 'user', function () {
                return 1;
            }, function () {
                return 2;
            })
        ];
    }
}
