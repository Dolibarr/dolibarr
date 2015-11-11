<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *      \file       htdocs/hrm/employee/list.php
 * 		\ingroup	core
 *      \brief      Page of users
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/employee.class.php';

if (! $user->rights->hrm->employee->read)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("hrm");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$sall=GETPOST('sall','alpha');
$search_user=GETPOST('search_user','alpha');
$search_login=GETPOST('search_login','alpha');
$search_lastname=GETPOST('search_lastname','alpha');
$search_firstname=GETPOST('search_firstname','alpha');
$search_statut=GETPOST('search_statut','alpha');
$search_thirdparty=GETPOST('search_thirdparty','alpha');
$optioncss = GETPOST('optioncss','alpha');

if ($search_statut == '') $search_statut='1';

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

$employeestatic = new Employee($db);
$companystatic = new Societe($db);
$form = new Form($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter"))
{
	$search_user="";
	$search_login="";
	$search_lastname="";
	$search_firstname="";
	$search_statut="";
	$search_thirdparty="";
}


/*
 * View
 */

llxHeader('',$langs->trans("ListOfEmployees"));

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/hrm/employee/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

print load_fiche_titre($langs->trans("ListOfEmployees"), $buttonviewhierarchy);

$sql = "SELECT u.rowid, u.lastname, u.firstname, u.email, u.gender,";
$sql.= " u.datec,";
$sql.= " u.tms as datem,";
$sql.= " u.ldap_sid, u.statut, u.entity,";
$sql.= " u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2,";
$sql.= " s.nom as name, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u2 ON u.fk_user = u2.rowid";
$sql.= " WHERE u.employee >= '1'";
$sql.= " AND u.entity IN (".getEntity('user',1).")";

if ($socid > 0) $sql.= " AND u.fk_soc = ".$socid;
if ($search_user != '') $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
if ($search_thirdparty != '') $sql.=natural_search(array('s.nom'), $search_thirdparty);
if ($search_login != '') $sql.= natural_search("u.login", $search_login);
if ($search_lastname != '') $sql.= natural_search("u.lastname", $search_lastname);
if ($search_firstname != '') $sql.= natural_search("u.firstname", $search_firstname);
if ($search_statut != '' && $search_statut >= 0) $sql.= " AND (u.statut=".$search_statut.")";
if ($sall) $sql.= natural_search(array('u.login', 'u.lastname', 'u.firstname', 'u.email', 'u.note'), $sall);
$sql.=$db->order($sortfield,$sortorder);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';

    $param="search_user=".$search_user."&sall=".$sall;
    $param.="&search_statut=".$search_statut;
    if ($optioncss != '') $param.='&optioncss='.$optioncss;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Login"),$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LastName"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("FirstName"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))
    {
	    print_liste_field_titre($langs->trans("Entity"),$_SERVER['PHP_SELF'],"u.entity",$param,"","",$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("HierarchicalResponsible"),$_SERVER['PHP_SELF'],"u2.login",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER['PHP_SELF'],"u.statut",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    // Search bar
    if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode)) $colspan++;
    print '<tr class="liste_titre">';
    print '<td><input type="text" name="search_login" size="6" value="'.$search_login.'"></td>';
    print '<td><input type="text" name="search_lastname" size="6" value="'.$search_lastname.'"></td>';
    print '<td><input type="text" name="search_firstname" size="6" value="'.$search_firstname.'"></td>';
    print '<td>&nbsp;</td>';

	// Status
    print '<td align="right">';
    print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
    print '</td>';

	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

    print "</tr>\n";

    $employee2=new Employee($db);

    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

		$employeestatic->id=$obj->rowid;
		$employeestatic->ref=$obj->label;
		$employeestatic->login=$obj->login;
		$employeestatic->statut=$obj->statut;
	    $employeestatic->email=$obj->email;
	    $employeestatic->gender=$obj->gender;
	    $employeestatic->societe_id=$obj->fk_soc;
	    $employeestatic->firstname=$obj->firstname;
		$employeestatic->lastname=$obj->lastname;

		$li=$employeestatic->getNomUrl(1,'',0,0,24,1);

        print "<tr ".$bc[$var].">";
        print '<td>';
        print $li;
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

        // Resp
        print '<td class="nowrap" align="center">';
        if ($obj->login2)
        {
	        $employee2->login=$obj->login2;
	        //$employee2->lastname=$obj->lastname2;
	        //$employee2->firstname=$obj->firstname2;
	        $employee2->lastname=$employee2->login;
	        $employee2->firstname='';
	        print $employee2->getNomUrl(1);
        }
        print '</td>';

        // Statut
		print '<td align="center">'.$employeestatic->getLibStatut(5).'</td>';
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
