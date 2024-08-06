<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio         <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			   <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/propal.php
 *		\ingroup    propale
 *		\brief      Setup page for commercial proposal module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "errors", "propal"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'propal';

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error = 0;
if ($action == 'updateMask') {
	$maskconstpropal = GETPOST('maskconstpropal', 'aZ09');
	$maskpropal = GETPOST('maskpropal', 'alpha');
	if ($maskconstpropal && preg_match('/_MASK$/', $maskconstpropal)) {
		$res = dolibarr_set_const($db, $maskconstpropal, $maskpropal, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$propal = new Propal($db);
	$propal->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/propale/doc/pdf_".$modele.".modules.php");
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFPropales $module';

		if ($module->write_file($propal, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=propal&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setribchq') {
	$rib = GETPOST('rib', 'alpha');
	$chq = GETPOST('chq', 'alpha');

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER", $rib, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER", $chq, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'update') {
	if (GETPOSTISSET('PROPALE_VALIDITY_DURATION')) {
		$value = GETPOST('PROPALE_VALIDITY_DURATION');
		$res = dolibarr_set_const($db, "PROPALE_VALIDITY_DURATION", $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
	if (GETPOSTISSET('PROPALE_DRAFT_WATERMARK')) {
		$draft = GETPOST('PROPALE_DRAFT_WATERMARK', 'alpha');
		$res = dolibarr_set_const($db, "PROPALE_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
	if (GETPOSTISSET('PROPOSAL_FREE_TEXT')) {
		$freetext = GETPOST('PROPOSAL_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
		$res = dolibarr_set_const($db, "PROPOSAL_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL') {
	$res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL", $value, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->PROPALE_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'PROPALE_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	if (dolibarr_set_const($db, "PROPALE_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		$conf->global->PROPALE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verify if the chosen numbering module can be active
	// by calling method canBeActivated

	dolibarr_set_const($db, "PROPALE_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);

	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
}


/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', $langs->trans("PropalSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-propal');

//if ($mesg) print $mesg;

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PropalSetup"), $linkback, 'title_setup');

$head = propal_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("Proposals"), -1, 'propal');

/*
 *  Module numerotation
 */
print load_fiche_titre($langs->trans("ProposalsNumberingModules"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/propale");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 12) == 'mod_propale_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.'/'.$file.'.php';

					$module = new $file();

					'@phan-var-force ModeleNumRefPropales $module';

					// Show modules according to features level
					if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
						continue;
					}
					if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
						continue;
					}

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
						print $module->info($langs);
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) {
							$langs->load("errors");
							print '<div class="error">'.$langs->trans($tmp).'</div>';
						} elseif ($tmp == 'NotConfigured') {
							print '<span class="opacitymedium">'.$langs->trans($tmp).'</span>';
						} else {
							print $tmp;
						}
						print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->PROPALE_ADDON == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$propal = new Propal($db);
						$propal->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$propal->type = 0;
						$nextval = $module->getNextValue($mysoc, $propal);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= ''.$langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
									$nextval = $langs->trans($nextval);
								}
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print "</tr>\n";
					}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("ProposalsPDFModules"), '', '');

// Load array def with activated templates
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
		if (is_array($array)) {
			array_push($def, $array[0]);
		}
		$i++;
	}
} else {
	dol_print_error($db);
}


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$realpath = $reldir."core/modules/propale".$valdir;
		$dir = dol_buildpath($realpath);

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				$filelist = array();
				while (($file = readdir($handle)) !== false) {
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							'@phan-var-force ModelePDFPropales $module';

							$modulequalified = 1;
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								$modulequalified = 0;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								$modulequalified = 0;
							}

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print(empty($module->name) ? $name : $module->name);
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
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print "<td align=\"center\">\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print "<td align=\"center\">";
								if ($conf->global->PROPALE_ADDON_PDF == "$name") {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = $langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								//$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								//$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftProposal").': '.yn($module->option_draft_watermark, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
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


/*
 *  Payment mode
 */

print '<br>';
print load_fiche_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInProposal"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td align="right">';
if (!isModEnabled('invoice')) {
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
}
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if (!isModEnabled('invoice')) {
	if (isModEnabled("bank")) {
		$sql = "SELECT rowid, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql .= " WHERE clos = 0";
		$sql .= " AND courant = 1";
		$sql .= " AND entity IN (".getEntity('bank_account').")";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0) {
				print '<select name="rib" class="flat" id="rib">';
				print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
				while ($i < $num) {
					$row = $db->fetch_row($resql);

					print '<option value="'.$row[0].'"';
					print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected' : '';
					print '>'.$row[1].'</option>';

					$i++;
				}
				print "</select>";
			} else {
				print "<i>".$langs->trans("NoActiveBankAccountDefined")."</i>";
			}
		}
	} else {
		print '<span class="opacitymedium">'.$langs->trans("BankModuleNotActive").'</span>';
	}
} else {
	print '<span class="opacitymedium">'.$langs->trans("SeeSetupOfModule", $langs->transnoentitiesnoconv("Module30Name")).'</span>';
}
print "</td></tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
if (!isModEnabled('invoice')) {
	print '<select class="flat" name="chq" id="chq">';
	print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
	print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER ? ' selected' : '').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name ? $mysoc->name : $langs->trans("NotDefined")).')</option>';

	$sql = "SELECT rowid, label";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
	$sql .= " WHERE clos = 0";
	$sql .= " AND courant = 1";
	$sql .= " AND entity IN (".getEntity('bank_account').")";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_row($resql);

			print '<option value="'.$row[0].'"';
			print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected' : '';
			print '>'.$langs->trans("OwnerOfBankAccount", $row[1]).'</option>';

			$i++;
		}
	}
	print "</select>";
} else {
	print '<span class="opacitymedium">'.$langs->trans("SeeSetupOfModule", $langs->transnoentitiesnoconv("Module30Name")).'</span>';
}
print "</td></tr>";
print "</table>";
print "</form>";


print '<br>';


/*
 * Other options
 */

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "</tr>";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DefaultProposalDurationValidity").'</td>';
print '<td width="60" align="center">';
print "<input size=\"3\" class=\"flat\" type=\"text\" name=\"PROPALE_VALIDITY_DURATION\" value=\"" . getDolGlobalString('PROPALE_VALIDITY_DURATION')."\"></td>";
print '</tr>';

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnProposal"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'PROPOSAL_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td>';
print "</tr>\n";


print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftProposal"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td>';
print '<input class="flat minwidth200" type="text" name="PROPALE_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('PROPALE_DRAFT_WATERMARK')).'">';
print '</td>';
print "</tr>\n";


// Allow external download
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowExternalDownload").'</td>';
print '<td class="center">';
print ajax_constantonoff('PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD', array(), null, 0, 0, 0, 2, 0, 1);
print '</td></tr>';

// Allow OnLine Sign
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowOnLineSign").'</td>';
print '<td class="center">';
print ajax_constantonoff('PROPOSAL_ALLOW_ONLINESIGN', array(), null, 0, 0, 0, 2, 0, 1);
print '</td></tr>';


/* Seems to be not so used. So kept hidden for the moment to avoid dangerous options inflation.
if (isModEnabled('facture'))
{

	print '<tr class="oddeven"><td>';
	print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL").'</td><td>&nbsp;</td><td class="right">';
	if (!empty($conf->use_javascript_ajax))
	{
		print ajax_constantonoff('BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL');
	}
	else
	{
		if (empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL&token='.newToken().'&value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL&token='.newToken().'&value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
		}
	}
	print '</td></tr>';
}
else
{

	print '<tr class="oddeven"><td>';
	print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL").'</td><td>&nbsp;</td><td align="center">'.$langs->trans('NotAvailable').'</td></tr>';
}
*/

print '</table>';

print '<center><input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'"></center>';

print '</form>';


/*
 *  Directory
 */
print '<br>';
print load_fiche_titre($langs->trans("PathToDocuments"), '', '');

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr class=\"oddeven\">\n  <td width=\"140\">".$langs->trans("PathDirectory")."</td>\n  <td>".$conf->propal->multidir_output[$conf->entity]."</td>\n</tr>\n";
print "</table>\n<br>";


/*
 * Notifications
 */

print load_fiche_titre($langs->trans("Notifications"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
print '</td><td class="right">';
print "</td></tr>\n";

print '</table>';

// End of page
llxFooter();
$db->close();
