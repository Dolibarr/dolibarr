<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/admin/tools/listessions.php
 *      \ingroup    core
 *      \brief      List of PHP sessions
 *      \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/admin.lib.php');

if (! $user->admin)
  accessforbidden();

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$langs->load("companies");
$langs->load("users");
$langs->load("other");

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="dateevent";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Actions
 */

// Purge sessions
if ($_REQUEST['action'] == 'confirm_purge' && $_REQUEST['confirm'] == 'yes' && $user->admin)
{
	$res=purgeSessions(session_id());
}

// Lock new sessions
if ($_REQUEST['action'] == 'confirm_lock' && $_REQUEST['confirm'] == 'yes' && $user->admin)
{
	if (dolibarr_set_const($db, 'MAIN_ONLY_LOGIN_ALLOWED', $user->login, 'text',1,'Logon is restricted to a particular user', 0) < 0)
	{
		dol_print_error($db);
	}
}

// Unlock new sessions
if ($_REQUEST['action'] == 'confirm_unlock' && $user->admin)
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

print_barre_liste($langs->trans("Sessions"), $page, $_SERVER["PHP_SELF"],"",$sortfield,$sortorder,'',$num,0,'setup');

$savehandler=ini_get("session.save_handler");
$savepath=ini_get("session.save_path");
$openbasedir=ini_get("open_basedir");

print '<b>'.$langs->trans("SessionSaveHandler").'</b>: '.$savehandler.'<br>';
print '<b>'.$langs->trans("SessionSavePath").'</b>: '.$savepath.'<br>';
if ($openbasedir) print '<b>'.$langs->trans("OpenBaseDir").'</b>: '.$openbasedir.'<br>';
print '<br>';

if ($_GET["action"] == 'purge')
{
	$formquestion=array();
	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('PurgeSessions'), $langs->trans('ConfirmPurgeSessions'),'confirm_purge',$formquestion,'no',2);
	if ($ret == 'html') print '<br>';
}
if ($_GET["action"] == 'lock')
{
	$formquestion=array();
	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('LockNewSessions'), $langs->trans('ConfirmLockNewSessions',$user->login),'confirm_lock',$formquestion,'no',1);
	if ($ret == 'html') print '<br>';
}

if ($savehandler == 'files')
{
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Login"),$_SERVER["PHP_SELF"],"login","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SessionId"),$_SERVER["PHP_SELF"],"id","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateModification"),$_SERVER["PHP_SELF"],"datem","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Age"),$_SERVER["PHP_SELF"],"age","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Raw"),$_SERVER["PHP_SELF"],"raw","","",'align="left"',$sortfield,$sortorder);
	print_liste_field_titre('','','');
	print "</tr>\n";


	// Lignes des champs de filtre
	/*
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<tr class="liste_titre">';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_code" value="'.$_GET["search_code"].'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_ip" value="'.$_GET["search_ip"].'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_user" value="'.$_GET["search_user"].'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_desc" value="'.$_GET["search_desc"].'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
	print '</td>';

	print "</tr>\n";
	print '</form>';
	*/

	$var=True;

	foreach ($listofsessions as $key => $sessionentry)
	{
		$var=!$var;

		print "<tr $bc[$var]>";

		// Login
		print '<td>'.$sessionentry['login'].'</td>';
		
		// ID
		print '<td align="left" nowrap="nowrap">';
		if ("$key" == session_id()) print $form->textwithpicto($key,$langs->trans("YourSession"));
		else print $key;
		print '</td>';

		// Date creation
		print '<td align="left" nowrap="nowrap">'.dol_print_date($sessionentry['creation'],'%Y-%m-%d %H:%M:%S').'</td>';

		// Date modification
		print '<td align="left" nowrap="nowrap">'.dol_print_date($sessionentry['modification'],'%Y-%m-%d %H:%M:%S').'</td>';

		// Age
		print '<td>'.$sessionentry['age'].'</td>';

		// Raw
		print '<td>'.dol_trunc($sessionentry['raw'],40,'middle').'</td>';

		print '<td>&nbsp;</td>';

		print "</tr>\n";
		$i++;
	}

	if (sizeof($listofsessions) == 0)
	{
		print '<tr><td colspan="6">'.$langs->trans("NoSessionFound",$savepath,$openbasedir).'</td></tr>';
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
	if (sizeof($listofsessions))
	{
	    print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=purge">'.$langs->trans("PurgeSessions").'</a>';
	}
}

print '</div>';

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
