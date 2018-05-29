<?php

/* Copyright (C) 2009       Laurent Destailleur        <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016  Juanjo Menent	       <jmenent@2byte.es>
 * Copyright (C) 2013-2014  Philippe Grand             <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *      \file       htdocs/admin/bank.php
 * 		\ingroup    bank
 * 		\brief      Page to setup the bank module
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("banks");

if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'bankaccount';


/*
 * Actions
 */

//Order display of bank account
if ($action == 'setbankorder') {
    if (dolibarr_set_const($db, "BANK_SHOW_ORDER_OPTION",
                    GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity) > 0) {
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    }
    else {
        dol_print_error($db);
    }
}

//Auto report last num releve on conciliate
if ($action == 'setreportlastnumreleve') {
    if (dolibarr_set_const($db, "BANK_REPORT_LAST_NUM_RELEVE", 1, 'chaine', 0,
                    '', $conf->entity) > 0) {
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    }
    else {
        dol_print_error($db);
    }
}
elseif ($action == 'unsetreportlastnumreleve') {
    if (dolibarr_set_const($db, "BANK_REPORT_LAST_NUM_RELEVE", 0, 'chaine', 0,
                    '', $conf->entity) > 0) {
        header("Location: " . $_SERVER["PHP_SELF"]);
        exit;
    }
    else {
        dol_print_error($db);
    }
}


if ($action == 'specimen') {
    $modele = GETPOST('module', 'alpha');

    if ($modele == 'sepamandate') {
        $object = new CompanyBankAccount($db);
    }
    else {
        $object = new Account($db);
    }
    $object->initAsSpecimen();

    // Search template files
    $file = '';
    $classname = '';
    $filefound = 0;
    $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir . "core/modules/bank/doc/pdf_" . $modele . ".modules.php",
                0);
        if (file_exists($file)) {
            $filefound = 1;
            $classname = "pdf_" . $modele;
            break;
        }
    }

    if ($filefound) {
        require_once $file;

        $module = new $classname($db);

        if ($module->write_file($object, $langs) > 0) {
            header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=bank&file=SPECIMEN.pdf");
            return;
        }
        else {
            setEventMessages($module->error, null, 'errors');
            dol_syslog($module->error, LOG_ERR);
        }
    }
    else {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// Activate a model
if ($action == 'set') {
    $ret = addDocumentModel($value, $type, $label, $scandir);
}
else if ($action == 'del') {
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        if ($conf->global->BANKADDON_PDF == "$value")
            dolibarr_del_const($db, 'BANKADDON_PDF', $conf->entity);
    }
}
// Set default model
else if ($action == 'setdoc') {
    if (dolibarr_set_const($db, "BANKADDON_PDF", $value, 'chaine', 0, '',
                    $conf->entity)) {
        // The constant that was read before the new set
        // We therefore requires a variable to have a coherent view
        $conf->global->BANKADDON_PDF = $value;
    }

    // On active le modele
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        $ret = addDocumentModel($value, $type, $label, $scandir);
    }
}



/*
 * view
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("BankSetupModule"));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("BankSetupModule"), $linkback,
                'title_setup');

$head = bank_admin_prepare_head(null);
dol_fiche_head($head, 'general', $langs->trans("BankSetupModule"), -1, 'account');

$var = true;

$var = !$var;

//Show bank account order
print load_fiche_titre($langs->trans("BankOrderShow"), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td align="center">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="60">&nbsp;</td>';
print "</tr>\n";

$bankorder[0][0] = $langs->trans("BankOrderGlobal");
$bankorder[0][1] = $langs->trans("BankOrderGlobalDesc");
$bankorder[0][2] = 'BankCode DeskCode BankAccountNumber BankAccountNumberKey';
$bankorder[1][0] = $langs->trans("BankOrderES");
$bankorder[1][1] = $langs->trans("BankOrderESDesc");
$bankorder[1][2] = 'BankCode DeskCode BankAccountNumberKey BankAccountNumber';

$var = true;
$i = 0;

$nbofbank = count($bankorder);
while ($i < $nbofbank) {
    $var = !$var;

    print '<tr class="oddeven">';
    print '<td>' . $bankorder[$i][0] . "</td><td>\n";
    print $bankorder[$i][1];
    print '</td>';
    print '<td class="nowrap">';
    $tmparray = explode(' ', $bankorder[$i][2]);
    foreach ($tmparray as $key => $val) {
        if ($key > 0)
            print ', ';
        print $langs->trans($val);
    }
    print "</td>\n";

    if ($conf->global->BANK_SHOW_ORDER_OPTION == $i) {
        print '<td align="center">';
        print img_picto($langs->trans("Activated"), 'on');
        print '</td>';
    }
    else {
        print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?action=setbankorder&amp;value=' . $i . '">';
        print img_picto($langs->trans("Disabled"), 'off');
        print '</a></td>';
    }
    print '<td>&nbsp;</td>';
    print '</tr>' . "\n";
    $i++;
}

print '</table>' . "\n";


print '<br><br>';


/*
 * Document templates generators
 */
//if (! empty($conf->global->MAIN_FEATURES_LEVEL))
//{
print load_fiche_titre($langs->trans("BankAccountModelModule"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
$sql .= " WHERE type = '" . $type . "'";
$sql .= " AND entity = " . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows) {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i ++;
    }
}
else {
    dol_print_error($db);
}

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Status") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Default") . "</td>\n";
print '<td align="center" width="38">' . $langs->trans("ShortInfo") . '</td>';
print '<td align="center" width="38">' . $langs->trans("Preview") . '</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    foreach (array('', '/doc') as $valdir) {
        $dir = dol_buildpath($reldir . "core/modules/bank" . $valdir);

        if (is_dir($dir)) {
            $handle = opendir($dir);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    $filelist[] = $file;
                }
                closedir($handle);
                arsort($filelist);

                foreach ($filelist as $file) {
                    if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/',
                                    $file)) {

                        if (file_exists($dir . '/' . $file)) {
                            $name = substr($file, 4, dol_strlen($file) - 16);
                            $classname = substr($file, 0, dol_strlen($file) - 12);

                            require_once $dir . '/' . $file;
                            $module = new $classname($db);

                            $modulequalified = 1;
                            if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL
                                    < 2)
                                $modulequalified = 0;
                            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL
                                    < 1)
                                $modulequalified = 0;

                            if ($modulequalified) {
                                print '<tr class="oddeven"><td width="100">';
                                print(empty($module->name) ? $name : $module->name);
                                print "</td><td>\n";
                                if (method_exists($module, 'info'))
                                    print $module->info($langs);
                                else
                                    print $module->description;
                                print '</td>';

                                // Active
                                if (in_array($name, $def)) {
                                    print '<td align="center">' . "\n";
                                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=del&value=' . $name . '">';
                                    print img_picto($langs->trans("Enabled"),
                                                    'switch_on');
                                    print '</a>';
                                    print '</td>';
                                }
                                else {
                                    print '<td align="center">' . "\n";
                                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=set&value=' . $name . '&amp;scan_dir=' . $module->scandir . '&amp;label=' . urlencode($module->name) . '">' . img_picto($langs->trans("Disabled"),
                                                    'switch_off') . '</a>';
                                    print "</td>";
                                }

                                // Default
                                print '<td align="center">';
                                if ($conf->global->BANKADDON_PDF == $name) {
                                    print img_picto($langs->trans("Default"),
                                                    'on');
                                }
                                else {
                                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setdoc&value=' . $name . '&amp;scan_dir=' . $module->scandir . '&amp;label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"),
                                                    'off') . '</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
                                $htmltooltip .= '<br>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
                                if ($module->type == 'pdf') {
                                    $htmltooltip .= '<br>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
                                }
                                $htmltooltip .= '<br><br><u>' . $langs->trans("FeaturesSupported") . ':</u>';
                                $htmltooltip .= '<br>' . $langs->trans("Logo") . ': ' . yn($module->option_logo,
                                                1, 1);
                                //$htmltooltip .= '<br>' . $langs->trans("PaymentMode") . ': ' . yn($module->option_modereg, 1, 1);
                                //$htmltooltip .= '<br>' . $langs->trans("PaymentConditions") . ': ' . yn($module->option_condreg, 1, 1);
                                $htmltooltip .= '<br>' . $langs->trans("MultiLanguage") . ': ' . yn($module->option_multilang,
                                                1, 1);
                                // $htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
                                // $htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
                                //$htmltooltip .= '<br>' . $langs->trans("WatermarkOnDraftOrders") . ': ' . yn($module->option_draft_watermark, 1, 1);

                                print '<td align="center">';
                                print $form->textwithpicto('', $htmltooltip, 1,
                                                0);
                                print '</td>';

                                // Preview
                                print '<td align="center">';
                                if ($module->type == 'pdf') {
                                    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=specimen&module=' . $name . '">' . img_object($langs->trans("Preview"),
                                                    'bill') . '</a>';
                                }
                                else {
                                    print img_object($langs->trans("PreviewNotAvailable"),
                                                    'generic');
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
//}



print '<br><br>';


/*
 * Document templates generators
 */
//if (! empty($conf->global->MAIN_FEATURES_LEVEL))
//{
print load_fiche_titre($langs->trans("BankAccountReleveModule"), '', '');


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Status") . "</td>\n";
print "</tr>\n";

print '<tr class="oddeven"><td width="100">';
print $langs->trans('AccountStatement');
print "</td><td>\n";
print $langs->trans('AutoReportLastAccountStatement');
print '</td>';
// Active
if ($conf->global->BANK_REPORT_LAST_NUM_RELEVE) {
    print '<td align="center">' . "\n";
    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=unsetreportlastnumreleve">';
    print img_picto($langs->trans("Enabled"), 'switch_on');
    print '</a>';
    print '</td>';
}
else {
    print '<td align="center">' . "\n";
    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setreportlastnumreleve">' . img_picto($langs->trans("Disabled"),
                    'switch_off') . '</a>';
    print "</td>";
}

print "</tr>\n";
print '</table>';
dol_fiche_end();

llxFooter();

$db->close();
