<?php
/* Copyright (C) 2013      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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

$langs->load("admin");
$langs->load("receiptprinter");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');
$value = GETPOST('value','alpha');
$varname = GETPOST('varname', 'alpha');
$printername = GETPOST('printername', 'alpha');
$printerid = GETPOST('printerid', 'int');
$parameter = GETPOST('parameter', 'alpha');

$printer = new dolReceiptPrinter($db);

if (!$mode) $mode='config';

if (!function_exists('gzdecode')) {
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
        $result= $printer->AddPrinter($printername, GETPOST('printertypeid', 'int'), $parameter);
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
        $result= $printer->UpdatePrinter($printername, GETPOST('printertypeid', 'int'), $parameter, $printerid);
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


/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("ReceiptPrinterSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
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
    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Name").'</th>';
    print '<th>'.$langs->trans("Type").'</th>';
    print '<th>'.$langs->trans("Parameters").'</th>';
    print '<th></th>';
    print '<th></th>';
    print '<th></th>';
    print "</tr>\n";
    $ret = $printer->listprinters();
    if ($ret > 0) {
        setEventMessages($printer->error, $printer->errors, 'errors');
    } else {
        for ($line=0; $line < count($printer->listprinters); $line++) {
            $var = !$var;
            print '<tr '.$bc[$var].'>';
            if ($action=='editprinter' && $printer->listprinters[$line]['rowid']==$printerid) {
                print '<input type="hidden" name="printerid" value="'.$printer->listprinters[$line]['rowid'].'">';
                print '<td><input size="50" type="text" name="printername" value="'.$printer->listprinters[$line]['name'].'"></td>';
                $ret = $printer->selectTypePrinter($printer->listprinters[$line]['fk_type']);
                print '<td>'.$printer->resprint.'</td>';
                print '<td><input size="60" type="text" name="parameter" value="'.$printer->listprinters[$line]['parameter'].'"></td>';
                print '<td></td>';
                print '<td></td>';
                print '<td></td>';
                print '</tr>';
             } else {
                print '<td>'.$printer->listprinters[$line]['name'].'</td>';
                switch ($printer->listprinters[$line]['fk_type']) {
                    case 1:
                        $connector = 'CONNECTOR_DUMMY';
                        break;
                    case 2:
                        $connector = 'CONNECTOR_FILE_PRINT';
                        break;
                    case 3:
                        $connector = 'CONNECTOR_NETWORK_PRINT';
                        break;
                    case 4:
                        $connector = 'CONNECTOR_WINDOWS_PRINT';
                        break;
                    case 5:
                        $connector = 'CONNECTOR_JAVA';
                        break;
                    default:
                        $connector = 'CONNECTOR_UNKNOWN';
                        break;
                }
                print '<td>'.$langs->trans($connector).'</td>';
                print '<td>'.$printer->listprinters[$line]['parameter'].'</td>';
                // edit icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=editprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'">';
                print img_picto($langs->trans("Edit"),'edit');
                print '</a></td>';
                // delete icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=deleteprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'&amp;printername='.$printer->listprinters[$line]['name'].'">';
                print img_picto($langs->trans("Delete"),'delete');
                print '</a></td>';
                // test icon
                print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=testprinter&amp;printerid='.$printer->listprinters[$line]['rowid'].'&amp;printername='.$printer->listprinters[$line]['name'].'">';
                print img_picto($langs->trans("TestPrinter"),'printer');
                print '</a></td>';
                print '</tr>';
            }
        }
    }

    if ($action!='editprinter') {
        print '<tr class="liste_titre">';
        print '<th>'.$langs->trans("Name").'</th>';
        print '<th>'.$langs->trans("Type").'</th>';
        print '<th>'.$langs->trans("Parameters").'</th>';
        print '<th></th>';
        print '<th></th>';
        print '<th></th>';
        print "</tr>\n";
        print '<tr>';
        print '<td><input size="50" type="text" name="printername"></td>';
        $ret = $printer->selectTypePrinter();
        print '<td>'.$printer->resprint.'</td>';
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

}

if ($mode == 'template' && $user->admin)
{
     $tags = array(
        'dol_align_left',
        'dol_align_center',
        'dol_align_right',
        'dol_use_font_a',
        'dol_use_font_b',
        'dol_use_font_c',
        'dol_bold',
        '/dol_bold',
        'dol_double_height',
        '/dol_double_height',
        'dol_double_width',
        '/dol_double_width',
        'dol_underline',
        '/dol_underline',
        'dol_underline_2dots',
        '/dol_underline',
        'dol_emphasized',
        '/dol_emphasized',
        'dol_switch_colors',
        '/dol_switch_colors',
        'dol_print_barcode',
        'dol_print_barcode_customer_id',
        'dol_set_print_width_57',
        'dol_cut_paper_full',
        'dol_cut_paper_partial',
        'dol_open_drawer',
        'dol_activate_buzzer',
        'dol_print_qrcode',
        'dol_print_date',
        'dol_print_date_time',
        'dol_print_year',
        'dol_print_month_letters',
        'dol_print_month',
        'dol_print_day',
        'dol_print_day_letters',
        'dol_print_table',
        'dol_print_cutlery',
        'dol_print_payment',
        'dol_print_logo',
        'dol_print_logo_old',
        'dol_print_order_lines',
        'dol_print_order_tax',
        'dol_print_order_local_tax',
        'dol_print_order_total',
        'dol_print_order_number',
        'dol_print_order_number_unique',
        'dol_print_customer_first_name',
        'dol_print_customer_last_name',
        'dol_print_customer_mail',
        'dol_print_customer_telephone',
        'dol_print_customer_mobile',
        'dol_print_customer_skype',
        'dol_print_customer_tax_number',
        'dol_print_customer_account_balance',
        'dol_print_vendor_last_name',
        'dol_print_vendor_first_name',
        'dol_print_vendor_mail',
        'dol_print_customer_points',
        'dol_print_order_points',
        'dol_print_if_customer',
        'dol_print_if_vendor',
        'dol_print_if_happy_hour',
        'dol_print_if_num_order_unique',
        'dol_print_if_customer_points',
        'dol_print_if_order_points',
        'dol_print_if_customer_tax_number',
        'dol_print_if_customer_account_balance_positive',
    );

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=config" autocomplete="off">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="addtemplate">';

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("ReceiptPrinterTemplateDesc")."<br><br>\n";
    print '<table class="noborder" width="100%">'."\n";
    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Template").'</th>';
    print '<th></th>';
    print "</tr>\n";
    print '</table>';
    print '</form>';
    print '<table class="noborder" width="100%">'."\n";
    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Tag").'</th>';
    print '<th>'.$langs->trans("Description").'</th>';
    print "</tr>\n";
    for ($tag=0; $tag < count($tags); $tag++) {
        $var = !$var;
        print '<tr '.$bc[$var].'>';
        print '<td>&lt;'.$tags[$tag].'&gt;</td><td>'.$langs->trans(strtoupper($tags[$tag])).'</td>';
        print '</tr>';
    }
    print '</table>';
    dol_fiche_end();

}


llxFooter();

$db->close();

