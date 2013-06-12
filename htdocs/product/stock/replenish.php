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
if ($user->societe_id) {
    $socid = $user->societe_id;
}
$result=restrictedArea($user,'produit|service');

//checks if a product has been ordered
function ordered($product_id) {
    global $db;
    $sql = 'SELECT DISTINCT cfd.fk_product, SUM(cfd.qty) from ';
    $sql .= MAIN_DB_PREFIX . 'commande_fournisseurdet as cfd '; 
    $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur as cf'; 
    $sql .= ' ON cfd.fk_commande = cf.rowid WHERE cf.source = 42 '; 
    $sql .= 'AND cf.fk_statut < 5 AND cfd.fk_product = ' . $product_id;
    $sql .= ' GROUP BY cfd.fk_product';
    
    $resql = $db->query($sql);
    if($resql) {
        $exists = $db->num_rows($resql);
        if($exists) {
            $obj = $db->fetch_array($resql);
            return $obj['SUM(cfd.qty)'] . ' ' . img_picto('','tick');
        }
        else {
            return img_picto('', 'stcomm-1');
        }
    }
    else {
        $error = $db->lasterror();
        dol_print_error($db);
        dol_syslog('replenish.php: ' . $error, LOG_ERROR);
        return $langs->trans('error');
    }
}

$action = GETPOST('action','alpha');
$sref = GETPOST('sref');
$snom = GETPOST('snom');
$sall = GETPOST('sall');
$type = GETPOST('type','int');
$sbarcode = GETPOST('sbarcode');
$catid = GETPOST('catid','int');
$tobuy = GETPOST('tobuy');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if (!$sortfield) {
    $sortfield = 'stock_physique';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}
$limit = $conf->liste_limit;
$offset = $limit * $page ;

// Load sale and categ filters
$search_sale = GETPOST('search_sale');
$search_categ = GETPOST('search_categ');

// Get object canvas 
//(By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST('canvas');
$objcanvas = '';
if (! empty($canvas)) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product', 'list', $canvas);
}

if (! empty($_POST['button_removefilter_x'])) {
    $sref = '';
    $snom = '';
    $sall = '';
    $search_sale = '';
    $search_categ = '';
    $type = '';
    $catid = '';
}



/*
 * Actions
 */

//orders creation
if($action == 'order') {
    $linecount = GETPOST('linecount', 'int');
    unset($_POST['linecount']);
    if($linecount > 0) {
        $suppliers = array();
        for($i = 0; $i < $linecount; $i++) {
            if(GETPOST($i, 'alpha') === 'on' 
              && GETPOST('fourn' . $i, 'int') > 0) { //one line
                $supplierpriceid = GETPOST('fourn'.$i, 'int');
                //get all the parameters needed to create a line
                $qty = GETPOST('tobuy'.$i, 'int');
                $desc = GETPOST('desc'.$i, 'alpha');
                $sql = 'SELECT fk_product, fk_soc, ref_fourn';
                $sql .= ', tva_tx, unitprice FROM ';
                $sql .= MAIN_DB_PREFIX . 'product_fournisseur_price';
                $sql .= ' WHERE rowid = ' . $supplierpriceid;
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
                    $tva = $line->tva_tx / 100;
                    $line->total_tva = $line->total_ht * $tva;
                    $line->total_ttc = $line->total_ht + $line->total_tva;
                    $line->ref_fourn = $obj->ref_fourn;
                    $suppliers[$obj->fk_soc]['lines'][] = $line;
                }
                else {
                    $error=$db->lasterror();
                    dol_print_error($db);
                    dol_syslog('replenish.php: '.$error, LOG_ERROR);
                }
                $db->free($resql);
                unset($_POST['fourn' . $i]);
            }
            unset($_POST[$i]);
        }
        //we now know how many orders we need and what lines they have
        $i = 0;
        $orders = array();
        $suppliersid = array_keys($suppliers);
        foreach($suppliers as $supplier) {
            $order = new CommandeFournisseur($db);
            $order->socid = $suppliersid[$i];
            //trick to know which orders have been generated this way
            $order->source = 42;
            foreach($supplier['lines'] as $line) {
                $order->lines[] = $line;
            }
            $id = $order->create($user);
            if($id < 0) {
                //error stuff
                $fail++;
                setEventMessage($langs->trans('OrderFail'), 'errors');
            }
            $i++;
        }
        if(!$fail && $id) {
            setEventMessage($langs->trans('OrderCreated'), 'mesgs');
            header('Location: replenishorders.php');
            exit;
        }
    }
}

/*
 * View
 */
$htmlother=new FormOther($db);

$title=$langs->trans('Replenishment');

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type';
$sql .= ', p.tms as datem, p.duration, p.tobuy, p.seuil_stock_alerte,';
$sql .= ' SUM(s.reel) as stock_physique';
$sql .= ', p.desiredstock';
$sql .= ' FROM (' . MAIN_DB_PREFIX . 'product as p';
// need this table joined to the select in order to filter by categ
if ($search_categ) {
    $sql.= ", " . MAIN_DB_PREFIX . "categorie_product as cp";
}
$sql .= ') LEFT JOIN ' . MAIN_DB_PREFIX . 'product_fournisseur_price as pf';
$sql .= ' ON p.rowid = pf.fk_product';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s';
$sql .= ' ON p.rowid = s.fk_product';

$sql.= ' WHERE p.entity IN (' . getEntity("product", 1) . ')';
if ($search_categ) { // Join for the needed table to filter by categ
    $sql .= ' AND p.rowid = cp.fk_product';
}
if ($sall) {
    $sql .= ' AND (p.ref LIKE "%'.$db->escape($sall).'%" ';
    $sql .= 'OR p.label LIKE "%'.$db->escape($sall).'%" ';
    $sql .= 'OR p.description LIKE "%'.$db->escape($sall).'%" ';
    $sql .= 'OR p.note LIKE "%'.$db->escape($sall).'%")';
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
    if ($type == 1) {
        $sql .= ' AND p.fk_product_type = 1';
    }
    else {
        $sql .= ' AND p.fk_product_type != 1';
    }
}
if ($sref) {
    $sql .= ' AND p.ref LIKE "%' . $sref . '%"';
}
if ($sbarcode) {
    $sql .= ' AND p.barcode LIKE "%' . $sbarcode . '%"';
}
if ($snom) {
    $sql .= ' AND p.label LIKE "%' . $db->escape($snom) . '%"';
}

$sql .= ' AND p.tobuy = 1';

if (!empty($canvas)) {
    $sql .= ' AND p.canvas = "' . $db->escape($canvas) . '"';
}
if($catid) {
    $sql .= ' AND cp.fk_categorie = ' . $catid;
}

    $sql .= ' AND p.rowid = pf.fk_product';

// Insert categ filter
if ($search_categ) {
    $sql .= ' AND cp.fk_categorie = ' . $db->escape($search_categ);
}
$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql .= ', p.duration, p.tobuy, p.seuil_stock_alerte';
$sql .= ', p.desiredstock';
$sql .= ' HAVING p.desiredstock > SUM(s.reel) or SUM(s.reel) is NULL';
$sql .= $db->order($sortfield,$sortorder);
$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    if ($num == 1 && ($sall or $snom or $sref)) {
        $objp = $db->fetch_object($resql);
        header('Location: ../fiche.php?id=' . $objp->rowid);
        exit;
    }

    $helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
    $helpurl .= 'ES:M&oacute;dulo_Stocks';
    $texte = $langs->trans('Replenishment');
    llxHeader('', $title, $helpurl, $texte);
    $head = array();
    $head[0][0] = DOL_URL_ROOT.'/product/stock/replenish.php';
    $head[0][1] = $title;
    $head[0][2] = 'replenish';
    $head[1][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
    $head[1][1] = $langs->trans("ReplenishmentOrders");
    $head[1][2] = 'replenishorders';
    dol_fiche_head($head, 'replenish', $title, 0, 'stock');
    if ($sref || $snom || $sall || GETPOST('search')) {
        $filters = '&sref=' . $sref . '&snom=' . $snom;
        $filters .= '&amp;sall=' . $sall;
        print_barre_liste($texte, 
                          $page, 
                          'replenish.php', 
                          $filters, 
                          $sortfield, 
                          $sortorder,
                          '',
                          $num
                          );
    }
    else {
        $filters = '&sref=' . $sref . '&snom=' . $snom;
        $filters .= '&fourn_id=' . $fourn_id;
        $filters .= (isset($type)?'&amp;type=' . $type:'');
        print_barre_liste($texte, 
                          $page, 
                          'replenish.php', 
                          $filters, 
                          $sortfield, 
                          $sortorder, 
                          '', 
                          $num);
    }

    if (!empty($catid)) {
        print '<div id="ways">';
        $c = new Categorie($db);
        $c->fetch($catid);
        $ways = $c->print_all_ways(' &gt; ', 'product/replenish.php');
        print ' &gt; ' . $ways[0] . '<br>';
        print '</div><br>';
    }

    print '<form action="replenish.php" method="post" name="formulaire">';
    print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="type" value="' . $type . '">';
    print '<input type="hidden" name="linecount" value="' . $num . '">';
    print '<input type="hidden" name="action" value="order">';
    //print '</div>';
    print '<table class="liste" width="100%">';

    // Filter on categories
    $moreforfilter = '';
    if (!empty($conf->categorie->enabled)) {
        $moreforfilter .= $langs->trans('Categories') . ': ';
        $moreforfilter .= $htmlother->select_categories(0, 
                                                        $search_categ, 
                                                        'search_categ'
                                                        );
        $moreforfilter .= ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
    }
    if ($moreforfilter) {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="9">';
        print $moreforfilter;
        print '</td></tr>';
    }

    $param = (isset($type)? '&type=' . $type : '');
    $param .= '&fourn_id=' . $fourn_id . '&snom='. $snom;
    $param .= '&sref=' . $sref;

    // Lignes des titres
    print '<tr class="liste_titre">';
    print '<td>&nbsp;</td>';
    print_liste_field_titre($langs->trans('Ref'), 
                            'replenish.php', 
                            'p.ref', 
                            $param, 
                            '', 
                            '', 
                            $sortfield, 
                            $sortorder
                            );
    print_liste_field_titre($langs->trans('Label'), 
                            'replenish.php', 
                            'p.label', 
                            $param, 
                            '', 
                            '', 
                            $sortfield, 
                            $sortorder
                            );
    if (!empty($conf->service->enabled) && $type == 1) {
        print_liste_field_titre($langs->trans('Duration'), 
                                'replenish.php', 
                                'p.duration', 
                                $param, 
                                '', 
                                'align="center"', 
                                $sortfield, 
                                $sortorder
                                );
    }
    print_liste_field_titre($langs->trans('DesiredStock'), 
                            'replenish.php', 
                            'p.desiredstock', 
                            $param, 
                            '', 
                            'align="right"', 
                            $sortfield, 
                            $sortorder
                            );
    if($conf->global->USE_VIRTUAL_STOCK) {
        print_liste_field_titre($langs->trans('VirtualStock'), 
                                'replenish.php', 
                                '', 
                                $param, 
                                '', 
                                'align="right"', 
                                $sortfield, 
                                $sortorder
                                );
    }
    else {
        print_liste_field_titre($langs->trans('PhysicalStock'), 
                                'replenish.php', 
                                'stock_physique', 
                                $param, 
                                '', 
                                'align="right"', 
                                $sortfield, 
                                $sortorder
                                );
    }
    print_liste_field_titre($langs->trans('StockToBuy'), 
                            'replenish.php', 
                            '', 
                            $param, 
                            '', 
                            'align="right"', 
                            $sortfield, 
                            $sortorder
                            );
    print_liste_field_titre($langs->trans('Ordered'), 
                            'replenish.php', 
                            '', 
                            $param, 
                            '', 
                            'align="right"', 
                            $sortfield, 
                            $sortorder
                            );
    print_liste_field_titre($langs->trans('Supplier'), 
                            'replenish.php', 
                            '', 
                            $param, 
                            '', 
                            'align="right"', 
                            $sortfield, 
                            $sortorder
                            );
    print '<td>&nbsp;</td>';
    print '</tr>';

    // Lignes des champs de filtre
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="sref" value="' . $sref . '">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="snom" value="' . $snom . '">';
    print '</td>';
    if (!empty($conf->service->enabled) && $type == 1) {
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
    }
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" alt="' . $langs->trans("Search") . '">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" alt="' . $langs->trans("RemoveFilter") . '">';
    print '</td>';
    print '</tr>';

    $prod = new Product($db);

    $var = True;
    while ($i < min($num, $limit)) {
        $objp = $db->fetch_object($resql);
        if($conf->global->STOCK_SUPPORTS_SERVICES 
           || $objp->fk_product_type == 0) {
            // Multilangs
            if(! empty($conf->global->MAIN_MULTILANGS)) {
                $sql = 'SELECT label';
                $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lang';
                $sql .= ' WHERE fk_product = ' . $objp->rowid;
                $sql .= ' AND lang = "' . $langs->getDefaultLang() . '"';
                $sql .= ' LIMIT 1';

                $result = $db->query($sql);
                if($result) {
                    $objtp = $db->fetch_object($result);
                    if (!empty($objtp->label)) {
                        $objp->label = $objtp->label;
                    }
                }
            }

            $var =! $var;
            print '<tr ' . $bc[$var] . '>';
            print '<td><input type="checkbox" name="' . $i . '"></td>';
            print '<td class="nowrap">';
            $prod->ref = $objp->ref;
            $prod->id = $objp->rowid;
            $prod->type = $objp->fk_product_type;
            print $prod->getNomUrl(1, '', 16);
            print '</td>';
            print '<td>' . $objp->label . '</td>';
            print '<input type="hidden" name="desc' . $i . '" value="' . $objp->label . '" >';

            if(!empty($conf->service->enabled) && $type == 1) {
                print '<td align="center">';
                if(preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
                     print $regs[1] . ' ' . $langs->trans('DurationYear');
                }
                else if(preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
                    print $regs[1] . ' ' . $langs->trans('DurationMonth');
                }
                else if(preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
                    print $regs[1] . ' ' . $langs->trans('DurationDay');
                }
                else {
                    print $objp->duration;
                }
                print '</td>';
            }
            print '<td align="right">' . $objp->desiredstock . '</td>';
            print '<td align="right">';
            if(!$objp->stock_physique) {
                $objp->stock_physique = 0;
            }
            if($conf->global->USE_VIRTUAL_STOCK) {
                $prod->fetch($prod->id);
                $result=$prod->load_stats_commande(0, '1,2');
                if ($result < 0) {
                    dol_print_error($db, $prod->error);
                }
                $stock_commande_client = $prod->stats_commande['qty'];
                $result=$prod->load_stats_commande_fournisseur(0, '3');
                if ($result < 0) {
                    dol_print_error($db,$prod->error);
                }
                $stock_commande_fournisseur = $prod->stats_commande_fournisseur['qty'];
                $stock = $objp->stock_physique - $stock_commande_client + $stock_commande_fournisseur;
            }
            else {
                $stock = $objp->stock_physique;
            }
            if ($objp->seuil_stock_alerte 
                && ($stock < $objp->seuil_stock_alerte)) {
                    $warn = $langs->trans('StockTooLow');
                    print img_warning($warn) . ' ';
            }
            print $stock;
            print '</td>';
            //depending on conf, use either physical stock or
            //virtual stock to compute the stock to buy value
            $stocktobuy = $objp->desiredstock - $stock;
            print '<td align="right">'.$stocktobuy.'</td>';
            print '<input type="hidden" name="tobuy' . $i . '" value="' . $stocktobuy . '" >';
            print '<td align="right">';
            print ordered($prod->id);
            print '</td>';
            $form = new Form($db);
            print '<td align="right">';
            print $form->select_product_fourn_price($prod->id, 
                                                    'fourn' . $i, 1);
            print '</td>';
            print '<td>&nbsp</td>';
            print "</tr>";
        }
        $i++;
    }
    print "</table>";
    print '<table width="100%">';
    print '<tr><td align="right">';
    $valid = $langs->trans("Validate");
    print '<input class="button" type="submit" value="' . $valid . '">';
    print '</td></tr></table>';
    print '</form>';

    if ($num > $conf->liste_limit) {
        if ($sref || $snom || $sall || GETPOST('search')) {
            $filters = '&sref=' . $sref . '&snom=' . $snom;
            $filters .= '&amp;sall=' . $sall;
            print_barre_liste('', 
                              $page, 
                              'replenish.php', 
                              $filters, 
                              $sortfield, 
                              $sortorder, 
                              '', 
                              $num, 
                              0, 
                              ''
                              );
        }
        else {
            $filters = '&sref=' . $sref . '&snom=' . $snom;
            $filters .= '&fourn_id=' . $fourn_id;
            $filters .= (isset($type)? '&amp;type=' . $type : '');
            print_barre_liste('', 
                              $page, 
                              'replenish.php', 
                              $filters, 
                              $sortfield, 
                              $sortorder, 
                              '', 
                              $num, 
                              0, 
                              ''
                              );
        }
    }

    $db->free($resql);

}
else {
    dol_print_error($db);
}

llxFooter();
$db->close();
?>
