<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\NotDisposableEmail;
use App\Rules\UniqueEmail;
use App\Rules\UniquePseudonym;
use App\Support\EmailHasher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public const PSEUDONYM_PATTERN = '/^[\p{L}\p{N}][\p{L}\p{N}_-]{2,39}$/u';

    /**
     * Valide et crée un compte. Le consentement explicite est requis (RGPD art. 9).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'pseudonym' => ['required', 'string', 'min:3', 'max:40', 'regex:'.self::PSEUDONYM_PATTERN, new UniquePseudonym],
            'email' => ['required', 'string', 'email:rfc', 'max:254', new NotDisposableEmail, new UniqueEmail],
            'password' => $this->passwordRules(),
            'consent' => ['accepted'],
        ], [
            'pseudonym.regex' => __('Le pseudonyme ne peut contenir que des lettres, des chiffres, des tirets et des traits de soulignement.'),
            'consent.accepted' => __('Vous devez accepter le traitement de vos votes et contributions pour créer un compte.'),
        ])->validate();

        return User::create([
            'pseudonym' => $input['pseudonym'],
            'email' => EmailHasher::normalize($input['email']),
            'password' => $input['password'],
            'consented_at' => now(),
        ]);
    }
}
