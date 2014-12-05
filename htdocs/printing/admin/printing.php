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
//require_once DOL_DOCUMENT_ROOT.'/core/class/dolprintipp.class.php';
//require_once DOL_DOCUMENT_ROOT.'/printing/lib/printing.lib.php';

$langs->load("admin");
$langs->load("printing");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$mode = GETPOST('mode','alpha');
$value = GETPOST('value','alpha');

if (!$mode) $mode='config';

/*
 * Action
 */



/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("PrintingSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PrintIPPSetup"),$linkback,'setup');

//$head=printippadmin_prepare_head();


if ($mode == 'config' && $user->admin)
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=config">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setvalue">';

    dol_fiche_head($head, $mode, $langs->trans("ModuleSetup"), 0, 'technic');

    print $langs->trans("PrintingDesc")."<br><br>\n";
    
    print '<table class="noborder" width="100%">';

    $var=true;
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Parameters").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print "</tr>\n";

    
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
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("PRINTIPP_USER").'</td><td>';
    print '<input size="32" type="text" name="PRINTIPP_USER" value="'.$conf->global->PRINTIPP_USER.'">';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("PRINTIPP_PASSWORD").'</td><td>';
    print '<input size="32" type="text" name="PRINTIPP_PASSWORD" value="'.$conf->global->PRINTIPP_PASSWORD.'">';
    print '</td></tr>';

    print '</table>';

    dol_fiche_end();
    
    //print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Modify")).'"></center>';
    

    print '</form>';


    //if (count($list) == 0) print $langs->trans("NoPrinterFound");

    dol_fiche_end();
}



llxFooter();

$db->close();
