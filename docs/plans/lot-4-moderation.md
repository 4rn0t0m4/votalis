# Plan du lot 4 — Modération

Statut : **validé le 8 octobre 2026 (« feu vert pour tes reco »), phase A réalisée, phase B en cours**.
Référence : cahier des charges, sections 3 (rôles), 4.8 (signalement), 6 (modération et transparence), 7 (intégrité), 10 (modèle de données), 13 et 14. Branche : `lot-4-moderation`.

## 1. Objectif et critères d'acceptation

Le lot 4 rend la plateforme gouvernable : chaque participant peut signaler, chaque décision de modération est motivée, publique, immuable et contestable, et les modérateurs disposent de signaux d'intégrité calculés sans jamais être appliqués automatiquement. Comme au lot 3, je propose trois phases sur la même branche, un point d'étape après chacune et une seule pull request.

| # | Critère (section 13) | Vérification |
| --- | --- | --- |
| D1 | Une entrée du journal ne peut être modifiée ni supprimée, même en base | Tests : `UPDATE`, `DELETE` et `TRUNCATE` exécutés en SQL brut sur `moderation_log` lèvent une exception PostgreSQL ; le modèle Eloquent n'expose ni `update()` ni `delete()` |
| D2 | Un modérateur ne peut pas traiter la contestation de sa propre décision | Test : le service refuse la décision d'appel quand l'arbitre est l'auteur de la décision contestée, même avec le rôle comité éditorial ; l'appel reste en attente pour un autre membre |

Critères complémentaires proposés :

| # | Critère | Vérification |
| --- | --- | --- |
| D3 | Seul un compte vérifié peut signaler ; un seul signalement par compte et par contenu ; plafond journalier appliqué côté serveur | Tests : visiteur refusé, doublon refusé par contrainte d'unicité et par le service, plafond refusé par `ContributionCaps` |
| D4 | Un contenu signalé reste visible jusqu'à décision, sauf motif « contenu illégal » où il est masqué immédiatement | Tests sur les deux cas, y compris l'exclusion du contenu masqué des classements, du vote rapide, de la recherche et des doublons |
| D5 | Toute action de modération crée une entrée du journal public ; l'entrée ne contient ni contenu si le motif est « contenu illégal », ni pseudonyme de modérateur, ni e-mail, ni IP | Tests sur chaque action (conserver, masquer, demander une reformulation, rétablir, suspendre, décision d'appel) et sur le rendu public |
| D6 | L'auteur peut contester une fois, dans le délai, et une décision annulée rétablit le contenu | Tests : second appel refusé, appel hors délai refusé, rétablissement effectif et journalisé |
| D7 | Les signaux d'intégrité ne sont calculés que si leurs seuils sont configurés, ne déclenchent aucune action et ne sont visibles que des modérateurs et de l'administrateur | Tests : sans seuil, aucun signal ; avec seuil fixé dans le test, signal créé, contenu et comptes intacts ; visiteur et participant refusés |
| D8 | Le rapport de transparence ne contient que des agrégats | Test : la structure JSON ne comporte aucun identifiant de compte ni texte de contribution |
| D9 | La charte, le journal, la page « Comment fonctionne le classement » et les rapports de transparence sont publics | Tests de réponse 200 sans connexion |

## 2. Périmètre

Phase A — Signalement, file et journal public :

- Bouton « Signaler » sur chaque fiche et chaque argument, pour les participants vérifiés, avec motif choisi dans la liste fermée de la charte (contenu illégal, attaque personnelle, hors sujet, doublon, spam, désinformation manifeste, campagne coordonnée) et précision facultative de 300 caractères. Plafond de signalements par jour dans `ContributionCaps`.
- Motif « contenu illégal » : masquage immédiat du contenu en attendant la décision ; autres motifs : contenu visible. Le contenu masqué est remplacé par un bandeau « Contenu masqué » avec le motif et un lien vers l'entrée du journal ; pour un contenu illégal, ni titre ni lien.
- Espace `/moderation` (capacité `moderate`) : file triée par gravité puis ancienneté, un dossier par contenu signalé regroupant ses signalements, avec le contexte : contenu, auteur pseudonymisé (pseudonyme, ancienneté du compte, nombre de décisions antérieures le concernant), historique agrégé des signaleurs (nombre de signalements émis, part retenue), sans révéler aux modérateurs qui a signalé.
- Actions : conserver, masquer avec motif, demander une reformulation. Pas de suppression. La fusion reste en V2 ; un « doublon » avéré est masqué avec référence à la fiche conservée.
- Reformulation : le contenu passe en statut « reformulation demandée » (invisible du public), l'auteur peut modifier le fond une fois malgré le verrou du premier vote, la révision est historisée, le contenu redevient visible et une entrée « reformulation reçue » est journalisée. Le modérateur peut remasquer.
- Journal public `/journal-de-moderation` : date, type de contenu, action, motif, rôle de l'acteur (modérateur ou comité), lien vers le contenu sauf motif illégal ; paginé, filtrable par type et action. Table `moderation_log` en ajout seul, protégée par un déclencheur PostgreSQL interdisant `UPDATE`, `DELETE` et `TRUNCATE`.
- Pages publiques : `/charte-de-moderation` (motifs, procédure, délais, contestation) et `/comment-fonctionne-le-classement` (version en langage simple de `docs/classement.md`, avec lien vers le code du service `Rankings` sur le dépôt).

Phase B — Contestation et information des auteurs :

- Page `/mon-compte/moderation` : décisions concernant mes contributions, contenu masqué consultable par son auteur, formulaire de contestation (1 000 caractères), une seule fois par décision, dans un délai de 14 jours.
- Notification par e-mail à l'auteur lors d'un masquage, d'une demande de reformulation et d'une décision d'appel ; message minimal sans contenu ni motif détaillé (« une décision concerne l'une de vos contributions »), le détail étant sur le compte.
- Espace `/moderation/contestations` (capacité `arbitrate-appeals`, comité éditorial) : liste des appels, décision confirmer ou annuler avec motivation ; le service refuse qu'un membre tranche l'appel d'une décision qu'il a prise. Une annulation rétablit le contenu ; chaque décision d'appel est journalisée.
- Suspension de compte par le comité éditorial uniquement (motif de la charte, durée ou indéfinie) : le compte peut lire et contester, ne peut plus voter, proposer, argumenter ni signaler. Journalisée (« compte suspendu », sans pseudonyme) et contestable comme toute décision. Nécessaire pour la ligne « comptes suspendus » du rapport de transparence.

Phase C — Signaux d'intégrité et transparence :

- Commande `integrity:scan` planifiée chaque nuit, calculant quatre signaux sur les 24 dernières heures : pic d'inscriptions ou de votes sur une fiche, comptes récents votant de manière quasi identique, contributions presque identiques de comptes différents (similarité d'embeddings via `EmbeddingClient`), activité concentrée sur des plages horaires atypiques. Un signal décrit des cibles et une gravité ; il ne modifie jamais un contenu ni un compte.
- Seuils lus depuis l'environnement (`INTEGRITY_*`), **sans valeur par défaut dans le dépôt** : un seuil absent désactive le signal. `.env.example` liste les noms sans valeur. Les tests fixent les seuils en configuration.
- Page `/moderation/signaux` : signaux par gravité et date, statuts nouveau, examiné, confirmé, écarté. Lecture seule pour l'administrateur technique (`manage-platform`), conformément au tableau des rôles.
- Rapport de transparence : commande `transparency:report` planifiée le premier jour de chaque trimestre, agrégats en JSON (signalements par motif, actions par type, appels et issues, comptes suspendus, signaux confirmés), page publique `/transparence` listant les rapports. Génération manuelle possible pour une période donnée.
- Procédure de signalement à VIGINUM et à l'ANSSI documentée dans `docs/incidents.md` (document, pas de code).

Reporté : fusion et familles (V2), vérification téléphonique (V2), RGPD export et suppression (lot 5), audit externe et RGAA (lot 5).

## 3. Modèle de données

| Table | Champs | Remarques |
| --- | --- | --- |
| reports | id, target_type, target_id, reporter_id (nullable), motive, details (300, nullable), status (`open`, `handled`), log_entry_id (nullable), created_at | Index unique (target_type, target_id, reporter_id) ; CHECK sur motive et status ; relation morphique vers proposals et arguments |
| moderation_log | id, target_type, target_id, action, motive (nullable), actor_id (nullable), actor_role, details (JSON, nullable), created_at | Ajout seul : déclencheur `BEFORE UPDATE OR DELETE` et `BEFORE TRUNCATE` levant une exception ; `details` ne contient jamais de texte de contribution, seulement des identifiants (fiche conservée pour un doublon, durée d'une suspension) |
| appeals | id, log_entry_id (unique), author_id, body (1000), status (`pending`, `confirmed`, `overturned`), decided_by (nullable), decision_log_entry_id (nullable), decided_at, created_at | Un appel par décision ; `decided_by` différent de `actor_id` de l'entrée contestée, vérifié par le service et par une contrainte |
| proposals, arguments (ajouts) | status élargi : `rewrite_requested` ; hidden_motive (nullable) ; rewrite_allowed_until (proposals) | Les requêtes publiques filtrent déjà sur `published` |
| users (ajouts) | suspended_until (nullable), suspended_at (nullable) | Aucun motif en clair sur le compte : le motif vit dans le journal |
| integrity_signals | id, type, severity, targets (JSON), details (JSON), status (`new`, `reviewed`, `confirmed`, `dismissed`), reviewed_by, reviewed_at, created_at | Visible des modérateurs et de l'administrateur ; `targets` ne contient que des identifiants internes |
| transparency_reports | id, period_start, period_end, data (JSON), generated_at | Public, agrégats uniquement |

Gravité des motifs, pour le tri de la file : contenu illégal (3) ; attaque personnelle, désinformation manifeste, campagne coordonnée (2) ; hors sujet, doublon, spam (1).

## 4. Règles métier côté serveur

- `App\Services\ReportService` : seul point de création d'un signalement ; vérifie le rôle, l'unicité, le plafond, l'absence de suspension, et masque immédiatement sur motif illégal.
- `App\Services\ModerationService` : seul point d'action d'un modérateur (conserver, masquer, demander une reformulation, rétablir, suspendre) ; chaque action écrit l'entrée du journal dans la même transaction et ferme les signalements du dossier.
- `App\Services\AppealService` : dépôt (auteur du contenu, une fois, dans le délai), décision (capacité `arbitrate-appeals`, arbitre différent de l'auteur de la décision), rétablissement si annulation.
- `App\Services\IntegrityScanner` : un détecteur par signal, chacun inactif sans seuil ; écrit uniquement dans `integrity_signals`.
- `App\Services\TransparencyReporter` : agrégats par période.
- `ContributionCaps::assertCanReport()` et vérification de suspension dans `assertCanVote`, `assertCanCreateProposal`, `assertCanCreateArgument`.
- Gates : `moderate` (file, actions, signaux), `arbitrate-appeals` (appels, suspension), `manage-platform` (signaux en lecture seule). Policies `ReportPolicy`, `AppealPolicy`.
- Le modèle `ModerationLogEntry` interdit `update`, `delete` et `save` sur une entrée existante, en plus du déclencheur.

## 5. Interface

- Livewire `ReportButton` (fiche et arguments), `ModerationQueue`, `ModerationCase` (actions), `AppealForm`, `IntegritySignals`.
- Pages Blade : journal public, charte, comment fonctionne le classement, transparence, mon compte / modération, bandeaux de contenu masqué.
- Lien « Signaler » discret, vocabulaire neutre, aucun pseudonyme de modérateur affiché nulle part.
- Chaînes dans `lang/fr/moderation.php`.

## 6. Tests

- Feature `Moderation/` : signalement, file, actions, journal (contenu, immuabilité D1), bandeaux, reformulation, appel (D2, délai, unicité, rétablissement), suspension, notifications (`Notification::fake`), signaux (seuils absents et présents, cibles, droits), rapport (agrégats), pages publiques.
- Unit : tri de gravité, détecteurs d'intégrité avec données construites, agrégateur du rapport.
- Jamais de pseudonyme de modérateur, d'e-mail ni d'IP dans le journal ni dans le rapport : assertions négatives systématiques.

## 7. Ordre de réalisation

1. Phase A : migrations (reports, moderation_log et déclencheur, statuts), `ReportService`, `ModerationService`, bouton, file, actions, journal, charte, page du classement. Point d'étape.
2. Phase B : appels, page compte, notifications, suspension. Point d'étape.
3. Phase C : signaux, planification, rapport, page transparence, `docs/incidents.md`, mise à jour de `docs/architecture.md`, `docs/classement.md`, `docs/guide-developpement.md`. Recette D1 à D9, pull request.

## 8. Point d'étape phase A (8 octobre 2026)

Livré : signalement (formulaire dédié sans JavaScript), masquage immédiat sur « contenu illégal », file et dossiers de modération, actions conserver / masquer / reformulation, journal public immuable (déclencheur testé en UPDATE, DELETE, TRUNCATE), bandeaux de contenu masqué, charte et page du classement. Écarts par rapport au plan : formulaires en Blade plutôt qu'en Livewire (aucune interactivité nécessaire) ; la reformulation est réservée aux propositions, les arguments n'étant pas modifiables ; le tri de la file est calculé en PHP depuis l'énumération des motifs. Suite : 178 tests, 804 assertions, PHPStan niveau 8.

## 9. Questions tranchées le 8 octobre 2026 (« feu vert pour tes reco »)

| Question | Décision |
| --- | --- |
| Q1 Masquage immédiat | Dès le premier signalement « contenu illégal », avec plafond de signalements et historique du signaleur |
| Q2 Suspension | Incluse, réservée au comité éditorial, journalisée et contestable |
| Q3 Délai d'appel | 14 jours, configurable (`APPEAL_DAYS`) |
| Q4 Notification | E-mail minimal sans contenu ni motif ; fournisseur européen à choisir au lot 5 |
| Q5 Reformulation | Contenu invisible pendant la reformulation, une modification de fond, republication immédiate |
| Q6 Doublons | Masquage avec lien vers la fiche conservée, votes non transférés |
| Q7 Administrateur | Lecture seule des signaux |

## 10. Questions posées à la validation

- **Q1 — Masquage immédiat sur « contenu illégal ».** Le cahier des charges l'impose dès le premier signalement. Un compte malveillant peut donc faire disparaître n'importe quelle fiche le temps d'une décision. Je propose de l'appliquer tel quel, avec deux garde-fous : plafond de signalements par jour (10 par défaut, 5 pour un compte de moins de 7 jours) et historique du signaleur visible des modérateurs (part de signalements retenus). Convient-il, ou faut-il exiger deux signalements « illégal » distincts avant masquage ?
- **Q2 — Suspension de compte.** Le rapport de transparence doit compter les « comptes suspendus », mais les actions listées en 6 ne la mentionnent pas. Je propose de la réserver au comité éditorial, journalisée et contestable. D'accord, ou reporter la suspension au lot 5 ?
- **Q3 — Délai de contestation.** Le texte fixe 14 jours pour les fusions, rien pour la modération. Je propose 14 jours, configurable.
- **Q4 — Notification par e-mail.** Je propose un e-mail minimal (sans contenu ni motif) à chaque décision concernant l'auteur, le détail étant sur le compte. En production, cela suppose un fournisseur d'envoi européen, à choisir au lot 5 avec l'hébergeur. D'accord ?
- **Q5 — Reformulation.** Je propose que le contenu soit invisible pendant la reformulation, que l'auteur dispose d'une seule modification de fond malgré le verrou, et que la fiche redevienne visible dès l'enregistrement sans nouvelle validation (le modérateur peut remasquer). Préférez-vous une validation par le modérateur avant republication ?
- **Q6 — Doublons sans fusion.** La fusion est en V2. Je propose que « doublon » conduise à masquer la fiche avec un lien vers la fiche conservée ; les votes ne sont pas transférés. Convient-il ?
- **Q7 — Accès de l'administrateur technique aux signaux.** Le tableau des rôles lui donne « consulter les signaux anti-fraude » alors que la table est « visible des modérateurs seulement ». Je propose la lecture seule pour l'administrateur, sans pouvoir changer le statut d'un signal.
