<?php

namespace App\Models\Cupone;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuponeUserUsage extends Model
{
    use HasFactory;

    protected $table = 'cupone_user_usage';

    public $timestamps = false;

    protected $fillable = [
        'cupone_id',
        'user_id',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function cupone()
    {
        return $this->belongsTo(Cupone::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
