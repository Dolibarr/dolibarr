<?php
/* Copyright (C) 2013-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2016      Juanjo Menent        <jmenent@2byte.es>
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
$langs->loadLangs(array("admin","receiptprinter"));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');

$printername = GETPOST('printername', 'alpha');
$printerid = GETPOST('printerid', 'int');
$parameter = GETPOST('parameter', 'alpha');

$template = GETPOST('template', 'alpha');
$templatename = GETPOST('templatename', 'alpha');
$templateid = GETPOST('templateid', 'int');

$printer = new dolReceiptPrinter($db);

if (!$mode) $mode='config';

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
        return gzinflate(substr($data,10,-8));
    }
}

/*
 * Action
 */

if ($action == 'addprinter' && $user->admin)
{
    $error=0;
    $db->begin();
    if (empty($printername)) {
        $error++;
        setEventMessages($langs->trans("PrinterNameEmpty"), null, 'errors');
    }

    if (empty($parameter)) {
        setEventMessages($langs->trans("PrinterParameterEmpty"), null, 'warnings');
    }

    if (! $error)
    {
        $result= $printer->AddPrinter($printername, GETPOST('printertypeid', 'int'), GETPOST('printerprofileid', 'int'), $parameter);
        if ($result > 0) $error++;

        if (! $error)
        {
            $db->commit();
            setEventMessages($langs->trans("PrinterAdded",$printername), null);
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
    $action = '';
}

if ($action == 'deleteprinter' && $user->admin)
{
    $error=0;
    $db->begin();
    if (empty($printerid)) {
        $error++;
        setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
    }

    if (! $error)
    {
        $result= $printer->DeletePrinter($printerid);
        if ($result > 0) $error++;

        if (! $error)
        {
            $db->commit();
            setEventMessages($langs->trans("PrinterDeleted",$printername), null);
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
    $action = '';
}

if ($action == 'updateprinter' && $user->admin)
{
    $error=0;
    $db->begin();
    if (empty($printerid)) {
        $error++;
        setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
    }

    if (! $error)
    {
        $result= $printer->UpdatePrinter($printername, GETPOST('printertypeid', 'int'), GETPOST('printerprofileid', 'int'), $parameter, $printerid);
        if ($result > 0) $error++;

        if (! $error)
        {
            $db->commit();
            setEventMessages($langs->trans("PrinterUpdated",$printername), null);
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
    $action = '';
}

if ($action == 'testprinter' && $user->admin)
{
    $error=0;
    if (empty($printerid)) {
        $error++;
        setEventMessages($langs->trans("PrinterIdEmpty"), null, 'errors');
    }

    if (! $error)
    {
        // test
        $ret = $printer->SendTestToPrinter($printerid);
        if ($ret == 0)
        {
            setEventMessages($langs->trans("TestSentToPrinter", $printername), null);
        }
        else
        {
            setEventMessages($printer->error, $printer->errors, 'errors');
        }
    }
    $action = '';
}


if ($action == 'updatetemplate' && $user->admin)
{
    $error=0;
    $db->begin();
    if (empty($templateid)) {
        $error++;
        setEventMessages($langs->trans("TemplateIdEmpty"), null, 'errors');
    }

    if (! $error)
    {
        $result= $printer->UpdateTemplate($templatename, $template, $templateid);
        if ($result > 0) $error++;

        if (! $error)
        {
            $db->commit();
            setEventMessages($langs->trans("TemplateUpdated",$templatename), null);
        }
        else
        {
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

llxHeader('',$langs->trans("ReceiptPrinterSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ReceiptPrinterSetup"),$linkback,'title_setup');

$head = receiptprinteradmin_prepare_head($mode);

if ($mode == 'config' && $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=config" autocomplete="off">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    if ($action!='editprinter') {
        print '<input type="hidden" name="action" value="addprinter">';
    } else {
        print '<input type="hidden" name="action" value="updateprinter">';
    }

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("ReceiptPrinterDesc")."<br><br>\n";

    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Name").'</th>';
    print '<th>'.$langs->trans("Type").'</th>';
    print '<th>'.$langs->trans("Profile").'</th>';
    print '<th>'.$langs->trans("Parameters").'</th>';
    print '<th></th>';
    print '<th></th>';
    print '<th></th>';
    print "</tr>\n";
    $ret = $printer->listprinters();
    $nbofprinters = count($printer->listprinters);

    if ($ret > 0) {
        setEventMessages($printer->error, $printer->errors, 'errors');
    } else {
        for ($line=0; $line < $nbofprinters; $line++)
        {
            print '<tr class="oddeven">';
            if ($action=='editprinter' && $printer->listprinters[$line]['rowid']==$printerid)
            {
                print '<input type="hidden" name="printerid" value="'.$printer->listprinters[$line]['rowid'].'">';
                print '<td><input size="50" type="text" name="printername" value="'.$printer->listprinters[$line]['name'].'"></td>';
                $ret = $printer->selectTypePrinter($printer->listprinters[$line]['fk_type']);
                print '<td>'.$printer->resprint.'</td>';
                $ret = $printer->selectProfilePrinter($printer->listprinters[$line]['fk_profile']);
                print '<td>'.$printer->profileresprint.'</td>';
                print '<td><input size="60" type="text" name="parameter" value="'.$printer->listprinters[$line]['parameter'].'"></td>';
                print '<td></td>';
                print '<td></td>';
                print '<td></td>';
                print '</tr>';
             } else {
                print '<td>'.$printer->listprinters[$line]['name'].'</td>';
                print '<td>'.$langs->trans($printer->listprinters[$line]['fk_type_name']).'</td>';
                print '<td>'.$langs->trans($printer->listprinters[$line]['fk_profile_name']).'</td>';
                print '<td>'.$printer->listprinters[$line]['parameter'].'</td>';
                // edit icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=config&amp;action=editprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'">';
                print img_picto($langs->trans("Edit"),'edit');
                print '</a></td>';
                // delete icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=config&amp;action=deleteprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'&amp;printername='.$printer->listprinters[$line]['name'].'">';
                print img_picto($langs->trans("Delete"),'delete');
                print '</a></td>';
                // test icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=config&amp;action=testprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'&amp;printername='.$printer->listprinters[$line]['name'].'">';
                print img_picto($langs->trans("TestPrinter"),'printer');
                print '</a></td>';
                print '</tr>';
            }
        }
    }

    if ($action!='editprinter')
    {
        if ($nbofprinters > 0)
        {
            print '<tr class="liste_titre">';
            print '<th>'.$langs->trans("Name").'</th>';
            print '<th>'.$langs->trans("Type").'</th>';
            print '<th>'.$langs->trans("Profile").'</th>';
            print '<th>'.$langs->trans("Parameters").'</th>';
            print '<th></th>';
            print '<th></th>';
            print '<th></th>';
            print "</tr>\n";
        }

        print '<tr>';
        print '<td><input size="50" type="text" name="printername"></td>';
        $ret = $printer->selectTypePrinter();
        print '<td>'.$printer->resprint.'</td>';
        $ret = $printer->selectProfilePrinter();
        print '<td>'.$printer->profileresprint.'</td>';
        print '<td><input size="60" type="text" name="parameter"></td>';
        print '<td></td>';
        print '<td></td>';
        print '<td></td>';
        print '</tr>';
    }
    print '</table>';

    dol_fiche_end();

    if ($action!='editprinter') {
        print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'"></div>';
    } else {
        print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';
    }
    print '</form>';

    print '<div><p></div>';

    dol_fiche_head();

    print $langs->trans("ReceiptPrinterTypeDesc")."<br><br>\n";
    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_DUMMY").':</td><td>'.$langs->trans("CONNECTOR_DUMMY_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_NETWORK_PRINT").':</td><td>'.$langs->trans("CONNECTOR_NETWORK_PRINT_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_FILE_PRINT").':</td><td>'.$langs->trans("CONNECTOR_FILE_PRINT_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_WINDOWS_PRINT").':</td><td>'.$langs->trans("CONNECTOR_WINDOWS_PRINT_HELP").'</td></tr>';
    //print '<tr class="oddeven"><td>'.$langs->trans("CONNECTOR_JAVA").':</td><td>'.$langs->trans("CONNECTOR_JAVA_HELP").'</td></tr>';
    print '</table>';
    dol_fiche_end();

    print '<div><p></div>';

    dol_fiche_head();
    print $langs->trans("ReceiptPrinterProfileDesc")."<br><br>\n";
    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="oddeven"><td>'.$langs->trans("PROFILE_DEFAULT").':</td><td>'.$langs->trans("PROFILE_DEFAULT_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("PROFILE_SIMPLE").':</td><td>'.$langs->trans("PROFILE_SIMPLE_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("PROFILE_EPOSTEP").':</td><td>'.$langs->trans("PROFILE_EPOSTEP_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("PROFILE_P822D").':</td><td>'.$langs->trans("PROFILE_P822D_HELP").'</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("PROFILE_STAR").':</td><td>'.$langs->trans("PROFILE_STAR_HELP").'</td></tr>';
    print '</table>';
    dol_fiche_end();
}

if ($mode == 'template' && $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=template" autocomplete="off">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    if ($action!='edittemplate') {
        print '<input type="hidden" name="action" value="addtemplate">';
    } else {
        print '<input type="hidden" name="action" value="updatetemplate">';
    }

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("ReceiptPrinterTemplateDesc")."<br><br>\n";
    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Name").'</th>';
    print '<th>'.$langs->trans("Template").'</th>';
    print '<th></th>';
    print '<th></th>';
    print '<th></th>';
    print "</tr>\n";
    $ret = $printer->listPrintersTemplates();
    //print '<pre>'.print_r($printer->listprinterstemplates, true).'</pre>';
    if ($ret > 0) {
        setEventMessages($printer->error, $printer->errors, 'errors');
    } else {
        $max = count($printer->listprinterstemplates);
        for ($line=0; $line < $max; $line++)
        {
            print '<tr class="oddeven">';
            if ($action=='edittemplate' && $printer->listprinterstemplates[$line]['rowid']==$templateid) {
                print '<input type="hidden" name="templateid" value="'.$printer->listprinterstemplates[$line]['rowid'].'">';
                print '<td><input size="50" type="text" name="templatename" value="'.$printer->listprinterstemplates[$line]['name'].'"></td>';
                print '<td><textarea name="template" wrap="soft" cols="120" rows="12">'.$printer->listprinterstemplates[$line]['template'].'</textarea>';
                print '</td>';
                print '<td></td>';
                print '<td></td>';
                print '<td></td>';
            } else {
                print '<td>'.$printer->listprinterstemplates[$line]['name'].'</td>';
                print '<td>'.nl2br(htmlentities($printer->listprinterstemplates[$line]['template'])).'</td>';
                // edit icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=template&amp;action=edittemplate&amp;templateid='.$printer->listprinterstemplates[$line]['rowid'].'">';
                print img_picto($langs->trans("Edit"),'edit');
                print '</a></td>';
                // delete icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=template&amp;action=deletetemplate&amp;templateid='.$printer->listprinterstemplates[$line]['rowid'].'&amp;templatename='.$printer->listprinterstemplates[$line]['name'].'">';
                print img_picto($langs->trans("Delete"),'delete');
                print '</a></td>';
                // test icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?mode=template&amp;action=testtemplate&amp;templateid='.$printer->listprinterstemplates[$line]['rowid'].'&amp;templatename='.$printer->listprinterstemplates[$line]['name'].'">';
                print img_picto($langs->trans("TestPrinterTemplate"),'printer');
                print '</a></td>';
            }
            print '</tr>';
        }
    }

    print '</table>';
    if ($action!='edittemplate') {
        print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'"></div>';
    } else {
        print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';
    }
    print '</form>';
    print '<div><p></div>';
    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Tag").'</th>';
    print '<th>'.$langs->trans("Description").'</th>';
    print "</tr>\n";
    $max = count($printer->tags);
    for ($tag=0; $tag < $max; $tag++)
    {
        print '<tr class="oddeven">';
        print '<td>&lt;'.$printer->tags[$tag].'&gt;</td><td>'.$langs->trans(strtoupper($printer->tags[$tag])).'</td>';
        print '</tr>';
    }
    print '</table>';

    dol_fiche_end();
}

// to remove after test
// $object=new stdClass();
// $object->date_time = '2015-11-02 22:30:25';
// $object->id = 1234;
// $object->customer_firstname  = 'John';
// $object->customer_lastname  = 'Deuf';
// $object->vendor_firstname  = 'Jim';
// $object->vendor_lastname  = 'Big';
// $object->barcode = '3700123862396';
//$printer->sendToPrinter($object, 1, 16);
//setEventMessages($printer->error, $printer->errors, 'errors');

// End of page
llxFooter();
$db->close();
