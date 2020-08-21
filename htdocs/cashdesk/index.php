<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011-2017 Juanjo Menent   	   <jmenent@2byte.es>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/index.php
 * 	\ingroup	cashdesk
 *  \brief      File to login to point of sales
 */

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects
require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "cashdesk"));

// Test if user logged
if ($_SESSION['uid'] > 0)
{
	header('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php');
	exit;
}

$usertxt = GETPOST('user', '', 1);
$err = GETPOST("err");

// Instantiate hooks of thirdparty module only if not already define
$hookmanager->initHooks(array('cashdeskloginpage'));

/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$arrayofcss = array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

// Execute hook getLoginPageOptions (for table)
$parameters = array('entity' => GETPOST('entity', 'int'));
$reshook = $hookmanager->executeHooks('getLoginPageOptions', $parameters); // Note that $action and $object may have been modified by some hooks.
if (is_array($hookmanager->resArray) && !empty($hookmanager->resArray)) {
	$morelogincontent = $hookmanager->resArray; // (deprecated) For compatibility
} else {
	$morelogincontent = $hookmanager->resPrint;
}
?>

<body>
<div class="conteneur">
<div class="conteneur_img_gauche">
<div class="conteneur_img_droite">

<div class="menu_principal hideonsmartphone">
<div class="logo">
<?php
if (!empty($mysoc->logo_small))
{
	print '<img class="logopos" alt="Logo company" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small).'">';
} else {
	print '<div class="logopos">'.$mysoc->name.'</div>';
}
?>
</div>
</div>

<div class="contenu">
<div class="inline-block" style="vertical-align: top">
<div class="principal_login">
<?php if ($err) print dol_escape_htmltag($err)."<br><br>\n"; ?>
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Identification"); ?></legend>
<form id="frmLogin" method="POST" action="index_verif.php">
	<input type="hidden" name="token" value="<?php echo newToken(); ?>" />

<table>

	<tr>
		<td class="label1"><?php echo $langs->trans("Login"); ?></td>
		<td><input name="txtUsername" class="texte_login maxwidth150onsmartphoneimp" type="text" value="<?php echo $usertxt; ?>" /></td>
	</tr>
	<tr>
		<td class="label1"><?php echo $langs->trans("Password"); ?></td>
		<td><input name="pwdPassword" class="texte_login maxwidth150onsmartphoneimp" type="password" value="" /></td>
	</tr>

<?php
if (!empty($morelogincontent)) {
	if (is_array($morelogincontent)) {
		foreach ($morelogincontent as $format => $option)
		{
			if ($format == 'table') {
				echo '<!-- Option by hook -->';
				echo $option;
			}
		}
	} else {
		echo '<!-- Option by hook -->';
		echo $morelogincontent;
	}
}
?>

	<tr>
		<td colspan="2">
		&nbsp;
		</td>
	</tr>

<?php
print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td>';
$disabled = 0;
$langs->load("companies");
if (!empty($conf->global->CASHDESK_ID_THIRDPARTY)) $disabled = 1; // If a particular third party is defined, we disable choice
print $form->select_company(GETPOST('socid', 'int') ?GETPOST('socid', 'int') : $conf->global->CASHDESK_ID_THIRDPARTY, 'socid', '(s.client IN (1,3) AND s.status = 1)', !$disabled, $disabled, 0, array(), 0, 'maxwidth300');
//print '<input name="warehouse_id" class="texte_login" type="warehouse_id" value="" />';
print '</td>';
print "</tr>\n";

if (!empty($conf->stock->enabled) && empty($conf->global->CASHDESK_NO_DECREASE_STOCK))
{
	$langs->load("stocks");
	print "<tr>";
	print '<td class="label1">'.$langs->trans("Warehouse").'</td>';
	print '<td>';
	$disabled = 0;
	if ($conf->global->CASHDESK_ID_WAREHOUSE > 0) $disabled = 1; // If a particular stock is defined, we disable choice
	print $formproduct->selectWarehouses((GETPOST('warehouseid') ?GETPOST('warehouseid', 'int') : (empty($conf->global->CASHDESK_ID_WAREHOUSE) ? 'ifone' : $conf->global->CASHDESK_ID_WAREHOUSE)), 'warehouseid', '', !$disabled, $disabled);
	print '</td>';
	print "</tr>\n";
}

print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskBankAccountForSell").'</td>';
print '<td>';
$defaultknown = 0;
if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH) && $conf->global->CASHDESK_ID_BANKACCOUNT_CASH > 0) $defaultknown = 1; // If a particular stock is defined, we disable choice
$form->select_comptes(((GETPOST('bankid_cash') > 0) ?GETPOST('bankid_cash') : $conf->global->CASHDESK_ID_BANKACCOUNT_CASH), 'CASHDESK_ID_BANKACCOUNT_CASH', 0, "courant=2", ($defaultknown ? 0 : 2));
print '</td>';
print "</tr>\n";

print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
print '<td>';
$defaultknown = 0;
if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE) && $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE > 0) $defaultknown = 1; // If a particular stock is defined, we disable choice
$form->select_comptes(((GETPOST('bankid_cheque') > 0) ?GETPOST('bankid_cheque') : $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE), 'CASHDESK_ID_BANKACCOUNT_CHEQUE', 0, "courant=1", ($defaultknown ? 0 : 2));
print '</td>';
print "</tr>\n";

print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskBankAccountForCB").'</td>';
print '<td>';
$defaultknown = 0;
if (!empty($conf->global->CASHDESK_ID_BANKACCOUNT_CB) && $conf->global->CASHDESK_ID_BANKACCOUNT_CB > 0) $defaultknown = 1; // If a particular stock is defined, we disable choice
$form->select_comptes(((GETPOST('bankid_cb') > 0) ?GETPOST('bankid_cb') : $conf->global->CASHDESK_ID_BANKACCOUNT_CB), 'CASHDESK_ID_BANKACCOUNT_CB', 0, "courant=1", ($defaultknown ? 0 : 2));
print '</td>';
print "</tr>\n";

?>

	<tr>
		<td colspan="2">
		&nbsp;
		</td>
	</tr>


</table>
<br>

<div align="center"><span class="bouton_login"><input class="button" type="submit" value="<?php echo dol_escape_htmltag($langs->trans("Connection")); ?>" /></span></div>

</form>
</fieldset>


<?php
if ($_GET['err'] < 0)
{
	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').pwdPassword.focus();');
	echo ('</script>');
} else {
	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').txtUsername.focus();');
	echo ('</script>');
}
?>

</div>
</div>
</div>

<?php include 'affPied.php'; ?></div>
</div>
</div>
</body>

<?php
print '</html>';
