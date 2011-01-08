<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/ligne-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

// Security check
if ($user->societe_id > 0) accessforbidden();

$langs->load("bills");
$langs->load("withdrawals");
$langs->load("categories");


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

	//$lipre->statuts[0] = $langs->trans("StatusWaiting");
    //$lipre->statuts[2] = $langs->trans("StatusCredited");
    //$lipre->statuts[3] = $langs->trans("StatusRefused");

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
		
		print $lipre->LibStatut($lipre->statut,1).'</td></tr>';
		
		/*print '<img src="./img/statut'.$lipre->statut.'.png"> ';
		print $langs->trans($lipre->statuts[$lipre->statut]).'</td></tr>';*/

		if ($lipre->statut == 3)
		{
			$rej = new RejetPrelevement($db, $user);
			$resf = $rej->fetch($lipre->id);
			if ($resf == 0)
			{
				print '<tr><td width="20%">M'.$langs->trans("RefusedReason").'</td><td>'.$rej->motif.'</td></tr>';
				print '<tr><td width="20%">'.$langs->trans("RefusedData").'</td><td>';
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


		print '</table><br>';
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

		$rej->motifs[0] = $langs->trans("StatusMotif0");
    	$rej->motifs[1] = $langs->trans("StatusMotif1");
    	$rej->motifs[2] = $langs->trans("StatusMotif2");
    	$rej->motifs[3] = $langs->trans("StatusMotif3");
    	$rej->motifs[4] = $langs->trans("StatusMotif4");
    	$rej->motifs[5] = $langs->trans("StatusMotif5");
    	$rej->motifs[6] = $langs->trans("StatusMotif6");
    	$rej->motifs[7] = $langs->trans("StatusMotif7");
    	$rej->motifs[8] = $langs->trans("StatusMotif8");

		print '<form name="confirm_rejet" method="post" action="ligne.php?id='.$_GET["id"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="confirm_rejet">';
		print '<table class="border" width="100%">';
		print '<tr><td colspan="3">'.$langs->trans("WithdrawalRefused").'</td></tr>';
		print '<tr><td class="valid">'.$langs->trans("WithdrawalRefusedConfirm").' '.$soc->nom.' ?</td>';
		print '<td colspan="2" class="valid">';
		print '<select name="confirm">';
		print '<option value="yes">'.$langs->trans("Yes").'</option>';
		print '<option value="no" selected="selected">'.$langs->trans("No").'</option>';
		print '</select>';
		print '</td></tr>';

		print '<tr><td class="valid">'.$langs->trans("RefusedData").'</td>';
		print '<td colspan="2" class="valid">';
		print $html->select_date('','','','','',"confirm_rejet");
		print '</td></tr>';
		print '<tr><td class="valid">'.$langs->trans("RefusedReason").'</td>';
		print '<td class="valid">';
		print '<select name="motif">';
		print '<option value="0">('.$langs->trans("RefusedReason").')</option>';

		foreach($rej->motifs as $key => $value)
		{
	  		print '<option value="'.$key.'">'.$value.'</option>';
		}
		print '</select>';
		print '</td>';
		print '<td class="valid" align="center">';
		print '<input type="submit" value='.$langs->trans("Confirm").'></td></tr>';

		print '<tr><td class="valid">'.$langs->trans("RefusedInvoicing").'</td>';
		print '<td class="valid" colspan="2">';
		print '<select name="facturer">';
		print '<option value="0">'.$langs->trans("NoInvoiceRefused").'</option>';
		print '<option value="1">'.$langs->trans("InvoiceRefused").'</option>';
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

		print_barre_liste($langs->trans("Bills"), $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num, 0, '');

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

		if ($bon->statut == 2 && $lipre->statut == 2)
		{
	  		print "<a class=\"butAction\" href=\"ligne.php?action=rejet&amp;id=$lipre->id\">".$langs->trans("StandingOrderReject")."</a>";
		}
	}

	print "</div>";
}

llxFooter('$Date$ - $Revision$');
?>
