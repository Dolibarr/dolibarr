<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/user/home.php
 *	\brief      Home page of users and groups management
 */

require '../main.inc.php';

if (! $user->rights->user->user->lire && ! $user->admin)
{
	// Redirection vers la page de l'utilisateur
	header("Location: fiche.php?id=".$user->id);
	exit;
}

$langs->load("users");

$canreadperms=true;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$canreadperms=($user->admin || $user->rights->user->group_advance->read);
}

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;

$companystatic = new Societe($db);
$fuserstatic = new User($db);


/*
 * View
 */

llxHeader();


print_fiche_titre($langs->trans("MenuUsersAndGroups"));


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


// Search User
$var=false;
print '<form method="post" action="'.DOL_URL_ROOT.'/user/index.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAUser").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input class="flat" type="text" name="search_user" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td class="nowrap">'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td></tr>';
print "</table><br>\n";
print '</form>';

// Search Group
if ($canreadperms)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/user/group/index.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAGroup").'</td></tr>';
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Ref").':</td><td><input class="flat" type="text" name="search_group" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap">'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td></tr>';
	print "</table><br>\n";
	print '</form>';
}


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last created users
 */
$max=10;

$sql = "SELECT u.rowid, u.lastname, u.firstname, u.admin, u.login, u.fk_societe, u.datec, u.statut, u.entity, u.ldap_sid,";
$sql.= " s.nom as name, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_societe = s.rowid";
if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
{
	$sql.= " WHERE u.entity IS NOT NULL";
}
else
{
	$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
}
if (!empty($socid)) $sql.= " AND u.fk_societe = ".$socid;
$sql.= $db->order("u.datec","DESC");
$sql.= $db->plimit($max);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("LastUsersCreated",min($num,$max)).'</td></tr>';
	$var = true;
	$i = 0;

	while ($i < $num && $i < $max)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr ".$bc[$var].">";
		print '<td><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.dolGetFirstLastname($obj->firstname,$obj->lastname).'</a>';
		if (! empty($conf->multicompany->enabled) && $obj->admin && ! $obj->entity)
		{
			print img_picto($langs->trans("SuperAdministrator"),'redstar');
		}
		else if ($obj->admin)
		{
			print img_picto($langs->trans("Administrator"),'star');
		}
		print "</td>";
		print '<td align="left">'.$obj->login.'</td>';
		print "<td>";
		if ($obj->fk_societe)
		{
			$companystatic->id=$obj->fk_societe;
            $companystatic->name=$obj->name;
            $companystatic->canvas=$obj->canvas;
            print $companystatic->getNomUrl(1);
		}
		else if (! empty($conf->multicompany->enabled))
        {
        	if ($obj->admin && ! $obj->entity)
        	{
        		print $langs->trans("AllEntities");
        	}
        	else
        	{
        		$mc->getInfo($obj->entity);
        		print $mc->label;
        	}
        }
		else if ($obj->ldap_sid)
		{
			print $langs->trans("DomainUser");
		}
		else
		{
			print $langs->trans("InternalUser");
		}
		print '</td>';
		print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'dayhour').'</td>';
        print '<td align="right">';
        $fuserstatic->id=$obj->rowid;
        $fuserstatic->statut=$obj->statut;
        print $fuserstatic->getLibStatut(3);
        print '</td>';

		print '</tr>';
		$i++;
	}
	print "</table><br>";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


/*
 * Last groups created
 */
if ($canreadperms)
{
	$max=5;

	$sql = "SELECT g.rowid, g.nom, g.note, g.entity, g.datec";
	$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
	if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
	{
		$sql.= " WHERE g.entity IS NOT NULL";
	}
	else
	{
		$sql.= " WHERE g.entity IN (0,".$conf->entity.")";
	}
	$sql.= $db->order("g.datec","DESC");
	$sql.= $db->plimit($max);

	$resql=$db->query($sql);
	if ($resql)
	{
		$colspan=2;
		if (! empty($conf->multicompany->enabled)) $colspan++;
		$num = $db->num_rows($resql);
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="'.$colspan.'">'.$langs->trans("LastGroupsCreated",($num ? $num : $max)).'</td></tr>';
		$var = true;
		$i = 0;

		while ($i < $num && (! $max || $i < $max))
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr ".$bc[$var].">";
			print '<td><a href="'.DOL_URL_ROOT.'/user/group/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom.'</a>';
			if (! $obj->entity)
			{
				print img_picto($langs->trans("GlobalGroup"),'redstar');
			}
			print "</td>";
			if (! empty($conf->multicompany->enabled))
			{
	        	$mc->getInfo($obj->entity);
	        	print '<td>';
	        	print $mc->label;
	        	print '</td>';
			}
			print '<td class="nowrap" align="right">'.dol_print_date($db->jdate($obj->datec),'dayhour').'</td>';
			print "</tr>";
			$i++;
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

//print '</td></tr></table>';
print '</div></div></div>';

llxFooter();

$db->close();
?>
