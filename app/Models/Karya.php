<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karya extends Model
{
    protected $fillable = [
        'user_id',
        'link_karya',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
