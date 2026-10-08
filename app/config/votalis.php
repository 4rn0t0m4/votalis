<?php

return [

    /*
    | Clé HMAC dédiée au haché des e-mails. Distincte d'APP_KEY pour pouvoir
    | être tournée indépendamment. Générée par `php artisan votalis:hash-key`.
    */
    'email_hash_key' => env('EMAIL_HASH_KEY'),

    /*
    | Liste des domaines d'e-mails jetables refusés à l'inscription.
    | Mise à jour par `php artisan votalis:update-disposable-domains`.
    */
    'disposable_domains_path' => resource_path('data/disposable-domains.txt'),
    'disposable_domains_source' => env(
        'DISPOSABLE_DOMAINS_SOURCE',
        'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf'
    ),

    /*
    | Verrouillage progressif après échecs de connexion : nombre d'échecs
    | avant verrouillage, puis durées successives en secondes.
    */
    'lockout' => [
        'failures' => (int) env('LOCKOUT_FAILURES', 5),
        'durations' => [60, 300, 900],
        'window' => 3600,
    ],

    /*
    | Identifiant du commit déployé, affiché en pied de page, et URL du dépôt.
    */
    'commit' => env('APP_COMMIT'),
    'repository_url' => env('APP_REPOSITORY_URL', 'https://codeberg.org/Orfeo/votalis'),

];
