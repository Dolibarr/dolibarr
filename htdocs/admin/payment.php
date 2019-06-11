<?php
/* Copyright (C) 2015  Juanjo Menent				<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/payment.php
 *		\ingroup    facture
 *		\brief      Page to setup invoices payments
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "errors", "bills"));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type='invoice';

if (empty($conf->global->PAYMENT_ADDON)) $conf->global->PAYMENT_ADDON = 'mod_payment_cicada.php';


/*
 * Actions
 */

if ($action == 'updateMask')
{
    $maskconstpayment=GETPOST('maskconstpayment', 'alpha');
    $maskpayment=GETPOST('maskpayment', 'alpha');
    if ($maskconstpayment) $res = dolibarr_set_const($db, $maskconstpayment, $maskpayment, 'chaine', 0, '', $conf->entity);

    if (! $res > 0) $error++;

    if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

if ($action == 'setmod')
{
    dolibarr_set_const($db, "PAYMENT_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setparams')
{
	$freetext = GETPOST('FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS', 'none');	// No alpha here, we want exact string

	$res = dolibarr_set_const($db, "FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS", $freetext, 'chaine', 0, '', $conf->entity);

	if (! $res > 0) $error++;

	if ($error)
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
	if (! $error)
	{
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}


/*
 * View
 */

$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("BillsSetup"), 'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura');

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"), $linkback, 'title_setup');

$head = invoice_admin_prepare_head();
dol_fiche_head($head, 'payment', $langs->trans("Invoices"), -1, 'invoice');

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("PaymentsNumberingModule"), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    $dir = dol_buildpath($reldir."core/modules/payment/");
    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
                {
                    $filebis = $file;
                    $classname = preg_replace('/\.php$/', '', $file);
                    // For compatibility
                    if (! is_file($dir.$filebis))
                    {
                        $filebis = $file."/".$file.".modules.php";
                        $classname = "mod_payment_".$file;
                    }
                    // Check if there is a filter on country
                    preg_match('/\-(.*)_(.*)$/', $classname, $reg);
                    if (! empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;

                    $classname = preg_replace('/\-.*$/', '', $classname);
                    if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
                    {
                        // Charging the numbering class
                        require_once $dir.$filebis;

                        $module = new $classname($db);

                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                        if ($module->isEnabled())
                        {
                            $var = !$var;
                            print '<tr class="oddeven"><td width="100">';
                            echo preg_replace('/\-.*$/', '', preg_replace('/mod_payment_/', '', preg_replace('/\.php$/', '', $file)));
                            print "</td><td>\n";

                            print $module->info();

                            print '</td>';

                            // Show example of numbering module
                            print '<td class="nowrap">';
                            $tmp=$module->getExample();
                            if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                            elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                            else print $tmp;
                            print '</td>'."\n";

                            print '<td align="center">';
                            //print "> ".$conf->global->PAYMENT_ADDON." - ".$file;
                            if ($conf->global->PAYMENT_ADDON == $file || $conf->global->PAYMENT_ADDON.'.php' == $file)
                            {
                                print img_picto($langs->trans("Activated"), 'switch_on');
                            }
                            else
                            {
                                print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                            }
                            print '</td>';

                            $payment=new Paiement($db);
                            $payment->initAsSpecimen();

                            // Example
                            $htmltooltip='';
                            $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                            $nextval=$module->getNextValue($mysoc, $payment);
                            if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                                    $htmltooltip.=$langs->trans("NextValue").': ';
                                if ($nextval) {
                                    if (preg_match('/^Error/', $nextval) || $nextval=='NotConfigured')
                                        $nextval = $langs->trans($nextval);
                                    $htmltooltip.=$nextval.'<br>';
                                } else {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }

                            print '<td align="center">';
                            print $form->textwithpicto('', $htmltooltip, 1, 0);

                            if ($conf->global->PAYMENT_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
                            {
                                if (! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
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

print "<br>";

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="setparams" />';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

// Allow payments on different thirdparties bills but same parent company
print '<tr class="oddeven"><td>';
print $langs->trans("PaymentOnDifferentThirdBills");
print '</td><td width="60" align="center">';
print $form->selectyesno("FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS", $conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS, 1);
print '</td><td class="right">';
print "</td></tr>\n";

print '</table>';

print '<center>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print '</center>';

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
