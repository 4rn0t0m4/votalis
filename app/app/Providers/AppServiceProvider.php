<?php

namespace App\Providers;

use App\Auth\UserProvider;
use App\Enums\Role;
use App\Models\Argument;
use App\Models\Proposal;
use App\Models\User;
use App\Policies\AppealPolicy;
use App\Policies\ReportPolicy;
use App\Policies\VotePolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        // Types de contenu modérables, tels qu'écrits dans `reports` et dans le journal public.
        Relation::enforceMorphMap(['proposal' => Proposal::class, 'argument' => Argument::class, 'user' => User::class]);

        // Le vote porte sur une proposition sans être un modèle à part entière : Gate explicite.
        Gate::define('vote', [VotePolicy::class, 'vote']);
        Gate::define('report', [ReportPolicy::class, 'report']);
        Gate::define('appeal', [AppealPolicy::class, 'appeal']);
    }

    /** Une Gate par capacité ; chaque rôle n'a que celles que lui donne le cahier des charges. */
    private function defineGates(): void
    {
        foreach (Role::allAbilities() as $ability) {
            Gate::define($ability, fn (User $user): bool => in_array($ability, $user->role->abilities(), true));
        }

        // Signaux d'intégrité : modération et comité, plus l'administrateur technique en lecture seule (CDC section 3).
        Gate::define('view-integrity-signals', fn (User $user): bool => $user->can('moderate') || $user->can('manage-platform'));

        // Un compte suspendu lit et conteste, mais ne contribue plus (CDC section 6).
        Gate::define('participate', fn (User $user): bool => in_array('participate', $user->role->abilities(), true) && ! $user->isSuspended());
    }
}
