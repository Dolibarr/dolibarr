<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2016	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2014-2015 	Philippe Grand 		    <philippe.grand@atoo-net.com>
 * Copyright (C) 2014		Ion agorria				<ion@agorria.com>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2016		Ferran Marcet			<fmarcet@2byte.es>
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
 * \file htdocs/product/price.php
 * \ingroup product
 * \brief Page to show product prices
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_parser.class.php';

if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
	require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

	$prodcustprice = new Productcustomerprice($db);
}

$langs->load("products");
$langs->load("bills");
$langs->load("companies");

$mesg=''; $error=0; $errors=array();

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$eid = GETPOST('eid', 'int');

$search_soc = GETPOST('search_soc');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid = $user->societe_id;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

if ($id > 0 || ! empty($ref))
{
	$object = new Product($db);
	$object->fetch($id, $ref);
}

// Clean param
if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($conf->global->PRODUIT_MULTIPRICES_LIMIT)) $conf->global->PRODUIT_MULTIPRICES_LIMIT = 5;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productpricecard','globalcard'));


/*
 * Actions
 */

if ($cancel) $action='';

$parameters=array('id'=>$id, 'ref'=>$ref);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {
        $search_soc = '';        
    }
    
    if ($action == 'setlabelsellingprice' && $user->admin)
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
        $keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.GETPOST('pricelevel');
        dolibarr_set_const($db, $keyforlabel, GETPOST('labelsellingprice','alpha'), 'chaine', 0, '', $conf->entity);
        $action = '';
    }
    
	if (($action == 'update_vat') && !$cancel && ($user->rights->produit->creer || $user->rights->service->creer))
	{
	    $tva_tx_txt = GETPOST('tva_tx', 'alpha');           // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

	    // We must define tva_tx, npr and local taxes
	    $vatratecode = '';
	    $tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt);     // keep remove all after the numbers and dot
	    $npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
	    $localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
	    // If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
	    if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
	    {
	        // We look into database using code
	        $vatratecode=$reg[1];
	        // Get record from code
	        $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
	        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	        $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
	        $sql.= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
	        $sql.= " AND t.code ='".$vatratecode."'";
	        $resql=$db->query($sql);
	        if ($resql)
	        {
                $obj = $db->fetch_object($resql);
                $npr = $obj->recuperableonly;
                $localtax1 = $obj->localtax1;
                $localtax2 = $obj->localtax2;
                $localtax1_type = $obj->localtax1_type;
                $localtax2_type = $obj->localtax2_type;
	        }
	    }

	    $object->default_vat_code = $vatratecode;
	    $object->tva_tx = $tva_tx;
	    $object->tva_npr = $npr;
	    $object->localtax1_tx = $localtax1;
	    $object->localtax2_tx = $localtax2;
	    $object->localtax1_type = $localtax1_type;
	    $object->localtax2_type = $localtax2_type;

	    $db->begin();

	    $resql = $object->update($object->id, $user);
	    if (! $resql)
	    {
	        $error++;
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	    
	    if ($error)
	    {
	        //$localtaxarray=array('0'=>$localtax1_type,'1'=>$localtax1,'2'=>$localtax2_type,'3'=>$localtax2);
	        $localtaxarray=array();    // We do not store localtaxes into product, we will use instead the "vat code" to retreive them.
	        $object->updatePrice(0, $object->price_base_type, $user, $tva_tx, '', 0, $npr, 0, 0, $localtaxarray, $vatratecode);
	    }
	    
	    if (! $error)
	    {
	        $db->commit();
	    }
	    else
	    {
	        $db->rollback();
	    }
	    
	    $action='';
	}
	    
	if (($action == 'update_price') && !$cancel && $object->getRights()->creer)
    {
		$error = 0;
		$pricestoupdate = array();

		$psq = GETPOST('psqflag');
		$psq = empty($newpsq) ? 0 : $newpsq;
		$maxpricesupplier = $object->min_recommended_price();

		if (!empty($conf->dynamicprices->enabled)) {
			$object->fk_price_expression = empty($eid) ? 0 : $eid; //0 discards expression

			if ($object->fk_price_expression != 0) {
				//Check the expression validity by parsing it
				$priceparser = new PriceParser($db);

				if ($priceparser->parseProduct($object) < 0) {
					$error ++;
					setEventMessages($priceparser->translatedError(), null, 'errors');
				}
			}
		}

		// Multiprices
		if (! $error && ! empty($conf->global->PRODUIT_MULTIPRICES)) {

			$newprice = GETPOST('price', 'array');
			$newprice_min = GETPOST('price_min', 'array');
			$newpricebase = GETPOST('multiprices_base_type', 'array');
			$newvattx = GETPOST('tva_tx', 'array');
			$newvatnpr = GETPOST('tva_npr', 'array');
			$newlocaltax1_tx = GETPOST('localtax1_tx', 'array');
			$newlocaltax1_type = GETPOST('localtax1_type', 'array');
			$newlocaltax2_tx = GETPOST('localtax2_tx', 'array');
			$newlocaltax2_type = GETPOST('localtax2_type', 'array');

			//Shall we generate prices using price rules?
			$object->price_autogen = GETPOST('usePriceRules') == 'on';

			for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++) 
			{
				if (!isset($newprice[$i])) {
					continue;
				}

				$tva_tx_txt = $newvattx[$i];
				
				$vatratecode = '';
        	    $tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt);     // keep remove all after the numbers and dot
        	    $npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
				$localtax1 = $newlocaltax1_tx[$i];
				$localtax1_type = $newlocaltax1_type[$i];
				$localtax2 = $newlocaltax2_tx[$i];
				$localtax2_type = $newlocaltax2_type[$i];
        	    if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
        	    {
        	        // We look into database using code
        	        $vatratecode=$reg[1];
        	        // Get record from code
        	        $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
        	        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
        	        $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
        	        $sql.= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
        	        $sql.= " AND t.code ='".$vatratecode."'";
        	        $resql=$db->query($sql);
        	        if ($resql)
        	        {
        	            $obj = $db->fetch_object($resql);
        	            $npr = $obj->recuperableonly;
        	            $localtax1 = $obj->localtax1;
        	            $localtax2 = $obj->localtax2;
        	            $localtax1_type = $obj->localtax1_type;
        	            $localtax2_type = $obj->localtax2_type;
        	        }        	        
        	    }

				$pricestoupdate[$i] = array(
					'price' => $newprice[$i],
					'price_min' => $newprice_min[$i],
					'price_base_type' => $newpricebase[$i],
				    'default_vat_code' => $vatratecode,
					'vat_tx' => $tva_tx,                                                                                       // default_vat_code should be used in priority in a future
					'npr' => $npr,                                                                                             // default_vat_code should be used in priority in a future
				    'localtaxes_array' => array('0'=>$localtax1_type, '1'=>$localtax1, '2'=>$localtax2_type, '3'=>$localtax2)  // default_vat_code should be used in priority in a future
				);

				//If autogeneration is enabled, then we only set the first level
				if ($object->price_autogen) {
					break;
				}
			}
		}
		elseif (! $error)
		{
			$tva_tx_txt = GETPOST('tva_tx', 'alpha');           // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

			$vatratecode = '';
		    // We must define tva_tx, npr and local taxes
		    $tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt);     // keep remove all after the numbers and dot
		    $npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
		    $localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
		    // If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
		    if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
		    {
		        // We look into database using code
		        $vatratecode=$reg[1];
		        // Get record from code
		        $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
		        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		        $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
		        $sql.= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
		        $sql.= " AND t.code ='".$vatratecode."'";
		        $resql=$db->query($sql);
		        if ($resql)
		        {
		            $obj = $db->fetch_object($resql);
		            $npr = $obj->recuperableonly;
		            $localtax1 = $obj->localtax1;
		            $localtax2 = $obj->localtax2;
		            $localtax1_type = $obj->localtax1_type;
		            $localtax2_type = $obj->localtax2_type;

		            // If spain, we don't use the localtax found into tax record in database with same code, but using the get_localtax rule
		            if (in_array($mysoc->country_code, array('ES')))
		            {
    		            $localtax1 = get_localtax($tva_tx,1);
	   	                $localtax2 = get_localtax($tva_tx,2);
		            }
		        }
		    }
			$pricestoupdate[0] = array(
				'price' => $_POST["price"],
				'price_min' => $_POST["price_min"],
				'price_base_type' => $_POST["price_base_type"],
			    'default_vat_code' => $vatratecode,
				'vat_tx' => $tva_tx,                                                                                        // default_vat_code should be used in priority in a future
				'npr' => $npr,                                                                                              // default_vat_code should be used in priority in a future
			    'localtaxes_array' => array('0'=>$localtax1_type, '1'=>$localtax1, '2'=>$localtax2_type, '3'=>$localtax2)   // default_vat_code should be used in priority in a future
			);
		}

		if (!$error) {
			$db->begin();

			foreach ($pricestoupdate as $key => $val) {

				$newprice = $val['price'];

				if ($val['price'] < $val['price_min'] && !empty($object->fk_price_expression)) {
					$newprice = $val['price_min']; //Set price same as min, the user will not see the
				}

				$newprice = price2num($newprice, 'MU');
				$newprice_min = price2num($val['price_min'], 'MU');

				if (!empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE) && $newprice_min < $maxpricesupplier) {
					setEventMessages($langs->trans("MinimumPriceLimit", price($maxpricesupplier, 0, '', 1, - 1, - 1, 'auto')), null, 'errors');
					$error ++;
					break;
				}

				$res = $object->updatePrice($newprice, $val['price_base_type'], $user, $val['vat_tx'], $newprice_min, $key, $val['npr'], $psq, 0, $val['localtaxes_array'], $val['default_vat_code']);

				if ($res < 0) {
					$error ++;
					setEventMessages($object->error, $object->errors, 'errors');
					break;
				}
			}
		}

		if (!$error && $object->update($object->id, $user) < 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}

		if (empty($error)) {
			$action = '';
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$db->commit();
		} else {
			$action = 'edit_price';
			$db->rollback();
		}
	}


	if ($action == 'delete' && $user->rights->produit->supprimer)
	{
		$result = $object->log_price_delete($user, $_GET ["lineid"]);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	/**
	 * ***************************************************
	 * Price by quantity
	 * ***************************************************
	 */
	if ($action == 'activate_price_by_qty') { // Activating product price by quantity add a new price, specified as by quantity

		$level = GETPOST('level');

		$object->updatePrice(0, $object->price_base_type, $user, $object->tva_tx, 0, $level, $object->tva_npr, 1);
	}

	if ($action == 'edit_price_by_qty')
	{ // Edition d'un prix par quantité
		$rowid = GETPOST('rowid');
	}

	if ($action == 'update_price_by_qty')
	{ // Ajout / Mise à jour d'un prix par quantité

		// Récupération des variables
		$rowid = GETPOST('rowid');
		$priceid = GETPOST('priceid');
		$newprice = price2num(GETPOST("price"), 'MU');
		// $newminprice=price2num(GETPOST("price_min"),'MU'); // TODO : Add min price management
		$quantity = GETPOST('quantity');
		$remise_percent = price2num(GETPOST('remise_percent'));
		$remise = 0; // TODO : allow discount by amount when available on documents

		if (empty($quantity)) {
			$error ++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Qty")), null, 'errors');
		}
		if (empty($newprice)) {
			$error ++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Price")), null, 'errors');
		}
		if (! $error) {
			// Calcul du prix HT et du prix unitaire
			if ($object->price_base_type == 'TTC') {
				$price = price2num($newprice) / (1 + ($object->tva_tx / 100));
			}

			$price = price2num($newprice, 'MU');
			$unitPrice = price2num($price / $quantity, 'MU');

			// Ajout / mise à jour
			if ($rowid > 0) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . "product_price_by_qty SET";
				$sql .= " price='" . $price . "',";
				$sql .= " unitprice=" . $unitPrice . ",";
				$sql .= " quantity=" . $quantity . ",";
				$sql .= " remise_percent=" . $remise_percent . ",";
				$sql .= " remise=" . $remise;
				$sql .= " WHERE rowid = " . GETPOST('rowid');

				$result = $db->query($sql);
			} else {
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_price_by_qty (fk_product_price,price,unitprice,quantity,remise_percent,remise) values (";
				$sql .= $priceid . ',' . $price . ',' . $unitPrice . ',' . $quantity . ',' . $remise_percent . ',' . $remise . ')';

				$result = $db->query($sql);
			}
		}
	}

	if ($action == 'delete_price_by_qty')
	{
		$rowid = GETPOST('rowid');

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "product_price_by_qty";
		$sql .= " WHERE rowid = " . GETPOST('rowid');

		$result = $db->query($sql);
	}

	if ($action == 'delete_all_price_by_qty')
	{
		$priceid = GETPOST('priceid');

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "product_price_by_qty";
		$sql .= " WHERE fk_product_price = " . $priceid;

		$result = $db->query($sql);
	}

	/**
	 * ***************************************************
	 * Price by customer
	 * ****************************************************
	 */
	if ($action == 'add_customer_price_confirm' && !$cancel && ($user->rights->produit->creer || $user->rights->service->creer)) {

		$maxpricesupplier = $object->min_recommended_price();

		$update_child_soc = GETPOST('updatechildprice');

		// add price by customer
		$prodcustprice->fk_soc = GETPOST('socid', 'int');
		$prodcustprice->fk_product = $object->id;
		$prodcustprice->price = price2num(GETPOST("price"), 'MU');
		$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
		$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');

		$tva_tx_txt = GETPOST("tva_tx");
		
		$vatratecode = '';
		// We must define tva_tx, npr and local taxes
		$tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt);     // keep remove all after the numbers and dot
		$npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
		$localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
		// If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
		if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
		{
		    // We look into database using code
		    $vatratecode=$reg[1];
		    // Get record from code
		    $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
		    $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		    $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
		    $sql.= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
		    $sql.= " AND t.code ='".$vatratecode."'";
		    $resql=$db->query($sql);
		    if ($resql)
		    {
		        $obj = $db->fetch_object($resql);
		        $npr = $obj->recuperableonly;
		        $localtax1 = $obj->localtax1;
		        $localtax2 = $obj->localtax2;
		        $localtax1_type = $obj->localtax1_type;
		        $localtax2_type = $obj->localtax2_type;
		    }
		}		
		
		$prodcustprice->default_vat_code = $vatratecode;
		$prodcustprice->tva_tx = $tva_tx;
		$prodcustprice->recuperableonly = $npr;
		$prodcustprice->localtax1_tx = $localtax1;
		$prodcustprice->localtax2_tx = $localtax2;
		$prodcustprice->localtax1_type = $localtax1_type;
		$prodcustprice->localtax2_type = $localtax2_type;
		
		if (! ($prodcustprice->fk_soc > 0))
		{
		    $langs->load("errors");
		    setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ThirdParty")), null, 'errors');
		    $error++;
		    $action='add_customer_price';
		}
		if (! empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE) && $prodcustprice->price_min < $maxpricesupplier)
		{
		    $langs->load("errors");
			setEventMessages($langs->trans("MinimumPriceLimit",price($maxpricesupplier,0,'',1,-1,-1,'auto')), null, 'errors');
			$error++;
			$action='add_customer_price';
		}

		if (! $error)
		{
			$result = $prodcustprice->create($user, 0, $update_child_soc);

			if ($result < 0) {
				setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
			} else {
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			}

			$action = '';
		}
	}

	if ($action == 'delete_customer_price' && ($user->rights->produit->supprimer || $user->rights->service->supprimer))
	{
		// Delete price by customer
		$prodcustprice->id = GETPOST('lineid');
		$result = $prodcustprice->delete($user);

		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		} else {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
		}
		$action = '';
	}

	if ($action == 'update_customer_price_confirm' && !$cancel && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		$maxpricesupplier = $object->min_recommended_price();

		$update_child_soc = GETPOST('updatechildprice');

		$prodcustprice->fetch(GETPOST('lineid', 'int'));

		// update price by customer
		$prodcustprice->price = price2num(GETPOST("price"), 'MU');
		$prodcustprice->price_min = price2num(GETPOST("price_min"), 'MU');
		$prodcustprice->price_base_type = GETPOST("price_base_type", 'alpha');

		$tva_tx_txt = GETPOST("tva_tx");

		$vatratecode='';
		// We must define tva_tx, npr and local taxes
		$tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt);     // keep remove all after the numbers and dot
		$npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
		$localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
		// If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
		if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
		{
		    // We look into database using code
		    $vatratecode=$reg[1];
		    // Get record from code
		    $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
		    $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		    $sql.= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
		    $sql.= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
		    $sql.= " AND t.code ='".$vatratecode."'";
		    $resql=$db->query($sql);
		    if ($resql)
		    {
		        $obj = $db->fetch_object($resql);
		        $npr = $obj->recuperableonly;
		        $localtax1 = $obj->localtax1;
		        $localtax2 = $obj->localtax2;
		        $localtax1_type = $obj->localtax1_type;
		        $localtax2_type = $obj->localtax2_type;
		    }
		}
		
		$prodcustprice->default_vat_code = $vatratecode;
		$prodcustprice->tva_tx = $tva_tx;
		$prodcustprice->recuperableonly = $npr;
		$prodcustprice->localtax1_tx = $localtax1;
		$prodcustprice->localtax2_tx = $localtax2;
		$prodcustprice->localtax1_type = $localtax1_type;
		$prodcustprice->localtax2_type = $localtax2_type;
		
		if ($prodcustprice->price_min < $maxpricesupplier && !empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
		{
			setEventMessages($langs->trans("MinimumPriceLimit",price($maxpricesupplier,0,'',1,-1,-1,'auto')), null, 'errors');
			$error++;
			$action='update_customer_price';
		}

		if ( ! $error)
		{
			$result = $prodcustprice->update($user, 0, $update_child_soc);

			if ($result < 0) {
				setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
			} else {
				setEventMessages($langs->trans('Save'), null, 'mesgs');
			}

			$action = '';
		}
	}
}


/*
 * View
 */

$form = new Form($db);

if (! empty($id) || ! empty($ref))
{
	// fetch updated prices
	$object->fetch($id, $ref);
}

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label,16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('SellingPrices');
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('SellingPrices');
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct" . $object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
dol_fiche_head($head, 'price', $titre, 0, $picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');


print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield" width="100%">';

// MultiPrix
if (! empty($conf->global->PRODUIT_MULTIPRICES))
{
	// Price and min price are variable (depends on level of company).
	if (! empty($socid))
	{
		$soc = new Societe($db);
		$soc->id = $socid;
		$soc->fetch($socid);

		// Selling price
		print '<tr><td class="titlefield">';
		print $langs->trans("SellingPrice");
		print '</td>';
		print '<td colspan="2">';
		if ($object->multiprices_base_type[$soc->price_level] == 'TTC') {
			print price($object->multiprices_ttc[$soc->price_level]);
		} else {
			print price($object->multiprices[$soc->price_level]);
		}
		if ($object->multiprices_base_type[$soc->price_level]) {
			print ' ' . $langs->trans($object->multiprices_base_type[$soc->price_level]);
		} else {
			print ' ' . $langs->trans($object->price_base_type);
		}
		print '</td></tr>';

		// Price min
		print '<tr><td>' . $langs->trans("MinPrice") . '</td><td colspan="2">';
		if ($object->multiprices_base_type[$soc->price_level] == 'TTC')
		{
			print price($object->multiprices_min_ttc[$soc->price_level]) . ' ' . $langs->trans($object->multiprices_base_type[$soc->price_level]);
		} else {
			print price($object->multiprices_min[$soc->price_level]) . ' ' . $langs->trans(empty($object->multiprices_base_type[$soc->price_level])?'HT':$object->multiprices_base_type[$soc->price_level]);
		}
		print '</td></tr>';
        
		if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
		{
    	   // TVA
	       print '<tr><td>' . $langs->trans("VATRate") . '</td><td colspan="2">' . vatrate($object->multiprices_tva_tx[$soc->price_level], true) . '</td></tr>';
		}
		else
		{
        	// TVA
        	print '<tr><td>' . $langs->trans("VATRate") . '</td><td>';
			if ($object->default_vat_code)
	        {
	            print vatrate($object->tva_tx, true) . ' ('.$object->default_vat_code.')';
	        }
        	else print vatrate($object->tva_tx . ($object->tva_npr ? '*' : ''), true);
        	print '</td></tr>';
		}
		
	}
	else
	{
		if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
		{
    	   // We show only vat for level 1
	       print '<tr><td class="titlefield">' . $langs->trans("VATRate") . '</td>';
	       print '<td colspan="2">' . vatrate($object->multiprices_tva_tx[1], true) . '</td>';
	       print '</tr>';
		}
		else
		{
            // TVA
	        print '<tr><td class="titlefield">' . $langs->trans("VATRate") . '</td><td>';
	        if ($object->default_vat_code)
	        {
	            print vatrate($object->tva_tx, true) . ' ('.$object->default_vat_code.')';
	        }
	        else print vatrate($object->tva_tx . ($object->tva_npr ? '*' : ''), true);
	        print '</td></tr>';
		}
	    print '</table>';
	    
	    print '<br>';
	    
	    print '<table class="noborder tableforfield" width="100%">';
		print '<tr class="liste_titre"><td>';
		print $langs->trans("PriceLevel");
		if ($user->admin) print ' <a href="'.$_SERVER["PHP_SELF"].'?action=editlabelsellingprice&amp;pricelevel='.$i.'&amp;id='.$object->id.'">'.img_edit($langs->trans('EditSellingPriceLabel'),0).'</a>';
		print '</td>';
		print '<td style="text-align: right">'.$langs->trans("SellingPrice").'</td>';
		print '<td style="text-align: right">'.$langs->trans("MinPrice").'</td>';
		print '</tr>';

		$var=True;
		
		for($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
		{
		    $var = ! $var;
		    
			print '<tr '.$bc[$var].'>';

			// Label of price
			print '<td>';
			$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
			if (preg_match('/editlabelsellingprice/', $action))
			{
			    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
			    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			    print '<input type="hidden" name="action" value="setlabelsellingprice">';
			    print '<input type="hidden" name="pricelevel" value="'.$i.'">';
			    print $langs->trans("SellingPrice") . ' ' . $i.' - ';
			    print '<input size="10" class="maxwidthonsmartphone" type="text" name="labelsellingprice" value="'.$conf->global->$keyforlabel.'">';
			    print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			    print '</form>';
			}
			else
			{
			    print $langs->trans("SellingPrice") . ' ' . $i;
			    if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
			}
			print '</td>';

			if ($object->multiprices_base_type [$i] == 'TTC') {
				print '<td style="text-align: right">' . price($object->multiprices_ttc[$i]);
			} else {
				print '<td style="text-align: right">' . price($object->multiprices[$i]);
			}

			if ($object->multiprices_base_type[$i]) {
				print ' '.$langs->trans($object->multiprices_base_type [$i]).'</td>';
			} else {
				print ' '.$langs->trans($object->price_base_type).'</td>';
			}

			// Prix min
			print '<td style="text-align: right">';
			if (empty($object->multiprices_base_type[$i])) $object->multiprices_base_type[$i]="HT";
			if ($object->multiprices_base_type[$i] == 'TTC')
			{
				print price($object->multiprices_min_ttc[$i]) . ' ' . $langs->trans($object->multiprices_base_type[$i]);
			}
			else
			{
				print price($object->multiprices_min[$i]) . ' ' . $langs->trans($object->multiprices_base_type[$i]);
			}
			print '</td></tr>';

			// Price by quantity
			if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))      // TODO Fix the form included into a tr instead of a td
			{
				print '<tr><td>' . $langs->trans("PriceByQuantity") . ' ' . $i;
				print '</td><td>';

				if ($object->prices_by_qty[$i] == 1) {
					print '<table width="50%" class="border" summary="List of quantities">';

					print '<tr class="liste_titre">';
					print '<td>' . $langs->trans("PriceByQuantityRange") . ' ' . $i . '</td>';
					print '<td align="right">' . $langs->trans("HT") . '</td>';
					print '<td align="right">' . $langs->trans("UnitPrice") . '</td>';
					print '<td align="right">' . $langs->trans("Discount") . '</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
					foreach ($object->prices_by_qty_list[$i] as $ii => $prices) 
					{
						if ($action == 'edit_price_by_qty' && $rowid == $prices['rowid'] && ($user->rights->produit->creer || $user->rights->service->creer)) {
							print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
							print '<input type="hidden" name="action" value="update_price_by_qty">';
							print '<input type="hidden" name="priceid" value="' . $object->prices_by_qty_id[$i] . '">';
							print '<input type="hidden" value="' . $prices['rowid'] . '" name="rowid">';
							print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
							print '<td><input size="5" type="text" value="' . $prices['quantity'] . '" name="quantity"></td>';
							print '<td align="right" colspan="2"><input size="10" type="text" value="' . price2num($prices['price'], 'MU') . '" name="price">&nbsp;' . $object->price_base_type . '</td>';
							// print '<td align="right">&nbsp;</td>';
							print '<td align="right"><input size="5" type="text" value="' . $prices['remise_percent'] . '" name="remise_percent">&nbsp;%</td>';
							print '<td align="center"><input type="submit" value="' . $langs->trans("Modify") . '" class="button"></td>';
							print '</tr>';
							print '</form>';
						} else {
							print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
							print '<td>' . $prices['quantity'] . '</td>';
							print '<td align="right">' . price($prices['price']) . '</td>';
							print '<td align="right">' . price($prices['unitprice']) . '</td>';
							print '<td align="right">' . price($prices['remise_percent']) . ' %</td>';
							print '<td align="center">';
							if (($user->rights->produit->creer || $user->rights->service->creer)) {
								print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=edit_price_by_qty&amp;rowid=' . $prices["rowid"] . '">';
								print img_edit() . '</a>';
								print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete_price_by_qty&amp;rowid=' . $prices["rowid"] . '">';
								print img_delete() . '</a>';
							} else {
								print '&nbsp;';
							}
							print '</td>';
							print '</tr>';
						}
					}
					if ($action != 'edit_price_by_qty' && ($user->rights->produit->creer || $user->rights->service->creer)) {
						print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
						print '<input type="hidden" name="action" value="update_price_by_qty">';
						print '<input type="hidden" name="priceid" value="' . $object->prices_by_qty_id[$i] . '">';
						print '<input type="hidden" value="0" name="rowid">';
						print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
						print '<td><input size="5" type="text" value="1" name="quantity"></td>';
						print '<td align="right" colspan="2"><input size="10" type="text" value="0" name="price">&nbsp;' . $object->price_base_type . '</td>';
						// print '<td align="right">&nbsp;</td>';
						print '<td align="right"><input size="5" type="text" value="0" name="remise_percent">&nbsp;%</td>';
						print '<td align="center"><input type="submit" value="' . $langs->trans("Add") . '" class="button"></td>';
						print '</tr>';
						print '</form>';
					}

					print '</table>';
				} else {
					print $langs->trans("No");
					print '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=activate_price_by_qty&level=' . $i . '">(' . $langs->trans("Activate") . ')</a>';
				}
				print '</td></tr>';
			}
		}
	}
} 
else 
{
	// TVA
	print '<tr><td class="titlefield">' . $langs->trans("VATRate") . '</td><td>';
	if ($object->default_vat_code)
	{
        print vatrate($object->tva_tx, true) . ' ('.$object->default_vat_code.')';
	}   
	else print vatrate($object->tva_tx, true, $object->tva_npr, true);
	print '</td></tr>';

	// Price
	print '<tr><td>' . $langs->trans("SellingPrice") . '</td><td>';
	if ($object->price_base_type == 'TTC') {
		print price($object->price_ttc) . ' ' . $langs->trans($object->price_base_type);
	} else {
		print price($object->price) . ' ' . $langs->trans($object->price_base_type);
	}
	print '</td></tr>';

	// Price minimum
	print '<tr><td>' . $langs->trans("MinPrice") . '</td><td>';
	if ($object->price_base_type == 'TTC') {
		print price($object->price_min_ttc) . ' ' . $langs->trans($object->price_base_type);
	} else {
		print price($object->price_min) . ' ' . $langs->trans($object->price_base_type);
	}
	print '</td></tr>';

	// Price by quantity
	if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))    // TODO Fix the form inside tr instead of td
	{
		print '<tr><td>' . $langs->trans("PriceByQuantity");
		if ($object->prices_by_qty [0] == 0) {
			print '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=activate_price_by_qty&level=1">' . $langs->trans("Activate");
		}
		print '</td><td>';

		if ($object->prices_by_qty [0] == 1) {
			print '<table width="50%" class="border" summary="List of quantities">';
			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("PriceByQuantityRange") . '</td>';
			print '<td align="right">' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("UnitPrice") . '</td>';
			print '<td align="right">' . $langs->trans("Discount") . '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			foreach ($object->prices_by_qty_list [0] as $ii => $prices)
			{
				if ($action == 'edit_price_by_qty' && $rowid == $prices['rowid'] && ($user->rights->produit->creer || $user->rights->service->creer))
				{
					print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
					print '<input type="hidden" name="action" value="update_price_by_qty">';
					print '<input type="hidden" name="priceid" value="' . $object->prices_by_qty_id[0] . '">';
					print '<input type="hidden" value="' . $prices['rowid'] . '" name="rowid">';
					print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
					print '<td><input size="5" type="text" value="' . $prices['quantity'] . '" name="quantity"></td>';
					print '<td align="right" colspan="2"><input size="10" type="text" value="' . price2num($prices['price'], 'MU') . '" name="price">&nbsp;' . $object->price_base_type . '</td>';
					// print '<td align="right">&nbsp;</td>';
					print '<td align="right"><input size="5" type="text" value="' . $prices['remise_percent'] . '" name="remise_percent">&nbsp;%</td>';
					print '<td align="center"><input type="submit" value="' . $langs->trans("Modify") . '" class="button"></td>';
					print '</tr>';
					print '</form>';
				} else {
					print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
					print '<td>' . $prices['quantity'] . '</td>';
					print '<td align="right">' . price($prices['price']) . '</td>';
					print '<td align="right">' . price($prices['unitprice']) . '</td>';
					print '<td align="right">' . price($prices['remise_percent']) . ' %</td>';
					print '<td align="center">';
					if (($user->rights->produit->creer || $user->rights->service->creer)) {
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=edit_price_by_qty&amp;rowid=' . $prices["rowid"] . '">';
						print img_edit() . '</a>';
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete_price_by_qty&amp;rowid=' . $prices["rowid"] . '">';
						print img_delete() . '</a>';
					} else {
						print '&nbsp;';
					}
					print '</td>';
					print '</tr>';
				}
			}
			if ($action != 'edit_price_by_qty') {
				print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';  // FIXME a form into a table is not allowed
				print '<input type="hidden" name="action" value="update_price_by_qty">';
				print '<input type="hidden" name="priceid" value="' . $object->prices_by_qty_id [0] . '">';
				print '<input type="hidden" value="0" name="rowid">';
				print '<tr class="' . ($ii % 2 == 0 ? 'pair' : 'impair') . '">';
				print '<td><input size="5" type="text" value="1" name="quantity"></td>';
				print '<td align="right" colspan="2"><input size="10" type="text" value="0" name="price">&nbsp;' . $object->price_base_type . '</td>';
				// print '<td align="right">&nbsp;</td>';
				print '<td align="right"><input size="5" type="text" value="0" name="remise_percent">&nbsp;%</td>';
				print '<td align="center"><input type="submit" value="' . $langs->trans("Add") . '" class="button"></td>';
				print '</tr>';
				print '</form>';
			}

			print '</table>';
		} else {
			print $langs->trans("No");
		}
		print '</td></tr>';
	}
}

print "</table>\n";

print '</div>';
print '<div style="clear:both"></div>';


dol_fiche_end();



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

if (! $action || $action == 'delete' || $action == 'showlog_customer_price' || $action == 'showlog_default_price' || $action == 'add_customer_price')
{
	print "\n" . '<div class="tabsAction">' . "\n";

	if (empty($conf->global->PRODUIT_MULTIPRICES))
	{
    	if ($user->rights->produit->creer || $user->rights->service->creer) {
    		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit_price&amp;id=' . $object->id . '">' . $langs->trans("UpdateDefaultPrice") . '</a></div>';
    	}
	}

	if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
	{
	    if ($user->rights->produit->creer || $user->rights->service->creer) {
	 		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=add_customer_price&amp;id=' . $object->id . '">' . $langs->trans("AddCustomerPrice") . '</a></div>';
	  	}
	}
	
	if (! empty($conf->global->PRODUIT_MULTIPRICES))
	{
	    if ($user->rights->produit->creer || $user->rights->service->creer) {
    		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit_vat&amp;id=' . $object->id . '">' . $langs->trans("UpdateVAT") . '</a></div>';
    	}
	    
	    if ($user->rights->produit->creer || $user->rights->service->creer) {
    		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit_price&amp;id=' . $object->id . '">' . $langs->trans("UpdateLevelPrices") . '</a></div>';
    	}
	}
    
	print "\n</div>\n";
}



/*
 * Edit price area
 */
 
if ($action == 'edit_vat' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	print load_fiche_titre($langs->trans("UpdateVAT"), '');

	print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update_vat">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	dol_fiche_head('');
	
	print '<table class="border" width="100%">';
	
	// VAT
	print '<tr><td>' . $langs->trans("VATRate") . '</td><td>';
	print $form->load_tva("tva_tx", $object->default_vat_code ? $object->tva_tx.' ('.$object->default_vat_code.')' : $object->tva_tx, $mysoc, '', $object->id, $object->tva_npr, $object->type, false, 1);
	print '</td></tr>';

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '<br></form><br>';
}
 
if ($action == 'edit_price' && $object->getRights()->creer)
{
	print load_fiche_titre($langs->trans("NewPrice"), '');

	if (empty($conf->global->PRODUIT_MULTIPRICES))
	{
		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update_price">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		dol_fiche_head('');
		
		print '<table class="border" width="100%">';

		// VAT
		print '<tr><td class="titlefield">' . $langs->trans("VATRate") . '</td><td>';
		print $form->load_tva("tva_tx", $object->default_vat_code ? $object->tva_tx.' ('.$object->default_vat_code.')' : $object->tva_tx, $mysoc, '', $object->id, $object->tva_npr, $object->type, false, 1);
		print '</td></tr>';

		// Price base
		print '<tr><td>';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->selectPriceBaseType($object->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

 		// Only show price mode and expression selector if module is enabled
		if (! empty($conf->dynamicprices->enabled)) {
			// Price mode selector
			print '<tr><td>'.$langs->trans("PriceMode").'</td><td>';
			$price_expression = new PriceExpression($db);
			$price_expression_list = array(0 => $langs->trans("PriceNumeric")); //Put the numeric mode as first option
			foreach ($price_expression->list_price_expression() as $entry) {
				$price_expression_list[$entry->id] = $entry->title;
			}
			$price_expression_preselection = GETPOST('eid') ? GETPOST('eid') : ($object->fk_price_expression ? $object->fk_price_expression : '0');
			print $form->selectarray('eid', $price_expression_list, $price_expression_preselection);
			print '&nbsp; <div id="expression_editor" class="button">'.$langs->trans("PriceExpressionEditor").'</div>';
			print '</td></tr>';

			// This code hides the numeric price input if is not selected, loads the editor page if editor button is pressed
			?>

			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#expression_editor").click(function() {
						window.location = "<?php echo DOL_URL_ROOT ?>/product/dynamic_price/editor.php?id=<?php echo $id ?>&tab=price&eid=" + $("#eid").val();
					});
					jQuery("#eid").change(on_change);
					on_change();
				});
				function on_change() {
					if ($("#eid").val() == 0) {
						jQuery("#price_numeric").show();
					} else {
						jQuery("#price_numeric").hide();
					}
				}
			</script>
			<?php
		}

		// Price
		$product = new Product($db);
		$product->fetch($id, $ref, '', 1); //Ignore the math expression when getting the price
		print '<tr id="price_numeric"><td>';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC') {
			print '<input name="price" size="10" value="' . price($product->price_ttc) . '">';
		} else {
			print '<input name="price" size="10" value="' . price($product->price) . '">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC') {
			print '<input name="price_min" size="10" value="' . price($object->price_min_ttc) . '">';
		} else {
			print '<input name="price_min" size="10" value="' . price($object->price_min) . '">';
		}
		if (! empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
		{
		    print ' &nbsp; '.$langs->trans("MinimumRecommendedPrice", price($maxpricesupplier,0,'',1,-1,-1,'auto')).' '.img_warning().'</td>';
		}
		print '</td>';
		print '</tr>';
		
		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '<br></form>';
	}
	else
	{
		?>
		<script>

			var showHidePriceRules = function () {
				var otherPrices = $('div.fiche form table tbody tr:not(:first)');
				var minPrice1 = $('div.fiche form input[name="price_min[1]"]');

				if (jQuery('input#usePriceRules').prop('checked')) {
					otherPrices.hide();
					minPrice1.hide();
				} else {
					otherPrices.show();
					minPrice1.show();
				}
			};

			jQuery(document).ready(function () {
				showHidePriceRules();

				jQuery('input#usePriceRules').click(showHidePriceRules);
			});
		</script>
		<?php

		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update_price">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		dol_fiche_head('');
		
		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($conf->global->PRODUIT_MULTIPRICES_ALLOW_AUTOCALC_PRICELEVEL)) {
			print $langs->trans('UseMultipriceRules'). ' <input type="checkbox" id="usePriceRules" name="usePriceRules" '.($object->price_autogen ? 'checked' : '').'><br><br>';
		}

		print '<table class="noborder">';
		print '<thead><tr class="liste_titre">';
		
		print '<td>'.$langs->trans("PriceLevel").'</td>';

		if (!empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL)) print '<td style="text-align: center">'.$langs->trans("VATRate").'</td>';
		else print '<td></td>';
		
		print '<td class="center">'.$langs->trans("SellingPrice").'</td>';
		
		print '<td class="center">'.$langs->trans("MinPrice").'</td>';

		if (!empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE)) {
			print '<td></td>';
		}
		print '</tr></thead>';

		print '<tbody>';
		
		$var = false;
		for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i ++) 
		{
			$var = !$var;

			print '<tr '.$bc[$var].'>';
			print '<td>';
			print $form->textwithpicto($langs->trans('SellingPrice') . ' ' . $i, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
			print '</td>';

			// VAT
			if (empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL)) {
			    print '<td>';
			    print '<input type="hidden" name="tva_tx[' . $i . ']" value="' . ($object->default_vat_code ? $object->tva_tx.' ('.$object->default_vat_code.')' : $object->tva_tx) . '">';
			    print '<input type="hidden" name="tva_npr[' . $i . ']" value="' . $object->tva_npr . '">';
			    print '<input type="hidden" name="localtax1_tx[' . $i . ']" value="' . $object->localtax1_tx . '">';
			    print '<input type="hidden" name="localtax1_type[' . $i . ']" value="' . $object->localtax1_type . '">';
			    print '<input type="hidden" name="localtax2_tx[' . $i . ']" value="' . $object->localtax2_tx . '">';
			    print '<input type="hidden" name="localtax2_type[' . $i . ']" value="' . $object->localtax2_type . '">';
			    print '</td>';
			} else {
				// This option is kept for backward compatibility but has no sense
				print '<td style="text-align: center">';
				print $form->load_tva("tva_tx[" . $i.']', $object->multiprices_tva_tx[$i], $mysoc, '', $object->id, false, $object->type, false, 1);
				print '</td>';
			}

			// Selling price
			print '<td style="text-align: center">';
			if ($object->multiprices_base_type [$i] == 'TTC') {
				print '<input name="price[' . $i . ']" size="10" value="' . price($object->multiprices_ttc [$i]) . '">';
			} else {
				print '<input name="price[' . $i . ']" size="10" value="' . price($object->multiprices [$i]) . '">';
			}
			print '&nbsp;'.$form->selectPriceBaseType($object->multiprices_base_type [$i], "multiprices_base_type[" . $i."]");
			print '</td>';

			// Min price
			print '<td style="text-align: center">';
			if ($object->multiprices_base_type [$i] == 'TTC') {
				print '<input name="price_min[' . $i . ']" size="10" value="' . price($object->multiprices_min_ttc [$i]) . '">';
			} else {
				print '<input name="price_min[' . $i . ']" size="10" value="' . price($object->multiprices_min [$i]) . '">';
			}
			if ( !empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
			{
				print '<td align="left">'.$langs->trans("MinimumRecommendedPrice", price($maxpricesupplier,0,'',1,-1,-1,'auto')).' '.img_warning().'</td>';
			}
			print '</td>';

			print '</tr>';
		}

		print '</tbody>';
		
		print '</table>';
		
		dol_fiche_end();
		
		print '<div style="text-align: center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '"></div>';
		print '</form>';

	}
}


// List of price changes - log historic (ordered by descending date)

if ((empty($conf->global->PRODUIT_CUSTOMER_PRICES) || $action=='showlog_default_price') && ! in_array($action, array('edit_price','edit_vat')))
{
    $sql = "SELECT p.rowid, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.default_vat_code, p.recuperableonly,";
    $sql .= " p.price_level, p.price_min, p.price_min_ttc,p.price_by_qty,";
    $sql .= " p.date_price as dp, p.fk_price_expression, u.rowid as user_id, u.login";
    $sql .= " FROM " . MAIN_DB_PREFIX . "product_price as p,";
    $sql .= " " . MAIN_DB_PREFIX . "user as u";
    $sql .= " WHERE fk_product = " . $object->id;
    $sql .= " AND p.entity IN (" . getEntity('productprice', 1) . ")";
    $sql .= " AND p.fk_user_author = u.rowid";
    if (! empty($socid) && ! empty($conf->global->PRODUIT_MULTIPRICES)) $sql .= " AND p.price_level = " . $soc->price_level;
    $sql .= " ORDER BY p.date_price DESC, p.rowid DESC, p.price_level ASC";
    // $sql .= $db->plimit();
    
    $result = $db->query($sql);
    if ($result)
    {
    	$num = $db->num_rows($result);
    
    	if (! $num)
    	{
    		$db->free($result);
    
    		// Il doit au moins y avoir la ligne de prix initial.
    		// On l'ajoute donc pour remettre a niveau (pb vieilles versions)
    		$object->updatePrice($object->price, $object->price_base_type, $user, $newprice_min);
    
    		$result = $db->query($sql);
    		$num = $db->num_rows($result);
    	}
    
    	if ($num > 0)
    	{
    	    // Default prices or
    	    // Log of previous customer prices
    	    $backbutton='<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">' . $langs->trans("Back") . '</a>';
    	    	
    		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) print_barre_liste($langs->trans("DefaultPrice"), 0, $_SERVER["PHP_SELF"], '', '', '', $backbutton, $num, $num, 'title_accountancy.png');
    		else print_barre_liste($langs->trans("PriceByCustomerLog"), 0, $_SERVER["PHP_SELF"], '', '', '', '', $num, $num, 'title_accountancy.png');
    	    //if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) print_barre_liste($langs->trans("DefaultPrice"),'','','','','',$backbutton, 0, 0, 'title_accountancy.png');
    		//else print_barre_liste($langs->trans("PriceByCustomerLog"),'','','','','','', 0, 0, 'title_accountancy.png');
    
    		print '<div class="div-table-responsive">';
    		print '<table class="noborder" width="100%">';
    
    		print '<tr class="liste_titre">';
    		print '<td>' . $langs->trans("AppliedPricesFrom") . '</td>';
    
    		if (! empty($conf->global->PRODUIT_MULTIPRICES)) {
    			print '<td align="center">' . $langs->trans("PriceLevel") . '</td>';
    		}
    		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) {
    			print '<td align="center">' . $langs->trans("Type") . '</td>';
    		}
    
    		print '<td align="center">' . $langs->trans("PriceBase") . '</td>';
    		print $conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL;
    		if (empty($conf->global->PRODUIT_MULTIPRICES)) print '<td align="right">' . $langs->trans("VATRate") . '</td>';
    		print '<td align="right">' . $langs->trans("HT") . '</td>';
    		print '<td align="right">' . $langs->trans("TTC") . '</td>';
    		if (! empty($conf->dynamicprices->enabled)) {
    			print '<td align="right">' . $langs->trans("PriceExpressionSelected") . '</td>';
    		}
    		print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("HT") . '</td>';
    		print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("TTC") . '</td>';
    		print '<td align="right">' . $langs->trans("ChangedBy") . '</td>';
    		if ($user->rights->produit->supprimer)
    			print '<td align="right">&nbsp;</td>';
    		print '</tr>';
    
    		$notfirstlineforlevel=array();
    		
    		$var = True;
    		$i = 0;
    		while ($i < $num)
    		{
    			$objp = $db->fetch_object($result);
    			$var = ! $var;
    			print '<tr '. $bc[$var].'>';
    			// Date
    			print "<td>" . dol_print_date($db->jdate($objp->dp), "dayhour") . "</td>";
    
    			// Price level
    			if (! empty($conf->global->PRODUIT_MULTIPRICES)) {
    				print '<td align="center">' . $objp->price_level . "</td>";
    			}
    			// Price by quantity
    			if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
    			{
    				$type = ($objp->price_by_qty == 1) ? 'PriceByQuantity' : 'Standard';
    				print '<td align="center">' . $langs->trans($type) . "</td>";
    			}
    
    			print '<td align="center">' . $langs->trans($objp->price_base_type) . "</td>";
    			if (empty($conf->global->PRODUIT_MULTIPRICES)) 
    			{
    			    print '<td align="right">';
    			    if ($objp->default_vat_code)
    			    {
    			        print vatrate($objp->tva_tx, true) . ' ('.$objp->default_vat_code.')';
    			    }
    			    else print vatrate($objp->tva_tx, true, $objp->recuperableonly);
    			    print "</td>";
    			}
    
    			// Price
    			if (! empty($objp->fk_price_expression) && ! empty($conf->dynamicprices->enabled))
    			{
    				$price_expression = new PriceExpression($db);
    				$res = $price_expression->fetch($objp->fk_price_expression);
    				$title = $price_expression->title;
    				print '<td align="right"></td>';
    				print '<td align="right"></td>';
    				print '<td align="right">' . $title . "</td>";
    			}
    			else
    			{
    				print '<td align="right">' . ($objp->price_base_type != 'TTC' ? price($objp->price) : ''). "</td>";
    				print '<td align="right">' . ($objp->price_base_type == 'TTC' ? price($objp->price_ttc) : '') . "</td>";
    				if (! empty($conf->dynamicprices->enabled)) { //Only if module is enabled
    					print '<td align="right"></td>';
    				}
    			}
    			print '<td align="right">' . ($objp->price_base_type != 'TTC' ? price($objp->price_min) : '') . '</td>';
    			print '<td align="right">' . ($objp->price_base_type == 'TTC' ? price($objp->price_min_ttc) : '') . '</td>';
    
    			// User
    			print '<td align="right"><a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $objp->user_id . '">' . img_object($langs->trans("ShowUser"), 'user') . ' ' . $objp->login . '</a></td>';
    
    			// Action
    			if ($user->rights->produit->supprimer)
    			{
    			    $candelete=0;
    			    if (! empty($conf->global->PRODUIT_MULTIPRICES)) 
    			    {
    			        if (empty($notfirstlineforlevel[$objp->price_level])) $notfirstlineforlevel[$objp->price_level]=1;
    			        else $candelete=1;
    			    }
    			    elseif ($i > 0) $candelete=1;
    			    
    				print '<td align="right">';
    				if ($candelete) 
    				{
    					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $object->id . '&amp;lineid=' . $objp->rowid . '">';
    					print img_delete();
    					print '</a>';
    				} else
    					print '&nbsp;'; // Can not delete last price (it's current price)
    				print '</td>';
    			}
    
    			print "</tr>\n";
    			$i++;
    		}
    		
    		$db->free($result);
    		print "</table>";
    		print '</div>';
    		print "<br>";
    	}
    } else {
    	dol_print_error($db);
    }
}


// Add area to show/add/edit a price for a dedicated customer
if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
{
	$prodcustprice = new Productcustomerprice($db);

	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOST("page", 'int');
	if ($page == - 1) {
		$page = 0;
	}
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (! $sortorder)
		$sortorder = "ASC";
	if (! $sortfield)
		$sortfield = "soc.nom";

		// Build filter to diplay only concerned lines
	$filter = array('t.fk_product' => $object->id);

	if (! empty($search_soc)) {
		$filter['soc.nom'] = $search_soc;
	}

	if ($action == 'add_customer_price')
	{
		// Form to add a new customer price
		$maxpricesupplier = $object->min_recommended_price();

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="add_customer_price_confirm">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';
		
		dol_fiche_head();
		
		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td class="fieldrequired">' . $langs->trans('ThirdParty') . '</td>';
		print '<td>';
		print $form->select_company('', 'socid', 's.client in (1,2,3) AND s.rowid NOT IN (SELECT fk_soc FROM ' . MAIN_DB_PREFIX . 'product_customer_price WHERE fk_product='.$object->id.')', 'SelectThirdParty', 0, 0, array(), 0, 'minwidth300');
		print '</td>';
		print '</tr>';

		// VAT
		print '<tr><td class="fieldrequired">' . $langs->trans("VATRate") . '</td><td>';
		print $form->load_tva("tva_tx", $object->tva_tx, $mysoc, '', $object->id, $object->tva_npr, $object->type, false, 1);
		print '</td></tr>';

		// Price base
		print '<tr><td class="fieldrequired">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->selectPriceBaseType($object->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

		// Price
		print '<tr><td class="fieldrequired">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC') {
			print '<input name="price" size="10" value="' . price($object->price_ttc) . '">';
		} else {
			print '<input name="price" size="10" value="' . price($object->price) . '">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		if ($object->price_base_type == 'TTC') {
			print '<td><input name="price_min" size="10" value="' . price($object->price_min_ttc) . '">';
		} else {
			print '<td><input name="price_min" size="10" value="' . price($object->price_min) . '">';
		}
		if ( !empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
		{
			print '<td align="left">'.$langs->trans("MinimumRecommendedPrice", price($maxpricesupplier,0,'',1,-1,-1,'auto')).' '.img_warning().'</td>';
		}
		print '</td></tr>';

		// Update all child soc
		print '<tr><td width="15%">';
		print $langs->trans('ForceUpdateChildPriceSoc');
		print '</td>';
		print '<td>';
		print '<input type="checkbox" name="updatechildprice" value="1">';
		print '</td>';
		print '</tr>';

		print '</table>';

		dol_fiche_end();
		
		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '</form>';
	}
	elseif ($action == 'edit_customer_price')
	{
		// Edit mode
		$maxpricesupplier = $object->min_recommended_price();

		print load_fiche_titre($langs->trans('PriceByCustomer'));

		$result = $prodcustprice->fetch(GETPOST('lineid', 'int'));
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update_customer_price_confirm">';
		print '<input type="hidden" name="lineid" value="' . $prodcustprice->id . '">';
		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td>' . $langs->trans('ThirdParty') . '</td>';
		$staticsoc = new Societe($db);
		$staticsoc->fetch($prodcustprice->fk_soc);
		print "<td colspan='2'>" . $staticsoc->getNomUrl(1) . "</td>";
		print '</tr>';

		// VAT
		print '<tr><td>' . $langs->trans("VATRate") . '</td><td colspan="2">';
		print $form->load_tva("tva_tx", $prodcustprice->tva_tx, $mysoc, '', $object->id, $prodcustprice->recuperableonly, $object->type, false, 1);
		print '</td></tr>';

		// Price base
		print '<tr><td width="15%">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->selectPriceBaseType($prodcustprice->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

		// Price
		print '<tr><td width="20%">';
		$text = $langs->trans('SellingPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($prodcustprice->price_base_type == 'TTC') {
			print '<input name="price" size="10" value="' . price($prodcustprice->price_ttc) . '">';
		} else {
			print '<input name="price" size="10" value="' . price($prodcustprice->price) . '">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>';
		$text = $langs->trans('MinPrice');
		print $form->textwithpicto($text, $langs->trans("PrecisionUnitIsLimitedToXDecimals", $conf->global->MAIN_MAX_DECIMALS_UNIT), 1, 1);
		print '</td><td>';
		if ($prodcustprice->price_base_type == 'TTC') {
			print '<input name="price_min" size="10" value="' . price($prodcustprice->price_min_ttc) . '">';
		} else {
			print '<input name="price_min" size="10" value="' . price($prodcustprice->price_min) . '">';
		}
		print '</td>';
		if ( !empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE))
		{
			print '<td align="left">'.$langs->trans("MinimumRecommendedPrice", price($maxpricesupplier,0,'',1,-1,-1,'auto')).' '.img_warning().'</td>';
		}
		print '</tr>';

		// Update all child soc
		print '<tr><td width="15%">';
		print $langs->trans('ForceUpdateChildPriceSoc');
		print '</td>';
		print '<td>';
		print '<input type="checkbox" name="updatechildprice" value="1">';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<br><div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '<br></form>';
	}
	elseif ($action == 'showlog_customer_price')
	{
		$filter = array('t.fk_product' => $object->id,'t.fk_soc' => GETPOST('socid', 'int'));

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		}

		$result = $prodcustprice->fetch_all_log($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

		$option = '&socid=' . GETPOST('socid', 'int') . '&id=' . $object->id;

		$staticsoc = new Societe($db);
		$staticsoc->fetch(GETPOST('socid', 'int'));
		
		$title=$langs->trans('PriceByCustomerLog');
		$title.=' - '.$staticsoc->getNomUrl(1);

		$backbutton='<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">' . $langs->trans("Back") . '</a>';
		
		print_barre_liste($title, $page, $_SERVEUR['PHP_SELF'], $option, $sortfield, $sortorder, $backbutton, count($prodcustprice->lines), $nbtotalofrecords, 'title_accountancy.png');

		if (count($prodcustprice->lines) > 0)
		{

			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
			print '<input type="hidden" name="id" value="' . $object->id . '">';

			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("ThirdParty") . '</td>';
			print '<td>' . $langs->trans("AppliedPricesFrom") . '</td>';
			print '<td align="center">' . $langs->trans("PriceBase") . '</td>';
			print '<td align="right">' . $langs->trans("VATRate") . '</td>';
			print '<td align="right">' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("HT") . '</td>';
			print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("TTC") . '</td>';
			print '<td align="right">' . $langs->trans("ChangedBy") . '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';

			$var = True;

			foreach ($prodcustprice->lines as $line)
			{
				$var = ! $var;
				print "<tr ".$bc[$var].">";
				// Date
				$staticsoc = new Societe($db);
				$staticsoc->fetch($line->fk_soc);

				print "<td>" . $staticsoc->getNomUrl(1) . "</td>";
				print "<td>" . dol_print_date($line->datec, "dayhour") . "</td>";

				print '<td align="center">' . $langs->trans($line->price_base_type) . "</td>";
				print '<td align="right">' . vatrate($line->tva_tx, true, $line->recuperableonly) . "</td>";
				print '<td align="right">' . price($line->price) . "</td>";
				print '<td align="right">' . price($line->price_ttc) . "</td>";
				print '<td align="right">' . price($line->price_min) . '</td>';
				print '<td align="right">' . price($line->price_min_ttc) . '</td>';

				// User
				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);
				print '<td align="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';
			}
			print "</table>";
		} else {
			print $langs->trans('None');
		}
	}
	else if ($action != 'showlog_default_price' && $action != 'edit_price')
	{
		// List of all prices by customers

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $prodcustprice->fetch_all($sortorder, $sortfield, 0, 0, $filter);
		}

		$result = $prodcustprice->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);
		if ($result < 0) {
			setEventMessages($prodcustprice->error, $prodcustprice->errors, 'errors');
		}

		$option = '&search_soc=' . $search_soc . '&id=' . $object->id;

		print_barre_liste($langs->trans('PriceByCustomer'), $page, $_SERVEUR ['PHP_SELF'], $option, $sortfield, $sortorder, '', count($prodcustprice->lines), $nbtotalofrecords, 'title_accountancy.png');

		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans("ThirdParty") . '</td>';
		print '<td>' . $langs->trans("AppliedPricesFrom") . '</td>';
		print '<td align="center">' . $langs->trans("PriceBase") . '</td>';
		print '<td align="right">' . $langs->trans("VATRate") . '</td>';
		print '<td align="right">' . $langs->trans("HT") . '</td>';
		print '<td align="right">' . $langs->trans("TTC") . '</td>';
		print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("HT") . '</td>';
		print '<td align="right">' . $langs->trans("MinPrice") . ' ' . $langs->trans("TTC") . '</td>';
		print '<td align="right">' . $langs->trans("ChangedBy") . '</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';

		if (count($prodcustprice->lines) > 0 || $search_soc)
		{
    		print '<tr class="liste_titre">';
    		print '<td><input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20"></td>';
    		print '<td colspan="8">&nbsp;</td>';
    		// Print the search button
            print '<td class="liste_titre" align="right">';
            $searchpitco=$form->showFilterAndCheckAddButtons(0);
            print $searchpitco;
            print '</td>';
    		print '</tr>';
		}
		
		$var = False;
		
		
		// Line for default price
		print "<tr ".$bc[$var].">";
		print "<td>" . $langs->trans("Default") . "</td>";
		print "<td>" . "</td>";
		
		print '<td align="center">' . $langs->trans($object->price_base_type) . "</td>";
		print '<td align="right">' . vatrate($object->tva_tx, true, $object->recuperableonly) . "</td>";
		print '<td align="right">' . price($object->price) . "</td>";
		print '<td align="right">' . price($object->price_ttc) . "</td>";
		print '<td align="right">' . price($object->price_min) . '</td>';
		print '<td align="right">' . price($object->price_min_ttc) . '</td>';
		print '<td align="right">';
		print '</td>';
		if ($user->rights->produit->supprimer || $user->rights->service->supprimer)
		{
		    print '<td align="right">';
		    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=showlog_default_price&amp;id=' . $object->id . '">';
		    print img_info($langs->trans('PriceByCustomerLog'));
		    print '</a>';
		    print ' ';
		    print '<a href="' . $_SERVER["PHP_SELF"] . '?action=edit_price&amp;id=' . $object->id . '">';
		    print img_edit('default', 0, 'style="vertical-align: middle;"');
		    print '</a>';
		    print ' &nbsp; ';
		    print '</td>';
		}
		print "</tr>\n";

		
		if (count($prodcustprice->lines) > 0)
		{
		    $var = false;
			foreach ($prodcustprice->lines as $line)
			{
			    $var = ! $var;
			    
				print "<tr ".$bc[$var].">";
				// Date
				$staticsoc = new Societe($db);
				$staticsoc->fetch($line->fk_soc);

				print "<td>" . $staticsoc->getNomUrl(1) . "</td>";
				print "<td>" . dol_print_date($line->datec, "dayhour") . "</td>";

				print '<td align="center">' . $langs->trans($line->price_base_type) . "</td>";
				print '<td align="right">' . vatrate($line->tva_tx, true, $line->recuperableonly) . "</td>";
				print '<td align="right">' . price($line->price) . "</td>";
				print '<td align="right">' . price($line->price_ttc) . "</td>";
				print '<td align="right">' . price($line->price_min) . '</td>';
				print '<td align="right">' . price($line->price_min_ttc) . '</td>';

				// User
				$userstatic = new User($db);
				$userstatic->fetch($line->fk_user);
				print '<td align="right">';
				print $userstatic->getLoginUrl(1);
				print '</td>';

				// Todo Edit or delete button
				// Action
				if ($user->rights->produit->supprimer || $user->rights->service->supprimer)
				{
					print '<td align="right">';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=showlog_customer_price&amp;id=' . $object->id . '&amp;socid=' . $line->fk_soc . '">';
					print img_info($langs->trans('PriceByCustomerLog'));
					print '</a>';
					print ' ';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=edit_customer_price&amp;id=' . $object->id . '&amp;lineid=' . $line->id . '">';
					print img_edit('default', 0, 'style="vertical-align: middle;"');
					print '</a>';
					print ' ';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete_customer_price&amp;id=' . $object->id . '&amp;lineid=' . $line->id . '">';
					print img_delete('default', 'style="vertical-align: middle;"');
					print '</a>';
					print '</td>';
				}

				print "</tr>\n";
			}
		}
		/*else
		{
			$colspan=9;
			if ($user->rights->produit->supprimer || $user->rights->service->supprimer) $colspan+=1;
			print "<tr ".$bc[false].">";
			print '<td colspan="'.$colspan.'">'.$langs->trans('None').'</td>';
			print "</tr>";
		}*/

		print "</table>";

		print "</form>";
	}
}

llxFooter();

$db->close();
