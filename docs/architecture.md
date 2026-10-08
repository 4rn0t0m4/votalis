# Architecture

Document tenu à jour à chaque lot. État : lot 2 (Contenu), 8 octobre 2026.

## Vue d'ensemble

Une application Laravel unique sert toutes les pages. En V2, un service Python séparé calculera embeddings et consensus et n'échangera avec Laravel que via la base et une API interne. Seul Laravel, derrière un reverse proxy, est exposé à Internet.

```
Internet ─▶ reverse proxy (nginx) ─▶ app (php-fpm, Laravel)
                                        ├─▶ PostgreSQL 16 (pgvector, pgcrypto)
                                        ├─▶ Redis (sessions, cache, files)
                                        └─▶ Brevo (e-mails transactionnels, prod) / Mailpit (dev)
                                     consensus (Python, V2) ─▶ PostgreSQL
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
