<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['level', 'type', 'question_text', 'question_img'];
    protected $appends = ['question_img_url'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function getQuestionImgUrlAttribute()
    {
        return $this->question_img ? asset('storage/' . $this->question_img) : null;
    }
}
