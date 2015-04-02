<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/compta/recap-compta.php
 *	\ingroup    compta
 *  \brief      Page de fiche recap compta
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("companies");
if (! empty($conf->facture->enabled)) $langs->load("bills");

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}



/*
 *	View
 */

$userstatic=new User($db);

llxHeader();

if ($socid > 0)
{
	$societe = new Societe($db);
	$societe->fetch($socid);

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');

	print "<table width=\"100%\">\n";
	print '<tr><td valign="top" width="50%">';

	print '<table class="border" width="100%">';

	// Name
	print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->name.'</td></tr>';

	// Prefix
	if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
		print ($societe->prefix_comm?$societe->prefix_comm:'&nbsp;');
		print '</td></tr>';
	}

	print "</table>";

	print "</td></tr></table>\n";

	print '</div>';

	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		// Factures
		print_fiche_titre($langs->trans("CustomerPreview"));

		print '<table class="noborder" width="100%">';

		$sql = "SELECT s.nom, s.rowid as socid, f.facnumber, f.amount, f.datef as df,";
		$sql.= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
		$sql.= " u.login, u.rowid as userid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$societe->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_user_valid = u.rowid";
		$sql.= " ORDER BY f.datef DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

			print '<tr class="liste_titre">';
			print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
			print '<td>&nbsp;</td>';
			print '<td>'.$langs->trans("Status").'</td>';
			print '<td align="right">'.$langs->trans("Debit").'</td>';
			print '<td align="right">'.$langs->trans("Credit").'</td>';
			print '<td align="right">'.$langs->trans("Balance").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';

			if (! $num > 0)
			{
				print '<tr><td colspan="7">'.$langs->trans("NoInvoice").'</td></tr>';
			}

			$solde = 0;

			// Boucle sur chaque facture
			for ($i = 0 ; $i < $num ; $i++)
			{
				$objf = $db->fetch_object($resql);

				$fac = new Facture($db);
				$ret=$fac->fetch($objf->facid);
				if ($ret < 0)
				{
					print $fac->error."<br>";
					continue;
				}
				$totalpaye = $fac->getSommePaiement();

				$var=!$var;
				print "<tr ".$bc[$var].">";

				print "<td align=\"center\">".dol_print_date($fac->date,'day')."</td>\n";
				print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id.'">'.img_object($langs->trans("ShowBill"),"bill")." ".$fac->ref."</a></td>\n";

				print '<td aling="left">'.$fac->getLibStatut(2,$totalpaye).'</td>';
				print '<td align="right">'.price($fac->total_ttc)."</td>\n";
				if (($fac->statut == Facture::STATUS_ABANDONED ) || ($fac->statut == Facture::STATUS_CLOSED && ! $fact->close_code) )  $solde = $solde = $solde + $totalpaye;
				else $solde = $solde + $fac->total_ttc;

				print '<td align="right">&nbsp;</td>';
				print '<td align="right">'.price($solde)."</td>\n";

				// Author
				$userstatic->id=$objf->userid;
				$userstatic->login=$objf->login;
				print '<td class="nowrap" align="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';

				print "</tr>\n";

				// Paiements
				$sql = "SELECT p.rowid, p.datep as dp, pf.amount, p.statut,";
				$sql.= " p.fk_user_creat, u.login, u.rowid as userid";
				$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,";
				$sql.= " ".MAIN_DB_PREFIX."paiement as p";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_creat = u.rowid";
				$sql.= " WHERE pf.fk_paiement = p.rowid";
				$sql.= " AND p.entity = ".$conf->entity;
				$sql.= " AND pf.fk_facture = ".$fac->id;

				$resqlp = $db->query($sql);
				if ($resqlp)
				{
					$nump = $db->num_rows($resqlp);
					$j = 0;

					while ($j < $nump)
					{
						$objp = $db->fetch_object($resqlp);
						//$var=!$var;
						print "<tr ".$bc[$var].">";
						print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
						print '<td>';
						print '&nbsp; &nbsp; &nbsp; '; // Decalage
						print '<a href="paiement/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$langs->trans("Payment").' '.$objp->rowid.'</td>';
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print '<td align="right">'.price($objp->amount).'</td>';
						$solde = $solde - $objp->amount;
						print '<td align="right">'.price($solde)."</td>\n";

						// Author
						$userstatic->id=$objp->userid;
						$userstatic->login=$objp->login;
						print '<td class="nowrap" align="right">';
						print $userstatic->getLoginUrl(1);
						print '</td>';

						print '</tr>';

						$j++;
					}

					$db->free($resqlp);
				}
				else
				{
					dol_print_error($db);
				}
			}
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
		print "<br>";
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
