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

Un jeu synthétique de 200 lignes, utilisé par les tests et le seeder de développement, se trouve dans `app/tests/Fixtures/amorcage-200.csv`. Il ne contient aucune proposition réelle.
