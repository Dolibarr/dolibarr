Interface OSC et Dolibarr
-------------------------

on va l'appeler version 0.

INSTALATION SUR SITE OSC :

copier le répertoire sur le seveur web
le répertoire lib qui  contient la librairie nusoap
le répertoire includes : le fichier configure.php
les fichiers ws_*

Configuration :
Tout est dans le fichier configure.php sous forme de define (accès à la BDD OSC et def du langage par défaut)

C'est tout !

TEST DE L'INSTALLATION

Pour tester l'installation utiliser le client basique fourni à installer sur un serveur web avec php4.

répertoire includes : Par défaut on pointe sur le site osc.tiris.info où j'ai mis à disposition les web services sur un environnement de test. Définir le répertoire où se trouvent les web_services (www.siteosc/webservices)

Ouvrir la page index.html
les liens accèdent à certaines méthodes des webservices
si on obtient une réponse Fault il y a un problème (en principe le message perlet de trouver!!


TEST DEPUIS DOLIBARR

L'intégration dans Dolibarr sera dispo via le cvs.

Une boutique OSC pour tester (avec les webservices installés) est ici http://osc.tiaris.info.
Créez des clients, commandes... Ca fera plus réel. Ca ne vous coûtera rien, mais vous n'aurez rien non plus !

********************
ATTENTION : ce n'est que le tout début de ce développement. Entre autre il n'y a pas encore de contrôle d'accès, donc n'installer que sur des systèmes en tests et non sur des sites en production.
********************

Consulter le wiki pour la doc et le suivi : 
	http://www.dolibarr.com/wikidev/index.php/Discussion_Utilisateur:Tiaris
Suivez la mailing list et le forum pour les discussions sur le sujet (et participez!).

Jean Heimburger			jean@tiaris.info







