<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/printing/admin/printing.php
 *      \ingroup    printing
 *      \brief      Page to setup printing module
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
require_once DOL_DOCUMENT_ROOT.'/printing/lib/printing.lib.php';

$langs->load("admin");
$langs->load("printing");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');
$value = GETPOST('value','alpha');
$varname = GETPOST('varname', 'alpha');
$driver = GETPOST('driver', 'alpha');

if (! empty($driver)) $langs->load($driver);

if (!$mode) $mode='config';

/*
 * Action
 */

if (($mode == 'test' || $mode == 'setup') && empty($driver))
{
    setEventMessage($langs->trans('PleaseSelectaDriverfromList'));
    header("Location: ".$_SERVER['PHP_SELF'].'?mode=config');
    exit;
}

if ($action == 'setconst' && $user->admin)
{
    $error=0;
    $db->begin();
    foreach ($_POST['setupdriver'] as $setupconst) {
        //print '<pre>'.print_r($setupconst, true).'</pre>';
        $result=dolibarr_set_const($db, $setupconst['varname'],$setupconst['value'],'chaine',0,'',$conf->entity);
        if (! $result > 0) $error++;
    }

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
    $action='';
}

if ($action == 'setvalue' && $user->admin)
{
    $db->begin();

    $result=dolibarr_set_const($db, $varname, $value,'chaine',0,'',$conf->entity);
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
    $action = '';
}

/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("PrintingSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PrintingSetup"),$linkback,'title_setup');

$head=printingadmin_prepare_head($mode);

if ($mode == 'setup' && $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=setup&amp;driver='.$driver.'" autocomplete="off">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setconst">';

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("PrintingDriverDesc".$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">'."\n";
    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Parameters").'</th>';
    print '<th>'.$langs->trans("Value").'</th>';
    print "</tr>\n";

    if (! empty($driver))
    {
        require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
        $classname = 'printing_'.$driver;
        $langs->load($driver);
        $printer = new $classname($db);
        //print '<pre>'.print_r($printer, true).'</pre>';
        $i=0;
        foreach ($printer->conf as $key)
        {
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td'.($key['required']?' class=required':'').'>'.$langs->trans($key['varname']).'</td><td>';
            print '<input size="32" type="'.(empty($key['type'])?'text':$key['type']).'" name="setupdriver['.$i.'][value]" value="'.$conf->global->{$key['varname']}.'"';
            print isset($key['moreattributes'])?$key['moreattributes']:'';
            print '>';
            print '<input type="hidden" name="setupdriver['.$i.'][varname]" value="'.$key['varname'].'">';
            print '&nbsp;'.($key['example']!=''?$langs->trans("Example").' : '.$key['example']:'');
            print '</tr>';
            $i++;
        }
    } else {
        print $langs->trans('PleaseSelectaDriverfromList');
    }

    print '</table>';
    if (! empty($driver))
    {
        print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Modify")).'"></center>';
    }
    print '</form>';
    dol_fiche_end();

}
if ($mode == 'config' && $user->admin)
{
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("PrintingDesc")."<br><br>\n";

    print '<table class="noborder" width="100%">'."\n";

    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Description").'</th>';
    print '<th class="center">'.$langs->trans("Active").'</th>';
    print '<th class="center">'.$langs->trans("Setup").'</th>';
    print '<th class="center">'.$langs->trans("TargetedPrinter").'</th>';
    print "</tr>\n";

    $object = new PrintingDriver($db);
    $result = $object->listDrivers($db, 10);
    foreach ($result as $driver) {
        require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
        $classname = 'printing_'.$driver;
        $langs->load($driver);
        $printer = new $classname($db);
        //print '<pre>'.print_r($printer, true).'</pre>';
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td>'.img_picto('', $printer->picto).$langs->trans($printer->desc).'</td>';
        print '<td class="center">';
        if (! empty($conf->use_javascript_ajax))
        {
            print ajax_constantonoff($printer->active);
        }
        else
        {
            if (empty($conf->global->{$printer->conf}))
            {
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&amp;varname='.$printer->active.'&amp;value=1">'.img_picto($langs->trans("Disabled"),'off').'</a>';
            }
            else
            {
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&amp;varname='.$printer->active.'&amp;value=0">'.img_picto($langs->trans("Enabled"),'on').'</a>';
            }
        }
        print '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?mode=setup&amp;driver='.$printer->name.'">'.img_picto('', 'setup').'</a></td>';
        print '<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?mode=test&amp;driver='.$printer->name.'">'.img_picto('', 'setup').'</a></td>';
        print '</tr>'."\n";
    }

    print '</table>';

    dol_fiche_end();
}

if ($mode == 'test' && $user->admin)
{
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans('PrintTestDesc'.$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">';
    if (! empty($driver))
    {
        require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
        $classname = 'printing_'.$driver;
        $langs->load($driver);
        $printer = new $classname($db);
        //print '<pre>'.print_r($printer, true).'</pre>';
        if (count($printer->getlist_available_printers())) {
            print $printer->listAvailablePrinters();
        }
        else {
            print $langs->trans('PleaseConfigureDriverfromList');
        }

    } else {
        print $langs->trans('PleaseSelectaDriverfromList');
    }
    print '</table>';

    dol_fiche_end();
}

if ($mode == 'userconf' && $user->admin)
{
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans('PrintUserConfDesc'.$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">';
    $var=true;
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("User").'</th>';
    print '<th>'.$langs->trans("PrintModule").'</th>';
    print '<th>'.$langs->trans("PrintDriver").'</th>';
    print '<th>'.$langs->trans("Printer").'</th>';
    print '<th>'.$langs->trans("PrinterLocation").'</th>';
    print '<th>'.$langs->trans("PrinterId").'</th>';
    print '<th>'.$langs->trans("NumberOfCopy").'</th>';
    print '<th class="center">'.$langs->trans("Delete").'</th>';
    print "</tr>\n";
    $sql = 'SELECT p.rowid, p.printer_name, p.printer_location, p.printer_id, p.copy, p.module, p.driver, p.userid, u.login FROM '.MAIN_DB_PREFIX.'printing as p, '.MAIN_DB_PREFIX.'user as u WHERE p.userid=u.rowid';
    $resql = $db->query($sql);
    while ($row=$db->fetch_array($resql)) {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td>'.$row['login'].'</td>';
        print '<td>'.$row['module'].'</td>';
        print '<td>'.$row['driver'].'</td>';
        print '<td>'.$row['printer_name'].'</td>';
        print '<td>'.$row['printer_location'].'</td>';
        print '<td>'.$row['printer_id'].'</td>';
        print '<td>'.$row['copy'].'</td>';
        print '<td class="center">'.img_picto($langs->trans("Delete"), 'delete').'</td>';
        print "</tr>\n";
    }
    print '</table>';

    dol_fiche_end();

}

llxFooter();

$db->close();
