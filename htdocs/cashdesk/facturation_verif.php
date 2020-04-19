<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur <eldy@uers.sourceforge.net>
 * Copyright (C) 2018		Juanjo Menent <jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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

$action = GETPOST('action', 'alpha');

$obj_facturation = unserialize($_SESSION['serObjFacturation']);
unset($_SESSION['serObjFacturation']);


switch ($action)
{
	default:
		if ($_POST['hdnSource'] != 'NULL')
		{
			$sql = "SELECT p.rowid, p.ref, p.price, p.tva_tx, p.default_vat_code, p.recuperableonly";
			if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= ", ps.reel";
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = ".$conf_fkentrepot;
			$sql .= " WHERE p.entity IN (".getEntity('product').")";

			// Recuperation des donnees en fonction de la source (liste deroulante ou champ texte) ...
			if ($_POST['hdnSource'] == 'LISTE')
			{
				$sql .= " AND p.rowid = ".((int) GETPOST('selProduit', 'int'));
			}
			elseif ($_POST['hdnSource'] == 'REF')
			{
				$sql .= " AND p.ref = '".$db->escape(GETPOST('txtRef', 'alpha'))."'";
			}

			$result = $db->query($sql);
			if ($result)
			{
				// ... et enregistrement dans l'objet
				if ($db->num_rows($result))
				{
					$ret = array();
					$tab = $db->fetch_array($result);
					foreach ($tab as $key => $value)
					{
						$ret[$key] = $value;
					}
                    // Here $ret['tva_tx'] is vat rate of product but we want to not use the one into table but found by function

					$productid = $ret['rowid'];
					$product = new Product($db);
                    $product->fetch($productid);
                    $prod = $product;

					$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
                    $societe = new Societe($db);
					$societe->fetch($thirdpartyid);

					// Update if prices fields are defined
					$tva_tx = get_default_tva($mysoc, $societe, $product->id);
					$tva_npr = get_default_npr($mysoc, $societe, $product->id);
					if (empty($tva_tx)) $tva_npr = 0;

					$pu_ht = $prod->price;
					$pu_ttc = $prod->price_ttc;
					$price_min = $prod->price_min;
					$price_base_type = $prod->price_base_type;

					// multiprix
					if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($societe->price_level))
					{
					    $pu_ht = $prod->multiprices[$societe->price_level];
					    $pu_ttc = $prod->multiprices_ttc[$societe->price_level];
					    $price_min = $prod->multiprices_min[$societe->price_level];
					    $price_base_type = $prod->multiprices_base_type[$societe->price_level];
					    if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
					    {
					        if (isset($prod->multiprices_tva_tx[$societe->price_level])) $tva_tx = $prod->multiprices_tva_tx[$societe->price_level];
					        if (isset($prod->multiprices_recuperableonly[$societe->price_level])) $tva_npr = $prod->multiprices_recuperableonly[$societe->price_level];
					    }
					}
					elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
					{
					    require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

					    $prodcustprice = new Productcustomerprice($db);

					    $filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $societe->id);

					    $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					    if ($result >= 0)
					    {
					        if (count($prodcustprice->lines) > 0)
					        {
					            $pu_ht = price($prodcustprice->lines[0]->price);
					            $pu_ttc = price($prodcustprice->lines[0]->price_ttc);
					            $price_base_type = $prodcustprice->lines[0]->price_base_type;
					            $tva_tx = $prodcustprice->lines[0]->tva_tx;
					            if ($prodcustprice->lines[0]->default_vat_code && !preg_match('/\(.*\)/', $tva_tx)) $tva_tx .= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
					            $tva_npr = $prodcustprice->lines[0]->recuperableonly;
					            if (empty($tva_tx)) $tva_npr = 0;
					        }
					    }
					    else
					    {
					        setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
					    }
					}

					$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
					$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

					// if price ht is forced (ie: calculated by margin rate and cost price). TODO Why this ?
					if (!empty($price_ht)) {
					    $pu_ht = price2num($price_ht, 'MU');
					    $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
					}
					// On reevalue prix selon taux tva car taux tva transaction peut etre different
					// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
					elseif ($tmpvat != $tmpprodvat) {
					    if ($price_base_type != 'HT') {
					        $pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					    } else {
					        $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
					    }
					}

					$obj_facturation->id($ret['rowid']);
					$obj_facturation->ref($ret['ref']);
					$obj_facturation->stock($ret['reel']);
					//$obj_facturation->prix($ret['price']);
					$obj_facturation->prix($pu_ht);


					$vatrate = $tva_tx;
					$obj_facturation->vatrate = $vatrate; // Save vat rate (full text vat with code)

					// Definition du filtre pour n'afficher que le produit concerne
					if ($_POST['hdnSource'] == 'LISTE')
					{
						$filtre = $ret['ref'];
					}
					elseif ($_POST['hdnSource'] == 'REF')
					{
						$filtre = $_POST['txtRef'];
					}

					$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation&filtre='.$filtre;
				}
				else
				{
					$obj_facturation->raz();

					if ($_POST['hdnSource'] == 'REF')
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

	case 'change_thirdparty':	// We have clicked on button "Modify" a thirdparty
		$newthirdpartyid = GETPOST('CASHDESK_ID_THIRDPARTY', 'int');
		if ($newthirdpartyid > 0)
		{
		    $_SESSION["CASHDESK_ID_THIRDPARTY"] = $newthirdpartyid;
		}

		$redirection = DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation';
        break;

	case 'ajout_article':	// We have clicked on button "Add product"

		if (!empty($obj_facturation->id))	// A product was previously selected and stored in session, so we can add it
		{
		    dol_syslog("facturation_verif save vat ".$_POST['selTva']);
			$obj_facturation->qte($_POST['txtQte']);
			$obj_facturation->tva($_POST['selTva']); // id of vat. Saved so we can use it for next product
			$obj_facturation->remisePercent($_POST['txtRemise']);
			$obj_facturation->ajoutArticle(); // This add an entry into $_SESSION['poscart']
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
