Interface OSC et Dolibarr
-------------------------


INSTALATION A FAIRE SUR LE SITE OSC :

Copier le contenu du répertoire ws_server sur le seveur web OSCommerce.
Cela inclut le répertoire lib qui contient la librairie nusoap, le répertoire includes qui contient
le fichier configure.php et les fichiers ws_*

Configuration :
Tout est dans le fichier configure.php sous forme de define (accès à la BDD OSC et def du langage par défaut)

C'est tout !
Votre application OSCommerce offre maintenant des web services utilisables par d'autres application (comme
Dolibarr).



TEST DE L'INSTALLATION

Ouvrir la page ws_index.html sur votre serveur OSCommerce.

Les liens accèdent à certaines méthodes des webservices.
si on obtient une réponse Fault il y a un problème (en principe le message permet de trouver)!!



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







