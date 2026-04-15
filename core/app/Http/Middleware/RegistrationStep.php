<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class RegistrationStep
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $this->syncProfileCompletionFlagSafely($user);

        if ($this->needsProfileCompletionSafely($user)) {
            if ($request->is('api/*')) {
                $notify[] = 'Please complete your profile to continue';
                return response()->json([
                    'remark'=>'profile_incomplete',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                    'data' => [
                        'missing_fields' => array_values($this->missingProfileFieldsSafely($user)),
                    ],
                ]);
            }else{
                return to_route('user.data');
            }
        }
        return $next($request);
    }

    protected function missingProfileFieldsSafely(User $user): array
    {
        if (method_exists($user, 'missingProfileFields')) {
            return $user->missingProfileFields();
        }

        $requiredFields = [
            'country_name' => 'country',
            'country_code' => 'country code',
            'dial_code' => 'mobile code',
            'mobile' => 'phone number',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
        ];

        $missing = [];

        foreach ($requiredFields as $field => $label) {
            if (blank($user->{$field})) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    protected function needsProfileCompletionSafely(User $user): bool
    {
        if (method_exists($user, 'needsProfileCompletion')) {
            return $user->needsProfileCompletion();
        }

        return $user->reg_step == Status::NO || !empty($this->missingProfileFieldsSafely($user));
    }

    protected function syncProfileCompletionFlagSafely(User $user): void
    {
        if (method_exists($user, 'syncProfileCompletionFlag')) {
            $user->syncProfileCompletionFlag();
            return;
        }

        $hasMissingFields = !empty($this->missingProfileFieldsSafely($user));
        $targetStatus = $hasMissingFields ? Status::NO : Status::YES;

        if ((int) $user->reg_step !== (int) $targetStatus) {
            $user->reg_step = $targetStatus;
            $user->save();
        }
    }
}
