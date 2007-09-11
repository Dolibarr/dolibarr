<?php

// -----------------------------------------------
// Cryptographp v1.4
// (c) 2006-2007 Sylvain BRISON 
//
// www.cryptographp.com 
// cryptographp@alphpa.com 
//
// Licence CeCILL modifiée
// => Voir fichier Licence_CeCILL_V2-fr.txt)
// -----------------------------------------------

// FIX LDR. Le bon nom de session n'etait pas positionne faisant echouer le session_name plus loin.
session_name($_GET['sn']);

session_start();
error_reporting(E_ALL ^ E_NOTICE);
SetCookie("cryptcookietest", "1");
Header("Location: cryptographp.inc.php?cfg=".$_GET['cfg']."&sn=".session_name()."&".SID);
?>
