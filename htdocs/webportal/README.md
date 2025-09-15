Module Web Portal
================

# Portail Web Personnalisé pour Dolibarr

Ce projet est une version modifiée (un "fork") du projet officiel [Dolibarr ERP & CRM](https://github.com/Dolibarr/dolibarr), spécifiquement adaptée pour les besoins de webmaster67.

L'objectif principal de cette modification est d'ajouter un espace de partage de documents sécurisé et simple d'utilisation pour chaque Tiers (client, partenaire, etc.).

## Fonctionnalités Ajoutées

La principale fonctionnalité ajoutée à ce module `Web Portal` est :

* **Espace "Mes Documents" pour les Clients :** Une nouvelle page a été créée sur le portail, accessible via le menu principal. Elle permet à un client connecté de consulter et télécharger tous les fichiers que l'administrateur a partagés avec lui.
* **Lien Direct avec la GED du Tiers :** La gestion des fichiers reste centralisée dans Dolibarr. Pour partager un fichier avec un client, il suffit de le déposer dans l'onglet **"Fichiers joints"** de sa fiche Tiers dans l'interface d'administration. Le fichier apparaît alors instantanément sur son portail.
* **Système de Dossiers par ID :** Le système est configuré pour trouver les dossiers des Tiers en se basant sur leur ID numérique (ex: `/documents/societe/47/`).

## Configuration Requise

Pour que la nouvelle page de documents fonctionne, les étapes suivantes sont nécessaires dans l'administration de Dolibarr :

1.  **Déclaration du Contrôleur :**
    Le nouveau contrôleur `documentlist.controller.class.php` doit être déclaré dans le fichier `htdocs/webportal/class/context.class.php`.

2.  **Ajout du Lien au Menu :**
    Un lien vers le nouveau contrôleur doit être ajouté dans le template du menu : `htdocs/public/webportal/tpl/menu.tpl.php`.

3.  **Activation de l'Accès :**
    Dans le menu `Accueil > Configuration > Divers`, la clé suivante doit être ajoutée :
    * **Nom :** `WEBPORTAL_DOCUMENT_LIST_ACCESS`
    * **Valeur :** `1`

## Licence

Ce projet est distribué sous la licence **GNU General Public License v3.0**, comme le projet Dolibarr original.

## Crédits

* **Auteurs du module original :** L'équipe Dolibarr et les contributeurs de la communauté open-source.
* **Modifications et Intégration :** webmaster67 sébastien schaffhauser

If the Thirdparty module is enabled:
* Read/modify Name, phone, email, addresses of thirdparty

If the Partnership module is enabled:
* Read properties (status, start date, end date) of its partnership.

If the Proposal module is enabled:
* Read its orders

If the Sale Order module is enabled:
* Read its orders

If the Invoice module is enabled:
* Read its invoices

If the Supplier module is enabled:
* Read its price requests
* Read its orders
* Read its invoices

If module Membership is enabled:
* Read/modify Name, phone, email, addresses of thirdparty
* Read its membership status (start and end date, amount paid)

 

Documentation
-------------

[Module Web Portal](https://wiki.dolibarr.org/index.php/Module_Web_Portal)
