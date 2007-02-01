<?php
/*---------------------------------------------
/ Webservices OSC pour dolibarr
/ configuration des clients
/
/ Jean Heimburger			juin 2006
----------------------------------------------*/

//base url des webservices
define(OSCWS_DIR,'http://www.tiaris.info/ws_OSC');
//define(OSCWS_DIR,'http://www.tiaris.info/ws_OSC');
//affichages dans la page d'accueil
define(OSC_MAXNBCOM, 5);
define(OSC_ORDWAIT,'4'); // code du statut de commande en attente
define(OSC_ORDPROCESS,'1'); // code du statut de commande en traitement
//
define(OSC_ENTREPOT, 1); //l'entrepot lié au stock du site web
?>
