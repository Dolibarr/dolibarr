<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/compta/prelevement/ligne.php
 *	\ingroup    prelevement
 *	\brief      card of withdraw line
 */

require("../bank/pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/ligne-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

// Security check
if ($user->societe_id > 0) accessforbidden();

$langs->load("bills");
$langs->load("withdrawals");
$langs->load("categories");

// Get supervariables
$action = GETPOST("action");
$id = GETPOST("id");
$socid = GETPOST("socid");

$page = GETPOST("page");
$sortorder = GETPOST("sortorder");
$sortfield = GETPOST("sortfield");

if ($action == 'confirm_rejet')
{
	if ( GETPOST("confirm") == 'yes')
	{
		$daterej = mktime(2, 0, 0, GETPOST("remonth"), GETPOST("reday"), GETPOST("reyear"));

		$lipre = new LignePrelevement($db, $user);

		if ($lipre->fetch($id) == 0)
		{

			if (GETPOST("motif") > 0 && $daterej < time())
			{
				$rej = new RejetPrelevement($db, $user);

				$rej->create($user, $id, GETPOST("motif"), $daterej, $lipre->bon_rowid, GETPOST("facturer"));

				Header("Location: ligne.php?id=".$id);
				exit;
			}
			else
			{
				dol_syslog("Motif : ".GETPOST("motif"));
				dol_syslog("$daterej $time ");
				Header("Location: ligne.php?id=".$id."&action=rejet");
				exit;
			}
		}
	}
	else
	{
		Header("Location: ligne.php?id=".$id);
		exit;
	}
}

/*
 * View
 */

llxHeader('',$langs->trans("StandingOrder"));

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$id;
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

if ($id)
{
	$lipre = new LignePrelevement($db, $user);

	if ($lipre->fetch($id) == 0)
	{
		$bon = new BonPrelevement($db);
		$bon->fetch($lipre->bon_rowid);

		dol_fiche_head($head, $hselected, $langs->trans("StandingOrder"));

		print '<table class="border" width="100%">';

		print '<tr><td width="20%">'.$langs->trans("WithdrawalReceipt").'</td><td>';
		print '<a href="fiche.php?id='.$lipre->bon_rowid.'">'.$lipre->bon_ref.'</a></td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($lipre->amount).'</td></tr>';		
		print '<tr><td width="20%">'.$langs->trans("Status").'</td><td>';
		
		print $lipre->LibStatut($lipre->statut,1).'</td></tr>';
		
		if ($lipre->statut == 3)
		{
			$rej = new RejetPrelevement($db, $user);
			$resf = $rej->fetch($lipre->id);
			if ($resf == 0)
			{
				print '<tr><td width="20%">'.$langs->trans("RefusedReason").'</td><td>'.$rej->motif.'</td></tr>';
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
				print '<tr><td width="20%">'.$langs->trans("RefusedInvoicing").'</td><td>'.$rej->invoicing.'</td></tr>';
			}
			else
			{
				print '<tr><td width="20%">'.$resf.'</td></tr>';
			}
		}

		print '</table>';
		dol_fiche_end();
	}
	else
	{
		dol_print_error($db);
	}

	if ($action == 'rejet' && $user->rights->prelevement->bons->credit)
	{
		$form = new Form($db);

		$soc = new Societe($db);
		$soc->fetch($lipre->socid);

		$rej = new RejetPrelevement($db, $user);

		print '<form name="confirm_rejet" method="post" action="ligne.php?id='.$id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="confirm_rejet">';
		print '<table class="border" width="100%">';

		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("WithdrawalRefused").'</td></tr>';
		
		//Select yes/no
		print '<tr><td class="valid">'.$langs->trans("WithdrawalRefusedConfirm").' '.$soc->nom.' ?</td>';
		print '<td colspan="2" class="valid">';
		print $form->selectyesno("confirm",1,0);
		print '</td></tr>';
		
		//Date
		print '<tr><td class="valid">'.$langs->trans("RefusedData").'</td>';
		print '<td colspan="2" class="valid">';
		print $form->select_date('','','','','',"confirm_rejet");
		print '</td></tr>';
		
		//Reason
		print '<tr><td class="valid">'.$langs->trans("RefusedReason").'</td>';
		print '<td class="valid">';
		print $form->selectarray("motif", $rej->motifs);
		print '</td></tr>';
			
		//Facturer
		print '<tr><td class="valid">'.$langs->trans("RefusedInvoicing").'</td>';
		print '<td class="valid" colspan="2">';
		print $form->selectarray("facturer", $rej->facturer);
		print '</td></tr>';
		print '</table><br>';
		
		//Confirm Button
		print '<center><input type="submit" class="valid" value='.$langs->trans("Confirm").'><center>';
		print '</form>';
	}
	
	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print "<div class=\"tabsAction\">";

	if ($action == '')
	{
		if ($bon->statut == 2 && $lipre->statut == 2 && $user->rights->prelevement->bons->credit)
		{
	  		print "<a class=\"butAction\" href=\"ligne.php?action=rejet&amp;id=$lipre->id\">".$langs->trans("StandingOrderReject")."</a>";
		}
		else 
		{
			print "<a class=\"butActionRefused\" href=\"#\">".$langs->trans("StandingOrderReject")."</a>";
		}
	}

	print "</div>";



	if ($page == -1) { $page = 0 ; }

	$offset = $conf->liste_limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	if ($sortorder == "") $sortorder="DESC";
	if ($sortfield == "") $sortfield="pl.fk_soc";

	/*
	 * Liste des factures
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
	$sql.= " AND pl.rowid=".$id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY $sortfield $sortorder ";
	$sql.= $db->plimit($conf->liste_limit+1, $offset);

	$result = $db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		$urladd = "&amp;id=".$id;

		print_barre_liste($langs->trans("Bills"), $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num, 0, '');

		print"\n<!-- debut table -->\n";
		print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Invoice").'</td><td>'.$langs->trans("ThirdParty").'</td><td align="right">'.$langs->trans("Amount").'</td>';
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

			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">';
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
}

llxFooter();
?>
