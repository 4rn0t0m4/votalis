# CLAUDE.md

Plateforme web non partisane de débat citoyen : des mesures concrètes que chacun peut consulter, voter, arbitrer, argumenter et proposer. Le cahier des charges fait foi ; ce fichier en reprend les consignes de travail (section 14) et les repères du dépôt.

## Consignes de travail (cahier des charges, section 14)

- Travailler **lot par lot** (section 13 : Socle, Contenu, Participation, Modération, Ouverture), dans une **branche dédiée par lot**, avec une pull request à la fin de chacun.
- Commencer chaque lot par un **plan écrit** dans `docs/plans/` et le faire **valider avant d'écrire du code**.
- Écrire les **tests avant ou avec le code** ; ne jamais désactiver un test pour le faire passer.
- Ne jamais ajouter de dépendance ni de service tiers **hébergé hors d'Europe** sans le signaler et demander validation.
- Ne jamais écrire de **secret, de seuil anti-fraude ou de valeur de production** dans le dépôt : `.env.example` et une configuration privée uniquement.
- Toute **règle métier** (plafonds, droits, fusion, modération) est appliquée **côté serveur** et couverte par un test.
- Tenir à jour `docs/architecture.md`, `docs/classement.md` et ce fichier.
- En cas d'ambiguïté dans le cahier des charges, **poser la question** plutôt que trancher.

## Principes opposables à toute décision technique

Neutralité (aucune étiquette partisane, vocabulaire et visuel neutres) · juger les idées, pas les auteurs (pseudonymat) · arbitrage plutôt que popularité · souveraineté (hébergement et services européens, rien sous Cloud Act) · transparence (AGPL v3, algorithme documenté, journal public de modération) · minimisation et chiffrement des données sensibles · sobriété.

## Dépôt

```
app/          Laravel 13, PHP 8.3+, Livewire 4, Tailwind 4
consensus/    Service Python (FastAPI, scikit-learn) — V2
infra/        Docker (dev), nginx, hooks git
docs/         architecture.md, classement.md, plans/
.woodpecker/  CI
```

## Commandes

```sh
make up          # Démarre Docker (app, worker, nginx, PostgreSQL 16 + pgvector, Redis, Meilisearch, embeddings, Mailpit, Vite)
make shell       # Shell dans le conteneur app
make test        # php artisan test (sur PostgreSQL, jamais SQLite)
make lint        # Pint en vérification
make stan        # PHPStan niveau 8
make hooks       # Installe le pre-commit (gitleaks + Pint)
```

Interface web : http://localhost:8080 · Mailpit : http://localhost:8025

## Repères techniques

- **Authentification** : Laravel Fortify. Argon2id, vérification e-mail obligatoire, mots de passe vérifiés contre les fuites connues par k-anonymat (HIBP, 5 caractères de haché envoyés), TOTP et WebAuthn.
- **E-mail** : colonne `email` chiffrée (cast `encrypted`) avec un haché HMAC séparé (`email_hash`, clé `EMAIL_HASH_KEY`) calculé à la sauvegarde pour l'unicité et la connexion. Aucune requête ne filtre sur l'e-mail en clair : passer par `App\Auth\UserProvider` ou `EmailHasher::hash()`. Dans les tests, `Event::fake()` sans argument désactive ce calcul : cibler les événements (`Event::fake([Verified::class])`).
- **WebAuthn** : `laravel/passkeys`, intégré à Fortify (routes `passkey.*`), client JS `@laravel/passkeys` dans `resources/js/app.js`.
- **Rôles** : colonne `role` (`participant`, `moderator`, `editorial`, `admin`) + Gates. L'administrateur technique n'a aucun pouvoir éditorial. Second facteur obligatoire pour `moderator`, `editorial`, `admin`.
- **Sécurité** : CSP stricte avec nonce (Livewire en `csp_safe`, Vite avec nonce), HSTS, cookies Secure/HttpOnly/SameSite=Lax, aucune ressource chargée depuis un CDN tiers (polices système), limitation de débit sur connexion et inscription, verrouillage progressif. Aucun `<script>` ni `style=` en ligne dans les vues : tout passe par Vite ou des attributs `data-*`.
- **Mots de passe fuités** : `Password::defaults()` appelle HIBP ; dans les tests, `Tests\TestCase` simule la réponse (`fakeLeakedPassword()`).
- **Journaux** : JSON structuré, jamais d'e-mail ni d'adresse IP.
- **Base** : PostgreSQL 16, extensions `vector` et `pgcrypto`. Les tests tournent sur PostgreSQL.
- **Nom du site** : non choisi. « votalis » est un nom de code technique ; l'interface lit `APP_NAME`, jamais de nom codé en dur.

## Contenu (lot 2)

- **Format imposé** : toutes les règles de la fiche vivent dans `App\Services\ProposalRules` ; formulaire, modification et import passent par `ProposalService`. Ne jamais valider une fiche ailleurs.
- **Plafonds** : `App\Services\ContributionCaps`, valeurs dans `config/votalis.php` (`caps.*`). Toute nouvelle forme de contribution passe par ce service.
- **Verrou du fond** : `content_locked_at` est posé par le premier vote (lot 3) ; ensuite `ProposalService::update` n'accepte que des corrections de forme.
- **Import** : `php artisan proposals:import fichier.csv [--dry-run]`, format dans `docs/import-amorcage.md`, jeu de test `tests/Fixtures/amorcage-200.csv`.
- **Livewire** : composants de classe dans `app/Livewire`, vues dans `resources/views/livewire`. Les paramètres de `mount()` homonymes d'une propriété publique y sont affectés directement : typer la propriété en conséquence (ex. `ArgumentSide $side`).
- **Tests** : `Vite::useHotFile()` pointe vers un fichier inexistant dans `Tests\TestCase` pour que les pages rendent les assets compilés même quand le serveur Vite de développement tourne.

## Participation (lot 3)

- **Votes** : toujours par `App\Services\VoteService::cast()`, jamais par `Vote::create()` : c'est lui qui applique le plafond, le verrou de la fiche, le vote initial et le marquage « après lecture des arguments ».
- **Classements** : `App\Services\Rankings`, définitions publiques dans `docs/classement.md`. Ne jamais ajouter un tri par nombre de soutiens.
- **Vote rapide** : `QuickVoteSelector`, poids dans `config/votalis.php` (`quick_vote.*`).
- **Embeddings** : uniquement via `App\Services\EmbeddingClient` (hôtes autorisés, repli `null`). Jamais d'appel direct au service, jamais d'API externe. Vecteurs écrits avec `EmbeddingClient::literal()`, requêtes avec `embedding <=> ?::vector`.
- **Service Python** : `consensus/` (FastAPI). Qualité : `ruff check . && ruff format --check . && mypy && pytest` dans un conteneur `python:3.12-slim` avec `requirements-test.txt` (sans torch). Le modèle n'est téléchargé qu'au build de l'image.
- **Recherche** : Scout + Meilisearch ; `toSearchableArray()` ne doit jamais contenir de donnée d'auteur. Tests avec `SCOUT_DRIVER=collection`.
- **Arbitrages** : toujours par `App\Services\TradeoffService` (réponse, ajout de mesure, statut, suggestion). Une mesure sans `impact`, `uncertainty` et `source_url` est refusée.
- **Files** : les jobs sont `ShouldQueue` ; en développement, le service `worker` les traite. Dans les tests, `QUEUE_CONNECTION=sync`.

## Modération (lot 4)

- **Signalements** : toujours par `App\Services\ReportService::report()` ; **actions** : toujours par `App\Services\ModerationService` (conserver, masquer, demander une reformulation). Jamais de changement de `status` ou de `hidden_motive` à la main : c'est le service qui écrit l'entrée du journal dans la même transaction.
- **Journal** : table `moderation_log` en ajout seul, protégée par déclencheur PostgreSQL ; le modèle `ModerationLogEntry` refuse `update`/`delete`. Aucune clé étrangère, aucun texte dans `details`, jamais de pseudonyme de modérateur affiché (seulement `actor_role`).
- **Motifs** : liste fermée dans `App\Enums\ReportMotive` (gravité, masquage immédiat pour « illégal »). Ajouter un motif = modifier l'énumération, la contrainte CHECK et la charte.
- **Contestation** : toujours par `App\Services\AppealService` (`file`, `decide`) ; le service refuse qu'un membre tranche sa propre décision. **Suspension** : `ModerationService::suspend()`, comité seulement ; la Gate `participate` (redéfinie dans `AppServiceProvider`) refuse un compte suspendu, donc toute nouvelle contribution doit passer par `can('participate')` plutôt que par le rôle.
- **Notifications** : `ModerationNotice` uniquement, sans contenu ni motif dans l'e-mail. Dans les tests, `Notification::fake()` avant toute action de modération.
- **Signaux d'intégrité** : `App\Services\IntegrityScanner`, seuils `votalis.integrity.*` lus depuis l'environnement **sans valeur par défaut** (jamais de seuil dans le dépôt, CDC 14) ; un seuil absent désactive le détecteur. Le service n'écrit que dans `integrity_signals` : ne jamais y brancher une action automatique. Dans les tests, fixer les seuils par `config([...])`.
- **Planification** : `routes/console.php` (`integrity:scan` nocturne, `transparency:report` trimestriel). **Transparence** : `TransparencyReporter` ne produit que des agrégats ; tout nouveau champ doit rester un comptage.
- **Affichage** : un contenu non publié passe par `proposals.hidden` (bandeau) ou une ligne « Argument masqué » ; les requêtes publiques filtrent avec `published()`.
- **Tests** : dans Docker, `APP_ENV=local` est exporté par Compose ; `phpunit.xml` force `APP_ENV=testing` via `<env>` et `<server>`. Les simulations HTTP du service d'embeddings utilisent le motif `*/embed`, valable sur l'hôte comme dans le conteneur. Une erreur PostgreSQL attendue dans un test doit être isolée par `DB::beginTransaction()` / `rollBack()` (point de sauvegarde), sinon la transaction du test est avortée.
- **Blade** : une directive inline doit être précédée d'un espace (`modération @if (...)`) ; collée à un mot (`modération@if`), elle n'est pas compilée.

## Conventions

- Interface en **français**, chaînes externalisées dans `app/lang/fr/`.
- Routes en slugs français (`/connexion`, `/inscription`, `/mon-compte`).
- Blade + Livewire 4 ; pas de framework JavaScript lourd sur les pages publiques (poids initial < 300 Ko).
- Le modèle `User` a le cast `'password' => 'hashed'` : ne jamais hacher à la main avant affectation.
- Accessibilité RGAA 4.1 AA : navigation clavier complète, libellés explicites, contrastes vérifiés.
