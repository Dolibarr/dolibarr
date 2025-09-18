# Module Web Portal pour Dolibarr

Le module Web Portal offre une interface client sécurisée et personnalisable, permettant aux tiers (clients, fournisseurs, adhérents...) d'accéder à leurs informations et documents directement depuis une interface web publique.

## Nouvelles Fonctionnalités Proposées

Cette contribution ajoute deux nouveaux espaces de gestion de documents pour enrichir le portail.

### 1. Espace "Mes Documents" (par Tiers)

* Une nouvelle page permet à un utilisateur connecté de consulter et télécharger les documents qui lui sont personnellement partagés.
* **Lien avec la GED du Tiers :** Les fichiers sont gérés simplement en les ajoutant dans l'onglet "Fichiers joints" de la fiche Tiers dans l'interface d'administration de Dolibarr. Tout fichier ajouté y est instantanément visible sur le portail du client.
* **Nommage des dossiers :** Le système utilise la **référence** du tiers (ex: `CU25-0001`) pour localiser le répertoire, conformément aux standards de Dolibarr.

### 2. Espace "Documents Partagés" (global)

* Une seconde page a été ajoutée pour afficher une liste de documents communs à **tous** les utilisateurs du portail (par exemple : brochures, conditions générales de vente, etc.).
* **Gestion centralisée :** Les fichiers sont placés dans un répertoire unique au sein du module GED/ECM. Le nom de ce répertoire est configurable par l'administrateur.

## Configuration Requise

Toute la configuration s'effectue depuis la page de paramétrage du module WebPortal (**Accueil > Configuration > Modules > WebPortal**).

1.  **Activer la page "Mes Documents"** : Activez l'option correspondante via l'interrupteur Oui/Non.
2.  **Activer la page "Documents Partagés"** : Activez l'option correspondante. Cette fonctionnalité requiert que le module **ECM/GED** soit activé.
3.  **Choisir le dossier partagé** : Saisissez le nom du répertoire des documents partagés (ex: `DocumentsPublics`) dans le champ de texte prévu à cet effet.

## Licence

Ce projet est distribué sous la licence GNU General Public License v3.0, comme le projet Dolibarr original.
