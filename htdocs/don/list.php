<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/don/list.php
 *	\ingroup    donations
 *	\brief      List of donations
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("companies");
$langs->load("donations");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.datedon";

$statut=isset($_GET["statut"])?$_GET["statut"]:"-1";
$search_all=GETPOST('sall','alpha');
$search_ref=GETPOST('search_ref','alpha');
$search_company=GETPOST('search_company','alpha');
$search_name=GETPOST('search_name','alpha');
$search_amount = GETPOST('search_amount','alpha');
$optioncss = GETPOST('optioncss','alpha');

if (!$user->rights->don->lire) accessforbidden();

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_all="";
    $search_ref="";
	$search_company="";
	$search_name="";
	$search_amount="";
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('orderlist'));


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'd.rowid'=>'Id',
    'd.ref'=>'Ref',
    'd.lastname'=>'Lastname',
    'd.firstname'=>'Firstname',
);
        
/*
 * View
 */

$form=new Form($db);
if (! empty($conf->projet->enabled)) $projectstatic=new Project($db);

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones');

$donationstatic=new Don($db);

// Genere requete de liste des dons
$sql = "SELECT d.rowid, d.datedon, d.firstname, d.lastname, d.societe,";
$sql.= " d.amount, d.fk_statut as statut, ";
$sql.= " p.rowid as pid, p.ref, p.title, p.public";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."projet AS p";
$sql.= " ON p.rowid = d.fk_projet WHERE 1 = 1";
if ($statut >= 0)
{
	$sql .= " AND d.fk_statut = ".$statut;
}
if (trim($search_ref) != '')
{
    $sql.= ' AND d.rowid LIKE \'%'.$db->escape(trim($search_ref)) . '%\'';
}
if (trim($search_all) != '')
{
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if (trim($search_company) != '')
{
    $sql .= natural_search('d.societe', $search_company);
}
if (trim($search_name) != '')
{
    $sql .= natural_search(array('d.lastname', 'd.firstname'), $search_name);
}
if ($search_amount) $sql.= natural_search(array('d.amount'), price2num(trim($search_amount)), 1);

$sql.= $db->order($sortfield,$sortorder);
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = '&statut='.$statut;
    //if ($page > 0) $param.= '&page='.$page;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;

	if ($statut >= 0)
	{
	    $donationstatic->statut=$statut;
	    $label=$donationstatic->getLibStatut(0);
		print_barre_liste($langs->trans("Donations"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num,$nbtotalofrecords);
	}
	else
	{
		print_barre_liste($langs->trans("Donations"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num,$nbtotalofrecords);
	}


    print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

    if ($search_all)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall);
    }
    
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid","", $param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"d.societe","", $param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Name"),$_SERVER["PHP_SELF"],"d.lastname","", $param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"d.datedon","", $param,'align="center"',$sortfield,$sortorder);
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print_liste_field_titre($langs->trans("Project"),$_SERVER["PHP_SELF"],"fk_projet","", $param,"",$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"d.amount","", $param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"d.fk_statut","", $param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

    // Filters lines
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_company" value="'.$search_company.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_name" value="'.$search_name.'">';
    print '</td>';
    print '<td class="liste_titre" align="left">';
    print '&nbsp;';
    print '</td>';
    if (! empty($conf->projet->enabled))
    {
        print '<td class="liste_titre" align="right">';
        print '&nbsp;';
        print '</td>';
    }
    print '<td class="liste_titre" align="right"><input name="search_amount" class="flat" type="text" size="8" value="'.$search_amount.'"></td>';
    print '<td class="liste_titre" align="right"></td>';
    print '<td class="liste_titre" align="right">';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';
	print "</tr>\n";

	$var=True;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		$donationstatic->id=$objp->rowid;
		$donationstatic->ref=$objp->rowid;
		$donationstatic->lastname=$objp->lastname;
		$donationstatic->firstname=$objp->firstname;
		print "<td>".$donationstatic->getNomUrl(1)."</td>\n";
        print "<td>".$objp->societe."</td>\n";
		print "<td>".$donationstatic->getFullName($langs)."</td>\n";
		print '<td align="center">'.dol_print_date($db->jdate($objp->datedon),'day').'</td>';
		if (! empty($conf->projet->enabled))
		{
			print "<td>";
			if ($objp->pid)
			{
				$projectstatic->id=$objp->pid;
				$projectstatic->ref=$objp->ref;
				$projectstatic->id=$objp->pid;
				$projectstatic->public=$objp->public;
				$projectstatic->title=$objp->title;
				print $projectstatic->getNomUrl(1);
			}
			else print '&nbsp;';
			print "</td>\n";
		}
		print '<td align="right">'.price($objp->amount).'</td>';
		print '<td align="right">'.$donationstatic->LibStatut($objp->statut,5).'</td>';
        print '<td></td>';
		print "</tr>";
		$i++;
	}
	print "</table>";
	print '</div>';
    print "</form>\n";
    $db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
