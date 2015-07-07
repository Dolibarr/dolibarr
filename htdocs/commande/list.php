<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent          <jmenent@2byte.es>
 * Copyright (C) 2013      Christophe Battarel    <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador        <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2015      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry      <jfefe@aternatik.fr>
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
 *	\file       htdocs/commande/list.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';

$langs->load('orders');
$langs->load('deliveries');
$langs->load('companies');

$orderyear=GETPOST("orderyear","int");
$ordermonth=GETPOST("ordermonth","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('search_ref','alpha')!=''?GETPOST('search_ref','alpha'):GETPOST('sref','alpha');
$search_ref_customer=GETPOST('search_ref_customer','alpha');
$search_company=GETPOST('search_company','alpha');
$sall=GETPOST('sall');
$socid=GETPOST('socid','int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_total_ht=GETPOST('search_total_ht','alpha');

// Security check
$id = (GETPOST('orderid')?GETPOST('orderid'):GETPOST('id','int'));
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $id,'');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.rowid';
if (! $sortorder) $sortorder='DESC';
$limit = $conf->liste_limit;

$viewstatut=GETPOST('viewstatut');

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_product_category='';
    $search_ref='';
    $search_ref_customer='';
    $search_company='';
    $search_total_ht='';
    $orderyear='';
    $ordermonth='';
    $deliverymonth='';
    $deliveryyear='';
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('orderlist'));

/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hook
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
llxHeader('',$langs->trans("Orders"),$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.nom as name, s.rowid as socid, s.client, s.code_client, c.rowid, c.ref, c.total_ht, c.tva as total_tva, c.total_ttc, c.ref_client,';
$sql.= ' c.date_valid, c.date_commande, c.note_private, c.date_livraison as date_delivery, c.fk_statut, c.facture as facturee';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND c.entity IN ('.getEntity('commande', 1).')';
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
if ($sall) $sql .= natural_search(array('c.ref', 'c.note_private'), $sall);
if ($viewstatut <> '')
{
	if ($viewstatut < 4 && $viewstatut > -3)
	{
		if ($viewstatut == 1 && empty($conf->expedition->enabled)) $sql.= ' AND c.fk_statut IN (1,2)';	// If module expedition disabled, we include order with status 'sending in process' into 'validated'
		else $sql.= ' AND c.fk_statut = '.$viewstatut; // brouillon, validee, en cours, annulee
		if ($viewstatut == 3)
		{
			$sql.= ' AND c.facture = 0'; // need to create invoice
		}
	}
	if ($viewstatut == 4)
	{
		$sql.= ' AND c.facture = 1'; // invoice created
	}
	if ($viewstatut == -2)	// To process
	{
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";    // If status is 2 and facture=1, it must be selected
	}
	if ($viewstatut == -3)	// To bill
	{
		//$sql.= ' AND c.fk_statut in (1,2,3)';
		//$sql.= ' AND c.facture = 0'; // invoice not created
		$sql .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'; // validated, in process or closed but not billed
	}
}
if ($ordermonth > 0)
{
    if ($orderyear > 0 && empty($day))
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,$ordermonth,false))."' AND '".$db->idate(dol_get_last_day($orderyear,$ordermonth,false))."'";
    else if ($orderyear > 0 && ! empty($day))
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $ordermonth, $day, $orderyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $ordermonth, $day, $orderyear))."'";
    else
    $sql.= " AND date_format(c.date_commande, '%m') = '".$ordermonth."'";
}
else if ($orderyear > 0)
{
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,1,false))."' AND '".$db->idate(dol_get_last_day($orderyear,12,false))."'";
}
if ($deliverymonth > 0)
{
    if ($deliveryyear > 0 && empty($day))
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,$deliverymonth,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,$deliverymonth,false))."'";
    else if ($deliveryyear > 0 && ! empty($day))
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $deliverymonth, $day, $deliveryyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $deliverymonth, $day, $deliveryyear))."'";
    else
    $sql.= " AND date_format(c.date_livraison, '%m') = '".$deliverymonth."'";
}
else if ($deliveryyear > 0)
{
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,1,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,12,false))."'";
}
if (!empty($search_company)) $sql .= natural_search('s.nom', $search_company);
if (!empty($search_ref_customer)) $sql.= natural_search('c.ref_client', $search_ref_customer);
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
if ($search_total_ht != '') $sql.= natural_search('c.total_ht', $search_total_ht, 1);
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1,$offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfOrders') . ' - '.$soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfOrders');
	}
	if (strval($viewstatut) == '0')
	$title.=' - '.$langs->trans('StatusOrderDraftShort');
	if ($viewstatut == 1)
	$title.=' - '.$langs->trans('StatusOrderValidatedShort');
	if ($viewstatut == 2)
	$title.=' - '.$langs->trans('StatusOrderSentShort');
	if ($viewstatut == 3)
	$title.=' - '.$langs->trans('StatusOrderToBillShort');
	if ($viewstatut == 4)
	$title.=' - '.$langs->trans('StatusOrderProcessedShort');
	if ($viewstatut == -1)
	$title.=' - '.$langs->trans('StatusOrderCanceledShort');
	if ($viewstatut == -2)
	$title.=' - '.$langs->trans('StatusOrderToProcessShort');
	if ($viewstatut == -3)
	$title.=' - '.$langs->trans('StatusOrderValidated').', '.(empty($conf->expedition->enabled)?'':$langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill');

	$param='&socid='.$socid.'&viewstatut='.$viewstatut;
	if ($ordermonth)      		$param.='&ordermonth='.$ordermonth;
	if ($orderyear)       		$param.='&orderyear='.$orderyear;
	if ($deliverymonth)   		$param.='&deliverymonth='.$deliverymonth;
	if ($deliveryyear)    		$param.='&deliveryyear='.$deliveryyear;
	if ($search_ref)      		$param.='&search_ref='.$search_ref;
	if ($search_company)  		$param.='&search_company='.$search_company;
	if ($search_ref_customer)	$param.='&search_ref_customer='.$search_ref_customer;
	if ($search_user > 0) 		$param.='&search_user='.$search_user;
	if ($search_sale > 0) 		$param.='&search_sale='.$search_sale;
	if ($search_total_ht != '') $param.='&search_total_ht='.$search_total_ht;

	$num = $db->num_rows($resql);
	print_barre_liste($title, $page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_commercial.png');
	$i = 0;

	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print '<table class="noborder" width="100%">';

	$moreforfilter='';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
	 	$moreforfilter.='</div>';
 	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
	    $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	 	$moreforfilter.='</div>';
	}
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && $user->rights->produit->lire)
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, '', 1);
		$moreforfilter.='</div>';
	}
	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre">';
		print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	print $hookmanager->resPrint;
    	print '</div>';
	}

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'c.ref','',$param,'width="25%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('RefCustomerOrder'),$_SERVER["PHP_SELF"],'c.ref_client','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('OrderDate'),$_SERVER["PHP_SELF"],'c.date_commande','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DeliveryDate'),$_SERVER["PHP_SELF"],'c.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],'c.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	$parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'c.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>';

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="6" name="search_ref_customer" value="'.$search_ref_customer.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_company" value="'.$search_company.'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="orderday" value="'.$orderday.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="ordermonth" value="'.$ordermonth.'">';
    $formother->select_year($orderyear?$orderyear:-1,'orderyear',1, 20, 5);
	print '</td><td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="deliveryday" value="'.$deliveryday.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="deliverymonth" value="'.$deliverymonth.'">';
    $formother->select_year($deliveryyear?$deliveryyear:-1,'deliveryyear',1, 20, 5);
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="6" name="search_total_ht" value="'.$search_total_ht.'">';
	print '</td>';
	print '<td align="right">';
	$liststatus=array('0'=>$langs->trans("StatusOrderDraftShort"), '1'=>$langs->trans("StatusOrderValidated"), '2'=>$langs->trans("StatusOrderSentShort"), '3'=>$langs->trans("StatusOrderToBill"), '4'=>$langs->trans("StatusOrderProcessed"), '-1'=>$langs->trans("StatusOrderCanceledShort"));
	print $form->selectarray('viewstatut', $liststatus, $viewstatut, 1);
	print '</td>';
	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print "</td></tr>\n";

	$var=true;
	$total=0;
	$subtotal=0;
    $productstat_cache=array();

    $generic_commande = new Commande($db);
    $generic_product = new Product($db);
    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td class="nowrap">';

        $generic_commande->id=$objp->rowid;
        $generic_commande->ref=$objp->ref;
        $generic_commande->ref_client = $objp->ref_client;
        $generic_commande->total_ht = $objp->total_ht;
        $generic_commande->total_tva = $objp->total_tva;
        $generic_commande->total_ttc = $objp->total_ttc;
        $generic_commande->lines=array();
        $generic_commande->getLinesArray();

        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
        print '<td class="nobordernopadding nowrap">';
        print $generic_commande->getNomUrl(1,($viewstatut != 2?0:$objp->fk_statut));
        print '</td>';

        // Shippable Icon
        if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && ! empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST)) {
            $notshippable=0;
            $text_info='';
            $nbprod=0;
            for ($lig=0; $lig<(count($generic_commande->lines)); $lig++) {
                if ($generic_commande->lines[$lig]->product_type==0) {
                    $nbprod++; // order contains real products
                    $generic_product->id = $generic_commande->lines[$lig]->fk_product;
                    if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product])) {
                        $generic_product->load_stock();
                        $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_reel;
                    } else {
                        $generic_product->stock_reel = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'];
                    }
                    // stock order and stock order_supplier
                    $stock_order=0;
                    $stock_order_supplier=0;
                    if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT))
                    {
                        if (! empty($conf->commande->enabled))
                        {
                            if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'])) {
                                $generic_product->load_stats_commande(0,'1,2');
                                $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
                            } else {
                                $generic_product->stats_commande['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'];
                            }
                            $stock_order=$generic_product->stats_commande['qty'];
                        }
                        if (! empty($conf->fournisseur->enabled))
                        {
                            if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'])) {
                                $generic_product->load_stats_commande_fournisseur(0,'3');
                                $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
                            } else {
                                $generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'];
                            }
                            $stock_order_supplier=$generic_product->stats_commande_fournisseur['qty'];
                        }
                    }
                    $text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
                    $text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
                    if ($generic_product->stock_reel<$generic_commande->lines[$lig]->qty) {
                        $notshippable++;
                        $text_info.='<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
                    } else {
                        $text_info.='<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
                    }
                    if ($stock_order_supplier>0) {
                        $text_info.= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier.'<br>';
                    } else {
                        $text_info.= '<br>';
                    }
                }
            }
            if ($notshippable==0) {
                $text_icon = img_picto('', 'object_sending');
                $text_info = $langs->trans('Shippable').'<br>'.$text_info;
            } else {
                $text_icon = img_picto('', 'error');
                $text_info = $langs->trans('NonShippable').'<br>'.$text_info;
            }
            if ($nbprod>0) {
                print '<td>';
                print $form->textwithtooltip('',$text_info,2,1,$text_icon,'',2);
                print '</td>';
            }
        }

        // warning late icon
		print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
		if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && max($db->jdate($objp->date_commande),$db->jdate($objp->date_delivery)) < ($now - $conf->commande->client->warning_delay))
			print img_picto($langs->trans("Late"),"warning");
		if(!empty($objp->note_private))
		{
			print ' <span class="note">';
			print '<a href="'.DOL_URL_ROOT.'/commande/note.php?id='.$objp->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
			print '</span>';
		}
		print '</td>';

		print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
		$filename=dol_sanitizeFileName($objp->ref);
		$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
		print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
		print '</td>';
		print '</tr></table>';

		print '</td>';

		// Ref customer
		print '<td>'.$objp->ref_client.'</td>';

		// Company
		$companystatic->id=$objp->socid;
        $companystatic->code_client = $objp->code_client;
		$companystatic->name=$objp->name;
		$companystatic->client=$objp->client;
		print '<td>';
		print $companystatic->getNomUrl(1,'customer');

		// If module invoices enabled and user with invoice creation permissions
		if (! empty($conf->facture->enabled) && ! empty($conf->global->ORDER_BILLING_ALL_CUSTOMER))
		{
			if ($user->rights->facture->creer)
			{
				if (($objp->fk_statut > 0 && $objp->fk_statut < 3) || ($objp->fk_statut == 3 && $objp->facturee == 0))
				{
					print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$companystatic->id.'">';
					print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
				}
			}
		}
		print '</td>';

		// Order date
		print '<td align="center">';
		print dol_print_date($db->jdate($objp->date_commande), 'day');
		print '</td>';

		// Delivery date
		print '<td align="center">';
		print dol_print_date($db->jdate($objp->date_delivery), 'day');
		print '</td>';

		// Amount HT
		print '<td align="right" class="nowrap">'.price($objp->total_ht).'</td>';

		// Statut
		print '<td align="right" class="nowrap">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facturee,5).'</td>';

		print '<td></td>';

		print '</tr>';

		$total+=$objp->total_ht;
		$subtotal+=$objp->total_ht;
		$i++;
	}

	if (! empty($conf->global->MAIN_SHOW_TOTAL_FOR_LIMITED_LIST))
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td class="nowrap" colspan="5">'.$langs->trans('TotalHT').'</td>';
		// Total HT
		print '<td align="right" class="nowrap">'.price($total).'</td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';
	}

	print '</table>';

	print '</form>'."\n";

	print '<br>'.img_help(1,'').' '.$langs->trans("ToBillSeveralOrderSelectCustomer", $langs->transnoentitiesnoconv("CreateInvoiceForThisCustomer")).'<br>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
