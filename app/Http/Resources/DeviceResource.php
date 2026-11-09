<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Device */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** @example 1 */
            'id' => $this->id,
            /** @example 550e8400-e29b-41d4-a716-446655440000 */
            'uuid' => $this->uuid,
            /** @example Sensor 01 */
            'name' => $this->name,
        ];
    }
}
