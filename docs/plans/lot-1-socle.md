# Plan du lot 1 — Socle

Statut : **réalisé le 8 octobre 2026, en attente de la recette Codeberg** (plan validé le 8 octobre 2026).
Référence : cahier des charges, sections 3, 7.1, 11, 13 et 14.

## 1. Objectif et critères d'acceptation

Le lot 1 livre un dépôt public, une CI, des environnements de développement et de préproduction, une authentification avec double facteur et les cinq rôles. Aucune fonctionnalité métier (thèmes, propositions, votes) n'est incluse.

Critères d'acceptation issus de la section 13, tous vérifiés en préproduction :

| # | Critère | Vérification |
| --- | --- | --- |
| A1 | Une pull request depuis un fork ne voit aucun secret | Woodpecker : secrets limités aux événements `push` et `tag` sur `main`, jamais `pull_request` ; test manuel avec un fork |
| A2 | Un push direct sur `main` est refusé | Protection de branche Codeberg : PR obligatoire, une approbation, statut CI vert |
| A3 | gitleaks bloque un faux secret ajouté dans un commit de test | Hook pre-commit + étape CI ; commit de test avec une fausse clé AWS |
| A4 | Double authentification obligatoire pour les trois rôles privilégiés | Test automatisé : un modérateur sans TOTP est redirigé vers l'activation et ne peut rien faire d'autre |
| A5 | Un administrateur technique n'a aucun pouvoir éditorial | Test automatisé sur les gates `moderate`, `edit-themes`, `publish-synthesis` |

## 2. Décisions prises et versions

| Brique | Choix | Note |
| --- | --- | --- |
| Framework | Laravel 13 (dernière version stable, PHP 8.3 minimum) | Laravel n'a plus de LTS ; on suit la dernière majeure |
| PHP | 8.4 dans Docker | PHP 8.5 local disponible mais trop récent pour certaines extensions |
| Interface | Livewire 4 + Blade + Tailwind 4 | Validé : dernière majeure, même modèle que Livewire 3 |
| Base | PostgreSQL 16, image `pgvector/pgvector:pg16` | pgvector et pgcrypto activés dès la première migration |
| Cache, sessions, files | Redis 7 | |
| Authentification | Laravel Fortify 1.41 | Inscription, connexion, vérification e-mail, TOTP, mots de passe |
| WebAuthn | `laravel/passkeys` (intégré à Fortify) | `laragear/webauthn` est abandonné au profit de ce paquet officiel |
| Rôles | Colonne `role` (enum) + Gates Laravel | Pas de package de permissions : 5 rôles fixes et cumulatifs |
| Analyse statique | Larastan 3 (PHPStan niveau 8), Pint | |
| E-mails en dev | Mailpit dans Docker | Brevo configuré mais inactif hors production |
| Forge | Codeberg + Woodpecker CI | Miroir GitHub en lecture seule, ajouté en fin de lot |

Le nom du site n'est pas choisi : « Votalis » reste un nom de code technique (dossier, dépôt). Le nom affiché vient de `APP_NAME` et aucune chaîne de l'interface ne le code en dur.

## 3. Structure du monorepo

```
votalis/
├── app/                 Laravel (tout le code PHP, Blade, assets)
├── consensus/           Service Python (vide au lot 1, README seulement)
├── infra/
│   ├── docker/          Dockerfiles et configuration nginx, php-fpm
│   ├── compose.dev.yml  Environnement de développement
│   └── hooks/           pre-commit (gitleaks, pint)
├── docs/
│   ├── architecture.md  Tenu à jour à chaque lot
│   ├── classement.md    Créé au lot 1 avec le principe, détaillé en V2
│   └── plans/           Un plan par lot
├── .woodpecker/         Pipelines CI
├── CLAUDE.md            Consignes de la section 14
├── LICENSE              AGPL v3
├── CONTRIBUTING.md
├── SECURITY.md          Adresse de signalement des failles, délai de réponse
└── .gitleaks.toml
```

## 4. Environnement Docker de développement

Services de `infra/compose.dev.yml` : `app` (php-fpm 8.4), `web` (nginx), `db` (PostgreSQL 16 + pgvector), `redis`, `mailpit`. Meilisearch arrive au lot 2, le service Python en V2.

- Les images proviennent des registres officiels Docker Hub ; en production elles seront reconstruites et poussées sur le registre privé de l'hébergeur.
- Un `Makefile` à la racine : `make up`, `make test`, `make lint`, `make hooks`.
- Aucun secret dans `compose.dev.yml` : les valeurs viennent de `.env`, seul `.env.example` est versionné.

## 5. Authentification et comptes

### 5.1 Modèle `users`

| Colonne | Type | Remarque |
| --- | --- | --- |
| id | bigint | |
| pseudonym | string(40), unique | Seul identifiant public |
| email | text | Chiffré par Laravel (cast `encrypted`, clé `APP_KEY`) ; la colonne garde le nom `email` pour rester compatible avec Fortify et les notifications |
| email_hash | char(64), unique | HMAC-SHA256 de l'e-mail normalisé avec une clé dédiée `EMAIL_HASH_KEY`, distincte d'`APP_KEY` |
| password | string | Argon2id |
| role | string + contrainte CHECK | `visitor` n'existe pas en base : `participant`, `moderator`, `editorial`, `admin` |
| consented_at | timestamp | Consentement explicite au traitement de données d'opinion (RGPD art. 9), recueilli à l'inscription |
| two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at | | Fortify |
| email_verified_at, created_at, updated_at | | |

La connexion se fait par pseudonyme ou e-mail ; côté serveur l'e-mail est normalisé puis haché pour retrouver le compte. Aucune requête ne lit `email_encrypted` sauf l'envoi d'un e-mail.

### 5.2 Règles

- Argon2id via `config/hashing.php`, paramètres mémoire et temps alignés sur les recommandations OWASP.
- Mot de passe : 12 caractères minimum, vérification contre les fuites connues par k-anonymat (API Have I Been Pwned, validé Q1, documenté dans la politique de confidentialité).
- Vérification e-mail obligatoire avant toute action ; lien signé valable 60 minutes.
- Domaines d'e-mails jetables refusés à l'inscription ; liste open source versionnée dans `app/resources/data/disposable-domains.txt`, mise à jour par une commande Artisan.
- Double authentification TOTP proposée à tous ; clés WebAuthn en second facteur ou en remplacement.
- Rôles privilégiés (`moderator`, `editorial`, `admin`) : un middleware impose l'activation du second facteur avant tout accès ; une commande Artisan `role:assign` est le seul moyen d'attribuer un rôle au lot 1.
- Limitation de débit : connexion 10 essais par minute par couple identifiant + IP (garde-fou brut), inscription 10 tentatives par heure par IP, et verrouillage progressif (1, 5, 15 minutes) dès 5 échecs sur un couple identifiant + IP.
- Sessions Redis, cookies `Secure`, `HttpOnly`, `SameSite=Lax`, régénération de session à la connexion.

### 5.3 Rôles et pouvoirs (section 3)

Gates définies dans `AuthServiceProvider` et couvertes par un test chacune :

| Gate | participant | moderator | editorial | admin |
| --- | --- | --- | --- | --- |
| participate (voter, argumenter, proposer, signaler) | oui | oui | oui | non |
| moderate | | oui | oui | non |
| manage-themes, publish-synthesis, arbitrate-appeals | | | oui | non |
| manage-platform (configuration, signaux anti-fraude) | | | | oui |

Les contenus métier n'existent pas encore : les gates sont définies et testées au lot 1, puis branchées sur les contrôleurs aux lots suivants.

## 6. En-têtes et durcissement

- Middleware `SecurityHeaders` : CSP avec nonce par requête, `HSTS`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Aucune ressource externe : Tailwind et JS compilés par Vite et servis par nginx, polices embarquées.
- Journaux structurés en JSON, sans adresse IP ni e-mail ; identifiant de requête pour corréler.
- Risque CSP levé : Livewire 4 fournit un mode `csp_safe` (build CSP d'Alpine, activé dans `config/livewire.php`) et lit le nonce de Vite. La CSP est stricte, sans `unsafe-inline` ni `unsafe-eval`, et testée.

## 7. Dépôt, CI et protection de `main`

- Dépôt Codeberg créé par Arnaud (voir Q3), code poussé depuis ce poste.
- `.woodpecker/ci.yml` : installation, Pint en vérification, Larastan niveau 8, tests PHPUnit avec un service PostgreSQL, build Vite, gitleaks sur l'historique de la PR.
- Secrets Woodpecker : aucun n'est nécessaire au lot 1 ; la politique « jamais sur `pull_request` » est posée dès maintenant.
- Protection de branche configurée dans Codeberg : PR obligatoire, une approbation, CI verte, pas de push direct même pour les mainteneurs.
- Hook `pre-commit` installé par `make hooks` : gitleaks puis Pint sur les fichiers indexés. gitleaks s'installe localement par Homebrew.
- Pied de page affichant le commit déployé (`APP_COMMIT` injecté au build) avec lien vers le dépôt.

## 8. Tests du lot 1

Tous écrits avec ou avant le code, exécutés sur PostgreSQL et non sur SQLite.

- Inscription : refus d'un domaine jetable, refus d'un mot de passe court ou fuité, e-mail stocké chiffré et retrouvable par son haché, unicité insensible à la casse.
- Connexion : par pseudonyme et par e-mail, limitation de débit, verrouillage progressif, régénération de session.
- Vérification e-mail : accès bloqué avant vérification, lien expiré refusé.
- Double facteur : activation TOTP, codes de secours, WebAuthn, rôle privilégié bloqué sans second facteur.
- Rôles : une classe de test par gate, dont « l'admin ne peut pas modérer ».
- Sécurité : présence de chaque en-tête, nonce différent à chaque requête, aucune URL externe dans le HTML rendu.
- Journaux : aucun e-mail ni IP dans la sortie JSON.

## 9. Ordre de réalisation

1. Monorepo, licence, CLAUDE.md, SECURITY.md, CONTRIBUTING.md, `.gitleaks.toml`, hooks.
2. Docker Compose, Dockerfile, Makefile, Laravel 13 installé dans `app/`, première migration avec `pgvector` et `pgcrypto`.
3. Fortify : inscription, connexion, vérification e-mail, Argon2id, e-mail chiffré et haché, domaines jetables.
4. TOTP et WebAuthn, middleware « second facteur obligatoire », rôles, gates, commande `role:assign`.
5. En-têtes de sécurité, essai CSP, journaux structurés, limitation de débit.
6. Pipeline Woodpecker, dépôt Codeberg, protection de `main`, miroir GitHub.
7. `docs/architecture.md`, recette des critères A1 à A5 en préproduction, pull request du lot.

## 10. Recette du 8 octobre 2026

| # | Critère | Résultat |
| --- | --- | --- |
| A1 | PR depuis un fork sans secret | Pipeline sans aucun secret ; politique écrite dans `.woodpecker/ci.yml`. À confirmer sur Codeberg une fois le dépôt créé |
| A2 | Push direct sur `main` refusé | Protection de branche à configurer dans Codeberg (étape 6, à faire par Arnaud) |
| A3 | gitleaks bloque un faux secret | Vérifié en local : une fausse clé AWS au format valide bloque le commit via `infra/hooks/pre-commit` ; même scan en CI |
| A4 | Second facteur obligatoire pour les rôles privilégiés | Tests `TwoFactorTest` : modérateur, comité et admin sans second facteur redirigés vers la page sécurité ; participant jamais contraint |
| A5 | L'admin technique n'a aucun pouvoir éditorial | Tests `RoleGateTest` : matrice complète des 6 capacités × 4 rôles |

Suite de tests : 64 tests, 269 assertions, sur PostgreSQL. PHPStan niveau 8 sans erreur. Pile Docker vérifiée (pages publiques en 200, CSP servie, pages protégées redirigées).

Reste à faire pour clore le lot : créer le dépôt Codeberg et pousser la branche `lot-1-socle`, activer Woodpecker, protéger `main`, ouvrir la pull request, configurer le miroir GitHub.

## 11. Questions tranchées le 8 octobre 2026

| Question | Décision |
| --- | --- |
| Q1 Mots de passe fuités | API Have I Been Pwned en k-anonymat au MVP ; auto-hébergement du jeu de données étudié au lot 5 |
| Q2 Livewire | Livewire 4 |
| Q3 Dépôt Codeberg | Nom de code `votalis` ; compte ou organisation Codeberg à créer par Arnaud avant l'étape 6 |
| Q4 Préproduction | CI Woodpecker et Docker local suffisent pour la recette du lot 1 ; le serveur de préproduction arrive avec le choix de l'hébergeur |
| Q5 Images Docker | Docker Hub accepté en développement ; registre de l'hébergeur en production |
