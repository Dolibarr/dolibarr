<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
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
$optioncss = GETPOST('optioncss','alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;


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


/*
 * Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$sref="";
	$sbarcode="";
	$snom="";
	$search_categ=0;
	$tosell="";
	$tobuy="";
}


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	$objcanvas->assign_values('list');       // This must contains code to load data (must call LoadListDatas($limit, $offset, $sortfield, $sortorder))
    $objcanvas->display_canvas('list');  	 // This is code to show template
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
    // Add what we are searching for
    if (! empty($sall)) $texte.= " - ".$sall;

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
    $sql.= ' p.fk_product_type, p.tms as datem,';
    $sql.= ' p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
    $sql.= ' MIN(pfp.unitprice) as minsellprice';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
    if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
   	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
	// multilang
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang() ."'";
	$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
	if ($sall)
	{
		// For natural search
		$params = array('p.ref', 'p.label', 'p.description', 'p.note');
		// multilang
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$params[] = 'pl.label';
			$params[] = 'pl.description';
			$params[] = 'pl.note';
		}
		if (! empty($conf->barcode->enabled)) {
			$params[] = 'p.barcode';
		}
		$sql .= natural_search($params, $sall);
	}
    // if the type is not 1, we show all products (type = 0,2,3)
    if (dol_strlen($type))
    {
    	if ($type == 1) $sql.= " AND p.fk_product_type = '1'";
    	else $sql.= " AND p.fk_product_type <> '1'";
    }
	if ($sref)     $sql .= natural_search('p.ref', $sref);
    if ($sbarcode) $sql .= natural_search('p.barcode', $sbarcode);
    if ($snom)
	{
		$params = array('p.label');
		// multilang
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$params[] = 'pl.label';
		}
		$sql .= natural_search($params, $snom);
	}
    if (isset($tosell) && dol_strlen($tosell) > 0  && $tosell!=-1) $sql.= " AND p.tosell = ".$db->escape($tosell);
    if (isset($tobuy) && dol_strlen($tobuy) > 0  && $tobuy!=-1)   $sql.= " AND p.tobuy = ".$db->escape($tobuy);
    if (dol_strlen($canvas) > 0)                    $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
    if ($catid > 0)    $sql.= " AND cp.fk_categorie = ".$catid;
    if ($catid == -2)  $sql.= " AND cp.fk_categorie IS NULL";
    if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$db->escape($search_categ);
    if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
    if ($fourn_id > 0) $sql.= " AND pfp.fk_soc = ".$fourn_id;
    $sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,";
    $sql.= " p.fk_product_type, p.tms,";
    $sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte";
    $sql .= ', p.desiredstock';
    //if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet

    $nbtotalofrecords = 0;
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
	}

    $sql.= $db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit + 1, $offset);

    $resql = $db->query($sql);
    if ($resql)
    {
    	$num = $db->num_rows($resql);

    	$i = 0;

    	if ($num == 1 && ($sall || $snom || $sref || $sbarcode) && $action != 'list')
    	{
    		$objp = $db->fetch_object($resql);
    		header("Location: card.php?id=".$objp->rowid);
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
		    setEventMessage($langs->trans("ProductDeleted", GETPOST('delprod')));
	    }

    	$param="&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"")."&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
    	$param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
    	$param.=($search_categ?"&amp;search_categ=".$search_categ:"");
    	$param.=isset($type)?"&amp;type=".$type:"";
		if ($optioncss != '') $param.='&optioncss='.$optioncss;

    	print_barre_liste($texte, $page, "list.php", $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords,'title_products.png');

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
    		print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
            if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    		print '<input type="hidden" name="action" value="list">';
    		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    		print '<input type="hidden" name="type" value="'.$type.'">';

    		// Filter on categories
    	 	$moreforfilter='';
    	 	$colspan=6;
    	 	if (! empty($conf->barcode->enabled)) $colspan++;
    	 	if (! empty($conf->service->enabled) && $type != 0) $colspan++;
    	 	if (empty($conf->global->PRODUIT_MULTIPRICES)) $colspan++;
    	 	if ($user->rights->fournisseur->lire) $colspan++;
    	 	if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1) $colspan+=2;

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

    		// Lignes des titres
    		print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';
    		print '<tr class="liste_titre">';
    		print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
    		print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label",$param,"","",$sortfield,$sortorder);
    		if (! empty($conf->barcode->enabled)) print_liste_field_titre($langs->trans("BarCode"), $_SERVER["PHP_SELF"], "p.barcode",$param,'','',$sortfield,$sortorder);
    		print_liste_field_titre($langs->trans("DateModification"), $_SERVER["PHP_SELF"], "p.tms",$param,"",'align="center"',$sortfield,$sortorder);
    		if (! empty($conf->service->enabled) && $type != 0) print_liste_field_titre($langs->trans("Duration"), $_SERVER["PHP_SELF"], "p.duration",$param,"",'align="center"',$sortfield,$sortorder);
    		if (empty($conf->global->PRODUIT_MULTIPRICES))
    		{
    			$titlefield=$langs->trans("SellingPrice");
    		   	if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
   				{
					$titlefield=$form->textwithpicto($langs->trans("SellingPrice"), $langs->trans("DefaultPriceRealPriceMayDependOnCustomer"));
    			}
    			print_liste_field_titre($titlefield, $_SERVER["PHP_SELF"], "p.price",$param,"",'align="right"',$sortfield,$sortorder);
    		}
    		if ($user->rights->fournisseur->lire) print_liste_field_titre($langs->trans("BuyingPriceMinShort"), '', '', '', '', 'align="right"');
    		if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1) print_liste_field_titre($langs->trans("DesiredStock"), '', '', '', '', 'align="right"');
    		if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1) print_liste_field_titre($langs->trans("PhysicalStock"), '', '', '', '', 'align="right"');
    		print_liste_field_titre($langs->trans("Sell"), $_SERVER["PHP_SELF"], "p.tosell",$param,"",'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("Buy"), $_SERVER["PHP_SELF"], "p.tobuy",$param,"",'align="center"',$sortfield,$sortorder);
            print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
			print "</tr>\n";

    		// Lignes des champs de filtre
    		print '<tr class="liste_titre">';
    		print '<td class="liste_titre" align="left">';
    		print '<input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'">';
    		print '</td>';
    		print '<td class="liste_titre" align="left">';
    		print '<input class="flat" type="text" name="snom" size="12" value="'.dol_escape_htmltag($snom).'">';
    		print '</td>';
    		// Barcode
    		if (! empty($conf->barcode->enabled))
    		{
    			print '<td class="liste_titre">';
    			print '<input class="flat" type="text" name="sbarcode" size="6" value="'.dol_escape_htmltag($sbarcode).'">';
    			print '</td>';
    		}
    		// Date modification
    		print '<td class="liste_titre">';
    		print '&nbsp;';
    		print '</td>';

    		// Duration
    		if (! empty($conf->service->enabled) && $type != 0)
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}

    		// Sell price
            if (empty($conf->global->PRODUIT_MULTIPRICES))
            {
        		print '<td class="liste_titre" align="right">';
        		print '</td>';
            }

    		// Minimum buying Price
    		if ($user->rights->fournisseur->lire) {
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}

    		// Stock
    		if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1)
    		{
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    			//desiredstock
    			print '<td class="liste_titre">';
    			print '&nbsp;';
    			print '</td>';
    		}

    		print '<td class="liste_titre" align="center">';
            print $form->selectarray('tosell', array('0'=>$langs->trans('ProductStatusNotOnSellShort'),'1'=>$langs->trans('ProductStatusOnSellShort')),$tosell,1);
            print '</td >';

            print '<td class="liste_titre" align="center">';
            print $form->selectarray('tobuy', array('0'=>$langs->trans('ProductStatusNotOnBuyShort'),'1'=>$langs->trans('ProductStatusOnBuyShort')),$tobuy,1);
            print '</td>';

    		print '<td class="liste_titre nowrap" align="right">';
    		print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    		print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    		print '</td>';

    		print '</tr>';


    		$product_static=new Product($db);
    		$product_fourn =new ProductFournisseur($db);

    		$var=true;
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

    			$var=!$var;
    			print '<tr '.$bc[$var].'>';

    			// Ref
    			print '<td class="nowrap">';
    			$product_static->id = $objp->rowid;
    			$product_static->ref = $objp->ref;
                $product_static->label = $objp->label;
    			$product_static->type = $objp->fk_product_type;
    			print $product_static->getNomUrl(1,'',24);
    			print "</td>\n";

    			// Label
    			print '<td>'.dol_trunc($objp->label,40).'</td>';

    			// Barcode
    			if (! empty($conf->barcode->enabled))
    			{
    				print '<td>'.$objp->barcode.'</td>';
    			}

    			// Modification Date
    			print '<td align="center">'.dol_print_date($db->jdate($objp->datem),'day')."</td>\n";

    			// Duration
    			if (! empty($conf->service->enabled) && $type != 0)
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
    			if (empty($conf->global->PRODUIT_MULTIPRICES))
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
    			if ($user->rights->fournisseur->lire)
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

    			// Show stock
    			if (! empty($conf->stock->enabled) && $user->rights->stock->lire && $type != 1)
    			{
    				if ($objp->fk_product_type != 1)
    				{
    					$product_static->id = $objp->rowid;
    					$product_static->load_stock();
                        print '<td align="right">';
                        print $objp->desiredstock;
                        print '</td>';
    					print '<td align="right">';
                        if ($product_static->stock_reel < $objp->seuil_stock_alerte) print img_warning($langs->trans("StockTooLow")).' ';
        				print $product_static->stock_reel;
    					print '</td>';
    				}
    				else
    				{
		    			print '<td>';
		    			print '&nbsp;';
		    			print '</td>';
		    			print '<td>';
		    			print '&nbsp;';
		    			print '</td>';
    				}
    			}

                $product_static->status_buy = $objp->tobuy;
                $product_static->status     = $objp->tosell;
                // Status (to sell)
                print '<td align="center" nowrap="nowrap">';
                if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                    print ajax_object_onoff($product_static, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
                } else {
                    print $product_static->LibStatut($objp->tosell,5,0);
                }
                print '</td>';

                // Status (to buy)
                print '<td align="center" nowrap="nowrap">';
                if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                    print ajax_object_onoff($product_static, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
                } else {
                    print $product_static->LibStatut($objp->tobuy,5,1);
                }
                print '</td>';

                print '<td>&nbsp;</td>';

                print "</tr>\n";
    			$i++;
    		}

    		$param="&sref=".$sref.($sbarcode?"&sbarcode=".$sbarcode:"")."&snom=".$snom."&sall=".$sall."&tosell=".$tosell."&tobuy=".$tobuy;
    		$param.=($fourn_id?"&fourn_id=".$fourn_id:"");
    		$param.=($search_categ?"&search_categ=".$search_categ:"");
    		$param.=isset($type)?"&type=".$type:"";
    		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', '', '', 'paginationatbottom');

    		$db->free($resql);

    		print "</table>";
    		print '</form>';
    	}
    }
    else
    {
    	dol_print_error($db);
    }
}


llxFooter();
$db->close();
