<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2018 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2015	   Claudio Aschieri		<c.aschieri@19.coop>
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
 *      \file       htdocs/admin/delivery.php
 *      \ingroup    delivery
 *      \brief      age to setup extra fields of delivery
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expedition.lib.php';
require_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "sendings", "deliveries", "other"));

if (!$user->admin) {
	accessforbidden();
}

$action  = GETPOST('action', 'alpha');
$value   = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label   = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'delivery';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


// Shipment note
if (isModEnabled('shipping') && !getDolGlobalString('MAIN_SUBMODULE_EXPEDITION')) {
	// This option should always be set to on when module is on.
	dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1", 'chaine', 0, '', $conf->entity);
}
/*
 if ($action == 'activate_sending')
 {
 dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1",'chaine',0,'',$conf->entity);
 header("Location: confexped.php");
 exit;
 }
 if ($action == 'disable_sending')
 {
 dolibarr_del_const($db, "MAIN_SUBMODULE_EXPEDITION",$conf->entity);
 header("Location: confexped.php");
 exit;
 }
 */

// Delivery note
if ($action == 'activate_delivery') {
	dolibarr_set_const($db, "MAIN_SUBMODULE_EXPEDITION", "1", 'chaine', 0, '', $conf->entity); // We must also enable this
	dolibarr_set_const($db, "MAIN_SUBMODULE_DELIVERY", "1", 'chaine', 0, '', $conf->entity);
	header("Location: delivery.php");
	exit;
} elseif ($action == 'disable_delivery') {
	dolibarr_del_const($db, "MAIN_SUBMODULE_DELIVERY", $conf->entity);
	header("Location: delivery.php");
	exit;
}

if ($action == 'updateMask') {
	$maskconstdelivery = GETPOST('maskconstdelivery', 'aZ09');
	$maskdelivery = GETPOST('maskdelivery', 'alpha');

	$res = 0;

	if ($maskconstdelivery && preg_match('/_MASK$/', $maskconstdelivery)) {
		$res = dolibarr_set_const($db, $maskconstdelivery, $maskdelivery, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'set_DELIVERY_FREE_TEXT') {
	$free = GETPOST('DELIVERY_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res = dolibarr_set_const($db, "DELIVERY_FREE_TEXT", $free, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$sending = new Delivery($db);
	$sending->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/delivery/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFDeliveryOrder $module';

		if ($module->write_file($sending, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=delivery&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

if ($action == 'set') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if (getDolGlobalString('DELIVERY_ADDON_PDF') == $value) {
			dolibarr_del_const($db, 'DELIVERY_ADDON_PDF', $conf->entity);
		}
	}
}

if ($action == 'setdoc') {
	if (dolibarr_set_const($db, "DELIVERY_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->DELIVERY_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

if ($action == 'setmod') {
	// TODO Verify if the chosen numbering module can be activated
	// by calling method canBeActivated

	dolibarr_set_const($db, "DELIVERY_ADDON_NUMBER", $value, 'chaine', 0, '', $conf->entity);
}



/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-delivery');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SendingsSetup"), $linkback, 'title_setup');
print '<br>';
$head = expedition_admin_prepare_head();

print dol_get_fiche_head($head, 'receivings', $langs->trans("Receivings"), -1, 'shipment');


print '<br>';
print '<div class="inline-block valignmiddle">'.$langs->trans("DeliveriesOrderAbility").'</div>';
if (!getDolGlobalString('MAIN_SUBMODULE_DELIVERY')) {
	print ' <a class="inline-block valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=activate_delivery&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
} else {
	print ' <a class="inline-block valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=disable_delivery&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
}

print '<br><span class="opacitymedium">'.info_admin($langs->trans("NoNeedForDeliveryReceipts"), 0, 1).'</span>';
print '<br>';
print '<br>';



if (getDolGlobalString('MAIN_SUBMODULE_DELIVERY')) {
	// Delivery numbering model

	print load_fiche_titre($langs->trans("DeliveryOrderNumberingModules"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="100">'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="nowrap">'.$langs->trans("Example").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
	print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>'."\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/delivery/");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (preg_match('/^mod_delivery_([a-z0-9_]*)\.php$/', $file)) {
						$file = substr($file, 0, dol_strlen($file) - 4);

						require_once $dir.$file.'.php';

						$module = new $file();

						'@phan-var-force ModeleNumRefDeliveryOrder $module';

						if ($module->isEnabled()) {
							// Show modules according to features level
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								continue;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								continue;
							}

							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
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
							if ($conf->global->DELIVERY_ADDON_NUMBER == "$file") {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$delivery = new Delivery($db);
							$delivery->initAsSpecimen();

							// Info
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $delivery);
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

							print '</tr>';
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print '</table>';


	/*
	 *  Documents Models for delivery
	 */
	print '<br>';
	print load_fiche_titre($langs->trans("DeliveryOrderModel"), '', '');

	// Defini tableau def de modele
	$type = "delivery";
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

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="140">'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
	print '<td align="center" width="32">'.$langs->trans("ShortInfo").'</td>';
	print '<td align="center" width="32">'.$langs->trans("Preview").'</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/delivery/doc/");

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

							'@phan-var-force ModelePDFDeliveryOrder $module';

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
									print $module->info($langs);  // @phan-suppress-current-line PhanUndeclaredMethod
								} else {
									print $module->description;
								}
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print "<td align=\"center\">\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print "</td>";
								} else {
									print "<td align=\"center\">\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print "<td align=\"center\">";
								if (getDolGlobalString('DELIVERY_ADDON_PDF') == "$name") {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").'</u>:';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
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

								print '</tr>';
							}
						}
					}
				}
			}
		}
	}

	print '</table>';

	// Other Options
	print "<br>";
	print load_fiche_titre($langs->trans("OtherOptions"), '', '');

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="set_DELIVERY_FREE_TEXT">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
	print '<td width="80">&nbsp;</td>';
	print "</tr>\n";

	$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
	$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
	$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
	foreach ($substitutionarray as $key => $val) {
		$htmltext .= $key.'<br>';
	}
	$htmltext .= '</i>';

	print '<tr class="oddeven"><td colspan="2">';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnDeliveryReceipts"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
	$variablename = 'DELIVERY_FREE_TEXT';
	if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print '</td><td class="right">';
	print "</td></tr>\n";

	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
