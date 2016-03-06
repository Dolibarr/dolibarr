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

		$soc = new Societe($db);
		$soc->id = $socid;
		$soc->fetch($socid);


		$parameters=array('id'=>$_GET["commid"]);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$soc,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $soc->add_commercial($user, $_GET["commid"]);

		header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$soc->id);
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
		$soc = new Societe($db);
		$soc->id = $_GET["socid"];
		$soc->fetch($_GET["socid"]);

		$parameters=array('id'=>$_GET["delcommid"]);
		$reshook=$hookmanager->executeHooks('doActions',$parameters,$soc,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) $soc->del_commercial($user, $_GET["delcommid"]);

		header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$soc->id);
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
	$soc = new Societe($db);
	$soc->id = $socid;
	$result=$soc->fetch($socid);

	$action='view';

	$head=societe_prepare_head2($soc);

	dol_fiche_head($head, 'salesrepresentative', $langs->trans("ThirdParty"),0,'company');

	/*
	 * Fiche societe en mode visu
	 */

	print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
    print '<td colspan="3">';
    print $form->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
    print '</td></tr>';

	print '<tr>';
    print '<td>'.$langs->trans('CustomerCode').'</td><td'.(empty($conf->global->SOCIETE_USEPREFIX)?' colspan="3"':'').'>';
    print $soc->code_client;
    if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
    print '</td>';
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
       print '<td>'.$langs->trans('Prefix').'</td><td>'.$soc->prefix_comm.'</td>';
    }
    print '</td>';
    print '</tr>';

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->address)."</td></tr>";

	print '<tr><td>'.$langs->trans('Zip').'</td><td width="20%">'.$soc->zip."</td>";
	print '<td>'.$langs->trans('Town').'</td><td>'.$soc->town."</td></tr>";

	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->country.'</td>';

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->phone,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';

	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
	print '</td></tr>';

	// Liste les commerciaux
	print '<tr><td valign="top">'.$langs->trans("SalesRepresentatives").'</td>';
	print '<td colspan="3">';

	$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode))
		$sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
	$sql .= " WHERE sc.fk_soc = ".$soc->id;
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

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

 			$parameters=array('socid'=>$soc->id);
        	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$obj,$action);    // Note that $action and $object may have been modified by hook
      		if (empty($reshook)) {

				null; // actions in normal case
      		}

			print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
			print img_object($langs->trans("ShowUser"),"user").' ';
			print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
			print '</a>&nbsp;';
			if ($user->rights->societe->creer)
			{
			    print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'&amp;delcommid='.$obj->rowid.'">';
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


	if ($user->rights->societe->creer && $user->rights->societe->client->voir)
	{
		/*
		 * Liste
		 *
		 */

		$langs->load("users");
		$title=$langs->trans("ListOfUsers");

		$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.login";
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
				print '<td><a href="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';

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
