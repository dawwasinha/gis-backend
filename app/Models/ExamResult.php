<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = [
        'user_id',
        'duration_in_minutes',
        'total_violations',
        'is_auto_submit',
        'submitted_at'
    ];

    protected $casts = [
        'is_auto_submit' => 'boolean',
        'submitted_at' => 'datetime'
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
