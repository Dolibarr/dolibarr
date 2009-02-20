<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/*
 * \version	$Id$
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");

// Sécurité accés client
if ($user->societe_id > 0) accessforbidden();


/*
 * View
 */

llxHeader('',$langs->trans("WithdrawalReceipt"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$h++;

if ($conf->use_preview_tabs)
{
	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/bon.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans("Preview");
	$h++;
}

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Lines");
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Bills");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Rejects");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Statistics");
$h++;

$prev_id = $_GET["id"];

if ($_GET["id"])
{
	$bon = new BonPrelevement($db,"");

	if ($bon->fetch($_GET["id"]) == 0)
	{

		dol_fiche_head($head, $hselected, $langs->trans("WithdrawalReceipt"));


		print '<table class="border" width="100%">';

		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';

		print '</table>';
		
		print '</div>';
	}
	else
	{
		print "Erreur";
	}
}

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") {
	$sortorder="DESC";
}
if ($sortfield == "") {
	$sortfield="pl.fk_soc";
}

/*
 * Liste des lignes de prélèvement
 *
 *
 */
$sql = "SELECT pl.rowid, pl.statut, pl.amount";
$sql .= " , s.rowid as socid, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pl.fk_prelevement_bons=".$prev_id;
$sql .= " AND pl.fk_soc = s.rowid";

if ($_GET["socid"])
{
	$sql .= " AND s.rowid = ".$_GET["socid"];
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	$urladd = "&amp;id=".$_GET["id"];

	print_barre_liste("", $page, "lignes.php", $urladd, $sortfield, $sortorder, '', $num);
	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Lines"),"lignes.php","pl.rowid",'',$urladd);
	print_liste_field_titre($langs->trans("ThirdParty"),"lignes.php","s.nom",'',$urladd);
	print_liste_field_titre($langs->trans("Amount"),"lignes.php","f.total_ttc","",$urladd,'align="center"');
	print '<td colspan="2">&nbsp;</td></tr>';

	$var=false;

	$total = 0;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		print "<tr $bc[$var]><td>";

		print '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
		print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';
		print substr('000000'.$obj->rowid, -6);
		print '</a></td>';

		print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->socid.'">'.stripslashes($obj->nom)."</a></td>\n";

		print '<td align="center">'.price($obj->amount)."</td>\n";

		print '<td>';

		if ($obj->statut == 3)
		{
	  		print '<b>Rejeté</b>';
		}
		else
		{
	  		print "&nbsp;";
		}

		print '</td></tr>';

		$total += $obj->total_ttc;
		$var=!$var;
		$i++;
	}

	if($_GET["socid"])
	{
		print "<tr $bc[$var]><td>";

		print '<td>Total</td>';

		print '<td align="center">'.price($total)."</td>\n";

		print '<td>&nbsp;</td>';

		print "</tr>\n";
	}

	print "</table>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
