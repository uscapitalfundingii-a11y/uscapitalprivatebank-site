<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'account_type',
        'currency_code',
        'currency_symbol',
        'balance',
        'status',
        'is_primary',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_account_id')->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', 1);
    }

    public function syncLegacyUserBalance(): void
    {
        $user = $this->user;

        if (!$user) {
            return;
        }

        if ((string) $user->account_number !== (string) $this->account_number) {
            return;
        }

        $user->forceFill([
            'balance' => $this->balance,
        ])->saveQuietly();
    }
}
