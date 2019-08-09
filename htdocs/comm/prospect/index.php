<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	    \file       htdocs/comm/prospect/index.php
 *      \ingroup    commercial
 *		\brief      Home page of propest area
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

// Load translation files required by the page
$langs->load("propal");


if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}



/*
 *	View
 */

$companystatic=new Societe($db);

llxHeader();

print load_fiche_titre($langs->trans("ProspectionArea"));

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


if (! empty($conf->propal->enabled))
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/comm/propal/card.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProposal").'</td></tr>';
	print '<tr class="oddeven"><td>';
	print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
	print '</tr>';
	print "</table></form><br>\n";
}

/*
 * Prospects par statut
 *
 */

$sql = "SELECT count(*) as cc, st.libelle, st.id";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st ";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (2, 3)";
$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY st.id";
$sql.= " ORDER BY st.id";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num > 0 )
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("ProspectsByStatus").'</td></tr>';
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';
			print '<a href="prospects.php?page=0&amp;stcomm='.$obj->id.'">';
			print img_action($langs->trans("Show"), $obj->id).' ';
			print $langs->trans("StatusProspect".$obj->id);
			print '</a></td><td class="right">'.$obj->cc.'</td></tr>';
			$i++;
		}
		print "</table><br>";
	}
}


/*
 * Liste des propal brouillons
 */
if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
{
	$sql = "SELECT p.rowid, p.ref, p.price, s.nom as sname";
	$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_statut = 0";
	$sql.= " AND p.fk_soc = s.rowid";
	$sql.= " AND p.entity IN (".getEntity('propal').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$total=0;
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num > 0)
		{
			print '<table class="noborder"" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="2">'.$langs->trans("ProposalsDraft").'</td></tr>';

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowPropal"), "propal").' '.$obj->ref.'</a>';
				print '</td><td class="right">';
				print price($obj->price);
				print "</td></tr>";
				$i++;
				$total += $obj->price;
			}
			if ($total>0) {

				print '<tr class="liste_total"><td>'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td></tr>";
			}
			print "</table><br>";
		}
		$db->free($resql);
	}
}


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Actions commerciales a faire
 */
if (! empty($conf->agenda->enabled)) show_array_actions_to_do(10);

/*
 * Dernieres propales ouvertes
 */
if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
{
	$sql = "SELECT s.nom as name, s.rowid as socid, s.client, s.canvas,";
	$sql.= " p.rowid as propalid, p.total as total_ttc, p.ref, p.datep as dp, c.label as statut, c.id as statutid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	$sql.= ", ".MAIN_DB_PREFIX."c_propalst as c";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.fk_statut = c.id";
	$sql.= " AND p.fk_statut = 1";
	$sql.= " AND p.entity IN (".getEntity('propal').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY p.rowid DESC";
	$sql.= $db->plimit(5, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProposalsOpened").'</td></tr>';

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print '<a href="../propal.php?id='.$obj->propalid.'">';
				print img_object($langs->trans("ShowPropal"), "propal").' '.$obj->ref.'</a></td>';

				print "<td>";
                $companystatic->id=$obj->socid;
                $companystatic->name=$obj->name;
                $companystatic->client=$obj->client;
                $companystatic->canvas=$obj->canvas;
                print $companystatic->getNomUrl(1, '', 44);
				print "</td>\n";
				print "<td align=\"right\">";
				print dol_print_date($db->jdate($obj->dp), 'day')."</td>\n";
				print "<td align=\"right\">".price($obj->total_ttc)."</td></tr>\n";
				$i++;
				$total += $obj->price;
			}
			if ($total>0)
			{
				print '<tr class="liste_total"><td colspan="3" class="right">'.$langs->trans("Total")."</td><td class=\"right\">".price($total)."</td></tr>";
			}
			print "</table><br>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Societes a contacter
 *
 */
$sql = "SELECT s.nom as name, s.rowid as socid, s.client, s.canvas";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = 1";
$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY s.tms ASC";
$sql.= $db->plimit(15, 0);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num > 0 )
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("ProspectToContact").'</td></tr>';

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td width="12%">';
            $companystatic->id=$obj->socid;
            $companystatic->name=$obj->name;
            $companystatic->client=$obj->client;
            $companystatic->canvas=$obj->canvas;
            print $companystatic->getNomUrl(1, 'prospect', 44);
			print '</td></tr>';
			$i++;
		}
		print "</table><br>";
	}
}


//print '</td></tr></table>';
print '</div></div></div>';

// End of page
llxFooter();
$db->close();
