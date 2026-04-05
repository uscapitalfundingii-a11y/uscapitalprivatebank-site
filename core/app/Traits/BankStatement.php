<?php

namespace App\Traits;

use App\Models\Transaction;
use App\Models\User;
use DateTime;

trait BankStatement
{
    public $actionBy;
    public $user;
    public $pageTitle;
    public $view;

    public function statement($account = null)
    {
        $user = $account ? $this->getUser($account) : $this->user;

        if (!$user) {
            abort(404);
        }

        $pageTitle = $this->pageTitle;

        if (request()->today) {
            request()->merge(['date' => now()->today()->format('F d, Y')]);
        }

        $transactions = collect([]);

        if (request()->date) {
            $transactions = Transaction::where('user_id', $user->id)->filter(['trx_type'])->dateFilter()->filterable()->orderBy('id', 'desc')->paginate(getPaginate());
        }

        if ($this->actionBy == 'user') {
            return view($this->view, compact('pageTitle', 'transactions'));
        }

        return view($this->view, compact('pageTitle', 'transactions', 'user'));
    }

    public function statementDownload($account = null)
    {
        $user = $account ? $this->getUser($account) : $this->user;
        if (!$user) {
            abort(404);
        }

        $date = request()->date;
        if (blank($date)) {
            $notify[] = ['warning', 'Please select the statement period'];
            return back()->withNotify($notify);
        }

        $explodeDate   = explode('-', $date);
        $startDateTime = new DateTime(trim($explodeDate[0]));
        $endDateTime   = new DateTime(trim($explodeDate[1]));
        $dayDifference = $startDateTime->diff($endDateTime)->days;

        if ($dayDifference > 365) {
            $notify[] = ['warning', 'You can generate a maximum of 365 days of statements'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Statement';
        if (request()->today) {
            request()->merge(['date' => now()->today()->format('F d, Y')]);
        }

        $transactions   = Transaction::where('user_id', $user->id)->dateFilter()->get();
        $plusSumAmount  = $transactions->where('trx_type', '+')->sum('amount');
        $minusSumAmount = $transactions->where('trx_type', '-')->sum('amount');

        if ($this->actionBy == 'branch_staff') {
            $statementFee = gs('statement_fee') ?? 0;

            if ($statementFee) {
                $user->balance -= $statementFee;
                $user->save();
                $this->createTransactionLog($user, $statementFee);
            }
        }

        return downloadPDF('pdf.statement', compact('pageTitle', 'transactions', 'user', 'plusSumAmount', 'minusSumAmount'));
    }

    private function getUser($account)
    {
        return User::where('account_number', $account)->first();
    }

    private function createTransactionLog($user, $amount)
    {
        $transaction                  = new Transaction();
        $transaction->user_id         = $user->id;
        $transaction->amount          = $amount;
        $transaction->post_balance    = $user->balance;
        $transaction->charge          = 0;
        $transaction->trx_type        = '-';
        $transaction->details         = 'Statement download form ' . authStaff()->branch()->name . ' branch';
        $transaction->trx             = getTrx();
        $transaction->branch_id       = authStaff()->branch()->id;
        $transaction->branch_staff_id = authStaff()->id;
        $transaction->remark          = 'statement_download_charge';
        $transaction->save();
    }
}
