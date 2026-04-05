<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Authorization extends Model
{
    protected $casts = [
        'merchant_data' => 'object'
    ];

    public function card()
    {
        return $this->belongsTo(VirtualCard::class, 'card_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', 1);
    }
}
