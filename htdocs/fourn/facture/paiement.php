<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles  <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
 *
 */

/**
   \file       htdocs/fourn/facture/paiement.php
   \ingroup    fournisseur,facture
   \brief      Paiements des factures fournisseurs
   \version    $Revision$
*/


require('./pre.inc.php');
require('./paiementfourn.class.php');

$langs->load('bills');
$langs->load('banks');

$facid=isset($_GET['facid'])?$_GET['facid']:$_POST['facid'];
$action=isset($_GET['action'])?$_GET['action']:$_POST['action'];

$sortfield = isset($_GET['sortfield'])?$_GET['sortfield']:$_POST['sortfield'];
$sortorder = isset($_GET['sortorder'])?$_GET['sortorder']:$_POST['sortorder'];
$page=isset($_GET['page'])?$_GET['page']:$_POST['page'];


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0)
{
	$action = '';
	$socidp = $user->societe_id;
}
/*
 * Actions
 */
if ($action == 'add_paiement')
{
	$error = 0;

	$datepaye = $db->idate(mktime(12, 0 , 0,
			$_POST['remonth'],
			$_POST['reday'],
			$_POST['reyear']));
	$paiement_id = 0;
	$total = 0;
	// Génère tableau des montants amounts
	$amounts = array();
	foreach ($_POST as $key => $value)
	{
		if (substr($key,0,7) == 'amount_')
		{
			$other_facid = substr($key,7);
			$amounts[$other_facid] = $_POST[$key];
			$total = $total + $amounts[$other_facid];
		}
	}

	// Effectue les vérifications des parametres
	if ($_POST['paiementid'] <= 0)
	{
		$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->trans('PaymentMode')).'</div>';
		$error++;
	}

	if ($conf->banque->enabled)
	{
		// Si module bank actif, un compte est obligatoire lors de la saisie
		// d'un paiement
		if (! $_POST['accountid'])
		{
			$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->trans('AccountToCredit')).'</div>';
			$error++;
		}
	}

	if ($total <= 0)
	{
		$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->trans('Amount')).'</div>';
		$error++;
	}

	if (! $error)
	{
		$db->begin();

		// Creation de la ligne paiement
		$paiement = new PaiementFourn($db);
		$paiement->datepaye     = $datepaye;
		$paiement->amounts      = $amounts;   // Tableau de montant
		$paiement->paiementid   = $_POST['paiementid'];
		$paiement->num_paiement = $_POST['num_paiement'];
		$paiement->note         = $_POST['comment'];

		$paiement_id = $paiement->create($user);
		if ($paiement_id > 0)
		{
			if ($conf->banque->enabled)
			{
				// Insertion dans llx_bank
				$label = 'Règlement fournisseur';
				$acc = new Account($db, $_POST['accountid']);
				//paiementid contient "CHQ ou VIR par exemple"
				$bank_line_id = $acc->addline($paiement->datepaye,
					$paiement->paiementid,
					$label,
					0.0 - $paiement->total,
					$paiement->num_paiement,
					'',
					$user);

				// Mise a jour fk_bank dans llx_paiement.
				// On connait ainsi le paiement qui a généré l'écriture bancaire
				if ($bank_line_id > 0)
				{
					$paiement->update_fk_bank($bank_line_id);
					// Mise a jour liens (pour chaque facture concernées par le paiement)
					foreach ($paiement->amounts as $key => $value)
					{
						$facid = $key;
						$fac = new FactureFournisseur($db);
						$fac->fetch($facid);
						$fac->fetch_fournisseur();
						$acc->add_url_line($bank_line_id,
							$paiement_id,
							DOL_URL_ROOT.'/fourn/paiement/fiche.php?id=',
							'(paiement)',
							'payment');
						$acc->add_url_line($bank_line_id,
							$fac->fournisseur->id,
							DOL_URL_ROOT.'/fourn/fiche.php?socid=',
							$fac->fournisseur->nom,
							'company');
					}
				}
				else
				{
					$error++;
				}
			}
		}
		else
		{
			$this->error=$paiement->error;
			$error++;
		}

		if ($error == 0)
		{
			$loc = DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement_id;
			$db->commit();
			Header('Location: '.$loc);
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
}

/*
 * Affichage
 */

llxHeader();

$html=new Form($db);

if ($fiche_erreur_message)
{
	print '<tr><td colspan="3" align="center">'.$fiche_erreur_message.'</td></tr>';
}

if ($action == 'create' || $action == 'add_paiement')
{
	$facture = new FactureFournisseur($db);
	$facture->fetch($facid);

	$sql = 'SELECT s.nom,s.idp, f.amount, f.total_ttc as total, f.facnumber';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn as f WHERE f.fk_soc = s.idp';
	$sql .= ' AND f.rowid = '.$facid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			$obj = $db->fetch_object($resql);
			$total = $obj->total;

			print_titre($langs->trans('DoPayment'));
			print '<form action="paiement.php" method="post">';
			print '<input type="hidden" name="action" value="add_paiement">';
			print '<input type="hidden" name="facid" value="'.$facid.'">';
			print '<input type="hidden" name="facnumber" value="'.$obj->facnumber.'">';
			print '<input type="hidden" name="socid" value="'.$obj->idp.'">';
			print '<input type="hidden" name="societe" value="'.$obj->nom.'">';

			print '<table class="border" width="100%">';

			print '<tr class="liste_titre"><td colspan="3">'.$langs->trans('Payment').'</td>';
			print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">'.$obj->nom.'</td></tr>';
			print '<tr><td>'.$langs->trans('Date').'</td><td>';
			if (!empty($_POST['remonth']) && !empty($_POST['reday']) && !empty($_POST['reyear']))
				$sel_date=mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			else
				$sel_date='';
			$html->select_date($sel_date);
			print '</td>';
			print '<td>'.$langs->trans('Comments').'</td></tr>';
			print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
			$html->select_types_paiements(empty($_POST['paiementid'])?'':$_POST['paiementid'],'paiementid');
			print '</td>';
			print '<td rowspan="3" valign="top">';
			print '<textarea name="comment" wrap="soft" cols="40" rows="4">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td></tr>';
			print '<tr><td>'.$langs->trans('Numero').'</td><td><input name="num_paiement" type="text" value="'.(empty($_POST['num_paiement'])?'':$_POST['num_paiement']).'"><br><em>Numéro du chèque / virement</em></td></tr>';
			if ($conf->banque->enabled)
			{
				print '<tr><td>'.$langs->trans('Account').'</td><td>';
				$html->select_comptes(empty($_POST['accountid'])?(defined('FACTURE_RIB_NUMBER')?FACTURE_RIB_NUMBER:''):$_POST['accountid'],'accountid',0,'',1);
				print '</td></tr>';
			}
			else
			{
				print '<tr><td colspan="2">&nbsp;</td></tr>';
			}
			/*
			 * Autres factures impayées
			 */
			$sql = 'SELECT f.rowid as facid,f.facnumber,f.total_ttc,'.$db->pdate('f.datef').' as df';
			$sql .= ', sum(pf.amount) as am';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
			$sql .= ' WHERE f.fk_soc = '.$facture->socidp;
			$sql .= ' AND f.paye = 0';
			$sql .= ' AND f.fk_statut = 1';  // Statut=0 => non validée, Statut=2 => annulée
			$sql .= ' GROUP BY f.facnumber';
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				if ($num > 0)
				{
					$i = 0;
					print '<tr><td colspan="3">';
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans('Bill').'</td><td align="center">'.$langs->trans('Date').'</td>';
					print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
					print '<td align="right">'.$langs->trans('AlreadyPayed').'</td>';
					print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
					print '<td align="center">'.$langs->trans('Amount').'</td>';
					print '</tr>';

					$var=True;
					$total=0;
					$totalrecu=0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;
						print '<tr '.$bc[$var].'>';
						print '<td><a href="fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' '.$objp->facnumber;
						print '</a></td>';
						if ($objp->df > 0 )
						{
							print '<td align="center">';
							print dolibarr_print_date($objp->df).'</td>';
						}
						else
						{
							print '<td align="center"><b>!!!</b></td>';
						}
						print '<td align="right">'.price($objp->total_ttc).'</td>';
						print '<td align="right">'.price($objp->am).'</td>';
						print '<td align="right">'.price($objp->total_ttc - $objp->am).'</td>';
						print '<td align="center">';
						$namef = 'amount_'.$objp->facid;
						print '<input type="text" size="8" name="'.$namef.'">';
						print "</td></tr>\n";
						$total+=$objp->total;
						$total_ttc+=$objp->total_ttc;
						$totalrecu+=$objp->am;
						$i++;
					}
					if ($i > 1)
					{
						// Print total
						print '<tr class="liste_total">';
						print '<td colspan="2" align="left">'.$langs->trans('TotalTTC').':</td>';
						print '<td align="right"><b>'.price($total_ttc).'</b></td>';
						print '<td align="right"><b>'.price($totalrecu).'</b></td>';
						print '<td align="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
						print '<td align="center">&nbsp;</td>';
						print "</tr>\n";
					}
					print "</table></td></tr>\n";
				}
				$db->free($resql);
			}
			else
			{
				dolibarr_print_error($db);
			}

			/*
			 *
			 */
			print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></td></tr>';
			print '</table>';
			print '</form>';
		}
	}
}

/*
 * Affichage liste
 */
if (! $_GET['action'] && ! $_POST['action'])
{
	if ($page == -1) $page = 0 ;
	$limit = $conf->liste_limit;
	$offset = $limit * $page ;

	if (! $sortorder) $sortorder='DESC';
	if (! $sortfield) $sortfield='p.datep';

	$sql  = 'SELECT p.rowid, '.$db->pdate('p.datep').' as dp, p.amount';
	$sql .= ', f.amount as fa_amount, f.facnumber, s.nom';
	$sql .= ', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
	$sql .= ', ba.rowid as bid, ba.label';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid=pf.fk_paiementfourn';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid=pf.fk_facturefourn ';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.idp = f.fk_soc';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
	if ($socidp)
	{
		$sql .= ' WHERE f.fk_soc = '.$socidp;
	}
	$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
	$sql .= $db->plimit($limit + 1 ,$offset);
/*
	$sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount,";
	$sql.= " p.statut, p.num_paiement,";
	$sql.= " c.libelle as paiement_type,";
	$sql.= " ba.rowid as bid, ba.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."paiement as p,";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	if ($socidp)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
	}
	$sql.= " WHERE p.fk_paiement = c.id";
	if ($socidp)
	{
		$sql.= " AND f.fk_soc = ".$socidp;
	}
	if ($_GET["search_montant"])
	{
	  $sql .=" AND p.amount=".ereg_replace(",",".",$_GET["search_montant"]);
	}
*/

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$var=True;

		print_barre_liste($langs->trans('Payments'), $page, 'paiement.php','',$sortfield,$sortorder,'',$num);
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print_liste_field_titre($langs->trans('Bill'),'paiement.php','facnumber','','','',$sortfield);
		print_liste_field_titre($langs->trans('Company'),'paiement.php','s.nom','','','',$sortfield);
		print_liste_field_titre($langs->trans('Date'),'paiement.php','dp','','','',$sortfield);
		print_liste_field_titre($langs->trans('Type'),'paiement.php','libelle','','','',$sortfield);
		print_liste_field_titre($langs->trans('Account'),'paiement.php','ba.label','','','',$sortfield);
		print_liste_field_titre($langs->trans('AmountTTC'),'paiement.php','fa_amount','','','align="right"',$sortfield);
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').'</a>';
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.$objp->rowid.'</a></td>';
			print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' '.$objp->facnumber.'</a></td>';
			print '<td>'.$objp->nom.'</td>';
			print '<td>'.dolibarr_print_date($objp->dp)."</td>\n";
			print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
			print '<td>';
			if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';
			else print '&nbsp;';
			print '</td>';
			print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;</td>';
			print '</tr>';
			$i++;
		}
		print "</table>";
	}
	else
	{
		dolibarr_print_error($db);
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
