<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur <eldy@uers.sourceforge.net>
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
 *	\file       htdocs/cashdesk/facturation_verif.php
 *	\ingroup    cashdesk
 *	\brief      facturation_verif.php
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/class/Facturation.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$obj_facturation = unserialize($_SESSION['serObjFacturation']);
unset ($_SESSION['serObjFacturation']);


switch ( $_GET['action'] )
{
	default:
		if ( $_POST['hdnSource'] != 'NULL' )
		{
			$sql = "SELECT p.rowid, p.ref, p.price, p.tva_tx, p.recuperableonly";
			if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql.= ", ps.reel";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = ".$conf_fkentrepot;
			$sql.= " WHERE p.entity IN (".getEntity('product', 1).")";

			// Recuperation des donnees en fonction de la source (liste deroulante ou champ texte) ...
			if ( $_POST['hdnSource'] == 'LISTE' )
			{
				$sql.= " AND p.rowid = ".$_POST['selProduit'];
			}
			else if ( $_POST['hdnSource'] == 'REF' )
			{
				$sql.= " AND p.ref = '".$_POST['txtRef']."'";
			}

			$result = $db->query($sql);

			if ($result)
			{
				// ... et enregistrement dans l'objet
				if ( $db->num_rows($result) )
				{
					$ret=array();
					$tab = $db->fetch_array($result);
					foreach ( $tab as $key => $value )
					{
						$ret[$key] = $value;
					}
                    // Here $ret['tva_tx'] is vat rate of product but we want to not use the one into table but found by function
                    
					$productid = $ret['rowid'];
					$product = new Product($db);
                    $product->fetch($productid);

					$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
                    $societe = new Societe($db);
					$societe->fetch($thirdpartyid);

					$tva_tx = get_default_tva($mysoc,$societe,$productid);
					$tva_npr = get_default_npr($mysoc,$societe,$productid);
					if (empty($tva_tx)) $tva_npr=0;
					dol_syslog('tva_tx='.$tva_tx.'-tva_npr='.$tva_npr);
					
					if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($societe->price_level))
					{
						if(isset($product->multiprices[$societe->price_level]))
						{
							$ret['price'] = $product->multiprices[$societe->price_level];
							$ret['price_ttc'] = $product->multiprices_ttc[$societe->price_level];
							// $product->multiprices_min[$societe->price_level];
							// $product->multiprices_min_ttc[$societe->price_level];
							// $product->multiprices_base_type[$societe->price_level];
							if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
							{
							    if (isset($prod->multiprices_tva_tx[$societe->price_level])) $tva_tx=$prod->multiprices_tva_tx[$societe->price_level];
							    if (isset($prod->multiprices_recuperableonly[$societe->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$societe->price_level];
							    if (empty($tva_tx)) $tva_npr=0;
							}
						}
					}

					$ret['tva_tx'] = $tva_tx;
					$ret['tva_npr'] = $tva_npr;
                    //var_dump('tva_tx='.$ret['tva_tx'].'-tva_npr='.$ret['tva_npr'].'-'.$conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL);exit;
                    
					$obj_facturation->id($ret['rowid']);
					$obj_facturation->ref($ret['ref']);
					$obj_facturation->stock($ret['reel']);
					$obj_facturation->prix($ret['price']);
					
					// Use $ret['tva_tx'] / ret['tva_npr'] to find vat id
					$vatrowid = null;
					$sqlfindvatid = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'c_tva';
					$sqlfindvatid.= ' WHERE taux = '.$ret['tva_tx'].' AND recuperableonly = '.(int) $ret['tva_npr'];
					$sqlfindvatid.= ' AND fk_pays = '.$mysoc->country_id;
					$resqlfindvatid=$db->query($sqlfindvatid);
					if ($resqlfindvatid)
					{
					    $obj = $db->fetch_object($resqlfindvatid);
					    if ($obj) $vatrowid = $obj->rowid;
					}
					else dol_print_error($db);
					
					dol_syslog("save vatrowid=".$vatrowid);
					$obj_facturation->tva($vatrowid);     // Save vat it for next use

					// Definition du filtre pour n'afficher que le produit concerne
					if ( $_POST['hdnSource'] == 'LISTE' )
					{
						$filtre = $ret['ref'];
					}
					else if ( $_POST['hdnSource'] == 'REF' )
					{
						$filtre = $_POST['txtRef'];
					}

					$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation&filtre='.$filtre;
				}
				else
				{
					$obj_facturation->raz();

					if ( $_POST['hdnSource'] == 'REF' )
					{
						$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation&filtre='.$_POST['txtRef'];
					}
					else
					{
						$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation';
					}
				}
			}
			else
			{
				dol_print_error($db);
			}
		}
		else
		{
			$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation';
		}

		break;

	case 'ajout_article':	// We have clicked on button "Add product"

		if (! empty($obj_facturation->id))	// A product was previously selected and stored in session, so we can add it
		{
		    dol_syslog("facturation_verif save vat ".$_POST['selTva']);
			$obj_facturation->qte($_POST['txtQte']);
			$obj_facturation->tva($_POST['selTva']);                 // Save VAT selected so we can use it for next product
			$obj_facturation->remisePercent($_POST['txtRemise']);
			$obj_facturation->ajoutArticle();	// This add an entry into $_SESSION['poscart']
			// We update prixTotalTtc
			 
		}

		$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation';
		break;

	case 'suppr_article':
		$obj_facturation->supprArticle($_GET['suppr_id']);

		$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation';
		break;

}

// We saved object obj_facturation
$_SESSION['serObjFacturation'] = serialize($obj_facturation);
//var_dump($_SESSION['serObjFacturation']);
header('Location: '.$redirection);
exit;

