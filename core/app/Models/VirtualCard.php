<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Stripe\Issuing\Card;
use Stripe\Stripe;

class VirtualCard extends Model {
    protected $casts = [
        'address' => 'object',
    ];

    public function authorizations() {
        return $this->hasMany(Authorization::class, 'card_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query) {
        return $query->where('status', 'inactive');
    }

    public function transactions() {
        return $this->hasMany(Transaction::class, 'virtual_card_id');
    }

    public function paymentStatusBadge(): Attribute {
        return new Attribute(function () {
            if ($this->payment_status == Status::PAYMENT_SUCCESS) {
                return '<span class="badge badge--success">' . __('Paid') . '</span>';
            } else {
                return '<span class="badge badge--warning">' . __('Unpaid') . '</span>';
            }
        });
    }

    public function statusBadge(): Attribute {
        return new Attribute(function () {
            if ($this->status == 'active') {
                return '<span class="badge badge--success">' . __('Active') . '</span>';
            } else {
                return '<span class="badge badge--warning">' . __('Inactive') . '</span>';
            }
        });
    }

    public function chargeYearly() {
        try {
            $yearCardCharge  = gs('yearly_card_charge');

            if ($this->spending_limit < $yearCardCharge) {
                $yearCardCharge = $this->spending_limit;
            }

            if ($yearCardCharge <= 0) return;

            Stripe::setApiKey(stripeSecretKey());
            Card::update($this->card_id, [
                'spending_controls' => [
                    'spending_limits' => [
                        [
                            'amount'   => max(0, intval(($this->spending_limit - $yearCardCharge)  * 100)),
                            'interval' => 'all_time'
                        ]
                    ],
                ],
            ]);

            $this->spending_limit -= $yearCardCharge;
            $this->charged_at      = now();
            $this->save();

            $transaction                  = new Transaction();
            $transaction->trx_type        = '-';
            $transaction->remark          = 'virtual_card_yearly_charge';
            $transaction->trx             = getTrx();
            $transaction->user_id         = $this->user_id;
            $transaction->virtual_card_id = $this->id;
            $transaction->amount          = $yearCardCharge;
            $transaction->post_balance    = $this->spending_limit;
            $transaction->details         = 'Virutal card yearly charge';
            $transaction->save();

            notify($this->user, 'VIRTUAL_CARD_YEARLY_CHARGE', [
                'card_label' => $this->label,
                'last_four_digit' => $this->last4,
                'amount' => showAmount($yearCardCharge, currencyFormat: false),
                'card_post_balance' => showAmount($this->spending_limit, currencyFormat: false),
                'trx' => $transaction->trx,
            ]);
        } catch (\Exception $e) {
            info('API ERROR: ' . $e->getMessage());
        }
    }
}
