<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 \file       htdocs/societe/lien.php
 \ingroup    societe
 \brief      Page of links to other third parties
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("banks");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');


/*
 * Actions
 */

// Positionne companie parente
if($_GET["socid"] && $_GET["select"])
{
	if ($user->rights->societe->creer)
	{
		$soc = new Societe($db);
		$soc->id = $_GET["socid"];
		$soc->fetch($_GET["socid"]);
		$soc->set_parent($_GET["select"]);

		Header("Location: lien.php?socid=".$soc->id);
		exit;
	}
	else
	{
		Header("Location: lien.php?socid=".$_GET["socid"]);
		exit;
	}
}

// Supprime companie parente
if($_GET["socid"] && $_GET["delsocid"])
{
	if ($user->rights->societe->creer)
	{
		$soc = new Societe($db);
		$soc->id = $_GET["socid"];
		$soc->fetch($_GET["socid"]);
		$soc->remove_parent($_GET["delsocid"]);

		Header("Location: lien.php?socid=".$soc->id);
		exit;
	}
	else
	{
		Header("Location: lien.php?socid=".$_GET["socid"]);
		exit;
	}
}




llxHeader();

if($_GET["socid"])
{

	$soc = new Societe($db);
	$soc->id = $_GET["socid"];
	$soc->fetch($_GET["socid"]);

	$head=societe_prepare_head2($soc);

	dol_fiche_head($head, 'links', $langs->trans("ThirdParty"),0,'company');

	/*
	 * Fiche societe en mode visu
	 */

	print '<table class="border" width="100%">';
	print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$soc->nom.'</td></tr>';

	print '<tr><td>';
	print $langs->trans('CustomerCode').'</td><td width="20%">';
	print $soc->code_client;
	if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
	print '</td><td>'.$langs->trans('Prefix').'</td><td>'.$soc->prefix_comm.'</td></tr>';

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

	print "<tr><td>".$langs->trans('Zip')."</td><td>".$soc->cp."</td>";
	print "<td>".$langs->trans('Town')."</td><td>".$soc->ville."</td></tr>";

	print "<tr><td>".$langs->trans('Country')."</td><td colspan=\"3\">".$soc->pays."</td></tr>";

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';

	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
	print '</td></tr>';

	print '<tr><td>'.$langs->transcountry('ProfId1',$soc->pays_code).'</td><td><a target="_blank" href="http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren.'">'.$soc->siren.'</a>&nbsp;</td>';

	print '<td>'.$langs->transcountry('ProfId2',$soc->pays_code).'</td><td>'.$soc->siret.'</td></tr>';

	print '<tr><td>'.$langs->transcountry('ProfId3',$soc->pays_code).'</td><td>'.$soc->ape.'</td><td colspan="2">&nbsp;</td></tr>';
	print '<tr><td>'.$langs->trans("Capital").'</td><td colspan="3">'.$soc->capital.' '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Societe mere
	print '<tr><td>'.$langs->trans("ParentCompany").'</td><td colspan="3">';
	if ($soc->parent)
	{
		$socm = new Societe($db);
		$socm->fetch($soc->parent);
		print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socm->id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$socm->nom.'</a>'.($socm->code_client?"(".$socm->code_client.")":"");
		print ($socm->ville?' - '.$socm->ville:'');
		print '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?socid='.$_GET["socid"].'&amp;delsocid='.$socm->id.'">';
		print img_delete();
		print '</a><br>';
	}
	else
	{
		print $langs->trans("NoParentCompany");
	}
	print '</td></tr>';

	print '</table>';
	print "</div>\n";


	if ($_GET["select"] > 0)
	{
		$socm = new Societe($db);
		$socm->id = $_GET["select"];
		$socm->fetch($_GET["select"]);
	}
	else
	{
		if ($user->rights->societe->creer)
		{

			$page=$_GET["page"];

			if ($page == -1) { $page = 0 ; }

			$offset = $conf->liste_limit * $page ;
			$pageprev = $page - 1;
			$pagenext = $page + 1;

			/*
			 * Liste
			 *
			 */

			$title=$langs->trans("CompanyList");

			$sql = "SELECT s.rowid as socid, s.nom, s.ville, s.prefix_comm, s.client, s.fournisseur,";
			$sql.= " te.code, te.libelle";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
			$sql.= " ".MAIN_DB_PREFIX."c_typent as te";
			$sql.= " WHERE s.fk_typent = te.id";
			if (strlen(trim($_GET["search_nom"])))
			{
				$sql .= " AND s.nom LIKE '%".$_GET["search_nom"]."%'";
			}
			$sql .= " ORDER BY s.nom ASC " . $db->plimit($conf->liste_limit+1, $offset);

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;

				$params = "&amp;socid=".$_GET["socid"];

				print_barre_liste($title, $page, "lien.php",$params,$sortfield,$sortorder,'',$num,0,'');

				// Lignes des titres
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Company").'</td>';
				print '<td>'.$langs->trans("Town").'</td>';
				print '<td>Type<td>';
				print '<td colspan="2" align="center">&nbsp;</td>';
				print "</tr>\n";

				// Lignes des champs de filtre
				print '<form action="lien.php" method="GET" >';
				print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">';
				print '<tr class="liste_titre">';
				print '<td valign="right">';
				print '<input type="text" name="search_nom" value="'.$_GET["search_nom"].'">';
				print '</td><td colspan="5" align="right">';
				print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
				print '</td>';
				print "</tr>\n";
				print '</form>';

				$var=True;

				while ($i < min($num,$conf->liste_limit))
				{
					$obj = $db->fetch_object($resql);
					$var=!$var;
					print "<tr $bc[$var]><td>";
					print $obj->nom."</td>\n";
					print "<td>".$obj->ville."&nbsp;</td>\n";
					print "<td>".$langs->getLabelFromKey($db,$obj->code,'c_typent','code','libelle')."</td>\n";
					print '<td align="center">';
					if ($obj->client==1)
					{
						print $langs->trans("Customer")."\n";
					}
					elseif ($obj->client==2)
					{
						print $langs->trans("Prospect")."\n";
					}
					else
					{
						print "&nbsp;";
					}
					print "</td><td align=\"center\">";
					if ($obj->fournisseur)
					{
						print $langs->trans("Supplier");
					}
					else
					{
						print "&nbsp;";
					}

					print '</td>';
					// Lien S�lectionner
					print '<td align="center"><a href="lien.php?socid='.$_GET["socid"].'&amp;select='.$obj->socid.'">'.$langs->trans("Select").'</a>';
					print '</td>';

					print '</tr>'."\n";
					$i++;
				}

				print "</table>";
				print '<br>';
				$db->free($resql);
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
