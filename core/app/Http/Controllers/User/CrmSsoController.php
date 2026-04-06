<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmSsoController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $crmUrl = rtrim((string) config('services.crm_sso.url'), '/');
        $secret = (string) config('services.crm_sso.secret');
        $ttl    = max((int) config('services.crm_sso.ttl', 120), 30);

        if (!$crmUrl || !$secret) {
            $notify[] = ['error', 'CRM single sign-on is not configured yet.'];

            return to_route('user.home')->withNotify($notify);
        }

        $email = strtolower(trim((string) $request->user()->email));

        if (!$email) {
            $notify[] = ['error', 'Your account does not have a valid email address for CRM access.'];

            return to_route('user.home')->withNotify($notify);
        }

        $payload = [
            'email' => $email,
            'exp'   => now()->addSeconds($ttl)->timestamp,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature      = hash_hmac('sha256', $encodedPayload, $secret);
        $token          = $encodedPayload . '.' . $signature;

        return redirect()->away($crmUrl . '/authentication/sso?token=' . urlencode($token));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
