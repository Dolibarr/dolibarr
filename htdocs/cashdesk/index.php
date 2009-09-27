<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/cashdesk/index.php
 * 	\ingroup	cashdesk
 *  \brief      File to login to point of sales
 *  \version    $Id$
 */

// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_start();

include('../master.inc.php');

if ( $_SESSION['uid'] > 0 )
{
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php');
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>Point of Sale login</title>

<meta name="robots" content="none" />

<meta name="author" content="Jeremie Ollivier - jeremie.o@laposte.net" />
<meta name="Generator" content="Kwrite, Gimp, Inkscape" />

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
<meta http-equiv="Content-Language" content="fr" />

<meta http-equiv="Content-Style-Type" content="text/css" />
<link href="style.css" rel="stylesheet" type="text/css" media="screen" />
</head>

<body>
<div class="conteneur">
<div class="conteneur_img_gauche">
<div class="conteneur_img_droite">

<h1 class="entete"><span>CAISSE : identification</span></h1>

<div class="menu_principal"></div>

<div class="contenu">
<div class="principal_login">
<fieldset class="cadre_facturation"><legend class="titre1">Identification</legend>
<form class="formulaire_login" id="frmLogin" method="post" action="index_verif.php">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

<table>

	<tr>
		<td class="label1"><?php echo $langs->trans("Login"); ?></td>
		<td><input name="txtUsername" class="texte_login" type="text"	value="<?php echo $_GET['user']; ?>" /></td>
	</tr>
	<tr>
		<td class="label1"><?php echo $langs->trans("Password"); ?></td>
		<td><input name="pwdPassword" class="texte_login" type="password"	value="" /></td>
	</tr>

</table>

<span class="bouton_login"><input name="sbmtConnexion" type="submit" value="Connexion" /></span>

</form>
</fieldset>

<?php
if ($_GET['err'] < 0) {

	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').pwdPassword.focus();');
	echo ('</script>');

} else {

	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').txtUsername.focus();');
	echo ('</script>');

}
?></div>
</div>

<?php include ('affPied.php'); ?></div>
</div>
</div>
</body>
</html>