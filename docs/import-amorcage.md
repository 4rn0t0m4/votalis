# Import d'un jeu d'amorçage

Les propositions d'amorçage (contenu de départ, section 4.2 du cahier des charges) s'importent par la commande :

```sh
php artisan proposals:import chemin/vers/fichier.csv            # importe
php artisan proposals:import chemin/vers/fichier.csv --dry-run  # valide sans écrire
```

L'import est transactionnel : une seule ligne invalide annule tout, et le rapport indique la ligne et le champ fautifs. Les propositions importées portent l'origine « contenu d'amorçage », n'ont pas d'auteur et affichent leur source d'amorçage sur la fiche.

## Format

Fichier CSV encodé en UTF-8, séparateur point-virgule, guillemets doubles pour les champs contenant un point-virgule ou un retour à la ligne. La première ligne est l'en-tête, exactement :

```
theme_slug;title;problem;measure;cost_estimate;cost_unknown;source_urls;seed_source
```

| Colonne | Contenu | Contrainte |
| --- | --- | --- |
| theme_slug | Identifiant du thème (colonne `slug` des thèmes, visible dans l'URL `/themes/<slug>`) | Thème existant et non archivé |
| title | Titre, formulé comme une mesure avec un verbe d'action | 120 caractères maximum |
| problem | Problème visé | 500 caractères maximum |
| measure | Mesure proposée | 1 500 caractères maximum |
| cost_estimate | Coût ou impact estimé, avec sa source si possible | 300 caractères maximum ; vide si `cost_unknown` vaut 1 |
| cost_unknown | `1` si le coût est inconnu, sinon `0` | |
| source_urls | Une ou plusieurs adresses web séparées par `|` | Au moins une adresse `http(s)://` |
| seed_source | Origine du contenu (rapport, programme, institution) affichée sur la fiche | 300 caractères maximum, facultatif |

Les lignes passent exactement par les mêmes règles de validation que le formulaire public.

## Exemple

```
theme_slug;title;problem;measure;cost_estimate;cost_unknown;source_urls;seed_source
sante;Plafonner les dépassements d’honoraires;"Les dépassements…";"La mesure consiste à…";"1 milliard d’euros par an (Cour des comptes 2024)";0;https://www.ccomptes.fr/…|https://www.assemblee-nationale.fr/…;Rapport de la Cour des comptes 2024
```

## Jeux de données fournis

- `app/database/data/amorcage-demo.csv` : **jeu de démonstration** chargé par le seeder de développement (`php artisan db:seed`). Soixante-treize mesures réelles, huit par thème (Économie, Emploi, Santé, Éducation, Défense, Écologie, Justice, Logement, Culture & Sport) plus une mesure de pilotage des retraites dans Emploi, reprises de recommandations formulées dans des rapports publics (Cour des comptes, Conseil des prélèvements obligatoires, Conseil d'analyse économique, France Stratégie, Sénat, Haut Conseil pour le climat, Conseil d'orientation des retraites), avec leur chiffrage tel qu'il figure dans le rapport et les liens exacts vers les documents, vérifiés le 8 octobre 2026. Chaque fiche porte l'origine « contenu d'amorçage » et cite le rapport. Ce jeu sert à la démonstration et peut servir de base de travail au comité éditorial ; il n'engage pas la plateforme sur le fond des mesures.
- `app/tests/Fixtures/amorcage-200.csv` : jeu **synthétique** de 200 lignes utilisé par les tests automatisés d'import. Textes passe-partout et domaines fictifs : ne jamais le charger ailleurs que dans les tests.
