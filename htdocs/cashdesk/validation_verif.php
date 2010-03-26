<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2009 Laurent Destailleur <eldy@uers.sourceforge.net>
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

require ('../master.inc.php');
require (DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php');
require (DOL_DOCUMENT_ROOT.'/cashdesk/classes/Facturation.class.php');
require (DOL_DOCUMENT_ROOT.'/compta/facture/facture.class.php');
require (DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');
require (DOL_DOCUMENT_ROOT.'/paiement.class.php');

$obj_facturation = unserialize ($_SESSION['serObjFacturation']);
unset ($_SESSION['serObjFacturation']);

switch ( $_GET['action'] )
{

	default:

		$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=validation';
		break;


	case 'valide_achat':

		$company=new Societe($db);
		$company->fetch($conf->global->CASHDESK_ID_THIRDPARTY);

		$invoice=new Facture($db);
		$invoice->date=dol_now('tzserver');
		$invoice->type=0;
		$num=$invoice->getNextNumRef($company);

		$obj_facturation->num_facture($num);

		$obj_facturation->mode_reglement ($_POST['hdnChoix']);

		// Si paiement autre qu'en especes, montant encaisse = prix total
		$mode_reglement = $obj_facturation->mode_reglement();
		if ( $mode_reglement != 'ESP' ) {
			$montant = $obj_facturation->prix_total_ttc();
		} else {
			$montant = $_POST['txtEncaisse'];
		}

		if ( $mode_reglement != 'DIF') {
			$obj_facturation->montant_encaisse ($montant);

			//Determination de la somme rendue
			$total = $obj_facturation->prix_total_ttc ();
			$encaisse = $obj_facturation->montant_encaisse();

			$obj_facturation->montant_rendu ( $encaisse - $total );
		} else {
			$obj_facturation->paiement_le ($_POST['txtDatePaiement']);
		}

		$redirection = 'affIndex.php?menu=validation';
		break;


	case 'retour':

		$redirection = 'affIndex.php?menu=facturation';
		break;


	case 'valide_facture':
		
		$now=dol_now('tzserver');
		
		// Recuperation de la date et de l'heure
		$date = dol_print_date($now,'day');
		$heure = dol_print_date($now,'hour');

		$note = '';
		if (! is_object($obj_facturation))
		{
			dol_print_error('','Empty context');
			exit;
		}

		switch ( $obj_facturation->mode_reglement() )
		{
			case 'DIF':
				$mode_reglement_id = 0;
				//$cond_reglement_id = dol_getIdFromCode($db,'RECEP','cond_reglement','code','rowid')
				$cond_reglement_id = 0;
				break;
			case 'ESP':
				$mode_reglement_id = dol_getIdFromCode($db,'LIQ','c_paiement');
				$cond_reglement_id = 0;
				$note .= $langs->trans("Cash")."\n";
				$note .= $langs->trans("Received").' : '.$obj_facturation->montant_encaisse()." ".$conf->monnaie."\n";
				$note .= $langs->trans("Rendu").' : '.$obj_facturation->montant_rendu()." ".$conf->monnaie."\n";
				$note .= "\n";
				$note .= '--------------------------------------'."\n\n";
				break;
			case 'CB':
				$mode_reglement_id = dol_getIdFromCode($db,'CB','c_paiement');
				$cond_reglement_id = 0;
				break;
			case 'CHQ':
				$mode_reglement_id = dol_getIdFromCode($db,'CHQ','c_paiement');
				$cond_reglement_id = 0;
				break;
		}
		if (empty($mode_reglement_id)) $mode_reglement_id=0;	// If mode_reglement_id not found
		if (empty($cond_reglement_id)) $cond_reglement_id=0;	// If cond_reglement_id not found
		$note .= $_POST['txtaNotes'];
		dol_syslog("obj_facturation->mode_reglement()=".$obj_facturation->mode_reglement()." mode_reglement_id=".$mode_reglement_id." cond_reglement_id=".$cond_reglement_id);


		$error=0;


		$db->begin();

		$user->id=$_SESSION['uid'];
		$user->fetch();
		$user->getrights();

		$invoice=new Facture($db,$conf_fksoc);


		// Recuperation de la liste des articles du panier
		$res=$db->query ('
				SELECT fk_article, qte, fk_tva, remise_percent, remise, total_ht, total_ttc
				FROM '.MAIN_DB_PREFIX.'tmp_caisse
				WHERE 1');
		$ret=array(); $i=0;
		while ( $tab = $db->fetch_array($res) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
		$tab_liste = $ret;
		// Loop on each product
		for ($i = 0; $i < count ($tab_liste); $i++)
		{
			// Recuperation de l'article
			$res = $db->query (
			'SELECT label, tva_tx, price
					FROM '.MAIN_DB_PREFIX.'product
					WHERE rowid = '.$tab_liste[$i]['fk_article']);
			$ret=array();
			$tab = $db->fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_article = $ret;

			$res = $db->query (
			'SELECT taux
					FROM '.MAIN_DB_PREFIX.'c_tva
					WHERE rowid = '.$tab_liste[$i]['fk_tva']);
			$ret=array();
			$tab = $db->fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_tva = $ret;

			$invoiceline=new FactureLigne($db);
			$invoiceline->fk_product=$tab_liste[$i]['fk_article'];
			$invoiceline->desc=$tab_article['label'];
			$invoiceline->tva_tx=empty($tab_tva['taux'])?0:$tab_tva['taux'];	// works even if vat_rate is ''
			//$invoiceline->tva_tx=$tab_tva['taux'];
			$invoiceline->qty=$tab_liste[$i]['qte'];
			$invoiceline->remise_percent=$tab_liste[$i]['remise_percent'];
			$invoiceline->price=$tab_article['price'];
			$invoiceline->subprice=$tab_article['price'];
			$invoiceline->total_ht=$tab_liste[$i]['total_ht'];
			$invoiceline->total_ttc=$tab_liste[$i]['total_ttc'];
			$invoiceline->total_tva=($tab_liste[$i]['total_ttc']-$tab_liste[$i]['total_ht']);
			$invoice->lignes[]=$invoiceline;
		}

		$invoice->socid=$conf_fksoc;
		$invoice->date_creation=$now;
		$invoice->date=$now;
		$invoice->date_lim_reglement=0;
		$invoice->total_ht=$obj_facturation->prix_total_ht();
		$invoice->total_tva=$obj_facturation->montant_tva();
		$invoice->total_ttc=$obj_facturation->prix_total_ttc();
		$invoice->note=$note;
		$invoice->cond_reglement_id=$cond_reglement_id;
		$invoice->mode_reglement_id=$mode_reglement_id;
		//print "c=".$invoice->cond_reglement_id." m=".$invoice->mode_reglement_id; exit;

		// Si paiement differe ...
		if ( $obj_facturation->mode_reglement() == 'DIF' )
		{
			$resultcreate=$invoice->create($user,0,dol_stringtotime($obj_facturation->paiement_le()));
			if ($resultcreate > 0)
			{
				$resultvalid=$invoice->set_valid($user,$conf_fksoc,$obj_facturation->num_facture());
			}
			else 
			{
				$error++;
			}
			
			$id = $invoice->id;
		}
		else
		{
			$resultcreate=$invoice->create($user,0,0);
			if ($resultcreate > 0)
			{
				$resultvalid=$invoice->set_valid($user,$conf_fksoc,$obj_facturation->num_facture());

				$id = $invoice->id;
	
				// Add the payment
				$payment=new Paiement($db);
				$payment->datepaye=$now;
				$payment->bank_account=$conf_fkaccount;
				$payment->amounts[$invoice->id]=$obj_facturation->prix_total_ttc();
				$payment->note=$langs->trans("Payment").' '.$langs->trans("Invoice").' '.$obj_facturation->num_facture();
				$payment->paiementid=$invoice->mode_reglement_id;
				$payment->num_paiement='';
	
				$paiement_id = $payment->create($user);
				if ($paiement_id > 0)
				{
					// Ajout d'une ecriture sur le compte bancaire
					if ($conf->banque->enabled)
					{
						$bankaccountid=0;
						if ( $obj_facturation->mode_reglement() == 'ESP' )
						{
							$bankaccountid=$conf_fkaccount_cash;
						}
						if ( $obj_facturation->mode_reglement() == 'CHQ' )
						{
							$bankaccountid=$conf_fkaccount_cheque;
						}
						if ( $obj_facturation->mode_reglement() == 'CB' )
						{
							$bankaccountid=$conf_fkaccount_cb;
						}
	
						if ($bankaccountid > 0)
						{
							// Insertion dans llx_bank
							$label = "(CustomerInvoicePayment)";
							$acc = new Account($db, $bankaccountid);
	
							$bank_line_id = $acc->addline($payment->datepaye,
							$payment->paiementid,	// Payment mode id or code ("CHQ or VIR for example")
							$label,
							$obj_facturation->prix_total_ttc(),
							$payment->num_paiement,
				      		'',
							$user,
							'',
							'');
	
							// Mise a jour fk_bank dans llx_paiement.
							// On connait ainsi le paiement qui a genere l'ecriture bancaire
							if ($bank_line_id > 0)
							{
								$payment->update_fk_bank($bank_line_id);
								// Mise a jour liens (pour chaque facture concernees par le paiement)
								foreach ($payment->amounts as $key => $value)
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
				}
				else 
				{
					$error++;
				}
			}
			else
			{
				$error++;
			}
		}

		if (! $error)
		{
			$db->commit();
			$redirection = 'affIndex.php?menu=validation_ok&facid='.$id;	// Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		}
		else
		{
			$db->rollback();
			$redirection = 'affIndex.php?facid='.$id.'&mesg=ErrorFailedToCreateInvoice';	// Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		}
		break;

		// End of case: valide_facture
}



$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

header ('Location: '.$redirection);
?>
