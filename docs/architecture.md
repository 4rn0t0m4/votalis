# Architecture

Document tenu à jour à chaque lot. État : lot 3 complet (votes, classements, embeddings, doublons, recherche, arbitrages), 8 octobre 2026.

## Vue d'ensemble

Une application Laravel unique sert toutes les pages. En V2, un service Python séparé calculera embeddings et consensus et n'échangera avec Laravel que via la base et une API interne. Seul Laravel, derrière un reverse proxy, est exposé à Internet.

```
Internet ─▶ reverse proxy (nginx) ─▶ app (php-fpm, Laravel) ──┐
                                        ├─▶ PostgreSQL 16 (pgvector, pgcrypto)      │ réseau privé uniquement
                                        ├─▶ Redis (sessions, cache, files) ◀─ worker (queue:work)
                                        ├─▶ Meilisearch (recherche plein texte)     │
                                        ├─▶ embeddings (Python, FastAPI, e5-small) ◀┘
                                        └─▶ Brevo (e-mails transactionnels, prod) / Mailpit (dev)
```

## Lot 1 : socle

### Authentification

- Laravel Fortify : inscription, connexion, vérification d'e-mail, réinitialisation, TOTP.
- Clés d'accès WebAuthn via `laravel/passkeys`, intégré à Fortify : second facteur ou connexion sans mot de passe.
- Hachage Argon2id. Mots de passe de 12 caractères minimum, vérifiés contre les fuites connues par k-anonymat (seuls les 5 premiers caractères du SHA-1 quittent le serveur).
- Domaines d'e-mails jetables refusés (liste versionnée, mise à jour par commande Artisan).

### Données de compte

| Colonne | Rôle |
| --- | --- |
| `pseudonym` | Seul identifiant public |
| `email` | E-mail chiffré (cast `encrypted`, clé `APP_KEY`), jamais filtré en clair ; `App\Auth\UserProvider` traduit les recherches en `email_hash` |
| `email_hash` | HMAC-SHA256 de l'e-mail normalisé, clé `EMAIL_HASH_KEY` distincte ; index unique ; sert à la connexion |
| `role` | `participant`, `moderator`, `editorial`, `admin` (contrainte CHECK en base) |
| `consented_at` | Consentement explicite recueilli à l'inscription (RGPD art. 9) |

Les votes (lot 3) seront liés à l'identifiant interne, jamais à l'e-mail.

### Rôles et permissions

Gates Laravel : `participate`, `moderate`, `manage-themes`, `publish-synthesis`, `arbitrate-appeals`, `manage-platform`. Les rôles sont cumulatifs de `participant` à `editorial` ; `admin` ne détient que `manage-platform`. Un middleware impose le second facteur aux rôles privilégiés avant tout accès.

### Durcissement

Middleware `SecurityHeaders` (CSP stricte avec nonce, sans `unsafe-inline` ni `unsafe-eval`, HSTS, Referrer-Policy, Permissions-Policy), Livewire en mode `csp_safe`, cookies Secure/HttpOnly/SameSite=Lax, sessions chiffrées dans Redis, limitation de débit (connexion, inscription, 2FA, passkeys) et verrouillage progressif (`App\Auth\LoginLockout`), journaux JSON nettoyés de toute donnée personnelle (`App\Logging\ScrubPersonalData`), aucune ressource externe (polices système, assets servis par la plateforme).

## Lot 2 : contenu

### Modèle

| Table | Rôle |
| --- | --- |
| `themes` | Deux niveaux maximum (`parent_id`), statuts `open` / `closed` / `archived`, ordre manuel ; contrainte CHECK sur le statut |
| `proposals` | Fiche au format imposé (titre 120, problème 500, mesure 1 500, coût 300 ou `cost_unknown`), `origin` citoyen ou amorçage, `status`, `content_locked_at` posé par le premier vote (lot 3), colonnes `family_id` et `parent_id` réservées à la V2 |
| `proposal_sources` | Une ou plusieurs URL, ou la mention « proposition personnelle » (`is_personal`) |
| `proposal_revisions` | Instantané JSON à la création et à chaque modification, type `content` ou `typo` |
| `arguments` | Pour ou contre, 600 caractères, source facultative, `status` |
| `argument_marks` | Marque « utile », clé composite participant + argument |

Les auteurs sont référencés par `author_id` (nullable, mis à null à la suppression du compte) et affichés par pseudonyme uniquement.

### Règles métier côté serveur

- `App\Services\ProposalRules` : règles et messages du format imposé, partagés par le formulaire, la modification et l'import. `ProposalService` les applique, vérifie que le thème est ouvert, applique les plafonds, écrit sources et révisions dans une transaction.
- Verrou du fond : après `content_locked_at`, seuls titre, problème et mesure peuvent changer, et la part modifiée (distance d'édition, `TextSimilarity`) doit rester sous `votalis.typo_ratio` ; thème, coût et sources ne changent plus.
- `ContributionCaps` : 3 propositions par mois et par thème, 20 arguments par jour, divisés par deux pour les comptes de moins de 7 jours (`config/votalis.php`, surchargeable par l'environnement).
- `ProposalImporter` : import CSV transactionnel du jeu d'amorçage, format dans `docs/import-amorcage.md`.
- Politiques : `ThemePolicy` (comité éditorial), `ProposalPolicy` et `ArgumentPolicy` (participant vérifié, auteur pour la modification).

### Interface

Blade pour les pages, Livewire 4 (mode CSP) pour le formulaire de proposition (`App\Livewire\ProposalForm`, compteurs et sources dynamiques) et les colonnes d'arguments (`ArgumentColumn`, dépôt et marque « utile »). Routes : `/themes`, `/themes/{slug}`, `/propositions/nouvelle`, `/propositions/{id}/{slug}`, `/propositions/{id}/modifier`, `/comite/themes`.

## Lot 3, phase A : votes et classements

| Table | Rôle |
| --- | --- |
| `votes` | Clé composite participant + proposition ; `desirable` et `necessary` (−1, 0, 1, contraintes CHECK), `condition` (200), `desirable_initial` et `necessary_initial` conservés, `revised_after_arguments` ; effacés avec le compte |
| `proposals.votes_count` | Compteur dénormalisé tenu par `VoteService`, utilisé par le vote rapide et les classements |

- `App\Services\VoteService` : un vote par compte et par proposition, révisable ; interdit sur sa propre fiche et sur une fiche non publiée ; plafond quotidien (nouveaux votes seulement) via `ContributionCaps` ; le premier vote pose `content_locked_at` ; une révision depuis une vue où les arguments sont visibles marque `revised_after_arguments`.
- `QuickVoteSelector` : tirage pondéré (récentes × 3, peu votées × 2) parmi les fiches non votées, jamais les siennes.
- `Rankings` : onglets par thème, cache 5 minutes, définitions dans `docs/classement.md` ; les onglets « arbitrages » et « consensuelles » sont annoncés comme à venir.
- Livewire : `VoteBox` (fiche et vote rapide, résultats après le vote), `QuickVote` (une fiche à la fois, arguments repliés, passage à la suivante).

## Lot 3, phase B : embeddings, doublons, conditions, recherche

- **Service `consensus/`** : FastAPI, `intfloat/multilingual-e5-small` (384 dimensions) chargé au build de l'image, exécution hors ligne (`TRANSFORMERS_OFFLINE=1`), `POST /embed`, `GET /health`, aucun texte journalisé. Jamais exposé à Internet ; en développement, le port 8001 est publié vers l'hôte pour Artisan et les tests.
- **`App\Services\EmbeddingClient`** : seul point d'appel ; refuse tout hôte absent de `votalis.embeddings.allowed_hosts` (C8) ; retourne `null` si le service est indisponible et l'appelant dégrade silencieusement.
- **`proposals.embedding vector(384)`** (index HNSW cosinus), `embedding_version`, `embedded_at` ; calculé par la file (`ComputeProposalEmbedding`) à la création et à chaque révision de contenu ; `php artisan proposals:embed [--all]` pour rattraper ou recalculer après changement de modèle.
- **`DuplicateFinder`** : cinq fiches publiées les plus proches de « titre + mesure » au-dessus de `votalis.duplicates.threshold` (0,89 après calibrage : même mesure reformulée ≈ 0,90-0,93, mesures différentes d'un même domaine ≈ 0,85-0,88). Affiché dans `ProposalForm` dès que titre et mesure sont assez renseignés, avec « Soutenir » et « Déposer quand même » ; « Proposer une variante » attend la V2.
- **`ConditionGrouper`** : regroupement glouton des conditions « oui, à condition que… » par similarité (seuil 0,86), libellé = condition la plus centrale, résultat dans `vote_condition_groups`, recalculé par la file (`RegroupVoteConditions`) après chaque vote conditionnel.
- **Recherche** : Laravel Scout + Meilisearch, index `proposals` (titre, problème, mesure, thème ; rien sur l'auteur), filtre par thème, page `/recherche`. Pilote `collection` dans les tests. Indexation en file (`SCOUT_QUEUE=true`).
- **File d'attente** : service `worker` (`queue:work`) dans Compose ; en production, un processus équivalent supervisé.

## Lot 3, phase C : arbitrages

| Table | Rôle |
| --- | --- |
| `tradeoffs` | Exercice du comité : objectif chiffré et sourcé, contrainte (`constraint_value`, `unit`, `direction` atteindre au moins / ne pas dépasser), statut brouillon / ouvert / clos, thème facultatif |
| `tradeoff_items` | Mesures candidates : proposition publiée, `impact`, `uncertainty` et `source_url` obligatoires (une mesure sans chiffrage fiable n'entre pas) |
| `tradeoff_answers` | Dernière combinaison d'un participant (clé composite), `item_ids`, `conditions` (item → « acceptée à condition que… »), `total` |
| `tradeoff_answer_revisions` | Historique des combinaisons, visible par le participant seul |
| `tradeoff_suggestions` | Mesures candidates proposées par les participants, ajoutées ou écartées par le comité |

- `App\Services\TradeoffService` : toutes les règles côté serveur (exercice ouvert, mesures de l'exercice, contrainte atteinte, conditions, remplacement de la réponse avec historique, ouverture à partir de deux mesures, chiffrage obligatoire), résultats agrégés (fréquence par mesure, combinaisons les plus fréquentes, conditions les plus citées) en cache.
- Livewire `TradeoffExercise` : jauge en direct, arguments de chaque mesure repliés, condition par mesure choisie, validation possible seulement si la contrainte est atteinte (vérifiée aussi par le service), historique, suggestion d'une mesure.
- Administration `/comite/arbitrages` (capacité `manage-tradeoffs`, comité éditorial) ; pages publiques `/arbitrages`, `/arbitrages/{slug}`, `/arbitrages/{slug}/resultats`.
- Onglet « les plus choisies dans les arbitrages » alimenté par `Rankings` (part des réponses retenant la mesure).

## Lot 4, phase A : signalement, file de modération, journal public

| Table | Rôle |
| --- | --- |
| `reports` | Signalement d'une proposition ou d'un argument (relation morphique `target`), motif de la charte, précision facultative (300 caractères), statut ouvert / traité, lien vers l'entrée du journal qui l'a clos. Un seul par compte et par contenu (index unique) |
| `moderation_log` | Journal public, **en ajout seul** : type et identifiant de la cible, action, motif, identifiant interne de l'acteur (jamais affiché) et son rôle, `details` JSON limité à des identifiants. Aucune clé étrangère pour que la suppression d'un compte ou d'un contenu (lot 5) ne touche jamais une entrée. Déclencheurs PostgreSQL `BEFORE UPDATE OR DELETE` et `BEFORE TRUNCATE` levant une exception ; le modèle `ModerationLogEntry` refuse aussi `save()` sur une entrée existante et `delete()` |
| `proposals`, `arguments` (ajouts) | `hidden_motive` ; statut `rewrite_requested` pour les propositions, avec `rewrite_allowed_until` |

- Motifs (`ReportMotive`) : liste fermée de la charte avec gravité (illégal 3 ; attaque personnelle, désinformation, campagne coordonnée 2 ; hors sujet, doublon, spam 1). Seul « contenu illégal » masque immédiatement (`auto_hide`, acteur `system`, journalisé) ; les autres laissent le contenu visible jusqu'à la décision.
- `App\Services\ReportService` : seul point de création d'un signalement (capacité `report` : participant vérifié, pas l'auteur, contenu publié ; unicité ; plafond `caps.reports_per_day`).
- `App\Services\ModerationService` : conserver (rétablit un contenu masqué en attente), masquer avec motif (un doublon référence la fiche conservée dans `details`), demander une reformulation (propositions seulement). Chaque action écrit l'entrée du journal et clôt les signalements ouverts dans la même transaction. `rewriteReceived()` est appelé par `ProposalService::update` quand l'auteur a reformulé : une seule modification de fond malgré le verrou, puis republication immédiate.
- `App\Services\ModerationQueue` : un dossier par contenu, trié par gravité du motif le plus grave puis par ancienneté ; contexte de l'auteur pseudonymisé (ancienneté du compte, décisions antérieures) ; historique agrégé du signaleur (émis, traités, retenus) sans jamais révéler son identité.
- Affichage : une proposition masquée est remplacée par un bandeau public avec le motif et le lien vers l'entrée du journal (ni titre, ni redirection canonique pour un contenu illégal) ; auteur et modérateurs voient le contenu complet avec le bandeau. Un argument masqué devient une ligne « Argument masqué par la modération ». Les contenus masqués sortent déjà des classements, du vote rapide, de la recherche et des doublons (`published()`).
- Routes : `/signaler/{proposition|argument}/{id}` (formulaire sans JavaScript), `/moderation` et `/moderation/dossiers/{type}/{id}` (capacité `moderate`, second facteur exigé), `/journal-de-moderation` (public, filtrable), `/charte-de-moderation`, `/comment-fonctionne-le-classement`.
- Journal public : date, type de contenu, action, motif, rôle de l'acteur ; jamais de pseudonyme de modérateur ; le titre d'une fiche masquée pour un motif ordinaire est montré, rien pour un contenu illégal.

## Lot 4, phase B : contestation, information des auteurs, suspension

| Table | Rôle |
| --- | --- |
| `appeals` | Une contestation par entrée du journal (`log_entry_id` unique) : auteur, texte (1 000 caractères), statut en attente / confirmée / annulée, arbitre (`decided_by`, identifiant interne), motivation visible de l'auteur (`decision_note`), entrée du journal portant l'issue |
| `users` (ajouts) | `suspended_at`, `suspended_until` (null : sans terme). Le motif ne figure que dans le journal |

- `App\Services\AppealService::file()` : capacité `appeal` (`AppealPolicy`) : auteur du contenu ou titulaire du compte suspendu, action contestable (masquage, reformulation, suspension), délai `moderation.appeal_days`, une seule fois. `decide()` : capacité `arbitrate-appeals` (comité) ; **refus si l'arbitre est l'auteur de la décision contestée** (critère D2, testé par le service et par la route) ; une annulation rétablit le contenu ou lève la suspension ; l'issue est journalisée (`appeal_confirmed` / `appeal_overturned`, `details.appealed_entry_id`) et l'auteur prévenu.
- `App\Services\ModerationService::suspend()` : comité éditorial seulement, compte participant seulement, durée en jours ou sans terme, entrée `suspend` avec `target_type = user` (le journal affiche « Compte », jamais le pseudonyme). La Gate `participate` refuse un compte suspendu : votes, propositions, arguments, signalements et arbitrages sont bloqués d'un coup ; lecture et contestation restent possibles.
- `App\Notifications\ModerationNotice` : e-mail minimal (file d'attente) à chaque masquage, demande de reformulation, suspension et décision d'appel ; ni contenu, ni motif, ni pseudonyme, seulement un lien vers `/mon-compte/moderation`.
- Pages : `/mon-compte/moderation` (décisions me concernant, contestation, issue et motivation du comité), `/mon-compte/moderation/contester/{entrée}`, `/moderation/contestations` et `/moderation/contestations/{id}` (comité ; une contestation de sa propre décision est affichée sans formulaire), suspension depuis le dossier de modération.

## Environnements

| Environnement | Où | Base | E-mail |
| --- | --- | --- | --- |
| Développement | Docker local (`infra/compose.dev.yml`) | PostgreSQL conteneur | Mailpit |
| CI | Woodpecker (Codeberg) | PostgreSQL service | Mailer `array` |
| Préproduction, production | Hébergeur européen à choisir (OVHcloud ou Scaleway) | PostgreSQL managé ou conteneur | Brevo |

## Décisions

| Date | Décision | Raison |
| --- | --- | --- |
| 2026-10-08 | Livewire 4 plutôt qu'Inertia + Vue | Pages publiques légères, compétence Blade existante |
| 2026-10-08 | Codeberg + Woodpecker CI, miroir GitHub lecture seule | Forge européenne |
| 2026-10-08 | HIBP en k-anonymat pour les mots de passe fuités | Aucune donnée personnelle transmise ; auto-hébergement étudié au lot 5 |
| 2026-10-08 | Rôles en colonne enum + Gates, sans package de permissions | Cinq rôles fixes définis par le cahier des charges |
| 2026-10-08 | `laravel/passkeys` plutôt que `laragear/webauthn` | Le second est abandonné au profit du paquet officiel, intégré à Fortify |
| 2026-10-08 | Vérification HIBP simulée dans les tests (`Http::fake`) | Aucun appel réseau en CI ; la logique de refus est testée |
| 2026-10-08 | Plafonds de contribution appliqués dès le lot 2 | Aucun formulaire ouvert sans limite côté serveur |
| 2026-10-08 | Pas de contrôle automatique du titre « formulé comme une mesure » | Aide affichée ; une liste de verbes produirait des faux refus |
| 2026-10-08 | Recherche texte et Meilisearch reportés au lot 3 | Livrés avec la détection de doublons |
| 2026-10-08 | Pas de brouillon de proposition | Publication immédiate puis correction, historique public |
| 2026-10-08 | `multilingual-e5-small` (384 dim.) téléchargé au build, exécution hors ligne | Empreinte mémoire ≈ 500 Mo ; aucun texte ne sort du réseau privé ; `embedding_version` permet un recalcul si le modèle change |
| 2026-10-08 | Seuil de doublon 0,89 | Calibré sur le modèle : évite de signaler deux mesures différentes d'un même domaine |
| 2026-10-08 | Journal de modération sans clé étrangère, immuable par déclencheur | Une suppression de compte ou de contenu ne doit jamais modifier ni effacer une entrée |
| 2026-10-08 | Masquage immédiat dès le premier signalement « contenu illégal » | Imposé par le cahier des charges ; garde-fous : plafond de signalements, historique du signaleur, rétablissement par « conserver » |
| 2026-10-08 | Formulaires de signalement et de modération en Blade sans Livewire | Pas de JavaScript nécessaire, CSP simple, testable en HTTP |
| 2026-10-08 | Reformulation : fiche invisible, une modification de fond, republication sans validation | Décision du 8 octobre ; le modérateur peut remasquer |
