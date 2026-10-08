# Sécurité — auto-évaluation OWASP ASVS niveau 2 et dossier d'audit

Référentiel : OWASP ASVS 4.0.3 niveau 2, guides d'hygiène ANSSI. Statuts : **C** couvert · **P** partiel · **NA** non applicable · **À faire**. Cette auto-évaluation prépare l'audit externe exigé avant l'ouverture publique (cahier des charges, section 7) ; elle ne le remplace pas.

## Auto-évaluation par chapitre

| Chap. | Domaine | Statut | Mise en œuvre |
| --- | --- | --- | --- |
| V1 | Architecture | C | Monorepo, séparation des rôles, réseau privé, documentation `docs/architecture.md`, menaces dans `docs/rgpd/aipd.md` |
| V2 | Authentification | C | Argon2id, 12 caractères minimum, refus des mots de passe fuités (k-anonymat HIBP), verrouillage progressif, limitation de débit, TOTP et WebAuthn, second facteur obligatoire pour les rôles privilégiés, e-mail vérifié |
| V3 | Sessions | C | Sessions Redis côté serveur, cookies `Secure`/`HttpOnly`/`SameSite=Lax`, régénération à la connexion, invalidation à la déconnexion et à la suppression de compte, confirmation du mot de passe pour les opérations sensibles |
| V4 | Contrôle d'accès | C | Gates par capacité, Policies, règles métier côté serveur dans les services, tests négatifs systématiques (participant, modérateur, comité, administrateur) |
| V5 | Validation, encodage | C | Validation Laravel, Blade échappé, aucune requête SQL concaténée avec une entrée (liaisons), listes fermées (énumérations + CHECK) |
| V6 | Cryptographie | C | E-mail chiffré (AES-256-GCM via `APP_KEY`), haché HMAC-SHA256 avec clé distincte, secrets 2FA chiffrés, sauvegardes AES-256 |
| V7 | Journalisation | C | JSON structuré, nettoyage des données personnelles, nginx sans IP, journal public de modération en ajout seul |
| V8 | Protection des données | C | Minimisation, chiffrement, export et suppression libre-service, purge d'inactivité, `Cache-Control: no-store` sur l'export |
| V9 | Communications | P | TLS terminé par le reverse proxy de l'hôte (HSTS posé par l'application) ; à vérifier au déploiement : TLS 1.2+, OCSP, HSTS preload |
| V10 | Code malveillant | C | Dépôt public, pull request obligatoire, gitleaks, audit des dépendances en CI, aucune ressource tierce |
| V11 | Logique métier | C | Plafonds, verrou du fond, un vote par compte, une contestation par décision, arbitre différent du décideur, rôles privilégiés non suspendables ni supprimables en libre-service |
| V12 | Fichiers | NA | Aucun téléversement de fichier |
| V13 | API | C | Pas d'API publique ; Livewire protégé par CSRF et sommes de contrôle ; endpoints de modération sous Gates |
| V14 | Configuration | C | En-têtes CSP (nonce), `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, COOP ; `APP_DEBUG=false` ; secrets hors dépôt ; images sans outils de build |

## Points à traiter avant l'audit

- Vérifier la configuration TLS du reverse proxy (V9) et publier la clé PGP annoncée dans `SECURITY.md`.
- Choisir et configurer l'hébergeur et le fournisseur d'e-mail (SPF, DKIM, DMARC).
- Revoir la rotation de `EMAIL_HASH_KEY` (commande dédiée) si l'auditeur la demande.

## Dossier pour l'auditeur externe

- **Périmètre** : application web (Laravel), service d'embeddings interne (FastAPI, non exposé), infrastructure Docker de production (`infra/compose.prod.yml`), reverse proxy TLS. Hors périmètre : hébergeur, poste des administrateurs, déni de service volumétrique.
- **Environnement de test** : instance de préproduction isolée avec données synthétiques (`php artisan db:seed`, 200 fiches d'amorçage) ; comptes `participante`, `moderateur`, `comite`, `admin` (mot de passe et secret TOTP fournis hors bande) ; aucun compte réel.
- **Règles d'engagement** : tests en boîte grise, code source public fourni ; pas de test sur la production ; signalement immédiat de toute donnée réelle rencontrée ; fenêtre convenue ; contact d'urgence.
- **Documents fournis** : `docs/architecture.md`, ce document, `docs/rgpd/aipd.md`, `docs/exploitation.md`, `docs/incidents.md`, résultats des tests automatisés et de `make audit-deps`.
- **Critère d'acceptation** (cahier des charges, lot 5) : aucune faille critique ni élevée ouverte à la fin des corrections. Les constats sont traités dans une branche dédiée avec un test de non-régression par faille corrigée.
