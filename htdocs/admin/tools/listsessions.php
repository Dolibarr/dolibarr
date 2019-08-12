<?php
/* Copyright (C) 2004-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 *		\file       htdocs/admin/tools/listsessions.php
 *      \ingroup    core
 *      \brief      List of PHP sessions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","install","users","other"));

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="dateevent";


/*
 * Actions
 */

// Purge sessions
if ($action == 'confirm_purge' && $confirm == 'yes' && $user->admin)
{
	$res=purgeSessions(session_id());
}

// Lock new sessions
if ($action == 'confirm_lock' && $confirm == 'yes' && $user->admin)
{
	if (dolibarr_set_const($db, 'MAIN_ONLY_LOGIN_ALLOWED', $user->login, 'text', 1, 'Logon is restricted to a particular user', 0) < 0)
	{
		dol_print_error($db);
	}
}

// Unlock new sessions
if ($action == 'confirm_unlock' && $user->admin)
{
	if (dolibarr_del_const($db, 'MAIN_ONLY_LOGIN_ALLOWED', -1) < 0)
	{
		dol_print_error($db);
	}
}



/*
*	View
*/

llxHeader();

$form=new Form($db);

$userstatic=new User($db);
$usefilter=0;

$listofsessions=listOfSessions();
$num=count($listofsessions);

print_barre_liste($langs->trans("Sessions"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, ($num?$num:''), 'setup');		// Do not show numer (0) if no session found (it means we can't know)

$savehandler=ini_get("session.save_handler");
$savepath=ini_get("session.save_path");
$openbasedir=ini_get("open_basedir");
$phparray=phpinfo_array();
$suhosin=empty($phparray['suhosin']["suhosin.session.encrypt"]["local"])?'':$phparray['suhosin']["suhosin.session.encrypt"]["local"];

print '<b>'.$langs->trans("SessionSaveHandler").'</b>: '.$savehandler.'<br>';
print '<b>'.$langs->trans("SessionSavePath").'</b>: '.$savepath.'<br>';
if ($openbasedir) print '<b>'.$langs->trans("OpenBaseDir").'</b>: '.$openbasedir.'<br>';
if ($suhosin) print '<b>'.$langs->trans("SuhosinSessionEncrypt").'</b>: '.$suhosin.'<br>';
print '<br>';

if ($action == 'purge')
{
	$formquestion=array();
	print $form->formconfirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('PurgeSessions'), $langs->trans('ConfirmPurgeSessions'), 'confirm_purge', $formquestion, 'no', 2);
}
elseif ($action == 'lock')
{
	$formquestion=array();
	print $form->formconfirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('LockNewSessions'), $langs->trans('ConfirmLockNewSessions', $user->login), 'confirm_lock', $formquestion, 'no', 1);
}

if ($savehandler == 'files')
{
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "login", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("SessionId", $_SERVER["PHP_SELF"], "id", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "datec", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("DateModification", $_SERVER["PHP_SELF"], "datem", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("Age", $_SERVER["PHP_SELF"], "age", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("Raw", $_SERVER["PHP_SELF"], "raw", "", "", 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	foreach ($listofsessions as $key => $sessionentry)
	{
		print '<tr class="oddeven">';

		// Login
		print '<td>'.$sessionentry['login'].'</td>';

		// ID
		print '<td class="nowrap left">';
		if ("$key" == session_id()) print $form->textwithpicto($key, $langs->trans("YourSession"));
		else print $key;
		print '</td>';

		// Date creation
		print '<td class="nowrap left">'.dol_print_date($sessionentry['creation'], '%Y-%m-%d %H:%M:%S').'</td>';

		// Date modification
		print '<td class="nowrap left">'.dol_print_date($sessionentry['modification'], '%Y-%m-%d %H:%M:%S').'</td>';

		// Age
		print '<td>'.$sessionentry['age'].'</td>';

		// Raw
		print '<td>'.dol_trunc($sessionentry['raw'], 40, 'middle').'</td>';

		print '<td>&nbsp;</td>';

		print "</tr>\n";
		$i++;
	}

	if (count($listofsessions) == 0)
	{
		print '<tr '.$bc[false].'><td colspan="6">'.$langs->trans("NoSessionFound", $savepath, $openbasedir).'</td></tr>';
	}
	print "</table>";
}
else
{
	print $langs->trans("NoSessionListWithThisHandler");
}

/*
 * Buttons
 */

print '<div class="tabsAction">';


if (empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=lock">'.$langs->trans("LockNewSessions").'</a>';
}
else
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=confirm_unlock">'.$langs->trans("UnlockNewSessions").'</a>';
}

if ($savehandler == 'files')
{
	if (count($listofsessions))
	{
	    print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=purge">'.$langs->trans("PurgeSessions").'</a>';
	}
}

print '</div>';

print '<br>';

// End of page
llxFooter();
$db->close();
