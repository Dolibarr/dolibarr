<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *   	\file       htdocs/admin/proxy.php
 *      \ingroup    core
 *		\brief      Page  setup proxy to use for external web access
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("users");
$langs->load("admin");
$langs->load("other");


if (!$user->admin) accessforbidden();

$upload_dir=$conf->admin->dir_temp;


/*
 * Actions
 */

if (GETPOST("action") == 'set_proxy')
{
    if (GETPOST("MAIN_USE_CONNECT_TIMEOUT") && ! is_numeric(GETPOST("MAIN_USE_CONNECT_TIMEOUT")))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorValueMustBeInteger").'</div>';
        $error++;
    }
    if (GETPOST("MAIN_USE_RESPONSE_TIMEOUT") && ! is_numeric(GETPOST("MAIN_USE_RESPONSE_TIMEOUT")))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorValueMustBeInteger").'</div>';
        $error++;
    }

    if (! $error)
    {
        $result=0;
        $result+=dolibarr_set_const($db, 'MAIN_USE_CONNECT_TIMEOUT', GETPOST("MAIN_USE_CONNECT_TIMEOUT"), 'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_USE_RESPONSE_TIMEOUT', GETPOST("MAIN_USE_RESPONSE_TIMEOUT"), 'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_USE', GETPOST("MAIN_PROXY_USE"), 'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_HOST',GETPOST("MAIN_PROXY_HOST"),'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_PORT',GETPOST("MAIN_PROXY_PORT"),'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_USER',GETPOST("MAIN_PROXY_USER"),'chaine',0,'',$conf->entity);
        $result+=dolibarr_set_const($db, 'MAIN_PROXY_PASS',GETPOST("MAIN_PROXY_PASS"),'chaine',0,'',$conf->entity);
        if ($result < 5) dol_print_error($db);
    }

    if (! $error)
    {
        $mesg='<div class="ok">'.$langs->trans("RecordModifiedSuccessfully").'</div>';
    }
}


/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("Proxy"));

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("ProxyDesc")."<br>\n";
print "<br>\n";

$head=security_prepare_head();

dol_fiche_head($head, 'proxy', $langs->trans("Security"));


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
$var=true;

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_proxy">';

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td width="200">'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ConnectionTimeout").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_USE_CONNECT_TIMEOUT" type="text" size="4" value="'.(isset($_POST["MAIN_USE_CONNECT_TIMEOUT"])?GETPOST("MAIN_USE_CONNECT_TIMEOUT"):$conf->global->MAIN_USE_CONNECT_TIMEOUT).'">';
print ' '.$langs->trans("seconds");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ResponseTimeout").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_USE_RESPONSE_TIMEOUT" type="text" size="4" value="'.$conf->global->MAIN_USE_RESPONSE_TIMEOUT.'">';
print ' '.$langs->trans("seconds");
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MAIN_PROXY_USE").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print $form->selectyesno('MAIN_PROXY_USE',$conf->global->MAIN_PROXY_USE,1);
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bcdd[$var].'>';
print '<td>'.$langs->trans("MAIN_PROXY_HOST").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_PROXY_HOST" type="text" size="16" value="'.$conf->global->MAIN_PROXY_HOST.'">';
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bcdd[$var].'>';
print '<td>'.$langs->trans("MAIN_PROXY_PORT").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_PROXY_PORT" type="text" size="4" value="'.$conf->global->MAIN_PROXY_PORT.'">';
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bcdd[$var].'>';
print '<td>'.$langs->trans("MAIN_PROXY_USER").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_PROXY_USER" type="text" size="16" value="'.$conf->global->MAIN_PROXY_USER.'">';
print '</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bcdd[$var].'>';
print '<td>'.$langs->trans("MAIN_PROXY_PASS").'</td><td align="right">';
//print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_PROXY_PASS" type="text" size="16" value="'.$conf->global->MAIN_PROXY_PASS.'">';
print '</td>';
print '</tr>';

print '</table>';

dol_fiche_end();

print '<center>';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</center>';

print '</form><br>';


dol_htmloutput_mesg($mesg);


$db->close();

llxFooter();
?>
