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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("banks");

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$hookmanager->initHooks(array('salesrepresentativescard','globalcard'));

/*
 *	Actions
 */

if (! empty($socid) && $_GET["commid"])
{
	$action = 'add';

	if ($user->rights->societe->creer)
	{
		$object = new Societe($db);
		$object->fetch($socid);

		$parameters=array('id'=>$_GET["commid"]);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $object->add_commercial($user, $_GET["commid"]);

		header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$object->id);
		exit;
	}
	else
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
		exit;
	}
}

if (! empty($socid) && $_GET["delcommid"])
{
	$action = 'delete';

	if ($user->rights->societe->creer)
	{
		$object = new Societe($db);
		$object->fetch($socid);

		$parameters=array('id'=>$_GET["delcommid"]);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $object->del_commercial($user, $_GET["delcommid"]);

		header("Location: commerciaux.php?socid=".$object->id);
		exit;
	}
	else
	{
		header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
		exit;
	}
}


/*
 *	View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$form = new Form($db);

if (! empty($socid))
{
	$object = new Societe($db);
	$result=$object->fetch($socid);

	$action='view';

	$head=societe_prepare_head2($object);

	dol_fiche_head($head, 'salesrepresentative', $langs->trans("ThirdParty"),0,'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';
	
    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');
        
	print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr>';
    print '<td class="titlefield">'.$langs->trans('CustomerCode').'</td><td'.(empty($conf->global->SOCIETE_USEPREFIX)?' colspan="3"':'').'>';
    print $object->code_client;
    if ($object->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
    print '</td>';
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
       print '<td>'.$langs->trans('Prefix').'</td><td>'.$object->prefix_comm.'</td>';
    }
    print '</td>';
    print '</tr>';

	// Liste les commerciaux
	print '<tr><td>'.$langs->trans("SalesRepresentatives").'</td>';
	print '<td colspan="3">';

	$sql = "SELECT DISTINCT u.rowid, u.login, u.fk_soc, u.lastname, u.firstname, u.statut, u.entity, u.photo";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
	{
	    $sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
	}
	$sql .= " WHERE sc.fk_soc =".$object->id;
	$sql .= " AND sc.fk_user = u.rowid";
	if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
	{
		$sql.= " AND ((ug.fk_user = sc.fk_user";
		$sql.= " AND ug.entity = ".$conf->entity.")";
		$sql.= " OR u.admin = 1)";
	}
	else
		$sql.= " AND u.entity IN (0,".$conf->entity.")";

	$sql .= " ORDER BY u.lastname ASC ";

	dol_syslog('societe/commerciaux.php::list salesman sql = '.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		$tmpuser = new User($db);
		
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

 			$parameters=array('socid'=>$object->id);
        	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$obj,$action);    // Note that $action and $object may have been modified by hook
      		if (empty($reshook)) {

				null; // actions in normal case
      		}

      		$tmpuser->id = $obj->rowid;
      		$tmpuser->firstname = $obj->firstname;
      		$tmpuser->lastname = $obj->lastname;
      		$tmpuser->statut = $obj->statut;
      		$tmpuser->login = $obj->login;
      		$tmpuser->entity = $obj->entity;
      		$tmpuser->societe_id = $obj->fk_soc;
      		$tmpuser->photo = $obj->photo;
      		print $tmpuser->getNomUrl(-1);
      		
			/*print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
			print img_object($langs->trans("ShowUser"),"user").' ';
			print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
			print '</a>';*/
			print '&nbsp;';
			if ($user->rights->societe->creer)
			{
			    print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;delcommid='.$obj->rowid.'">';
			    print img_delete();
			    print '</a>';
			}
			print '<br>';
			$i++;
		}

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	if($i == 0) { print $langs->trans("NoSalesRepresentativeAffected"); }

	print "</td></tr>";

	print '</table>';
	print "</div>\n";
	
	dol_fiche_end();


	if ($user->rights->societe->creer && $user->rights->societe->client->voir)
	{
		/*
		 * Liste
		 *
		 */

		$langs->load("users");
		$title=$langs->trans("ListOfUsers");

		$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.login, u.email, u.statut, u.fk_soc, u.photo";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
		{
			$sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
			$sql.= " WHERE ((ug.fk_user = u.rowid";
			$sql.= " AND ug.entity = ".$conf->entity.")";
			$sql.= " OR u.admin = 1)";
		}
		else
			$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND u.statut<>0 ";
		$sql.= " ORDER BY u.lastname ASC ";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print load_fiche_titre($title);

			// Lignes des titres
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td>'.$langs->trans("Login").'</td>';
			print '<td>'.$langs->trans("Status").'</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			$var=True;
			$tmpuser=new User($db);
				
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				$tmpuser->id=$obj->rowid;
				$tmpuser->firstname=$obj->firstname;
				$tmpuser->lastname=$obj->lastname;
				$tmpuser->statut=$obj->statut;
				$tmpuser->login=$obj->login;
				$tmpuser->email=$obj->email;
				$tmpuser->societe_id=$obj->fk_soc;
				$tmpuser->photo=$obj->photo;
				print $tmpuser->getNomUrl(-1);
				print '</td>';
				print '<td>'.$obj->login.'</td>';
				print '<td>'.$tmpuser->getLibStatut(2).'</td>';
				print '<td><a href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';

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

llxFooter();
$db->close();
