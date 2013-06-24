<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/user/index.php
 * 		\ingroup	core
 *      \brief      Page of users
 */

require '../main.inc.php';
if (! empty($conf->multicompany->enabled))
	dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');


if (! $user->rights->user->user->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$sall=GETPOST('sall','alpha');
$search_user=GETPOST('search_user','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="u.login";
if (! $sortorder) $sortorder="ASC";

$userstatic=new User($db);
$companystatic = new Societe($db);


/*
 * View
 */

llxHeader('',$langs->trans("ListOfUsers"));

print_fiche_titre($langs->trans("ListOfUsers"), '<form action="'.DOL_URL_ROOT.'/user/hierarchy.php" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>');

$sql = "SELECT u.rowid, u.lastname, u.firstname, u.admin, u.fk_societe, u.login,";
$sql.= " u.datec,";
$sql.= " u.tms as datem,";
$sql.= " u.datelastlogin,";
$sql.= " u.ldap_sid, u.statut, u.entity,";
$sql.= " s.nom, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_societe = s.rowid";
if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && (! empty($conf->multicompany->transverse_mode) || (! empty($user->admin) && empty($user->entity))))
{
	$sql.= " WHERE u.entity IS NOT NULL";
}
else
{
	$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
}
if (! empty($socid)) $sql.= " AND u.fk_societe = ".$socid;
if (! empty($search_user))
{
    $sql.= " AND (u.login LIKE '%".$db->escape($search_user)."%' OR u.lastname LIKE '%".$db->escape($search_user)."%' OR u.firstname LIKE '%".$db->escape($search_user)."%')";
}
if ($sall) $sql.= " AND (u.login LIKE '%".$db->escape($sall)."%' OR u.lastname LIKE '%".$db->escape($sall)."%' OR u.firstname LIKE '%".$db->escape($sall)."%' OR u.email LIKE '%".$db->escape($sall)."%' OR u.note LIKE '%".$db->escape($sall)."%')";
$sql.=$db->order($sortfield,$sortorder);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $param="search_user=$search_user&amp;sall=$sall";
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Login"),"index.php","u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LastName"),"index.php","u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("FirstName"),"index.php","u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),"index.php","u.fk_societe",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateCreation"),"index.php","u.datec",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LastConnexion"),"index.php","u.datelastlogin",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),"index.php","u.statut",$param,"",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";
    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

        print "<tr ".$bc[$var].">";
        print '<td><a href="fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a>';
        if (! empty($conf->multicompany->enabled) && $obj->admin && ! $obj->entity)
        {
          	print img_picto($langs->trans("SuperAdministrator"),'redstar');
        }
        else if ($obj->admin)
        {
        	print img_picto($langs->trans("Administrator"),'star');
        }
        print '</td>';
        print '<td>'.ucfirst($obj->lastname).'</td>';
        print '<td>'.ucfirst($obj->firstname).'</td>';
        print "<td>";
        if ($obj->fk_societe)
        {
            $companystatic->id=$obj->fk_societe;
            $companystatic->nom=$obj->nom;
            $companystatic->canvas=$obj->canvas;
            print $companystatic->getNomUrl(1);
        }
        // Multicompany enabled
        else if (! empty($conf->multicompany->enabled))
        {
        	if (! $obj->entity)
        	{
        		print $langs->trans("AllEntities");
        	}
        	else
        	{
        		// $mc is defined in conf.class.php if multicompany enabled.
        		if (is_object($mc))
        		{
        			$mc->getInfo($obj->entity);
        			print $mc->label;
        		}
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

        // Date creation
        print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datec),"dayhour").'</td>';

        // Date last login
        print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datelastlogin),"dayhour").'</td>';

		// Statut
		$userstatic->statut=$obj->statut;
        print '<td width="100" align="right">'.$userstatic->getLibStatut(5).'</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

llxFooter();

$db->close();
?>
