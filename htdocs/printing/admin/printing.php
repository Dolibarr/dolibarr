<?php
/* Copyright (C) 2013-2016  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2015  Frederic France      <frederic.france@free.fr>
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
use OAuth\Common\Storage\DoliStorage;

// Load translation files required by the page
$langs->loadLangs(array('admin', 'printing', 'oauth'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$mode = GETPOST('mode', 'alpha');
$value = GETPOST('value', 'alpha', 0, null, null, 1);			// The value may be __google__docs so we force disable of replace
$varname = GETPOST('varname', 'alpha');
$driver = GETPOST('driver', 'alpha');

if (! empty($driver)) $langs->load($driver);

if (!$mode) $mode='config';

$OAUTH_SERVICENAME_GOOGLE = 'Google';


/*
 * Action
 */

if (($mode == 'test' || $mode == 'setup') && empty($driver))
{
    setEventMessages($langs->trans('PleaseSelectaDriverfromList'), null);
    header("Location: ".$_SERVER['PHP_SELF'].'?mode=config');
    exit;
}

if ($action == 'setconst' && $user->admin)
{
    $error=0;
    $db->begin();
    foreach ($_POST['setupdriver'] as $setupconst) {
        //print '<pre>'.print_r($setupconst, true).'</pre>';
        $result=dolibarr_set_const($db, $setupconst['varname'], $setupconst['value'], 'chaine', 0, '', $conf->entity);
        if (! $result > 0) $error++;
    }

    if (! $error)
    {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null);
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

    $result=dolibarr_set_const($db, $varname, $value, 'chaine', 0, '', $conf->entity);
    if (! $result > 0) $error++;

    if (! $error)
    {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null);
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

llxHeader('', $langs->trans("PrintingSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PrintingSetup"), $linkback, 'title_setup');

$head = printingAdminPrepareHead($mode);

if ($mode == 'setup' && $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=setup&amp;driver='.$driver.'" autocomplete="off">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setconst">';

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

    print $langs->trans("PrintingDriverDesc".$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Parameters").'</th>';
    print '<th>'.$langs->trans("Value").'</th>';
    print '<th>&nbsp;</th>';
    print "</tr>\n";
    $submit_enabled=0;

    if (! empty($driver)) {
        require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
        $classname = 'printing_'.$driver;
        $langs->load($driver);
        $printer = new $classname($db);

        $i=0;
        $submit_enabled=0;
        foreach ($printer->conf as $key)
        {
            switch ($key['type']) {
                case "text":
                case "password":
                    print '<tr class="oddeven">';
                    print '<td'.($key['required']?' class=required':'').'>'.$langs->trans($key['varname']).'</td>';
                    print '<td><input size="32" type="'.(empty($key['type'])?'text':$key['type']).'" name="setupdriver['.$i.'][value]" value="'.$conf->global->{$key['varname']}.'"';
                    print isset($key['moreattributes'])?' '.$key['moreattributes']:'';
                    print '><input type="hidden" name="setupdriver['.$i.'][varname]" value="'.$key['varname'].'"></td>';
                    print '<td>&nbsp;'.($key['example']!=''?$langs->trans("Example").' : '.$key['example']:'').'</td>';
                    print '</tr>'."\n";
                    break;
                case "info":    // Google Api setup or Google OAuth Token
                    print '<tr class="oddeven">';
                    print '<td'.($key['required']?' class=required':'').'>';
                    if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS')
                    {
                        print $langs->trans("IsTokenGenerated");
                    }
                    else
                    {
                        print $langs->trans($key['varname']);
                    }
                    print '</td>';
                    print '<td>'.$langs->trans($key['info']).'</td>';
                    print '<td>';
                    //var_dump($key);
                    if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS')
                    {
                        // Delete remote tokens
                        if (! empty($key['delete'])) print '<a class="button" href="'.$key['delete'].'">'.$langs->trans('DeleteAccess').'</a><br><br>';
                        // Request remote token
                        print '<a class="button" href="'.$key['renew'].'">'.$langs->trans('RequestAccess').'</a><br><br>';
                        // Check remote access
                        print $langs->trans("ToCheckDeleteTokenOnProvider", $OAUTH_SERVICENAME_GOOGLE).': <a href="https://security.google.com/settings/security/permissions" target="_google">https://security.google.com/settings/security/permissions</a>';
                    }
                    print '</td>';
                    print '</tr>'."\n";
                    break;
                case "submit":
                    if ($key['enabled']) $submit_enabled=1;
                    break;
            }
            $i++;

            if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS')
            {
                // Token
                print '<tr class="oddeven">';
                print '<td>'.$langs->trans("Token").'</td>';
                print '<td colspan="2">';
                $tokenobj=null;
                // Dolibarr storage
                $storage = new DoliStorage($db, $conf);
                try
                {
                    $tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME_GOOGLE);
                }
                catch(Exception $e)
                {
                    // Return an error if token not found
                }
                if (is_object($tokenobj))
                {
                    //var_dump($tokenobj);
                    print $tokenobj->getAccessToken().'<br>';
                    //print 'Refresh: '.$tokenobj->getRefreshToken().'<br>';
                    //print 'EndOfLife: '.$tokenobj->getEndOfLife().'<br>';
                    //var_dump($tokenobj->getExtraParams());
                    /*print '<br>Extra: <br><textarea class="quatrevingtpercent">';
                    print ''.join(',',$tokenobj->getExtraParams());
                    print '</textarea>';*/
                }
                print '</td>';
                print '</tr>'."\n";
            }
        }
    } else {
        print $langs->trans('PleaseSelectaDriverfromList');
    }

    print '</table>';

    dol_fiche_end();

    if (! empty($driver))
    {
        if ($submit_enabled) {
            print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Modify")).'"></div>';
        }
    }

    print '</form>';
}
if ($mode == 'config' && $user->admin)
{
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

    print $langs->trans("PrintingDesc")."<br><br>\n";

    print '<table class="noborder" width="100%">'."\n";

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

        print '<tr class="oddeven">';
        print '<td>'.img_picto('', $printer->picto).' '.$langs->trans($printer->desc).'</td>';
        print '<td class="center">';
        if (! empty($conf->use_javascript_ajax))
        {
            print ajax_constantonoff($printer->active);
        }
        else
        {
            if (empty($conf->global->{$printer->conf}))
            {
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&amp;varname='.$printer->active.'&amp;value=1">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
            }
            else
            {
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=setvalue&amp;varname='.$printer->active.'&amp;value=0">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
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
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

    print $langs->trans('PrintTestDesc'.$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">';
    if (! empty($driver))
    {
        require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
        $classname = 'printing_'.$driver;
        $langs->load($driver);
        $printer = new $classname($db);
        //print '<pre>'.print_r($printer, true).'</pre>';
        if (count($printer->getlistAvailablePrinters())) {
            if ($printer->listAvailablePrinters()==0) {
                print $printer->resprint;
            } else {
                setEventMessages($printer->error, $printer->errors, 'errors');
            }
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
    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), -1, 'technic');

    print $langs->trans('PrintUserConfDesc'.$driver)."<br><br>\n";

    print '<table class="noborder" width="100%">';
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

        print '<tr class="oddeven">';
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

// End of page
llxFooter();
$db->close();
