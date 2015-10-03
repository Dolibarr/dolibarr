<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2015	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2014  Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Ferran Marcet		  	<fmarcet@2byte.es>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
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
 *       \file       htdocs/contrat/card.php
 *       \ingroup    contrat
 *       \brief      Page of a contract
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->propal->enabled))  require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load('compta');

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$socid = GETPOST('socid','int');
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');

$datecontrat='';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$id);

$usehm=(! empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE)?$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE:0);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('contractcard','globalcard'));

$object = new Contrat($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0 || ! empty($ref) && $action!='add') {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		dol_print_error('', $object->error);
}

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// fetch optionals attributes lines and labels
$extrafieldsline = new ExtraFields($db);
$extralabelslines=$extrafieldsline->fetch_name_optionals_label($object->table_element_line);

$permissionnote=$user->rights->contrat->creer;	// Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->contrat->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once
	
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
	
	if ($action == 'confirm_active' && $confirm == 'yes' && $user->rights->contrat->activer)
	{
	    $result = $object->active_line($user, GETPOST('ligne'), GETPOST('date'), GETPOST('dateend'), GETPOST('comment'));
	
	    if ($result > 0)
	    {
	        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
	        exit;
	    }
	    else {
	        setEventMessage($object->error,'errors');
	    }
	}
	
	else if ($action == 'confirm_closeline' && $confirm == 'yes' && $user->rights->contrat->activer)
	{
		if (! GETPOST('dateend'))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")),'errors');
		}
		if (! $error)
		{
		    $result = $object->close_line($user, GETPOST('ligne'), GETPOST('dateend'), urldecode(GETPOST('comment')));
		    if ($result > 0)
		    {
		        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		        exit;
		    }
		    else 
		    {
		        setEventMessage($object->error,'errors');
		    }
		}
	}
	
	// Si ajout champ produit predefini
	if (GETPOST('mode')=='predefined')
	{
	    $date_start='';
	    $date_end='';
	    if (GETPOST('date_startmonth') && GETPOST('date_startday') && GETPOST('date_startyear'))
	    {
	        $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	    }
	    if (GETPOST('date_endmonth') && GETPOST('date_endday') && GETPOST('date_endyear'))
	    {
	        $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
	    }
	}
	
	// Si ajout champ produit libre
	if (GETPOST('mode')=='libre')
	{
	    $date_start_sl='';
	    $date_end_sl='';
	    if (GETPOST('date_start_slmonth') && GETPOST('date_start_slday') && GETPOST('date_start_slyear'))
	    {
	        $date_start_sl=dol_mktime(GETPOST('date_start_slhour'), GETPOST('date_start_slmin'), 0, GETPOST('date_start_slmonth'), GETPOST('date_start_slday'), GETPOST('date_start_slyear'));
	    }
	    if (GETPOST('date_end_slmonth') && GETPOST('date_end_slday') && GETPOST('date_end_slyear'))
	    {
	        $date_end_sl=dol_mktime(GETPOST('date_end_slhour'), GETPOST('date_end_slmin'), 0, GETPOST('date_end_slmonth'), GETPOST('date_end_slday'), GETPOST('date_end_slyear'));
	    }
	}
	
	// Param dates
	$date_contrat='';
	$date_start_update='';
	$date_end_update='';
	$date_start_real_update='';
	$date_end_real_update='';
	if (GETPOST('date_start_updatemonth') && GETPOST('date_start_updateday') && GETPOST('date_start_updateyear'))
	{
	    $date_start_update=dol_mktime(GETPOST('date_start_updatehour'), GETPOST('date_start_updatemin'), 0, GETPOST('date_start_updatemonth'), GETPOST('date_start_updateday'), GETPOST('date_start_updateyear'));
	}
	if (GETPOST('date_end_updatemonth') && GETPOST('date_end_updateday') && GETPOST('date_end_updateyear'))
	{
	    $date_end_update=dol_mktime(GETPOST('date_end_updatehour'), GETPOST('date_end_updatemin'), 0, GETPOST('date_end_updatemonth'), GETPOST('date_end_updateday'), GETPOST('date_end_updateyear'));
	}
	if (GETPOST('date_start_real_updatemonth') && GETPOST('date_start_real_updateday') && GETPOST('date_start_real_updateyear'))
	{
	    $date_start_real_update=dol_mktime(GETPOST('date_start_real_updatehour'), GETPOST('date_start_real_updatemin'), 0, GETPOST('date_start_real_updatemonth'), GETPOST('date_start_real_updateday'), GETPOST('date_start_real_updateyear'));
	}
	if (GETPOST('date_end_real_updatemonth') && GETPOST('date_end_real_updateday') && GETPOST('date_end_real_updateyear'))
	{
	    $date_end_real_update=dol_mktime(GETPOST('date_end_real_updatehour'), GETPOST('date_end_real_updatemin'), 0, GETPOST('date_end_real_updatemonth'), GETPOST('date_end_real_updateday'), GETPOST('date_end_real_updateyear'));
	}
	if (GETPOST('remonth') && GETPOST('reday') && GETPOST('reyear'))
	{
	    $datecontrat = dol_mktime(GETPOST('rehour'), GETPOST('remin'), 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	}
	
	// Add contract
	if ($action == 'add' && $user->rights->contrat->creer)
	{
		// Check
		if (empty($datecontrat))
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
			$action='create';
		}
	
		if ($socid<1)
		{
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Customer")),'errors');
			$action='create';
			$error++;
		}
	
		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) {
			$error ++;
			$action = 'create';
		}
	
		if (! $error)
		{
			$object->socid						= $socid;
	    	$object->date_contrat				= $datecontrat;
	
	    	$object->commercial_suivi_id		= GETPOST('commercial_suivi_id','int');
	    	$object->commercial_signature_id	= GETPOST('commercial_signature_id','int');
	
	    	$object->note_private				= GETPOST('note_private','alpha');
	    	$object->note_public				= GETPOST('note_public','alpha');
	    	$object->fk_project					= GETPOST('projectid','int');
	    	$object->remise_percent				= GETPOST('remise_percent','alpha');
	    	$object->ref						= GETPOST('ref','alpha');
	    	$object->ref_supplier				= GETPOST('ref_supplier','alpha');
	
		    // If creation from another object of another module (Example: origin=propal, originid=1)
		    if ($_POST['origin'] && $_POST['originid'])
		    {
		        // Parse element/subelement (ex: project_task)
		        $element = $subelement = $_POST['origin'];
		        if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
		        {
		            $element = $regs[1];
		            $subelement = $regs[2];
		        }
	
		        // For compatibility
		        if ($element == 'order')    { $element = $subelement = 'commande'; }
		        if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
	
		        $object->origin    = $_POST['origin'];
		        $object->origin_id = $_POST['originid'];
	
		        // Possibility to add external linked objects with hooks
		        $object->linked_objects[$object->origin] = $object->origin_id;
		        if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects']))
		        {
		        	$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
		        }
	
		        $id = $object->create($user);
		        if ($id < 0) {
		        	setEventMessage($object->error,'errors');
		        }
	
		        if ($id > 0)
		        {
		            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
	
		            $classname = ucfirst($subelement);
		            $srcobject = new $classname($db);
	
		            dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
		            $result=$srcobject->fetch($object->origin_id);
		            if ($result > 0)
		            {
		                $srcobject->fetch_thirdparty();
						$lines = $srcobject->lines;
		                if (empty($lines) && method_exists($srcobject,'fetch_lines'))
		                {
		                	$srcobject->fetch_lines();
		                	$lines = $srcobject->lines;
		                }
	
		                $fk_parent_line=0;
		                $num=count($lines);
	
		                for ($i=0;$i<$num;$i++)
		                {
		                    $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);
	
							if ($product_type == 1 || (! empty($conf->global->CONTRACT_SUPPORT_PRODUCTS) && in_array($product_type, array(0,1)))) { 	// TODO Exclude also deee
								// service prédéfini
								if ($lines[$i]->fk_product > 0)
								{
									$product_static = new Product($db);
	
									// Define output language
									if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
									{
										$prod = new Product($db);
										$prod->id=$lines[$i]->fk_product;
										$prod->getMultiLangs();
	
										$outputlangs = $langs;
										$newlang='';
										if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
										if (empty($newlang)) $newlang=$srcobject->thirdparty->default_lang;
										if (! empty($newlang))
										{
											$outputlangs = new Translate("",$conf);
											$outputlangs->setDefaultLang($newlang);
										}
	
										$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["libelle"])) ? $prod->multilangs[$outputlangs->defaultlang]["libelle"] : $lines[$i]->product_label;
									}
									else
									{
										$label = $lines[$i]->product_label;
									}
	
									if ($conf->global->PRODUIT_DESC_IN_FORM)
										$desc .= ($lines[$i]->desc && $lines[$i]->desc!=$lines[$i]->libelle)?dol_htmlentitiesbr($lines[$i]->desc):'';
								}
								else {
								    $desc = dol_htmlentitiesbr($lines[$i]->desc);
						        }
	
			                    $result = $object->addline(
					                $desc,
					                $lines[$i]->subprice,
					                $lines[$i]->qty,
					                $lines[$i]->tva_tx,
					                $lines[$i]->localtax1_tx,
					                $lines[$i]->localtax2_tx,
					                $lines[$i]->fk_product,
					                $lines[$i]->remise_percent,
					                $lines[$i]->date_start,
					                $lines[$i]->date_end,
					                'HT',
					                0,
					                $lines[$i]->info_bits,
				                    $lines[$i]->fk_fournprice,
				                    $lines[$i]->pa_ht,
			                        array(),
				                    $lines[$i]->fk_unit
			                    );
	
			                    if ($result < 0)
			                    {
			                        $error++;
			                        break;
			                    }
	
							}
		                }
		            }
		            else
		            {
		                setEventMessage($srcobject->error,'errors');
		                $error++;
		            }
		        }
		        else
		        {
		            setEventMessage($object->error,'errors');
		            $error++;
		        }
		    }
		    else
		    {
		        $result = $object->create($user);
		        if ($result > 0)
		        {
		            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		            exit;
		        }
		        else {
		        	setEventMessage($object->error,'errors');
		        }
		        $action='create';
			}
	    }
	}
	
	else if ($action == 'classin' && $user->rights->contrat->creer)
	{
	    $object->setProject(GETPOST('projectid'));
	}
	
	// Add a new line
	else if ($action == 'addline' && $user->rights->contrat->creer)
	{
		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		if (GETPOST('prod_entry_mode') == 'free')
		{
			$idprod=0;
			$price_ht = GETPOST('price_ht');
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		}
		else
		{
			$idprod=GETPOST('idprod', 'int');
			$price_ht = '';
			$tva_tx = '';
		}
	
		$qty = GETPOST('qty'.$predef);
		$remise_percent=GETPOST('remise_percent'.$predef);
	
	    if ($qty == '')
	    {
	    	setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")),'errors');
	    	$error++;
	    }
	    if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && empty($product_desc))
	    {
	    	setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")),'errors');
	    	$error++;
	    }
	
	    // Extrafields
	    $extrafieldsline = new ExtraFields($db);
	    $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	    $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
	    // Unset extrafield
	    if (is_array($extralabelsline)) {
	    	// Get extra fields
	    	foreach ($extralabelsline as $key => $value) {
	    		unset($_POST["options_" . $key]);
	    	}
	    }
	
	    if (! $error)
	    {
			// Clean parameters
			$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha')?GETPOST('price_base_type', 'alpha'):'HT');
	
	        // Ecrase $pu par celui du produit
	        // Ecrase $desc par celui du produit
	        // Ecrase $txtva par celui du produit
	        // Ecrase $base_price_type par celui du produit
	        if ($idprod > 0)
	        {
	            $prod = new Product($db);
	            $prod->fetch($idprod);
	
	            $tva_tx = get_default_tva($mysoc,$object->thirdparty,$prod->id);
	            $tva_npr = get_default_npr($mysoc,$object->thirdparty,$prod->id);
	            $pu_ht = $prod->price;
	            $pu_ttc = $prod->price_ttc;
	            $price_min = $prod->price_min;
	            $price_base_type = $prod->price_base_type;
	
	            // On defini prix unitaire
	            if ($conf->global->PRODUIT_MULTIPRICES && $object->thirdparty->price_level)
	            {
	                $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
	                $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
	                $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
	                $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
	            }
	        	elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
				{
					require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';
	
					$prodcustprice = new Productcustomerprice($db);
	
					$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);
	
					$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					if ($result) {
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines [0]->price);
							$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
							$price_base_type = $prodcustprice->lines [0]->price_base_type;
							$prod->tva_tx = $prodcustprice->lines [0]->tva_tx;
						}
					}
				}
	
	            // On reevalue prix selon taux tva car taux tva transaction peut etre different
	            // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
	            if ($tva_tx != $prod->tva_tx)
	            {
	                if ($price_base_type != 'HT')
	                {
	                    $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
	                }
	                else
	              {
	                    $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
	                }
	            }
	
	           	$desc=$prod->description;
	           	$desc=dol_concatdesc($desc,$product_desc);
		        $fk_unit = $prod->fk_unit;
	        }
	        else
			{
	            $pu_ht=GETPOST('price_ht');
	            $price_base_type = 'HT';
	            $tva_tx=GETPOST('tva_tx')?str_replace('*','',GETPOST('tva_tx')):0;		// tva_tx field may be disabled, so we use vat rate 0
	            $tva_npr=preg_match('/\*/',GETPOST('tva_tx'))?1:0;
	            $desc=$product_desc;
				$fk_unit= GETPOST('units', 'alpha');
	        }
	
	        $localtax1_tx=get_localtax($tva_tx,1,$object->thirdparty);
	        $localtax2_tx=get_localtax($tva_tx,2,$object->thirdparty);
	
			// ajout prix achat
			$fk_fournprice = $_POST['fournprice'];
			if ( ! empty($_POST['buying_price']) )
			  $pa_ht = $_POST['buying_price'];
			else
			  $pa_ht = null;
	
	        $info_bits=0;
	        if ($tva_npr) $info_bits |= 0x01;
	
	        if($price_min && (price2num($pu_ht)*(1-price2num($remise_percent)/100) < price2num($price_min)))
	        {
	            $object->error = $langs->trans("CantBeLessThanMinPrice",price(price2num($price_min,'MU'),0,$langs,0,0,-1,$conf->currency));
	            $result = -1 ;
	        }
	        else
			{
	            // Insert line
	            $result = $object->addline(
	                $desc,
	                $pu_ht,
	                $qty,
	                $tva_tx,
	                $localtax1_tx,
	                $localtax2_tx,
	                $idprod,
	                $remise_percent,
	                $date_start,
	                $date_end,
	                $price_base_type,
	                $pu_ttc,
	                $info_bits,
	      			$fk_fournprice,
	      			$pa_ht,
	            	$array_options,
		            $fk_unit
	            );
	        }
	
	        if ($result > 0)
	        {
	        	// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
	
					$ret = $object->fetch($id); // Reload to get new records
	
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
	
				unset($_POST ['prod_entry_mode']);
	
				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST ['np_marginRate']);
				unset($_POST ['np_markRate']);
				unset($_POST['dp_desc']);
				unset($_POST['idprod']);
	
		    	unset($_POST['date_starthour']);
		    	unset($_POST['date_startmin']);
		    	unset($_POST['date_startsec']);
		    	unset($_POST['date_startday']);
		    	unset($_POST['date_startmonth']);
		    	unset($_POST['date_startyear']);
		    	unset($_POST['date_endhour']);
		    	unset($_POST['date_endmin']);
		    	unset($_POST['date_endsec']);
		    	unset($_POST['date_endday']);
		    	unset($_POST['date_endmonth']);
		    	unset($_POST['date_endyear']);
	        }
	        else
	        {
	        	setEventMessage($object->error,'errors');
	        }
	    }
	}
	
	else if ($action == 'updateligne' && $user->rights->contrat->creer && ! GETPOST('cancel'))
	{
	    $objectline = new ContratLigne($db);
	    if ($objectline->fetch(GETPOST('elrowid')))
	    {
	        $db->begin();
	
	        if ($date_start_real_update == '') $date_start_real_update=$objectline->date_ouverture;
	        if ($date_end_real_update == '')   $date_end_real_update=$objectline->date_cloture;
	
			$localtax1_tx=get_localtax(GETPOST('eltva_tx'),1,$object->thirdparty);
	        $localtax2_tx=get_localtax(GETPOST('eltva_tx'),2,$object->thirdparty);
	
		  	// ajout prix d'achat
		  	$fk_fournprice = $_POST['fournprice'];
		  	if ( ! empty($_POST['buying_price']) )
		  	  $pa_ht = $_POST['buying_price'];
		  	else
		  	  $pa_ht = null;
	
		    $fk_unit = GETPOST('unit', 'alpha');
	
	        $objectline->description=GETPOST('product_desc');
	        $objectline->price_ht=GETPOST('elprice');
	        $objectline->subprice=GETPOST('elprice');
	        $objectline->qty=GETPOST('elqty');
	        $objectline->remise_percent=GETPOST('elremise_percent');
	        $objectline->tva_tx=GETPOST('eltva_tx')?GETPOST('eltva_tx'):0;	// Field may be disabled, so we use vat rate 0
	        $objectline->localtax1_tx=$localtax1_tx;
	        $objectline->localtax2_tx=$localtax2_tx;
	        $objectline->date_ouverture_prevue=$date_start_update;
	        $objectline->date_ouverture=$date_start_real_update;
	        $objectline->date_fin_validite=$date_end_update;
	        $objectline->date_cloture=$date_end_real_update;
	        $objectline->fk_user_cloture=$user->id;
	        $objectline->fk_fournprice=$fk_fournprice;
	        $objectline->pa_ht=$pa_ht;
	
		    if ($fk_unit > 0) {
			    $objectline->fk_unit = GETPOST('unit');
		    } else {
			    $objectline->fk_unit = null;
		    }
	
	        // Extrafields
	        $extrafieldsline = new ExtraFields($db);
	        $extralabelsline = $extrafieldsline->fetch_name_optionals_label($objectline->table_element);
	        $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
	        $objectline->array_options=$array_options;
	
	        // TODO verifier price_min si fk_product et multiprix
	
	        $result=$objectline->update($user);
	        if ($result > 0)
	        {
	            $db->commit();
	        }
	        else
	        {
	        	setEventMessage($objectline->error,'errors');
	            $db->rollback();
	        }
	    }
	    else
	    {
	    	setEventMessage($objectline->error,'errors');
	    }
	}
	
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->contrat->creer)
	{
	    $result = $object->deleteline(GETPOST('lineid'),$user);
	
	    if ($result >= 0)
	    {
	        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
	        exit;
	    }
	    else
	    {
	    	setEventMessage($object->error,'errors');
	    }
	}
	
	else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->contrat->creer)
	{
	    $result = $object->validate($user);
	}
	
	// Close all lines
	else if ($action == 'confirm_close' && $confirm == 'yes' && $user->rights->contrat->creer)
	{
	    $object->cloture($user);
	}
	
	else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->contrat->supprimer)
	{
		$result=$object->delete($user);
		if ($result >= 0)
		{
			header("Location: index.php");
			return;
		}
		else
		{
			setEventMessage($object->error,'errors');
		}
	}
	
	else if ($action == 'confirm_move' && $confirm == 'yes' && $user->rights->contrat->creer)
	{
		if (GETPOST('newcid') > 0)
		{
			$contractline = new ContratLigne($db);
			$result=$contractline->fetch(GETPOST('lineid'));
			$contractline->fk_contrat = GETPOST('newcid');
			$result=$contractline->update($user,1);
			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
				return;
			}
			else
			{
				setEventMessage($object->error,'errors');
			}
		}
		else
		{
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("RefNewContract")),'errors');
		}
	} else if ($action == 'update_extras') {
		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
		if ($ret < 0)
			$error ++;
	
		if (! $error) {
	
				$result = $object->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			} else if ($reshook < 0)
				$error ++;
	
		if ($error) {
			$action = 'edit_extras';
			setEventMessage($object->error,'errors');
		}
	} elseif ($action=='setref_supplier') {
		$cancelbutton = GETPOST('cancel');
	
		if (!$cancelbutton) {
	
			$result = $object->fetch($id);
			if ($result < 0) {
				setEventMessage($object->errors, 'errors');
			}
	
	        $result = $object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
			if ($result < 0) {
				setEventMessage($object->errors, 'errors');
				$action = 'editref_supplier';
			} else {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
		}
	    else {
	        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	        exit;
	    }
	} elseif ($action=='setref') {
	    $cancelbutton = GETPOST('cancel');
	
	    if (!$cancelbutton) {
	        $result = $object->fetch($id);
	        if ($result < 0) {
	            setEventMessage($object->errors, 'errors');
	        }
	
	        $result = $object->setValueFrom('ref',GETPOST('ref','alpha'));;
	        if ($result < 0) {
	            setEventMessage($object->errors, 'errors');
	            $action = 'editref';
	        } else {
	            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
	            exit;
	        }
	    }
	    else {
	        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	        exit;
	    }
	}
	
	// Generation doc (depuis lien ou depuis cartouche doc)
	else if ($action == 'builddoc' && $user->rights->contrat->creer) {
		if (GETPOST('model')) {
			$object->setDocModel($user, GETPOST('model'));
		}
	
		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS)) {
			$outputlangs = new Translate("", $conf);
			$newlang = (GETPOST('lang_id') ? GETPOST('lang_id') : $object->thirdparty->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret = $object->fetch($id); // Reload to get new records
		$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		if ($result <= 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
		}
	}
	
	// Remove file in doc form
	else if ($action == 'remove_file' && $user->rights->contrat->creer) {
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	
			$langs->load("other");
			$upload_dir = $conf->contrat->dir_output;
			$file = $upload_dir . '/' . GETPOST('file');
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
			else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
		}
	}
	
	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->contrat->creer)
	{
		if ($action == 'addcontact')
		{
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $object->add_contact($contactid, GETPOST('type'), GETPOST('source'));
	
			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else
			{
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$langs->load("errors");
					setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"),'errors');
				}
				else
				{
					setEventMessage($object->error,'errors');
				}
			}
		}
	
		// bascule du statut d'un contact
		else if ($action == 'swapstatut')
		{
			$result=$object->swapContactStatus(GETPOST('ligne'));
		}
	
		// Efface un contact
		else if ($action == 'deletecontact')
		{
			$result = $object->delete_contact(GETPOST('lineid'));
	
			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else {
				setEventMessage($object->error,'errors');
			}
		}
	}
}

/*
 * View
 */

llxHeader('',$langs->trans("ContractCard"),"Contrat");

$form = new Form($db);
$formfile = new FormFile($db);

$objectlignestatic=new ContratLigne($db);

// Load object modContract
$module=(! empty($conf->global->CONTRACT_ADDON)?$conf->global->CONTRACT_ADDON:'mod_contract_serpis');
if (substr($module, 0, 13) == 'mod_contract_' && substr($module, -3) == 'php')
{
	$module = substr($module, 0, dol_strlen($module)-4);
}
$result=dol_include_once('/core/modules/contract/'.$module.'.php');
if ($result > 0)
{
	$modCodeContract = new $module();
}


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($action == 'create')
{
	print load_fiche_titre($langs->trans('AddContract'),'','title_commercial.png');

    $soc = new Societe($db);
    if ($socid>0) $soc->fetch($socid);

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');
        if (preg_match('/^([^_]+)_([^_]+)/i',GETPOST('origin'),$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
        }
        else
        {
            // For compatibility
            if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
            if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
            $objectsrc->fetch_thirdparty();

            $projectid          = (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');

            $soc = $objectsrc->client;

            $note_private		= (! empty($objectsrc->note_private) ? $objectsrc->note_private : '');
            $note_public		= (! empty($objectsrc->note_public) ? $objectsrc->note_public : '');

            // Object source contacts list
            $srccontactslist = $objectsrc->liste_contact(-1,'external',1);
        }
    }
    else {
		$projectid = GETPOST('projectid','int');
		$note_private = GETPOST("note_private");
		$note_public = GETPOST("note_public");
	}

    $object->date_contrat = dol_now();

    print '<form name="form_contract" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
    print '<input type="hidden" name="remise_percent" value="0">';

    dol_fiche_head();

    print '<table class="border" width="100%">';

    // Ref
    if (! empty($modCodeContract->code_auto)) {
    	$tmpcode=$langs->trans("Draft");
    } else {
    	$tmpcode='<input name="ref" size="20" maxlength="128" value="'.dol_escape_htmltag(GETPOST('ref')?GETPOST('ref'):$tmpcode).'">';
    }
	print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$tmpcode.'</td></tr>';

	// Ref supplier
	print '<tr><td>'.$langs->trans('RefSupplier').'</td>';
	print '<td colspan="2"><input type="text" size="5" name="ref_supplier" id="ref_supplier" value="'.GETPOST('ref_supplier','alpha').'"></td></tr>';

    // Customer
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
	if($socid>0)
	{
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	}
	else
	{
		print '<td colspan="2">';
		print $form->select_company('','socid','s.client = 1 OR s.client = 3',1);
		print '</td>';
	}
	print '</tr>'."\n";

	if($socid>0)
	{
		// Ligne info remises tiers
		print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
		if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_percent);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		print '. ';
		$absolute_discount=$soc->getAvailableDiscounts();
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';
	}

    // Commercial suivi
    print '<tr><td width="20%" class="nowrap"><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPFOLL").'</span></td><td>';
    print $form->select_dolusers(GETPOST("commercial_suivi_id")?GETPOST("commercial_suivi_id"):$user->id,'commercial_suivi_id',1,'');
    print '</td></tr>';

    // Commercial signature
    print '<tr><td width="20%" class="nowrap"><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPSIGN").'</span></td><td>';
    print $form->select_dolusers(GETPOST("commercial_signature_id")?GETPOST("commercial_signature_id"):$user->id,'commercial_signature_id',1,'');
    print '</td></tr>';

    print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td>';
    $form->select_date($datecontrat,'',0,0,'',"contrat");
    print "</td></tr>";

    if (! empty($conf->projet->enabled))
    {
    	$formproject=new FormProjets($db);

        print '<tr><td>'.$langs->trans("Project").'</td><td>';
        $formproject->select_projects($soc->id,$projectid,"projectid");
        print "</td></tr>";
    }

    print '<tr><td>'.$langs->trans("NotePublic").'</td><td valign="top">';


    $doleditor=new DolEditor('note_public', $note_public, '', '100', 'dolibarr_notes', 'In', 1, true, true, ROWS_3, 70);
    print $doleditor->Create(1);


    if (empty($user->societe_id))
    {
        print '<tr><td>'.$langs->trans("NotePrivate").'</td><td valign="top">';
        $doleditor=new DolEditor('note_private', $note_private, '', '100', 'dolibarr_notes', 'In', 1, true, true, ROWS_3, 70);
        print $doleditor->Create(1);
        print '</td></tr>';
    }

    // Other attributes
    $parameters=array('objectsrc' => $objectsrc,'colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

    // Other attributes
    if (empty($reshook) && ! empty($extrafields->attribute_label)) {
    	print $object->showOptionals($extrafields, 'edit');
    }

    print "</table>\n";

    dol_fiche_end();

    print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div>';

    if (is_object($objectsrc))
    {
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        if (empty($conf->global->CONTRACT_SUPPORT_PRODUCTS))
        {
        	print '<br>'.$langs->trans("Note").': '.$langs->trans("OnlyLinesWithTypeServiceAreUsed");
        }
	}

    print "</form>\n";
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
    $now=dol_now();

    if ($object->id > 0)
    {
    	$object->fetch_thirdparty();

        $result=$object->fetch_lines();	// This also init $this->nbofserviceswait, $this->nbofservicesopened, $this->nbofservicesexpired=, $this->nbofservicesclosed
        if ($result < 0) dol_print_error($db,$object->error);

        $nbofservices=count($object->lines);

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $commercial_signature = new User($db);
        $commercial_signature->fetch($object->commercial_signature_id);

        $commercial_suivi = new User($db);
        $commercial_suivi->fetch($object->commercial_suivi_id);

        $head = contract_prepare_head($object);

        $hselected = 0;

        dol_fiche_head($head, $hselected, $langs->trans("Contract"), 0, 'contract');


        /*
         * Confirmation de la suppression du contrat
         */
        if ($action == 'delete')
        {
            print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("DeleteAContract"),$langs->trans("ConfirmDeleteAContract"),"confirm_delete",'',0,1);

        }

        /*
         * Confirmation de la validation
         */
        if ($action == 'valid')
        {
        	$ref = substr($object->ref, 1, 4);
        	if ($ref == 'PROV' && !empty($modCodeContract->code_auto))
        	{
        		$numref = $object->getNextNumRef($object->thirdparty);
        	}
        	else
        	{
        		$numref = $object->ref;
        	}

        	$text=$langs->trans('ConfirmValidateContract',$numref);

            print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("ValidateAContract"),$text,"confirm_valid",'',0,1);

        }

        /*
         * Confirmation de la fermeture
         */
        if ($action == 'close')
        {
            print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("CloseAContract"),$langs->trans("ConfirmCloseContract"),"confirm_close",'',0,1);

        }

        /*
         *   Contrat
         */
        if (! empty($object->brouillon) && $user->rights->contrat->creer)
        {
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setremise">';
        }

        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

        // Ref du contrat
        if (!empty($modCodeContract->code_auto)) {
	        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	        print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
	        print "</td></tr>";
        } else {
        	print '<tr>';
        	print '<td  width="20%">';
        	print $form->editfieldkey("Ref",'ref',$object->ref,$object,$user->rights->contrat->creer);
        	print '</td><td>';
        	print $form->editfieldval("Ref",'ref',$object->ref,$object,$user->rights->contrat->creer);
        	print '</td>';
        	print '</tr>';
        }

        print '<tr>';
		print '<td  width="20%">';
		print $form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->contrat->creer);
		print '</td><td>';
		print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->contrat->creer);
		print '</td>';
		print '</tr>';

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

        // Ligne info remises tiers
        print '<tr><td>'.$langs->trans('Discount').'</td><td colspan="3">';
        if ($object->thirdparty->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$object->thirdparty->remise_percent);
        else print $langs->trans("CompanyHasNoRelativeDiscount");
        $absolute_discount=$object->thirdparty->getAvailableDiscounts();
        print '. ';
        if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
        else print $langs->trans("CompanyHasNoAbsoluteDiscount");
        print '.';
        print '</td></tr>';

        // Statut contrat
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        if ($object->statut==0) print $object->getLibStatut(2);
        else print $object->getLibStatut(4);
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dol_print_date($object->date_contrat,"dayhour")."</td></tr>\n";

        // Projet
        if (! empty($conf->projet->enabled))
        {
            $langs->load("projects");
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            if ($action != "classify" && $user->rights->projet->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == "classify")
            {
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id,$object->socid,$object->fk_project,"projectid", 0, 0, 1);
            }
            else
            {
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id,$object->socid,$object->fk_project,"none", 0, 0);
            }
            print "</td></tr>";
        }

        // Other attributes
        $cols = 3;
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print "</table>";

        if (! empty($object->brouillon) && $user->rights->contrat->creer)
        {
            print '</form>';
        }

        echo '<br>';

        if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
        	$blocname = 'contacts';
        	$title = $langs->trans('ContactsAddresses');
        	include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
        }

        if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
        {
        	$blocname = 'notes';
        	$title = $langs->trans('Notes');
        	include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
        }


        $colorb='666666';

        $arrayothercontracts=$object->getListOfContracts('others');

        /*
         * Lines of contracts
         */

		$productstatic=new Product($db);

        $usemargins=0;
		if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;

        $var=false;

		// Title line for service
        $cursorline=1;
        while ($cursorline <= $nbofservices)
        {
            print '<form name="update" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="updateligne">';
            print '<input type="hidden" name="elrowid" value="'.GETPOST('rowid').'">';
            print '<input type="hidden" name="idprod" value="'.($objp->fk_product?$objp->fk_product:'0').'">';
            print '<input type="hidden" name="fournprice" value="'.($objp->fk_fournprice?$objp->fk_fournprice:'0').'">';

            // Area with common detail of line
            print '<table class="notopnoleftnoright allwidth tableforservicepart1" width="100%">';

            $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
            $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
            $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
            $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
            $sql.= " cd.commentaire as comment, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht,";
	        $sql.= " cd.fk_unit,";
            $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
            $sql.= " WHERE cd.rowid = ".$object->lines[$cursorline-1]->id;

            $result = $db->query($sql);
            if ($result)
            {
                $total = 0;

                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("ServiceNb",$cursorline).'</td>';
                print '<td width="50" align="center">'.$langs->trans("VAT").'</td>';
                print '<td width="50" align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
	            if ($conf->global->PRODUCT_USE_UNITS) print '<td width="30" align="left">'.$langs->trans("Unit").'</td>';
                print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
				if (! empty($conf->margin->enabled) && ! empty($conf->global->MARGIN_SHOW_ON_CONTRACT)) print '<td width="50" align="right">'.$langs->trans("BuyingPrice").'</td>';
                print '<td width="30">&nbsp;</td>';
                print "</tr>\n";

                $objp = $db->fetch_object($result);

                $var=!$var;

                if ($action != 'editline' || GETPOST('rowid') != $objp->rowid)
                {
                    print '<tr '.$bc[$var].' valign="top">';
                    // Libelle
                    if ($objp->fk_product > 0)
                    {
                        print '<td>';
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        $text = $productstatic->getNomUrl(1,'',20);
                        if ($objp->label)
                        {
                        	$text .= ' - ';
                        	$productstatic->ref=$objp->label;
                        	$text .= $productstatic->getNomUrl(0,'',16);
                        }
                        $description = $objp->description;

	                    // Add description in form
						if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
						{
							$text .= (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
							$description = '';	// Already added into main visible desc
						}

                        echo $form->textwithtooltip($text,$description,3,'','',$cursorline,0,(!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));

                        print '</td>';
                    }
                    else
					{
                        print '<td>'.dol_htmlentitiesbr($objp->description)."</td>\n";
                    }
                    // TVA
                    print '<td align="center">'.vatrate($objp->tva_tx,'%',$objp->info_bits).'</td>';
                    // Prix
                    print '<td align="right">'.($objp->subprice != '' ? price($objp->subprice) : '')."</td>\n";
                    // Quantite
                    print '<td align="center">'.$objp->qty.'</td>';
	                // Unit
	                if($conf->global->PRODUCT_USE_UNITS) print '<td align="left">'.$langs->trans($object->lines[$cursorline-1]->getLabelOfUnit()).'</td>';
                    // Remise
                    if ($objp->remise_percent > 0)
                    {
                        print '<td align="right" '.$bc[$var].'>'.$objp->remise_percent."%</td>\n";
                    }
                    else
                    {
                        print '<td>&nbsp;</td>';
                    }

					// Margin
					if (! empty($conf->margin->enabled) && ! empty($conf->global->MARGIN_SHOW_ON_CONTRACT)) print '<td align="right" class="nowrap">'.price($objp->pa_ht).'</td>';

                    // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                    print '<td align="right" class="nowrap">';
                    if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0))
                    {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=move&amp;rowid='.$objp->rowid.'">';
                        print img_picto($langs->trans("MoveToAnotherContract"),'uparrow');
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ($user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
                        print img_edit();
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ( $user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '&nbsp;';
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';

                    print "</tr>\n";

                    // Dates de en service prevues et effectives
                    if ($objp->subprice >= 0)
                    {
	                    $colspan = 6;

	                    if ($conf->margin->enabled && $conf->global->PRODUCT_USE_UNITS) {
		                    $colspan = 8;
	                    } elseif ($conf->margin->enabled || $conf->global->PRODUCT_USE_UNITS) {
		                    $colspan = 7;
	                    }

                        print '<tr '.$bc[$var].'>';
                        print '<td colspan="'.$colspan.'">';

                        // Date planned
                        print $langs->trans("DateStartPlanned").': ';
                        if ($objp->date_debut)
                        {
                            print dol_print_date($db->jdate($objp->date_debut));
                            // Warning si date prevu passee et pas en service
                            if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");
                        print ' &nbsp;-&nbsp; ';
                        print $langs->trans("DateEndPlanned").': ';
                        if ($objp->date_fin)
                        {
                            print dol_print_date($db->jdate($objp->date_fin));
                            if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");

                        print '</td>';
                        print '</tr>';
                    }


                    //Display lines extrafields
                    if (is_array($extralabelslines) && count($extralabelslines)>0) {
                    	print '<tr '.$bc[$var].'>';
                    	$line = new ContratLigne($db);
                    	$line->fetch_optionals($objp->rowid,$extralabelslines);
                    	print $line->showOptionals($extrafieldsline, 'view', array('style'=>$bc[$var], 'colspan'=>$colspan));
                    	print '</tr>';
                    }
                }
                // Ligne en mode update
                else
              {
                    // Ligne carac
                    print "<tr ".$bc[$var].">";
                    print '<td>';
                    if ($objp->fk_product)
                    {
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        print $productstatic->getNomUrl(1,'',20);
                        print $objp->label?' - '.dol_trunc($objp->label,16):'';
                        print '<br>';
                    }
                    else
                    {
                        print $objp->label?$objp->label.'<br>':'';
                    }

                    // editeur wysiwyg
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $nbrows=ROWS_2;
                    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
                    $enable=(isset($conf->global->FCKEDITOR_ENABLE_DETAILS)?$conf->global->FCKEDITOR_ENABLE_DETAILS:0);
                    $doleditor=new DolEditor('product_desc',$objp->description,'',92,'dolibarr_details','',false,true,$enable,$nbrows,70);
                    $doleditor->Create();

                    print '</td>';
                    print '<td align="right">';
                    print $form->load_tva("eltva_tx",$objp->tva_tx,$mysoc,$object->thirdparty);
                    print '</td>';
                    print '<td align="right"><input size="5" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
                    print '<td align="center"><input size="2" type="text" name="elqty" value="'.$objp->qty.'"></td>';
                    if ($conf->global->PRODUCT_USE_UNITS)
                    {
                    	print '<td align="left">';
                    	print $form->selectUnits($objp->fk_unit, "unit");
                    	print '</td>';
                    }
                    print '<td align="right" class="nowrap"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
					if (! empty($usemargins))
					{
					    print '<td align="right">';
					    if ($objp->fk_product) print '<select id="fournprice" name="fournprice"></select>';
						print '<input id="buying_price" type="text" size="5" name="buying_price" value="'.price($objp->pa_ht,0,'',0).'"></td>';
					}
                    print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
                    print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                    print '</td>';

                    $colspan=5;
                    if (! empty($conf->margin->enabled) && ! empty($conf->global->MARGIN_SHOW_ON_CONTRACT)) $colspan++;
	              if($conf->global->PRODUCT_USE_UNITS) $colspan++;

                    // Ligne dates prevues
                    print "<tr ".$bc[$var].">";
                    print '<td colspan="'.$colspan.'">';
                    print $langs->trans("DateStartPlanned").' ';
                    $form->select_date($db->jdate($objp->date_debut),"date_start_update",$usehm,$usehm,($db->jdate($objp->date_debut)>0?0:1),"update");
                    print ' &nbsp;&nbsp;'.$langs->trans("DateEndPlanned").' ';
                    $form->select_date($db->jdate($objp->date_fin),"date_end_update",$usehm,$usehm,($db->jdate($objp->date_fin)>0?0:1),"update");
                    print '</td>';

                    if (is_array($extralabelslines) && count($extralabelslines)>0) {
                    	print '<tr '.$bc[$var].'>';
                    	$line = new ContratLigne($db);
                    	$line->fetch_optionals($objp->rowid,$extralabelslines);
                    	print $line->showOptionals($extrafieldsline, 'edit', array('style'=>$bc[$var], 'colspan'=>$colspan));
                    	print '</tr>';
                    }

                    print '</tr>';
                }

                $db->free($result);
            }
            else
			{
                dol_print_error($db);
            }

            if ($object->statut > 0)
            {
                print '<tr '.$bc[$var].'>';
                print '<td colspan="'.($conf->margin->enabled?7:6).'"><hr></td>';
                print "</tr>\n";
            }

            print "</table>";

            print "</form>\n";


            /*
             * Confirmation to delete service line of contract
             */
            if ($action == 'deleteline' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == GETPOST('rowid'))
            {
                print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".GETPOST('rowid'),$langs->trans("DeleteContractLine"),$langs->trans("ConfirmDeleteContractLine"),"confirm_deleteline",'',0,1);
                if ($ret == 'html') print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[$var].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation to move service toward another contract
             */
            if ($action == 'move' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == GETPOST('rowid'))
            {
                $arraycontractid=array();
                foreach($arrayothercontracts as $contractcursor)
                {
                    $arraycontractid[$contractcursor->id]=$contractcursor->ref;
                }
                //var_dump($arraycontractid);
                // Cree un tableau formulaire
                $formquestion=array(
				'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

                $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".GETPOST('rowid'),$langs->trans("MoveToAnotherContract"),$langs->trans("ConfirmMoveToAnotherContract"),"confirm_move",$formquestion);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[$var].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation activation
             */
            if ($action == 'active' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                $dateactend   = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                $comment      = GETPOST('comment');
                $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".GETPOST('ligne')."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment),$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",dol_print_date($dateactstart,"%A %d %B %Y")),"confirm_active", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[$var].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation fermeture
             */
            if ($action == 'closeline' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                $dateactend   = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                $comment      = GETPOST('comment');
                $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".GETPOST('ligne')."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService",dol_print_date($dateactend,"%A %d %B %Y")), "confirm_closeline", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[$var].' height="6"><td></td></tr></table>';
            }


            // Area with status and activation info of line
            if ($object->statut > 0)
            {
                print '<table class="notopnoleftnoright tableforservicepart2" width="100%">';

                print '<tr '.$bc[$var].'>';
                print '<td>'.$langs->trans("ServiceStatus").': '.$object->lines[$cursorline-1]->getLibStatut(4).'</td>';
                print '<td width="30" align="right">';
                if ($user->societe_id == 0)
                {
                    if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline')
                    {
                        $tmpaction='activateline';
                        if ($objp->statut == 4) $tmpaction='unactivateline';
                        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action='.$tmpaction.'">';
                        print img_edit();
                        print '</a>';
                    }
                }
                print '</td>';
                print "</tr>\n";

                print '<tr '.$bc[$var].'>';

                print '<td>';
                // Si pas encore active
                if (! $objp->date_debut_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    if ($objp->date_debut_reelle) print dol_print_date($objp->date_debut_reelle);
                    else print $langs->trans("ContractStatusNotRunning");
                }
                // Si active et en cours
                if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                }
                // Si desactive
                if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                    print ' &nbsp;-&nbsp; ';
                    print $langs->trans("DateEndReal").': ';
                    print dol_print_date($objp->date_fin_reelle);
                }
                if (! empty($objp->comment)) print "<br>".$objp->comment;
                print '</td>';

                print '<td align="center">&nbsp;</td>';

                print '</tr>';
                print '</table>';
            }

            if ($user->rights->contrat->activer && $action == 'activateline' && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                /**
                 * Activer la ligne de contrat
                 */
                print '<form name="active" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.GETPOST('ligne').'&amp;action=active" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="notopnoleftnoright tableforservicepart2" width="100%">';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut;
                if (GETPOST('remonth')) $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin;
                if (GETPOST('endmonth')) $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }

                print '<tr '.$bc[$var].'><td>'.$langs->trans("DateServiceActivate").'</td><td>';
                print $form->select_date($dateactstart,'',$usehm,$usehm,'',"active",1,0,1);
                print '</td>';

                print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
                print $form->select_date($dateactend,"end",$usehm,$usehm,'',"active",1,0,1);
                print '</td>';

                print '<td align="center" rowspan="2" valign="middle">';
                print '<input type="submit" class="button" name="activate" value="'.$langs->trans("Activate").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td>';

                print '</tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="'.($conf->margin->enabled?4:3).'"><input size="80" type="text" name="comment" value="'.$_POST["comment"].'"></td></tr>';

                print '</table>';

                print '</form>';
            }

            if ($user->rights->contrat->activer && $action == 'unactivateline' && $object->lines[$cursorline-1]->id == GETPOST('ligne'))
            {
                /**
                 * Desactiver la ligne de contrat
                 */
                print '<form name="closeline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action=closeline" method="post">';

                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="noborder tableforservicepart2" width="100%">';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut_reelle;
                if (GETPOST('remonth')) $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin_reelle;
                if (GETPOST('endmonth')) $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }
                $now=dol_now();
                if ($dateactend > $now) $dateactend=$now;

                print '<tr '.$bc[$var].'><td colspan="2">';
                if ($objp->statut >= 4)
                {
                    if ($objp->statut == 4)
                    {
                        print $langs->trans("DateEndReal").' ';
                        print $form->select_date($dateactend,"end",$usehm,$usehm,($objp->date_fin_reelle>0?0:1),"closeline",1,1,1);
                    }
                }
                print '</td>';

                print '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="'.$langs->trans("Close").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td></tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td><input size="70" type="text" class="flat" name="comment" value="'.GETPOST('comment').'"></td></tr>';
                print '</table>';

                print '</form>';
            }

            $cursorline++;
        }

		// Form to add new line
        if ($user->rights->contrat->creer && ($object->statut == 0))
        {
        	$dateSelector=1;

			print "\n";
			print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline')?'#add':'#line_'.GETPOST('lineid')).'" method="POST">
			<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
			<input type="hidden" name="action" value="'.(($action != 'editline')?'addline':'updateligne').'">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="id" value="'.$object->id.'">
			';

			print '<br>';
            print '<table id="tablelines" class="noborder noshadow" width="100%">';	// Array with (n*2)+1 lines

            // Trick to not show product entries
            $savproductenabled=$conf->product->enabled;
            if (empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $conf->product->enabled = 0;

        	// Form to add new line
       		if ($action != 'editline')
			{
				$var = true;

				// Add free products/services
				$object->formAddObjectLine(1, $mysoc, $soc);

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}

        	// Restore correct setup
        	$conf->product->enabled = $savproductenabled;

            print '</table>';

            print '</form>';
        }

		dol_fiche_end();


        /*
         * Buttons
         */

        if ($user->societe_id == 0)
        {
            print '<div class="tabsAction">';

            $parameters=array();
            $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

            if ($object->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans("Validate").'</a></div>';
                else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a></div>';
            }

            if (! empty($conf->facture->enabled) && $object->statut > 0 && $object->nbofservicesclosed < $nbofservices)
            {
                $langs->load("bills");
                if ($user->rights->facture->creer) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->thirdparty->id.'">'.$langs->trans("CreateBill").'</a></div>';
                else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a></div>';
            }

            if ($object->nbofservicesclosed < $nbofservices)
            {
                //if (! $numactive)
                //{
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a></div>';
                //}
                //else
                //{
                //	print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("CloseRefusedBecauseOneServiceActive").'">'.$langs->trans("Close").'</a></div>';
                //}
            }

            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $object->statut == 0) || $user->rights->contrat->supprimer)
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a></div>';
            }
            else
            {
            	print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("Delete").'</a></div>';
            }

            print "</div>";
        }

        print '<div class="fichecenter"><div class="fichehalfleft">';

        /*
         * Documents generes
        */
        $filename = dol_sanitizeFileName($object->ref);
        $filedir = $conf->contrat->dir_output . "/" . dol_sanitizeFileName($object->ref);
        $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
        $genallowed = $user->rights->contrat->creer;
        $delallowed = $user->rights->contrat->supprimer;

        $var = true;

        $somethingshown = $formfile->show_documents('contract', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);

		// Linked object block
		$somethingshown = $form->showLinkedObjectBlock($object);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object);
		if ($linktoelem) print '<br>'.$linktoelem;


        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

        print '</div></div></div>';
    }
}


llxFooter();

$db->close();
?>

<?php
if ($conf->margin->enabled && $action == 'editline')
{
?>

<script type="text/javascript">
$(document).ready(function() {
  var idprod = $("input[name='idprod']").val();
  var fournprice = $("input[name='fournprice']").val();
  if (idprod > 0) {
	  $.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': idprod}, function(data) {
	    if (data.length > 0) {
	      var options = '';
	      var trouve=false;
	      $(data).each(function() {
	        options += '<option value="'+this.id+'" price="'+this.price+'"';
	        if (fournprice > 0) {
		        if (this.id == fournprice) {
		          options += ' selected';
		          $("#buying_price").val(this.price);
		          trouve = true;
		        }
	        }
	        options += '>'+this.label+'</option>';
	      });
	      options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
	      $("#fournprice").html(options);
	      if (trouve) {
	        $("#buying_price").hide();
	        $("#fournprice").show();
	      }
	      else {
	        $("#buying_price").show();
	      }
	      $("#fournprice").change(function() {
	        var selval = $(this).find('option:selected').attr("price");
	        if (selval)
	          $("#buying_price").val(selval).hide();
	        else
	          $('#buying_price').show();
	      });
	    }
	    else {
	      $("#fournprice").hide();
	      $('#buying_price').show();
	    }
	  },
	  'json');
	}
    else {
      $("#fournprice").hide();
      $('#buying_price').show();
    }
});
</script>

<?php
}
