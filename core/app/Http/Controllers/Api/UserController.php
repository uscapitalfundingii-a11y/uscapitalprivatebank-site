<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\BalanceTransfer;
use App\Models\Deposit;
use App\Models\DeviceToken;
use App\Models\Dps;
use App\Models\Fdr;
use App\Models\Form;
use App\Models\Loan;
use App\Models\NotificationLog;
use App\Models\ReferralSetting;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller {
    public function dashboard() {
        $user = auth()->user()->makeVisible('balance', 'kyc_data');

        $widget['total_deposit']  = Deposit::where('user_id', $user->id)->where('status', 1)->sum('amount');
        $widget['total_fdr']      = Fdr::where('user_id', $user->id)->count();
        $widget['total_withdraw'] = Withdrawal::approved()->where('user_id', $user->id)->sum('amount');
        $widget['total_loan']     = Loan::approved()->where('user_id', $user->id)->count();
        $widget['total_dps']      = Dps::where('user_id', $user->id)->count();
        $widget['total_trx']      = Transaction::where('user_id', $user->id)->count();

        $credits = Transaction::where('user_id', $user->id)->where('trx_type', '+')->apiQuery();
        $debits  = Transaction::where('user_id', $user->id)->where('trx_type', '-')->apiQuery();

        $filePath = getFilePath('userProfile');

        $referralLink = route('home') . '?reference=' . $user->username;

        $notify[] = 'User dashboard data';

        $data = [
            'user'           => $user,
            'dashboard_data' => $widget,
            'latest_credits' => $credits,
            'latest_debits'  => $debits,
            'filePath'       => $filePath,
            'referral_link' => $referralLink,
        ];

        return responseSuccess('dashboard', $notify, $data);
    }

    public function userDataSubmit(Request $request) {
        $user = auth()->user();
        if ($user->profile_complete == Status::YES) {
            $notify[] = 'You\'ve already completed your profile';
            return responseError('already_completed', $notify);
        }

        $countryData  = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        $validator = Validator::make($request->all(), [
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'username'     => 'required|unique:users|min:6',
            'mobile'       => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
            'image'        => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'address'      => 'nullable|string',
            'state'        => 'nullable|string',
            'city'         => 'nullable|string',
            'zip'          => 'nullable|string',
        ]);

        if ($validator->fails()) return responseError('validation_error', $validator->errors());

        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = 'No special character, space or capital letters in username';
            return responseError('validation_error', $notify);
        }

        if ($request->hasFile('image')) {
            try {
                $old         = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $e) {
                $notify[] = 'Couldn\'t upload your image';
                return responseError('validation_error', $notify);
            }
        }

        $user->country_code     = $request->country_code;
        $user->mobile           = $request->mobile;
        $user->username         = $request->username;

        $user->address          = $request->address;
        $user->city             = $request->city;
        $user->state            = $request->state;
        $user->zip              = $request->zip;
        $user->country_name     = @$request->country;
        $user->dial_code        = $request->mobile_code;

        $user->profile_complete = Status::YES;
        $user->save();

        $notify[] = 'Profile completed successfully';
        return responseSuccess('profile_completed', $notify, ['user' => $user]);
    }

    public function kycForm() {
        if (auth()->user()->kv == Status::KYC_PENDING) {
            $notify[] = 'Your KYC is under review';
            return responseError('under_review', $notify);
        }
        if (auth()->user()->kv == Status::KYC_VERIFIED) {
            $notify[] = 'You are already KYC verified';
            return responseError('already_verified', $notify);
        }
        $form = Form::where('act', 'kyc')->first();
        $notify[] = 'KYC field is below';
        return responseSuccess('kyc_form', $notify, ['form' => $form->form_data]);
    }

    public function kycSubmit(Request $request) {
        $form = Form::where('act', 'kyc')->first();
        if (!$form) {
            $notify[] = 'Invalid KYC request';
            return responseError('invalid_request', $notify);
        }
        $formData = $form->form_data;
        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }
        $user = auth()->user();
        foreach (@$user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
            }
        }
        $userData = $formProcessor->processFormData($request, $formData);

        $user->kyc_data = $userData;
        $user->kyc_rejection_reason = null;
        $user->kv = Status::KYC_PENDING;
        $user->save();

        $notify[] = 'KYC data submitted successfully';
        return responseSuccess('kyc_submitted', $notify, ['kyc_data' => $user->kyc_data]);
    }

    public function depositHistory(Request $request) {
        $deposits = auth()->user()->deposits();
        if ($request->search) {
            $deposits = $deposits->where('trx', $request->search);
        }
        $deposits = $deposits->with(['gateway'])->orderBy('id', 'desc')->apiQuery();
        $notify[] = 'Deposit data';
        return responseSuccess('deposits', $notify, ['deposits' => $deposits]);
    }

    public function transactions(Request $request) {
        $remarks = Transaction::distinct('remark')->get('remark');
        $transactions = Transaction::where('user_id', auth()->id());

        if ($request->search) {
            $transactions = $transactions->where('trx', $request->search);
        }

        if ($request->type) {
            $type = $request->type == 'plus' ? '+' : '-';
            $transactions = $transactions->where('trx_type', $type);
        }

        if ($request->remark) {
            $transactions = $transactions->where('remark', $request->remark);
        }

        $transactions = $transactions->orderBy('id', 'desc')->apiQuery();
        $notify[] = 'Transactions data';
        return responseSuccess('transactions', $notify, [
            'transactions' => $transactions,
            'remarks' => $remarks,
        ]);
    }

    public function transferHistory() {
        $transfers = BalanceTransfer::where('user_id', auth()->id())->with('beneficiary', 'beneficiary.beneficiaryOf')->apiQuery();
        $notify[]  = 'User transfer history';
        $path      = getFilePath('verify');

        return responseSuccess('transfer_history', $notify, [
            'transfers' => $transfers,
            'path'      => $path,
        ]);
    }

    public function submitProfile(Request $request) {
        $validator = Validator::make($request->all(), [
            'firstname'    => 'required',
            'lastname'     => 'required',
            'image'        => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required'  => 'The last name field is required'
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();

        if ($request->hasFile('image')) {
            try {
                $old         = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $e) {
                $notify[] = 'Couldn\'t upload your image';
                return responseError('validation_error', $notify);
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;

        $user->address   = $request->address;
        $user->city      = $request->city;
        $user->state     = $request->state;
        $user->zip       = $request->zip;

        $user->save();

        $notify[] = 'Profile updated successfully';
        return responseSuccess('profile_updated', $notify);
    }

    public function submitPassword(Request $request) {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => ['required', 'confirmed', $passwordValidation]
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = 'Password changed successfully';
            return responseSuccess('password_changed', $notify);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return responseError('validation_error', $notify);
        }
    }

    public function referredUsers() {
        $maxLevel  = ReferralSetting::max('level');
        $relations[0] = 'allReferees';

        for ($label = 1; $label < $maxLevel; $label++) {
            $relations[$label] = $relations[$label - 1] . '.allReferees';
        }

        $user      = auth()->user()->load($relations);
        $referrals = getReferees($user, $maxLevel);
        $notify[]  = 'My Referrals';

        return responseSuccess('referred_users', $notify, ['referrals' => $referrals]);
    }

    public function addDeviceToken(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            $notify[] = 'Token already exists';
            return responseError('validation_error', $notify);
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::YES;
        $deviceToken->save();

        $notify[] = 'Token saved successfully';
        return responseSuccess('token_saved', $notify);
    }


    public function show2faForm() {
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);

        $notify[] = '2FA Qr';
        return responseSuccess('2fa_qr', $notify, [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function create2fa(Request $request) {
        $validator = Validator::make($request->all(), [
            'secret' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();
        $response = verifyG2fa($user, $request->code, $request->secret);
        if ($response) {
            $user->tsc = $request->secret;
            $user->ts = Status::ENABLE;
            $user->save();

            $notify[] = 'Google authenticator activated successfully';
            return responseSuccess('2fa_qr', $notify);
        } else {
            $notify[] = 'Wrong verification code';
            return responseError('wrong_verification', $notify);
        }
    }

    public function disable2fa(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $user = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts = Status::DISABLE;
            $user->save();
            $notify[] = 'Two factor authenticator deactivated successfully';
            return responseSuccess('2fa_qr', $notify);
        } else {
            $notify[] = 'Wrong verification code';
            return responseError('wrong_verification', $notify);
        }
    }

    public function pushNotifications() {
        $notifications = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->apiQuery();
        $notify[] = 'Push notifications';
        return responseSuccess('notifications', $notify, [
            'notifications' => $notifications
        ]);
    }

    public function pushNotificationsRead($id) {
        $notification = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->find($id);
        if (!$notification) {
            $notify[] = 'Notification not found';
            return responseError('notification_not_found', $notify);
        }

        $notification->user_read = Status::YES;
        $notification->save();

        $notify[] = 'Notification marked as read successfully';
        return responseSuccess('notification_read', $notify);
    }


    public function userInfo() {
        $notify[] = 'User information';

        return responseSuccess('user_info', $notify, [
            'user' => auth()->user(),
            'referral_link' => route('home') . '?reference=' . auth()->user()->username
        ]);
    }

    public function deleteAccount() {
        $user = auth()->user();
        $user->username = 'deleted_' . $user->username;
        $user->email = 'deleted_' . $user->email;
        $user->provider_id = 'deleted_' . $user->provider_id;
        $user->save();

        $user->tokens()->delete();

        $notify[] = 'Account deleted successfully';
        return responseSuccess('account_deleted', $notify);
    }
}
