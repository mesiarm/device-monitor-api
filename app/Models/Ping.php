<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ping extends Model
{
    /** @use HasFactory<\Database\Factories\PingFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['battery_percent'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
