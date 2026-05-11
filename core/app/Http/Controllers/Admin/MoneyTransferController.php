<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BalanceTransfer;
use App\Models\OtherBank;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Http\Request;

class MoneyTransferController extends Controller
{
    private $pageTitle;

    public function index($userId = null)
    {
        $this->pageTitle = 'All Transfer';
        return $this->transferData(userId: $userId);
    }

    public function pending($userId = null)
    {
        $this->pageTitle = 'Pending Transfers';
        return $this->transferData('pending', userId: $userId);
    }

    public function rejected($userId = null)
    {
        $this->pageTitle = 'Rejected Transfers';
        return $this->transferData('rejected', userId: $userId);
    }

    public function ownBank()
    {
        $this->pageTitle = 'Own Bank Transfers';
        return $this->transferData('ownBank');
    }

    public function otherBank()
    {
        $this->pageTitle = 'Other Bank Transfers';
        return $this->transferData('otherBank');
    }

    public function wireTransfer()
    {
        $this->pageTitle = 'Wire Transfers';
        return $this->transferData('wireTransfer');
    }

    protected function transferData($scope = null, $userId = null)
    {
        $pageTitle      = $this->pageTitle;
        $senderColumn   = 'users.account_number';
        $receiverColumn = 'CASE WHEN balance_transfers.beneficiary_id = 0 THEN JSON_UNQUOTE(JSON_EXTRACT(wire_transfer_data, "$[1].value")) ELSE beneficiaries.account_number END';

        $receiverBankColumn = 'CASE WHEN balance_transfers.beneficiary_id = 0 THEN "Wire Transfer" WHEN beneficiaries.beneficiary_type = ' . json_encode(User::class) . ' THEN ? WHEN beneficiaries.beneficiary_type = ' . json_encode(OtherBank::class) . ' THEN other_banks.name END';

        $finalAmountColumn = '(balance_transfers.amount + balance_transfers.charge)';

        $query = BalanceTransfer::searchable(['trx', $senderColumn, $receiverColumn]);

        if ($scope) {
            $query = $query->$scope();
        }

        $transfers = $query->selectRaw('balance_transfers.*, ' . $senderColumn . ' AS sender, ' . $receiverColumn . ' AS receiver,
        ' . $finalAmountColumn . '  AS final_amount, beneficiaries.beneficiary_type,
        ' . $receiverBankColumn . ' AS receiver_bank ', [gs('site_name')])

            ->leftJoin('users', 'balance_transfers.user_id', '=', 'users.id')
            ->leftJoin('beneficiaries', 'balance_transfers.beneficiary_id', '=', 'beneficiaries.id')
            ->leftJoin('users as users_beneficiary', function ($join) {
                $join->on('beneficiaries.beneficiary_id', '=', 'users_beneficiary.id')->where('beneficiaries.beneficiary_type', '=', User::class);
            })
            ->leftJoin('other_banks', function ($join) {
                $join->on('beneficiaries.beneficiary_id', '=', 'other_banks.id')->where('beneficiaries.beneficiary_type', OtherBank::class);
            })
            ->filterable()
            ->orderable();

        if ($userId) {
            $transfers = $transfers->where('balance_transfers.user_id', $userId);
        }


        if(request()->has('username')) {
            $transfers->where('users.username', request()->username);
        }


        $transfers = $transfers->dynamicPaginate();

        return view('admin.transfers.index', compact('pageTitle', 'transfers'));
    }

    public function details($id)
    {
        $transfer  = BalanceTransfer::where('id', $id)->with('user', 'beneficiary.beneficiaryOf')->firstOrFail();
        $pageTitle = 'Transfer Details';
        return view('admin.transfers.details', compact('pageTitle', 'transfer'));
    }

    public function complete($id)
    {
        $transfer = BalanceTransfer::where('id', $id)->with('user.activeAccount', 'beneficiary.beneficiaryOf')->firstOrFail();

        if ($transfer->status == Status::TRANSFER_COMPLETED) {
            $notify[] = ['error', 'This transfer has already been completed'];
            return back()->withNotify($notify);
        }

        if ($transfer->status != Status::TRANSFER_PENDING) {
            $notify[] = ['error', 'Only pending transfers can be completed'];
            return back()->withNotify($notify);
        }

        $debitResult = $this->debitSenderForApproval($transfer);

        if (!$debitResult['ok']) {
            $notify[] = ['error', $debitResult['message']];
            return back()->withNotify($notify);
        }

        $transfer->status = Status::TRANSFER_COMPLETED;
        $transfer->save();

        if ($transfer->beneficiary_id && $transfer->beneficiary?->beneficiary_type == User::class) {
            $recipient            = $transfer->beneficiary->beneficiaryOf;
            $recipientPostBalance = $this->creditOwnBankRecipient($transfer, $recipient);

            $shortCodes = $this->ownBankTransferShortCodes($transfer, $transfer->user, $recipient, $debitResult['post_balance']);
            notify($transfer->user, 'OWN_BANK_TRANSFER_MONEY_SEND', $shortCodes);

            $shortCodes = $this->ownBankTransferShortCodes($transfer, $transfer->user, $recipient, $recipientPostBalance);
            notify($recipient, 'OWN_BANK_TRANSFER_MONEY_RECEIVE', $shortCodes);
        } elseif ($transfer->beneficiary_id) {
            $shortCodes = $this->bankTransferShortCodes($transfer);
            notify($transfer->user, 'OTHER_BANK_TRANSFER_COMPLETE', $shortCodes);
        } else {
            $shortCodes = $this->wireTransferShortCodes($transfer);
            notify($transfer->user, 'WIRE_TRANSFER_COMPLETED', $shortCodes);
        }

        $notify[] = ['success', 'Transfer completed successfully'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request)
    {

        $request->validate([
            'reject_reason' => 'required',
            'id'            => 'required',
        ]);

        $transfer = BalanceTransfer::where('id', $request->id)->with('user', 'beneficiary.beneficiaryOf')->firstOrFail();

        if ($transfer->status != Status::TRANSFER_PENDING) {
            $notify[] = ['error', 'This transfer can\'t be rejected'];
            return back()->withNotify($notify);
        }

        $transfer->status        = Status::TRANSFER_REJECTED;
        $transfer->reject_reason = $request->reject_reason;
        $transfer->save();

        $this->refundLegacyDebitedTransfer($transfer);

        if ($transfer->beneficiary_id && $transfer->beneficiary?->beneficiary_type == User::class) {
            $shortCodes = $this->ownBankTransferShortCodes($transfer, $transfer->user, $transfer->beneficiary->beneficiaryOf, $transfer->user->balance);
            $template   = 'OTHER_BANK_TRANSFER_REJECT';
        } elseif ($transfer->beneficiary_id) {
            $shortCodes = $this->bankTransferShortCodes($transfer);
            $template   = 'OTHER_BANK_TRANSFER_REJECT';
        } else {
            $shortCodes = $this->wireTransferShortCodes($transfer);
            $template   = 'WIRE_TRANSFER_REJECTED';
        }

        notify($transfer->user, $template, $shortCodes);

        $notify[] = ['success', 'Transfer rejected successfully'];
        return back()->withNotify($notify);
    }

    private function debitSenderForApproval(BalanceTransfer $transfer): array
    {
        $existingDebit = Transaction::where('trx', $transfer->trx)
            ->where('trx_type', '-')
            ->whereIn('remark', ['own_bank_transfer', 'other_bank_transfer', 'wire_transfer'])
            ->first();

        if ($existingDebit) {
            return ['ok' => true, 'post_balance' => $existingDebit->post_balance];
        }

        $user          = $transfer->user;
        $activeAccount = $user->activeAccount;
        $available     = $activeAccount?->balance ?? $user->balance;

        if ($available < $transfer->final_amount) {
            return ['ok' => false, 'message' => 'Sender does not have sufficient balance for approval'];
        }

        if ($activeAccount) {
            $activeAccount->balance -= $transfer->final_amount;
            $activeAccount->save();
            $activeAccount->syncLegacyUserBalance();
            $postBalance = $activeAccount->balance;
        } else {
            $user->balance -= $transfer->final_amount;
            $user->save();
            $postBalance = $user->balance;
        }

        $transaction                  = new Transaction();
        $transaction->user_id         = $user->id;
        $transaction->user_account_id = $activeAccount?->id;
        $transaction->account_number  = $activeAccount?->account_number ?: $user->account_number;
        $transaction->amount          = $transfer->final_amount;
        $transaction->post_balance    = $postBalance;
        $transaction->charge          = $transfer->charge;
        $transaction->trx_type        = '-';
        $transaction->details         = $this->transferDebitDetails($transfer);
        $transaction->trx             = $transfer->trx;
        $transaction->remark          = $this->transferDebitRemark($transfer);
        $transaction->save();

        return ['ok' => true, 'post_balance' => $postBalance];
    }

    private function creditOwnBankRecipient(BalanceTransfer $transfer, User $recipient): float
    {
        $existingCredit = Transaction::where('trx', $transfer->trx)
            ->where('trx_type', '+')
            ->where('remark', 'received_money')
            ->first();

        if ($existingCredit) {
            return $existingCredit->post_balance;
        }

        $recipientAccount = UserAccount::where('user_id', $recipient->id)
            ->where('account_number', $transfer->beneficiary->account_number)
            ->first();

        if ($recipientAccount) {
            $recipientAccount->balance += $transfer->amount;
            $recipientAccount->save();
            $recipientAccount->syncLegacyUserBalance();
            $postBalance = $recipientAccount->balance;
        } else {
            $recipient->balance += $transfer->amount;
            $recipient->save();
            $postBalance = $recipient->balance;
        }

        $transaction                  = new Transaction();
        $transaction->user_id         = $recipient->id;
        $transaction->user_account_id = $recipientAccount?->id;
        $transaction->account_number  = $recipientAccount?->account_number ?: $recipient->account_number;
        $transaction->amount          = $transfer->amount;
        $transaction->post_balance    = $postBalance;
        $transaction->charge          = 0;
        $transaction->trx_type        = '+';
        $transaction->details         = 'Received transferred money';
        $transaction->remark          = 'received_money';
        $transaction->trx             = $transfer->trx;
        $transaction->save();

        return $postBalance;
    }

    private function refundLegacyDebitedTransfer(BalanceTransfer $transfer): void
    {
        $existingDebit = Transaction::where('trx', $transfer->trx)
            ->where('trx_type', '-')
            ->whereIn('remark', ['own_bank_transfer', 'other_bank_transfer', 'wire_transfer'])
            ->first();

        $existingRefund = Transaction::where('trx', $transfer->trx)
            ->where('trx_type', '+')
            ->where('remark', 'transfer_amount_refund')
            ->exists();

        if (!$existingDebit || $existingRefund) {
            return;
        }

        $user          = $transfer->user;
        $activeAccount = $user->activeAccount;

        if ($activeAccount) {
            $activeAccount->balance += $transfer->final_amount;
            $activeAccount->save();
            $activeAccount->syncLegacyUserBalance();
            $postBalance = $activeAccount->balance;
        } else {
            $user->balance += $transfer->final_amount;
            $user->save();
            $postBalance = $user->balance;
        }

        $transaction                  = new Transaction();
        $transaction->user_id         = $user->id;
        $transaction->user_account_id = $activeAccount?->id;
        $transaction->account_number  = $activeAccount?->account_number ?: $user->account_number;
        $transaction->amount          = $transfer->final_amount;
        $transaction->post_balance    = $postBalance;
        $transaction->charge          = 0;
        $transaction->trx_type        = '+';
        $transaction->remark          = 'transfer_amount_refund';
        $transaction->details         = 'Transferred amount refunded';
        $transaction->trx             = $transfer->trx;
        $transaction->save();
    }

    private function transferDebitRemark(BalanceTransfer $transfer): string
    {
        if (!$transfer->beneficiary_id) {
            return 'wire_transfer';
        }

        return $transfer->beneficiary?->beneficiary_type == User::class ? 'own_bank_transfer' : 'other_bank_transfer';
    }

    private function transferDebitDetails(BalanceTransfer $transfer): string
    {
        if (!$transfer->beneficiary_id) {
            return 'Wire Transfer';
        }

        return $transfer->beneficiary?->beneficiary_type == User::class ? 'Own bank transfer' : 'Other bank transfer';
    }

    private function ownBankTransferShortCodes($transfer, $sender, $recipient, $postBalance)
    {
        return [
            'sender'       => $sender->username,
            'recipient'    => $recipient->username,
            'recipient_account' => $transfer->beneficiary->account_number,
            'bank_name'    => gs('site_name'),
            'amount'       => showAmount($transfer->amount, currencyFormat: false),
            'charge'       => showAmount($transfer->charge, currencyFormat: false),
            'final_amount' => showAmount($transfer->final_amount, currencyFormat: false),
            'trx'          => $transfer->trx,
            'post_balance' => showAmount($postBalance, currencyFormat: false),
            'reject_reason' => $transfer->reject_reason,
        ];
    }

    private function bankTransferShortCodes($transfer)
    {
        $bank = $transfer->beneficiary->beneficiaryOf;
        return [
            "sender_account_number"    => $transfer->user->account_number,
            "sender_account_name"      => $transfer->user->username,
            "recipient_account_number" => $transfer->beneficiary->account_number,
            "recipient_account_name"   => $transfer->beneficiary->account_name,
            "sending_amount"           => showAmount($transfer->amount, currencyFormat: false),
            "charge"                   => showAmount($transfer->charge, currencyFormat: false),
            "final_amount"             => showAmount($transfer->final_amount, currencyFormat: false),
            "bank_name"                => $bank->name,
            "reject_reason"            => $transfer->reject_reason,
        ];
    }

    private function wireTransferShortCodes($transfer)
    {
        $accountName   = $transfer->wireTransferAccountName();
        $accountNumber = $transfer->wireTransferAccountNumber();

        return [
            "sender_account_number"    => $transfer->user->account_number,
            "sender_account_name"      => $transfer->user->username,
            "recipient_account_number" => $accountNumber,
            "recipient_account_name"   => $accountName,
            "sending_amount"           => showAmount($transfer->amount, currencyFormat: false),
            "charge"                   => showAmount($transfer->charge, currencyFormat: false),
            "final_amount"             => showAmount($transfer->final_amount, currencyFormat: false),
            "reject_reason"            => $transfer->reject_reason,
        ];
    }
}
