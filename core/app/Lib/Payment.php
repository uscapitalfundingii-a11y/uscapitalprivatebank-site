<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\GatewayCurrency;

class Payment
{
    public static function gatewayCurrency()
    {
        return GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();
    }

    public static function handle($gateway, $currency, $amount, $issueData = null, $cardId = null, $isTopUp = 0)
    {
        try {
            $gate = GatewayCurrency::whereHas('method', function ($gate) {
                $gate->where('status', Status::ENABLE);
            })
                ->where('method_code', $gateway)
                ->where('currency', $currency)
                ->first();

            if (!$gate) {
                throw new \Exception('Invalid gateway');
            }

            if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
                throw new \Exception('Please follow payment limit');
            }

            // Calculate charges and final amount
            $charge      = $gate->fixed_charge + ($amount * $gate->percent_charge / 100);
            $payable     = $amount + $charge;
            $finalAmount = $payable * $gate->rate;

            $user                  = auth()->user();
            $deposit                  = new Deposit();
            $deposit->user_id         = $user->id;
            $deposit->card_id         = $cardId;
            $deposit->is_card_issue   = $issueData ? 1 : 0;
            $deposit->method_code     = $gate->method_code;
            $deposit->method_currency = strtoupper($gate->currency);
            $deposit->amount          = $amount;
            $deposit->charge          = $charge;
            $deposit->rate            = $gate->rate;
            $deposit->final_amount    = $finalAmount;
            $deposit->btc_amount      = 0;
            $deposit->btc_wallet      = "";
            $deposit->trx             = getTrx();
            $deposit->success_url     = route('user.deposit.history');
            $deposit->failed_url      = route('user.deposit.history');
            $deposit->is_topup = $isTopUp;

            if ($issueData) {
                $deposit->card_issue_details = $issueData;
            }

            $deposit->save();
            $deposit->refresh();

            return $deposit;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
