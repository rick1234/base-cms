<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Cms\Domain;
use App\Models\User;
use App\Support\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, TwoFactorAuthenticator $twoFactor): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = (bool) $request->boolean('remember');
        $domainRequiresTwoFactor = Domain::resolveForHost($request->getHost())?->requiresTwoFactorForBackend() ?? false;

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => __('These credentials do not match our records.')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! $request->user()?->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => __('This account is not allowed to access the admin area.')])
                ->onlyInput('email');
        }

        $user = $request->user();

        if ($domainRequiresTwoFactor && ! $user?->two_factor_secret) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['two_factor_code' => __('Two-factor authentication is required for this domain. Ask an administrator to generate a key for your account.')])
                ->onlyInput('email');
        }

        if (($domainRequiresTwoFactor || $user?->hasTwoFactorEnabled()) && ! $twoFactor->verify($user?->two_factor_secret, $request->input('two_factor_code'))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['two_factor_code' => __('The two-factor authentication code is invalid.')])
                ->onlyInput('email');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('admin_impersonator_id');

        if ($impersonatorId) {
            $impersonator = User::query()->find($impersonatorId);

            if ($impersonator && $impersonator->hasAdminPermission('admin.access')) {
                Auth::login($impersonator);
                $request->session()->regenerate();

                flash(__('Returned to original account.'))->success();

                return redirect()->route('admin.dashboard');
            }
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
