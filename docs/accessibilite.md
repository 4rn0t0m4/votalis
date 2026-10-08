# Accessibilité — RGAA 4.1, auto-évaluation

Pages auditées : accueil `/`, thème `/themes/{slug}`, fiche `/propositions/{id}`, vote rapide `/vote-rapide` (connecté), inscription `/inscription`.

Audit automatisé : `make a11y` (pa11y-ci, moteurs axe-core et HTML CodeSniffer, WCAG 2 AA) sur la pile Docker locale. Résultat du 8 octobre 2026 : **5/5 pages sans erreur** après correction de la pagination (attribut `aria-label` sur un `span`, remplacé par une pagination accessible dans `resources/views/vendor/pagination/tailwind.blade.php`).

Déclaration publiée sur `/accessibilite`. Audit par un tiers : à commander (voir plan du lot 5, question 6).

## Grille par thématique

C = conforme · NA = non applicable · P = partiellement conforme (à traiter)

| # | Thématique | Accueil | Thème | Fiche | Vote rapide | Inscription | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Images | NA | NA | NA | NA | NA | Aucune image ; pas de pictogramme porteur d'information |
| 2 | Cadres | NA | NA | NA | NA | NA | |
| 3 | Couleurs | C | C | C | C | C | Palette `ink`/`accent` vérifiée (axe) ; état « utile » et choix de vote doublés par `aria-pressed` ou l'état coché |
| 4 | Multimédia | NA | NA | NA | NA | NA | |
| 5 | Tableaux | NA | C | NA | NA | NA | Classements et journal : `<th>`, `caption` ou titre visible |
| 6 | Liens | C | C | C | C | C | Intitulés explicites ; « Signaler », « Modifier » contextualisés par la fiche ; pagination avec `sr-only` |
| 7 | Scripts | C | C | C | C | C | Livewire : composants utilisables au clavier ; messages dans des zones `role=status`/`alert` ; fonctionnement sans script : lecture complète, formulaires classiques pour signalement, modération, compte |
| 8 | Éléments obligatoires | C | C | C | C | C | `lang="fr"`, `<title>` unique par page, doctype, code valide |
| 9 | Structuration | C | C | C | C | C | Un `h1`, hiérarchie respectée, listes réelles, `nav`, `main`, `footer`, `aside`, fil d'Ariane |
| 10 | Présentation | C | C | C | C | C | Aucun style en ligne, texte redimensionnable, focus visible (`outline` 3 px), cibles tactiles ≥ 44 px sur les actions de vote |
| 11 | Formulaires | C | NA | C | C | C | Étiquettes reliées, `aria-required`, erreurs reliées par `aria-describedby`, `autocomplete` sur les champs d'identité, `fieldset`/`legend` pour les choix de vote |
| 12 | Navigation | C | C | C | C | C | Lien d'évitement, navigation principale `aria-label`, ordre de tabulation logique, pagination `aria-current` |
| 13 | Consultation | C | C | C | C | C | Pas de limite de temps, pas d'ouverture de fenêtre non annoncée, export JSON téléchargeable |

## Vérifications manuelles effectuées

- Parcours clavier complet : inscription, connexion, vote sur une fiche, vote rapide avec dépliage des arguments, signalement, contestation.
- Agrandissement 200 % sans perte de contenu ni défilement horizontal sur 360 px de large.
- Vote rapide : radios, « Voir les arguments », « Passer » et « Proposition suivante » atteignables au pouce, hauteur minimale 44 px.

## À faire

- Vérifier les zones dynamiques (`wire:model.live`, jauge d'arbitrage) avec NVDA et VoiceOver.
- Audit RGAA par un tiers avant l'ouverture publique ; mettre à jour la déclaration.
