<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Device */
class DeviceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'last_ping' => $this->latestPing?->created_at->toIso8601ZuluString(),
            'last_battery_percent' => $this->latestPing?->battery_percent,
        ];
    }
}
