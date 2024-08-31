<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2019      Markus Welters       <markus@welters.de>
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
 *	\file       htdocs/admin/prelf
 *	\ingroup    prelevement
 *	\brief      Page to setup Withdrawals
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "withdrawals"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$type = 'paymentorder';

$error = 0;


/*
 * Actions
 */

if ($action == "set") {
	$db->begin();

	$id = GETPOSTINT('PRELEVEMENT_ID_BANKACCOUNT');
	$account = new Account($db);
	if ($account->fetch($id) > 0) {
		$res = dolibarr_set_const($db, "PRELEVEMENT_ID_BANKACCOUNT", $id, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		/*
		$res = dolibarr_set_const($db, "PRELEVEMENT_CODE_BANQUE", $account->code_banque,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_CODE_GUICHET", $account->code_guichet,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_NUMERO_COMPTE", $account->number,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_NUMBER_KEY", $account->cle_rib,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_IBAN", $account->iban,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_BIC", $account->bic,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db, "PRELEVEMENT_RAISON_SOCIALE", $account->proprio,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		*/
	} else {
		$error++;
	}

	/* Moved to account
	$res = dolibarr_set_const($db, "PRELEVEMENT_ICS", GETPOST("PRELEVEMENT_ICS"), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	*/
	if (GETPOST("PRELEVEMENT_USER") > 0) {
		$res = dolibarr_set_const($db, "PRELEVEMENT_USER", GETPOST("PRELEVEMENT_USER"), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
	if (GETPOST("PRELEVEMENT_END_TO_END") || GETPOST("PRELEVEMENT_END_TO_END") == "") {
		$res = dolibarr_set_const($db, "PRELEVEMENT_END_TO_END", GETPOST("PRELEVEMENT_END_TO_END"), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
	if (GETPOST("PRELEVEMENT_USTRD") || GETPOST("PRELEVEMENT_USTRD") == "") {
		$res = dolibarr_set_const($db, "PRELEVEMENT_USTRD", GETPOST("PRELEVEMENT_USTRD"), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	$res = dolibarr_set_const($db, "PRELEVEMENT_ADDDAYS", GETPOST("PRELEVEMENT_ADDDAYS"), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == "addnotif") {
	$bon = new BonPrelevement($db);
	$bon->addNotification($db, GETPOSTINT('user'), $action);

	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($action == "deletenotif") {
	$bon = new BonPrelevement($db);
	$bon->deleteNotificationById(GETPOSTINT('notif'));

	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 *	View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', $langs->trans("WithdrawalsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-prelevement');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("WithdrawalsSetup"), $linkback, 'title_setup');
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=set">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>";


// Bank account (from Banks module)
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("BankToReceiveWithdraw").'</td>';
print '<td>';
print img_picto('', 'bank_account', 'class="pictofixedwidth"');
print $form->select_comptes(getDolGlobalInt('PRELEVEMENT_ID_BANKACCOUNT'), 'PRELEVEMENT_ID_BANKACCOUNT', 0, "courant=1", 1, '', 0, 'minwidth200 widthcentpercentminusxx maxwidth300', 1);
// TODO Add plus to add a bank account
print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&backtopage='.DOL_URL_ROOT.'/admin/prelevement.php"><span class="fa fa-plus-circle"></span></a>';
print '</td></tr>';

/* Moved to bank account data
// ICS

print '<tr class="oddeven"><td class="fieldrequired">';
$htmltext = $langs->trans("AskThisIDToYourBank");
print $form->textwithpicto($langs->trans("ICS"), $htmltext);
print '</td>';
print '<td class="left">';
print '<input type="text" name="PRELEVEMENT_ICS" value="'.$conf->global->PRELEVEMENT_ICS.'" size="15" >';
print '</td>';
print '</td></tr>';
*/

//User
print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("ResponsibleUser").'</td>';
print '<td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers(getDolGlobalInt('PRELEVEMENT_USER'), 'PRELEVEMENT_USER', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
print '</td>';
print '</tr>';

//EntToEnd
print '<tr class="oddeven"><td>';
$htmltext = $langs->trans("KeepThisEmptyInMostCases");
print $form->textwithpicto($langs->trans("END_TO_END"), $htmltext);
print '</td>';
print '<td>';
print '<input type="text" name="PRELEVEMENT_END_TO_END" value="'.getDolGlobalString('PRELEVEMENT_END_TO_END').'" class="width100"></td>';
print '</td></tr>';

//USTRD
print '<tr class="oddeven"><td>';
$htmltext = $langs->trans("KeepThisEmptyInMostCases");
print $form->textwithpicto($langs->trans("USTRD"), $htmltext);
print '</td>';
print '<td class="left">';
print '<input type="text" name="PRELEVEMENT_USTRD" value="'.getDolGlobalString('PRELEVEMENT_USTRD').'" class="width100"></td>';
print '</td></tr>';

//ADDDAYS
print '<tr class="oddeven"><td>'.$langs->trans("ADDDAYS").'</td>';
print '<td>';
if (!getDolGlobalString('PRELEVEMENT_ADDDAYS')) {
	$conf->global->PRELEVEMENT_ADDDAYS = 0;
}
print '<input type="text" name="PRELEVEMENT_ADDDAYS" value="'.getDolGlobalString('PRELEVEMENT_ADDDAYS').'"  class="width50"></td>';
print '</td></tr>';

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';


print '<br>';


/*
 * Document templates generators
 */
/*
print load_fiche_titre($langs->trans("OrdersModelModule"),'','');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$db->escape($type)."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="38">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/paymentorders".$valdir);

		if (is_dir($dir))
		{
			$handle=opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					$filelist[]=$file;
				}
				closedir($handle);
				arsort($filelist);

				foreach($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
					{

						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module,'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
									print img_picto($langs->trans("Enabled"),'switch_on');
									print '</a>';
									print '</td>';
								}
								else
								{
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if ($conf->global->PAYMENTORDER_ADDON_PDF == $name)
								{
									print img_picto($langs->trans("Default"),'on');
								}
								else
								{
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
								//$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								//$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);


								print '<td class="center">';
								print $form->textwithpicto('',$htmltooltip,1,0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"),'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}

*/


print dol_get_fiche_end();

print '<br>';


/*
 * Notifications
 */

/* Disable this, there is no trigger with elementtype 'withdraw'
if (isModEnabled('notification') )
{
	$langs->load("mails");
	print load_fiche_titre($langs->trans("Notifications"));

	$sql = "SELECT u.rowid, u.lastname, u.firstname, u.fk_soc, u.email";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql.= " WHERE entity IN (".getEntity('invoice').")";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			if (!$obj->fk_soc)
			{
				$username=dolGetFirstLastname($obj->firstname,$obj->lastname);
				$internalusers[$obj->rowid] = $username;
			}

			$i++;
		}
		$db->free($resql);
	}

	// Get list of triggers for module withdraw
	$sql = "SELECT rowid, code, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger";
	$sql.= " WHERE elementtype = 'withdraw'";
	$sql.= " ORDER BY rang ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
			$actions[$obj->rowid]=$label;
			$i++;
		}
		$db->free($resql);
	}


	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=addnotif&token='.newToken().'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print '<td class="right">'.$langs->trans("Action").'</td>';
	print "</tr>\n";

	print '<tr class="impair"><td class="left">';
	print $form->selectarray('user',$internalusers);//  select_dolusers(0,'user',0);
	print '</td>';

	print '<td>';
	print $form->selectarray('action',$actions);//  select_dolusers(0,'user',0);
	print '</td>';

	print '<td class="right"><input type="submit" class="button button-add" value="'.$langs->trans("Add").'"></td></tr>';

	// List of current notifications for objet_type='withdraw'
	$sql = "SELECT u.lastname, u.firstname,";
	$sql.= " nd.rowid, ad.code, ad.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."user as u,";
	$sql.= " ".MAIN_DB_PREFIX."notify_def as nd,";
	$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as ad";
	$sql.= " WHERE u.rowid = nd.fk_user";
	$sql.= " AND nd.fk_action = ad.rowid";
	$sql.= " AND u.entity IN (0,".$conf->entity.")";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);


			print '<tr class="oddeven">';
			print '<td>'.dolGetFirstLastname($obj->firstname,$obj->lastname).'</td>';
			$label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
			print '<td>'.$label.'</td>';
			print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=deletenotif&token='.newToken().'&notif='.$obj->rowid.'">'.img_delete().'</a></td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
	}

	print '</table>';
	print '</form>';
}
*/

// End of page
llxFooter();
$db->close();
