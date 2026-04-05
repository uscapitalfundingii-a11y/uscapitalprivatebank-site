<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Transaction extends Model {
    protected static function booted()
    {
        static::creating(function (self $transaction) {
            if (!Schema::hasColumn('transactions', 'user_account_id') || !Schema::hasColumn('transactions', 'account_number')) {
                return;
            }

            if ($transaction->user_account_id && $transaction->account_number) {
                return;
            }

            if ($transaction->user_account_id && !$transaction->account_number) {
                $account = UserAccount::find($transaction->user_account_id);
                $transaction->account_number = $account?->account_number;
                return;
            }

            if (!$transaction->user_id) {
                return;
            }

            $user = User::with('activeAccount')->find($transaction->user_id);
            if (!$user) {
                return;
            }

            $account = $user->activeAccount;
            if (!$account && $user->account_number) {
                $account = UserAccount::where('user_id', $user->id)
                    ->where('account_number', $user->account_number)
                    ->first();
            }

            $transaction->user_account_id = $account?->id;
            $transaction->account_number = $account?->account_number ?: $user->account_number;
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function account() {
        return $this->belongsTo(UserAccount::class, 'user_account_id');
    }

    public function card() {
        return $this->belongsTo(VirtualCard::class, 'virtual_card_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function branchStaff() {
        return $this->belongsTo(BranchStaff::class, 'branch_staff_id');
    }

    public function scopeReferralCommission($query) {
        return $query->where('remark', 'referral_commission');
    }

    public function scopePlus($query) {
        return $query->where('trx_type', '+');
    }

    public function scopeMinus($query) {
        return $query->where('trx_type', '-');
    }

    public function scopeSumAmount($query) {
        return $query->selectRaw("SUM(amount) as amount, DATE_FORMAT(created_at,'%Y-%m-%d') as date");
    }

    public function scopeLastDays($query, $days = 30) {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
