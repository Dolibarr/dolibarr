<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
require ('include/environnement.php');
require ('classes/Facturation.class.php');

$obj_facturation = unserialize ($_SESSION['serObjFacturation']);
unset ($_SESSION['serObjFacturation']);

switch ( $_GET['action'] ) {

	default:

		$redirection = 'affIndex.php?menu=validation';
		break;

	case 'valide_achat':

		// R�cup�ration du dernier num�ro de facture
		$res = $sql->query (
		"SELECT facnumber
					FROM ".MAIN_DB_PREFIX."facture
					WHERE facnumber LIKE 'FA%'
					ORDER BY rowid DESC");

		if ( $sql->num_rows ($res) ) {

			$ret=array();
			$tab = mysql_fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
				
			$tab_num_facture = $ret;

			$tab = explode ('-', $tab_num_facture['facnumber']);
			$num_txt = $tab[1];
			$num = $num_txt + 1;

			// Formatage du num�ro sur quatre caract�res
			if ( $num < 1000 ) { $num = '0'.$num; }
			if ( $num < 100 ) { $num = '0'.$num; }
			if ( $num < 10 ) { $num = '0'.$num; }

			$obj_facturation->num_facture ('FA'.date('ym').'-'.$num);

		} else {

			$obj_facturation->num_facture ( 'FA'.date('ym').'-0001' );

		}


		$obj_facturation->mode_reglement ($_POST['hdnChoix']);

		// Si paiement autre qu'en esp�ces, montant encaiss� = prix total
		$mode_reglement = $obj_facturation->mode_reglement();
		if ( $mode_reglement != 'ESP' ) {

			$montant = $obj_facturation->prix_total_ttc();

		} else {

			$montant = $_POST['txtEncaisse'];

		}

		if ( $mode_reglement != 'DIF') {

			$obj_facturation->montant_encaisse ($montant);

			//D�termination de la somme rendue
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

		// R�cup�ration de la date et de l'heure
		$date = date ('Y-m-d');
		$heure = date ('H:i:s');

		// R�cup�ration du mode de r�glement et cr�ation de la note priv�e ...
		$note = '';

		switch ( $obj_facturation->mode_reglement() ) {

			case 'ESP':
				$mode_reglement = 4;

				$note .= 'Reglement en especes'."\n";
				$note .= 'Somme encaissee : '.$obj_facturation->montant_encaisse()." euro\n";
				$note .= 'Somme rendue : '.$obj_facturation->montant_rendu()." euro\n";
				$note .= "\n";
				$note .= '--------------------------------------'."\n\n";
				break;

			case 'CB':
				$mode_reglement = 6;
				break;

			case 'CHQ':
				$mode_reglement = 7;
				break;

		}

		// ... on termine la note
		$note .= addslashes ($_POST['txtaNotes']);

		// Si paiement differe ...
		if ( $obj_facturation->mode_reglement() == 'DIF' ) {

			// ... ajout d'une facture sans mode de r�glement, avec la date d'�ch�ance
			$sql->query (
			"INSERT INTO ".MAIN_DB_PREFIX."facture (
							facnumber,
							type,
							ref_client,
							increment,
							fk_soc,
							datec,
							datef,
							date_valid,
							paye,
							amount,
							remise_percent,
							remise_absolue,
							remise,
							close_code,
							close_note,
							tva,
							total,
							total_ttc,
							fk_statut,
							fk_user_author,
							fk_user_valid,
							fk_facture_source,
							fk_projet,
							fk_cond_reglement,
							fk_mode_reglement,
							date_lim_reglement,
							note,
							model_pdf
						) VALUES (
							'".$obj_facturation->num_facture()."',
							0,
							NULL,
							NULL,
							".$conf_fksoc.",
							'".$date." ".$heure."',
							'".$date."',
							NULL,
							0,
							".$obj_facturation->prix_total_ht().",
							0,
							0,
							0,
							NULL,
							NULL,
							".$obj_facturation->montant_tva().",
							".$obj_facturation->prix_total_ht().",
							".$obj_facturation->prix_total_ttc().",
							1,
							".$_SESSION['uid'].",
							".$_SESSION['uid'].",
							NULL,
							NULL,
							0,
							255,
							'".$obj_facturation->paiement_le()."',
							'".$note."',
							'crabe'
						);");


			// Recuperation de l'id de la facture nouvellement creee
			$resql=$sql->query ("SELECT rowid
						FROM ".MAIN_DB_PREFIX."facture
						WHERE 1
						ORDER BY rowid DESC");
			
			$ret=array();
			$tab = mysql_fetch_array($resql);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_id_facture=$ret;

			$id = $tab_id_facture['rowid'];


			// Sinon ...
		} else {

			// ... ajout d'une facture et d'un paiement
			$sql->query ("INSERT INTO ".MAIN_DB_PREFIX."facture (
							facnumber,
							type,
							ref_client,
							increment,
							fk_soc,
							datec,
							datef,
							date_valid,
							paye,
							amount,
							remise_percent,
							remise_absolue,
							remise,
							close_code,
							close_note,
							tva,
							total,
							total_ttc,
							fk_statut,
							fk_user_author,
							fk_user_valid,
							fk_facture_source,
							fk_projet,
							fk_cond_reglement,
							fk_mode_reglement,
							date_lim_reglement,
							note,
							note_public)

						VALUES (
							'".$obj_facturation->num_facture()."',
							0,
							NULL,
							NULL,
							".$conf_fksoc.",
							'".$date." ".$heure."',
							'".$date."',
							NULL,
							1,
							".$obj_facturation->prix_total_ht().",
							0,
							0,
							0,
							NULL,
							NULL,
							".$obj_facturation->montant_tva().",
							".$obj_facturation->prix_total_ht().",
							".$obj_facturation->prix_total_ttc().",
							2,
							".$_SESSION['uid'].",
							".$_SESSION['uid'].",
							NULL,
							NULL,
							1,
							".$mode_reglement.",
							'".$date."',
							'".$note."',
							NULL)
				;");


			// Recuperation de l'id de la facture nouvellement creee
			$resql=$sql->query (
			"SELECT rowid
						FROM ".MAIN_DB_PREFIX."facture
						WHERE 1
						ORDER BY rowid DESC");
			
			$ret=array();
			$tab = mysql_fetch_array($resql);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}

			$tab_id_facture = $tab;

			$id = $tab_id_facture['rowid'];



			// Ajout d'une op�ration sur le compte de caisse, uniquement si le paiement est en esp�ces
			if ( $obj_facturation->mode_reglement() == 'ESP' ) {

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
					;");

			}

			// Recuperation de l'id de l'operation nouvellement creee
			$resql=$sql->query (
			"SELECT rowid
						FROM ".MAIN_DB_PREFIX."bank
						WHERE 1
						ORDER BY rowid DESC");

			$ret=array();
			$tab = mysql_fetch_array($resql);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}

			$tab_id_operation = $tab;

			$id_op = $tab_id_operation['rowid'];


			// Ajout d'un nouveau paiement
			$sql->query (
			"INSERT INTO ".MAIN_DB_PREFIX."paiement (
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
						)");


			// R�cup�ration de l'id du paiement nouvellement cr�
 			$resql=$sql->query (
			"SELECT rowid
					FROM ".MAIN_DB_PREFIX."paiement
					WHERE 1
					ORDER BY rowid DESC");
			
			$ret=array();
			$tab = mysql_fetch_array($resql);
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
				;");

		}

		// Ajout d'un r�glement tva
		$sql->query (
		"INSERT INTO llx_facture_tva_sum (
						fk_facture,
						amount,
						tva_tx
					)

					VALUES (
						".$id.",
						".$obj_facturation->montant_tva().",
						19.6
					)
			;");


		// Recuperation de la liste des articles du panier
		$res=$sql->query ('
				SELECT fk_article, qte, fk_tva, remise_percent, remise, total_ht, total_ttc, reel
				FROM '.MAIN_DB_PREFIX.'tmp_caisse
				LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ON '.MAIN_DB_PREFIX.'tmp_caisse.fk_article = '.MAIN_DB_PREFIX.'product_stock.fk_product
				WHERE 1');
		$ret=array(); $i=0;
		while ( $tab = mysql_fetch_array($res) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}

		$tab_liste = $ret;

		for ($i = 0; $i < count ($tab_liste); $i++) {

			// Recuperation de l'article
			$res = $sql->query (
			'SELECT label, tva_tx, price
					FROM '.MAIN_DB_PREFIX.'product
					WHERE rowid = '.$tab_liste[$i]['fk_article']);
			$ret=array();
			$tab = mysql_fetch_array($res);
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
			$tab = mysql_fetch_array($res);
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$cle] = $valeur;
			}
			$tab_tva = $ret;
				
				
			// Calcul du montant de la TVA
			$montant_tva = $tab_liste[$i]['total_ttc'] - $tab_liste[$i]['total_ht'];

			// Calcul de la position de l'article dans la liste
			$position = $i + 1;


			$reel = $tab_liste[$i]['reel'];
			$qte = $tab_liste[$i]['qte'];
			$stock = $reel - $qte;

			// Mise � jour du stock
			$sql->query (
			'UPDATE '.MAIN_DB_PREFIX.'product_stock
					SET reel = '.$stock."
					WHERE fk_product = ".$tab_liste[$i]['fk_article']."
					LIMIT 1");


			// Ajout d'une entr�e dans le d�tail de la facture
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

		}

		$redirection = 'affIndex.php?menu=validation_ok&facid='.$id;	// Ajout de l'id de la facture, pour l'inclure dans un lien pointant directement vers celle-ci dans Dolibarr
		break;

}

$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

header ('Location: '.$redirection);
?>
