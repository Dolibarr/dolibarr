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
 * \file        htdocs/admin/oauthlogintoken.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access to login information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
use OAuth\Common\Storage\DoliStorage;

$langs->load("admin");
$langs->load("printing");
$langs->load("oauth");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');
$value = GETPOST('value','alpha');
$varname = GETPOST('varname', 'alpha');
$driver = GETPOST('driver', 'alpha');

if (! empty($driver)) $langs->load($driver);

if (!$mode) $mode='setup';


/*
 * Action
 */

/*if (($mode == 'test' || $mode == 'setup') && empty($driver))
{
    setEventMessages($langs->trans('PleaseSelectaDriverfromList'), null);
    header("Location: ".$_SERVER['PHP_SELF'].'?mode=config');
    exit;
}*/

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

    $result=dolibarr_set_const($db, $varname, $value,'chaine',0,'',$conf->entity);
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

llxHeader('',$langs->trans("PrintingSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'),$linkback,'title_setup');

$head=oauthadmin_prepare_head($mode);

dol_fiche_head($head, 'tokengeneration', '', 0, 'technic');


if ($mode == 'setup' && $user->admin)
{

    print $langs->trans("OAuthSetupForLogin")."<br><br>\n";

    foreach($list as $key)
    {
        $supported=0;
        if (in_array($key[0], array_keys($supportedoauth2array))) $supported=1;
        if (! $supported) continue;     // show only supported
        
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=setup&amp;driver='.$driver.'" autocomplete="off">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="setconst">';
    
        
        print '<table class="noborder" width="100%">'."\n";
        $var=true;
        print '<tr class="liste_titre">';
        print '<th>'.$langs->trans("Parameters").'</th>';
        print '<th>'.$langs->trans("Value").'</th>';
        print '<th>&nbsp;</th>';
        print "</tr>\n";
        $submit_enabled=0;
    
        print '<tr '.$bc[$var?1:0].'>';
        print '<td'.($key['required']?' class=required':'').'>'.$langs->trans($key['varname']).'</td>';
        print '<td>'.$langs->trans($key['info']).'</td>';
        print '<td>';
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
        
        // Show value of token
        if ($key['varname'] == 'PRINTGCP_TOKEN_ACCESS')
        {
            // Token
            print '<tr '.$bc[$var?1:0].'>';
            print '<td>'.$langs->trans("Token").'</td>';
            print '<td>';
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
                print ''.implode(',',$tokenobj->getExtraParams());
                print '</textarea>';*/
            }
            print '</td>';
            print '<td>';
            print '</td>';
            print '</tr>'."\n";
        }
    
        print '</table>';

        if (! empty($driver))
        {
            if ($submit_enabled) {
                print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Modify")).'"></div>';
            }
        }
    
        print '</form>';
    }
    
}

if ($mode == 'test' && $user->admin)
{
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
            if ($printer->listAvailablePrinters()==0) {
                print $printer->resprint;
            } else {
                setEventMessages($printer->error, $printer->errors, 'errors');
            }
        }
        else {
            print $langs->trans('PleaseConfigureDriverfromList');
        }

    }
    
    print '</table>';

}

if ($mode == 'userconf' && $user->admin)
{
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
        print '<tr '.$bc[$var?1:0].'>';
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
}

dol_fiche_end();

llxFooter();

$db->close();
