<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2014	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@capnetworks.com>
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
 *  \ingroup    stock
 *  \brief      Page to list stocks to replenish
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once './lib/replenishment.lib.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");

// Security check
if ($user->societe_id) {
    $socid = $user->societe_id;
}
$result=restrictedArea($user,'produit|service');

//checks if a product has been ordered

$action = GETPOST('action','alpha');
$sref = GETPOST('sref', 'alpha');
$snom = GETPOST('snom', 'alpha');
$sall = GETPOST('sall', 'alpha');
$type = GETPOST('type','int');
$tobuy = GETPOST('tobuy', 'int');
$salert = GETPOST('salert', 'alpha');
$mode = GETPOST('mode','alpha');

$fourn_id = GETPOST('fourn_id','int');
$texte = '';

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}


/*
 * Actions
 */

if (isset($_POST['button_removefilter']) || isset($_POST['valid']))
{
    $sref = '';
    $snom = '';
    $sal = '';
    $salert = '';
}

// Create orders
if ($action == 'order' && isset($_POST['valid']))
{
    $linecount = GETPOST('linecount', 'int');
    $box = 0;
    unset($_POST['linecount']);
    if ($linecount > 0)
    {
    	$db->begin();

        $suppliers = array();
        for ($i = 0; $i < $linecount; $i++)
        {
            if (GETPOST($i, 'alpha') === 'on' && GETPOST('fourn' . $i, 'int') > 0)
            {
            	//one line
                $box = $i;
                $supplierpriceid = GETPOST('fourn'.$i, 'int');
                //get all the parameters needed to create a line
                $qty = GETPOST('tobuy'.$i, 'int');
                $desc = GETPOST('desc'.$i, 'alpha');
                $sql = 'SELECT fk_product, fk_soc, ref_fourn';
                $sql .= ', tva_tx, unitprice FROM ';
                $sql .= MAIN_DB_PREFIX . 'product_fournisseur_price';
                $sql .= ' WHERE rowid = ' . $supplierpriceid;
                $resql = $db->query($sql);
                if ($resql && $db->num_rows($resql) > 0)
                {
                	if ($qty)
                	{
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
                }
                else
				{
                    $error=$db->lasterror();
                    dol_print_error($db);
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
        foreach ($suppliers as $supplier)
        {
            $order = new CommandeFournisseur($db);
            // Check if an order for the supplier exists
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " WHERE fk_soc = ".$suppliersid[$i];
            $sql.= " AND source = 42 AND fk_statut = 0";
            $sql.= " ORDER BY date_creation DESC";
            $resql = $db->query($sql);
            if($resql && $db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
                $order->fetch($obj->rowid);
                foreach ($supplier['lines'] as $line) {
                    $result = $order->addline(
                        $line->desc,
                        $line->subprice,
                        $line->qty,
                        $line->tva_tx,
                        $line->localtax1_tx,
                        $line->localtax2_tx,
                        $line->fk_product,
                        0,
                        $line->ref_fourn,
                        $line->remise_percent,
                        'HT',
                        0,
                        $line->info_bits
                    );
                }
                if ($result < 0) {
                    $fail++;
                    $msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
                    $msg .= $order->error;
                    setEventMessage($msg, 'errors');
                } else {
                    $id = $result;
                }
            } else {
                $order->socid = $suppliersid[$i];
                $order->fetch_thirdparty();
                //trick to know which orders have been generated this way
                $order->source = 42;
                foreach ($supplier['lines'] as $line) {
                    $order->lines[] = $line;
                }
                $order->cond_reglement_id = $order->thirdparty->cond_reglement_supplier_id;
                $order->mode_reglement_id = $order->thirdparty->mode_reglement_supplier_id;
                $id = $order->create($user);
                if ($id < 0) {
                    $fail++;
                    $msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
                    $msg .= $order->error;
                    setEventMessage($msg, 'errors');
                }
                $i++;
            }
        }

        if (! $fail && $id)
        {
        	$db->commit();

            setEventMessage($langs->trans('OrderCreated'), 'mesgs');
            header('Location: replenishorders.php');
            exit;
        }
        else
        {
        	$db->rollback();
        }
    }
    if ($box == 0)
    {
        setEventMessage($langs->trans('SelectProductWithNotNullQty'), 'warnings');
    }
}


/*
 * View
 */

$form = new Form($db);

$virtualdiffersfromphysical=0;
if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
{
	$virtualdiffersfromphysical=1;		// According to increase/decrease stock options, virtual and physical stock may differs.
}

$usevirtualstock=-1;
if ($virtualdiffersfromphysical)
{
	$usevirtualstock=(! empty($conf->global->STOCK_USE_VIRTUAL_STOCK)?1:0);
	if ($mode=='virtual') $usevirtualstock=1;
	if ($mode=='physical') $usevirtualstock=0;
}

$title = $langs->trans('Status');

$sql = 'SELECT p.rowid, p.ref, p.label, p.price,';
$sql.= ' p.price_ttc, p.price_base_type,p.fk_product_type,';
$sql.= ' p.tms as datem, p.duration, p.tobuy,';
$sql.= ' p.desiredstock, p.seuil_stock_alerte as alertstock,';
$sql.= ' SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").') as stock_physique';
$sql.= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
$sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s';
$sql.= ' ON p.rowid = s.fk_product';
$sql.= ' WHERE p.entity IN (' . getEntity("product", 1) . ')';
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
    } else {
        $sql .= ' AND p.fk_product_type <> 1';
    }
}
if ($sref) {
    //natural search
    $scrit = explode(' ', $sref);
    foreach ($scrit as $crit) {
        $sql .= ' AND p.ref LIKE "%' . $crit . '%"';
    }
}
if ($snom) {
    //natural search
    $scrit = explode(' ', $snom);
    foreach ($scrit as $crit) {
        $sql .= ' AND p.label LIKE "%' . $db->escape($crit) . '%"';
    }
}
$sql.= ' AND p.tobuy = 1';
if (!empty($canvas)) $sql .= ' AND p.canvas = "' . $db->escape($canvas) . '"';
$sql.= ' GROUP BY p.rowid, p.ref, p.label, p.price';
$sql.= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql.= ', p.duration, p.tobuy';
$sql.= ', p.desiredstock, p.seuil_stock_alerte';
$sql.= ', s.fk_product';

if ($usevirtualstock)
{
	$sqlCommandesCli = "(SELECT ".$db->ifsql("SUM(cd.qty) IS NULL", "0", "SUM(cd.qty)")." as qty";
	$sqlCommandesCli.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
	$sqlCommandesCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (c.rowid = cd.fk_commande)";
	$sqlCommandesCli.= " WHERE c.entity IN (".getEntity('order', 1).")";
	$sqlCommandesCli.= " AND cd.fk_product = p.rowid";
	$sqlCommandesCli.= " AND c.fk_statut IN (1,2))";

	$sqlExpeditionsCli = "(SELECT ".$db->ifsql("SUM(ed.qty) IS NULL", "0", "SUM(ed.qty)")." as qty";
	$sqlExpeditionsCli.= " FROM ".MAIN_DB_PREFIX."expedition as e";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (ed.fk_expedition = e.rowid)";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet as cd ON (cd.rowid = ed.fk_origin_line)";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (c.rowid = cd.fk_commande)";
	$sqlExpeditionsCli.= " WHERE e.entity IN (".getEntity('expedition', 1).")";
	$sqlExpeditionsCli.= " AND cd.fk_product = p.rowid";
	$sqlExpeditionsCli.= " AND c.fk_statut IN (1,2))";

	$sqlCommandesFourn = "(SELECT ".$db->ifsql("SUM(cd.qty) IS NULL", "0", "SUM(cd.qty)")." as qty";
	$sqlCommandesFourn.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
	$sqlCommandesFourn.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sqlCommandesFourn.= " WHERE c.rowid = cd.fk_commande";
	$sqlCommandesFourn.= " AND c.entity IN (".getEntity('commande_fournisseur', 1).")";
	$sqlCommandesFourn.= " AND cd.fk_product = p.rowid";
	$sqlCommandesFourn.= " AND c.fk_statut IN (3,4))";

	$sqlReceptionFourn = "(SELECT ".$db->ifsql("SUM(fd.qty) IS NULL", "0", "SUM(fd.qty)")." as qty";
	$sqlReceptionFourn.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sqlReceptionFourn.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as fd ON (fd.fk_commande = cf.rowid)";
	$sqlReceptionFourn.= " WHERE cf.entity IN (".getEntity('commande_fournisseur', 1).")";
	$sqlReceptionFourn.= " AND fd.fk_product = p.rowid";
	$sqlReceptionFourn.= " AND cf.fk_statut IN (3,4))";

	$sql.= ' HAVING ((('.$db->ifsql("p.desiredstock IS NULL", "0", "p.desiredstock").' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.')))';
	$sql.= ' OR (p.seuil_stock_alerte >= 0 AND (p.seuil_stock_alerte > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.'))))';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql.= ' AND (p.seuil_stock_alerte > 0 AND (p.seuil_stock_alerte > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
		$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.')))';
		$alertchecked = 'checked';
	}
} else {
	$sql.= ' HAVING ((p.desiredstock > 0 AND (p.desiredstock > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
	$sql.= ' OR (p.seuil_stock_alerte > 0 AND (p.seuil_stock_alerte > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").'))))';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql.= ' AND (p.seuil_stock_alerte > 0 AND (p.seuil_stock_alerte > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
		$alertchecked = 'checked';
	}
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if (empty($resql))
{
    dol_print_error($db);
    exit;
}
//print $sql;

$num = $db->num_rows($resql);
$i = 0;

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
$helpurl .= 'ES:M&oacute;dulo_Stocks';

llxHeader('', $title, $helpurl, '');

$head = array();
$head[0][0] = DOL_URL_ROOT.'/product/stock/replenish.php';
$head[0][1] = $title;
$head[0][2] = 'replenish';
$head[1][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
$head[1][1] = $langs->trans("ReplenishmentOrders");
$head[1][2] = 'replenishorders';



print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">'.
	'<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">'.
	'<input type="hidden" name="sortfield" value="' . $sortfield . '">'.
	'<input type="hidden" name="sortorder" value="' . $sortorder . '">'.
	'<input type="hidden" name="type" value="' . $type . '">'.
	'<input type="hidden" name="linecount" value="' . $num . '">'.
	'<input type="hidden" name="action" value="order">'.
	'<input type="hidden" name="mode" value="' . $mode . '">';

dol_fiche_head($head, 'replenish', $langs->trans('Replenishment'), 0, 'stock');

print $langs->trans("ReplenishmentStatusDesc").'<br>'."\n";
if ($usevirtualstock == 1)
{
	print $langs->trans("CurentSelectionMode").': ';
	print $langs->trans("CurentlyUsingVirtualStock").' - ';
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=physical">'.$langs->trans("UsePhysicalStock").'</a><br>';
}
if ($usevirtualstock == 0)
{
	print $langs->trans("CurentSelectionMode").': ';
	print $langs->trans("CurentlyUsingPhysicalStock").' - ';
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=virtual">'.$langs->trans("UseVirtualStock").'</a><br>';
}
print '<br>'."\n";

if ($sref || $snom || $sall || $salert || GETPOST('search', 'alpha')) {
	$filters = '&sref=' . $sref . '&snom=' . $snom;
	$filters .= '&sall=' . $sall;
	$filters .= '&salert=' . $salert;
	$filters .= '&mode=' . $mode;
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num
	);
} else {
	$filters = '&sref=' . $sref . '&snom=' . $snom;
	$filters .= '&fourn_id=' . $fourn_id;
	$filters .= (isset($type)?'&type=' . $type:'');
	$filters .=  '&=' . $salert;
	$filters .= '&mode=' . $mode;
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num
	);
}

print '<table class="liste" width="100%">';

$param = (isset($type)? '&type=' . $type : '');
$param .= '&fourn_id=' . $fourn_id . '&snom='. $snom . '&salert=' . $salert;
$param .= '&sref=' . $sref;
$param .= '&mode=' . $mode;

$stocklabel = $langs->trans('Stock');
if ($usevirtualstock == 1) $stocklabel = $langs->trans('VirtualStock');
if ($usevirtualstock == 0) $stocklabel = $langs->trans('PhysicalStock');


// Lines of title
print '<tr class="liste_titre"><td><input type="checkbox" onClick="toggle(this)" /></td>';

print_liste_field_titre($langs->trans('Ref'), $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Label'), $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
if (!empty($conf->service->enabled) && $type == 1) print_liste_field_titre($langs->trans('Duration'), $_SERVER["PHP_SELF"], 'p.duration', $param, '', 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('DesiredStock'), $_SERVER["PHP_SELF"], 'p.desiredstock', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('StockLimitShort'), $_SERVER["PHP_SELF"], 'p.seuil_stock_alerte', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], 'stock_physique', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Ordered'), $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('StockToBuy'), $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Supplier'), $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print "</tr>\n";

// Lignes des champs de filtre
print '<tr class="liste_titre">'.
'<td class="liste_titre">&nbsp;</td>'.
'<td class="liste_titre"><input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'"></td>'.
'<td class="liste_titre"><input class="flat" type="text" name="snom" size="8" value="'.dol_escape_htmltag($snom).'"></td>';
if (!empty($conf->service->enabled) && $type == 1) print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>'.
	'<td class="liste_titre" align="right">&nbsp;</td>'.
	'<td class="liste_titre" align="right">' . $langs->trans('AlertOnly') . '&nbsp;<input type="checkbox" id="salert" name="salert" ' . (!empty($alertchecked)?$alertchecked:'') . '></td>'.
	'<td class="liste_titre" align="right">&nbsp;</td>'.
	'<td class="liste_titre">&nbsp;</td>'.
	'<td class="liste_titre" align="right">'.
	'<input class="liste_titre" name="button_search" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">'.
	'<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">'.
	'</td>';
print '</tr>';

$prod = new Product($db);

$var = True;
while ($i < ($limit ? min($num, $limit) : $num))
{
	$objp = $db->fetch_object($resql);

	if (! empty($conf->global->STOCK_SUPPORTS_SERVICES) || $objp->fk_product_type == 0)
	{
		$prod->fetch($objp->rowid);
		$prod->load_stock();

		// Multilangs
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql = 'SELECT label';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lang';
			$sql .= ' WHERE fk_product = ' . $objp->rowid;
			$sql .= ' AND lang = "' . $langs->getDefaultLang() . '"';
			$sql .= ' LIMIT 1';

			$resqlm = $db->query($sql);
			if ($resqlm)
			{
				$objtp = $db->fetch_object($resqlm);
				if (!empty($objtp->label)) $objp->label = $objtp->label;
			}
		}
		$var =! $var;

		if ($usevirtualstock)
		{
			// If option to increase/decrease is not on an object validation, virtual stock may differs from physical stock.
			$stock = $prod->stock_theorique;
		}
		else
		{
			$stock = $prod->stock_reel;
		}

		// Force call prod->load_stats_xxx to choose status to count (otherwise it is loaded by load_stock function)
		$result=$prod->load_stats_commande_fournisseur(0,'1,2,3,4');
		$result=$prod->load_stats_reception(0,'4');

		//print $prod->stats_commande_fournisseur['qty'].'<br>'."\n";
		//print $prod->stats_reception['qty'];
		$ordered = $prod->stats_commande_fournisseur['qty'] - $prod->stats_reception['qty'];

		$warning='';
		if ($objp->alertstock && ($stock < $objp->alertstock))
		{
			$warning = img_warning($langs->trans('StockTooLow')) . ' ';
		}

		//depending on conf, use either physical stock or
		//virtual stock to compute the stock to buy value
		$stocktobuy = max(max($objp->desiredstock, $objp->alertstock) - $stock - $ordered, 0);
		$disabled = '';
		if ($ordered > 0)
		{
			$stockforcompare = $usevirtualstock ? $stock : $stock + $ordered;
			if ($stockforcompare >= $objp->desiredstock)
			{
				$picto = img_picto('', './img/yes', '', 1);
				$disabled = 'disabled';
			}
			else {
				$picto = img_picto('', './img/no', '', 1);
			}
		} else {
			//$picto = img_help('',$langs->trans("NoPendingReceptionOnSupplierOrder"));
			$picto = img_picto($langs->trans("NoPendingReceptionOnSupplierOrder"), './img/no', '', 1);
		}

		print '<tr '.$bc[$var].'>';

		// Select field
		//print '<td><input type="checkbox" class="check" name="' . $i . '"' . $disabled . '></td>';
		print '<td><input type="checkbox" class="check" name="'.$i.'"></td>';

		print '<td class="nowrap">'.$prod->getNomUrl(1, '', 16).'</td>';

		print '<td>' . $objp->label . '<input type="hidden" name="desc' . $i . '" value="' . $objp->label . '" ></td>';

		if (!empty($conf->service->enabled) && $type == 1)
		{
			if (preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
				$duration =  $regs[1] . ' ' . $langs->trans('DurationYear');
			} elseif (preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
				$duration =  $regs[1] . ' ' . $langs->trans('DurationMonth');
			} elseif (preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
				$duration =  $regs[1] . ' ' . $langs->trans('DurationDay');
			} else {
				$duration = $objp->duration;
			}
			print '<td align="center">'.$duration.'</td>';
		}

		// Desired stock
		print '<td align="right">' . $objp->desiredstock . '</td>';

		// Limit stock for alerr
		print '<td align="right">' . $objp->alertstock . '</td>';

		// Current stock
		print '<td align="right">'. $warning . $stock. '</td>';

		// Already ordered
		print '<td align="right"><a href="replenishorders.php?sproduct=' . $prod->id . '">'. $ordered . '</a> ' . $picto. '</td>';

		// To order
		//print '<td align="right"><input type="text" name="tobuy'.$i.'" value="'.$stocktobuy.'" '.$disabled.'></td>';
		print '<td align="right"><input type="text" size="4" name="tobuy'.$i.'" value="'.$stocktobuy.'"></td>';

		// Supplier
		print '<td align="right">'.	$form->select_product_fourn_price($prod->id, 'fourn'.$i, 1).'</td>';

		print '</tr>';
	}
	$i++;
}
print '</table>';


if ($num > $conf->liste_limit)
{
	if ($sref || $snom || $sall || $salert || GETPOST('search', 'alpha'))
	{
		$filters = '&sref=' . $sref . '&snom=' . $snom;
		$filters .= '&sall=' . $sall;
		$filters .= '&salert=' . $salert;
		$filters .= '&mode=' . $mode;
		print_barre_liste('', $page, 'replenish.php', $filters, $sortfield, $sortorder, '', $num, 0, '');
	}
	else
	{
		$filters = '&sref=' . $sref . '&snom=' . $snom;
		$filters .= '&fourn_id=' . $fourn_id;
		$filters .= (isset($type)? '&type=' . $type : '');
		$filters .= '&salert=' . $salert;
		$filters .= '&mode=' . $mode;
		print_barre_liste('', $page, 'replenish.php', $filters, $sortfield, $sortorder, '', $num, 0, '');
	}
}

$db->free($resql);

dol_fiche_end();


$value=$langs->trans("CreateOrders");
print '<div class="center"><input class="button" type="submit" name="valid" value="'.$value.'"></div>';


print '</form>';


// TODO Replace this with jquery
print '
<script type="text/javascript">
function toggle(source)
{
	checkboxes = document.getElementsByClassName("check");
	for (var i=0; i < checkboxes.length;i++) {
		if (!checkboxes[i].disabled) {
			checkboxes[i].checked = source.checked;
		}
	}
}
</script>';


llxFooter();

$db->close();
