<?php
/*
 * Copyright (C) 2013   Cédric Salvador    <csalvador@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/replenish.php
 *  \ingroup    produit
 *  \brief      Page to list stocks to replenish
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');


$action=GETPOST('action','alpha');
$sref=GETPOST("sref");
$snom=GETPOST("snom");
$sall=GETPOST("sall");
$type=GETPOST("type","int");
$sbarcode=GETPOST("sbarcode");
$catid=GETPOST('catid','int');
$tobuy = GETPOST("tobuy");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (! $sortfield) $sortfield="stock_physique";
if (! $sortorder) $sortorder="ASC";
$limit = $conf->liste_limit;
$offset = $limit * $page ;

// Load sale and categ filters
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ");

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
//$object->getCanvas($id);
$canvas=GETPOST("canvas");
$objcanvas='';
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','list',$canvas);
}

if (! empty($_POST["button_removefilter_x"]))
{
    $sref="";
    $snom="";
    $sall="";
    $search_sale="";
    $search_categ="";
    $type="";
    $catid='';
}



/*
 * Actions
 */

//orders creation
if($action == 'order'){
    $linecount = GETPOST('linecount', 'int');
    if($linecount > 0){
        $suppliers = array();
        for($i = 0; $i < $linecount; $i++) {
            if(GETPOST($i, 'alpha') === 'on') { //one line
                $supplierpriceid = GETPOST('fourn'.$i, 'int');
                //get all the parameters needed to create a line
                $qty = GETPOST('tobuy'.$i, 'int');
                $desc = GETPOST('desc'.$i, 'alpha');
                $sql = 'Select fk_product, fk_soc, ref_fourn';
                $sql .= ', tva_tx, unitprice';
                $sql .= ' from '.MAIN_DB_PREFIX.'product_fournisseur_price';
                $sql .= ' where rowid = '.$supplierpriceid;
                $resql = $db->query($sql);
                if($resql && $db->num_rows($resql) > 0) {
                    //might need some value checks
                    $obj = $db->fetch_object($resql);
                    $line = new CommandeFournisseurLigne($db);
                    $line->qty = $qty;
                    $line->desc = $desc;
                    $line->fk_product = $obj->fk_product;
                    $line->tva_tx = $obj->tva_tx;
                    $line->subprice = $obj->unitprice;
                    $line->total_ht = $obj->unitprice * $qty;
                    $line->total_tva = $line->total_ht * $line->tva_tx / 100;
                    $line->total_ttc = $line->total_ht + $line->total_tva;
                    $line->ref_fourn = $obj->ref_fourn;
                    $suppliers[$obj->fk_soc]['lines'][] = $line;
                }
                else {
                    $error=$db->lasterror();
                    dol_print_error($db);
                    dol_syslog("replenish.php: ".$error, LOG_ERROR);
                }
            }
        }
        $db->free($resql);
        //we now know how many orders we need and what lines they have
        $i = 0;
        $orders = array();
        $suppliersid = array_keys($suppliers);
        foreach($suppliers as $supplier){
            $order = new CommandeFournisseur($db);
            $order->socid = $suppliersid[$i];
            //little trick to know which orders have been generated this way
            $order->source = 42;
            foreach($supplier['lines'] as $line){
                $order->lines[] = $line;
            }
            $id = $order->create($user);
            if($id < 0) {
                //error stuff
            }
            $i++;
        }
    }
}

/*
 * View
 */

$htmlother=new FormOther($db);

$title=$langs->trans("Replenishment");

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
$sql.= ' p.fk_product_type, p.tms as datem,';
$sql.= ' p.duration, p.tobuy, p.seuil_stock_alerte,';
$sql.= ' SUM(s.reel) as stock_physique';
$sql .= ', p.desiredstock';
$sql.= ' FROM ('.MAIN_DB_PREFIX.'product as p';
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_product as cp";
$sql .= ') LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price as pf on p.rowid = pf.fk_product';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as s on p.rowid = s.fk_product';

$sql.= " WHERE p.entity IN (".getEntity('product', 1).")";
if ($search_categ) $sql.= " AND p.rowid = cp.fk_product";	// Join for the needed table to filter by categ
if ($sall)
{
    $sql.= " AND (p.ref LIKE '%".$db->escape($sall)."%' OR p.label LIKE '%".$db->escape($sall)."%' OR p.description LIKE '%".$db->escape($sall)."%' OR p.note LIKE '%".$db->escape($sall)."%')";
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type))
{
    if ($type==1)
    {
        $sql.= " AND p.fk_product_type = '1'";
    }
    else
    {
        $sql.= " AND p.fk_product_type <> '1'";
    }
}
if ($sref)     $sql.= " AND p.ref LIKE '%".$sref."%'";
if ($sbarcode) $sql.= " AND p.barcode LIKE '%".$sbarcode."%'";
if ($snom)     $sql.= " AND p.label LIKE '%".$db->escape($snom)."%'";

$sql.= " AND p.tobuy = 1";

if (! empty($canvas))
{
    $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
}
if($catid)
{
    $sql.= " AND cp.fk_categorie = ".$catid;
}

    $sql.= " AND p.rowid = pf.fk_product";

// Insert categ filter
if ($search_categ)
{
    $sql .= " AND cp.fk_categorie = ".$db->escape($search_categ);
}
$sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,";
$sql.= " p.fk_product_type, p.tms,";
$sql.= " p.duration, p.tobuy, p.seuil_stock_alerte";
$sql .= ", p.desiredstock";
$sql.= ' HAVING p.desiredstock > SUM(s.reel) or SUM(s.reel) is NULL';
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);

    $i = 0;

    if ($num == 1 && ($sall or $snom or $sref))
    {
        $objp = $db->fetch_object($resql);
        header("Location: fiche.php?id=$objp->rowid");
        exit;
    }

    $helpurl='';
    $helpurl='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
    $texte = $langs->trans('Replenishment');
    llxHeader("",$title,$helpurl,$texte);

    if ($sref || $snom || $sall || GETPOST('search'))
    {
        print_barre_liste($texte, $page, "replenish.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall, $sortfield, $sortorder,'',$num);
    }
    else
    {
        print_barre_liste($texte, $page, "replenish.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
    }

    if (! empty($catid))
    {
        print "<div id='ways'>";
        $c = new Categorie($db);
        $c->fetch($catid);
        $ways = $c->print_all_ways(' &gt; ','product/replenish.php');
        print " &gt; ".$ways[0]."<br>\n";
        print "</div><br>";
    }

    print '<form action="replenish.php" method="post" name="formulaire">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';
    print '<input type="hidden" name="linecount" value="'.$num.'">';
    print '<input type="hidden" name="action" value="order">';

    print '<table class="liste" width="100%">';

    // Filter on categories
    $moreforfilter='';
    if (! empty($conf->categorie->enabled))
    {
        $moreforfilter.=$langs->trans('Categories'). ': ';
        $moreforfilter.=$htmlother->select_categories(0,$search_categ,'search_categ');
        $moreforfilter.=' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
    }
    if ($moreforfilter)
    {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="9">';
        print $moreforfilter;
        print '</td></tr>';
    }

    $param=(isset($type)?"&type=$type":"")."&fourn_id=$fourn_id&snom=$snom&sref=$sref";

    // Lignes des titres
    print "<tr class=\"liste_titre\">";
    print "<td>&nbsp;</td>";
    print_liste_field_titre($langs->trans("Ref"),"replenish.php", "p.ref",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"),"replenish.php", "p.label",$param,"","",$sortfield,$sortorder);
    if (! empty($conf->service->enabled) && $type == 1) print_liste_field_titre($langs->trans("Duration"),"replenish.php", "p.duration",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DesiredStock"),"replenish.php", "p.desiredstock",$param,"",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("PhysicalStock"),"replenish.php", "stock_physique",$param,"",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("StockToBuy"),"replenish.php", "",$param,"",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Supplier"),"replenish.php", "",$param,"",'align="right"',$sortfield,$sortorder);
    print '<td>&nbsp;</td>';
    print "</tr>\n";

    // Lignes des champs de filtre
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="sref" value="'.$sref.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
    print '</td>';
    if (! empty($conf->service->enabled) && $type == 1)
    {
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
    }
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
    print '</td>';
    print '</tr>';

    $product_static=new Product($db);

    $var=True;
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
        print '<td><input type="checkbox" name="'.$i.'"></td>';
        print '<td class="nowrap">';
        $product_static->ref=$objp->ref;
        $product_static->id=$objp->rowid;
        $product_static->type=$objp->fk_product_type;
        print $product_static->getNomUrl(1,'',16);
        print '</td>';
        print '<td>'.$objp->label.'</td>';
        print '<input type="hidden" name="desc'.$i.'" value="'.$objp->label.'" >';

        if (! empty($conf->service->enabled) && $type == 1)
        {
            print '<td align="center">';
            if (preg_match('/([0-9]+)y/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
            elseif (preg_match('/([0-9]+)m/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
            elseif (preg_match('/([0-9]+)d/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
            else print $objp->duration;
            print '</td>';
        }
        print '<td align="right">'.$objp->desiredstock.'</td>';
        print '<td align="right">';
        if(!$objp->stock_physique) $objp->stock_physique = 0;
        if ($objp->seuil_stock_alerte && ($objp->stock_physique < $objp->seuil_stock_alerte)) print img_warning($langs->trans("StockTooLow")).' ';
        print $objp->stock_physique;
        print '</td>';
        //depending on conf, use either physical stock or
        //theoretical stock to compute the stock to buy value
        ($conf->global->use_theoretical_stock? $stock = $objp->stock_théorique : $stock = $objp->stock_physique);
        $stocktobuy = $objp->desiredstock - $stock;
        print '<td align="right">'.$stocktobuy.'</td>';
        print '<input type="hidden" name="tobuy'.$i.'" value="'.$stocktobuy.'" >';
        $form = new Form($db);
        print '<td align="right">'.$form->select_product_fourn_price($product_static->id, "fourn".$i).'</td>';
        print '<td>&nbsp</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    print '<table width="100%">';
    print '<tr><td align="right"><input class="button" type="submit" value="'.$langs->trans("Validate").'"></td></tr>';
    print '</table>';
    print '</form>';

    if ($num > $conf->liste_limit)
    {
        if ($sref || $snom || $sall || GETPOST('search'))
        {
            print_barre_liste('', $page, "replenish.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall, $sortfield, $sortorder,'',$num, 0, '');
        }
        else
        {
            print_barre_liste('', $page, "replenish.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num, 0, '');
        }
    }

    $db->free($resql);

}
else
{
    dol_print_error($db);
}

$commandestatic=new CommandeFournisseur($db);
$sref=GETPOST('search_ref');
$snom=GETPOST('search_nom');
$suser=GETPOST('search_user');
$sttc=GETPOST('search_ttc');
$sall=GETPOST('search_all');

$page  = GETPOST('page','int');

$sortorder="DESC";
$sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;
$sql = "SELECT s.rowid as socid, s.nom, cf.date_creation as dc,";
$sql.= " cf.rowid,cf.ref, cf.fk_statut, cf.total_ttc, cf.fk_user_author,";
$sql.= " u.login";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON cf.fk_user_author = u.rowid";
$sql.= " WHERE cf.fk_soc = s.rowid ";
$sql.= " AND cf.entity = ".$conf->entity;
$sql.= " AND cf.source = 42";
$sql.= " AND cf.fk_statut < 5";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($sref)
{
	$sql.= " AND cf.ref LIKE '%".$db->escape($sref)."%'";
}
if ($snom)
{
	$sql.= " AND s.nom LIKE '%".$db->escape($snom)."%'";
}
if ($suser)
{
	$sql.= " AND u.login LIKE '%".$db->escape($suser)."%'";
}
if ($sttc)
{
	$sql .= " AND total_ttc = ".price2num($sttc);
}
if ($sall)
{
	$sql.= " AND (cf.ref LIKE '%".$db->escape($sall)."%' OR cf.note LIKE '%".$db->escape($sall)."%')";
}
if ($socid) $sql.= " AND s.rowid = ".$socid;

if (GETPOST('statut'))
{
	$sql .= " AND fk_statut =".GETPOST('statut');
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
$resql = $db->query($sql);
if ($resql)
{

	$num = $db->num_rows($resql);
	$i = 0;


	print_barre_liste($langs->trans('ReplenishmentOrders'), $page, "replenish.php", "", $sortfield, $sortorder, '', $num);
	print '<form action="replenish.php" method="GET">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("OrderCreation"),$_SERVER["PHP_SELF"],"","","",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"","","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$sref.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$snom.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_user" value="'.$suser.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ttc" value="'.$sttc.'"></td>';
	print '<td colspan="2" class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print '</tr>';

	$var=true;

	$userstatic = new User($db);

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr $bc[$var]>";

		// Ref
		print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>'."\n";

		// Company
		print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
		print $obj->nom.'</a></td>'."\n";

		// Author
		$userstatic->id=$obj->fk_user_author;
		$userstatic->login=$obj->login;
		print "<td>";
		if ($userstatic->id) print $userstatic->getLoginUrl(1);
		else print "&nbsp;";
		print "</td>";

		// Amount
		print '<td align="right" width="100">'.price($obj->total_ttc)."</td>";

		// Date
		print "<td align=\"center\" width=\"100\">";
		if ($obj->dc)
		{
			print dol_print_date($db->jdate($obj->dc),"day");
		}
		else
		{
			print "-";
		}
		print '</td>';

		// Statut
		print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut, 5).'</td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>\n";
	print "</form>\n";

	$db->free($resql);
}

llxFooter();
$db->close();
?>
