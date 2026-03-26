<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'response',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response'   => 'array',
            'expires_at' => 'datetime',
        ];
    }

    // Relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper - check if key is still valid
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
