<?php

namespace App\Auth;

use App\Support\EmailHasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Fournisseur d'utilisateurs : traduit les identifiants `email` et `identifier`
 * (pseudonyme ou e-mail) en recherche sur `email_hash` ou `pseudonym`,
 * car l'e-mail est stocké chiffré et n'est jamais interrogé en clair.
 */
class UserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (isset($credentials['identifier'])) {
            $identifier = (string) $credentials['identifier'];
            unset($credentials['identifier']);

            $credentials[str_contains($identifier, '@') ? 'email' : 'pseudonym'] = $identifier;
        }

        if (isset($credentials['email'])) {
            $credentials['email_hash'] = EmailHasher::hash((string) $credentials['email']);
            unset($credentials['email']);
        }

        if (isset($credentials['pseudonym'])) {
            $pseudonym = (string) $credentials['pseudonym'];
            unset($credentials['pseudonym']);

            $query = $this->newModelQuery()->whereRaw('lower(pseudonym) = ?', [mb_strtolower($pseudonym)]);

            foreach ($credentials as $key => $value) {
                if (! str_contains($key, 'password')) {
                    $query->where($key, $value);
                }
            }

            $model = $query->first();

            return $model instanceof Authenticatable ? $model : null;
        }

        return parent::retrieveByCredentials($credentials);
    }
}
