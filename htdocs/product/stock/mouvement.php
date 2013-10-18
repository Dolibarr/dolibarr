<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/product/stock/mouvement.php
 *	\ingroup    stock
 *	\brief      Page to list stock movements
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("products");
$langs->load("stocks");

// Security check
$result=restrictedArea($user,'stock');

$id=GETPOST('id','int');
$product_id=GETPOST("product_id");
$action=GETPOST('action');
$cancel=GETPOST('cancel');
$idproduct = GETPOST('idproduct','int');
$year = GETPOST("year");
$month = GETPOST("month");
$search_movement = GETPOST("search_movement");
$search_product_ref = trim(GETPOST("search_product_ref"));
$search_product = trim(GETPOST("search_product"));
$search_warehouse = trim(GETPOST("search_warehouse"));
$search_user = trim(GETPOST("search_user"));
$page = GETPOST("page",'int');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if ($page < 0) $page = 0;
$offset = $conf->liste_limit * $page;

if (! $sortfield) $sortfield="m.datem";
if (! $sortorder) $sortorder="DESC";

if (GETPOST("button_removefilter"))
{
    $year='';
    $month='';
    $search_movement="";
    $search_product_ref="";
    $search_product="";
    $search_warehouse="";
    $search_user="";
    $sall="";
}


/*
 * Actions
 */

if ($cancel) $action='';

// Correct stock
if ($action == "correct_stock" && ! $_POST["cancel"])
{
    if (is_numeric($_POST["nbpiece"]) && $product_id)
    {
        $product = new Product($db);
        $result=$product->fetch($product_id);

        $result=$product->correct_stock(
            $user,
            $id,
            $_POST["nbpiece"],
            $_POST["mouvement"],
            $_POST["label"],
            0
        );		// We do not change value of stock for a correction

        if ($result > 0)
        {
            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
            exit;
        }
    }
    else $action='';
}


/*
 * View
 */

$productstatic=new Product($db);
$warehousestatic=new Entrepot($db);
$movement=new MouvementStock($db);
$userstatic=new User($db);
$form=new Form($db);
$formother=new FormOther($db);
$formproduct=new FormProduct($db);

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type,";
$sql.= " e.label as stock, e.rowid as entrepot_id,";
$sql.= " m.rowid as mid, m.value, m.datem, m.fk_user_author, m.label,";
$sql.= " u.login";
$sql.= " FROM (".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product as p,";
$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = e.rowid";
$sql.= " AND e.entity = ".$conf->entity;
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
if ($id)
{
    $sql.= " AND e.rowid ='".$id."'";
}
if ($month > 0)
{
    if ($year > 0)
    $sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else
    $sql.= " AND date_format(m.datem, '%m') = '$month'";
}
else if ($year > 0)
{
    $sql.= " AND m.datem BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if (! empty($search_movement))
{
    $sql.= " AND m.label LIKE '%".$db->escape($search_movement)."%'";
}
if (! empty($search_product_ref))
{
    $sql.= " AND p.ref LIKE '%".$db->escape($search_product_ref)."%'";
}
if (! empty($search_product))
{
    $sql.= " AND p.label LIKE '%".$db->escape($search_product)."%'";
}
if (! empty($search_warehouse))
{
    $sql.= " AND e.label LIKE '%".$db->escape($search_warehouse)."%'";
}
if (! empty($search_user))
{
    $sql.= " AND u.login LIKE '%".$db->escape($search_user)."%'";
}
if ($idproduct > 0)
{
    $sql.= " AND p.rowid = '".$idproduct."'";
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    if ($idproduct)
    {
        $product = new Product($db);
        $product->fetch($idproduct);
    }

    if ($id > 0)
    {
        $entrepot = new Entrepot($db);
        $result = $entrepot->fetch($id);
        if ($result < 0)
        {
            dol_print_error($db);
        }
    }

    $i = 0;

    $help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
    $texte = $langs->trans("ListOfStockMovements");
    if ($id) $texte.=' ('.$langs->trans("ForThisWarehouse").')';
    llxHeader("",$texte,$help_url);

    /*
     * Show tab only if we ask a particular warehouse
     */
    if ($id)
    {
        $head = stock_prepare_head($entrepot);

        dol_fiche_head($head, 'movements', $langs->trans("Warehouse"), 0, 'stock');


        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/liste.php">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
        print $form->showrefnav($entrepot, 'id', $linkback, 1, 'rowid', 'libelle');
        print '</td>';

        print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$entrepot->lieu.'</td></tr>';

        // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">'.dol_htmlentitiesbr($entrepot->description).'</td></tr>';

        // Address
        print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
        print $entrepot->address;
        print '</td></tr>';

        // Town
        print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$entrepot->zip.'</td>';
        print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$entrepot->town.'</td></tr>';

        // Country
        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        $img=picto_from_langcode($entrepot->country_code);
        print ($img?$img.' ':'');
        print $entrepot->country;
        print '</td></tr>';

        // Status
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$entrepot->getLibStatut(4).'</td></tr>';

        $calcproductsunique=$entrepot->nb_different_products();
        $calcproducts=$entrepot->nb_products();

        // Total nb of different products
        print '<tr><td valign="top">'.$langs->trans("NumberOfDifferentProducts").'</td><td colspan="3">';
        print empty($calcproductsunique['nb'])?'0':$calcproductsunique['nb'];
        print "</td></tr>";

        // Nb of products
        print '<tr><td valign="top">'.$langs->trans("NumberOfProducts").'</td><td colspan="3">';
        print empty($calcproducts['nb'])?'0':$calcproducts['nb'];
        print "</td></tr>";

        // Value
        print '<tr><td valign="top">'.$langs->trans("EstimatedStockValueShort").'</td><td colspan="3">';
        print empty($calcproducts['value'])?'0':$calcproducts['value'];
        print "</td></tr>";

        // Last movement
        $sql = "SELECT MAX(m.datem) as datem";
        $sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
        $sql .= " WHERE m.fk_entrepot = '".$entrepot->id."'";
        $resqlbis = $db->query($sql);
        if ($resqlbis)
        {
            $obj = $db->fetch_object($resqlbis);
            $lastmovementdate=$db->jdate($obj->datem);
        }
        else
        {
            dol_print_error($db);
        }

        print '<tr><td valign="top">'.$langs->trans("LastMovement").'</td><td colspan="3">';
        if ($lastmovementdate)
        {
            print dol_print_date($lastmovementdate,'dayhour');
        }
        else
        {
            print $langs->trans("None");
        }
        print "</td></tr>";

        print "</table>";

        print '</div>';
    }


    /*
     * Correct stock
     */
    if ($action == "correction")
    {
        print_titre($langs->trans("StockCorrection"));
        print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="correct_stock">';
        print '<table class="border" width="100%">';

        // Warehouse
        print '<tr>';
        print '<td width="20%">'.$langs->trans("Product").'</td>';
        print '<td width="20%">';
        print $form->select_produits(GETPOST('productid'),'product_id',(empty($conf->global->STOCK_SUPPORTS_SERVICES)?'0':''));
        print '</td>';
        print '<td width="20%">';
        print '<select name="mouvement" class="flat">';
        print '<option value="0">'.$langs->trans("Add").'</option>';
        print '<option value="1">'.$langs->trans("Delete").'</option>';
        print '</select></td>';
        print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" size="10" value=""></td>';
        print '</tr>';

        // Label
        print '<tr>';
        print '<td width="20%">'.$langs->trans("Label").'</td>';
        print '<td colspan="4">';
        print '<input type="text" name="label" size="40" value="">';
        print '</td>';
        print '</tr>';

        print '</table>';

        print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';
        print '</form>';
    }

    /*
     * Transfer of units
     */
    /*
    if ($action == "transfert")
    {
        print_titre($langs->trans("Transfer"));
        print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="transfert_stock">';
        print '<table class="border" width="100%">';

        print '<tr>';
        print '<td width="20%">'.$langs->trans("Product").'</td>';
        print '<td width="20%">';
        print $form->select_produits(GETPOST('productid'),'product_id');
        print '</td>';
        print '<td width="20%">'.$langs->trans("WarehouseTarget").'</td><td width="20%">';
        print $formproduct->selectWarehouses('','id_entrepot_destination','',1);
        print '</td>';
        print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td>';
        print '</tr>';

        // Label
        print '<tr>';
        print '<td width="20%">'.$langs->trans("Label").'</td>';
        print '<td colspan="5">';
        print '<input type="text" name="label" size="40" value="">';
        print '</td>';
        print '</tr>';

        print '</table>';

        print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

        print '</form>';
    }
	*/

    /* ************************************************************************** */
    /*                                                                            */
    /* Barre d'action                                                             */
    /*                                                                            */
    /* ************************************************************************** */

    if (empty($action) && $id)
    {
        print "<div class=\"tabsAction\">\n";

        if ($user->rights->stock->creer)
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=correction">'.$langs->trans("StockCorrection").'</a>';
        }

        /*if ($user->rights->stock->mouvement->creer)
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert">'.$langs->trans("StockMovement").'</a>';
        }*/
        print '</div><br>';
    }


    $param='';
    if ($id) $param.='&id='.$id;
    if ($search_movement)   $param.='&search_movement='.urlencode($search_movement);
    if ($search_product_ref) $param.='&search_product_ref='.urlencode($search_product_ref);
    if ($search_product)   $param.='&search_product='.urlencode($search_product);
    if ($search_warehouse) $param.='&search_warehouse='.urlencode($search_warehouse);
    if ($sref) $param.='&sref='.urlencode($sref);
    if ($snom) $param.='&snom='.urlencode($snom);
    if ($search_user)    $param.='&search_user='.urlencode($search_user);
    if ($idproduct > 0)  $param.='&idproduct='.$idproduct;
    if ($id) print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num,0,'');
    else print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);

    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    //print_liste_field_titre($langs->trans("Id"),$_SERVER["PHP_SELF"], "m.rowid","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"], "m.datem","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("LabelMovement"),$_SERVER["PHP_SELF"], "m.label","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ProductRef"),$_SERVER["PHP_SELF"], "p.ref","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ProductLabel"),$_SERVER["PHP_SELF"], "p.ref","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Warehouse"),$_SERVER["PHP_SELF"], "","",$param,"",$sortfield,$sortorder);	// We are on a specific warehouse card, no filter on other should be possible
    print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"], "m.fk_user_author","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Units"),$_SERVER["PHP_SELF"], "m.value","",$param,'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    // Lignes des champs de filtre
    print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
    if ($id) print '<input type="hidden" name="id" value="'.$id.'">';

    print '<tr class="liste_titre">';
    print '<td class="liste_titre" valign="right">';
    print $langs->trans('Month').': <input class="flat" type="text" size="2" maxlength="2" name="month" value="'.$month.'">';
    print '&nbsp;'.$langs->trans('Year').': ';
    $syear = GETPOST('year')?GETPOST('year'):-1;
    $formother->select_year($syear,'year',1, 20, 5);
    print '</td>';
    // Label of movement
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="10" name="search_movement" value="'.$search_movement.'">';
    print '</td>';
    // Product Ref
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="6" name="search_product_ref" value="'.($idproduct?$product->ref:$search_product_ref).'">';
    print '</td>';
    // Product label
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="10" name="search_product" value="'.($idproduct?$product->libelle:$search_product).'">';
    print '</td>';
    print '<td class="liste_titre" align="left">';
    if (empty($idproduct) || $idproduct < 0) print '<input class="flat" type="text" size="10" name="search_warehouse" value="'.($search_warehouse).'">';	// We are on a specific warehouse card, no filter on other should be possible
    print '</td>';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="6" name="search_user" value="'.($search_user).'">';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '&nbsp; ';
    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';
    print "</tr>\n";
    print '</form>';

    $arrayofuniqueproduct=array();

    $var=True;
    while ($i < min($num,$conf->liste_limit))
    {
        $objp = $db->fetch_object($resql);

        $arrayofuniqueproduct[$objp->rowid]=$objp->produit;

        $var=!$var;
        print "<tr ".$bc[$var].">";
        // Id movement
        //print '<td>'.$objp->mid.'</td>';	// This is primary not movement id
        // Date
        print '<td>'.dol_print_date($db->jdate($objp->datem),'dayhour').'</td>';
        // Label of movement
        print '<td>'.$objp->label.'</td>';
		// Product ref
        print '<td>';
        $productstatic->id=$objp->rowid;
        $productstatic->ref=$objp->product_ref;
        $productstatic->type=$objp->type;
        print $productstatic->getNomUrl(1,'',16);
        print "</td>\n";
        // Product label
        print '<td>';
        $productstatic->id=$objp->rowid;
        $productstatic->ref=$objp->produit;
        $productstatic->type=$objp->type;
        print $productstatic->getNomUrl(1,'',16);
        print "</td>\n";
        // Warehouse
        print '<td>';
        $warehousestatic->id=$objp->entrepot_id;
        $warehousestatic->libelle=$objp->stock;
        print $warehousestatic->getNomUrl(1);
        print "</td>\n";
        // Author
        print '<td>';
        $userstatic->id=$objp->fk_user_author;
        $userstatic->lastname=$objp->login;
        print $userstatic->getNomUrl(1);
        print "</td>\n";
        // Value
        print '<td align="right">';
        if ($objp->value > 0) print '+';
        print $objp->value.'</td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($resql);

    print "</table><br>";

    // Add number of product when there is a filter on period
    if (count($arrayofuniqueproduct) == 1)
    {
    	$productidselected=0;
    	foreach ($arrayofuniqueproduct as $key => $val)
    	{
    		$productidselected=$key;
    		$productlabelselected=$val;
    	}
		$datebefore=dol_get_first_day($year, $month?$month:1, true);
		$dateafter=dol_get_last_day($year, $month?$month:12, true);
    	$balancebefore=$movement->calculateBalanceForProductBefore($productidselected, $datebefore);
    	$balanceafter=$movement->calculateBalanceForProductBefore($productidselected, $dateafter);

    	//print '<tr class="total"><td class="liste_total">';
    	print $langs->trans("NbOfProductBeforePeriod", $productlabelselected, dol_print_date($datebefore,'day','gmt'));
    	//print '</td>';
    	//print '<td class="liste_total" colspan="6" align="right">';
    	print ': '.$balancebefore;
    	print "<br>\n";
    	//print '</td></tr>';
    	//print '<tr class="total"><td class="liste_total">';
    	print $langs->trans("NbOfProductAfterPeriod", $productlabelselected, dol_print_date($dateafter,'day','gmt'));
    	//print '</td>';
    	//print '<td class="liste_total" colspan="6" align="right">';
    	print ': '.$balanceafter;
    	print "<br>\n";
    	//print '</td></tr>';
    }


}
else
{
    dol_print_error($db);
}

llxFooter();

$db->close();

?>
