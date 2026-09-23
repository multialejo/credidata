<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $user = $request->user();
        $key = 'verification-email-resend:'.$user->getAuthIdentifier();

        $sent = RateLimiter::attempt(
            $key,
            1,
            fn () => $user->sendEmailVerificationNotification(),
            60,
        );

        if (! $sent) {
            return back()->with('status', 'verification-link-cooldown');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
