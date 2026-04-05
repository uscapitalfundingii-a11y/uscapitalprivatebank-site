<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, UserNotify;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'ver_code',
        'balance',
        'kyc_data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'address' => 'object',
        'kyc_data' => 'object',
        'ver_code_send_at' => 'datetime'
    ];

    protected static function booted()
    {
        static::saved(function (self $user) {
            if (!Schema::hasTable('user_accounts') || !$user->account_number) {
                return;
            }

            $account = UserAccount::updateOrCreate(
                ['account_number' => $user->account_number],
                [
                    'user_id' => $user->id,
                    'account_name' => UserAccount::where('account_number', $user->account_number)->value('account_name') ?: 'Primary Checking',
                    'account_type' => UserAccount::where('account_number', $user->account_number)->value('account_type') ?: 'checking',
                    'currency_code' => UserAccount::where('account_number', $user->account_number)->value('currency_code') ?: gs('cur_text'),
                    'currency_symbol' => UserAccount::where('account_number', $user->account_number)->value('currency_symbol') ?: gs('cur_sym'),
                    'balance' => $user->balance,
                    'status' => $user->status == Status::USER_BAN ? Status::DISABLE : Status::ENABLE,
                    'is_primary' => 1,
                ]
            );

            UserAccount::where('user_id', $user->id)
                ->where('id', '!=', $account->id)
                ->where('is_primary', 1)
                ->update(['is_primary' => 0]);
        });
    }


    public function loginLogs() {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class)->where('virtual_card_id', 0)->orderBy('id', 'desc');
    }

    public function deposits() {
        return $this->hasMany(Deposit::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function withdrawals() {
        return $this->hasMany(Withdrawal::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function fdr() {
        return $this->hasMany(Fdr::class, 'user_id');
    }

    public function dps() {
        return $this->hasMany(Dps::class, 'user_id');
    }
    public function loan() {
        return $this->hasMany(Loan::class, 'user_id');
    }
    public function transfer() {
        return $this->hasMany(BalanceTransfer::class, 'user_id');
    }

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function accounts()
    {
        return $this->hasMany(UserAccount::class)->orderByDesc('is_primary')->orderBy('created_at');
    }

    public function activeAccount()
    {
        return $this->hasOne(UserAccount::class)->where('is_primary', 1);
    }

    public function accountOpeningRequests()
    {
        return $this->hasMany(AccountOpeningRequest::class)->latest();
    }

    public function branchStaff() {
        return $this->belongsTo(BranchStaff::class, 'branch_staff_id');
    }

    public function referrer() {
        return $this->belongsTo(User::class, 'ref_by');
    }

    public function referees() {
        return $this->hasMany(User::class, 'ref_by');
    }

    public function beneficiaryTypes() {
        return $this->morphMany(Beneficiary::class, 'beneficiary', 'beneficiary_type', 'beneficiary_id');
    }

    public function allReferees() {
        return $this->referees()->with('allReferees:id,ref_by,username');
    }

    public function virtualCards() {
        return $this->hasMany(VirtualCard::class);
    }

    public function topups() {
        return $this->hasMany(Topup::class);
    }

    public function tickets() {
        return $this->hasMany(SupportTicket::class);
    }

    public function fullname(): Attribute {
        return new Attribute(
            get: fn() => $this->firstname . ' ' . $this->lastname,
        );
    }

    public function mobileNumber(): Attribute {
        return new Attribute(
            get: fn() => $this->dial_code . $this->mobile,
        );
    }

    // SCOPES
    public function scopeProfileInComplete($query) {
        return $query->where('profile_complete', Status::NO);
    }

    public function scopeProfileCompleted($query) {
        return $query->where('profile_complete', Status::YES);
    }

    public function scopeActive($query) {
        return $query->where('users.status', Status::USER_ACTIVE)->where('ev', Status::VERIFIED)->where('sv', Status::VERIFIED);
    }

    public function scopeBanned($query) {
        return $query->where('users.status', Status::USER_BAN);
    }

    public function scopeEmailUnverified($query) {
        return $query->where('ev', Status::UNVERIFIED);
    }

    public function scopeMobileUnverified($query) {
        return $query->where('sv', Status::UNVERIFIED);
    }

    public function scopeKycUnverified($query) {
        return $query->where('kv', Status::KYC_UNVERIFIED);
    }

    public function scopeKycPending($query) {
        return $query->where('kv', Status::KYC_PENDING);
    }

    public function scopeEmailVerified($query) {
        return $query->where('ev', Status::VERIFIED);
    }

    public function scopeMobileVerified($query) {
        return $query->where('sv', Status::VERIFIED);
    }

    public function scopeWithBalance($query) {
        return $query->where('balance', '>', 0);
    }

    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class);
    }

    public function switchToAccount(UserAccount $account): void
    {
        abort_if($account->user_id !== $this->id, 403);

        $this->accounts()->update(['is_primary' => 0]);

        $account->forceFill([
            'is_primary' => 1,
            'status' => Status::ENABLE,
        ])->save();

        $this->forceFill([
            'account_number' => $account->account_number,
            'balance' => $account->balance,
        ])->saveQuietly();
    }
}
