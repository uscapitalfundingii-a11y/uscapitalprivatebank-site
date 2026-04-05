<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountOpeningRequest extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'user_id',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public static function currencyOptions(): array
    {
        return [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'symbol' => 'EUR'],
            'GBP' => ['name' => 'British Pound', 'symbol' => 'GBP'],
            'AED' => ['name' => 'UAE Dirham', 'symbol' => 'AED'],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'CAD'],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'AUD'],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => 'JPY'],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => 'CNY'],
            'ZAR' => ['name' => 'South African Rand', 'symbol' => 'ZAR'],
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(Admin::class, 'rejected_by');
    }
}
