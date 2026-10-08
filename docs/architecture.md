# Architecture

Document tenu à jour à chaque lot. État : lot 1 (Socle), 8 octobre 2026.

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
