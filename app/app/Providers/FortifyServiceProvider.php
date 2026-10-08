<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Auth\LoginLockout;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        $this->registerViews();
        $this->registerAuthentication();
        $this->registerRateLimiters();
    }

    private function registerViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
    }

    /**
     * Connexion par pseudonyme ou e-mail, avec verrouillage progressif après échecs.
     */
    private function registerAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $lockout = app(LoginLockout::class);
            $key = LoginLockout::key((string) $request->input(Fortify::username()), (string) $request->ip());

            if (($seconds = $lockout->remainingSeconds($key)) > 0) {
                throw ValidationException::withMessages([
                    Fortify::username() => [__('Trop de tentatives. Réessayez dans :seconds secondes.', ['seconds' => $seconds])],
                ]);
            }

            /** @var User|null $user */
            $user = app('auth')->getProvider()->retrieveByCredentials([
                'identifier' => (string) $request->input(Fortify::username()),
            ]);

            if ($user !== null && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $identifier = (string) ($event->credentials[Fortify::username()] ?? '');
            app(LoginLockout::class)->recordFailure(LoginLockout::key($identifier, (string) request()->ip()));
        });

        Event::listen(Login::class, function (): void {
            $identifier = (string) request()->input(Fortify::username(), '');
            app(LoginLockout::class)->clear(LoginLockout::key($identifier, (string) request()->ip()));
        });
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = LoginLockout::key((string) $request->input(Fortify::username()), (string) $request->ip());

            // Garde-fou brut par couple identifiant + IP ; le verrouillage progressif (LoginLockout) agit dès 5 échecs.
            return Limit::perMinute(10)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by((string) $request->session()->get('login.id')));

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(($credentialId ?: $request->session()->getId()).'|'.$request->ip());
        });

        // Appliqué par le middleware ThrottleRegistration (Fortify n'expose pas de limiteur pour l'inscription).
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by((string) $request->ip()));
    }
}
