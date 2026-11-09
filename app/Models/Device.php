<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    protected $fillable = ['uuid', 'name'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function pings(): HasMany
    {
        return $this->hasMany(Ping::class);
    }

    public function latestPing(): HasOne
    {
        return $this->hasOne(Ping::class)->latestOfMany();
    }
}
