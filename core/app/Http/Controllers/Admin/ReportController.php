<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Transaction;
use App\Models\UserLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function fundedTransactionAudit(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'remark'     => 'nullable|string|max:80',
            'actor'      => 'nullable|in:all,no_staff,staff,sender_user,admin_adjustment,gateway_or_manual',
        ]);

        $pageTitle = 'Funded Account Transaction Audit';
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->subMonthsNoOverflow(3)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $hasUserAccounts              = Schema::hasTable('user_accounts');
        $hasTransactionUserAccountId  = Schema::hasColumn('transactions', 'user_account_id');
        $hasTransactionAccountNumber  = Schema::hasColumn('transactions', 'account_number');
        $hasTransactionBranchStaffId  = Schema::hasColumn('transactions', 'branch_staff_id');
        $hasTransactionBranchId       = Schema::hasColumn('transactions', 'branch_id');

        $query = Transaction::query()
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->leftJoin('deposits as audit_deposits', 'transactions.trx', '=', 'audit_deposits.trx')
            ->leftJoin('balance_transfers as audit_transfers', 'transactions.trx', '=', 'audit_transfers.trx')
            ->leftJoin('users as sender_users', 'audit_transfers.user_id', '=', 'sender_users.id')
            ->leftJoin('branch_staff as deposit_staff', 'audit_deposits.branch_staff_id', '=', 'deposit_staff.id')
            ->leftJoin('branches as deposit_branches', 'audit_deposits.branch_id', '=', 'deposit_branches.id');

        if ($hasTransactionBranchStaffId) {
            $query->leftJoin('branch_staff as transaction_staff', 'transactions.branch_staff_id', '=', 'transaction_staff.id');
        }

        if ($hasTransactionBranchId) {
            $query->leftJoin('branches as transaction_branches', 'transactions.branch_id', '=', 'transaction_branches.id');
        }

        if ($hasUserAccounts) {
            $query->leftJoin('user_accounts as audited_accounts', function ($join) use ($hasTransactionUserAccountId, $hasTransactionAccountNumber) {
                if ($hasTransactionUserAccountId) {
                    $join->on('transactions.user_account_id', '=', 'audited_accounts.id');

                    if ($hasTransactionAccountNumber) {
                        $join->orOn(function ($nested) {
                            $nested->on('transactions.account_number', '=', 'audited_accounts.account_number')
                                ->on('transactions.user_id', '=', 'audited_accounts.user_id');
                        });
                    }
                    return;
                }

                if ($hasTransactionAccountNumber) {
                    $join->on('transactions.account_number', '=', 'audited_accounts.account_number')
                        ->on('transactions.user_id', '=', 'audited_accounts.user_id');
                    return;
                }

                $join->on('users.account_number', '=', 'audited_accounts.account_number')
                    ->on('users.id', '=', 'audited_accounts.user_id');
            });
        }

        $accountNumberSelect = $hasTransactionAccountNumber
            ? 'COALESCE(transactions.account_number, ' . ($hasUserAccounts ? 'audited_accounts.account_number, ' : '') . 'users.account_number)'
            : ($hasUserAccounts ? 'COALESCE(audited_accounts.account_number, users.account_number)' : 'users.account_number');

        $accountSelect = $hasUserAccounts
            ? ', audited_accounts.id as audit_account_id, audited_accounts.account_name, audited_accounts.account_type, audited_accounts.currency_code, audited_accounts.currency_symbol, audited_accounts.balance as account_balance'
            : ', NULL as audit_account_id, NULL as account_name, NULL as account_type, NULL as currency_code, NULL as currency_symbol, users.balance as account_balance';

        $actorCases = [];
        if ($hasTransactionBranchStaffId) {
            $actorCases[] = 'WHEN transaction_staff.id IS NOT NULL THEN CONCAT("Branch staff: ", transaction_staff.name)';
        }
        $actorCases[] = 'WHEN deposit_staff.id IS NOT NULL THEN CONCAT("Deposit staff: ", deposit_staff.name)';
        $actorCases[] = 'WHEN sender_users.id IS NOT NULL AND transactions.remark = "received_money" THEN CONCAT("Sending user: ", sender_users.username)';
        $actorCases[] = 'WHEN audit_deposits.id IS NOT NULL AND audit_deposits.method_code >= 1000 AND audit_deposits.status = ' . Status::PAYMENT_SUCCESS . ' THEN "Manual/gateway deposit; admin approver not recorded"';
        $actorCases[] = 'WHEN audit_deposits.id IS NOT NULL THEN "Gateway deposit; payer/account session"';
        $actorCases[] = 'WHEN transactions.remark = "balance_add" THEN "Admin balance adjustment; admin ID not recorded"';

        $branchNameSelect = $hasTransactionBranchId
            ? 'COALESCE(transaction_branches.name, deposit_branches.name)'
            : 'deposit_branches.name';

        $query->selectRaw('
                transactions.*,
                users.username,
                users.firstname,
                users.lastname,
                users.email,
                users.balance as user_balance,
                ' . $accountNumberSelect . ' as audited_account_number,
                ' . $branchNameSelect . ' as audit_branch_name,
                audit_deposits.id as audit_deposit_id,
                audit_deposits.method_code as audit_deposit_method_code,
                audit_deposits.status as audit_deposit_status,
                audit_transfers.id as audit_transfer_id,
                sender_users.id as sender_user_id,
                sender_users.username as sender_username,
                CASE ' . implode(' ', $actorCases) . ' ELSE "No staff/admin actor recorded" END as actor_summary
                ' . $accountSelect . '
            ')
            ->where('transactions.trx_type', '+')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where(function ($funded) use ($hasUserAccounts) {
                $funded->where('users.balance', '>', 0);

                if ($hasUserAccounts) {
                    $funded->orWhere('audited_accounts.balance', '>', 0);
                }
            });

        if ($request->filled('remark') && $request->remark !== 'all') {
            $query->where('transactions.remark', $request->remark);
        }

        match ($request->input('actor', 'all')) {
            'no_staff' => $query
                ->when($hasTransactionBranchStaffId, fn ($q) => $q->whereNull('transactions.branch_staff_id'))
                ->whereNull('audit_deposits.branch_staff_id')
                ->where(function ($q) {
                    $q->whereNull('sender_users.id')
                        ->orWhere('transactions.remark', '!=', 'received_money');
                }),
            'staff' => $query->where(function ($q) use ($hasTransactionBranchStaffId) {
                if ($hasTransactionBranchStaffId) {
                    $q->whereNotNull('transactions.branch_staff_id')
                        ->orWhereNotNull('audit_deposits.branch_staff_id');
                    return;
                }

                $q->whereNotNull('audit_deposits.branch_staff_id');
            }),
            'sender_user' => $query->where('transactions.remark', 'received_money')->whereNotNull('sender_users.id'),
            'admin_adjustment' => $query->where('transactions.remark', 'balance_add'),
            'gateway_or_manual' => $query->where('transactions.remark', 'deposit')->whereNotNull('audit_deposits.id')
                ->when($hasTransactionBranchStaffId, fn ($q) => $q->whereNull('transactions.branch_staff_id'))
                ->whereNull('audit_deposits.branch_staff_id'),
            default => null,
        };

        $remarks = Transaction::whereNotNull('remark')
            ->where('trx_type', '+')
            ->distinct()
            ->orderBy('remark')
            ->pluck('remark');

        $summaryQuery = clone $query;
        $summary = [
            'transactions' => (clone $summaryQuery)->count('transactions.id'),
            'amount'       => (clone $summaryQuery)->sum('transactions.amount'),
        ];

        $transactions = $query
            ->orderByDesc('transactions.created_at')
            ->orderByDesc('transactions.id')
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.reports.funded_transaction_audit', compact('pageTitle', 'transactions', 'remarks', 'summary', 'startDate', 'endDate'));
    }

    public function transaction(Request $request, $username = null)
    {
        $pageTitle = 'Transaction Logs';

        $remarks = Transaction::whereNotNull('remark')->distinct('remark')->selectRaw('UCASE(REPLACE(remark, "_", " ")) AS remark_text')->orderBy('remark')->get('remark');

        $transactions = Transaction::selectRaw('transactions.*, users.account_number, users.username,
            CASE WHEN transactions.trx_type = "+" THEN "Credited" ELSE "Debited" END AS transaction_type,
            UCASE(REPLACE(remark, "_", " ")) AS remark_text')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
        ->searchable(['trx', 'username', 'account_number', 'details'])
            ->filterable()
            ->orderable();

        $username = $username ?? $request->username;

        if ($username || $request->has('username')) {
            $transactions = $transactions->where('username', $username);
        }

        if ($username || $request->has('username')) {
            $transactions = $transactions->where('username', $username);
        }
        $transactions = $transactions->dynamicPaginate();

        return view('admin.reports.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    private function getDistinctData($column)
    {
        return UserLogin::select($column)->whereNotNull($column)->where($column, '!=', "")->distinct($column)->orderBy($column)->get()->pluck($column)->toArray();
    }

    public function loginHistory(Request $request)
    {
        $pageTitle = 'User Login History';

        $countries = $this->getDistinctData('country');
        $cities = $this->getDistinctData('city');
        $browsers = $this->getDistinctData('browser');
        $allOs = $this->getDistinctData('os');

        $loginLogs = UserLogin::selectRaw('
            user_logins.*,
            users.account_number,
            users.username
        ')
            ->leftJoin('users', 'user_logins.user_id', '=', 'users.id')
            ->searchable(['users.account_number', 'users.username', 'user_ip', 'city', 'country', 'longitude', 'latitude', 'browser', 'os'])
            ->filterable()
            ->orderable()
            ->dynamicPaginate();
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'countries', 'cities', 'browsers', 'allOs'));
    }

    public function notificationHistory(Request $request)
    {
        $pageTitle = 'Notification History';
        $logs = NotificationLog::reportQuery();
        return view('admin.reports.notification_history', compact('pageTitle', 'logs'));
    }

    public function emailDetails($id)
    {
        $pageTitle = 'Email Details';
        $email = NotificationLog::findOrFail($id);
        return view('admin.reports.email_details', compact('pageTitle', 'email'));
    }
}
