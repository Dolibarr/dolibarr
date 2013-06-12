<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/printipp/admin/printipp.php
 *      \ingroup    core
 *      \brief      Page to setup printipp module
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolprintipp.class.php';
require_once DOL_DOCUMENT_ROOT.'/printipp/lib/printipp.lib.php';

$langs->load("admin");
$langs->load("printipp");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');

if (!$mode) $mode='config';

/*
 * Action
 */
if ($action == 'setvalue' && $user->admin)
{
    $db->begin();

    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PRINTIPP_HOST",GETPOST('PRINTIPP_HOST','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PRINTIPP_PORT",GETPOST('PRINTIPP_PORT','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PRINTIPP_USER",GETPOST('PRINTIPP_USER','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PRINTIPP_PASSWORD",GETPOST('PRINTIPP_PASSWORD','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;

    if (! $error)
    {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        $db->rollback();
        dol_print_error($db);
    }
}


/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("PrintIPPSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PrintIPPSetup"),$linkback,'setup');

$head=printippadmin_prepare_head();

dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"));

print $langs->trans("PrintIPPDesc")."<br>\n";

print '<br>';

if ($mode=='config'&& $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=config">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setvalue">';


    print '<table class="nobordernopadding" width="100%">';

    $var=true;
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Parameters").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print "</tr>\n";

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("PRINTIPP_ENABLED").'</td><td colspan="2" align="left">';

    if (! empty($conf->use_javascript_ajax))
    {
        print ajax_constantonoff('PRINTIPP_ENABLED');
    }
    else
    {
        if (empty($conf->global->PRINTIPP_ENABLED))
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_PRINTIPP_ENABLED">'.img_picto($langs->trans("Disabled"),'off').'</a>';
         }
         else
         {
             print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_PRINTIPP_ENABLED">'.img_picto($langs->trans("Enabled"),'on').'</a>';
         }
    }
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("PRINTIPP_HOST").'</td><td>';
    print '<input size="64" type="text" name="PRINTIPP_HOST" value="'.$conf->global->PRINTIPP_HOST.'">';
    print ' &nbsp; '.$langs->trans("Example").': localhost';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("PRINTIPP_PORT").'</td><td>';
    print '<input size="32" type="text" name="PRINTIPP_PORT" value="'.$conf->global->PRINTIPP_PORT.'">';
    print ' &nbsp; '.$langs->trans("Example").': 631';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("PRINTIPP_USER").'</td><td>';
    print '<input size="32" type="text" name="PRINTIPP_USER" value="'.$conf->global->PRINTIPP_USER.'">';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("PRINTIPP_PASSWORD").'</td><td>';
    print '<input size="32" type="text" name="PRINTIPP_PASSWORD" value="'.$conf->global->PRINTIPP_PASSWORD.'">';
    print '</td></tr>';
    
    //$var=true;
    //print '<tr class="liste_titre">';
    //print '<td>'.$langs->trans("OtherParameter").'</td>';
    //print '<td>'.$langs->trans("Value").'</td>';
    //print "</tr>\n";

    print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';

    print '</table>';

    print '</form>';
}

if ($mode=='test'&& $user->admin)
{
    print '<table class="nobordernopadding" width="100%">';
    $printer = new dolPrintIPP($db,$conf->global->PRINTIPP_HOST,$conf->global->PRINTIPP_PORT,$user->login,$conf->global->PRINTIPP_USER,$conf->global->PRINTIPP_PASSWORD);
    $var=true;
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>Uri</td>';
    print '<td>Name</td>';
    print '<td>State</td>';
    print '<td>State_reason</td>';
    print '<td>State_reason1</td>';
    print '<td>BW</td>';
    print '<td>Color</td>';
    //print '<td>Device</td>';
    print '<td>Media</td>';
    print '<td>Supported</td>';
    print "</tr>\n";
    $list = $printer->getlist_available_printers();
    $var = True;
    foreach ($list as $value )
    {
        $var=!$var;
        $printer_det = $printer->get_printer_detail($value);
        print "<tr ".$bc[$var].">";
        print '<td>'.$value.'</td>';
        //print '<td><pre>'.print_r($printer_det,true).'</pre></td>';
        print '<td>'.$printer_det->printer_name->_value0.'</td>';
        print '<td>'.$printer_det->printer_state->_value0.'</td>';
        print '<td>'.$printer_det->printer_state_reasons->_value0.'</td>';
        print '<td>'.$printer_det->printer_state_reasons->_value1.'</td>';
        print '<td>'.$printer_det->printer_type->_value2.'</td>';
        print '<td>'.$printer_det->printer_type->_value3.'</td>';
        //print '<td>'.$printer_det->device_uri->_value0.'</td>';
        print '<td>'.$printer_det->media_default->_value0.'</td>';
        print '<td>'.$printer_det->media_type_supported->_value1.'</td>';
        print "</tr>\n";
    }
    print '</table>';
    
    if (count($list) == 0) print $langs->trans("NoPrinterFound");
}

dol_fiche_end();

llxFooter();
$db->close();
?>
