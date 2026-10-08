# Plan du lot 2 — Contenu

Statut : **en attente de validation** (rédigé le 8 octobre 2026).
Référence : cahier des charges, sections 4.1, 4.2, 4.4, 10, 12 et 13. Branche : `lot-2-contenu`.

## 1. Objectif et critères d'acceptation

Le lot 2 rend la plateforme lisible : des thèmes sur deux niveaux, des fiches de proposition au format imposé avec leurs sources et leur historique, des arguments pour et contre, et l'import d'un jeu d'amorçage au format CSV. Les votes, les arbitrages, la détection de doublons et les classements arrivent au lot 3.

| # | Critère (section 13) | Vérification |
| --- | --- | --- |
| B1 | Une fiche incomplète est refusée avec un message clair | Validation côté serveur de chaque champ et de chaque limite, messages en français nommant le champ et la contrainte ; un test par règle |
| B2 | 200 propositions d'amorçage s'importent sans erreur | Commande `proposals:import` sur un fichier de test de 200 lignes couvrant tous les cas valides ; rapport d'import ; un test d'intégration |

Critères complémentaires que je propose d'ajouter à la recette, issus des sections 3, 4 et 12 :

| # | Critère | Vérification |
| --- | --- | --- |
| B3 | Seul le comité éditorial crée et réorganise les thèmes | Tests : participant, modérateur et administrateur reçoivent 403 |
| B4 | Un thème fermé ou archivé n'accepte aucune nouvelle proposition, même par requête directe | Test côté serveur, pas seulement masquage du bouton |
| B5 | L'historique des modifications d'une fiche est visible et complet | Chaque modification crée une révision ; test |
| B6 | Aucune affiliation politique, aucun e-mail, aucune donnée de compte autre que le pseudonyme n'apparaît sur une fiche | Test sur le HTML rendu |
| B7 | Pages publiques sous 300 Ko et navigables au clavier | Mesure du poids initial dans un test ; revue manuelle clavier des 4 pages du lot |

## 2. Périmètre

Inclus :

- Thèmes : hiérarchie à deux niveaux, statuts ouvert / fermé / archivé, ordre manuel, pages publiques, administration par le comité éditorial.
- Fiches de proposition : création, affichage, correction, historique, sources, origine (contribution citoyenne ou amorçage avec sa source).
- Arguments pour et contre, marque « utile ».
- Import CSV d'amorçage avec rapport, et jeu de données de test de 200 lignes.
- Verrou du fond après le premier vote : le mécanisme est posé (colonne `content_locked_at` et règle de validation), il sera déclenché par le premier vote au lot 3.

Reporté :

- Au lot 3 : votes, vote rapide, arbitrages, classements, plafonds de contribution, détection de doublons (embeddings), onglets « clivantes » et « mises en avant » qui dépendent des votes.
- Au lot 4 : signalement, masquage, file de modération ; la colonne `status` des propositions et arguments est prévue dès maintenant.
- En V2 : familles et variantes ; les colonnes `family_id` et `parent_id` sont créées vides.

## 3. Modèle de données

| Table | Champs | Remarques |
| --- | --- | --- |
| themes | id, parent_id (nullable), name, slug (unique), description (nullable), status (`open`, `closed`, `archived`), position, timestamps | Contrainte : un thème enfant ne peut pas avoir lui-même d'enfant (vérifiée côté serveur et par un test) |
| proposals | id, theme_id, author_id (nullable, mis à null à la suppression du compte), family_id (nullable), parent_id (nullable), title (120), problem (500), measure (1500), cost_estimate (nullable, 300), cost_unknown (bool), origin (`citizen`, `seed`), seed_source (nullable), status (`published`, `hidden`, `merged`), content_locked_at (nullable), timestamps | `embedding vector(1024)` ajoutée au lot 3. Index sur theme_id, status, created_at |
| proposal_sources | id, proposal_id, url (nullable), label (nullable), is_personal (bool) | Règle : au moins une source par proposition ; une source est soit une URL, soit « proposition personnelle » |
| proposal_revisions | id, proposal_id, author_id (nullable), kind (`content`, `typo`), snapshot (JSON : title, problem, measure, cost_estimate, cost_unknown, sources), created_at | Une révision à la création puis à chaque modification ; l'historique public affiche date, auteur (pseudonyme) et type |
| arguments | id, proposal_id, author_id (nullable), side (`for`, `against`), body (600), source_url (nullable), status (`published`, `hidden`), timestamps | |
| argument_marks | participant_id, argument_id, created_at | Clé primaire composite ; une marque « utile » par participant et par argument |

Les auteurs sont référencés par `author_id` et affichés par pseudonyme uniquement. Un compte supprimé laisse `author_id` à null et l'affichage « participant supprimé » (section 8).

## 4. Fonctionnalités et pages

### 4.1 Thèmes

- `/themes` : liste des thèmes ouverts et fermés, groupés par thème parent, avec le nombre de propositions. Les thèmes archivés restent accessibles par leur URL avec un bandeau, mais ne sont plus listés.
- `/themes/{slug}` : description, sous-thèmes, propositions récentes paginées, et deux emplacements réservés (« mises en avant », « vote rapide ») qui s'activeront au lot 3.
- Administration `/comite/themes` (gate `manage-themes`) : créer, modifier, changer le statut, réordonner, rattacher à un parent. Pas de suppression : un thème passe en « archivé ».

### 4.2 Fiches de proposition

- `/propositions/nouvelle` : formulaire en une page, composant Livewire pour les compteurs de caractères et l'ajout de sources, validation finale côté serveur. Champs : thème (liste des thèmes ouverts), titre, problème, mesure, coût ou impact avec case « inconnu », sources (au moins une URL ou la case « proposition personnelle »).
- Vérification du titre « formulé comme une mesure » : le cahier des charges demande un verbe d'action. Je propose une aide visible et pas de contrôle automatique au MVP, car une liste de verbes serait fragile en français ; voir Q1.
- `/propositions/{id}-{slug}` : fiche complète, origine, date, auteur (pseudonyme), sources, historique des révisions, colonnes d'arguments.
- Modification par l'auteur : libre tant que `content_locked_at` est nul ; ensuite seuls titre, problème et mesure peuvent changer et la différence doit rester mineure (distance de Levenshtein sous un seuil configurable, défaut 10 % du texte), sinon refus avec le message « un changement de fond passe par une variante ». Chaque modification crée une révision `content` ou `typo`.
- Création réservée aux participants vérifiés (gate `participate` et e-mail vérifié), uniquement dans un thème ouvert.

### 4.3 Arguments

- Deux colonnes « pour » et « contre » de même largeur sur la fiche, chaque argument avec son auteur, sa date, sa source éventuelle et son nombre de marques « utile ».
- Formulaire par colonne, 600 caractères, source facultative, réservé aux participants vérifiés.
- Marque « utile » : bouton Livewire, une par participant et par argument, retirable. Tri par date au MVP ; le tri par consensus arrive en V2.

### 4.4 Import d'amorçage

- Commande `proposals:import {fichier} [--dry-run]`. Format CSV UTF-8, séparateur point-virgule, en-tête obligatoire : `theme_slug;title;problem;measure;cost_estimate;cost_unknown;source_urls;seed_source`. Les URLs multiples sont séparées par `|`.
- Chaque ligne passe par la même validation que le formulaire ; l'import est transactionnel : une ligne invalide annule tout et le rapport liste la ligne et le champ fautifs. `--dry-run` valide sans écrire.
- Les propositions importées ont `origin = seed`, `author_id = null`, `seed_source` renseignée et une révision initiale.
- Format documenté dans `docs/import-amorcage.md`. Un fichier de test de 200 lignes synthétiques est généré dans `tests/Fixtures/`.

## 5. Choix techniques

- Livewire 4 pour le formulaire de proposition, les compteurs, les sources dynamiques et la marque « utile » ; tout le reste en Blade.
- Slugs générés côté serveur, uniques par table, en français sans accents.
- Pagination serveur, 20 propositions par page.
- Recherche texte : reportée au lot 3 avec Meilisearch, en même temps que la détection de doublons, voir Q2.
- Textes des règles de validation dans `lang/fr/validation.php` et `lang/fr/proposals.php`.

## 6. Tests

- Thèmes : création et réorganisation par le comité uniquement ; deux niveaux maximum ; thème fermé ou archivé refuse une proposition ; thèmes archivés non listés.
- Propositions : chaque limite de caractères refusée avec le bon message ; au moins une source ou « proposition personnelle » ; un seul thème ; participant non vérifié refusé ; révision créée à la création et à chaque modification ; verrou du fond respecté ; fiche sans donnée personnelle.
- Arguments : 600 caractères, côté obligatoire, marque « utile » unique et retirable, affichage en deux colonnes.
- Import : 200 lignes valides importées ; une ligne invalide annule tout et nomme la ligne ; `--dry-run` n'écrit rien ; thème inconnu refusé.
- Poids des pages publiques du lot sous 300 Ko.

## 7. Ordre de réalisation

1. Migrations, modèles, factories, enums (statuts, origine, côté).
2. Thèmes : administration pour le comité, pages publiques.
3. Fiches de proposition : formulaire Livewire, validation, révisions, affichage.
4. Modification sous verrou et historique.
5. Arguments et marques « utile ».
6. Import CSV, documentation du format, fixture de 200 lignes.
7. Mise à jour de `docs/architecture.md`, recette B1 à B7, pull request.

## 8. Questions à valider

- **Q1 — Contrôle du titre « formulé comme une mesure ».** Aide affichée seulement (recommandé au MVP), ou contrôle automatique sur une liste de verbes d'action à l'infinitif, avec les faux refus que cela implique ?
- **Q2 — Recherche texte.** Reporter Meilisearch au lot 3 avec la détection de doublons (recommandé), ou l'intégrer dès ce lot pour une recherche sur les titres ?
- **Q3 — Plafonds de contribution.** Le cahier des charges les place au lot 3. Je recommande néanmoins d'appliquer dès ce lot les plafonds « 3 propositions par mois et par thème » et « 20 arguments par jour », puisque les formulaires existent, afin de ne jamais ouvrir un formulaire sans limite côté serveur. Les valeurs restent configurables hors dépôt.
- **Q4 — Thèmes de lancement.** La liste reste une question ouverte du cahier des charges. Je propose d'amorcer avec les thèmes de la section 1 (Économie, Emploi, Santé, Éducation, Défense) sans sous-thèmes, modifiables par le comité ; confirmez ou donnez votre liste.
- **Q5 — Jeu d'amorçage réel.** Disposez-vous déjà d'un fichier de propositions d'amorçage avec leurs sources ? Sinon, le lot livre le format, la commande et un jeu de test synthétique, et le contenu réel viendra du comité éditorial.
- **Q6 — Brouillons.** Faut-il pouvoir enregistrer une proposition en brouillon avant publication ? Je recommande non au MVP : publication immédiate, correction possible ensuite.
