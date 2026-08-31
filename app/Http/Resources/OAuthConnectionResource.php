<?php

namespace App\Http\Resources;

use App\Models\OAuthConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OAuthConnection
 */
class OAuthConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'status' => $this->status,
            'token_expires_at' => $this->token_expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
