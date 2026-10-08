# Plan du lot 6 — Expérience

Statut : **réalisé le 8 octobre 2026, en attente de la pull request** (plan validé le 8 octobre 2026, « feu vert pour tes reco »).
Référence : cahier des charges, section 1 (principes directeurs), 2 (hors périmètre : profils publics détaillés), 4.3 (votes), 4.7 (plafonds), 7.2 (anti-manipulation), 12 (exigences non fonctionnelles : accessibilité, mobile d'abord, sobriété, identité visuelle), 14. Branche : `lot-6-experience`.

Demande d'origine : « rendre le design beaucoup plus fun, attrayant et gamifier tout ça ».

## 1. Objectif et cadre

Rendre la plateforme chaleureuse, vivante et engageante, pour qu'un visiteur ait envie de lire, de voter et de revenir, sans trahir les principes qui fondent le projet. Trois principes du cahier des charges encadrent directement ce lot :

- **Neutralité** : identité visuelle et vocabulaire neutres, palette sans couleur associée à un parti.
- **Juger les idées, pas les auteurs** : pseudonymat, aucun profil public détaillé (hors périmètre, section 2), aucun classement par popularité.
- **Anti-manipulation et plafonds** : rien ne doit récompenser le volume de votes ou d'arguments, qui est précisément ce qu'une opération organisée produit.

La gamification proposée est donc **un parcours personnel, privé et qualitatif** : on célèbre les bonnes pratiques du débat (lire les deux camps, réviser son avis, arbitrer sous contrainte, sourcer), jamais la quantité ni la comparaison entre participants. Le design, lui, peut être franchement plus joyeux : couleurs, formes, illustrations abstraites, micro-animations, retours immédiats.

### Ce que le lot ne fera pas, et pourquoi

| Mécanique écartée | Raison |
| --- | --- |
| Points, niveaux, classement des participants | Récompense le volume, incite au bourrage ; contredit « arbitrage plutôt que popularité » et « un vote vaut un vote » |
| Badges affichés à côté du pseudonyme | Étiquette les auteurs ; une idée serait lue différemment selon le « grade » de son auteur |
| Séries quotidiennes (« streaks »), relances par e-mail, compte à rebours | Capture de l'attention, pression ; contraire à la sobriété et à la minimisation |
| Récompense liée au sens du vote ou au contenu | Biais évident |
| Couleur « verte » pour « pour » et « rouge » pour « contre » | Oriente la lecture ; les deux camps gardent le même traitement visuel |

### Critères d'acceptation proposés

| # | Critère | Vérification |
| --- | --- | --- |
| F1 | Nouvelle identité visuelle appliquée à toutes les pages publiques et au compte, cohérente sur mobile et bureau | Revue visuelle page par page ; captures avant/après dans le plan |
| F2 | Palette sans couleur partisane, contrastes AA sur chaque couple texte/fond utilisé | Tableau des couples et ratios dans `docs/accessibilite.md` ; axe (via `make a11y`) sans erreur de contraste |
| F3 | Vote et vote rapide donnent un retour immédiat et agréable, utilisables d'une main | Composant de confirmation testé ; cibles 44 px conservées ; vérification mobile |
| F4 | Parcours personnel privé : tableau de bord, jalons qualitatifs, parcours de démarrage | Tests du service : chaque jalon se déclenche exactement sur la règle annoncée, jamais sur un volume |
| F5 | Rien du parcours n'est visible d'un autre participant | Tests : fiches, arguments, journal, recherche, API Livewire ne contiennent ni jalon ni compteur personnel d'autrui |
| F6 | Le parcours est exporté avec les données du compte et supprimé avec lui | Tests sur `AccountExporter` et `AccountEraser` |
| F7 | Animations respectueuses : `prefers-reduced-motion` honoré, aucune animation infinie, aucun son | Test de la feuille de style compilée (règle présente) ; revue manuelle |
| F8 | Sobriété conservée : pages publiques toujours sous 300 Ko, aucune police ni image externe, aucune bibliothèque JavaScript ajoutée | `PageWeightTest` ; CSP inchangée ; `package.json` sans nouvelle dépendance d'exécution |
| F9 | Accessibilité conservée : `make a11y` à 5/5, les nouvelles pages ajoutées à la liste | `app/.pa11yci.json` enrichi (parcours) ; pa11y sans erreur |
| F10 | Classements et règles métier des lots 1 à 5 inchangés | Suite de tests existante verte sans modification de test |

## 2. Périmètre

### Phase A — Identité visuelle « vivante »

- **Palette** (jetons Tailwind dans `app.css`) : encre (neutre chaud, existant) ; **lagon** (sarcelle, teinte 190, existant, couleur d'action) ; **prune** (violet, teinte ~310, couleur d'accent et de célébration) ; **sable** (fond chaud, teinte ~75, faible saturation) ; **brume** (bleu-gris très désaturé pour les surfaces secondaires). Aucun rouge, bleu franc, vert, rose, orange ni jaune en couleur d'identité : ce sont les couleurs des grands partis français. Le rouge reste réservé aux erreurs, comme aujourd'hui.
- **Typographie** : polices système conservées (aucune ressource externe), mais hiérarchie plus marquée : titres plus grands et plus gras, interlignage généreux, chiffres clés en très grand corps.
- **Formes** : coins très arrondis (`rounded-3xl`), ombres douces, boutons en pilule, cartes qui se soulèvent au survol ; les quatre blocs d'une fiche (problème, mesure, coût, sources) se distinguent par une pastille de couleur et un fond teinté, pas par une bordure.
- **Maquette d'intention** validée avant code : https://claude.ai/artifact/EnwncSMkajTa1vPorou3Vo (accueil et fiche en version bureau ; vote rapide, confirmation et parcours en version mobile).
- **Illustrations** : formes géométriques abstraites (cercles, arcs, grilles de points) dessinées en SVG dans `resources/views/components/illustration/`, inclus en ligne par un composant Blade `<x-illustration name="…">`, quelques Ko chacune. Aucune figure humaine ni symbole politique. Un pictogramme neutre par thème (ligne simple), choisi par le comité dans une liste fermée (`Theme::icon`).
- **Pages** :
  - **Accueil** : bandeau d'accroche illustré, « le pouls de la plateforme » (compteurs agrégés : propositions, votes, arbitrages, participants, tous publics et déjà calculés), trois portes d'entrée en grandes cartes (« Donner mon avis en 2 minutes », « Explorer un thème », « Arbitrer sous contrainte »), un arbitrage ouvert mis en avant.
  - **Thèmes** : tuiles colorées (couleur tirée d'une rotation de la palette, jamais liée au sujet) avec pictogramme et compteurs.
  - **Fiche** : en-tête aéré, les quatre blocs du format imposé en cartes distinctes, le double vote au centre de la page en carte « tactile », colonnes pour/contre au même traitement visuel.
  - **Vote** : les six choix deviennent de grands boutons à bascule (icônes neutres : coche, croix, point d'interrogation), animation de sélection, puis **écran de confirmation** : « Merci. Vous êtes le 1 234ᵉ participant à vous prononcer », révélation animée des résultats, bouton « proposition suivante » en vote rapide.
  - **Vote rapide** : visuel de pile de cartes, compteur de session (« 7 fiches lues aujourd'hui », privé, en session), barre d'actions fixée en bas d'écran sur mobile pour le pouce.
  - **Arbitrages** : jauge plus expressive (changement de couleur et petite animation quand la contrainte est atteinte), récapitulatif de la combinaison validée façon « ticket ».
  - **Compte, modération, pages légales** : mêmes composants, sans illustration.
- **Micro-interactions** : transitions CSS uniquement (sélection, apparition des cartes, remplissage des jauges, bascule « utile ») ; une dizaine de lignes de JavaScript dans `app.js` pour la confirmation de vote (écoute d'un événement Livewire, ajout d'une classe). Tout est désactivé sous `prefers-reduced-motion: reduce`.

### Phase B — Parcours personnel (gamification privée)

- **Page « Mon parcours »** (`/mon-compte/parcours`) : anneaux de progression et chiffres personnels (fiches votées, thèmes explorés, arguments jugés utiles, arbitrages validés, votes révisés après lecture), liste des jalons atteints et à atteindre avec la règle écrite en clair sous chacun.
- **Jalons** (liste fermée dans une énumération `Milestone`, règle calculée côté serveur à partir des données existantes) :

  | Clé | Libellé | Règle |
  | --- | --- | --- |
  | `premiere_voix` | Première voix | Premier vote enregistré |
  | `lecture_complete` | Lecture complète | Un vote déposé après avoir déplié les arguments |
  | `deux_points_de_vue` | Deux points de vue | A marqué « utile » au moins un argument pour et un argument contre |
  | `esprit_ouvert` | Esprit ouvert | A révisé un vote après lecture des arguments (quel que soit le sens) |
  | `oui_a_condition` | Nuance | A déposé un « oui, à condition que… » |
  | `premier_arbitrage` | Arbitre | A validé une combinaison dans un arbitrage |
  | `themes_explores` | Exploration | A voté dans cinq thèmes de premier niveau différents |
  | `argument_source` | Sourcé | A publié un argument avec une source |
  | `premiere_proposition` | Proposant | A publié une proposition |

  Aucun jalon ne porte sur un nombre de votes ou d'arguments au-delà du premier. La liste peut être ajustée à la validation.
- **Parcours de démarrage** : trois étapes affichées sur l'accueil et le compte d'un participant connecté tant qu'elles ne sont pas faites (lire une fiche avec ses arguments, voter, arbitrer) ; disparaît ensuite.
- **Célébration** : au premier affichage d'un jalon nouvellement atteint, un encart `role="status"` avec animation discrète (CSS) sur la page courante ; jamais de son, jamais de fenêtre modale.
- **Confidentialité** : les jalons et compteurs ne sont lisibles que par le participant ; ils n'apparaissent ni sur les fiches, ni sur les arguments, ni dans la recherche, ni dans la file de modération, ni dans les rapports de transparence. Export RGPD complété, suppression en cascade.
- **Compteurs collectifs** sur l'accueil (« le pouls ») : agrégats publics déjà disponibles, en cache 60 s comme la page ; pas d'objectif collectif imposé.

### Phase C — Qualité et documentation

- Tableau des couples couleur/contraste dans `docs/accessibilite.md` ; `make a11y` repassé sur les cinq pages plus « Mon parcours » ; `PageWeightTest` repassé ; captures avant/après dans ce plan.
- `docs/architecture.md` : section lot 6, décisions (palette, gamification privée, mécaniques écartées) ; `docs/guide-developpement.md` : repères (jetons, composants, règle « aucun jalon public », `prefers-reduced-motion`).
- Vérification mobile manuelle du vote et du vote rapide (iPhone, Android).

Hors lot : refonte des e-mails (ils restent minimaux), mode sombre (proposé en lot ultérieur si souhaité), consensus V2.

## 3. Modèle de données

| Table | Changement |
| --- | --- |
| `milestones` | nouvelle : `participant_id` (cascade à la suppression), `key` (40), `reached_at` ; clé primaire `(participant_id, key)` ; `seen_at` nullable pour la célébration unique |
| `themes` | `icon` (string 30, nullable, liste fermée dans `App\Enums\ThemeIcon`), choisi par le comité dans le formulaire existant |

Les compteurs personnels sont calculés à la demande à partir de `votes`, `arguments`, `argument_marks`, `tradeoff_answers`, `proposals`, sans table de score. Les jalons sont écrits par un service à partir des mêmes événements, donc toujours recalculables.

## 4. Règles métier côté serveur

- `App\Services\Journey` : `stats(User)`, `evaluate(User)` (calcule et enregistre les jalons atteints), `freshMilestones(User)` (non encore vus). Appelé après `VoteService::cast()`, publication d'argument, marque « utile », `TradeoffService::submit()`, publication de proposition ; toujours dans la transaction de l'écriture ou en file (`ShouldQueue`) si elle s'alourdit.
- Aucun jalon n'est accessible par une route publique ni sérialisé dans un modèle exposé (`$hidden`, pas de relation chargée dans les vues publiques).
- Aucune modification des services `Rankings`, `VoteService`, `ContributionCaps`, `ModerationService` au-delà de l'appel au service de parcours.
- `Theme::icon` validé contre l'énumération dans `ThemeService` (ou le contrôleur du comité existant).

## 5. Tests

- `JourneyTest` : un test par jalon (déclenché par la règle, non déclenché juste avant), idempotence (`evaluate()` deux fois n'écrit qu'une ligne), compteurs justes, aucun jalon pour un volume seul (300 votes sans lecture des arguments ne débloquent que « première voix »).
- `JourneyPrivacyTest` : la fiche, la colonne d'arguments, la recherche, la file de modération et le journal rendus pour un autre compte ne contiennent aucune clé ni libellé de jalon.
- `JourneyPageTest` : page « Mon parcours » réservée au participant connecté, célébration affichée une fois.
- `DataExportTest` et `AccountDeletionTest` complétés (jalons exportés, supprimés).
- `VoteConfirmationTest` : après un vote, la réponse Livewire contient l'écran de confirmation et les résultats ; les règles de vote existantes ne changent pas.
- `StyleTest` : la feuille compilée contient la règle `prefers-reduced-motion`, aucune `@import url(` externe ; `PageWeightTest` inchangé et vert.
- Suite existante : aucune modification de test attendue, sauf des sélecteurs de texte si un libellé change.

## 6. Ordre de réalisation

1. Jetons de couleur, composants de base (bouton, carte, illustration, pictogrammes), mise en page générale : accueil et thèmes.
2. Fiche, vote avec écran de confirmation, vote rapide, arbitrages.
3. Service `Journey`, jalons, page « Mon parcours », parcours de démarrage, célébration ; export et suppression.
4. Compte, modération, pages légales ; accessibilité, poids, captures, documentation ; pull request.

## 7. Questions posées à la validation

| # | Question | Recommandation |
| --- | --- | --- |
| Q1 | Jalons visibles publiquement (à côté du pseudonyme) ? | **Non** : hors périmètre du cahier des charges (profils publics) et contraire à « juger les idées, pas les auteurs » |
| Q2 | Séries quotidiennes et relances par e-mail ? | **Non** : sobriété, minimisation ; le parcours reste visible seulement quand on vient |
| Q3 | Palette lagon + prune + sable (voir maquette) | **Oui** ; aucune de ces teintes n'est celle d'un parti représenté |
| Q4 | Illustrations : dessinées en interne (abstraites) ou banque libre européenne (unDraw, MIT, Grèce) ? | **Interne** : zéro dépendance, poids maîtrisé, aucune figure humaine |
| Q5 | « Le pouls » (compteurs agrégés) visible des visiteurs non connectés ? | **Oui** : agrégats déjà publics (transparence), en cache |
| Q6 | Liste des neuf jalons | **Oui**, telle quelle ; ajustable sans migration (liste fermée en code) |
| Q7 | Pictogramme par thème choisi par le comité | **Oui**, liste fermée de pictogrammes neutres |
| Q8 | Mode sombre | **Reporté** à un lot ultérieur |

## 8. Point d'étape et recette (8 octobre 2026)

Livré en trois phases sur la branche `lot-6-experience` :

- **Phase A** : palette et jetons (`app.css`), composants (`x-button`, `x-card`, `x-pill`, `x-stat`, `x-icon`, `x-illustration`), nouvelle mise en page (menu large à partir de `xl`, menu `<details>` en dessous, outils des rôles regroupés), accueil avec « pouls » et arbitrage mis en avant, thèmes en tuiles avec pictogramme (`themes.icon`, liste fermée `ThemeIcon`, formulaire du comité), fiche en cartes, bloc de vote à grands boutons et écran de remerciement, résultats en barres SVG, vote rapide en pile de cartes avec compteur privé du jour et barre d'actions au pouce, arbitrages (jauge, cartes, résultats), recherche, formulaires, pagination ; restylage mécanique des autres pages (compte, modération, légal, authentification).
- **Phase B** : `Journey`, neuf jalons (`Milestone`), table `milestones`, colonne `votes.after_arguments`, page `/mon-compte/parcours`, parcours de démarrage sur l'accueil, célébration unique (dans le composant Livewire ou au chargement suivant), export et suppression.
- **Phase C** : `make a11y` étendu à six pages, documentation (`architecture.md`, `guide-developpement.md`, `accessibilite.md`), captures dans `docs/plans/captures/lot-6/`.

Écarts avec le plan : les blocs de la fiche se distinguent par une pastille et un fond teinté (pas de bordure gauche) ; le pas 1 du parcours de démarrage est « donner un premier avis » plutôt que « lire une fiche », car aucune lecture n'est tracée (minimisation) ; les clés des jalons ont été rendues distinctives (`oui_a_condition`, `premier_arbitrage`, `themes_explores`, `argument_source`, `premiere_proposition`) pour que le test de confidentialité ne confonde pas un mot courant avec une clé.

| # | Critère | Résultat |
| --- | --- | --- |
| F1 | Identité visuelle sur toutes les pages | Fait ; captures bureau et mobile dans `captures/lot-6/` ; aucun débordement horizontal à 390 px (vérifié par script sur accueil, thèmes, fiche, vote rapide, arbitrage, parcours) |
| F2 | Palette sans couleur partisane, contrastes AA | Tableau des couples dans `docs/accessibilite.md` ; axe sans erreur de contraste sur 6 pages |
| F3 | Retour immédiat au vote, utilisable d'une main | `VoteConfirmationTest` ; cibles 44 px, barre d'actions fixée en bas sur mobile |
| F4 | Parcours privé : tableau de bord, jalons, démarrage | `JourneyTest` (une règle par jalon, 40 votes sans lecture ne débloquent que « Première voix », idempotence), `JourneyPageTest` |
| F5 | Rien du parcours visible d'autrui | `JourneyPrivacyTest` : fiches, recherche, journal, file et dossier de modération, composants Livewire, modèle sérialisé |
| F6 | Export et suppression | `JourneyTest` : bloc `parcours` exporté, lignes supprimées en cascade |
| F7 | Mouvement réduit, aucun son | `StyleTest` : `prefers-reduced-motion` dans la feuille compilée ; aucune animation infinie |
| F8 | Sobriété | `PageWeightTest` vert ; CSS compilée 65 Ko (11 Ko gzip) ; aucune dépendance ajoutée ; `StyleTest` : aucune ressource externe, aucun `style=` dans les vues |
| F9 | Accessibilité | `make a11y` : 6/6 pages sans erreur |
| F10 | Règles des lots 1 à 5 inchangées | Suite existante verte sans modification de test (seul changement : URL canonique dans un nouveau test) |

Suite complète : 235 tests, 1 361 assertions, PHPStan niveau 8, Pint.

Point d'attention découvert : un serveur Vite d'un autre projet écoutait sur le port 5173 de l'hôte et masquait celui du conteneur ; le fichier `public/hot` a été retiré pour que le site de développement serve les ressources compilées. Le conteneur `node` ne sait pas exécuter `vite build` (binaire natif absent pour `linux-arm64-musl`) : la compilation se fait sur l'hôte (`npm run build`), comme pour le lot 5.

## 9. Complément du 8 octobre 2026 : jeu de démonstration réaliste

À la demande d'Arnaud (« toutes les sources ont des liens fake, ça fait pas sérieux »), le jeu synthétique de 200 fiches n'est plus chargé par le seeder de développement. Il est remplacé par `app/database/data/amorcage-demo.csv` : soixante-douze mesures réelles, huit par thème, reprises de recommandations de rapports publics (Cour des comptes, Conseil des prélèvements obligatoires, Conseil d'analyse économique, France Stratégie, Sénat, Haut Conseil pour le climat), avec le chiffrage tel qu'il figure dans le rapport ou « inconnu », et deux liens par fiche vérifiés (réponse HTTP 200 le 8 octobre 2026, textes extraits des PDF pour contrôler les chiffres). L'arbitrage de démonstration « Trouver 20 milliards d'euros par an » s'appuie sur huit de ces mesures ; chaque impact est un ordre de grandeur dont l'hypothèse de calcul est écrite dans le champ « incertitude ». Sur les fiches, une source sans libellé affiche désormais le nom du site et le chemin tronqué plutôt que l'URL brute. Le jeu synthétique reste dans `tests/Fixtures` pour les tests d'import. Ce contenu est une base de travail pour le comité éditorial, pas une position de la plateforme.

Le 8 octobre 2026, à la demande d'Arnaud (« Il faudrait un thème Écologie aussi »), un sixième thème de lancement « Écologie » (pictogramme feuille) a été ajouté au `ThemeSeeder` avec huit mesures tirées de rapports publics récents : Cour des comptes (transition écologique 2025, filière automobile 2026, soutien aux énergies renouvelables 2026, gestion quantitative de l'eau 2023, police de l'eau 2026, forêt métropolitaine dans le rapport public annuel 2024, diagnostic de performance énergétique 2025) et Haut Conseil pour le climat (avis sur le PNACC 3, 2025). Le jalon « Exploration » reste fixé à cinq thèmes par défaut (`JOURNEY_EXPLORATION_THEMES`).

Le même jour, à la demande d'Arnaud (« il faudrait ajouter les thèmes Justice, Logement et Culture & Sport »), trois autres thèmes de lancement ont été ajoutés (pictogrammes marteau, maison, livre), chacun avec huit mesures tirées de rapports publics : Justice (Sénat, exécution des peines 2025 ; Cour des comptes, peines alternatives 2025, surpopulation carcérale 2023, plan 15 000 places 2025, aide juridictionnelle 2023), Logement (Conseil des prélèvements obligatoires 2023 ; Cour des comptes, logements vacants 2025, logement social dans le rapport public annuel 2026, réduction de loyer de solidarité 2025, accès des jeunes au logement 2025 ; Sénat, crise du logement 2024), Culture & Sport (Cour des comptes, pass Culture 2024, éducation artistique et culturelle 2025, accès des jeunes au sport 2025, patrimoine monumental des collectivités 2025, crédits exceptionnels à la culture 2024 ; Sénat, monuments historiques 2026, fonds d'investissement dans le football 2024). Neuf thèmes au total.
