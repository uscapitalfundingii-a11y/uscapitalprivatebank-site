<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastMessagePreset extends Model
{
    protected $fillable = [
        'name',
        'via',
        'subject',
        'message',
        'created_by',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
