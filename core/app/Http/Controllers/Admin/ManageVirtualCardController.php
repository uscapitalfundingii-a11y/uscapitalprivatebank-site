<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Topup;
use App\Models\Transaction;
use App\Models\VirtualCard;
use Illuminate\Http\Request;
use Stripe\Issuing\Card;
use Stripe\Stripe;

class ManageVirtualCardController extends Controller
{
    private function cardData($scopes = [])
    {
        $cards = VirtualCard::query()->searchable(['name', 'last4', 'user:username'])->orderable();

        foreach ($scopes as $scope) {
            $cards = $cards->$scope();
        }

        return $cards->dynamicPaginate();
    }

    public function index()
    {
        $pageTitle = 'All Virtual Cards';
        $cards     = $this->cardData();
        return view('admin.card.list', compact('pageTitle', 'cards'));
    }

    public function active()
    {
        $pageTitle = 'Active Virtual Cards';
        $cards     = $this->cardData(['active']);
        return view('admin.card.list', compact('pageTitle', 'cards'));
    }

    public function inactive()
    {
        $pageTitle = 'Inactive Virtual Cards';
        $cards     = $this->cardData(['inactive']);
        return view('admin.card.list', compact('pageTitle', 'cards'));
    }

    public function detail($id)
    {

        $card              = VirtualCard::with('user')->findOrFail($id);
        $pageTitle         = 'Virtual Card Details';

        $totalTransactions = Transaction::where('virtual_card_id', $card->id)->count();
        $creditedAmount    = Transaction::where('virtual_card_id', $card->id)->where('trx_type', '+')->sum('amount');
        $debitedAmount     = Transaction::where('virtual_card_id', $card->id)->where('trx_type', '-')->sum('amount');
        $totalStripeTransaction = Transaction::where('virtual_card_id', $card->id)->where('stripe_transaction', Status::YES)->sum('amount');

        return view('admin.card.detail', compact('pageTitle', 'card', 'totalTransactions', 'creditedAmount', 'debitedAmount', 'totalStripeTransaction'));
    }

    public function changeStatus(Request $request, $cardId)
    {
        $virtualCard = VirtualCard::where('id', $cardId)->firstOrFail();

        $request->validate([
            'reason' => $virtualCard->status == 'active' ? 'required|string|max:255' : 'nullable|string|max:255',
        ]);

        Stripe::setApiKey(stripeSecretKey());

        try {
            Card::update(
                $virtualCard->card_id,
                [
                    'status' => $virtualCard->status == 'active' ? 'inactive' : 'active',
                ]
            );

            $virtualCard->status = $virtualCard->status == 'active' ? 'inactive' : 'active';
            $virtualCard->save();
            $virtualCard->refresh();

            $user = $virtualCard->user;

            if ($virtualCard->status == 'active') {
                notify($user, 'VIRTUAL_CARD_ACTIVATED', [
                    'label'          => $virtualCard->label,
                    'last4'          => $virtualCard->last4,
                    'spending_limit' => showAmount($virtualCard->spending_limit, currencyFormat:false)
                ]);
            } else {
                notify($user, 'VIRTUAL_CARD_DEACTIVATED', [
                    'label'          => $virtualCard->label,
                    'last4'          => $virtualCard->last4,
                    'reason'         => $request->reason
                ]);
            }

            $notify[] = ['success', 'Card status updated successfully'];
            return back()->withNotify($notify);
        } catch (\Exception $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    public function transactions(){
        $pageTitle    = 'Virtual Card Transactions';
        $remarks      = Transaction::where('virtual_card_id', '>', 0)->distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::selectRaw('transactions.*, virtual_cards.name as card_name, CONCAT(virtual_cards.name, " (", virtual_cards.label, " ", virtual_cards.last4, ")") as card_details, CASE WHEN transactions.trx_type = "+" THEN "Credited" ELSE "Debited" END AS transaction_type, virtual_cards.last4, virtual_cards.name, virtual_cards.label')
        ->join('virtual_cards', 'virtual_cards.id', '=', 'transactions.virtual_card_id')
        ->where('virtual_card_id', '!=', 0)
        ->searchable(['trx', 'user:username', 'last4', 'name', 'label'])
        ->filterable()
        ->orderable()
        ->dynamicPaginate();

        $cards  =  Transaction::where('virtual_card_id', '!=', 0)->with('card')->get()->mapWithKeys(function($transaction){
            return [$transaction->card->id => $transaction->card->name . ' ('. $transaction->card->label . ' '. $transaction->card->last4. ')'];
        })->toArray();

        return view('admin.card.transactions', compact('pageTitle', 'transactions', 'remarks', 'cards'));
    }
}
