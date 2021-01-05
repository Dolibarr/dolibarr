<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2020       Frédéric France     <frederic.france@netlogic.fr>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

if (!$user->admin) accessforbidden();


$choices = array('yesno', 'texte', 'chaine');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'member';

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'set_default') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
	$res = true;
} elseif ($action == 'del_default') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->MEMBER_ADDON_PDF_ODT == "$value") {
			dolibarr_del_const($db, 'MEMBER_ADDON_PDF_ODT', $conf->entity);
		}
	}
	$res = true;
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "MEMBER_ADDON_PDF_ODT", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->MEMBER_ADDON_PDF_ODT = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
	$res = true;
} elseif (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'updateall') {
	$db->begin();
	$res1 = $res2 = $res3 = $res4 = $res5 = $res6 = 0;
	$res1 = dolibarr_set_const($db, 'ADHERENT_LOGIN_NOT_REQUIRED', GETPOST('ADHERENT_LOGIN_NOT_REQUIRED', 'alpha') ? 0 : 1, 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'ADHERENT_MAIL_REQUIRED', GETPOST('ADHERENT_MAIL_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'ADHERENT_DEFAULT_SENDINFOBYMAIL', GETPOST('ADHERENT_DEFAULT_SENDINFOBYMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res4 = dolibarr_set_const($db, 'ADHERENT_BANK_USE', GETPOST('ADHERENT_BANK_USE', 'alpha'), 'chaine', 0, '', $conf->entity);
	// Use vat for invoice creation
	if ($conf->facture->enabled) {
		$res4 = dolibarr_set_const($db, 'ADHERENT_VAT_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_VAT_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		$res5 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
			$res6 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		}
	}
	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0) {
		setEventMessages('ErrorFailedToSaveDate', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
}

// Action to update or add a constant
if ($action == 'update' || $action == 'add') {
	$constname = GETPOST('constname', 'alpha');
	$constvalue = (GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));

	if (($constname == 'ADHERENT_CARD_TYPE' || $constname == 'ADHERENT_ETIQUETTE_TYPE' || $constname == 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) $constvalue = '';
	if ($constname == 'ADHERENT_LOGIN_NOT_REQUIRED') { // Invert choice
		if ($constvalue) $constvalue = 0;
		else $constvalue = 1;
	}

	$consttype = GETPOST('consttype', 'alpha');
	$constnote = GETPOST('constnote');
	$res = dolibarr_set_const($db, $constname, $constvalue, $choices[$consttype], 0, $constnote, $conf->entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Action to enable of a submodule of the adherent module
if ($action == 'set') {
	$result = dolibarr_set_const($db, GETPOST('name', 'alpha'), GETPOST('value'), '', 0, '', $conf->entity);
	if ($result < 0) {
		print $db->error();
	}
}

// Action to disable a submodule of the adherent module
if ($action == 'unset') {
	$result = dolibarr_del_const($db, GETPOST('name', 'alpha'), $conf->entity);
	if ($result < 0) {
		print $db->error();
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('', $langs->trans("MembersSetup"), $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("Members"), -1, 'user');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateall">';

print load_fiche_titre($langs->trans("MemberMainOptions"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Login/Pass required for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentLoginRequired").'</td><td>';
print $form->selectyesno('ADHERENT_LOGIN_NOT_REQUIRED', (!empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED) ? 0 : 1), 1);
print "</td></tr>\n";

// Mail required for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('ADHERENT_MAIL_REQUIRED', (!empty($conf->global->ADHERENT_MAIL_REQUIRED) ? $conf->global->ADHERENT_MAIL_REQUIRED : 0), 1);
print "</td></tr>\n";

// Send mail information is on by default
print '<tr class="oddeven"><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('ADHERENT_DEFAULT_SENDINFOBYMAIL', (!empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL) ? $conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL : 0), 1);
print "</td></tr>\n";

// Insert subscription into bank account
print '<tr class="oddeven"><td>'.$langs->trans("MoreActionsOnSubscription").'</td>';
$arraychoices = array('0'=>$langs->trans("None"));
if (!empty($conf->banque->enabled)) $arraychoices['bankdirect'] = $langs->trans("MoreActionBankDirect");
if (!empty($conf->banque->enabled) && !empty($conf->societe->enabled) && !empty($conf->facture->enabled)) $arraychoices['invoiceonly'] = $langs->trans("MoreActionInvoiceOnly");
if (!empty($conf->banque->enabled) && !empty($conf->societe->enabled) && !empty($conf->facture->enabled)) $arraychoices['bankviainvoice'] = $langs->trans("MoreActionBankViaInvoice");
print '<td>';
print $form->selectarray('ADHERENT_BANK_USE', $arraychoices, $conf->global->ADHERENT_BANK_USE, 0);
if ($conf->global->ADHERENT_BANK_USE == 'bankdirect' || $conf->global->ADHERENT_BANK_USE == 'bankviainvoice') {
	print '<br><div style="padding-top: 5px;"><span class="opacitymedium">'.$langs->trans("ABankAccountMustBeDefinedOnPaymentModeSetup").'</span></div>';
}
print '</td>';
print "</tr>\n";

// Use vat for invoice creation
if ($conf->facture->enabled) {
	print '<tr class="oddeven"><td>'.$langs->trans("VATToUseForSubscriptions").'</td>';
	if (!empty($conf->banque->enabled)) {
		print '<td>';
		print $form->selectarray('ADHERENT_VAT_FOR_SUBSCRIPTIONS', array('0'=>$langs->trans("NoVatOnSubscription"), 'defaultforfoundationcountry'=>$langs->trans("Default")), (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) ? '0' : $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS), 0);
		print '</td>';
	} else {
		print '<td class="right">';
		print $langs->trans("WarningModuleNotActive", $langs->transnoentities("Module85Name"));
		print '</td>';
	}
	print "</tr>\n";

	if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
		print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS").'</td>';
		print '<td>';
		$form->select_produits($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', '', 0);
		print '</td>';
	}
	print "</tr>\n";
}

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</div>';

print '</form>';

print '<br>';


/*
 * Edit info of model document
 */
$constantes = array(
		'ADHERENT_CARD_TYPE',
		//'ADHERENT_CARD_BACKGROUND',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_TEXT_RIGHT',
		'ADHERENT_CARD_FOOTER_TEXT'
);

print load_fiche_titre($langs->trans("MembersCards"), '', '');

$helptext = '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

form_constantes($constantes, 0, $helptext);

print '<br>';


/*
 * Edit info of model document
 */
$constantes = array('ADHERENT_ETIQUETTE_TYPE', 'ADHERENT_ETIQUETTE_TEXT');

print load_fiche_titre($langs->trans("MembersTickets"), '', '');

$helptext = '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

form_constantes($constantes, 0, $helptext);
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Defini tableau def des modeles
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
} else {
	dol_print_error($db);
}

print load_fiche_titre($langs->trans("MembersDocModules"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/member".$valdir);
		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);
				foreach ($filelist as $file) {
					if (preg_match('/\.class\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) - 14);
							$classname = substr($file, 0, dol_strlen($file) - 10);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified = 1;
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
								$modulequalified = 0;
							}

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) {
									print $module->info($langs);
								} else {
									print $module->description;
								}
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del_default&amp;token='.newToken().'&amp;value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set_default&amp;token='.newToken().'&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print '<td class="center">';
								if ($conf->global->MEMBER_ADDON_PDF == $name) {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'contract').'</a>';
								} else {
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
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

print '</table>';
print "<br>";

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
