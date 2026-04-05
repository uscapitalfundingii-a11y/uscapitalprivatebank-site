<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Lib\Payment;
use App\Lib\VirtualCardLib;
use App\Models\Topup;
use App\Models\Transaction;
use App\Models\VirtualCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;
use Stripe\Issuing\Card;
use Stripe\Stripe;

class VirtualCardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Virtual Cards';
        $status    = request()->status;

        $cards     = auth()->user()->virtualCards()->latest()->when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })->paginate(getPaginate());

        $gatewayCurrency = Payment::gatewayCurrency();

        return view('Template::user.vcard.index', compact('pageTitle', 'cards', 'gatewayCurrency'));
    }

    public function issue()
    {
        $pageTitle       = 'Issue Card';
        $user            = auth()->user();
        $gatewayCurrency = Payment::gatewayCurrency();

        return view('Template::user.vcard.issue', compact('pageTitle', 'user', 'gatewayCurrency'));
    }

    public function issueStore(Request $request)
    {
        $request->validate([
            'label'        => 'required|string',
            'amount'       => 'required|numeric|gt:0',
            'gateway'      => 'required_without:from_wallet',
            'currency'     => 'required_without:from_wallet',
        ]);

        $user = auth()->user();

        if ($request->from_wallet == 1) {
            if ($request->amount + gs('card_issue_fee') > $user->balance) {
                $notify[] = ['error', 'Insufficient balance'];
                return back()->withNotify($notify);
            }

            try {
                $issuedVirtualCard = VirtualCardLib::issueCard($request, $user);
            } catch (\Exception $e) {
                $message = explode('.', $e->getMessage())[0];
                $notify[] = ['error', $message];
                return back()->withNotify($notify);
            }

            $virtualCard = VirtualCardLib::createVirtualCard($user->id, $issuedVirtualCard->id);

            VirtualCardLib::issueAmountSubtract($user, $virtualCard, $request->amount);
            VirtualCardLib::cardIssueCompleted($user, $virtualCard, $request->amount);
            VirtualCardLib::issueFeeSubtract($user, $virtualCard);

            $notify[] = ['success', 'Card issued successfully'];
            return to_route('user.vcard.details', encrypt($virtualCard->id))->withNotify($notify);
        } else {
            try {
                if (!stripeSecretKey()) {
                    throw new \Exception('Invalid API key.');
                }

                $stripe        = new StripeClient(stripeSecretKey());
                $stripe->balance->retrieve();
            } catch (\Exception $e) {
                $message = explode('.', $e->getMessage())[0];
                $notify[] = ['error', $message];
                return back()->withNotify($notify);
            }

            $paymentData = Payment::handle(
                $request->gateway,
                $request->currency,
                $request->amount,
                $request->all()
            );

            session()->put('Track', $paymentData->trx);
            return to_route('user.deposit.confirm');
        }
    }

    public function details($idEncrypted)
    {
        $id = null;

        try {
            $id = decrypt($idEncrypted);
        } catch (\Exception $e) {
            abort(404);
        }

        $pageTitle       = 'Card Details';
        $card            = auth()->user()->virtualCards()->findOrFail($id);
        $transactions    = $card->transactions()->searchable(['trx'])->latest('id')->paginate(getPaginate());
        $gatewayCurrency = Payment::gatewayCurrency();

        return view('Template::user.vcard.details', compact('pageTitle', 'card', 'transactions', 'gatewayCurrency'));
    }

    public function topup(Request $request, $id)
    {
        $request->validate([
            'from_wallet' => 'nullable',
            'gateway'     => 'required_without:from_wallet',
            'currency'    => 'required_without:from_wallet',
            'amount'      => 'required|numeric|min:1'
        ]);

        $user = auth()->user();
        $virtualCard  = VirtualCard::where('user_id', $user->id)->with('user')->active()->findOrFail($id);

        if (@$request->from_wallet == 1) {
            if ($request->amount > $user->balance) {
                $notify[] = ['error', 'Insufficient balance'];
                return back()->withNotify($notify);
            }

            VirtualCardLib::updateCardForTopup($virtualCard, $request->amount);

            $notify[] = ['success', 'Topup completed successfully'];
            return back()->withNotify($notify);
        } else {
            $data = Payment::handle($request->gateway, $request->currency, $request->amount, cardId: $virtualCard->id, isTopUp: 1);
            session()->put('Track', $data->trx);
            return to_route('user.deposit.confirm');
        }
    }

    public function revealSecret(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first('password')
            ]);
        }

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password'
            ]);
        }

        $virtualCard = VirtualCard::where('id', $id)->first();
        if (!$virtualCard) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Card'
            ]);
        }

        $response = VirtualCardLib::revealCardSecret($virtualCard->card_id);

        if (!$response['status']) {
            return response()->json([
                'status' => false,
                'message' => 'Card information not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $response['data']
        ]);
    }
}
