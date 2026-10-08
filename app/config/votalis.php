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
    | Plafonds de contribution (CDC 4.7). Valeurs par défaut du cahier des charges,
    | surchargeables par l'environnement. Les comptes de moins de `new_account_days`
    | jours ont des plafonds divisés par deux.
    */
    'caps' => [
        'proposals_per_month_per_theme' => (int) env('CAP_PROPOSALS_PER_MONTH_PER_THEME', 3),
        'arguments_per_day' => (int) env('CAP_ARGUMENTS_PER_DAY', 20),
        'votes_per_day' => (int) env('CAP_VOTES_PER_DAY', 300),
        'new_account_days' => (int) env('CAP_NEW_ACCOUNT_DAYS', 7),
    ],

    /*
    | Après le premier vote, une fiche ne peut plus changer de fond : la part du
    | texte modifiée (distance d'édition / longueur) doit rester sous ce ratio.
    */
    'typo_ratio' => (float) env('PROPOSAL_TYPO_RATIO', 0.10),

    /*
    | Classements (CDC 4.10) : nombre minimal de votes pour figurer dans les onglets
    | « clivantes » et « nécessaires mais pas souhaitées », durée du cache en secondes.
    */
    'rankings' => [
        'min_votes' => (int) env('RANKINGS_MIN_VOTES', 10),
        'cache_seconds' => (int) env('RANKINGS_CACHE_SECONDS', 300),
        'per_tab' => 20,
    ],

    /*
    | Vote rapide (CDC 4.3) : tirage pondéré pour donner leur chance aux propositions
    | récentes et peu votées.
    */
    'quick_vote' => [
        'recent_days' => (int) env('QUICK_VOTE_RECENT_DAYS', 14),
        'recent_weight' => (int) env('QUICK_VOTE_RECENT_WEIGHT', 3),
        'low_votes_threshold' => (int) env('QUICK_VOTE_LOW_VOTES', 10),
        'low_votes_weight' => (int) env('QUICK_VOTE_LOW_VOTES_WEIGHT', 2),
        'sample_size' => 200,
    ],

    /*
    | Service d'embeddings interne (consensus/). Seuls les hôtes listés sont joignables :
    | aucun texte ne doit sortir du réseau privé (CDC 4.5, 9).
    */
    'embeddings' => [
        'url' => env('EMBEDDINGS_URL', 'http://localhost:8001'),
        'allowed_hosts' => array_filter(explode(',', (string) env('EMBEDDINGS_ALLOWED_HOSTS', 'embeddings,localhost,127.0.0.1'))),
        'timeout' => (float) env('EMBEDDINGS_TIMEOUT', 5.0),
        'dimension' => (int) env('EMBEDDINGS_DIMENSION', 384),
        'version' => env('EMBEDDINGS_VERSION', 'multilingual-e5-small@1'),
    ],

    /*
    | Détection de doublons au dépôt : similarité cosinus minimale et nombre de suggestions.
    | Calibrage multilingual-e5-small (8 oct. 2026) : deux formulations d'une même mesure ≈ 0,90-0,93,
    | deux mesures différentes d'un même domaine ≈ 0,85-0,88, hors sujet ≈ 0,80-0,85.
    */
    'duplicates' => [
        'threshold' => (float) env('DUPLICATES_THRESHOLD', 0.89),
        'limit' => (int) env('DUPLICATES_LIMIT', 5),
        'min_title_chars' => 10,
        'min_measure_chars' => 30,
    ],

    /*
    | Regroupement des conditions « oui, à condition que… » : similarité minimale pour fusionner.
    */
    'conditions' => [
        'threshold' => (float) env('CONDITIONS_THRESHOLD', 0.86),
    ],

    /*
    | Identifiant du commit déployé, affiché en pied de page, et URL du dépôt.
    */
    'commit' => env('APP_COMMIT'),
    'repository_url' => env('APP_REPOSITORY_URL', 'https://codeberg.org/Orfeo/votalis'),

];
