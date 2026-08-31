<?php

namespace App\Http\Resources;

use App\Models\Lounge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lounge
 */
class LoungeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'airport_iata' => $this->airport_iata,
            'name' => $this->name,
            'terminal' => $this->terminal,
            'access_conditions' => $this->access_conditions,
        ];
    }
}
