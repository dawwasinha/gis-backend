<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatus extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'last_cbt_submission',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_cbt_submission' => 'datetime',
    ];

    /**
     * Get the user that owns the status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can login.
     */
    public function canLogin(): bool
    {
        return in_array($this->status, ['active', 'can_login']);
    }

    /**
     * Scope query to get users who can login.
     */
    public function scopeCanLogin($query)
    {
        return $query->whereIn('status', ['active', 'can_login']);
    }

    /**
     * Scope query to get users who cannot login.
     */
    public function scopeCannotLogin($query)
    {
        return $query->whereIn('status', ['inactive', 'suspended', 'cannot_login']);
    }
}
