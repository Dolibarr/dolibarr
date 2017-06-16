# DOLIBARR ERP & CRM

Dolibarr ERP & CRM est un logiciel moderne pour gérer votre activité (société, association, auto-entrepreneurs, artisans). 
Il est simple d'utilisation et modulaire, vous permettant de n'activez que les fonctions dont vous avez besoin (contacts, fournisseurs, factures, commandes, stocks, agenda, ...).
 
![ScreenShot](https://www.dolibarr.org/images/dolibarr_screenshot1_640x480.png)



## LICENCE

Dolibarr est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure.



## INSTALLER DOLIBARR

Si vous n'avez pas de connaissances techniques, et que vous recherchez
un programme d'installation qui install Dolibarr ERP/CRM en quelques clics,
vous devez vous réorienter vers DoliWamp (la version tout-en-un
de Dolibarr pour Windows), DoliDeb (la version tout-en-un pour Debian ou
Ubuntu) ou DoliRpm (la version tout-en-un de Dolibarr pour Fedora, Redhat,
OpenSuse, Mandriva ou Mageia).

Vous pouvez les télécharger depuis la rubrique *download* du portail officiel: 
https://www.dolibarr.org/

Si vous avez déjà installé un serveur Web avec PHP et une base de donnée (MariaDb/MySql/PostgreSql),
vous pouvez installer Dolibarr avec cette version de la manière suivante:

- Copier le répertoire "dolibarr" et son contenu dans la racine de votre serveur
  web, ou bien copier le répertoire sur le serveur et configurer ce serveur pour
  utiliser "dolibarr/htdocs" comme racine d'un nouveau virtual host (ce second 
  choix requiert des compétences et habilitations en administration du serveur
  web).
  
- Créer un fichier vide "htdocs/conf/conf.php" et attribuer les permissions
  en lecture et écriture pour le user du serveur web (les permissions en 
  écriture seront supprimées une fois l'installation terminée).

- Depuis votre navigateur, appeler la page "install/" de dolibarr. L'url dépend 
  du choix fait à la première etape:
   http://localhost/dolibarr/htdocs/install/
  ou
   http://yourdolibarrvirtualhost/install/
   
- Suivez les instructions fournies par l'installeur...



## METTRE A JOUR DOLIBARR

Pour mettre a jour Dolibarr depuis une vieille version vers celle ci:
- Ecraser les vieux fichiers dans le vieux repertoire 'dolibarr' par les fichiers
  fournis dans ce nouveau package.
  
- Au prochain accès, Dolibarr proposera la page de "mise a jour" des données (si necessaire).
  Si un fichier install.lock existe pour vérouiller le processus de mise à jour, il sera demandé de le supprimer manuellement (vous devriez trouver le fichier install.lock dans le répertoire utilisé pour stocker les documents générés ou transféré sur le serveur. Dans la plupart des cas, c'est le répertoire appelé "documents") 

*Note: Le processus de migration peut etre lancé manuellement et plusieurs fois, sans risque, en appelant la page /install/*
  

## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## CE QUE DOLIBARR PEUT FAIRE

### Modules principaux (tous optionnels):

- Annuaires des prospects et/ou client et/ou fournisseurs
- Gestion de catalogue de produits et services
- Gestion des devis, propositions commerciales
- Gestion des commandes
- Gestion des factures clients/fournisseurs et paiements
- Gestion des virements bancaires SEPA
- Gestion des comptes bancaires
- Calendrier/Agenda partagé (avec export ical, vcal) 
- Suivi des opportunités et/ou projets (suivi de rentabilité incluant les factures, notes de frais, temps consommé valorisé, ...)
- Gestion de contrats de services
- Gestion de stock
- Gestion des expéditions
- Gestion des demandes de congès
- Gestion des notes de frais
- GED (Gestion Electronique de Documents)
- EMailings de masse
- Réalisation de sondages
- Point de vente/Caisse enregistreuse
- …

### Autres modules:

- Gestion de marque-pages
- Gestion des promesses de dons
- Gestion de la TVA NPR (non perçue récupérable - pour les utilisateurs français des DOM-TOM)
- Rapports
- Imports/Exports des données
- Connectivité LDAP
- Intégratn de ClickToDial
- Intégration RSS
- Intégation Skype
- Intégration de système de paiements (Paypal, Strip, Paybox...)
- …

### Divers:

- Multi-langue.
- Multi-utilisateurs avec différents niveaux de permissions par module.
- Multi-devise.
- Peux être multi-société par ajout du module externe multi-société.
- Plusieurs thèmes visuels.
- Application simple à utiliser.
- Requiert PHP et MariaDb, Mysql ou Postgresql (Voir versions exactes sur https://wiki.dolibarr.org/index.php/Prérequis). 
- Compatible avec toutes les offres Cloud du marché respectant les prérequis de base de données et PHP.
- Code simple et facilement personnalisable (pas de framework lourd; mécanisme de hook et triggers).
- APIs.
- Génération PDF et ODT des éléments (factures, propositions commerciales, commandes, bons expéditions, etc...)
- …

### Extension

Dolibarr peut aussi être étendu à volonté avec l'ajout de module/applications externes développées par des développeus tiers, disponible sur [DoliStore](https://www.dolistore.com).


## CE QUE DOLIBARR NE PEUT PAS (ENCORE) FAIRE

Voici un liste de fonctionnalites pas encore gérées par Dolibarr:
- Dolibarr ne contient pas de module de Gestion de la paie.
- Les tâches du module de gestion de projets n'ont pas de dépendances entre elle.
- Dolibarr n'embarque pas de Webmail intégré nativement.
- Dolibarr ne fait pas le café (pas encore). 


## DOCUMENTATION

Les documentations utilisateur, développeur et traducteur sont disponible sous forme de ressources de la communautés via la site [Wiki](https://wiki.dolibarr.org).


## CONTRIBUTING

Voir le fichier [CONTRIBUTING](https://github.com/Dolibarr/dolibarr/blob/develop/.github/CONTRIBUTING.md)


## CREDITS

Dolibarr est le résultat du travail de nombreux contributeurs depuis des années et utilise des librairies d'autres contributeurs.

Voir le fichier [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT)


## ACTUALITES ET RESEAUX SOCIAUX

Suivez le projet Dolibarr project sur les réseaux francophones

- Facebook: <https://www.facebook.com/dolibarr.fr>
- Google+: <https://plus.google.com/+DolibarrFrance>
- Twitter: <https://www.twitter.com/dolibarr_france>

ou sur les réseaux anglophones

- [Facebook](https://www.facebook.com/dolibarr)
- [Google+](https://plus.google.com/+DolibarrOrg)
- [Twitter](https://www.twitter.com/dolibarr)
- [LinkedIn](https://www.linkedin.com/company/association-dolibarr)
- [YouTube](https://www.youtube.com/user/DolibarrERPCRM)
- [GitHub](https://github.com/Dolibarr/dolibarr)
