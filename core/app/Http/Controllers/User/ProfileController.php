<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserAccount;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function profile()
    {
        $pageTitle = "Profile Setting";
        $user = auth()->user()->load('accounts');
        return view('Template::user.profile_setting', compact('pageTitle', 'user'));
    }

    public function accounts()
    {
        $pageTitle = 'My Accounts';
        $user = auth()->user()->load('accounts');
        return view('Template::user.accounts', compact('pageTitle', 'user'));
    }

    public function accountTransactions($id)
    {
        $user = auth()->user()->load('accounts');
        $account = $user->accounts()->findOrFail($id);
        $pageTitle = 'Account Transactions';
        $transactions = $this->accountTransactionsQuery($user, $account)
            ->searchable(['trx'])
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('Template::user.account_transactions', compact('pageTitle', 'user', 'account', 'transactions'));
    }

    public function accountTransactionDetail($accountId, $transactionId)
    {
        $user = auth()->user()->load('accounts');
        $account = $user->accounts()->findOrFail($accountId);
        $pageTitle = 'Transaction Details';
        $transaction = $this->accountTransactionsQuery($user, $account)->where('id', $transactionId)->firstOrFail();

        return view('Template::user.account_transaction_detail', compact('pageTitle', 'user', 'account', 'transaction'));
    }

    protected function accountTransactionsQuery($user, UserAccount $account)
    {
        return Transaction::where('user_id', $user->id)
            ->where(function ($query) use ($user, $account) {
                $query->where('user_account_id', $account->id)
                    ->orWhere('account_number', $account->account_number);

                if ($user->accounts->count() === 1) {
                    $query->orWhere(function ($legacy) {
                        $legacy->whereNull('user_account_id')->whereNull('account_number');
                    });
                }
            });
    }

    public function switchAccount($id)
    {
        $user = auth()->user()->load('accounts');
        $account = $user->accounts()->findOrFail($id);

        $user->switchToAccount($account);

        $notify[] = ['success', 'Active account switched successfully'];
        return back()->withNotify($notify);
    }

    public function submitProfile(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required'
        ]);

        $user = auth()->user();

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;

        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return back()->withNotify($notify);
    }

    public function changePassword()
    {
        $pageTitle = 'Change Password';
        return view('Template::user.password', compact('pageTitle'));
    }

    public function submitPassword(Request $request)
    {

        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', $passwordValidation]
        ]);

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = ['success', 'Password changed successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'The password doesn\'t match!'];
            return back()->withNotify($notify);
        }
    }
}
