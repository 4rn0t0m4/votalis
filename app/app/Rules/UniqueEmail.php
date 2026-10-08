<?php

namespace App\Rules;

use App\Models\User;
use App\Support\EmailHasher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Unicité de l'e-mail par son haché, l'e-mail étant stocké chiffré. */
class UniqueEmail implements ValidationRule
{
    public function __construct(private readonly ?User $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = User::query()->where('email_hash', EmailHasher::hash($value));

        if ($this->ignore !== null) {
            $query->whereKeyNot($this->ignore->getKey());
        }

        if ($query->exists()) {
            $fail(__('validation.unique', ['attribute' => __('validation.attributes.email')]));
        }
    }
}
