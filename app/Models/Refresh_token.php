<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refresh_token extends Model
{
    protected $table = 'refresh_token';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
