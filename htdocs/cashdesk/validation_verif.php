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
require (DOL_DOCUMENT_ROOT.'/Facture.class.php');

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

		// Recuperation de la date et de l'heure
		$date = date ('Y-m-d');
		$heure = date ('H:i:s');

		$note = '';

		switch ( $obj_facturation->mode_reglement() )
		{
			case 'DIF':
				$mode_reglement_id = 0;
				//$cond_reglement_id = dol_getIdFromCode($db,'RECEP','cond_reglement','code','rowid')
				$cond_reglement_id = 0;
				break;
			case 'ESP':
				$mode_reglement_id = dol_getIdFromCode($db,$obj_facturation->mode_reglement(),'c_paiement');
				$cond_reglement_id = 0;
				$note .= $langs->trans("Cash")."\n";
				$note .= $langs->trans("Received").' : '.$obj_facturation->montant_encaisse()." ".$conf->monnaie."\n";
				$note .= $langs->trans("Rendu").' : '.$obj_facturation->montant_rendu()." ".$conf->monnaie."\n";
				$note .= "\n";
				$note .= '--------------------------------------'."\n\n";
				break;
			case 'CB':
				$mode_reglement_id = dol_getIdFromCode($db,$obj_facturation->mode_reglement(),'c_paiement');
				$cond_reglement_id = 0;
				break;
			case 'CHQ':
				$mode_reglement_id = dol_getIdFromCode($db,$obj_facturation->mode_reglement(),'c_paiement');
				$cond_reglement_id = 0;
				break;
		}

		// ... on termine la note
		$note .= $_POST['txtaNotes'];



		$sql->begin();

		$user->id=$_SESSION['uid'];
		$user->fetch();
		$user->getrights();

		$now=dol_now('tzserver');
		$invoice=new Facture($sql,$conf_fksoc);


		// Recuperation de la liste des articles du panier
		$res=$sql->query ('
				SELECT fk_article, qte, fk_tva, remise_percent, remise, total_ht, total_ttc
				FROM '.MAIN_DB_PREFIX.'tmp_caisse
				WHERE 1');
		$ret=array(); $i=0;
		while ( $tab = $sql->fetch_array($res) )
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
			$res = $sql->query (
			'SELECT label, tva_tx, price
					FROM '.MAIN_DB_PREFIX.'product
					WHERE rowid = '.$tab_liste[$i]['fk_article']);
			$ret=array();
			$tab = $sql->fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_article = $ret;

			$res = $sql->query (
			'SELECT taux
					FROM '.MAIN_DB_PREFIX.'c_tva
					WHERE rowid = '.$tab_liste[$i]['fk_tva']);
			$ret=array();
			$tab = $sql->fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_tva = $ret;

			$invoiceline=new FactureLigne($db);
			$invoiceline->fk_product=$tab_liste[$i]['fk_article'];
			$invoiceline->desc=$tab_article['label'];
			$invoiceline->tva_tx=$tab_tva['taux'];
			$invoiceline->qty=$tab_liste[$i]['qte'];
			$invoiceline->remise_percent=$tab_liste[$i]['remise_percent'];
			$invoiceline->price=$tab_article['price'];
			$invoiceline->subprice=$tab_article['price'];
			$invoiceline->total_ht=$tab_liste[$i]['total_ht'];
			$invoiceline->total_ttc=$tab_liste[$i]['total_ttc'];
			$invoiceline->total_tva=($tab_liste[$i]['total_ttc']-$tab_liste[$i]['total_ht']);
			$invoice->lignes[]=$invoiceline;

			// Calcul du montant de la TVA
/*			$montant_tva = $tab_liste[$i]['total_ttc'] - $tab_liste[$i]['total_ht'];
			// Calcul de la position de l'article dans la liste
			$reel = $tab_liste[$i]['reel'];
			$qte = $tab_liste[$i]['qte'];
			$stock = $reel - $qte;
			$position = $i + 1;

			// Ajout d'une entree dans le detail de la facture
			$sql->query (
			'INSERT INTO '.MAIN_DB_PREFIX.'facturedet (
							fk_facture,
							fk_product,
							description,
							tva_taux,
							qty,
							remise_percent,
							remise,
							fk_remise_except,
							subprice,
							price,
							total_ht,
							total_tva,
							total_ttc,
							date_start,
							date_end,
							info_bits,
							fk_code_ventilation,
							fk_export_compta,
							rang
						)

						VALUES (
							'.$id.",
							".$tab_liste[$i]['fk_article'].",
							'".$tab_article['label']."',
							".$tab_tva['taux'].",
							".$tab_liste[$i]['qte'].",
							".$tab_liste[$i]['remise_percent'].",
							".$tab_liste[$i]['remise'].",
							0,
							".$tab_article['price'].",
							".$tab_article['price'].",
							".$tab_liste[$i]['total_ht'].",
							".$montant_tva.",
							".$tab_liste[$i]['total_ttc'].",
							NULL,
							NULL,
							0,
							0,
							0,
							".$position.")");
		*/
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
			$result=$invoice->create($user,0,dol_stringtotime($obj_facturation->paiement_le()));
			$result=$invoice->set_valid($user,$conf_fksoc,$obj_facturation->num_facture());

			$id = $invoice->id;
		}
		else
		{
			$result=$invoice->create($user,0,0);
			$result=$invoice->set_valid($user,$conf_fksoc,$obj_facturation->num_facture());

			$id = $invoice->id;

			// Add the payment
			// TODO Manage ESP, CHQ...

			// Ajout d'une operation sur le compte de caisse, uniquement si le paiement est en especes
			if ( $obj_facturation->mode_reglement() == 'ESP' )
			{
				$sql->query (
				"INSERT INTO ".MAIN_DB_PREFIX."bank (
								datec,
								datev,
								dateo,
								amount,
								label,
								fk_account,
								fk_user_author,
								fk_type,
								rappro,
								fk_bordereau
							)

							VALUES (
								'".$date." ".$heure."',
								'".$date."',
								'".$date."',
								".$obj_facturation->prix_total_ttc().",
								'Paiement caisse facture ".$obj_facturation->num_facture()."',
								".$conf_fkaccount.",
								".$_SESSION['uid'].",
								'ESP',
								0,
								0
							)
					");

			}
			// Recuperation de l'id de l'operation nouvellement creee
			$resql=$sql->query (
			"SELECT rowid
						FROM ".MAIN_DB_PREFIX."bank
						WHERE 1
						ORDER BY rowid DESC");
			$ret=array();
			$tab = $sql->fetch_array($resql);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_id_operation = $tab;
			$id_op = $tab_id_operation['rowid'];

			// Ajout d'un nouveau paiement
			$request="INSERT INTO ".MAIN_DB_PREFIX."paiement (
							fk_facture,
							datec,
							datep,
							amount,
							fk_paiement,
							num_paiement,
							note,
							fk_bank,
							fk_user_creat,
							fk_user_modif,
							statut,
							fk_export_compta
						)

						VALUES (
							".$id.",
							'".$date." ".$heure."',
							'".$date." 12:00:00',
							".$obj_facturation->prix_total_ttc().",
							".$mode_reglement.",
			NULL,
			NULL,
			$id_op,
							".$_SESSION['uid'].",
							NULL,
							1,
							0
						)";
			$sql->query ($request);
			// Recuperation de l'id du paiement nouvellement cr�
			$resql=$sql->query (
			"SELECT rowid
					FROM ".MAIN_DB_PREFIX."paiement
					WHERE 1
					ORDER BY rowid DESC");
			$ret=array();
			$tab = $sql->fetch_array($resql);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_id_paiement = $tab;
			$id_paiement = $tab_id_paiement['rowid'];


			$sql->query (
			"INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (
							fk_paiement,
							fk_facture,
							amount
						)

						VALUES (
							".$id_paiement.",
							".$id.",
							".$obj_facturation->prix_total_ttc()."
						)
				");

		}

		$sql->commit();


		$redirection = 'affIndex.php?menu=validation_ok&facid='.$id;	// Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		break;

		// End of case: valide_facture
}



$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

header ('Location: '.$redirection);
?>
