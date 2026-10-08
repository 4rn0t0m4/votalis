<?php

namespace App\Providers;

use App\Auth\UserProvider;
use App\Enums\Role;
use App\Models\User;
use App\Policies\VotePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('votalis', fn ($app, array $config) => new UserProvider($app['hash'], $config['model']));

        // 12 caractères minimum et absence des fuites connues (k-anonymat HIBP : 5 caractères de haché envoyés).
        Password::defaults(fn () => Password::min(12)->uncompromised());

        $this->defineGates();

        // Le vote porte sur une proposition sans être un modèle à part entière : Gate explicite.
        Gate::define('vote', [VotePolicy::class, 'vote']);
    }

    /** Une Gate par capacité ; chaque rôle n'a que celles que lui donne le cahier des charges. */
    private function defineGates(): void
    {
        foreach (Role::allAbilities() as $ability) {
            Gate::define($ability, fn (User $user): bool => in_array($ability, $user->role->abilities(), true));
        }
    }
}
