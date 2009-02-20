<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      �ric Seigne          <eric.seigne@ryxeo.com>
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
 *	\file       htdocs/compta/facture/prelevement.php
 *	\ingroup    facture
 *	\brief      Gestion des prelevement d'une facture
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

if (!$user->rights->facture->lire)
accessforbidden();

$langs->load("bills");
$langs->load("banks");
$langs->load("withdrawals");

// S�curit� acc�s client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions
 */

if ($_GET["action"] == "new")
{
	$fact = new Facture($db);
	if ($fact->fetch($_GET["facid"]))
	{
		$result = $fact->demande_prelevement($user);
		if ($result > 0)
		{
			Header("Location: prelevement.php?facid=".$fact->id);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$fact->error.'</div>';
		}
	}
}

if ($_GET["action"] == "delete")
{
	$fact = new Facture($db);
	if ($fact->fetch($_GET["facid"]))
	{
		$result = $fact->demande_prelevement_delete($user,$_GET["did"]);
		if ($result == 0)
		{
			Header("Location: prelevement.php?facid=".$fact->id);
			exit;
		}
	}
}


/*
 * View
 */

$now=gmmktime();

llxHeader('',$langs->trans("Bill"));

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["facid"] > 0)
{
	$fac = New Facture($db);
	if ( $fac->fetch($_GET["facid"], $user->societe_id) > 0)
	{
		if ($mesg) print $mesg.'<br>';

		$soc = new Societe($db, $fac->socid);
		$soc->fetch($fac->socid);

		$author = new User($db);
		if ($fac->user_author)
		{
			$author->id = $fac->user_author;
			$author->fetch();
		}

		$head = facture_prepare_head($fac);
		dol_fiche_head($head, 'standingorders', $langs->trans('InvoiceCustomer'));

		/*
		 *   Facture
		 */
		print '<table class="border" width="100%">';

		// Reference du facture
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $fac->ref;
		print "</td></tr>";

		// Societe
		print '<tr><td width="20%">'.$langs->trans("Company").'</td>';
		print '<td colspan="5">';
		print '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
		print '</tr>';

		// Dates
		print '<tr><td>'.$langs->trans("Date").'</td>';
		print '<td colspan="3">'.dol_print_date($fac->date,"daytext").'</td>';
		print '<td>'.$langs->trans("DateMaxPayment").'</td><td>' . dol_print_date($fac->date_lim_reglement,"daytext");
		if ($fac->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans("Late"));
		print "</td></tr>";

		// Conditions et modes de r�glement
		print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td colspan="3">';
		$html->form_conditions_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->cond_reglement_id,"none");
		print '</td>';
		print '<td width="25%">'.$langs->trans("PaymentMode").'</td><td width="25%">';
		$html->form_modes_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->mode_reglement_id,"none");
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("AmountHT").'</td>';
		print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td colspan="2">&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right" colspan="2"><b>'.price($fac->total_ttc).'</b></td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td><td colspan="2">&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("RIB").'</td><td colspan="5">';
		print $soc->display_rib();
		print '</td></tr>';

		print '</table>';
		print '</div>';

		/*
		 * Demande de pr�l�vement
		 *
		 */

		$sql = "SELECT pfd.rowid, pfd.traite,".$db->pdate("pfd.date_demande")." as date_demande";
		$sql .= " ,".$db->pdate("pfd.date_traite")." as date_traite";
		$sql .= " , pfd.amount";
		$sql .= " , u.rowid as user_id, u.name, u.firstname, u.login";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE fk_facture = ".$fac->id;
		$sql .= " AND pfd.fk_user_demande = u.rowid";
		$sql .= " AND pfd.traite = 0";
		$sql .= " ORDER BY pfd.date_demande DESC";

		$result_sql = $db->query($sql);
		if ($result_sql)
		{
			$num = $db->num_rows($result_sql);
		}


		print "<div class=\"tabsAction\">\n";

		// Add a withdraw request
		if ($fac->statut > 0 && $fac->paye == 0 && $fac->mode_reglement_code == 'PRE' && $num == 0)
		{
			if ($user->rights->facture->creer)
			{
				print '<a class="butAction" href="prelevement.php?facid='.$fac->id.'&amp;action=new">'.$langs->trans("MakeWithdrawRequest").'</a>';
			}
		}
		print "</div><br/>";


		/*
		 * Pr�l�vement
		 */
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td align="left">'.$langs->trans("DateRequest").'</td>';
		print '<td align="center">'.$langs->trans("DateProcess").'</td>';
		print '<td align="center">'.$langs->trans("Amount").'</td>';
		print '<td align="center">'.$langs->trans("WithdrawalReceipt").'</td>';
		print '<td align="center">'.$langs->trans("User").'</td><td>&nbsp;</td><td>&nbsp;</td>';
		print '</tr>';
		$var=True;

		if ($result_sql)
		{
			$i = 0;

			while ($i < $num)
			{
				$obj = $db->fetch_object($result_sql);
				$var=!$var;

				print "<tr $bc[$var]>";
				print '<td align="left">'.dol_print_date($obj->date_demande,'day')."</td>\n";
				print '<td align="center">En attente de traitement</td>';
				print '<td align="center">'.price($obj->amount).'</td>';
				print '<td align="center">-</td>';
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
				print '<td>&nbsp;</td>';
				print '<td>';
				print '<a href="prelevement.php?facid='.$fac->id.'&amp;action=delete&amp;did='.$obj->rowid.'">';
				print img_delete();
				print '</a></td>';
				print "</tr>\n";
				$i++;
			}

			$db->free($result_sql);
		}
		else
		{
			dol_print_error($db);
		}

		$sql = "SELECT pfd.rowid, pfd.traite,".$db->pdate("pfd.date_demande")." as date_demande";
		$sql .= " ,".$db->pdate("pfd.date_traite")." as date_traite";
		$sql .= " , pfd.fk_prelevement_bons, pfd.amount";
		$sql .= " , u.rowid as user_id, u.name, u.firstname, u.login";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE fk_facture = ".$fac->id;
		$sql .= " AND pfd.fk_user_demande = u.rowid";
		$sql .= " AND pfd.traite = 1";
		$sql .= " ORDER BY pfd.date_demande DESC";

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				$var=!$var;

				print "<tr $bc[$var]>";

				print '<td align="center">'.dol_print_date($obj->date_demande)."</td>\n";

				print '<td align="center">'.dol_print_date($obj->date_traite)."</td>\n";

				print '<td align="center">'.price($obj->amount).'</td>';

				print '<td align="center">';
				print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$obj->fk_prelevement_bons;
				print '">'.$obj->fk_prelevement_bons."</a></td>\n";

				print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';

				print "</tr>\n";
				$i++;
			}

			$db->free($result);
		}
		else
		{
			dol_print_error($db);
		}

		print "</table>";

	}
	else
	{
		/* Facture non trouv�e */
		print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
	}
}

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
