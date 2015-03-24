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
$search_statut=GETPOST('search_statut','alpha');

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
$form = new Form($db);


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
$sql.= " u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2,";
$sql.= " s.nom as name, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_societe = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u2 ON u.fk_user = u2.rowid";
if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && (! empty($conf->multicompany->transverse_mode) || (! empty($user->admin) && empty($user->entity))))
{
	$sql.= " WHERE u.entity IS NOT NULL";
}
else
{
	$sql.= " WHERE u.entity IN (".getEntity('user',1).")";
}
if (! empty($socid)) $sql.= " AND u.fk_societe = ".$socid;
if (! empty($search_user))
{
    $sql.= " AND (u.login LIKE '%".$db->escape($search_user)."%' OR u.lastname LIKE '%".$db->escape($search_user)."%' OR u.firstname LIKE '%".$db->escape($search_user)."%')";
}
if ($search_statut != '' && $search_statut >= 0)
{
	$sql.= " AND (u.statut=".$search_statut.")";
}
if ($sall) $sql.= " AND (u.login LIKE '%".$db->escape($sall)."%' OR u.lastname LIKE '%".$db->escape($sall)."%' OR u.firstname LIKE '%".$db->escape($sall)."%' OR u.email LIKE '%".$db->escape($sall)."%' OR u.note LIKE '%".$db->escape($sall)."%')";
$sql.=$db->order($sortfield,$sortorder);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

    $param="search_user=".$search_user."&sall=".$sall;
    $param.="&search_statut=".$search_statut;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Login"),$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LastName"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("FirstName"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER['PHP_SELF'],"u.fk_societe",$param,"","",$sortfield,$sortorder);
    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))
    {
	    print_liste_field_titre($langs->trans("Entity"),$_SERVER['PHP_SELF'],"u.entity",$param,"","",$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("DateCreation"),$_SERVER['PHP_SELF'],"u.datec",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LastConnexion"),$_SERVER['PHP_SELF'],"u.datelastlogin",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("HierarchicalResponsible"),$_SERVER['PHP_SELF'],"u2.login",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER['PHP_SELF'],"u.statut",$param,"",'align="center"',$sortfield,$sortorder);
    print '<td width="1%">&nbsp;</td>';
    print "</tr>\n";

    // SearchBar
    $colspan=7;
    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode)) $colspan++;
    print '<tr class="liste_titre">';
    print '<td colspan="'.$colspan.'">&nbsp;</td>';

	// Status
    print '<td>';
    print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
    print '</td>';

    print '<td class="liste_titre" align="right">';
    print '<input class="liste_titre" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '</td>';

    print "</tr>\n";

    $user2=new User($db);

    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

        print "<tr ".$bc[$var].">";
        print '<td><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a>';
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
            $companystatic->name=$obj->name;
            $companystatic->canvas=$obj->canvas;
            print $companystatic->getNomUrl(1);
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

        // Multicompany enabled
        if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))
        {
        	print '<td>';
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
        	print '</td>';
        }

        // Date creation
        print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datec),"dayhour").'</td>';

        // Date last login
        print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datelastlogin),"dayhour").'</td>';

        // Resp
        print '<td class="nowrap" align="center">';
        if ($obj->login2)
        {
	        $user2->login=$obj->login2;
	        //$user2->lastname=$obj->lastname2;
	        //$user2->firstname=$obj->firstname2;
	        $user2->lastname=$user2->login;
	        $user2->firstname='';
	        print $user2->getNomUrl(1);
        }
        print '</td>';

        // Statut
		$userstatic->statut=$obj->statut;
		print '<td width="100" align="center">'.$userstatic->getLibStatut(5).'</td>';
        print '<td>&nbsp;</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    print "</form>\n";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

llxFooter();

$db->close();
