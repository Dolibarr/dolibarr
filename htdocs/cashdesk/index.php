<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011	   Juanjo Menent   	   <jmenent@2byte.es>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/index.php
 * 	\ingroup	cashdesk
 *  \brief      File to login to point of sales
 */

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects
require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");

$langs->load("admin");
$langs->load("cashdesk");

// Test if user logged
if ( $_SESSION['uid'] > 0 )
{
	header('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php');
	exit;
}

$usertxt=GETPOST('user','',1);


/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('','',0,0,'',$arrayofcss);
?>

<body>
<div class="conteneur">
<div class="conteneur_img_gauche">
<div class="conteneur_img_droite">

<h1 class="entete"></h1>

<div class="menu_principal">
</div>

<div class="contenu">
<div class="principal_login">
<?php if (! empty($_GET["err"])) print $_GET["err"]."<br><br>\n"; ?>
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Identification"); ?></legend>
<form id="frmLogin" method="POST" action="index_verif.php">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

<table>

	<tr>
		<td class="label1"><?php echo $langs->trans("Login"); ?></td>
		<td><input name="txtUsername" class="texte_login" type="text" value="<?php echo $usertxt; ?>" /></td>
	</tr>
	<tr>
		<td class="label1"><?php echo $langs->trans("Password"); ?></td>
		<td><input name="pwdPassword" class="texte_login" type="password"	value="" /></td>
	</tr>
<?php
print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td>';
$disabled=0;
$langs->load("companies");
if (! empty($conf->global->CASHDESK_ID_THIRDPARTY)) $disabled=1; // If a particular third party is defined, we disable choice
$form->select_societes(GETPOST('socid')?GETPOST('socid'):$conf->global->CASHDESK_ID_THIRDPARTY,'socid','s.client in (1,3)',!$disabled,$disabled,1);
//print '<input name="warehouse_id" class="texte_login" type="warehouse_id" value="" />';
print '</td>';
print "</tr>\n";

if ($conf->stock->enabled)
{
	$langs->load("stocks");
	print "<tr>";
	print '<td class="label1">'.$langs->trans("Warehouse").'</td>';
	print '<td>';
	$disabled=0;
	if (! empty($conf->global->CASHDESK_ID_WAREHOUSE)) $disabled=1;	// If a particular stock is defined, we disable choice
	$formproduct->selectWarehouses(GETPOST('warehouseid')?GETPOST('warehouseid'):$conf->global->CASHDESK_ID_WAREHOUSE,'warehouseid','',!$disabled,$disabled);
	//print '<input name="warehouse_id" class="texte_login" type="warehouse_id" value="" />';
	print '</td>';
	print "</tr>\n";
}
?>
</table>

<center><span class="bouton_login"><input name="sbmtConnexion" type="submit" value=<?php echo $langs->trans("Connection"); ?> /></span></center>

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

<?php include('affPied.php'); ?></div>
</div>
</div>
</body>

<?php
print '</html>';
?>