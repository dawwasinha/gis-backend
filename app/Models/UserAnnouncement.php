<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnnouncement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status_lolos',
        'kategori_lomba',
        'skor_akhir',
        'ranking',
        'keterangan',
        'tanggal_pengumuman',
        'diumumkan_oleh',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_pengumuman' => 'datetime',
        'skor_akhir' => 'integer',
        'ranking' => 'integer',
    ];

    /**
     * Get the user that owns the announcement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user lolos.
     */
    public function isLolos(): bool
    {
        return $this->status_lolos === 'lolos';
    }

    /**
     * Check if user tidak lolos.
     */
    public function isTidakLolos(): bool
    {
        return $this->status_lolos === 'tidak_lolos';
    }

    /**
     * Scope query to get users who lolos.
     */
    public function scopeLolos($query)
    {
        return $query->where('status_lolos', 'lolos');
    }

    /**
     * Scope query to get users who tidak lolos.
     */
    public function scopeTidakLolos($query)
    {
        return $query->where('status_lolos', 'tidak_lolos');
    }

    /**
     * Scope query to get announcements by kategori lomba.
     */
    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori_lomba', $kategori);
    }

    /**
     * Scope query to get announcements ordered by ranking.
     */
    public function scopeOrderByRanking($query)
    {
        return $query->orderBy('ranking', 'asc');
    }
}
