<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\UserNotificationSender;
use App\Models\AccountOpeningRequest;
use App\Models\BalanceTransfer;
use App\Models\Beneficiary;
use App\Models\BroadcastMessagePreset;
use App\Models\Deposit;
use App\Models\Dps;
use App\Models\Fdr;
use App\Models\Loan;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\Withdrawal;
use App\Services\AdminNotificationAiAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\FileTypeValidate;
use Illuminate\Support\Facades\DB;

class ManageUsersController extends Controller
{
    private $pageTitle;

    public function allUsers()
    {
        $this->pageTitle = 'All Accounts';
        return $this->userData();
    }

    public function profileIncomplete()
    {
        $this->pageTitle = 'Profile Incomplete Accounts';
        return $this->userData('profileIncomplete');
    }

    public function profileCompleted()
    {
        $this->pageTitle = 'Profile Completed Accounts';
        return $this->userData('profileCompleted');
    }

    public function activeUsers()
    {
        $this->pageTitle = 'Active Accounts';
        return $this->userData('active');
    }

    public function bannedUsers()
    {
        $this->pageTitle = 'Banned Accounts';
        return $this->userData('banned');
    }

    public function emailUnverifiedUsers()
    {
        $this->pageTitle = 'Email Unverified Accounts';
        return $this->userData('emailUnverified');
    }

    public function kycVerifiedUsers()
    {
        $this->pageTitle = 'KYC Unverified Accounts';
        return $this->userData('kycVerified');
    }
    public function kycUnverifiedUsers()
    {
        $this->pageTitle = 'KYC Unverified Accounts';
        return $this->userData('kycUnverified');
    }

    public function kycPendingUsers()
    {
        $this->pageTitle = 'KYC Pending Accounts';
        return $this->userData('kycPending');
    }

    public function emailVerifiedUsers()
    {
        $this->pageTitle = 'Email Verified Accounts';
        return $this->userData('emailVerified');
    }

    public function mobileUnverifiedUsers()
    {

        $this->pageTitle = 'Mobile Unverified Accounts';
        return $this->userData('mobileUnverified');
    }

    public function mobileVerifiedUsers()
    {
        $this->pageTitle = 'Mobile Verified Accounts';
        return $this->userData('mobileVerified');
    }

    protected function userData($scope = null)
    {
        $users = User::query();
        if ($scope) {
            $users = User::$scope();
        }

        if(request()->has('staff_id')) {
            $users->where('users.branch_staff_id', request()->staff_id);
        }

        if(request()->has('branch_id')) {
            $users->where('users.branch_id', request()->branch_id);
        }

        $request = request();

        $fullNameColumn = 'CONCAT(users.firstname, " ", users.lastname)';
        $users->selectRaw(' users.*, branch_staff.name as staff_name, ' . $fullNameColumn . ' AS fullname, CASE WHEN users.branch_id = 0 THEN "Online" ELSE branches.name END AS branch_name
        ')
            ->searchable(['username', 'firstname', 'lastname', 'email', 'mobile', 'account_number', $fullNameColumn])
            ->filterable()
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->leftJoin('branch_staff', 'users.branch_staff_id', '=', 'branch_staff.id')
            ->orderable();

        if ($request->has('notify')) {
            $count = $users->count();
            session()->put('FILTERED_USERS', encrypt([
                'query' => $users->toBase()->toSql(),
                'bindings' => $users->getBindings(),
                'count' => $count,
                'title' => "$this->pageTitle",
                'filter_data' => $request->filter
            ]));
            return redirect()->route('admin.users.notification.all', ['selectedUsers' => 1]);
        }

        $users = $users->dynamicPaginate();

        $pageTitle = $this->pageTitle;
        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function detail($id)
    {
        $user                          = User::with(['accounts', 'accountOpeningRequests', 'referrer'])->findOrFail($id);
        $pageTitle                     = 'Account Detail - ' . $user->username;
        $widget['total_deposit']       = Deposit::successful()->where('user_id', $user->id)->sum('amount');
        $widget['total_withdrawn']     = Withdrawal::approved()->where('user_id', $user->id)->sum('amount');
        $widget['total_transferred']   = BalanceTransfer::completed()->where('user_id', $user->id)->sum('amount');
        $widget['total_loan']          = Loan::running()->where('user_id', $user->id)->count();
        $widget['total_fdr']           = Fdr::where('user_id', $user->id)->running()->count();
        $widget['total_dps']           = Dps::where('user_id', $user->id)->running()->count();
        $widget['total_beneficiaries'] = Beneficiary::where('user_id', $user->id)->count();
        $countries                     = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $availableAccountCurrencies    = AccountOpeningRequest::currencyOptions();
        return view('admin.users.detail', compact('pageTitle', 'user', 'widget', 'countries', 'availableAccountCurrencies'));
    }

    public function storeAccount(Request $request, $id)
    {
        $request->validate([
            'account_type' => 'required|in:checking,savings,business_checking',
            'account_name' => 'nullable|string|max:140',
        ]);

        $user = User::with('accounts')->findOrFail($id);

        $account = new UserAccount();
        $account->user_id = $user->id;
        $account->account_number = generateAccountNumber();
        $account->account_type = $request->account_type;
        $account->account_name = $request->account_name ?: ucwords(str_replace('_', ' ', $request->account_type)) . ' Account';
        $account->currency_code = gs('cur_text');
        $account->currency_symbol = gs('cur_sym');
        $account->balance = 0;
        $account->status = Status::ENABLE;
        $account->is_primary = $user->accounts->isEmpty() ? 1 : 0;
        $account->save();

        if ($account->is_primary) {
            $user->switchToAccount($account);
        }

        $notify[] = ['success', 'Additional account created successfully'];
        return to_route('admin.users.detail', $user->id)->withNotify($notify);
    }

    public function approveAccountRequest($id, $requestId)
    {
        $user = User::with(['accounts', 'accountOpeningRequests'])->findOrFail($id);
        $accountRequest = $user->accountOpeningRequests()->where('status', AccountOpeningRequest::STATUS_PENDING)->findOrFail($requestId);

        $hasExistingAccount = $user->accounts->contains(fn ($account) => strtoupper((string) $account->currency_code) === strtoupper((string) $accountRequest->currency_code));
        if ($hasExistingAccount) {
            $accountRequest->forceFill([
                'status' => AccountOpeningRequest::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => optional(auth()->guard('admin')->user())->id,
            ])->save();

            $notify[] = ['error', 'The user already has an account in that currency, so the pending request was rejected.'];
            return to_route('admin.users.detail', $user->id)->withNotify($notify);
        }

        $account = new UserAccount();
        $account->user_id = $user->id;
        $account->account_number = generateAccountNumber();
        $account->account_type = 'multi_currency';
        $account->account_name = $accountRequest->currency_code . ' Multi-Currency Account';
        $account->currency_code = $accountRequest->currency_code;
        $account->currency_symbol = $accountRequest->currency_symbol ?: $accountRequest->currency_code;
        $account->balance = 0;
        $account->status = Status::ENABLE;
        $account->is_primary = 0;
        $account->save();

        $accountRequest->forceFill([
            'status' => AccountOpeningRequest::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => optional(auth()->guard('admin')->user())->id,
        ])->save();

        $notify[] = ['success', 'Multi-currency account request approved successfully.'];
        return to_route('admin.users.detail', $user->id)->withNotify($notify);
    }

    public function rejectAccountRequest($id, $requestId)
    {
        $user = User::findOrFail($id);
        $accountRequest = $user->accountOpeningRequests()->where('status', AccountOpeningRequest::STATUS_PENDING)->findOrFail($requestId);

        $accountRequest->forceFill([
            'status' => AccountOpeningRequest::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => optional(auth()->guard('admin')->user())->id,
        ])->save();

        $notify[] = ['success', 'Multi-currency account request rejected successfully.'];
        return to_route('admin.users.detail', $user->id)->withNotify($notify);
    }

    public function switchAccount($id, $accountId)
    {
        $user = User::with('accounts')->findOrFail($id);
        $account = $user->accounts()->findOrFail($accountId);

        $user->switchToAccount($account);

        $notify[] = ['success', 'Active account updated successfully'];
        return to_route('admin.users.detail', $user->id)->withNotify($notify);
    }


    public function kycDetails($id)
    {
        $pageTitle = 'KYC Details';
        $user = User::findOrFail($id);
        return view('admin.users.kyc_detail', compact('pageTitle', 'user'));
    }

    public function kycApprove($id)
    {
        $user = User::findOrFail($id);
        $user->kv = Status::KYC_VERIFIED;
        $user->save();

        notify($user, 'KYC_APPROVE', []);

        $notify[] = ['success', 'KYC approved successfully'];
        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function kycReject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required'
        ]);
        $user = User::findOrFail($id);
        $user->kv = Status::KYC_UNVERIFIED;
        $user->kyc_rejection_reason = $request->reason;
        $user->save();

        notify($user, 'KYC_REJECT', [
            'reason' => $request->reason
        ]);

        $notify[] = ['success', 'KYC rejected successfully'];
        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryArray   = (array)$countryData;
        $countries      = implode(',', array_keys($countryArray));

        $countryCode    = $request->country;
        $country        = $countryData->$countryCode->country;
        $dialCode       = $countryData->$countryCode->dial_code;

        $request->validate([
            'firstname' => 'required|string|max:40',
            'lastname' => 'required|string|max:40',
            'email' => 'required|email|string|max:40|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:40',
            'country' => 'required|in:' . $countries,
        ]);

        $exists = User::where('mobile', $request->mobile)->where('dial_code', $dialCode)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            $notify[] = ['error', 'The mobile number already exists.'];
            return back()->withNotify($notify);
        }

        $user->mobile = $request->mobile;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$country;
        $user->dial_code = $dialCode;
        $user->country_code = $countryCode;

        $user->ev = $request->ev ? Status::VERIFIED : Status::UNVERIFIED;
        $user->sv = $request->sv ? Status::VERIFIED : Status::UNVERIFIED;
        $user->ts = $request->ts ? Status::ENABLE : Status::DISABLE;
        if (!$request->kv) {
            $user->kv = Status::KYC_UNVERIFIED;
            if ($user->kyc_data) {
                foreach ($user->kyc_data as $kycData) {
                    if ($kycData->type == 'file') {
                        fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
                    }
                }
            }
            $user->kyc_data = null;
        } else {
            $user->kv = Status::KYC_VERIFIED;
        }
        $user->save();

        $notify[] = ['success', 'Account details updated successfully'];
        return back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act' => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);
        $amount = $request->amount;
        $trx = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $user->balance += $amount;

            $transaction->trx_type = '+';
            $transaction->remark = 'balance_add';

            $notifyTemplate = 'BAL_ADD';

            $notify[] = ['success', 'Balance added successfully'];
        } else {
            if ($amount > $user->balance) {
                $notify[] = ['error', $user->username . ' doesn\'t have sufficient balance.'];
                return back()->withNotify($notify);
            }

            $user->balance -= $amount;

            $transaction->trx_type = '-';
            $transaction->remark = 'balance_subtract';

            $notifyTemplate = 'BAL_SUB';
            $notify[] = ['success', 'Balance subtracted successfully'];
        }

        $user->save();

        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = 0;
        $transaction->trx =  $trx;
        $transaction->details = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx' => $trx,
            'amount' => showAmount($amount, currencyFormat: false),
            'remark' => $request->remark,
            'post_balance' => showAmount($user->balance, currencyFormat: false)
        ]);

        return back()->withNotify($notify);
    }

    public function login($id)
    {
        Auth::loginUsingId($id);
        return to_route('user.home');
    }

    public function status(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->status == Status::USER_ACTIVE) {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);
            $user->status = Status::USER_BAN;
            $user->ban_reason = $request->reason;
            $notify[] = ['success', 'Account banned successfully'];
        } else {
            $user->status = Status::USER_ACTIVE;
            $user->ban_reason = null;
            $notify[] = ['success', 'Account unbanned successfully'];
        }
        $user->save();
        return back()->withNotify($notify);
    }


    public function showNotificationSingleForm($id)
    {
        $user = User::findOrFail($id);
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.users.detail', $user->id)->withNotify($notify);
        }
        $pageTitle = 'Send Notification to ' . $user->username;
        return view('admin.users.notification_single', compact('pageTitle', 'user'));
    }

    public function sendNotificationSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required',
            'via'     => 'required|in:email,sms,push',
            'subject' => 'required_if:via,email,push',
            'image'   => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        return (new UserNotificationSender())->notificationToSingle($request, $id);
    }

    public function notificationAiPolish(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'via'     => 'required|in:email,sms,push',
            'subject' => 'nullable|string',
        ]);

        try {
            $draft = app(AdminNotificationAiAssistant::class)->polishDraft(
                $request->message,
                $request->subject,
                $request->via
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status'  => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'draft'  => $draft,
        ]);
    }

    public function showNotificationAllForm()
    {
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $notifyToUser = User::notifyToUser();
        $users        = User::active()->count();
        $pageTitle    = 'Notification to Verified Accounts';
        $hasFilteredSelection = request()->has('selectedUsers')
            || (
                session()->has('SEND_NOTIFICATION')
                && data_get(session('SEND_NOTIFICATION'), 'being_sent_to') === 'filtered_users'
                && session()->has('FILTERED_USERS')
            );

        if (session()->has('SEND_NOTIFICATION') && !request()->email_sent) {
            session()->forget('SEND_NOTIFICATION');
        }

        if (!$hasFilteredSelection) {
            session()->forget('FILTERED_USERS');
            $filterTitle = null;
        } else {
            $pageTitle = 'Send Notification';
            $filterTitle = $this->makeFilterTitle(decrypt(session('FILTERED_USERS')));
        }

        $savedPresets = class_exists(BroadcastMessagePreset::class) && \Illuminate\Support\Facades\Schema::hasTable('broadcast_message_presets')
            ? BroadcastMessagePreset::query()->orderByDesc('last_used_at')->orderByDesc('id')->limit(25)->get()
            : collect();

        return view('admin.users.notification_all', compact('pageTitle', 'users', 'notifyToUser', 'filterTitle', 'savedPresets'));
    }

    private function makeFilterTitle($selectedData)
    {
        $filterTitle = 'The notification will be sent to total <strong>' . $selectedData['count'] . '</strong> accounts from <strong>' . $selectedData['title'] . '</strong> list';
        if (count($selectedData['filter_data'] ?? [])) {
            $filterTitle .= ' where ';
            foreach ($selectedData['filter_data'] as $column => $value) {
                $filterTitle .= \Str::title(keyToTitle($column)) . ': ' . $value;

                if (end($selectedData['filter_data']) != $value) {
                    $filterTitle .= ', ';
                }
            }
        }

        return $filterTitle;
    }

    public function sendNotificationAll(Request $request)
    {
        if (session()->has('SEND_NOTIFICATION')) {
            $sessionData = session('SEND_NOTIFICATION');
            $request->merge([
                'via' => $request->input('via', $sessionData['via'] ?? null),
                'message' => $request->filled('message') ? $request->message : ($sessionData['message'] ?? null),
                'subject' => $request->filled('subject') ? $request->subject : ($sessionData['subject'] ?? null),
                'being_sent_to' => $request->input('being_sent_to', $sessionData['being_sent_to'] ?? null),
                'start' => $request->input('start', $sessionData['start'] ?? null),
                'batch' => $request->input('batch', $sessionData['batch'] ?? null),
                'cooling_time' => $request->input('cooling_time', $sessionData['cooling_time'] ?? null),
                'number_of_top_deposited_user' => $request->input('number_of_top_deposited_user', $sessionData['number_of_top_deposited_user'] ?? null),
                'number_of_days' => $request->input('number_of_days', $sessionData['number_of_days'] ?? null),
            ]);
        }

        $request->validate([
            'via'                          => 'required|in:email,sms,push',
            'message'                      => 'required',
            'subject'                      => 'required_if:via,email,push',
            'start'                        => 'required|integer|gte:1',
            'batch'                        => 'required|integer|gte:1',
            'being_sent_to'                => 'required',
            'cooling_time'                 => 'required|integer|gte:1',
            'number_of_top_deposited_user' => 'nullable|required_if:being_sent_to,topDepositedUsers|integer|gte:0',
            'number_of_days'               => 'nullable|required_if:being_sent_to,notLoginUsers|integer|gte:0',
            'image'                        => ["nullable", 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'number_of_days.required_if'               => "Number of days field is required",
            'number_of_top_deposited_user.required_if' => "Number of top deposited user field is required",
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        return (new UserNotificationSender())->notificationToAll($request);
    }

    public function countBySegment($methodName)
    {

        return User::active()->$methodName()->count();
    }

    public function list()
    {
        $query = User::active();

        if (request()->search) {
            $query->where(function ($q) {
                $q->where('email', 'like', '%' . request()->search . '%')->orWhere('username', 'like', '%' . request()->search . '%');
            });
        }
        $users = $query->orderBy('id', 'desc')->paginate(getPaginate());
        return response()->json([
            'success' => true,
            'users'   => $users,
            'more'    => $users->hasMorePages()
        ]);
    }

    public function notificationLog($id)
    {
        $user = User::findOrFail($id);
        $pageTitle = 'Notifications Sent to ' . $user->username;
        $logs = NotificationLog::where('user_id', $id)->reportQuery();
        return view('admin.reports.notification_history', compact('pageTitle', 'logs', 'user'));
    }

    public function beneficiaries($id)
    {
        $user          = User::findOrFail($id);
        $pageTitle     = 'Beneficiaries of ' . $user->username;
        $beneficiaries = Beneficiary::where('user_id', $id)->latest()->with('user', 'beneficiaryOf')->paginate(getPaginate());
        return view('admin.users.beneficiaries', compact('pageTitle', 'beneficiaries'));
    }

    public function beneficiaryDetails($id) {
        $beneficiary = Beneficiary::where('id', $id)->first();

        if (!$beneficiary) {
            return response()->json([
                'success' => false,
                'message' => "Beneficiary not found",
            ]);
        }
        $data = @$beneficiary->details;

        $html = view('components.view-form-data', compact('data'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html,
        ]);
    }
}
