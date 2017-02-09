<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013  Marcos García           <marcosgdf@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("suppliers");
$langs->load("companies");
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action');
$sref=GETPOST("sref");
$sbarcode=GETPOST("sbarcode");
$snom=GETPOST("snom");
$sall=GETPOST("sall");
$type=GETPOST("type","int");
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

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'productservicelist';
if ((string) $type == '1') { $contextpage='servicelist'; if ($search_type=='') $search_type='1'; }
if ((string) $type == '0') { $contextpage='productlist'; if ($search_type=='') $search_type='0'; }

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
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
	'p.sellprice'=>array('label'=>$langs->trans("SellingPrice"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES)),
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
    'p.tobuy'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Purchases").')', 'checked'=>1, 'position'=>1000)
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
    $sql.= ' p.datec as date_creation, p.tms as date_update,';
    //$sql.= ' pfp.ref_fourn as ref_supplier, ';
    $sql.= ' MIN(pfp.unitprice) as minsellprice';
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
	$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
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
	// Add fields from extrafields
    foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    //if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
    $sql.= $db->order($sortfield,$sortorder);
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
	}
    $sql.= $db->plimit($limit + 1, $offset);

    $resql = $db->query($sql);
    if ($resql)
    {
    	$num = $db->num_rows($resql);

    	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
    	{
    	    $obj = $db->fetch_object($resql);
    	    $id = $obj->rowid;
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
		
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="type" value="'.$type.'">';

	    print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_products.png', 0, '', '', $limit);

    	if (! empty($catid))
    	{
    		print "<div id='ways'>";
    		$c = new Categorie($db);
    		$ways = $c->print_all_ways(' &gt; ','product/list.php');
    		print " &gt; ".$ways[0]."<br>\n";
    		print "</div><br>";
    	}

    	if (! empty($canvas) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$canvas.'/actions_card_'.$canvas.'.class.php'))
    	{
    		$fieldlist = $object->field_list;
    		$datas = $object->list_datas;
    		$picto='title.png';
    		$title_picto = img_picto('',$picto);
    		$title_text = $title;

    		// Default templates directory
    		$template_dir = DOL_DOCUMENT_ROOT . '/product/canvas/'.$canvas.'/tpl/';
    		// Check if a custom template is present
    		if (file_exists(DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/list.tpl.php'))
    		{
    			$template_dir = DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$canvas.'/';
    		}

    		include $template_dir.'list.tpl.php';	// Include native PHP templates
    	}
    	else
    	{
    	    if ($sall)
            {
                foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
                print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
            }
            
    		// Filter on categories
    	 	$moreforfilter='';
    		if (! empty($conf->categorie->enabled))
    		{
                $moreforfilter.='<div class="divsearchfield">';
    		    $moreforfilter.=$langs->trans('Categories'). ': ';
    			$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_PRODUCT,$search_categ,'search_categ',1);
    		 	$moreforfilter.='</div>';
    		}
    	 	if ($moreforfilter)
    		{
        		print '<div class="liste_titre liste_titre_bydiv centpercent">';
    		    print $moreforfilter;
            	$parameters=array();
            	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        	    print $hookmanager->resPrint;
    		    print '</div>';
    		}

			$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
            $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
    		print '<tr class="liste_titre">';
    		if (! empty($arrayfields['p.ref']['checked']))  print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
    		if (! empty($arrayfields['pfp.ref_fourn']['checked']))  print_liste_field_titre($arrayfields['pfp.ref_fourn']['label'], $_SERVER["PHP_SELF"],"pfp.ref_fourn","",$param,"",$sortfield,$sortorder);
    		if (! empty($arrayfields['p.label']['checked']))  print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"],"p.label","",$param,"",$sortfield,$sortorder);
    		if (! empty($arrayfields['p.barcode']['checked']))  print_liste_field_titre($arrayfields['p.barcode']['label'], $_SERVER["PHP_SELF"],"p.barcode","",$param,"",$sortfield,$sortorder);
    		if (! empty($arrayfields['p.duration']['checked']))  print_liste_field_titre($arrayfields['p.duration']['label'], $_SERVER["PHP_SELF"],"p.duration","",$param,'align="center"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.sellprice']['checked']))  print_liste_field_titre($arrayfields['p.sellprice']['label'], $_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.minbuyprice']['checked']))  print_liste_field_titre($arrayfields['p.minbuyprice']['label'], $_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.seuil_stock_alerte']['checked']))  print_liste_field_titre($arrayfields['p.seuil_stock_alerte']['label'], $_SERVER["PHP_SELF"],"p.seuil_stock_alerte","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.desiredstock']['checked']))  print_liste_field_titre($arrayfields['p.desiredstock']['label'], $_SERVER["PHP_SELF"],"p.desiredstock","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.stock']['checked']))  print_liste_field_titre($arrayfields['p.stock']['label'], $_SERVER["PHP_SELF"],"p.stock","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['stock_virtual']['checked']))  print_liste_field_titre($arrayfields['stock_virtual']['label'], $_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.tobatch']['checked']))  print_liste_field_titre($arrayfields['p.tobatch']['label'], $_SERVER["PHP_SELF"],"p.tobatch","",$param,'align="center"',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.accountancy_code_sell']['checked']))  print_liste_field_titre($arrayfields['p.accountancy_code_sell']['label'], $_SERVER["PHP_SELF"],"p.accountancy_code_sell","",$param,'',$sortfield,$sortorder);
    		if (! empty($arrayfields['p.accountancy_code_buy']['checked']))  print_liste_field_titre($arrayfields['p.accountancy_code_buy']['label'], $_SERVER["PHP_SELF"],"p.accountancy_code_buy","",$param,'',$sortfield,$sortorder);
    		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
			{
			   foreach($extrafields->attribute_label as $key => $val) 
			   {
		           if (! empty($arrayfields["ef.".$key]['checked'])) 
		           {
						$align=$extrafields->getAlignFlag($key);
						print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
		           }
			   }
			}
    		// Hook fields
			$parameters=array('arrayfields'=>$arrayfields);
		    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
		    print $hookmanager->resPrint;
			if (! empty($arrayfields['p.datec']['checked']))  print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
			if (! empty($arrayfields['p.tms']['checked']))    print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
			if (! empty($arrayfields['p.tosell']['checked'])) print_liste_field_titre($langs->trans("Status").' ('.$langs->trans("Sell").')',$_SERVER["PHP_SELF"],"p.tosell","",$param,'align="right"',$sortfield,$sortorder);
			if (! empty($arrayfields['p.tobuy']['checked']))  print_liste_field_titre($langs->trans("Status").' ('.$langs->trans("Buy").')',$_SERVER["PHP_SELF"],"p.tobuy","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
			print "</tr>\n";

    		// Lines with input filters
    		print '<tr class="liste_titre">';
    		if (! empty($arrayfields['p.ref']['checked']))
    		{
    			print '<td class="liste_titre" align="left">';
    			print '<input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'">';
    			print '</td>';
    		}
    	    if (! empty($arrayfields['pfp.ref_fourn']['checked']))
    		{
    			print '<td class="liste_titre" align="left">';
    			print '<input class="flat" type="text" name="search_ref_supplier" size="8" value="'.dol_escape_htmltag($search_ref_supplier).'">';
    			print '</td>';
    		}
    		if (! empty($arrayfields['p.label']['checked']))
    		{
    			print '<td class="liste_titre" align="left">';
		   		print '<input class="flat" type="text" name="snom" size="12" value="'.dol_escape_htmltag($snom).'">';
    			print '</td>';
    		}
    		// Barcode
    		if (! empty($arrayfields['p.barcode']['checked']))
    		{
    			print '<td class="liste_titre">';
    			print '<input class="flat" type="text" name="sbarcode" size="6" value="'.dol_escape_htmltag($sbarcode).'">';
    			print '</td>';
    		}
    		// Duration
			if (! empty($arrayfields['p.duration']['checked']))
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}
    		// Sell price
			if (! empty($arrayfields['p.sellprice']['checked']))
            {
        		print '<td class="liste_titre" align="right">';
        		print '</td>';
            }
    		// Minimum buying Price
			if (! empty($arrayfields['p.minbuyprice']['checked']))
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}
    	    // Limit for alert
			if (! empty($arrayfields['p.seuil_stock_alerte']['checked']))
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}
    		// Desired stock
			if (! empty($arrayfields['p.desiredstock']['checked']))
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}
    		// Stock
			if (! empty($arrayfields['p.stock']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
    		// Stock
			if (! empty($arrayfields['stock_virtual']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
			// To batch
			if (! empty($arrayfields['p.tobatch']['checked'])) print '<td class="liste_titre center">'.$form->selectyesno($search_tobatch, '', '', '', 1).'</td>';
			// Accountancy code sell
    		if (! empty($arrayfields['p.accountancy_code_sell']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" name="search_accountancy_code_sell" size="6" value="'.dol_escape_htmltag($search_accountancy_code_sell).'"></td>';
    	    // Accountancy code sell
    		if (! empty($arrayfields['p.accountancy_code_buy']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" name="search_accountancy_code_buy" size="6" value="'.dol_escape_htmltag($search_accountancy_code_buy).'"></td>';
    		// Extra fields
			if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
			{
			   foreach($extrafields->attribute_label as $key => $val) 
			   {
					if (! empty($arrayfields["ef.".$key]['checked'])) print '<td class="liste_titre"></td>';
			   }
			}
    		// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields);
		    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
		    print $hookmanager->resPrint;
		    // Date creation
		    if (! empty($arrayfields['p.datec']['checked']))
		    {
		        print '<td class="liste_titre">';
		        print '</td>';
		    }
		    // Date modification
		    if (! empty($arrayfields['p.tms']['checked']))
		    {
		        print '<td class="liste_titre">';
		        print '</td>';
		    }
    		if (! empty($arrayfields['p.tosell']['checked']))
    		{
	    		print '<td class="liste_titre" align="right">';
	            print $form->selectarray('tosell', array('0'=>$langs->trans('ProductStatusNotOnSellShort'),'1'=>$langs->trans('ProductStatusOnSellShort')),$tosell,1);
	            print '</td >';
    		}
			if (! empty($arrayfields['p.tobuy']['checked']))
    		{
	            print '<td class="liste_titre" align="right">';
	            print $form->selectarray('tobuy', array('0'=>$langs->trans('ProductStatusNotOnBuyShort'),'1'=>$langs->trans('ProductStatusOnBuyShort')),$tobuy,1);
	            print '</td>';
    		}
            print '<td class="liste_titre" align="right">';
            $searchpitco=$form->showFilterAndCheckAddButtons(0);
            print $searchpitco;
            print '</td>';

    		print '</tr>';


    		$product_static=new Product($db);
    		$product_fourn =new ProductFournisseur($db);

    		$var=true;
    	    $i = 0;
    		while ($i < min($num,$limit))
    		{
    			$objp = $db->fetch_object($resql);

    			// Multilangs
    			if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
    			{
    				$sql = "SELECT label";
    				$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
    				$sql.= " WHERE fk_product=".$objp->rowid;
    				$sql.= " AND lang='". $langs->getDefaultLang() ."'";
    				$sql.= " LIMIT 1";

    				$result = $db->query($sql);
    				if ($result)
    				{
    					$objtp = $db->fetch_object($result);
    					if (! empty($objtp->label)) $objp->label = $objtp->label;
    				}
    			}

    			$product_static->id = $objp->rowid;
    			$product_static->ref = $objp->ref;
    			$product_static->ref_fourn = $objp->ref_supplier;
                $product_static->label = $objp->label;
    			$product_static->type = $objp->fk_product_type;
    			$product_static->status_buy = $objp->tobuy;
                $product_static->status     = $objp->tosell;
				$product_static->entity = $objp->entity;
				
				if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1)	// To optimize call of load_stock
				{
				    if ($objp->fk_product_type != 1)    // Not a service
				    {
				        $product_static->load_stock('nobatch');             // Load stock_reel + stock_warehouse. This also call load_virtual_stock()
				    }
				}
				 
				
    			$var=!$var;
    			print '<tr '.$bc[$var].'>';

    			// Ref
			    if (! empty($arrayfields['p.ref']['checked']))
			    {
	    			print '<td class="nowrap">';
	    			print $product_static->getNomUrl(1,'',24);
	    			print "</td>\n";
			    }
       			// Ref supplier
			    if (! empty($arrayfields['pfp.ref_fourn']['checked']))
			    {
	    			print '<td class="nowrap">';
	    			print $product_static->getNomUrl(1,'',24);
	    			print "</td>\n";
			    }
    			// Label
			    if (! empty($arrayfields['p.label']['checked']))
			    {
			    	print '<td>'.dol_trunc($objp->label,40).'</td>';
			    }
			    
    			// Barcode
			    if (! empty($arrayfields['p.barcode']['checked']))
			    {
    				print '<td>'.$objp->barcode.'</td>';
    			}

    			// Duration
 			    if (! empty($arrayfields['p.duration']['checked']))
    			{
    				print '<td align="center">';
    				if (preg_match('/([0-9]+)[a-z]/i',$objp->duration))
    				{
	    				if (preg_match('/([0-9]+)y/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
	    				elseif (preg_match('/([0-9]+)m/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
	    				elseif (preg_match('/([0-9]+)w/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationWeek");
	    				elseif (preg_match('/([0-9]+)d/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
	    				//elseif (preg_match('/([0-9]+)h/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationHour");
	    				else print $objp->duration;
    				}
    				print '</td>';
    			}

    			// Sell price
 			    if (! empty($arrayfields['p.sellprice']['checked']))
    			{
    			    print '<td align="right">';
    			    if ($objp->tosell)
    			    {
        				if ($objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
        				else print price($objp->price).' '.$langs->trans("HT");
    			    }
        			print '</td>';
    			}

    			// Better buy price
 			    if (! empty($arrayfields['p.minbuyprice']['checked']))
    			{
        			print  '<td align="right">';
    			    if ($objp->tobuy && $objp->minsellprice != '')
        			{
    					//print price($objp->minsellprice).' '.$langs->trans("HT");
    					if ($product_fourn->find_min_price_product_fournisseur($objp->rowid) > 0)
    					{
    						if ($product_fourn->product_fourn_price_id > 0)
    						{
    							if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
    							{
    								$htmltext=$product_fourn->display_price_product_fournisseur(1, 1, 0, 1);
    								print $form->textwithpicto(price($product_fourn->fourn_unitprice).' '.$langs->trans("HT"),$htmltext);
    							}
    							else print price($product_fourn->fourn_unitprice).' '.$langs->trans("HT");
    						}
    					}
        			}
        			print '</td>';
    			}

    		    // Limit alert
		        if (! empty($arrayfields['p.seuil_stock_alerte']['checked']))
        		{
                    print '<td align="right">';
    				if ($objp->fk_product_type != 1)
    				{
                        print $objp->seuil_stock_alerte;
    				}
    				print '</td>';
        		}
    			// Desired stock
		        if (! empty($arrayfields['p.desiredstock']['checked']))
        		{
                    print '<td align="right">';
    				if ($objp->fk_product_type != 1)
    				{
                        print $objp->desiredstock;
    				}
    				print '</td>';
        		}
				// Stock
		        if (! empty($arrayfields['p.stock']['checked']))
        		{
   					print '<td align="right">';
    				if ($objp->fk_product_type != 1)
    				{
   						if ($objp->seuil_stock_alerte != '' && $product_static->stock_reel < (float) $objp->seuil_stock_alerte) print img_warning($langs->trans("StockTooLow")).' ';
      					print $product_static->stock_reel;
    				}
    				print '</td>';
    			}
    			// Stock
		        if (! empty($arrayfields['stock_virtual']['checked']))
        		{
   					print '<td align="right">';
    				if ($objp->fk_product_type != 1)
    				{
   						if ($objp->seuil_stock_alerte != '' && $product_static->stock_theorique < (float) $objp->seuil_stock_alerte) print img_warning($langs->trans("StockTooLow")).' ';
      					print $product_static->stock_theorique;
    				}
    				print '</td>';
    			}
    			// Lot/Serial
		        if (! empty($arrayfields['p.tobatch']['checked']))
        		{
                    print '<td align="center">';
    				print yn($objp->tobatch);
    				print '</td>';
        		}        		
    			// Accountancy code sell
		        if (! empty($arrayfields['p.accountancy_code_sell']['checked'])) print '<td>'.$objp->accountancy_code_sell.'</td>';
    			// Accountancy code sell
		        if (! empty($arrayfields['p.accountancy_code_buy']['checked'])) print '<td>'.$objp->accountancy_code_buy.'</td>';
		        // Extra fields
				if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
				{
				   foreach($extrafields->attribute_label as $key => $val) 
				   {
						if (! empty($arrayfields["ef.".$key]['checked'])) 
						{
							print '<td';
							$align=$extrafields->getAlignFlag($key);
							if ($align) print ' align="'.$align.'"';
							print '>';
							$tmpkey='options_'.$key;
							print $extrafields->showOutputField($key, $objp->$tmpkey, '', 1);
							print '</td>';
						}
				   }
				}
		        // Fields from hook
			    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
				$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		        print $hookmanager->resPrint;
		        // Date creation
		        if (! empty($arrayfields['p.datec']['checked']))
		        {
		            print '<td align="center">';
		            print dol_print_date($objp->date_creation, 'dayhour');
		            print '</td>';
		        }
		        // Date modification
		        if (! empty($arrayfields['p.tms']['checked']))
		        {
		            print '<td align="center">';
		            print dol_print_date($objp->date_update, 'dayhour');
		            print '</td>';
		        }    			
    			
                // Status (to sell)
		        if (! empty($arrayfields['p.tosell']['checked']))
        		{
	                print '<td align="right" nowrap="nowrap">';
	                if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
	                    print ajax_object_onoff($product_static, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
	                } else {
	                    print $product_static->LibStatut($objp->tosell,5,0);
	                }
	                print '</td>';
        		}
                // Status (to buy)
		        if (! empty($arrayfields['p.tobuy']['checked']))
        		{
	        		print '<td align="right" nowrap="nowrap">';
	                if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
	                    print ajax_object_onoff($product_static, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
	                } else {
	                    print $product_static->LibStatut($objp->tobuy,5,1);
	                }
	                print '</td>';
        		}
        		// Action	
                print '<td>&nbsp;</td>';

                print "</tr>\n";
    			$i++;
    		}
    		
    		$db->free($resql);

    		print "</table>";
    		print "</div>";
    	}
    	print '</form>';
    }
    else
    {
    	dol_print_error($db);
    }
}


llxFooter();
$db->close();
