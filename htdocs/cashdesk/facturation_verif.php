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
		if ( $_POST['hdnSource'] != 'NULL' ) {

			// Recuperation des donnees en fonction de la source (liste d�roulante ou champ texte) ...
			if ( $_POST['hdnSource'] == 'LISTE' ) {

				$res = $sql->query('SELECT fk_product, ref, stock_propale, stock_commande, price, reel, tva_tx
								FROM '.MAIN_DB_PREFIX.'product
								LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ON '.MAIN_DB_PREFIX.'product.rowid = '.MAIN_DB_PREFIX.'product_stock.fk_product
								WHERE fk_product = '.$_POST['selProduit'].'
								;');

			} else if ( $_POST['hdnSource'] == 'REF' ) {

				$res = $sql->query('SELECT fk_product, ref, stock_propale, stock_commande, price, reel, tva_tx
								FROM '.MAIN_DB_PREFIX.'product
								LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ON '.MAIN_DB_PREFIX.'product.rowid = '.MAIN_DB_PREFIX.'product_stock.fk_product
								WHERE ref = \''.$_POST['txtRef'].'\'
								;');

			}



			// ... et enregistrement dans l'objet
			if ( $sql->num_rows ($res) ) {

				$ret=array();
				$tab = mysql_fetch_array($res);
				foreach ( $tab as $cle => $valeur )
				{
					$ret[$cle] = $valeur;
				}
				$tab = $ret;

				$obj_facturation->id( $tab['fk_product'] );
				$obj_facturation->ref( $tab['ref'] );
				$obj_facturation->stock( $tab['reel'] - $tab['stock_propale'] - $tab['stock_commande'] );
				$obj_facturation->prix( $tab['price'] );
				$obj_facturation->tva( $tab['tva_tx'] );

				// Definition du filtre pour n'afficher que le produit concern�
				if ( $_POST['hdnSource'] == 'LISTE' ) {

					$filtre = $tab['ref'];

				} else if ( $_POST['hdnSource'] == 'REF' ) {

					$filtre = $_POST['txtRef'];;

				}


				$redirection = 'affIndex.php?menu=facturation&filtre='.$filtre;

			} else {

				$obj_facturation->raz();

				if ( $_POST['hdnSource'] == 'REF' ) {

					$redirection = 'affIndex.php?menu=facturation&filtre='.$_POST['txtRef'];

				} else {

					$redirection = 'affIndex.php?menu=facturation';

				}

			}

		} else {

			$redirection = 'affIndex.php?menu=facturation';

		}

		break;

	case 'ajout_article';
	$obj_facturation->qte($_POST['txtQte']);
	$obj_facturation->tva($_POST['selTva']);
	$obj_facturation->remise_percent($_POST['txtRemise']);
	$obj_facturation->ajoutArticle();

	$redirection = 'affIndex.php?menu=facturation';
	break;

	case 'suppr_article':
		$obj_facturation->supprArticle($_GET['suppr_id']);

		$redirection = 'affIndex.php?menu=facturation';
		break;

}


$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

header ('Location: '.$redirection);
?>
