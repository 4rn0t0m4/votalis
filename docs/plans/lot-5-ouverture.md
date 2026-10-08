# Plan du lot 5 — Ouverture

Statut : **réalisé le 8 octobre 2026, en attente de la pull request** (plan validé le 8 octobre 2026, « feu vert pour tes reco »).
Référence : cahier des charges, sections 7 (sécurité, résilience), 8 (RGPD), 11 (exigences non fonctionnelles), 12 (hébergement), 13 et 14. Branche : `lot-5-ouverture`.

## 1. Objectif et critères d'acceptation

Le lot 5 rend la plateforme ouvrable au public : droits RGPD en libre-service, documents légaux, accessibilité RGAA, préparation de l'audit de sécurité externe, tenue en charge, exploitation (sauvegardes, lecture seule, déploiement). Deux critères viennent du cahier des charges ; ils dépendent en partie de prestataires externes (auditeur, hébergeur) que je ne peux pas remplacer : le lot livre tout ce qui est faisable dans le dépôt et un dossier prêt pour chaque intervention externe.

| # | Critère (section 13) | Vérification |
| --- | --- | --- |
| E1 | Audit de sécurité externe sans faille critique ni élevée ouverte | Hors de ma portée directe : je livre l'auto-évaluation OWASP ASVS niveau 2, l'audit des dépendances en CI, le dossier d'audit (périmètre, comptes de test, architecture) ; l'audit est commandé par vous et ses constats traités dans une branche dédiée |
| E2 | Audit RGAA AA passé sur les 5 pages principales | Audit automatisé (pa11y, axe) sur accueil, thème, fiche, vote rapide, inscription, zéro erreur ; grille RGAA 4.1 manuelle remplie page par page dans `docs/accessibilite.md` ; déclaration d'accessibilité publiée |

Critères complémentaires proposés :

| # | Critère | Vérification |
| --- | --- | --- |
| E3 | Export JSON de ses données complet, sans donnée d'un tiers | Test : l'export contient compte, votes, conditions, propositions, arguments, réponses d'arbitrage, signalements émis, contestations ; aucun pseudonyme ni identifiant d'un autre compte |
| E4 | Suppression de compte en libre-service : votes effacés, contenus publiés rattachés à « participant supprimé », journal de modération intact | Tests : votes et réponses d'arbitrage supprimés, `author_id` nul sur propositions et arguments, entrées du journal inchangées, e-mail et haché effacés, connexion impossible |
| E5 | Comptes inactifs prévenus puis supprimés après 3 ans | Tests sur la commande planifiée : préavis à 35 mois, suppression à 36, aucune action sur un compte actif ou privilégié |
| E6 | Aucune donnée personnelle conservée au-delà des durées annoncées | Revue : aucune IP ni journal de connexion en base ; sessions et jetons expirés purgés ; test sur la purge |
| E7 | Pages publiques sous 300 Ko au premier chargement, vote rapide utilisable d'une main | Test automatisé du poids des réponses et des ressources référencées ; vérification manuelle mobile |
| E8 | Tenue en charge : pages publiques p95 < 1 s, vote < 300 ms, 500 votes par minute | Scénario de charge rejoué localement sur la pile Docker, résultats consignés ; seuils vérifiés par le script |
| E9 | Mode lecture seule activable en un clic ; sauvegarde chiffrée et restauration testées | Tests : en lecture seule, toute écriture est refusée avec un message, la lecture reste possible ; script de sauvegarde et procédure de restauration rejoués sur la pile Docker |

## 2. Périmètre

Phase A — Droits et documents RGPD :

- Export JSON depuis « Mon compte » (`/mon-compte/donnees`), généré à la demande, téléchargé immédiatement, jamais stocké.
- Suppression du compte en libre-service avec confirmation du mot de passe : votes, réponses d'arbitrage, marques « utile » et clés d'accès effacés ; propositions et arguments conservés avec `author_id` nul ; signalements et contestations conservés sans texte libre ni auteur ; e-mail, haché et secrets 2FA effacés, sessions révoquées. Le journal de modération, sans clé étrangère, reste intact.
- Comptes inactifs : colonne `last_seen_at` (date seule, mise à jour au plus une fois par jour), préavis par e-mail à 35 mois d'inactivité, suppression à 36 mois par la même procédure que la suppression volontaire ; commande planifiée quotidienne ; comptes privilégiés exclus.
- Pages légales : politique de confidentialité, mentions légales, politique cookies (cookies techniques seulement, aucune bannière), mentions de consentement revues à l'inscription. Documents internes : registre des traitements et analyse d'impact (AIPD) dans `docs/rgpd/`, rédigés comme projets à faire valider par un juriste ou un délégué à la protection des données.
- Durées de conservation appliquées : purge des sessions expirées et des jetons de réinitialisation ; aucun journal de connexion n'existe.

Phase B — Accessibilité et sobriété :

- Audit automatisé local (`make a11y`) avec pa11y et axe-core sur les cinq pages principales, zéro erreur exigé ; corrections (libellés, ordre de focus, contrastes, régions, messages d'état annoncés).
- Grille RGAA 4.1 remplie page par page dans `docs/accessibilite.md` ; déclaration d'accessibilité publiée sur `/accessibilite`.
- Vote rapide : boutons atteignables au pouce, taille minimale 44 px, pas de double saisie ; vérification manuelle sur mobile.
- Test automatisé du poids initial des pages publiques (HTML + CSS + JS) sous 300 Ko.

Phase C — Sécurité, charge et exploitation :

- CI : audit des dépendances (`composer audit`, `npm audit --omit=dev`, `pip-audit`) bloquant sur une faille haute ou critique.
- Auto-évaluation OWASP ASVS niveau 2 dans `docs/securite.md` (chaque exigence : couverte, partielle, non applicable, à faire) et dossier pour l'auditeur externe : périmètre, architecture, comptes de test, règles d'engagement, contact `SECURITY.md`.
- Mode lecture seule : interrupteur en configuration et page `/admin` (capacité `manage-platform`) pour l'activer en un clic ; toute écriture refusée côté serveur avec un bandeau public explicite.
- Cache des pages publiques (accueil, thèmes, fiches, journal) avec invalidation à l'écriture ; en-têtes de cache pour les ressources statiques.
- Sauvegardes : script `infra/backup/backup.sh` (pg_dump chiffré avec age, horodaté, rotation) et `restore.sh`, procédure de test mensuel dans `docs/exploitation.md`.
- Déploiement : `infra/compose.prod.yml` (app, worker, scheduler, nginx, PostgreSQL, Redis, Meilisearch, embeddings), guide `docs/exploitation.md` (hébergeur européen, reverse proxy TLS, secrets, planificateur, files, rotation des clés, fournisseur d'e-mail européen).
- Test de charge : scénarios k6 dans `infra/load/` (lecture publique, vote rapide authentifié), rejoués sur la pile Docker ; résultats et goulots dans le plan.

Reporté : vérification téléphonique, fusions, consensus (V2) ; export open data (V3) ; Matomo (voir Q2).

## 3. Modèle de données

| Table | Changement |
| --- | --- |
| users | `last_seen_at` (date), `inactivity_notice_sent_at` ; à la suppression : ligne supprimée (les contenus portent déjà `author_id` nullable avec `nullOnDelete`) |
| appeals, reports | à la suppression de l'auteur : `author_id` / `reporter_id` nuls (déjà), `body` et `details` remplacés par un libellé neutre |
| settings | nouvelle table clé-valeur minimale pour l'interrupteur lecture seule (`read_only`, `read_only_message`) |

## 4. Règles métier côté serveur

- `App\Services\AccountExporter` : seule source de l'export ; chaque bloc ne contient que les données du compte.
- `App\Services\AccountEraser` : seule procédure de suppression, utilisée par le libre-service et par la purge d'inactivité ; transactionnelle ; journalise un comptage anonyme (pas d'entrée au journal public : la suppression n'est pas une décision de modération).
- `App\Services\ReadOnlyMode` + middleware : toute requête d'écriture (POST, PUT, DELETE, Livewire) refusée hors connexion, déconnexion et administration ; les services métier vérifient aussi le mode avant d'écrire.
- Purge : commande `accounts:purge-inactive` planifiée chaque nuit ; `sessions:purge` pour les sessions et jetons expirés.

## 5. Tests

- Feature `Account/` : export (contenu, absence de tiers), suppression (effets, impossibilité de connexion, contenus conservés, journal intact), inactivité (préavis, suppression, exclusions).
- Feature `Platform/` : lecture seule (écritures refusées, lecture permise, Livewire inclus), cache (invalidation après écriture), poids des pages.
- Scripts : `make a11y`, `make load`, `make backup-test` documentés et exécutés en recette.

## 6. Ordre de réalisation

1. Phase A : export, suppression, inactivité, pages légales, documents RGPD. Point d'étape.
2. Phase B : audit d'accessibilité, corrections, déclaration, poids des pages. Point d'étape.
3. Phase C : audit des dépendances en CI, ASVS, lecture seule, cache, sauvegardes, compose de production, charge. Recette E1 à E9, mise à jour de `docs/architecture.md`, `docs/guide-developpement.md`, `README.md`, pull request.

## 7. Point d'étape phase A (8 octobre 2026)

Livré : export JSON (E3), suppression en libre-service avec mot de passe (E4), date de dernière visite et purge d'inactivité avec préavis (E5), purge quotidienne des jetons (E6), pages confidentialité, mentions légales et cookies, lien de consentement à l'inscription, registre des traitements et AIPD en projet. Suite : 204 tests, 1 022 assertions. Point d'attention découvert : `logout()` après `delete()` réinsérait le compte via la rotation du jeton « se souvenir de moi » ; l'ordre est inversé et documenté.

## 8. Point d'étape phase B (8 octobre 2026)

Livré : audit automatisé pa11y/axe sur les cinq pages (5/5 sans erreur après correction de la pagination), grille RGAA 4.1 (`docs/accessibilite.md`), déclaration d'accessibilité `/accessibilite`, cibles tactiles ≥ 44 px pour le vote et le vote rapide, test automatisé du poids des pages (E7, toutes sous 300 Ko). L'audit RGAA par un tiers reste à commander (Q6). Suite : 205 tests.

## 9. Point d'étape phase C et recette (8 octobre 2026)

Livré : audit des dépendances en CI, auto-évaluation ASVS niveau 2 et dossier d'audit, mode lecture seule (page `/admin`, commande, middleware et services), cache des pages publiques invalidé à l'écriture, compose de production et image dédiée, guide d'exploitation, scripts de sauvegarde chiffrée et de restauration, scénarios k6 et mesure serveur des votes. Bug corrigé au passage : les classements mis en cache renvoyaient une erreur 500 avec Redis (Laravel 13 ne désérialise plus d'objets) ; le cache ne contient plus que des identifiants, avec un test de non-régression.

| # | Critère | Résultat |
| --- | --- | --- |
| E1 | Audit externe sans faille critique ni élevée | **À commander** : dossier prêt dans `docs/securite.md` ; auto-évaluation ASVS sans point rouge ; `make audit-deps` : Composer et npm sans faille ; pip-audit signalait starlette 0.46 et transformers 4.57, corrigé par la mise à jour de `consensus/requirements.txt` (FastAPI 0.143, sentence-transformers 6), image reconstruite, test d'intégration C2 toujours vert |
| E2 | Audit RGAA AA sur 5 pages | Automatisé : 5/5 sans erreur (`make a11y`) ; grille manuelle `docs/accessibilite.md` ; déclaration `/accessibilite`. Audit tiers **à commander** |
| E3 | Export complet sans donnée de tiers | `DataExportTest` |
| E4 | Suppression : votes effacés, contenus conservés sans auteur, journal intact | `AccountDeletionTest` |
| E5 | Comptes inactifs prévenus puis supprimés | `InactivityPurgeTest` |
| E6 | Conservation | Aucune IP ni journal de connexion en base ; `auth:clear-resets` planifié ; sessions Redis à expiration |
| E7 | Pages < 300 Ko, vote rapide d'une main | `PageWeightTest` ; cibles 44 px, vérification mobile manuelle |
| E8 | Charge : pages p95 < 1 s, vote < 300 ms, 500 votes/min | k6 lecture publique 50 VU : p95 34 ms, 0 erreur ; vote rapide 8 VU : p95 103 ms ; 500 votes serveur : p95 2,7 ms (pile Docker locale, 5 processus PHP-FPM) |
| E9 | Lecture seule ; sauvegarde et restauration | `ReadOnlyModeTest` ; `make backup-test` : sauvegarde chiffrée 348 Ko, restauration dans `votalis_restore_test` avec comptages |

Suite complète : 212 tests, 1 096 assertions, PHPStan niveau 8, Pint.

## 10. Questions tranchées le 8 octobre 2026 (« feu vert pour tes reco »)

| Question | Décision |
| --- | --- |
| Q1 Hébergeur, e-mail | Guide générique : OVHcloud ou Scaleway, fournisseur d'e-mail européen |
| Q2 Matomo | Reporté au déploiement, service optionnel, aucun script de mesure dans le MVP |
| Q3 Suppression | Signalements et contestations conservés sans auteur ni texte libre |
| Q4 Inactivité | Date de dernière visite (au plus une mise à jour par jour), préavis unique 30 jours avant |
| Q5 Charge | k6, exécuté localement |
| Q6 Audits externes | Constats traités dans une branche ultérieure |
| Q7 Responsable de traitement | Champs à compléter dans les documents |
| Q8 Lecture seule | Page `/admin` (administrateur technique) + commande `votalis:read-only` |

## 11. Questions posées à la validation

- **Q1 — Hébergeur et e-mail.** Le guide de déploiement peut rester générique (OVHcloud ou Scaleway, fournisseur d'e-mail européen comme Brevo). Avez-vous déjà un choix, ou je documente les deux options avec leurs points de vigilance ?
- **Q2 — Matomo.** Le cahier des charges prévoit une mesure d'audience Matomo auto-hébergée en mode exempté. Je propose de la reporter au déploiement (service optionnel dans le compose de production, aucun script de mesure dans le MVP tant qu'elle n'est pas installée). D'accord ?
- **Q3 — Suppression : signalements et contestations.** Je propose de conserver les signalements et contestations d'un compte supprimé sans auteur et sans texte libre (remplacé par « supprimé à la demande de l'auteur »), pour que les statistiques de transparence restent exactes. Préférez-vous les supprimer entièrement ?
- **Q4 — Inactivité.** « Inactif » = aucune visite connectée depuis 3 ans, mesurée par une date de dernière visite mise à jour au plus une fois par jour (pas de journal de connexion). Préavis unique par e-mail 30 jours avant la suppression. Convient-il ?
- **Q5 — Outil de charge.** Je propose k6, outil libre exécuté localement contre la pile Docker : aucune donnée ne sort. Son éditeur est américain, mais il s'agit d'un logiciel, pas d'un service. Acceptable, ou préférez-vous Locust (Python) ?
- **Q6 — Audits externes.** L'audit de sécurité et, si vous le souhaitez, l'audit RGAA formel doivent être commandés à des prestataires. Je prépare les dossiers ; confirmez-vous que leurs constats seront traités dans une branche ultérieure, hors de ce lot ?
- **Q7 — Responsable de traitement.** Les documents légaux doivent nommer le responsable de traitement (association, société, personne) et un contact. Je mets des champs à compléter ; pouvez-vous me donner ces éléments, ou je les laisse en attente ?
- **Q8 — Lecture seule.** Je propose une page `/admin` minimale pour l'administrateur technique avec l'interrupteur et un message public personnalisable, plus une commande `votalis:read-only on|off`. D'accord ?
