# Guide de développement

Plateforme web non partisane de débat citoyen : des mesures concrètes que chacun peut consulter, voter, arbitrer, argumenter et proposer. Le cahier des charges fait foi ; ce guide en reprend les consignes de travail (section 14) et les repères du dépôt.

## Consignes de travail (cahier des charges, section 14)

- Travailler **lot par lot** (section 13 : Socle, Contenu, Participation, Modération, Ouverture), dans une **branche dédiée par lot**, avec une pull request à la fin de chacun.
- Commencer chaque lot par un **plan écrit** dans `docs/plans/` et le faire **valider avant d'écrire du code**.
- Écrire les **tests avant ou avec le code** ; ne jamais désactiver un test pour le faire passer.
- Ne jamais ajouter de dépendance ni de service tiers **hébergé hors d'Europe** sans le signaler et demander validation.
- Ne jamais écrire de **secret, de seuil anti-fraude ou de valeur de production** dans le dépôt : `.env.example` et une configuration privée uniquement.
- Toute **règle métier** (plafonds, droits, fusion, modération) est appliquée **côté serveur** et couverte par un test.
- Tenir à jour `docs/architecture.md`, `docs/classement.md` et ce guide.
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
make up          # Démarre Docker (app, nginx, PostgreSQL 16 + pgvector, Redis, Mailpit, Vite)
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

## Conventions

- Interface en **français**, chaînes externalisées dans `app/lang/fr/`.
- Routes en slugs français (`/connexion`, `/inscription`, `/mon-compte`).
- Blade + Livewire 4 ; pas de framework JavaScript lourd sur les pages publiques (poids initial < 300 Ko).
- Le modèle `User` a le cast `'password' => 'hashed'` : ne jamais hacher à la main avant affectation.
- Accessibilité RGAA 4.1 AA : navigation clavier complète, libellés explicites, contrastes vérifiés.
