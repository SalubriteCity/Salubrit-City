<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthSession extends Model
{
    protected $fillable = [
        'user_id',
        'access_token_hash',
        'refresh_token_hash',
        'token_expires_at',
        'refresh_expires_at',
        'revoked_at',
        'last_used_at',
    ];

    protected $hidden = ['access_token_hash', 'refresh_token_hash'];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
