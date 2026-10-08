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
        'reports_per_day' => (int) env('CAP_REPORTS_PER_DAY', 10),
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
    | Modération (CDC section 6) : délai de contestation d'une décision, délai laissé à
    | l'auteur pour reformuler, nombre d'entrées par page du journal public.
    */
    'moderation' => [
        'appeal_days' => (int) env('APPEAL_DAYS', 14),
        'rewrite_days' => (int) env('REWRITE_DAYS', 14),
        'log_per_page' => 50,
        'queue_per_page' => 50,
    ],

    /*
    | Signaux d'intégrité (CDC section 7). Les seuils vivent dans la configuration privée
    | (environnement) et JAMAIS dans le dépôt : un seuil absent désactive le signal.
    | Calcul nocturne par `integrity:scan`, présentation aux modérateurs, aucune action automatique.
    */
    'integrity' => [
        // Inscriptions sur 24 h au-delà desquelles un signal est levé.
        'registration_spike' => env('INTEGRITY_REGISTRATION_SPIKE') !== null ? (int) env('INTEGRITY_REGISTRATION_SPIKE') : null,
        // Votes sur une même proposition en 24 h.
        'vote_spike' => env('INTEGRITY_VOTE_SPIKE') !== null ? (int) env('INTEGRITY_VOTE_SPIKE') : null,
        // Comptes de moins de `identical_voting_account_days` jours partageant au moins
        // `identical_voting_min_shared` votes strictement identiques, à partir de `identical_voting_min_accounts` comptes.
        'identical_voting_min_shared' => env('INTEGRITY_IDENTICAL_VOTING_MIN_SHARED') !== null ? (int) env('INTEGRITY_IDENTICAL_VOTING_MIN_SHARED') : null,
        'identical_voting_min_accounts' => (int) env('INTEGRITY_IDENTICAL_VOTING_MIN_ACCOUNTS', 2),
        'identical_voting_account_days' => (int) env('INTEGRITY_IDENTICAL_VOTING_ACCOUNT_DAYS', 30),
        // Similarité cosinus minimale entre deux propositions de comptes différents déposées en 24 h.
        'duplicate_content_similarity' => env('INTEGRITY_DUPLICATE_CONTENT_SIMILARITY') !== null ? (float) env('INTEGRITY_DUPLICATE_CONTENT_SIMILARITY') : null,
        // Part des votes des 24 h émis entre `night_start` et `night_end` (heures) au-delà de laquelle un signal est levé,
        // à partir de `night_min_votes` votes.
        'night_share' => env('INTEGRITY_NIGHT_SHARE') !== null ? (float) env('INTEGRITY_NIGHT_SHARE') : null,
        'night_min_votes' => (int) env('INTEGRITY_NIGHT_MIN_VOTES', 50),
        'night_start' => (int) env('INTEGRITY_NIGHT_START', 2),
        'night_end' => (int) env('INTEGRITY_NIGHT_END', 6),
        'window_hours' => 24,
    ],

    /*
    | Conservation (CDC section 8) : un compte sans visite connectée depuis `inactive_months`
    | mois est prévenu `notice_days` jours avant d'être supprimé.
    */
    'retention' => [
        'inactive_months' => (int) env('RETENTION_INACTIVE_MONTHS', 36),
        'notice_days' => (int) env('RETENTION_NOTICE_DAYS', 30),
    ],

    /*
    | Mentions légales et politique de confidentialité : responsable de traitement et hébergeur,
    | à renseigner dans l'environnement (jamais de valeur de production dans le dépôt).
    */
    'legal' => [
        'controller_name' => env('LEGAL_CONTROLLER_NAME', '[Responsable de traitement à compléter]'),
        'controller_address' => env('LEGAL_CONTROLLER_ADDRESS', '[Adresse à compléter]'),
        'contact_email' => env('LEGAL_CONTACT_EMAIL', '[contact à compléter]'),
        'dpo_email' => env('LEGAL_DPO_EMAIL'),
        'host_name' => env('LEGAL_HOST_NAME', '[Hébergeur européen à compléter]'),
        'host_address' => env('LEGAL_HOST_ADDRESS', '[Adresse de l’hébergeur à compléter]'),
        'publication_director' => env('LEGAL_PUBLICATION_DIRECTOR', '[Directeur de la publication à compléter]'),
    ],

    /*
    | Cache des pages publiques pour les visiteurs non connectés, en secondes (0 : désactivé).
    */
    'cache' => [
        'public_seconds' => (int) env('PUBLIC_CACHE_SECONDS', 60),
    ],

    /*
    | Identifiant du commit déployé, affiché en pied de page, et URL du dépôt.
    */
    'commit' => env('APP_COMMIT'),
    'repository_url' => env('APP_REPOSITORY_URL', 'https://github.com/4rn0t0m4/votalis'),

];
