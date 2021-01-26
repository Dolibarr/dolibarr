<?php
/* Copyright (C) 2009       Laurent Destailleur        <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016  Juanjo Menent	       <jmenent@2byte.es>
 * Copyright (C) 2013-2018  Philippe Grand             <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Jean-François Ferry         <jfefe@aternatik.fr>
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
 *      \file       htdocs/admin/bank.php
 *		\ingroup    bank
 *		\brief      Page to setup the bank module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "companies", "bills", "other", "banks"));

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');


if (empty($conf->global->CHEQUERECEIPTS_ADDON)) $conf->global->CHEQUERECEIPTS_ADDON = 'mod_chequereceipts_mint.php';



/*
 * Actions
 */

if ($action == 'updateMask')
{
	$maskconstchequereceipts = GETPOST('maskconstchequereceipts', 'alpha');
	$maskchequereceipts = GETPOST('maskchequereceipts', 'alpha');
	if ($maskconstchequereceipts) $res = dolibarr_set_const($db, $maskconstchequereceipts, $maskchequereceipts, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) $error++;

	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod')
{
	dolibarr_set_const($db, "CHEQUERECEIPTS_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'set_BANK_CHEQUERECEIPT_FREE_TEXT')
{
	$freetext = GETPOST('BANK_CHEQUERECEIPT_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string

	$res = dolibarr_set_const($db, "BANK_CHEQUERECEIPT_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) $error++;

 	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
llxHeader("", $langs->trans("BankSetupModule"));

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BankSetupModule"), $linkback, 'title_setup');

$head = bank_admin_prepare_head(null);
print dol_get_fiche_head($head, 'checkreceipts', $langs->trans("BankSetupModule"), -1, 'account');

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("ChequeReceiptsNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/cheque/");
	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle)) !== false)
			{
				if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
				{
					$filebis = $file;
					$name = substr($file, 4, dol_strlen($file) - 16);
					$classname = preg_replace('/\.php$/', '', $file);
					// For compatibility
					if (!is_file($dir.$filebis))
					{
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_chequereceipt_".$file;
					}
					// Check if there is a filter on country
					preg_match('/\-(.*)_(.*)$/', $classname, $reg);
					if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;

					$classname = preg_replace('/\-.*$/', '', $classname);
					if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php')
					{
						// Charging the numbering class
						require_once $dir.$filebis;

						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled())
						{
							print '<tr class="oddeven"><td width="100">';
							print (empty($module->name) ? $name : $module->name);
							print "</td><td>\n";

							print $module->info();

							print '</td>';

							// Show example of numbering module
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								$langs->load("errors");
								print '<div class="error">'.$langs->trans($tmp).'</div>';
							} elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
							else print $tmp;
							print '</td>'."\n";

							print '<td class="center">';
							if ($conf->global->CHEQUERECEIPTS_ADDON == $file || $conf->global->CHEQUERECEIPTS_ADDON.'.php' == $file)
							{
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$chequereceipts = new RemiseCheque($db);
							$chequereceipts->initAsSpecimen();

							// Example
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $chequereceipts);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValue").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
										$nextval = $langs->trans($nextval);
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);

							if ($conf->global->CHEQUERECEIPTS_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
							{
								if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
							}

							print '</td>';

							print "</tr>\n";
						}
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';

print '<br>';


/*
 * Other options
 */
print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_BANK_CHEQUERECEIPT_FREE_TEXT">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td class="center" width="60">&nbsp;</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val)	$htmltext .= $key.'<br>';
$htmltext .= '</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnChequeReceipts"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'BANK_CHEQUERECEIPT_FREE_TEXT';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td><td class="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</table>';
print "<br>";

print '</table>'."\n";

print dol_get_fiche_end();

print '</form>';

// End of page
llxFooter();
$db->close();
