<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/compta/dons/liste.php
 *	\ingroup    don
 *	\brief      Page de liste des dons
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/dons/class/don.class.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("companies");
$langs->load("donations");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.datedon";
$limit = $conf->liste_limit;

$statut=isset($_GET["statut"])?$_GET["statut"]:"-1";
$search_ref=GETPOST('search_ref');
$search_company=GETPOST('search_company');
$search_name=GETPOST('search_name');

if (!$user->rights->don->lire) accessforbidden();


/*
 * View
 */

if (! empty($conf->projet->enabled)) $projectstatic=new Project($db);

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones');

$donationstatic=new Don($db);

// Genere requete de liste des dons
$sql = "SELECT d.rowid, d.datedon, d.firstname, d.lastname, d.societe,";
$sql.= " d.amount, d.fk_statut as statut, ";
$sql.= " p.rowid as pid, p.ref, p.title, p.public";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."projet AS p";
$sql.= " ON p.rowid = d.fk_don_projet WHERE 1 = 1";
if ($statut >= 0)
{
	$sql .= " AND d.fk_statut = ".$statut;
}
if (trim($search_ref) != '')
{
    $sql.= ' AND d.rowid LIKE \'%'.$db->escape(trim($search_ref)) . '%\'';
}
if (trim($search_company) != '')
{
    $sql.= ' AND d.societe LIKE \'%'.$db->escape(trim($search_company)) . '%\'';
}
if (trim($search_name) != '')
{
    $sql.= ' AND d.lastname LIKE \'%'.$db->escape(trim($search_name)) . '%\' OR d.firstname LIKE \'%'.$db->escape(trim($search_name)) . '%\'';
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param="&statut=$statut&sortorder=$sortorder&sortfield=$sortfield";

	if ($statut >= 0)
	{
	    $donationstatic->statut=$statut;
	    $label=$donationstatic->getLibStatut(0);
		print_barre_liste($label, $page, $_SERVER["PHP_SELF"], $param, '', '', '', $num);
	}
	else
	{
		print_barre_liste($langs->trans("Donations"), $page, $_SERVER["PHP_SELF"], $param, '', '', '', $num);
	}


    print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print "<table class=\"noborder\" width=\"100%\">";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"d.rowid","&page=$page&statut=$statut","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"d.societe","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Name"),$_SERVER["PHP_SELF"],"d.lastname","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"d.datedon","&page=$page&statut=$statut","",'align="center"',$sortfield,$sortorder);
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print_liste_field_titre($langs->trans("Project"),$_SERVER["PHP_SELF"],"fk_don_projet","&page=$page&statut=$statut","","",$sortfield,$sortorder);
	}
	print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"d.amount","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"d.fk_statut","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
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
    print '<td class="liste_titre" align="right">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print "</td></tr>\n";

	$var=True;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($result);
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

		print "</tr>";
		$i++;
	}
	print "</table>";
    print "</form>\n";
    $db->free($resql);
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter();
?>
