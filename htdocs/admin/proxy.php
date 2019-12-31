<?php
/* Copyright (C) 2011-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 *   	\file       htdocs/admin/proxy.php
 *      \ingroup    core
 *		\brief      Page  setup proxy to use for external web access
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "users", "admin"));

if (!$user->admin) accessforbidden();

$upload_dir=$conf->admin->dir_temp;


/*
 * Actions
 */

if (GETPOST('action', 'aZ09') == 'set_proxy')
{
    if (GETPOST("MAIN_USE_CONNECT_TIMEOUT") && ! is_numeric(GETPOST("MAIN_USE_CONNECT_TIMEOUT")))
    {
        setEventMessages($langs->trans("ErrorValueMustBeInteger"), null, 'errors');
        $error++;
    }
    if (GETPOST("MAIN_USE_RESPONSE_TIMEOUT") && ! is_numeric(GETPOST("MAIN_USE_RESPONSE_TIMEOUT")))
    {
        setEventMessages($langs->trans("ErrorValueMustBeInteger"), null, 'errors');
        $error++;
    }

    if (! $error)
    {
        $result=0;
        $result+=dolibarr_set_const($db, 'MAIN_USE_CONNECT_TIMEOUT', GETPOST("MAIN_USE_CONNECT_TIMEOUT"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_USE_RESPONSE_TIMEOUT', GETPOST("MAIN_USE_RESPONSE_TIMEOUT"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_USE', GETPOST("MAIN_PROXY_USE"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_HOST', GETPOST("MAIN_PROXY_HOST"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_PORT', GETPOST("MAIN_PROXY_PORT"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_USER', GETPOST("MAIN_PROXY_USER"), 'chaine', 0, '', $conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_PASS', GETPOST("MAIN_PROXY_PASS"), 'chaine', 0, '', $conf->entity);
        if ($result < 5) dol_print_error($db);
    }

    if (! $error)
    {
        setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
    }
}


/*
 * View
 */

$form = new Form($db);

$wikihelp='EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Proxy"), $wikihelp);

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("ProxyDesc")."</span><br>\n";
print "<br>\n";



print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_proxy">';


$head=security_prepare_head();

dol_fiche_head($head, 'proxy', $langs->trans("Security"), -1);


if ($conf->use_javascript_ajax)
{
    print "\n".'<script type="text/javascript" language="javascript">';
    print 'jQuery(document).ready(function () {
                function initfields()
                {
                    if (jQuery("#MAIN_PROXY_USE").val()==\'1\')
                    {
                        jQuery(".drag").show();
                    }
                    if (jQuery("#MAIN_PROXY_USE").val()==\'0\')
                    {
                        jQuery(".drag").hide();
                    }
                }
                initfields();
                jQuery("#MAIN_PROXY_USE").change(function() {
                    initfields();
                });
           })';
    print '</script>'."\n";
}


// Timeout

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td width="200">'.$langs->trans("Value").'</td>';
print "</tr>\n";


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ConnectionTimeout").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_USE_CONNECT_TIMEOUT" type="text" size="4" value="'.(isset($_POST["MAIN_USE_CONNECT_TIMEOUT"])?GETPOST("MAIN_USE_CONNECT_TIMEOUT"):$conf->global->MAIN_USE_CONNECT_TIMEOUT).'">';
print ' '.strtolower($langs->trans("Seconds"));
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ResponseTimeout").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_USE_RESPONSE_TIMEOUT" type="text" size="4" value="'.$conf->global->MAIN_USE_RESPONSE_TIMEOUT.'">';
print ' '.strtolower($langs->trans("Seconds"));
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_PROXY_USE").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print $form->selectyesno('MAIN_PROXY_USE', $conf->global->MAIN_PROXY_USE, 1);
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_PROXY_HOST").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_PROXY_HOST" type="text" size="16" value="'.$conf->global->MAIN_PROXY_HOST.'">';
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_PROXY_PORT").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_PROXY_PORT" type="text" size="4" value="'.$conf->global->MAIN_PROXY_PORT.'">';
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_PROXY_USER").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_PROXY_USER" type="text" size="16" value="'.$conf->global->MAIN_PROXY_USER.'">';
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MAIN_PROXY_PASS").'</td><td class="right">';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_PROXY_PASS" type="text" size="16" value="'.$conf->global->MAIN_PROXY_PASS.'">';
print '</td>';
print '</tr>';

print '</table>';

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
