# Procédure d'incident : ingérence et attaque

Référence : cahier des charges, section 7. Cette procédure s'applique dès qu'un signal d'intégrité est **confirmé** par la modération (opération coordonnée) ou qu'une attaque technique est constatée. Elle ne remplace pas la modération ordinaire, qui reste journalisée publiquement.

## 1. Qualifier

| Situation | Qui décide | Délai |
| --- | --- | --- |
| Signal d'intégrité confirmé (comptes coordonnés, pics artificiels, contenus dupliqués en masse) | Comité éditorial, sur proposition de la modération | 48 h après confirmation |
| Attaque technique (déni de service, intrusion, fuite de données, compromission d'un compte privilégié) | Administrateur technique | Immédiat |

Conserver les éléments : identifiants internes des comptes et contenus concernés, horodatages, signaux (`/moderation/signaux`), journaux applicatifs (sans e-mail ni IP par construction). Ne rien supprimer : les décisions de modération restent journalisées, les comptes sont suspendus, pas effacés.

## 2. Signaler une ingérence numérique étrangère : VIGINUM

VIGINUM (Secrétariat général de la défense et de la sécurité nationale) est compétent pour les manœuvres informationnelles impliquant un acteur étranger visant le débat public français.

- Portail : https://www.sgdsn.gouv.fr/notre-organisation/composantes/service-de-vigilance-et-protection-contre-les-ingerences-numeriques-etrangeres
- Transmettre : description des faits, période, volumes (comptes, votes, contenus), signaux ayant conduit à la confirmation, mesures prises. Aucune donnée personnelle au-delà de ce qui est strictement nécessaire ; jamais d'e-mail en clair sans base légale.
- Décision de transmission : comité éditorial, consignée dans le compte rendu interne et comptabilisée dans le rapport de transparence (« opérations coordonnées détectées »).

## 3. Signaler une attaque : ANSSI ou Cybermalveillance.gouv.fr

- Incident de sécurité significatif (intrusion, exfiltration, rançongiciel, compromission) : ANSSI, https://www.ssi.gouv.fr/en-cas-dincident/ (déclaration via le CERT-FR, cert-fr@ssi.gouv.fr).
- Attaque courante (déni de service, hameçonnage visant l'équipe, usurpation) : Cybermalveillance.gouv.fr, https://www.cybermalveillance.gouv.fr/diagnostic.
- En cas de fuite de données personnelles : notification à la CNIL sous 72 h (https://notifications.cnil.fr), information des personnes concernées si le risque est élevé. L'e-mail étant chiffré et le haché séparé, préciser l'état des clés (`APP_KEY`, `EMAIL_HASH_KEY`).
- Décision : administrateur technique, informant le comité éditorial. Rotation des secrets et révocation des sessions si un compte privilégié est en cause.

## 4. Rendre compte

- Chaque incident fait l'objet d'une note interne (date, qualification, autorités saisies, mesures) hors du dépôt public.
- Le rapport de transparence trimestriel (`/transparence`) comptabilise les opérations coordonnées confirmées et les comptes suspendus, sans aucun détail nominatif.
- Si l'incident a affecté la disponibilité ou l'intégrité des votes, une information publique est publiée sur la page d'accueil.
