<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Unicité du pseudonyme insensible à la casse. */
class UniquePseudonym implements ValidationRule
{
    public function __construct(private readonly ?User $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = User::query()->whereRaw('lower(pseudonym) = ?', [mb_strtolower($value)]);

        if ($this->ignore !== null) {
            $query->whereKeyNot($this->ignore->getKey());
        }

        if ($query->exists()) {
            $fail(__('Ce pseudonyme est déjà utilisé.'));
        }
    }
}
