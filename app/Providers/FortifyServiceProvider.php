<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginViewResponse;
use App\Http\Responses\GenericPasswordResetLinkResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse;
use Laravel\Fortify\Contracts\ResetPasswordViewResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SimpleViewResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginViewResponse::class, function () {
            return new SimpleViewResponse(function () {
                return view('auth.login');
            });
        });

        $this->app->singleton(
            RequestPasswordResetLinkViewResponse::class,
            fn () => new SimpleViewResponse(fn () => view('auth.forgot-password'))
        );

        $this->app->singleton(
            ResetPasswordViewResponse::class,
            fn () => new SimpleViewResponse(fn () => view('auth.reset-password'))
        );

        $this->app->singleton(
            GenericPasswordResetLinkResponse::class
        );

        $this->app->singleton(
            SuccessfulPasswordResetLinkRequestResponse::class,
            fn ($app) => $app->make(GenericPasswordResetLinkResponse::class)
        );

        $this->app->singleton(
            FailedPasswordResetLinkRequestResponse::class,
            fn ($app) => $app->make(GenericPasswordResetLinkResponse::class)
        );
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::updateUserProfileInformationUsing(
            UpdateUserProfileInformation::class
        );

        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class
        );

        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class
        );

        Fortify::redirectUserForTwoFactorAuthenticationUsing(
            RedirectIfTwoFactorAuthenticatable::class
        );

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->input('email'))->first();

            if (! $user || ! $user->is_active) {
                return null;
            }

            if (! Hash::check($request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username()))
                . '|'
                . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId())
                . '|'
                . $request->ip()
            );
        });
    }
}