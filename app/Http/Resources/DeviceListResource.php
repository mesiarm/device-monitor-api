<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Device */
class DeviceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /** @example 550e8400-e29b-41d4-a716-446655440000 */
            'uuid' => $this->uuid,
            /** @example Sensor 01 */
            'name' => $this->name,
            /** @example 85 */
            'last_battery_percent' => $this->latestPing?->battery_percent,
        ];
    }
}
