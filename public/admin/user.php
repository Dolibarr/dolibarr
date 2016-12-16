<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
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
 *   	\file       htdocs/admin/user.php
 *		\ingroup    core
 *		\brief      Page to setup user module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("admin");
$langs->load("members");
$langs->load("users");

if (! $user->admin) accessforbidden();

$extrafields = new ExtraFields($db);


/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}
//Set hide closed customer into combox or select
if ($action == 'sethideinactiveuser')
{
	$status = GETPOST('status','alpha');

	if (dolibarr_set_const($db, "USER_HIDE_INACTIVE_IN_COMBOBOX",$status,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */

$help_url='EN:Module_Users|FR:Module_Utilisateurs|ES:M&oacute;dulo_Usuarios';
llxHeader('',$langs->trans("UsersSetup"),$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("UsersSetup"),$linkback,'title_setup');


$head=user_admin_prepare_head();

dol_fiche_head($head,'card', $langs->trans("MenuUsersAndGroups"), 0, 'user');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

$var=true;
$form = new Form($db);

// Mail required for members
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UserMailRequired").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('USER_MAIL_REQUIRED');
}
else
{
	if (empty($conf->global->USER_MAIL_REQUIRED))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_USER_MAIL_REQUIRED">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_USER_MAIL_REQUIRED">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';

dol_fiche_end();

llxFooter();
$db->close();
