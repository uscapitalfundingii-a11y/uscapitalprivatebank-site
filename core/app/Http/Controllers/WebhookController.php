<?php

namespace App\Http\Controllers;

use App\Lib\VirtualCardLib;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function stripeWebhook(Request $request)
    {
        $endpoint_secret = gs('webhook_endpoint_secret');

        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(), // Raw payload
                $sig_header,            // Stripe-Signature header
                $endpoint_secret        // Webhook secret
            );
        } catch (\UnexpectedValueException $e) {
            info('Invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            info('Invalid signature: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }


        switch ($event->type) {
            case 'issuing_transaction.created':
                VirtualCardLib::transactionCreated((object) $event->data->object);
                break;

            case 'issuing_authorization.request':
                VirtualCardLib::authorizationRequest((object) $event->data->object);
                break;

            case 'issuing_authorization.created':
                VirtualCardLib::authorizationCreated((object) $event->data->object);
                break;

            default:
                info('Unhandled event type: ' . $event->type);
                break;
        }

        return response()->json(['status' => 'success'], 200);
    }
}
