<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\NotDisposableEmail;
use App\Rules\UniqueEmail;
use App\Rules\UniquePseudonym;
use App\Support\EmailHasher;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'pseudonym' => ['required', 'string', 'min:3', 'max:40', 'regex:'.CreateNewUser::PSEUDONYM_PATTERN, new UniquePseudonym($user)],
            'email' => ['required', 'string', 'email:rfc', 'max:254', new NotDisposableEmail, new UniqueEmail($user)],
        ])->validateWithBag('updateProfileInformation');

        $email = EmailHasher::normalize($input['email']);

        if (EmailHasher::hash($email) !== $user->email_hash) {
            $user->forceFill([
                'pseudonym' => $input['pseudonym'],
                'email' => $email,
                'email_verified_at' => null,
            ])->save();

            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill(['pseudonym' => $input['pseudonym']])->save();
        }
    }
}
