<?php

namespace App\Http\Resources;

use App\Models\ForwardingAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ForwardingAddress
 */
class ForwardingAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->address,
            'created_at' => $this->created_at,
        ];
    }
}
