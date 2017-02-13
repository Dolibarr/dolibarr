<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \brief      Page de fiche recap customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

$langs->load("companies");
if (! empty($conf->facture->enabled)) $langs->load("bills");

$id = GETPOST('id')?GETPOST('id','int'):GETPOST('socid','int');

// Security check
if ($user->societe_id) $id=$user->societe_id;
$result = restrictedArea($user, 'societe', $id, '&societe');

$object = new Societe($db);
if ($id > 0) $object->fetch($id);


/*
 *	View
 */

$form = new Form($db);
$userstatic=new User($db);

$title=$langs->trans("ThirdParty").' - '.$langs->trans("Summary");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name.' - '.$langs->trans("Symmary");
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('',$title,$help_url);

if ($id > 0)
{
	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');
	dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom', '', '', 0, '', '', 1);
	dol_fiche_end();
	
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		// Invoice list
		print load_fiche_titre($langs->trans("CustomerPreview"));

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Element").'</td>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td align="right">'.$langs->trans("Debit").'</td>';
		print '<td align="right">'.$langs->trans("Credit").'</td>';
		print '<td align="right">'.$langs->trans("Balance").'</td>';
		print '<td align="right">'.$langs->trans("Author").'</td>';
		print '</tr>';
		
		$TData = array();
		$TDataSort = array();

		$sql = "SELECT s.nom, s.rowid as socid, f.facnumber, f.amount, f.datef as df,";
		$sql.= " f.paye as paye, f.fk_statut as statut, f.rowid as facid,";
		$sql.= " u.login, u.rowid as userid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_user_valid = u.rowid";
		$sql.= " ORDER BY f.datef ASC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);

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
				
				$userstatic->id=$objf->userid;
				$userstatic->login=$objf->login;
				
				$TData[] = array(
					'date' => $fac->date,
					'link' => $fac->getNomUrl(1),
					'status' => $fac->getLibStatut(2,$totalpaye),
					'amount' => $fac->total_ttc,
					'author' => $userstatic->getLoginUrl(1)
				);
				$TDataSort[] = $fac->date;

				// Paiements
				$sql = "SELECT p.rowid, p.datep as dp, pf.amount, p.statut,";
				$sql.= " p.fk_user_creat, u.login, u.rowid as userid";
				$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,";
				$sql.= " ".MAIN_DB_PREFIX."paiement as p";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_creat = u.rowid";
				$sql.= " WHERE pf.fk_paiement = p.rowid";
				$sql.= " AND p.entity = ".$conf->entity;
				$sql.= " AND pf.fk_facture = ".$fac->id;
				$sql.= " ORDER BY p.datep ASC";

				$resqlp = $db->query($sql);
				if ($resqlp)
				{
					$nump = $db->num_rows($resqlp);
					$j = 0;

					while ($j < $nump)
					{
						$objp = $db->fetch_object($resqlp);
						
						$paymentstatic = new Paiement($db);
						$paymentstatic->id = $objp->rowid;
						
						$userstatic->id=$objp->userid;
						$userstatic->login=$objp->login;
						
						$TData[] = array(
							'date' => $db->jdate($objp->dp),
							'link' => $langs->trans("Payment") .' '. $paymentstatic->getNomUrl(1),
							'status' => '',
							'amount' => -$objp->amount,
							'author' => $userstatic->getLoginUrl(1)
						);
						$TDataSort[] = $db->jdate($objp->dp);

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
		
		if(empty($TData)) {
			print '<tr '.$bc[false].'><td colspan="7">'.$langs->trans("NoInvoice").'</td></tr>';
		} else {
		
			// Sort array by date
			asort($TDataSort);
			array_multisort($TData,$TDataSort);
			
			// Balance calculation
			foreach($TData as &$data1) {
				$balance += $data1['amount'];
				$data1['balance'] += $balance;
			}
			
			// Reverse array to have last elements on top
			$TData = array_reverse($TData);
			
			$totalDebit = 0;
			$totalCredit = 0;
			
			// Display array
			foreach($TData as $data) {
				$var=!$var;
				print "<tr ".$bc[$var].">";
	
				print "<td align=\"center\">".dol_print_date($data['date'],'day')."</td>\n";
				print '<td>'.$data['link']."</td>\n";
	
				print '<td aling="left">'.$data['status'].'</td>';
				print '<td align="right">'.(($data['amount'] > 0) ? price(abs($data['amount'])) : '')."</td>\n";
				$totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;
				print '<td align="right">'.(($data['amount'] > 0) ? '' : price(abs($data['amount'])))."</td>\n";
				$totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);
				print '<td align="right">'.price($data['balance'])."</td>\n";
	
				// Author
				print '<td class="nowrap" align="right">';
				print $data['author'];
				print '</td>';
	
				print "</tr>\n";
			}
			
			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td align="right">'.price($totalDebit).'</td>';
			print '<td align="right">'.price($totalCredit).'</td>';
			print '<td colspan="2">&nbsp;</td>';
			print "</tr>\n";
		}
		
		print "</table>";
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
