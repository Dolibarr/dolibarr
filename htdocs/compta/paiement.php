<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 *	\file       htdocs/compta/paiement.php
 *	\ingroup    compta
 *	\brief      Page to create a payment
 *	\version    $Id$
 */

include_once('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
include_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
include_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

$facid=isset($_GET['facid'])?$_GET['facid']:$_POST['facid'];
$socname=isset($_GET['socname'])?$_GET['socname']:$_POST['socname'];

$sortfield = isset($_GET['sortfield'])?$_GET['sortfield']:$_POST['sortfield'];
$sortorder = isset($_GET['sortorder'])?$_GET['sortorder']:$_POST['sortorder'];
$page=isset($_GET['page'])?$_GET['page']:$_POST['page'];

$amounts=array();
$amountsresttopay=array();
$addwarning=0;

/*
 * Action add_paiement et confirm_paiement
 */
if ($_POST['action'] == 'add_paiement' || $_POST['action'] == 'confirm_paiement')
{
	$error = 0;

	$datepaye = dol_mktime(12, 0 , 0,
	$_POST['remonth'],
	$_POST['reday'],
	$_POST['reyear']);
	$paiement_id = 0;

	// Verifie si des paiements sont sup�rieurs au montant facture
	foreach ($_POST as $key => $value)
	{
		if (substr($key,0,7) == 'amount_')
		{
			$cursorfacid = substr($key,7);
			$amounts[$cursorfacid] = price2num($_POST[$key]);
			$totalpaiement = $totalpaiement + $amounts[$cursorfacid];
			$tmpfacture=new Facture($db);
			$tmpfacture->fetch($cursorfacid);
			$amountsresttopay[$cursorfacid]=price2num($tmpfacture->total_ttc-$tmpfacture->getSommePaiement());
			if ($amounts[$cursorfacid] && $amounts[$cursorfacid] > $amountsresttopay[$cursorfacid])
			{
				$addwarning=1;
				$formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
			}

			$formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => $_POST[$key]);
		}
	}

	// Effectue les verifications des parametres
	if ($_POST['paiementid'] <= 0)
	{
		$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('PaymentMode')).'</div>';
		$error++;
	}

	if ($conf->banque->enabled)
	{
		// Si module bank actif, un compte est obligatoire lors de la saisie
		// d'un paiement
		if (! $_POST['accountid'])
		{
	  $fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')).'</div>';
	  $error++;
		}
	}

	if ($totalpaiement == 0)
	{
		$fiche_erreur_message = '<div class="error">'.$langs->transnoentities('ErrorFieldRequired',$langs->trans('Amount')).'</div>';
		$error++;
	}

	if (empty($datepaye))
	{
		$fiche_erreur_message = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Date')).'</div>';
		$error++;
	}
}

/*
 * Action add_paiement
 */
if ($_POST['action'] == 'add_paiement')
{
	if ($error)
	{
		$_POST['action']='';
		$_GET['action'] = 'create';
	}
	// Le reste propre a cette action s'affiche en bas de page.
}

/*
 * Action confirm_paiement
 */
if ($_POST['action'] == 'confirm_paiement' && $_POST['confirm'] == 'yes')
{
	$datepaye = dol_mktime(12, 0 , 0,
	$_POST['remonth'],
	$_POST['reday'],
	$_POST['reyear']);

	if (! $error)
	{
		$db->begin();

		// Creation de la ligne paiement
		$paiement = new Paiement($db);
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
				$label = "(CustomerInvoicePayment)";
				$acc = new Account($db, $_POST['accountid']);
				//paiementid contient "CHQ ou VIR par exemple"
				$bank_line_id = $acc->addline($paiement->datepaye,
				$paiement->paiementid,
				$label,
				$totalpaiement,
				$paiement->num_paiement,
	      															'',
				$user,
				$_POST['chqemetteur'],
				$_POST['chqbank']);

				// Mise a jour fk_bank dans llx_paiement.
				// On connait ainsi le paiement qui a g�n�r� l'�criture bancaire
				if ($bank_line_id > 0)
				{
					$paiement->update_fk_bank($bank_line_id);
					// Mise a jour liens (pour chaque facture concern�es par le paiement)
					foreach ($paiement->amounts as $key => $value)
					{
						$facid = $key;
						$fac = new Facture($db);
						$fac->fetch($facid);
						$fac->fetch_client();
						$acc->add_url_line($bank_line_id,
						$paiement_id,
						DOL_URL_ROOT.'/compta/paiement/fiche.php?id=',
		        									 '(paiement)',
		        									 'payment');
						$acc->add_url_line($bank_line_id,
						$fac->client->id,
						DOL_URL_ROOT.'/compta/fiche.php?socid=',
						$fac->client->nom,
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
			$error++;
		}

		if ($error == 0)
		{
			$loc = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$paiement_id;
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

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * View
 */

llxHeader();

$html=new Form($db);
$facturestatic=new Facture($db);


if ($_GET['action'] == 'create' || $_POST['action'] == 'confirm_paiement' || $_POST['action'] == 'add_paiement')
{
	$facture = new Facture($db);
	$result=$facture->fetch($facid);

	if ($result >= 0)
	{
		$facture->fetch_client();

		$title='';
		if ($facture->type != 2) $title.=$langs->trans("EnterPaymentReceivedFromCustomer");
		if ($facture->type == 2) $title.=$langs->trans("EnterPaymentDueToCustomer");
		print_fiche_titre($title);

		// Bouchon
		if ($facture->type == 2)
		{
			print $langs->trans("FeatureNotYetAvailable");
			llxFooter();
			exit;
		}

		// Initialize data for confirmation (this is used because data can be change during confirmation)
		if ($_POST["action"] == 'add_paiement')
		{
			$i=0;

			$formquestion[$i++]=array('type' => 'hidden','name' => 'facid', 'value' => $facture->id);
			$formquestion[$i++]=array('type' => 'hidden','name' => 'socid', 'value' => $facture->socid);
			$formquestion[$i++]=array('type' => 'hidden','name' => 'type',  'value' => $facture->type);
		}

		print '<form name="add_paiement" action="paiement.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_paiement">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';
		print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
		print '<input type="hidden" name="type" value="'.$facture->type.'">';

		print '<table class="border" width="100%">';

		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">'.$facture->client->getNomUrl(4)."</td></tr>\n";

		// Date payment
		print '<tr><td>'.$langs->trans('Date').'</td><td>';
		$datepayment = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		$datepayment= ($datepayment == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0) : $datepayment);
		$html->select_date($datepayment,'','','',0,"add_paiement");
		print '</td>';
		print '<td>'.$langs->trans('Comments').'</td></tr>';

		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
		$html->select_types_paiements(empty($_POST['paiementid'])?'':$_POST['paiementid'],'paiementid');
		print "</td>\n";

		print '<td rowspan="5" valign="top">';
		print '<textarea name="comment" wrap="soft" cols="60" rows="'.ROWS_4.'">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td></tr>';

		print '<tr>';
		if ($conf->banque->enabled)
		{
			if ($facture->type != 2) print '<td>'.$langs->trans('AccountToCredit').'</td>';
			if ($facture->type == 2) print '<td>'.$langs->trans('AccountToDebit').'</td>';
			print '<td>';
			$html->select_comptes(empty($_POST['accountid'])?'':$_POST['accountid'],'accountid',0,'',1);
			print '</td>';
		}
		else
		{
			print '<td colspan="2">&nbsp;</td>';
		}
		print "</tr>\n";

		print '<tr><td>'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</td>';
		print '<td><input name="num_paiement" type="text" value="'.(empty($_POST['num_paiement'])?'':$_POST['num_paiement']).'"></td></tr>';

		print '<tr><td>'.$langs->trans('CheckTransmitter');
		print ' <em>('.$langs->trans("ChequeMaker").')</em>';
		print '</td>';
		print '<td><input name="chqemetteur" size="30" type="text" value="'.(empty($_POST['chqemetteur'])?$facture->client->nom:$_POST['chqemetteur']).'"></td></tr>';

		print '<tr><td>'.$langs->trans('Bank');
		print ' <em>('.$langs->trans("ChequeBank").')</em>';
		print '</td>';
		print '<td><input name="chqbank" size="30" type="text" value="'.(empty($_POST['chqbank'])?'':$_POST['chqbank']).'"></td></tr>';

		print '</table>';

		/*
		 * List of unpayed invoices
		 */
		$sql = 'SELECT f.rowid as facid, f.facnumber, f.total_ttc, f.type, ';
		$sql.= $db->pdate('f.datef').' as df, ';
		$sql.= ' sum(pf.amount) as am';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
		$sql.= ' WHERE f.fk_soc = '.$facture->socid;
		$sql.= ' AND f.paye = 0';
		$sql.= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
		if ($facture->type != 2)
		{
			$sql .= ' AND type in (0,1,3)';	// Standard invoice, replacement, deposit
		}
		else
		{
			$sql .= ' AND type = 2';		// If paying back a credit note, we show all credit notes
		}
		$sql .= ' GROUP BY f.facnumber';
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			if ($num > 0)
			{

				$i = 0;
				//print '<tr><td colspan="3">';
				print '<br>';
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Invoice').'</td>';
				print '<td align="center">'.$langs->trans('Date').'</td>';
				print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
				print '<td align="right">'.$langs->trans('Received').'</td>';
				print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
				print '<td align="right">'.$langs->trans('PaymentAmount').'</td>';
				print '<td align="right">&nbsp;</td>';
				print "</tr>\n";

				$var=True;
				$total=0;
				$totalrecu=0;
				$totalrecucreditnote=0;
				$totalrecudeposits=0;

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					$facturestatic->ref=$objp->facnumber;
					$facturestatic->id=$objp->facid;
					$facturestatic->type=$objp->type;

					$creditnotes=$facturestatic->getSumCreditNotesUsed();
					$deposits=$facturestatic->getSumDepositsUsed();

					print '<tr '.$bc[$var].'>';

					print '<td>';
					print $facturestatic->getNomUrl(1,'');
					print "</td>\n";

					// Date
					print '<td align="center">'.dol_print_date($objp->df,'day')."</td>\n";

					// Prix
					print '<td align="right">'.price($objp->total_ttc).'</td>';

					// Recu
					print '<td align="right">'.price($objp->am);
					if ($creditnotes) print '+'.price($creditnotes);
					if ($deposits) print '+'.price($deposits);
					print '</td>';

					// Reste a payer
					print '<td align="right">'.price(price2num($objp->total_ttc - $objp->am - $creditnotes - $deposits,'MT')).'</td>';

					// Montant
					print '<td align="right">';
					$namef = 'amount_'.$objp->facid;
					print '<input type="text" size="8" name="'.$namef.'" value="'.$_POST[$namef].'">';
					print "</td>";

					// Warning
					print '<td align="center" width="16">';
					if ($amounts[$facturestatic->id] && $amounts[$facturestatic->id] > $amountsresttopay[$facturestatic->id])
					{
						print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
					}
					print '</td>';


					print "</tr>\n";

					$total+=$objp->total;
					$total_ttc+=$objp->total_ttc;
					$totalrecu+=$objp->am;
					$totalrecucreditnote+=$creditnotes;
					$totalrecudeposits+=$deposits;
					$i++;
				}
				if ($i > 1)
				{
					// Print total
					print '<tr class="liste_total">';
					print '<td colspan="2" align="left">'.$langs->trans('TotalTTC').'</td>';
					print '<td align="right"><b>'.price($total_ttc).'</b></td>';
					print '<td align="right"><b>'.price($totalrecu);
					if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
					if ($totalrecudeposits) print '+'.price($totalrecudeposits);
					print '</b></td>';
					print '<td align="right"><b>'.price(price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits,'MT')).'</b></td>';
					print '<td align="center">&nbsp;</td>';
					print '<td align="center">&nbsp;</td>';
					print "</tr>\n";
				}
				print "</table>";
				//print "</td></tr>\n";
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}


		// Bouton Enregistrer
		if ($_POST["action"] != 'add_paiement')
		{
//			print '<tr><td colspan="3" align="center">';
			print '<br><center><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
//			print '</td></tr>';
		}



		// Message d'erreur
		if ($fiche_erreur_message)
		{
			print $fiche_erreur_message;
		}

		// Formulaire confirmation
		if ($_POST["action"] == 'add_paiement')
		{
			print '<br>';
			$text=$langs->trans('ConfirmCustomerPayment',$totalpaiement,$langs->trans("Currency".$conf->monnaie));
			$html->form_confirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type,$langs->trans('ReceivedCustomersPayments'),$text,'confirm_paiement',$formquestion);
		}

		print "</form>\n";
	}
}


/**
 *  \brief      Affichage de la liste des paiements
 */
if (! $_GET['action'] && ! $_POST['action'])
{
	if ($page == -1) $page = 0 ;
	$limit = $conf->liste_limit;
	$offset = $limit * $page ;

	if (! $sortorder) $sortorder='DESC';
	if (! $sortfield) $sortfield='p.datep';

	$sql = 'SELECT '.$db->pdate('p.datep').' as dp, p.amount, f.amount as fa_amount, f.facnumber';
	$sql .=', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'c_paiement as c';
	$sql .= ' WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id';
	if ($socid)
	{
		$sql .= ' AND f.fk_soc = '.$socid;
	}

	$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
	$sql .= $db->plimit( $limit +1 ,$offset);
	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$var=True;

		print_barre_liste($langs->trans('Payments'), $page, 'paiement.php','',$sortfield,$sortorder,'',$num);
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Invoice'),'paiement.php','facnumber','','','',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Date'),'paiement.php','dp','','','',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Type'),'paiement.php','libelle','','','',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Amount'),'paiement.php','fa_amount','','','align="right"',$sortfield,$sortorder);
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td><a href="facture.php?facid='.$objp->facid.'">'.$objp->facnumber."</a></td>\n";
			print '<td>'.dol_print_date($objp->dp)."</td>\n";
			print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
			print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;</td>';
			print '</tr>';
			$i++;
		}
		print '</table>';
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
