<?php
/* Copyright (C) 2013-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France       <frederic.france@free.fr>
 * Copyright (C) 2016      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2020      Andreu Bisquerra Gaya <jove@bisquerra.com>
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
 *      \file       htdocs/admin/receiptprinter.php
 *      \ingroup    printing
 *      \brief      Page to setup receipt printer
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/receiptprinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "receiptprinter"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');

$printername = GETPOST('printername', 'alpha');
$printerid = GETPOST('printerid', 'int');
$parameter = GETPOST('parameter', 'alpha');

$template = GETPOST('template', 'alphanohtml');
$templatename = GETPOST('templatename', 'alpha');
$templateid = GETPOST('templateid', 'int');

$printer = new dolReceiptPrinter($db);

if (!$mode) {
	$mode = 'config';
}

// used in library escpos maybe useful if php doesn't support gzdecode
if (!function_exists('gzdecode')) {
	/**
	 * Gzdecode
	 *
	 * @param string    $data   data to deflate
	 * @return string           data deflated
	 */
	function gzdecode($data)
	{
		return gzinflate(substr($data, 10, -8));
	}
}


/*
 * Action
 */

if ($action == 'addprinter' && $user->admin) {
	$error = 0;
	if (empty($printername)) {
		$error++;
		setEventMessages($langs->trans("PrinterNameEmpty"), null, 'errors');
	}

	if (empty($parameter)) {
		setEventMessages($langs->trans("PrinterParameterEmpty"), null, 'warnings');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->addPrinter($printername, GETPOST('printertypeid', 'int'), GETPOST('printerprofileid', 'int'), $parameter);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("PrinterAdded", $printername), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}

if ($action == 'deleteprinter' && $user->admin) {
	$error = 0;
	if (empty($printerid)) {
		$error++;
		setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->deletePrinter($printerid);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("PrinterDeleted", $printername), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}

if ($action == 'updateprinter' && $user->admin) {
	$error = 0;
	if (empty($printerid)) {
		$error++;
		setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->updatePrinter($printername, GETPOST('printertypeid', 'int'), GETPOST('printerprofileid', 'int'), $parameter, $printerid);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("PrinterUpdated", $printername), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}

if ($action == 'testprinter' && $user->admin) {
	$error = 0;
	if (empty($printerid)) {
		$error++;
		setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
	}

	if (!$error) {
		// test
		$ret = $printer->sendTestToPrinter($printerid);
		if ($ret == 0) {
			setEventMessages($langs->trans("TestSentToPrinter", $printername), null);
		} else {
			setEventMessages($printer->error, $printer->errors, 'errors');
		}
	}
	$action = '';
}

if ($action == 'testtemplate' && $user->admin) {
	$error = 0;
	// if (empty($printerid)) {
	//     $error++;
	//     setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
	// }

	// if (! $error) {
	// test
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$object = new Facture($db);
	$object->initAsSpecimen();
	//$object->fetch(18);
	//var_dump($object->lines);
	$ret = $printer->sendToPrinter($object, $templateid, 1);
	if ($ret == 0) {
		setEventMessages($langs->trans("TestTemplateToPrinter", $printername), null);
	} else {
		setEventMessages($printer->error, $printer->errors, 'errors');
	}
	//}
	$action = '';
}

if ($action == 'updatetemplate' && $user->admin) {
	$error = 0;
	if (empty($templateid)) {
		$error++;
		setEventMessages($langs->trans("TemplateIdEmpty"), null, 'errors');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->updateTemplate($templatename, $template, $templateid);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("TemplateUpdated", $templatename), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}

if ($action == 'addtemplate' && $user->admin) {
	$error = 0;
	if (empty($templatename)) {
		$error++;
		setEventMessages($langs->trans("TemplateNameEmpty"), null, 'errors');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->addTemplate($templatename, $template);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("TemplateAdded", $templatename), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}

if ($action == 'deletetemplate' && $user->admin) {
	$error = 0;
	if (empty($templateid)) {
		$error++;
		setEventMessages($langs->trans("TemplateIdEmpty"), null, 'errors');
	}

	if (!$error) {
		$db->begin();
		$result = $printer->deleteTemplate($templateid);
		if ($result > 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("TemplateDeleted", $templatename), null);
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
	$action = '';
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("ReceiptPrinterSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ReceiptPrinterSetup"), $linkback, 'title_setup');

$head = receiptprinteradmin_prepare_head($mode);

// mode = config
if ($mode == 'config' && $user->admin) {
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=config" autocomplete="off">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if ($action != 'editprinter') {
		print '<input type="hidden" name="action" value="addprinter">';
	} else {
		print '<input type="hidden" name="action" value="updateprinter">';
	}


	print dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	print '<span class="opacitymedium">'.$langs->trans("ReceiptPrinterDesc")."</span><br><br>\n";

	print '<table class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("Name").'</th>';
	print '<th>'.$langs->trans("Type").'</th>';
	print '<th>';
	$htmltext = $langs->trans("PROFILE_DEFAULT").' = '.$langs->trans("PROFILE_DEFAULT_HELP").'<br>';
	$htmltext .= $langs->trans("PROFILE_SIMPLE").' = '.$langs->trans("PROFILE_SIMPLE_HELP").'<br>';
	$htmltext .= $langs->trans("PROFILE_EPOSTEP").' = '.$langs->trans("PROFILE_EPOSTEP_HELP").'<br>';
	$htmltext .= $langs->trans("PROFILE_P822D").' = '.$langs->trans("PROFILE_P822D_HELP").'<br>';
	$htmltext .= $langs->trans("PROFILE_STAR").' = '.$langs->trans("PROFILE_STAR_HELP").'<br>';

	print $form->textwithpicto($langs->trans("Profile"), $htmltext);
	print '</th>';
	print '<th>'.$langs->trans("Parameters").'</th>';
	print '<th></th>';
	print "</tr>\n";
	$ret = $printer->listprinters();
	$nbofprinters = count($printer->listprinters);

	if ($action != 'editprinter') {
		print '<tr>';
		print '<td><input class="minwidth200" type="text" name="printername"></td>';
		$ret = $printer->selectTypePrinter();
		print '<td>'.$printer->resprint.'</td>';
		$ret = $printer->selectProfilePrinter();
		print '<td>'.$printer->profileresprint.'</td>';
		print '<td><input size="60" type="text" name="parameter"></td>';
		print '<td class="right">';
		if ($action != 'editprinter') {
			print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'"></div>';
		}
		print '</td>';
		print '</tr>';
	}

	if ($ret > 0) {
		setEventMessages($printer->error, $printer->errors, 'errors');
	} else {
		for ($line = 0; $line < $nbofprinters; $line++) {
			print '<tr class="oddeven">';
			if ($action == 'editprinter' && $printer->listprinters[$line]['rowid'] == $printerid) {
				print '<input type="hidden" name="printerid" value="'.$printer->listprinters[$line]['rowid'].'">';
				print '<td><input type="text" class="minwidth200" name="printername" value="'.$printer->listprinters[$line]['name'].'"></td>';
				$ret = $printer->selectTypePrinter($printer->listprinters[$line]['fk_type']);
				print '<td>'.$printer->resprint.'</td>';
				$ret = $printer->selectProfilePrinter($printer->listprinters[$line]['fk_profile']);
				print '<td>'.$printer->profileresprint.'</td>';
				print '<td><input size="60" type="text" name="parameter" value="'.$printer->listprinters[$line]['parameter'].'"></td>';
				print '<td>';
				print $form->buttonsSaveCancel("Save", '');
				print '</td>';
				print '</tr>';
			} else {
				print '<td>'.$printer->listprinters[$line]['name'].'</td>';
				print '<td>'.$langs->trans($printer->listprinters[$line]['fk_type_name']).'</td>';
				print '<td>'.$langs->trans($printer->listprinters[$line]['fk_profile_name']).'</td>';
				print '<td>'.$printer->listprinters[$line]['parameter'].'</td>';
				// edit icon
				print '<td class="right"><a class="editfielda marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=config&action=editprinter&token='.newToken().'&printerid='.$printer->listprinters[$line]['rowid'].'">';
				print img_picto($langs->trans("Edit"), 'edit');
				print '</a>';
				// delete icon
				print '<a class="marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=config&action=deleteprinter&token='.newToken().'&printerid='.$printer->listprinters[$line]['rowid'].'&printername='.urlencode($printer->listprinters[$line]['name']).'">';
				print img_picto($langs->trans("Delete"), 'delete');
				print '</a>';
				// test icon
				print '<a class="marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=config&action=testprinter&token='.newToken().'&printerid='.$printer->listprinters[$line]['rowid'].'&printername='.urlencode($printer->listprinters[$line]['name']).'">';
				print img_picto($langs->trans("TestPrinter"), 'printer');
				print '</a></td>';
				print '</tr>';
			}
		}
	}

	print '</table>';

	print dol_get_fiche_end();

	print '</form>';

	print '<br>';


	print load_fiche_titre($langs->trans("ReceiptPrinterTypeDesc"), '', '')."\n";

	print '<table class="noborder centpercent">'."\n";
	print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_DUMMY").':</td><td>'.$langs->trans("CONNECTOR_DUMMY_HELP").'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_NETWORK_PRINT").':</td><td>'.$langs->trans("CONNECTOR_NETWORK_PRINT_HELP").'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_FILE_PRINT").':</td><td>'.$langs->trans("CONNECTOR_FILE_PRINT_HELP").'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_WINDOWS_PRINT").':</td><td>'.$langs->trans("CONNECTOR_WINDOWS_PRINT_HELP").'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_CUPS_PRINT").':</td><td>'.$langs->trans("CONNECTOR_CUPS_PRINT_HELP").'</td></tr>';
	print '</table>';

	print '<br>';
}

// mode = template
if ($mode == 'template' && $user->admin) {
	print dol_get_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=template" autocomplete="off">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if ($action != 'edittemplate') {
		print '<input type="hidden" name="action" value="addtemplate">';
	} else {
		print '<input type="hidden" name="action" value="updatetemplate">';
	}

	print '<table class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("Name").'</th>';
	print '<th>'.$langs->trans("Template").'</th>';
	print '<th></th>';
	print "</tr>\n";
	$ret = $printer->listPrintersTemplates();
	//print '<pre>'.print_r($printer->listprinterstemplates, true).'</pre>';
	if ($ret > 0) {
		setEventMessages($printer->error, $printer->errors, 'errors');
	} else {
		$max = count($printer->listprinterstemplates);
		for ($line = 0; $line < $max; $line++) {
			print '<tr class="oddeven">';
			if ($action == 'edittemplate' && $printer->listprinterstemplates[$line]['rowid'] == $templateid) {
				print '<input type="hidden" name="templateid" value="'.$printer->listprinterstemplates[$line]['rowid'].'">';
				print '<td><input type="text" class="minwidth200" name="templatename" value="'.$printer->listprinterstemplates[$line]['name'].'"></td>';
				print '<td>';
				print '<textarea name="template" wrap="soft" cols="120" rows="12">'.$printer->listprinterstemplates[$line]['template'].'</textarea>';
				print '</td>';
				print '<td>';
				print $form->buttonsSaveCancel("Save", '');
				print '</td>';
			} else {
				print '<td>'.$printer->listprinterstemplates[$line]['name'].'</td>';
				print '<td>'.dol_htmlentitiesbr($printer->listprinterstemplates[$line]['template']).'</td>';
				// edit icon
				print '<td><a class="editfielda paddingleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=template&action=edittemplate&token='.newToken().'&templateid='.$printer->listprinterstemplates[$line]['rowid'].'">';
				print img_picto($langs->trans("Edit"), 'edit');
				print '</a>';
				// delete icon
				print '<a class="paddingleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=template&action=deletetemplate&token='.newToken().'&templateid='.$printer->listprinterstemplates[$line]['rowid'].'&templatename='.urlencode($printer->listprinterstemplates[$line]['name']).'">';
				print img_picto($langs->trans("Delete"), 'delete');
				print '</a>';
				// test icon
				print '<a class="paddingleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=template&action=testtemplate&token='.newToken().'&templateid='.$printer->listprinterstemplates[$line]['rowid'].'&templatename='.urlencode($printer->listprinterstemplates[$line]['name']).'">';
				print img_picto($langs->trans("TestPrinterTemplate"), 'printer');
				print '</a></td>';
			}
			print '</tr>';
		}
	}

	if ($action != 'edittemplate') {
		print '<tr>';
		print '<td><input type="text" class="minwidth200" name="templatename" value="'.$printer->listprinterstemplates[$line]['name'].'"></td>';
		print '<td>';
		print '<textarea name="template" wrap="soft" cols="120" rows="12">';
		print '</textarea>';
		print '</td>';
		print '<td>';
		print '<input type="hidden" name="templateid" value="'.$printer->listprinterstemplates[$line]['rowid'].'">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'">';
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	print '</form>';

	print dol_get_fiche_end();

	print '<br>';

	print '<table class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("Tag").'</th>';
	print '<th>'.$langs->trans("Description").'</th>';
	print "</tr>\n";

	$langs->loadLangs(array("bills", "companies"));
	foreach ($printer->tags as $key => $val) {
		print '<tr class="oddeven">';
		print '<td>{'.$key.'}</td><td>'.$langs->trans($val).'</td>';
		print '</tr>';
	}
	print '</table>';
}

// End of page
llxFooter();
$db->close();
