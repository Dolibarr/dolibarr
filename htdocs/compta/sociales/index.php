<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/compta/sociales/index.php
 *		\ingroup    tax
 *		\brief      Page to list all social contributions
 *		\version    $Id: index.php,v 1.66 2011/07/31 22:23:20 eldy Exp $
 */

require('../../main.inc.php');
require(DOL_DOCUMENT_ROOT."/compta/sociales/class/chargesociales.class.php");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="s.date_ech";
if (! $sortorder) $sortorder="DESC";

$year=$_GET["year"];
$filtre=$_GET["filtre"];

if (empty($_REQUEST['typeid']))
{
	$newfiltre=str_replace('filtre=','',$filtre);
	$filterarray=explode('-',$newfiltre);
	foreach($filterarray as $val)
	{
		$part=explode(':',$val);
		if ($part[0] == 's.fk_type') $typeid=$part[1];
	}
}
else
{
	$typeid=$_REQUEST['typeid'];
}


/*
 *	View
 */

llxHeader();

$html = new Form($db);


$sql = "SELECT s.rowid as id, s.fk_type as type, ";
$sql.= " s.amount, s.date_ech, s.libelle, s.paye, s.periode,";
$sql.= " c.libelle as type_lib";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
$sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
$sql.= " WHERE s.fk_type = c.id";
$sql.= " AND s.entity = ".$conf->entity;
if (GETPOST("search_label")) $sql.=" AND s.libelle like '%".GETPOST("search_label")."%'";
if ($year > 0)
{
    $sql .= " AND (";
    // Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
    // ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
    $sql .= "   (s.periode is not null and date_format(s.periode, '%Y') = '".$year."') ";
    $sql .= "or (s.periode is null     and date_format(s.date_ech, '%Y') = '".$year."')";
    $sql .= ")";
}
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND s.fk_type=".$typeid;
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1,$offset);


$chargesociale_static=new ChargeSociales($db);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	$param='';
	if ($year)   $param.='&amp;year='.$year;
	if ($typeid) $param.='&amp;typeid='.$typeid;

	if ($year)
	{
		print_fiche_titre($langs->trans("SocialContributions"),($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));
	}
	else
	{
		print_barre_liste($langs->trans("SocialContributions"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$totalnboflines);
	}

	if ($mesg)
	{
	    print $mesg."<br>";
	}


	if (empty($mysoc->pays_id) && empty($mysoc->pays_code))
	{
		print '<div class="error">';
		$langs->load("errors");
		$countrynotdefined=$langs->trans("ErrorSetACountryFirst");
		print $countrynotdefined;
		print '</div>';
	}
	else
	{

		print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

		print "<table class=\"noborder\" width=\"100%\">";

		print "<tr class=\"liste_titre\">";
		print_liste_field_titre($langs->trans("Ref"),"index.php","id","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),"index.php","s.libelle","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Type"),"index.php","type","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("PeriodEndDate"),"index.php","periode","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Amount"),"index.php","s.amount","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateDue"),"index.php","s.date_ech","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Status"),"index.php","s.paye","",$param,'align="right"',$sortfield,$sortorder);
		print "</tr>\n";

		print '<tr class="liste_titre">';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_label" value="'.GETPOST("search_label").'"></td>';
		// Type
		print '<td class="liste_titre" align="left">';
	    $html->select_type_socialcontrib($typeid,'typeid',1,16,0);
	    print '</td>';
		// Period end date
		print '<td class="liste_titre">&nbsp;</td>';
	    print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre" align="right">';
		print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '</td>';
		print "</tr>\n";

		while ($i < min($num,$limit))
		{
			$obj = $db->fetch_object($resql);

			$var = !$var;
			print "<tr $bc[$var]>";

			// Ref
			print '<td width="60">';
			$chargesociale_static->id=$obj->id;
			$chargesociale_static->lib=$obj->id;
			$chargesociale_static->ref=$obj->id;
			print $chargesociale_static->getNomUrl(1,'20');
			print '</td>';

			// Label
			print '<td>'.dol_trunc($obj->libelle,42).'</td>';

			// Type
			print '<td>'.dol_trunc($obj->type_lib,16).'</td>';

			// Date end period
			print '<td align="center">';
			if ($obj->periode)
			{
				print '<a href="index.php?year='.strftime("%Y",$db->jdate($obj->periode)).'">'.dol_print_date($db->jdate($obj->periode),'day').'</a>';
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			print '<td align="right" width="100">'.price($obj->amount).'</td>';

			// Due date
			print '<td width="110" align="center">'.dol_print_date($db->jdate($obj->date_ech), 'day').'</td>';

			print '<td align="right" nowrap="nowrap">'.$chargesociale_static->LibStatut($obj->paye,5).'</a></td>';

			print '</tr>';
			$i++;
		}

		print '</table>';

		print '</form>';
	}
}
else
{
	dol_print_error($db);
}




$db->close();

llxFooter('$Date: 2011/07/31 22:23:20 $ - $Revision: 1.66 $');
?>
