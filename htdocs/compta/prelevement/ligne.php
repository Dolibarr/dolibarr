<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/compta/prelevement/ligne.php
 *	\ingroup    prelevement
 *	\brief      card of withdraw line
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/ligne-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");

// Security check
if ($user->societe_id > 0) accessforbidden();


if ($_POST["action"] == 'confirm_rejet')
{
	if ( $_POST["confirm"] == 'yes')
	{

		$daterej = mktime(2, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

		$lipre = new LignePrelevement($db, $user);

		if ($lipre->fetch($_GET["id"]) == 0)
		{

			if ($_POST["motif"] > 0 && $daterej < time())
			{
				$rej = new RejetPrelevement($db, $user);

				$rej->create($user, $_GET["id"], $_POST["motif"], $daterej, $lipre->bon_rowid, $_POST["facturer"]);

				Header("Location: ligne.php?id=".$_GET["id"]);
				exit;
			}
			else
			{
				dol_syslog("Motif : ".$_POST["motif"]);
				dol_syslog("$daterej $time ");
				Header("Location: ligne.php?id=".$_GET["id"]."&action=rejet");
				exit;
			}
		}
	}
	else
	{
		Header("Location: ligne.php?id=".$_GET["id"]);
		exit;
	}
}

/*
 * View
 */

llxHeader('',$langs->trans("StandingOrder"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

if ($_GET["id"])
{
	$lipre = new LignePrelevement($db, $user);

	if ($lipre->fetch($_GET["id"]) == 0)
	{
		$bon = new BonPrelevement($db);
		$bon->fetch($lipre->bon_rowid);

		dol_fiche_head($head, $hselected, $langs->trans("StandingOrder"));

		print '<table class="border" width="100%">';

		print '<tr><td width="20%">'.$langs->trans("WithdrawalReceipt").'</td><td>';
		print '<a href="fiche.php?id='.$lipre->bon_rowid.'">'.$lipre->bon_ref.'</a></td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($lipre->amount).'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Status").'</td><td>';
		print '<img src="./statut'.$lipre->statut.'.png">&nbsp;';
		print $lipre->statuts[$lipre->statut].'</td></tr>';

		if ($lipre->statut == 3)
		{
			$rej = new RejetPrelevement($db, $user);
			$resf = $rej->fetch($lipre->id);
			if ($resf == 0)
			{
				print '<tr><td width="20%">Motif du rejet</td><td>'.$rej->motif.'</td></tr>';
				print '<tr><td width="20%">Date du rejet</td><td>';
				if ($rej->date_rejet == 0)
				{
					/* Historique pour certaines install */
					print $langs->trans("Unknown");
				}
				else
				{
					print dol_print_date($rej->date_rejet,'day');
				}
				print '</td></tr>';
			}
			else
			{
				print '<tr><td width="20%">'.$resf.'</td></tr>';
			}
		}


		print '</table><br />';
	}
	else
	{
		print "Erreur";
	}



	if ($_GET["action"] == 'rejet')
	{
		$html = new Form($db);

		$soc = new Societe($db);
		$soc->fetch($lipre->socid);

		$rej = new RejetPrelevement($db, $user);

		print '<form name="confirm_rejet" method="post" action="ligne.php?id='.$_GET["id"].'">';
		print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="confirm_rejet">';
		print '<table class="border" width="100%">';
		print '<tr><td colspan="3">Rejet de prélèvement</td></tr>';
		print '<tr><td class="valid">Etes-vous sûr de vouloir saisir un rejet de prélèvement pour la société '.$soc->nom.' ?</td>';
		print '<td colspan="2" class="valid">';
		print '<select name="confirm">';
		print '<option value="yes">oui</option>';
		print '<option value="no" selected="true">non</option>';
		print '</select>';
		print '</td></tr>';

		print '<tr><td class="valid">Date du rejet</td>';
		print '<td colspan="2" class="valid">';
		print $html->select_date('','','','','',"confirm_rejet");
		print '</td></tr>';
		print '<tr><td class="valid">Motif du rejet</td>';
		print '<td class="valid">';
		print '<select name="motif">';
		print '<option value="0">(Motif du Rejet)</option>';

		foreach($rej->motifs as $key => $value)
		{
	  print '<option value="'.$key.'">'.$value.'</option>';
		}
		print '</select>';
		print '</td>';
		print '<td class="valid" align="center">';
		print '<input type="submit" value="Confirmer"></td></tr>';

		print '<tr><td class="valid">Facturation du rejet</td>';
		print '<td class="valid" colspan="2">';
		print '<select name="facturer">';
		print '<option value="0">Ne Pas Facturer le rejet</option>';
		print '<option value="1">Facturer le rejet au client</option>';
		print '</select>';
		print '</td>';
		print '</table></form>';
	}

	$page = $_GET["page"];
	$sortorder = $_GET["sortorder"];
	$sortfield = $_GET["sortfield"];

	if ($page == -1) { $page = 0 ; }

	$offset = $conf->liste_limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	if ($sortorder == "") $sortorder="DESC";
	if ($sortfield == "") $sortfield="pl.fk_soc";

	/*
	 * Liste des factures
	 *
	 *
	 */
	$sql = "SELECT pf.rowid";
	$sql.= " ,f.rowid as facid, f.facnumber as ref, f.total_ttc";
	$sql.= " , s.rowid as socid, s.nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
	$sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
	$sql.= " , ".MAIN_DB_PREFIX."facture as f";
	$sql.= " , ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE pf.fk_prelevement_lignes = pl.rowid";
	$sql.= " AND pl.fk_prelevement_bons = p.rowid";
	$sql.= " AND f.fk_soc = s.rowid";
	$sql.= " AND pf.fk_facture = f.rowid";
	$sql.= " AND f.entity = ".$conf->entity;
	$sql.= " AND pl.rowid=".$_GET["id"];
	if ($_GET["socid"])	$sql.= " AND s.rowid = ".$_GET["socid"];
	$sql.= " ORDER BY $sortfield $sortorder ";
	$sql.= $db->plimit($conf->liste_limit+1, $offset);

	$result = $db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		$urladd = "&amp;id=".$_GET["id"];

		print_barre_liste("Factures", $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num, 0, '');

		print"\n<!-- debut table -->\n";
		print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Invoice").'</td><td>'.$langs->trans("Company").'</td><td align="right">'.$langs->trans("Amount").'</td>';
		print '</tr>';

		$var=True;

		$total = 0;

		$var=false;
		while ($i < min($num,$conf->liste_limit))
		{
			$obj = $db->fetch_object($result);

			print "<tr $bc[$var]><td>";

			print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
			print img_object($langs->trans("ShowBill"),"bill");
			print '</a>&nbsp;';

			print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->ref."</a></td>\n";

			print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$obj->socid.'">';
			print img_object($langs->trans("ShowCompany"),"company"). ' '.stripslashes($obj->nom)."</a></td>\n";

			print '<td align="right">'.price($obj->total_ttc)."</td>\n";


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

	$db->close();


	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print "\n</div>\n<div class=\"tabsAction\">\n";

	if ($_GET["action"] == '')
	{

		if ($bon->credite == 1 && $lipre->statut == 2)
		{
	  		print "<a class=\"butAction\" href=\"ligne.php?action=rejet&amp;id=$lipre->id\">".$langs->trans("Emmetre un rejet")."</a>";
		}
	}

	print "</div>";
}

llxFooter('$Date$ - $Revision$');
?>
