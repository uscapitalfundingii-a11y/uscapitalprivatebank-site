<?php

namespace App\Http\Middleware;

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
        $user->syncProfileCompletionFlag();

        if ($user->needsProfileCompletion()) {
            if ($request->is('api/*')) {
                $notify[] = 'Please complete your profile to continue';
                return response()->json([
                    'remark'=>'profile_incomplete',
                    'status'=>'error',
                    'message'=>['error'=>$notify],
                    'data' => [
                        'missing_fields' => array_values($user->missingProfileFields()),
                    ],
                ]);
            }else{
                return to_route('user.data');
            }
        }
        return $next($request);
    }
}
