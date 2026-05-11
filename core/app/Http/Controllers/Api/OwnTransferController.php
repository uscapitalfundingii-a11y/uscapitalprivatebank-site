<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\OTPManager;
use App\Models\AdminNotification;
use App\Models\BalanceTransfer;
use App\Models\Beneficiary;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OwnTransferController extends Controller {

    public function transferRequest(Request $request, $id) {
        $beneficiary = Beneficiary::where('user_id', auth()->id())->find($id);
        if (!$beneficiary) {
            $notify[] = 'Beneficiary not found';
            return responseError('validation_error', $notify);
        }

        $validator = $this->validation($request, $beneficiary);
        $this->checkTransferAvailability($request->amount, $validator);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $additionalData = [
            'amount'         => $request->amount,
            'after_verified' => 'api.own.transfer.confirm',
        ];

        $otpManager = new OTPManager();
        return $otpManager->newOTP($beneficiary, $request->auth_mode, 'OWN_BANK_TRANSFER_OTP', $additionalData, true);
    }

    public function confirm($id) {
        $verification = OtpVerification::find($id);
        $beneficiary  = $verification->verifiable;
        $validator    = Validator::make(request()->all(), []);

        OTPManager::checkVerificationData($verification, Beneficiary::class, true, $validator);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        if ($beneficiary->beneficiary_type != User::class) {
            $notify[] = ['error', 'Invalid session data'];
            return responseError('validation_error', $notify);
        }

        $sender = auth()->user();
        $amount = $verification->additional_data->amount;
        $this->checkTransferAvailability($amount, $validator);

        $charge      = $this->charge($amount);

        $transfer                 = new BalanceTransfer();
        $transfer->user_id        = $sender->id;
        $transfer->trx            = getTrx();
        $transfer->beneficiary_id = $beneficiary->id;
        $transfer->amount         = $amount;
        $transfer->charge         = $charge;
        $transfer->status         = Status::TRANSFER_PENDING;
        $transfer->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $sender->id;
        $adminNotification->title     = 'New ledger-to-ledger transfer request';
        $adminNotification->click_url = urlPath('admin.transfers.details', $transfer->id);
        $adminNotification->save();

        $notify[] = 'Transfer request submitted for admin approval';
        return responseSuccess('own_transfer_requested', $notify);
    }
    private function validation($request, $beneficiary) {
        $rules     = ['amount' => "required|numeric|gt:0"];
        $rules     = mergeOtpField($rules);
        $validator = Validator::make($request->all(), $rules);

        if ($beneficiary->beneficiary_type != User::class) {
            return addCustomValidation($validator, 'balance', 'Invalid beneficiary selected');
        }
        return $validator;
    }
    private function checkTransferAvailability($amount, $validator) {

        $finalAmount = $amount + $this->charge($amount);
        $user        = auth()->user();
        $general     = gs();

        if ($amount < $general->minimum_transfer_limit) {
            return addCustomValidation($validator, 'error', 'Sorry minimum transfer limit is ' . showAmount($general->minimum_transfer_limit, currencyFormat: false));
        }

        if ($user->balance < $finalAmount) {
            return addCustomValidation($validator, 'error', 'Sorry! You don\'t have sufficient balance');
        }

        $todaysTotal = BalanceTransfer::completed()->where('user_id', $user->id)->ownBank()->whereDate('created_at', now())->sum('amount');

        if ($todaysTotal + $amount > $general->daily_transfer_limit) {
            return addCustomValidation($validator, 'error', 'Sorry you are exceeding the daily transfer limit');
        }

        $thisMonthTotal = BalanceTransfer::completed()->where('user_id', $user->id)->ownBank()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');

        if ($thisMonthTotal + $amount > $general->monthly_transfer_limit) {
            return addCustomValidation($validator, 'error', 'Sorry you are exceeding the monthly transfer limit');
        }
    }

    private function charge($amount) {
        $general       = gs();
        $percentCharge = $amount * $general->percent_transfer_charge / 100;
        return $general->fixed_transfer_charge + $percentCharge;
    }

    private function shortCodes($transfer, $sender, $recipient, $postBalance) {
        return [
            'sender'       => $sender->username,
            'recipient'    => $recipient->username,
            'amount'       => showAmount($transfer->amount, currencyFormat: false),
            'charge'       => showAmount($transfer->charge, currencyFormat: false),
            'final_amount' => showAmount($transfer->final_amount, currencyFormat: false),
            'trx'          => $transfer->trx,
            'post_balance' => showAmount($postBalance, currencyFormat: false),
        ];
    }
}
