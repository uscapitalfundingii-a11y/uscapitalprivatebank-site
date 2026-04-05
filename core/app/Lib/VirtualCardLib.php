<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\Topup;
use App\Models\Transaction;
use App\Models\VirtualCard;
use Illuminate\Validation\ValidationException;
use Stripe\Issuing\Authorization as StripeAuthorization;
use Stripe\Issuing\Card;
use Stripe\Issuing\Cardholder;
use Stripe\Stripe;
use Stripe\StripeClient;

class VirtualCardLib
{
    public static function transactionCreated($transaction1)
    {
        try {
            $virtualCard = VirtualCard::where('card_id', $transaction1->card)->first();

            if (!$virtualCard) throw new \Exception('Virtual Card Not Found');

            $virtualCard->current_spend += -1 * floatval($transaction1->amount / 100);
            $virtualCard->save();
            $virtualCard->refresh();

            $transaction                     = new Transaction();
            $transaction->trx_type           = '-';
            $transaction->stripe_transaction = Status::YES;
            $transaction->trx                = $transaction1->balance_transaction;
            $transaction->remark             = 'purchase';
            $transaction->details            = 'Purchase via ' . $transaction1->merchant_data['name'];
            $transaction->user_id            = $virtualCard->user_id;
            $transaction->virtual_card_id    = $virtualCard->id;
            $transaction->amount             = -1 * floatval($transaction1->amount / 100);
            $transaction->post_balance       = $virtualCard->spending_limit - $virtualCard->current_spend;
            $transaction->save();

            notify($virtualCard->user, 'CARD_TRANSACTION', [
                'last4'                  => $virtualCard->last4,
                'current_spend'          => showAmount($virtualCard->current_spend),
                'spending_limit'         => showAmount($virtualCard->spending_limit),
                'current_spending_limit' => showAmount($virtualCard->spending_limit -  $virtualCard->current_spend),
                'name'                   => $virtualCard->name,
                'fullname'               => $virtualCard->user->fullname,
                'transaction_trx'        => $transaction1->balance_transaction,
                'merchant_url'           => $transaction1->merchant_data['url'],
            ]);
        } catch (\Exception $e) {
            info('Virtual Card Transaction Created Error: ' . $e->getMessage());
        }
    }

    public static function createVirtualCard($userId, $issuedCardId)
    {
        Stripe::setApiKey(stripeSecretKey());
        $issuedVirtualCard = Card::retrieve($issuedCardId);
        $cardHolder        = $issuedVirtualCard->cardholder;

        $virtualCard                 = new VirtualCard();
        $virtualCard->user_id        = $userId;
        $virtualCard->label       = @$cardHolder?->metadata['nickname'];
        $virtualCard->name           = $cardHolder->name;
        $virtualCard->currency       = 'usd';
        $virtualCard->spending_limit = $issuedVirtualCard->spending_controls['spending_limits'][0]['amount'] / 100;
        $virtualCard->brand          = $issuedVirtualCard->brand;
        $virtualCard->card_id        = $issuedVirtualCard->id;
        $virtualCard->last4          = $issuedVirtualCard->last4;
        $virtualCard->exp_month      = $issuedVirtualCard->exp_month;
        $virtualCard->exp_year       = $issuedVirtualCard->exp_year;
        $virtualCard->phone_number   = $cardHolder->phone_number;
        $virtualCard->cardholder_id  = $cardHolder->id;
        $virtualCard->status         = $issuedVirtualCard->status;
        $virtualCard->payment_status = Status::PAYMENT_SUCCESS;
        $virtualCard->address        = $cardHolder->billing['address'];
        $virtualCard->save();

        return $virtualCard;
    }

    // update card's information after a successful topup
    public static function updateCardForTopup($virtualCard, $amount, $deposit = null, $throwExp = true)
    {
        $user = $virtualCard->user;
        $spendingLimit = (intval($virtualCard->spending_limit) + intval($amount)) * 100;

        try {
            Stripe::setApiKey(stripeSecretKey());
            Card::update($virtualCard->card_id, [
                'spending_controls' => [
                    'spending_limits' => [
                        [
                            'amount'   => $spendingLimit,
                            'interval' => 'all_time'
                        ]
                    ],
                ],
            ]);

            $virtualCard->spending_limit = $spendingLimit / 100;
            $virtualCard->save();

            $topup                  = new Topup();
            $topup->user_id         = $user->id;
            $topup->virtual_card_id = $virtualCard->id;
            $topup->deposit_id      = $deposit->id ?? 0;
            $topup->trx             = $deposit->trx ?? getTrx();
            $topup->amount          = $amount;
            $topup->save();

            if ($deposit) {
                $deposit->success_url = route('user.transaction.history');
                $deposit->save();
            }

            $user->balance -= $amount;
            $user->save();

            // user's transaction
            $transactions               = new Transaction();
            $transactions->user_id      = $user->id;
            $transactions->amount       = $amount;
            $transactions->trx_type     = '-';
            $transactions->remark       = 'virtual_card_topup';
            $transactions->details      = 'Virtual card topup';
            $transactions->trx          = $topup->trx;
            $transactions->post_balance = $user->balance;
            $transactions->save();

            notify($user, 'VIRTUAL_CARD_TOPUP_AMOUNT_SUBTRACT', [
                'card_label'   => $virtualCard->label,
                'last4'        => $virtualCard->last4,
                'topup_amount' => showAmount($amount, currencyFormat: false),
                'post_balance' => showAmount($user->balance, currencyFormat: false),
                'trx'          => $topup->trx
            ]);

            // card's transaction
            $transaction                  = new Transaction();
            $transaction->user_id         = $user->id;
            $transaction->virtual_card_id = $virtualCard->id;
            $transaction->amount          = $amount;
            $transaction->post_balance    = $virtualCard->spending_limit - $virtualCard->current_spend;
            $transaction->charge          = $deposit->charge ?? 0;
            $transaction->trx_type        = '+';
            $transaction->remark          = 'virtual_card_topup';
            $transaction->details         = 'Virtual card topup Via ' . ($deposit ? $deposit->methodName() : gs('site_name') . ' wallet');
            $transaction->trx             = $topup->trx;
            $transaction->save();

            notify($user, 'VIRTUAL_CARD_TOPUP_COMPLETE', [
                'card_label'             => $virtualCard->label,
                'last4'                  => $virtualCard->last4,
                'topup_amount'           => showAmount($topup->amount, currencyFormat:false),
                'spending_limit'         => showAmount($virtualCard->spending_limit, currencyFormat:false),
                'trx'                    => $topup->trx,
            ]);
        } catch (\Exception $e) {
            if ($throwExp) {
                throw ValidationException::withMessages(['error' => $e->getMessage()]);
            }
        }
    }

    public static function authorizationRequest($authorization)
    {
        try {
            Stripe::setApiKey(stripeSecretKey());

            $authorizationId = $authorization->id;
            $auth            = StripeAuthorization::retrieve($authorizationId);
            $virtualCard     = VirtualCard::where('card_id', $auth->card->id)->first();

            if ($virtualCard) {
                $currentSpendingLimit = ($virtualCard->spending_limit - $virtualCard->current_spend) * 100; // to cents

                if ($auth->amount <= $currentSpendingLimit) {
                    info('Authorization object class: ' . get_class($auth));
                    $auth->approve();
                    info("Authorization $authorizationId approved successfully.");
                } else {
                    info("Authorization $authorizationId declined.");
                    $auth->decline();
                }
            }

            return response()->json(
                ['approved' => true],
                200,
                ['Stripe-Version' => stripeVersion()]
            );
        } catch (\Exception $e) {
            info('Error approving authorization: ' . $e->getMessage());
        }
    }

    public static function authorizationCreated($authorization)
    {
        try {
            Stripe::setApiKey(stripeSecretKey());

            $authorizationId = $authorization->id;

            info("Authorization $authorizationId created successfully.");
        } catch (\Exception $e) {
            info('Error approving authorization: ' . $e->getMessage());
        }
    }

    public static function issueCard($request, $user, $amount = null)
    {

        $countryCode = in_array($user->country_code, stripeValidCountryCodes()) ?  $user->country_code : 'US';
        $state       = in_array($user->state, countryStates($countryCode)) ? $user->state : 'OH';

        $cardHolderData = [
            'metadata' => [
                'nickname' => $request->label,
            ],
            'name'       => $user->fullname,
            'email'      => $user->email,
            'type'       => 'individual',
            'individual' => [
                'first_name' => $user->firstname,
                'last_name'  => $user->lastname,
                'dob'        => ['day' => 1, 'month' => 11, 'year' => 1981],
                'card_issuing' => [
                    'user_terms_acceptance' => [
                        'date' => time(),
                        'ip' => request()->ip(),
                    ],
                ],
            ],
            'billing' => [
                'address' => [
                    'line1'       => $user->address,
                    'city'        => $user->city,
                    'state'       => $state,
                    'postal_code' => $user->zip,
                    'country'     => $countryCode,
                ],
            ],
        ];

        Stripe::setApiKey(stripeSecretKey());
        $cardHolder = Cardholder::create($cardHolderData);
        $spendingLimit = intval($amount ?? $request->amount) * 100;
        try {
            $issuedVirtualCard = Card::create([
                'cardholder'        => $cardHolder->id,
                'status'            => gs('auto_active_card') ? 'active' : 'inactive',
                'currency'          => 'usd',
                'type'              => 'virtual',
                'spending_controls' => [
                    'spending_limits' => [
                        [
                            'amount'   => $spendingLimit,
                            'interval' => 'all_time',
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $issuedVirtualCard;
    }


    // card transactions start
    public static function issueAmountSubtract($user, $virtualCard, $amount)
    {
        $user->balance -=  $amount;
        $user->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $amount;
        $transaction->post_balance = $user->balance;
        $transaction->trx          = getTrx();
        $transaction->trx_type     = '-';
        $transaction->details      = 'Balance deducted for issue virutal card';
        $transaction->remark       = 'issue_virtual_card';
        $transaction->save();

        notify($user, 'VIRTUAL_CARD_ISSUE_AMOUNT_SUBTRACTED', [
            'label' => $virtualCard->label,
            'amount' => showAmount($amount, currencyFormat: false),
            'post_balance' => showAmount($user->balance, currencyFormat: false),
            'trx' => $transaction->trx
        ]);
    }

    public static function issueFeeSubtract($user, $virtualCard)
    {
        $user->balance -= gs('card_issue_fee');
        $user->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = gs('card_issue_fee');
        $transaction->post_balance = $user->balance;
        $transaction->trx          = getTrx();
        $transaction->trx_type     = '-';
        $transaction->details      = 'Virtual Card issue fee';
        $transaction->remark       = 'virtual_card_issue_fee';
        $transaction->save();

        notify($user, 'VIRTUAL_CARD_ISSUE_FEE_SUBTRACTED', [
            'label' => $virtualCard->label,
            'amount' => showAmount(gs('card_issue_fee'), currencyFormat: false),
            'post_balance' => showAmount($user->balance, currencyFormat: false),
            'trx' => $transaction->trx
        ]);
    }

    public static function cardIssueCompleted($user, $virtualCard, $amount)
    {
        $cardTransaction                  = new Transaction();
        $cardTransaction->virtual_card_id = $virtualCard->id;
        $cardTransaction->user_id         = $user->id;
        $cardTransaction->amount          = $virtualCard->spending_limit;
        $cardTransaction->trx             = getTrx();
        $cardTransaction->trx_type        = '+';
        $cardTransaction->details         = 'Virtual card issue amount';
        $cardTransaction->remark          = 'issue_virtual_card';
        $cardTransaction->post_balance    = $amount;
        $cardTransaction->save();

        notify($user, 'VIRTUAL_CARD_ISSUED', [
            'label' => $virtualCard->label,
            'last4' => $virtualCard->last4,
            'expire_month' => $virtualCard->exp_month,
            'expire_year' => $virtualCard->exp_year,
            'issue_amount' => showAmount($amount, currencyFormat: false),
            'spending_limit' => showAmount($virtualCard->spending_limit, currencyFormat: false),
            'trx' => $cardTransaction->trx
        ]);
    }
    // card transactions end

    // reveal secret start
    private static function createNonces()
    {
        $headers = [
            'Authorization' => "Bearer " . stripeSecretKey(),
            'Stripe-Version' => stripeVersion(),
            'Content-Type' => 'application/json',
        ];

        $headers = [
            "Authorization: Bearer " . stripeSecretKey(),
            "Stripe-Version: " . stripeVersion(),
            "Content-Type: application/json",
        ];

        try {
            $response = CurlRequest::curlPostContent("https://api.stripe.com/v1/ephemeral_key_nonces", null, $headers);
            $response = json_decode($response);

            if (!isset($response->public_nonce)) {
                return [
                    'status' => false,
                    'message' => "Failed to create nonces"
                ];
            }

            return [
                'status' => true,
                'data' => $response
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => "API ERROR: " . $e->getMessage()
            ];
        }
    }

    private static function createEphmeralKey($cardId, $publi_nonce)
    {
        try {
            $stripe = new StripeClient(stripeSecretKey());

            $ephemeralKey = $stripe->ephemeralKeys->create([
                'nonce' => $publi_nonce,
                'issuing_card' => $cardId,
            ], [
                'stripe_version' => stripeVersion(),
            ]);

            return [
                'status' => true,
                'ephemeralKey' => $ephemeralKey,
                'ephemeralKeySecret' => $ephemeralKey->secret,
                'id' => $ephemeralKey->id
            ];
        } catch (\UnexpectedValueException $e) {
            return [
                'status' => false,
                'message' => 'Unexpected value'
            ];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return [
                'status' => false,
                'message' => 'Invalid signature'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public static function revealCardSecret($cardId)
    {
        $nonces = self::createNonces();

        if (!$nonces['status']) {
            return $nonces;
        }

        $nonceData = $nonces['data'];
        $ephemeral = self::createEphmeralKey($cardId, $nonceData->public_nonce);

        if (!$ephemeral['status']) {
            return $ephemeral;
        }

        $ephemeralKeySecret = $ephemeral['ephemeralKeySecret'];

        $url = "https://api.stripe.com/v1/issuing/cards/{$cardId}?" .
            "ephemeral_key_private_nonce={$nonceData->private_nonce}&" .
            "expand[0]=number&expand[1]=cvc&expand[2]=pin.number&safe_expands=true";

        $headers = [
            "Authorization: Bearer {$ephemeralKeySecret}",
            "Stripe-Version: " . stripeVersion(),
            "Content-Type: application/json",
        ];

        try {
            $response = CurlRequest::curlContent($url, $headers);
            $data = json_decode($response, true);

            if (!isset($data['number'])) {
                return [
                    'status' => false,
                    'message' => 'Failed to reveal card details'
                ];
            }

            return [
                'status' => true,
                'data' => [
                    'card_number' => $data['number'] ?? null,
                    'card_cvc' => $data['cvc'] ?? null,
                    'card_pin' => $data['pin']['number'] ?? null
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
