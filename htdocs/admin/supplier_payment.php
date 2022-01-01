<?php
/* Copyright (C) 2015  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2016  Laurent Destailleur          <eldy@users.sourceforge.net>
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
 *      \file       htdocs/admin/supplier_payment.php
 *		\ingroup    supplier
 *		\brief      Page to setup supplier invoices payments
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "other", "bills", "orders"));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'supplier_payment';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
    $maskconstsupplierpayment = GETPOST('maskconstsupplierpayment', 'alpha');
    $masksupplierpayment = GETPOST('masksupplierpayment', 'alpha');
    if ($maskconstsupplierpayment) $res = dolibarr_set_const($db, $maskconstsupplierpayment, $masksupplierpayment, 'chaine', 0, '', $conf->entity);

    if (!$res > 0) $error++;

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}elseif ($action == 'setmod')
{
    dolibarr_set_const($db, "SUPPLIER_PAYMENT_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

// Activate a model
elseif ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

elseif ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->FACTURE_ADDON_PDF == "$value") dolibarr_del_const($db, 'SUPPLIER_PAYMENT_ADDON_PDF', $conf->entity);
	}
}

// Set default model
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "SUPPLIER_PAYMENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FACTURE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

elseif ($action == 'specimen')
{
    $modele = GETPOST('module', 'alpha');

    $paiementFourn = new PaiementFourn($db);
    $paiementFourn->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
	    $file = dol_buildpath($reldir."core/modules/supplier_payment/doc/pdf_".$modele.".modules.php", 0);
    	if (file_exists($file))
    	{
    		$filefound = 1;
    		$classname = "pdf_".$modele;
    		break;
    	}
    }

    if ($filefound)
    {
    	require_once $file;

    	$module = new $classname($db);

    	if ($module->write_file($paiementFourn, $langs) > 0)
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=supplier_payment&file=SPECIMEN.pdf");
    		return;
    	}
    	else
    	{
    		setEventMessages($module->error, $module->errors, 'errors');
    		dol_syslog($module->error, LOG_ERR);
    	}
    }
    else
    {
    	setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
    	dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("SupplierPaymentSetup"), 'EN:Supplier_Payment_Configuration|FR:Configuration_module_paiement_fournisseur');

$form = new Form($db);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SupplierPaymentSetup"), $linkback, 'title_setup');

print "<br>";

$head = supplierorder_admin_prepare_head();
dol_fiche_head($head, 'supplierpayment', $langs->trans("Suppliers"), -1, 'company');

/*
 *  Numbering module
 */

if (empty($conf->global->SUPPLIER_PAYMENT_ADDON)) $conf->global->SUPPLIER_PAYMENT_ADDON = 'mod_supplier_payment_bronan';

print load_fiche_titre($langs->trans("PaymentsNumberingModule"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$type."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows = $db->num_rows($resql);
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

print '<table class="noborder centpercent">';
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
    $dir = dol_buildpath($reldir."core/modules/supplier_payment/");
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
                    $classname = preg_replace('/\.php$/', '', $file);
                    // For compatibility
                    if (!is_file($dir.$filebis))
                    {
                        $filebis = $file."/".$file.".modules.php";
                        $classname = "mod_supplier_payment_".$file;
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
                            echo preg_replace('/\-.*$/', '', preg_replace('/mod_supplier_payment_/', '', preg_replace('/\.php$/', '', $file)));
                            print "</td><td>\n";

                            print $module->info();

                            print '</td>';

                            // Show example of numbering module
                            print '<td class="nowrap">';
                            $tmp = $module->getExample();
                            if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                            elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
                            else print $tmp;
                            print '</td>'."\n";

                            print '<td class="center">';
                            //print "> ".$conf->global->SUPPLIER_PAYMENT_ADDON." - ".$file;
                            if ($conf->global->SUPPLIER_PAYMENT_ADDON == $file || $conf->global->SUPPLIER_PAYMENT_ADDON.'.php' == $file)
                            {
                                print img_picto($langs->trans("Activated"), 'switch_on');
                            }
                            else
                            {
                                print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                            }
                            print '</td>';

                            $payment = new PaiementFourn($db);
                            $payment->initAsSpecimen();

                            // Example
                            $htmltooltip = '';
                            $htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                            $nextval = $module->getNextValue($mysoc, $payment);
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

                            if ($conf->global->PAYMENT_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
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


/*
 *  Document templates generators
 */
print '<br>';
print load_fiche_titre($langs->trans("PaymentsPDFModules"), '', '');

print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$realpath = $reldir."core/modules/supplier_payment/doc";
	$dir = dol_buildpath($realpath);

    if (is_dir($dir))
    {
        $handle = opendir($dir);


        if (is_resource($handle))
        {
            while (($file = readdir($handle)) !== false)
            {
                if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
                {
                    $name = substr($file, 4, dol_strlen($file) - 16);
                    $classname = substr($file, 0, dol_strlen($file) - 12);

	                require_once $dir.'/'.$file;
	                $module = new $classname($db, new PaiementFourn($db));

                    print "<tr class=\"oddeven\">\n";
                    print "<td>";
	                print (empty($module->name) ? $name : $module->name);
	                print "</td>\n";
                    print "<td>\n";
                    require_once $dir.'/'.$file;
                    $module = new $classname($db, $specimenthirdparty);
                    if (method_exists($module, 'info')) print $module->info($langs);
	                else print $module->description;

                    print "</td>\n";

                    // Active
                    if (in_array($name, $def))
                    {
                        print '<td class="center">'."\n";
                        //if ($conf->global->SUPPLIER_PAYMENT_ADDON_PDF != "$name")
                        //{
                            // Even if choice is the default value, we allow to disable it: For supplier invoice, we accept to have no doc generation at all
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=SUPPLIER_PAYMENT">';
                            print img_picto($langs->trans("Enabled"), 'switch_on');
                            print '</a>';
                        /*}
                        else
                        {
                            print img_picto($langs->trans("Enabled"),'switch_on');
                        }*/
                        print "</td>";
                    }
                    else
                    {
                        print '<td class="center">'."\n";
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=SUPPLIER_PAYMENT">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                        print "</td>";
                    }

                    // Default
                    print '<td class="center">';
                    if ($conf->global->SUPPLIER_PAYMENT_ADDON_PDF == "$name")
                    {
                        //print img_picto($langs->trans("Default"),'on');
                        // Even if choice is the default value, we allow to disable it: For supplier invoice, we accept to have no doc generation at all
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=SUPPLIER_PAYMENT"" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
                    }
                    else
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=SUPPLIER_PAYMENT"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
                    }
                    print '</td>';

                    // Info
                    $htmltooltip = ''.$langs->trans("Name").': '.$module->name;
                    $htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
                    $htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                    $htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

                    $htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                    $htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                    print '<td class="center">';
                    print $form->textwithpicto('', $htmltooltip, 1, 0);
                    print '</td>';
                    print '<td class="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"), 'order').'</a>';
                    print '</td>';

                    print "</tr>\n";
                }
            }

            closedir($handle);
        }
    }
}

print '</table>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
