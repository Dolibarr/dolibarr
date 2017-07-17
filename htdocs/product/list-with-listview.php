<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2016	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet		    <fmarcet@2byte.es>
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
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/listview.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("suppliers");
$langs->load("companies");
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$sref=GETPOST("sref");
$sbarcode=GETPOST("sbarcode");
$snom=GETPOST("snom");
$sall=GETPOST("sall");
$type= (int) GETPOST("type","int");
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ",'int');
$tosell = GETPOST("tosell", 'int');
$tobuy = GETPOST("tobuy", 'int');
$fourn_id = GETPOST("fourn_id",'int');
$catid = GETPOST('catid','int');
$search_tobatch = GETPOST("search_tobatch",'int');
$search_accountancy_code_sell = GETPOST("search_accountancy_code_sell",'alpha');
$search_accountancy_code_buy = GETPOST("search_accountancy_code_buy",'alpha');
$optioncss = GETPOST('optioncss','alpha');

//Show/hide child products. Hidden by default
if (!$_POST) {
	$search_hidechildproducts = 'on';
} else {
	$search_hidechildproducts = GETPOST('search_hidechildproducts');
}

$diroutputmassaction=$conf->product->dir_output . '/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = (GETPOST("page",'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'productservicelist';
if ((string) $type == '1') { $contextpage='servicelist'; if ($search_type=='') $search_type='1'; }
if ((string) $type == '0') { $contextpage='productlist'; if ($search_type=='') $search_type='0'; }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);
$form=new Form($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('product');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

if (empty($action)) $action='list';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas=GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','list',$canvas);
}

// Security check
if ($type=='0') $result=restrictedArea($user,'produit','','','','','',$objcanvas);
else if ($type=='1') $result=restrictedArea($user,'service','','','','','',$objcanvas);
else $result=restrictedArea($user,'produit|service','','','','','',$objcanvas);

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical=0;
if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
{
    $virtualdiffersfromphysical=1;		// According to increase/decrease stock options, virtual and physical stock may differs.
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"Ref",
    'pfp.ref_fourn'=>"RefSupplier",
	'p.label'=>"ProductLabel",
	'p.description'=>"Description",
    "p.note"=>"Note",
);
// multilang
if (! empty($conf->global->MAIN_MULTILANGS))
{
	$fieldstosearchall['pl.label']='ProductLabelTranslated';
	$fieldstosearchall['pl.description']='ProductDescriptionTranslated';
	$fieldstosearchall['pl.note']='ProductNoteTranslated';
}
if (! empty($conf->barcode->enabled)) {
	$fieldstosearchall['p.barcode']='Gencod';
}

if (empty($conf->global->PRODUIT_MULTIPRICES))
{
	$titlesellprice=$langs->trans("SellingPrice");
	if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
	{
		$titlesellprice=$form->textwithpicto($langs->trans("SellingPrice"), $langs->trans("DefaultPriceRealPriceMayDependOnCustomer"));
	}
}

// Definition of fields for lists
$arrayfields=array(
    'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    //'pfp.ref_fourn'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
    'p.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	'p.barcode'=>array('label'=>$langs->trans("Gencod"), 'checked'=>($contextpage != 'servicelist'), 'enabled'=>(! empty($conf->barcode->enabled))),
    'p.duration'=>array('label'=>$langs->trans("Duration"), 'checked'=>($contextpage != 'productlist'), 'enabled'=>(! empty($conf->service->enabled))),
	'p.price'=>array('label'=>$langs->trans("SellingPrice"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES)),
    'p.minbuyprice'=>array('label'=>$langs->trans("BuyingPriceMinShort"), 'checked'=>1, 'enabled'=>(! empty($user->rights->fournisseur->lire))),
	'p.seuil_stock_alerte'=>array('label'=>$langs->trans("StockLimit"), 'checked'=>0, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
    'p.desiredstock'=>array('label'=>$langs->trans("DesiredStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
    'p.stock'=>array('label'=>$langs->trans("PhysicalStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
    'stock_virtual'=>array('label'=>$langs->trans("VirtualStock"), 'checked'=>1, 'enabled'=>(! empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service' && $virtualdiffersfromphysical)),
    'p.tobatch'=>array('label'=>$langs->trans("ManageLotSerial"), 'checked'=>0, 'enabled'=>(! empty($conf->productbatch->enabled))),
	'p.accountancy_code_sell'=>array('label'=>$langs->trans("ProductAccountancySellCode"), 'checked'=>0),
	'p.accountancy_code_buy'=>array('label'=>$langs->trans("ProductAccountancyBuyCode"), 'checked'=>0),
	'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'p.tosell'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Sell").')', 'checked'=>1, 'position'=>1000),
    'p.tobuy'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Buy").')', 'checked'=>1, 'position'=>1000)
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val) 
   {
       $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key]);
   }
}



/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {
    	$sall="";
    	$sref="";
    	$snom="";
    	$sbarcode="";
    	$search_categ=0;
    	$tosell="";
    	$tobuy="";
    	$search_tobatch='';
    	$search_accountancy_code_sell='';
    	$search_accountancy_code_buy='';
    	$search_array_options=array();
    }
    
    // Mass actions
    $objectclass='Product';
    if ((string) $type == '1') { $objectlabel='Services'; }
    if ((string) $type == '0') { $objectlabel='Products'; }
    
    $permtoread = $user->rights->produit->lire;
    $permtodelete = $user->rights->produit->supprimer;
    $uploaddir = $conf->product->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';    
}


/*
 * View
 */

$htmlother=new FormOther($db);

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	$objcanvas->assign_values($action);       // This must contains code to load data (must call LoadListDatas($limit, $offset, $sortfield, $sortorder))
    $objcanvas->display_canvas($action);  	  // This is code to show template
}
else
{
	$title=$langs->trans("ProductsAndServices");

	if (isset($type))
	{
		if ($type==1)
		{
			$texte = $langs->trans("Services");
		}
		else
		{
			$texte = $langs->trans("Products");
		}
	}
	else
	{
		$texte = $langs->trans("ProductsAndServices");
	}

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
    $sql.= ' p.fk_product_type, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
    $sql.= ' p.tobatch, p.accountancy_code_sell, p.accountancy_code_buy,';
    $sql.= ' p.datec, p.tms,';
    //$sql.= ' pfp.ref_fourn as ref_supplier, ';
    $sql.= ' MIN(pfp.unitprice) as minsellprice';
	if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($type === 0)) {
		$sql .= ', pac.rowid prod_comb_id';
	}
	// Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object)";
    if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
   	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
	// multilang
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang() ."'";
	if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($type === 0)) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = p.rowid";
	}

	$sql.= ' WHERE p.entity IN ('.getEntity('product').')';
	if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
    // if the type is not 1, we show all products (type = 0,2,3)
    if (dol_strlen($type))
    {
    	if ($type == 1) $sql.= " AND p.fk_product_type = '1'";
    	else $sql.= " AND p.fk_product_type <> '1'";
    }
	if ($sref)     $sql .= natural_search('p.ref', $sref);
	if ($snom)     $sql .= natural_search('p.label', $snom);
	if ($sbarcode) $sql .= natural_search('p.barcode', $sbarcode);
    if (isset($tosell) && dol_strlen($tosell) > 0  && $tosell!=-1) $sql.= " AND p.tosell = ".$db->escape($tosell);
    if (isset($tobuy) && dol_strlen($tobuy) > 0  && $tobuy!=-1)   $sql.= " AND p.tobuy = ".$db->escape($tobuy);
    if (dol_strlen($canvas) > 0)                    $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
    if ($catid > 0)    $sql.= " AND cp.fk_categorie = ".$catid;
    if ($catid == -2)  $sql.= " AND cp.fk_categorie IS NULL";
    if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$db->escape($search_categ);
    if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
    if ($fourn_id > 0) $sql.= " AND pfp.fk_soc = ".$fourn_id;
    if ($search_tobatch != '' && $search_tobatch >= 0)   $sql.= " AND p.tobatch = ".$db->escape($search_tobatch);
    if ($search_accountancy_code_sell)   $sql.= natural_search('p.accountancy_code_sell', $search_accountancy_code_sell);
    if ($search_accountancy_code_sell)   $sql.= natural_search('p.accountancy_code_buy', $search_accountancy_code_buy);
    // Add where from extra fields

	if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($type === 0)) {
		$sql .= " AND pac.rowid IS NULL";
	}

	// Add where from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    $typ=$extrafields->attribute_type[$tmpkey];
	    $mode=0;
	    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
	    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
	    {
	        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
	    }
	}
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,";
    $sql.= " p.fk_product_type, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,";
    $sql.= ' p.datec, p.tms, p.entity, p.tobatch, p.accountancy_code_sell, p.accountancy_code_buy';
	if (!empty($conf->variants->enabled) && $search_hidechildproducts && ($type === 0)) {
		$sql .= ', pac.rowid';
	}
	// Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;

	// TODO put these functions into product.lib.php if this go from demo to core
	/**
	 * Function return formated sell price
	 *
	 * @param   int 	$fk_object rowid of product
	 * @return string
	 */
	function list_get_product_sellprice($fk_object) {
		global $langs,$conf, $user;
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		if($object === false) return '';
		
		if ($object->status)
		{
			if ($object->price_base_type == 'TTC') return price($object->price_ttc).' '.$langs->trans("TTC");
			else return price($object->price).' '.$langs->trans("HT");
		}
		return '';
	}

	/**
	 * Function return formated product status sell or buy
	 *
	 * @param   int 	$fk_object 	rowid of product
	 * @param	string	$field		concerned field status|status_buy
	 * @param	int		$type		for libstatus
	 * @return string	
	 */
	function list_get_product_status($fk_object, $field, $type) {
		global $conf, $user;
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		
		if($object === false) return '';
		
		if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
			return ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
			return $object->LibStatut($object->{$field},5,$type);
		}
		
	}
	
	/**
	 * Function return formated ref
	 *
	 * @param   int $fk_object rowid of product
	 * @return string
	 */
	function list_get_product_ref($fk_object) {
		global $conf, $user;
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		
		if($object === false) return '';
		
		return $object->getNomUrl(1,'',24);
	}
	
	/**
	 * Function return formated extrafield
	 *
	 * @param   int 	$fk_object 	rowid of product
	 * @param	string	$key		extrafield to output
	 * @return string
	 */
	function list_get_product_extrafield($fk_object, $key) {
		global $extrafields;
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		if($object === false) return '';
		
		return $extrafields->showOutputField($key, $object->array_options['options_'.$key], '', 1);
	}

	/**
	 * Function return formated virtual stock
	 *
	 * @param   int $fk_object 	rowid of product
	 * @return string
	 */
	function list_get_product_virtual_stock($fk_object) {
		global $langs;
	
		$object = Listview::getCachedOjbect('Product', $fk_object);
		if($object === false) return '';
		
		$object->load_stock('nobatch');
		
		$out = '';
		if ($object->type != 1)
		{
			if ($object->seuil_stock_alerte != '' && $object->stock_theorique < (float) $object->seuil_stock_alerte) $out.= img_warning($langs->trans("StockTooLow")).' ';
			$out.= (double) $object->stock_theorique;
		}

		return $out;
	}
	
	/**
	 * Function return formated stock
	 *
	 * @param   int $fk_object rowid of product
	 * @return string
	 */
	function list_get_product_stock($fk_object) {
		global $langs;
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		if($object === false) return '';
		
		$out = '';
		if ($object->type != 1)
		{
			if ($object->seuil_stock_alerte != '' && $object->stock_reel< (float) $object->seuil_stock_alerte) $out.= img_warning($langs->trans("StockTooLow")).' ';
			$out.= (double) $object->stock_reel;
		}
		
		return $out;
	}
	/**
	 * Function return formated min buy price
	 *
	 * @param   int $fk_object rowid of product
	 * @return string
	 */
	function list_get_product_minbuyprice($fk_object) {
		global $conf, $user, $langs,$db,$form;
		
		$out = '';
		
		$object = Listview::getCachedOjbect('Product', $fk_object);
		if($object === false || empty($object->status_buy) ) return '';
		
		$product_fourn =new ProductFournisseur($db);
		if ($product_fourn->find_min_price_product_fournisseur($fk_object) > 0)
		{
			if ($product_fourn->product_fourn_price_id > 0)
			{
				if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
				{
					$htmltext=$product_fourn->display_price_product_fournisseur(1, 1, 0, 1);
					$out.= $form->textwithpicto(price($product_fourn->fourn_unitprice).' '.$langs->trans("HT"),$htmltext);
				}
				else $out.= price($product_fourn->fourn_unitprice).' '.$langs->trans("HT");
			}
		}
		return $out;
	}
	
	// array of customized field function
	$arrayeval = array(
			'tobuy'=>'list_get_product_status(@rowid@, "status_buy",1)'
			,'tosell'=>'list_get_product_status(@rowid@, "status",0)'
			,'ref'=>'list_get_product_ref(@rowid@)'
			,'label'=>'dol_trunc("@val@",40)'
			,'price'=>'list_get_product_sellprice(@rowid@)'
			,'stock_virtual'=>'list_get_product_virtual_stock(@rowid@)'
			,'stock'=>'list_get_product_stock(@rowid@)'
			,'minbuyprice'=>'list_get_product_minbuyprice(@rowid@)'
	);
	
	// defined list align for field
	$arrayalign = array(
			'price'=>'right'
			,'tobuy'=>'right'
			,'tosell'=>'right'
			,'desiredstock'=>'right'
			,'stock'=>'right'
			,'stock_virtual'=>'right'
			,'minbuyprice'=>'right'
			,'datec'=>'center'
			,'tms'=>'center'
	);
	
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListMoreFields',$parameters);    // Note that $action and $object may have been modified by hook
	if($reshook) {
		$arrayfields = $hookmanager->resArray;
	}
	
	// init title, hidden field (allowed into selected fields), and position
	$arrayhide = $arraytitle = $arrayposition = array();
    foreach($arrayfields as $k=>$data) {
    	if(!isset($data['enabled']) || $data['enabled']) {
    		list($t,$f) = explode('.',$k);
    		if(empty($f))$f = $k;
    		$arraytitle[$f]=$data['label'];
    		if(empty($data['checked'])) $arrayhide[] = $f;
    		$arrayposition[$f] = empty($data['position']) ? 0 : $data['position'];
    	}
    }
    
    // Extra fields
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
    {
    	foreach($extrafields->attribute_label as $key => $val)
    	{
    		$arrayalign[$key]=$extrafields->getAlignFlag($key);
   			$arrayeval[$key] = 'list_get_product_extrafield(@rowid@, "'.$key.'")';
    	}
    }
    // List of mass actions available
    $arrayofmassactions =  array(
    		//'presend'=>$langs->trans("SendByMail"),
    		//'builddoc'=>$langs->trans("PDFMerge"),
    );
    if ($user->rights->produit->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
    if ($massaction == 'presend' || $massaction == 'createbills') $arrayofmassactions=array();
    
    // Filter on categories
    $moreforfilter='';
    if (! empty($conf->categorie->enabled))
    {
    	$moreforfilter.='<div class="divsearchfield">';
    	$moreforfilter.=$langs->trans('Categories'). ': ';
    	$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_PRODUCT,$search_categ,'search_categ',1);
    	$moreforfilter.='</div>';
    }
    
    //Show/hide child products. Hidden by default
    if (!empty($conf->variants->enabled) && $type === 0) {
    	$moreforfilter.='<div class="divsearchfield">';
    	$moreforfilter.= '<input type="checkbox" id="search_hidechildproducts" name="search_hidechildproducts" value="on"'.($search_hidechildproducts ? 'checked="checked"' : '').'>';
    	$moreforfilter.= ' <label for="search_hidechildproducts">'.$langs->trans('HideChildProducts').'</label>';
    	$moreforfilter.='</div>';
    }
    
    if ($moreforfilter)
    {
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	
    	if(!empty($hookmanager->resPrint)) {
    		$moreforfilter.=$hookmanager->resPrint;
    	}
    				
    }

    $param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
    if ($search_categ > 0) $param.="&amp;search_categ=".urlencode($search_categ);
    if ($sref) $param="&amp;sref=".urlencode($sref);
    if ($search_ref_supplier) $param="&amp;search_ref_supplier=".urlencode($search_ref_supplier);
    if ($sbarcode) $param.=($sbarcode?"&amp;sbarcode=".urlencode($sbarcode):"");
    if ($snom) $param.="&amp;snom=".urlencode($snom);
    if ($sall) $param.="&amp;sall=".urlencode($sall);
    if ($tosell != '') $param.="&amp;tosell=".urlencode($tosell);
    if ($tobuy != '') $param.="&amp;tobuy=".urlencode($tobuy);
    if ($fourn_id > 0) $param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
    if ($seach_categ) $param.=($search_categ?"&amp;search_categ=".urlencode($search_categ):"");
    if ($type != '') $param.='&amp;type='.urlencode($type);
    if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);
    if ($search_tobatch) $param="&amp;search_ref_supplier=".urlencode($search_ref_supplier);
    if ($search_accountancy_code_sell) $param="&amp;search_accountancy_code_sell=".urlencode($search_accountancy_code_sell);
    if ($search_accountancy_code_buy) $param="&amp;search_accountancy_code_buy=".urlencode($search_accountancy_code_buy);
    // Add $param from extra fields
    foreach ($search_array_options as $key => $val)
    {
    	$crit=$val;
    	$tmpkey=preg_replace('/search_options_/','',$key);
    	if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    }
    
    //var_dump($arraytitle,$arrayhide);
    $list=new Listview($db, 'product');
    $listHTML = $list->render($sql,array(
    		'list'=>array(
    				'title'=>$texte
    				,'image'=>'title_products.png'
    				,'massactions'=>$arrayofmassactions
    				,'param_url'=>$param
    				,'messageNothing'=>''
    		)
    		,'limit'=>array(
    				'nbLine'=>$limit
    		)
    		,'sortfield'=>$sortfield
    		,'sortorder'=>$sortorder
    		,'title'=>$arraytitle // column definition title (only defined where abble to show)
    		,'position'=>array(
    				'text-align'=>$arrayalign
    				,'rank'=>$arrayposition
    		)
    		,'allow-fields-select'=>1 // allow to select hidden fields
    		,'head_search'=>$moreforfilter //custom search on head
    		,'no-auto-sql-search'=>1 //disabled auto completion sql for search and pager url, use dolibarr style for migration of product list
			,'translate'=>array()    		
    		,'search'=>array(
    				'ref'=>array('search_type'=>true, 'table'=>'p', 'fieldname'=>'sref')
    				,'label'=>array('search_type'=>true, 'table'=>'p', 'fieldname'=>'snom')
    				,'tosell'=>array('search_type'=> array('0'=>$langs->trans('ProductStatusNotOnSellShort'),'1'=>$langs->trans('ProductStatusOnSellShort')), 'fieldname'=>'tosell')
    				,'tobuy'=>array('search_type'=> array('0'=>$langs->trans('ProductStatusNotOnBuyShort'),'1'=>$langs->trans('ProductStatusOnBuyShort')), 'fieldname'=>'tobuy')
    				,'barcode'=>array('search_type'=>true, 'table'=>'p', 'fieldname'=>'sbarcode')
    				,'accountancy_code_sell'=>array('search_type'=>true, 'table'=>'p', 'fieldname'=>'search_accountancy_code_sell')
    				,'accountancy_code_buy'=>array('search_type'=>true, 'table'=>'p', 'fieldname'=>'search_accountancy_code_buy')
    		)
    		,'type'=>array(
    				'datec'=>'datetime'
    				,'tms'=>'datetime'
    		)
    		,'hide'=>$arrayhide
    		,'eval'=>$arrayeval
    ));
    
    $num = $list->totalRow;
    
    	$arrayofselected=is_array($toselect)?$toselect:array();
    	
    	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
    	{
    	    $id = $list->TField[0]->rowid;
    	    header("Location: ".DOL_URL_ROOT.'/product/card.php?id='.$id);
    	    exit;
    	}

    	$helpurl='';
    	if (isset($type))
    	{
    		if ($type == 0)
    		{
    			$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
    		}
    		else if ($type == 1)
    		{
    			$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
    		}
    	}

    	llxHeader('',$title,$helpurl,'');

    	// Displays product removal confirmation
    	if (GETPOST('delprod'))	{
		    setEventMessages($langs->trans("ProductDeleted", GETPOST('delprod')), null, 'mesgs');
	    }

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="type" value="'.$type.'">';

		echo $listHTML;
    
    	print '</form>';
    
}


llxFooter();
$db->close();
