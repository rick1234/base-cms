<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\AdminTwoFactorChallengeRequest;
use App\Models\Cms\Domain;
use App\Models\User;
use App\Support\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    private const TWO_FACTOR_USER_KEY = 'admin_two_factor.user_id';

    private const TWO_FACTOR_REMEMBER_KEY = 'admin_two_factor.remember';

    public function create(): View
    {
        session()->forget([
            self::TWO_FACTOR_USER_KEY,
            self::TWO_FACTOR_REMEMBER_KEY,
        ]);

        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
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

        if ($domainRequiresTwoFactor || $user?->hasTwoFactorEnabled()) {
            $request->session()->put(self::TWO_FACTOR_USER_KEY, $user->id);
            $request->session()->put(self::TWO_FACTOR_REMEMBER_KEY, $remember);

            Auth::logout();
            $request->session()->regenerate();

            return redirect()->route('admin.login.two-factor');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::TWO_FACTOR_USER_KEY)) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor');
    }

    public function verify(AdminTwoFactorChallengeRequest $request, TwoFactorAuthenticator $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get(self::TWO_FACTOR_USER_KEY);
        $remember = (bool) $request->session()->get(self::TWO_FACTOR_REMEMBER_KEY, false);
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        if (! $user || ! $user->is_admin || ! $user->isActive()) {
            $this->clearTwoFactorChallenge($request);

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => __('This account is not allowed to access the admin area.')]);
        }

        if (! filled($user->two_factor_secret)) {
            $this->clearTwoFactorChallenge($request);

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => __('Two-factor authentication is required for this domain. Ask an administrator to generate a key for your account.')]);
        }

        if (! $twoFactor->verify($user->two_factor_secret, $request->input('two_factor_code'))) {
            return back()->withErrors(['two_factor_code' => __('The two-factor authentication code is invalid.')]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->clearTwoFactorChallenge($request);

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

    private function clearTwoFactorChallenge(Request $request): void
    {
        $request->session()->forget([
            self::TWO_FACTOR_USER_KEY,
            self::TWO_FACTOR_REMEMBER_KEY,
        ]);
    }
}
