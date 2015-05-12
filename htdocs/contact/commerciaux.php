<?php
/* Copyright (C) 2005 		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010 		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 	Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/societe/commerciaux.php
 *  \ingroup    societe
 *  \brief      Page of links to sales representatives
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("banks");

// Security check
$socpeople_id = GETPOST('id', 'int');
$commid = GETPOST('commid', 'int');
$delcommid = GETPOST('delcommid', 'int');

if ($user->contact_id) $socpeople_id=$user->contact_id;
$result = restrictedArea($user, 'contact','','');

$hookmanager->initHooks(array('salesrepresentativescard','globalcard'));

/*
 *	Actions
 */

if($socpeople_id && $commid)
{
	$action = 'add';

	if ($user->rights->societe->contact->creer)
	{

		$contact = new Contact($db);
		$contact->fetch($socpeople_id);


		$parameters=array('id'=>$commid);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$contact,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $contact->add_commercial($user, $commid);

		header("Location: commerciaux.php?id=".$contact->id);
		exit;
	}
	else
	{
		header("Location: commerciaux.php?id=".$socpeople_id);
		exit;
	}
}

if($socpeople_id && $delcommid)
{
	$action = 'delete';

	if ($user->rights->societe->contact->creer)
	{
		$contact = new Contact($db);
		$contact->fetch($socpeople_id);

		$parameters=array('id'=>$delcommid);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$contact,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $contact->del_commercial($user, $delcommid);

		header("Location: commerciaux.php?id=".$contact->id);
		exit;
	}
	else
	{
		header("Location: commerciaux.php?id=".$socpeople_id);
		exit;
	}
}


/*
 *	View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("Contact"),$help_url);

$form = new Form($db);

if ($socpeople_id)
{
	$contact = new Contact($db);
	$result=$contact->fetch($socpeople_id);

	$action='view';

	$head=contact_prepare_head2($contact);

	dol_fiche_head($head, 'salesrepresentative', $langs->trans("Contact"),0,'contact');

	/*
	 * Fiche contact en mode visu
	 */

	print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans('Lastname').' / '.$langs->trans("Label").'</td>';
    print '<td colspan="3">';
    print $form->showrefnav($contact,'id','',($user->contact_id?0:1),'rowid','lastname');
    print '</td></tr>';

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($contact->address)."</td></tr>";

	print '<tr><td>'.$langs->trans('Zip').'</td><td width="20%">'.$contact->zip."</td>";
	print '<td>'.$langs->trans('Town').'</td><td>'.$contact->town."</td></tr>";

	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$contact->country.'</td>';

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($contact->phone_pro,$contact->country_code,0,$contact->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($contact->fax,$contact->country_code,0,$contact->id,'AC_FAX').'</td></tr>';

	// Liste les commerciaux
	print '<tr><td valign="top">'.$langs->trans("SalesRepresentatives").'</td>';
	print '<td colspan="3">';

	
	$TSaleRepresentative = $contact->getSalesRepresentatives($user);
	
	dol_syslog('contact/commerciaux.php::list salesman nb result = '.count($TSaleRepresentative),LOG_DEBUG);
	if (count($TSaleRepresentative) > 0)
	{
		foreach ($TSaleRepresentative as $obj)
		{
 			$parameters=array('id'=>$contact->id);
        	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$obj,$action);    // Note that $action and $object may have been modified by hook
      		if (empty($reshook)) {

				null; // actions in normal case
      		}

			print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
			print img_object($langs->trans("ShowUser"),"user").' ';
			print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
			print '</a>&nbsp;';
			if ($user->rights->societe->contact->creer)
			{
			    print '<a href="commerciaux.php?id='.$contact->id.'&amp;delcommid='.$obj->rowid.'">';
			    print img_delete();
			    print '</a>';
			}
			print '<br />';
		}
	}
	else
	{
		print $langs->trans("NoSalesRepresentativeAffected");
	}

	print "</td></tr>";

	print '</table>';
	print "</div>\n";

	if ($user->rights->societe->contact->creer && $user->rights->societe->contact->lire)
	{
		/*
		 * Liste
		 *
		 */

		$langs->load("users");
		$title=$langs->trans("ListOfUsers");

		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.login";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND u.statut<>0 ";
		$sql.= " ORDER BY u.lastname ASC ";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print_titre($title);

			// Lignes des titres
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td>'.$langs->trans("Login").'</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			$var=True;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),"user").' ';
				print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
				print '</a>';
				print '</td><td>'.$obj->login.'</td>';
				print '<td><a href="commerciaux.php?id='.$socpeople_id.'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';

				print '</tr>'."\n";
				$i++;
			}

			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}

}


$db->close();

llxFooter();
