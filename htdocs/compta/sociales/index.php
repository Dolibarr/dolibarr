<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
    	\file       htdocs/compta/sociales/index.php
		\ingroup    tax
		\brief      Ecran des charges sociales
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/chargesociales.class.php");


if (!$user->admin && ! $user->rights->tax->charges->lire)
  accessforbidden();


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = $_GET["page"];
if ($page < 0) $page = 0;

$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (! $sortfield) $sortfield="c.id";
if (! $sortorder) $sortorder="DESC";

$year=$_GET["year"];
$filtre=$_GET["filtre"];
//if (! $year) { $year=date("Y", time()); }




/*
 *  Affichage liste et formulaire des charges.
 */

llxHeader();

print_fiche_titre($langs->trans("SocialContributions"),($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));
print "<br>\n";

if ($mesg)
{
    print $mesg."<br>";
}

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Ref"),"index.php","id","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateDue"),"index.php","de","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Period"),"index.php","periode","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Type"),"index.php","type","","",'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Label"),"index.php","s.libelle","","",'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Amount"),"index.php","s.amount","","",'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Status"),"index.php","s.paye","","",'align="center"',$sortfield,$sortorder);
print "</tr>\n";


$sql = "SELECT s.rowid as id, s.fk_type as type, ";
$sql.= " s.amount,".$db->pdate("s.date_ech")." as de, s.libelle, s.paye,".$db->pdate("s.periode")." as periode,";
$sql.= " c.libelle as type_lib";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql.= " WHERE s.fk_type = c.id";
if ($year > 0)
{
    $sql .= " AND (";
    // Si period renseigné on l'utilise comme critere de date, sinon on prend date échéance,
    // ceci afin d'etre compatible avec les cas ou la période n'etait pas obligatoire
    $sql .= "   (s.periode is not null and date_format(s.periode, '%Y') = $year) ";
    $sql .= "or (s.periode is null     and date_format(s.date_ech, '%Y') = $year)";
    $sql .= ")";
}
if ($filtre) {
    $filtre=ereg_replace(":","=",$filtre);
    $sql .= " AND $filtre";
}
if ($_GET["sortfield"]) {
    $sql .= " ORDER BY ".$_GET["sortfield"];
}
else {
    $sql .= " ORDER BY lower(s.date_ech)";
}
if ($_GET["sortorder"]) {
    $sql .= " ".$_GET["sortorder"];
}
else {
    $sql .= " DESC";
}


$chargesociale_static=new ChargeSociales($db);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		$var = !$var;
		print "<tr $bc[$var]>";
		print '<td width="60">';
		print '<a href="charges.php?id='.$obj->id.'">'.img_file().' '.$obj->id.'</a>';
		print '</td>';

		print '<td width="110">'.dolibarr_print_date($obj->de).'</td>';
		print '<td>';
		if ($obj->periode) {
			print '<a href="index.php?year='.strftime("%Y",$obj->periode).'">'.strftime("%Y",$obj->periode).'</a>';
			} else {
				print '&nbsp;';
			}
			print '</td>';
			print '<td>'.$obj->type_lib.'</td><td>'.dolibarr_trunc($obj->libelle,36).'</td>';
			print '<td align="right" width="100">'.price($obj->amount).'</td>';

			print '<td align="right" nowrap="nowrap">'.$chargesociale_static->LibStatut($obj->paye,5).'</a></td>';

			print '</tr>';
			$i++;
		}
	}
	else
	{
		dolibarr_print_error($db);
	}

print '</table>';



$db->close();

llxFooter('$Date$ - $Revision$');
?>
