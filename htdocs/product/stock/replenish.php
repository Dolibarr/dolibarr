<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2018	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		ATM Consulting		<support@atm-consulting.fr>
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
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once './lib/replenishment.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Security check
if ($user->societe_id) {
    $socid = $user->societe_id;
}
$result=restrictedArea($user,'produit|service');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('stockreplenishlist'));

//checks if a product has been ordered

$action = GETPOST('action','alpha');
$sref = GETPOST('sref', 'alpha');
$snom = GETPOST('snom', 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$type = GETPOST('type','int');
$tobuy = GETPOST('tobuy', 'int');
$salert = GETPOST('salert', 'alpha');
$mode = GETPOST('mode','alpha');
$draftorder = GETPOST('draftorder','alpha');


$fourn_id = GETPOST('fourn_id','int');
$fk_supplier = GETPOST('fk_supplier','int');
$fk_entrepot = GETPOST('fk_entrepot','int');
$texte = '';

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page ;

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical=0;
if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
|| ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
|| ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)
|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION)
|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))
{
    $virtualdiffersfromphysical=1;		// According to increase/decrease stock options, virtual and physical stock may differs.
}
$usevirtualstock=0;
if ($mode == 'virtual') $usevirtualstock=1;

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
 */

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha') || isset($_POST['valid'])) // Both test are required to be compatible with all browsers
{
    $sref = '';
    $snom = '';
    $sal = '';
    $salert = '';
	$draftorder='';
}
if($draftorder == 'on') $draftchecked = "checked";

// Create orders
if ($action == 'order' && isset($_POST['valid']))
{
    $linecount = GETPOST('linecount', 'int');
    $box = 0;
	$errorQty = 0;
    unset($_POST['linecount']);
    if ($linecount > 0)
    {
    	$db->begin();

        $suppliers = array();
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
		$productsupplier = new ProductFournisseur($db);
        for ($i = 0; $i < $linecount; $i++)
        {
            if (GETPOST('choose' . $i, 'alpha') === 'on' && GETPOST('fourn' . $i, 'int') > 0)
            {
            	//one line
                $box = $i;
                $supplierpriceid = GETPOST('fourn'.$i, 'int');
                //get all the parameters needed to create a line
                $qty = GETPOST('tobuy'.$i, 'int');
				$idprod=$productsupplier->get_buyprice($supplierpriceid, $qty);
				$res=$productsupplier->fetch($idprod);
                if ($res && $idprod > 0)
                {
                	if ($qty)
                	{
	                    //might need some value checks
	                    $obj = $db->fetch_object($resql);
	                    $line = new CommandeFournisseurLigne($db);
	                    $line->qty = $qty;
	                    $line->fk_product = $idprod;

	                    //$product = new Product($db);
	                    //$product->fetch($obj->fk_product);
	                    if (! empty($conf->global->MAIN_MULTILANGS))
	                    {
	                        $productsupplier->getMultiLangs();
	                    }
	                    $line->desc = $productsupplier->description;
                        if (! empty($conf->global->MAIN_MULTILANGS))
                        {
                            // TODO Get desc in language of thirdparty
                        }

	                    $line->tva_tx = $productsupplier->vatrate_supplier;
	                    $line->subprice = $productsupplier->fourn_pu;
	                    $line->total_ht = $productsupplier->fourn_pu * $qty;
	                    $tva = $line->tva_tx / 100;
	                    $line->total_tva = $line->total_ht * $tva;
	                    $line->total_ttc = $line->total_ht + $line->total_tva;
						$line->remise_percent = $productsupplier->remise_percent;
	                    $line->ref_fourn = $productsupplier->ref_supplier;
						$line->type = $productsupplier->type;
						$line->fk_unit = $productsupplier->fk_unit;
	                    $suppliers[$productsupplier->fourn_socid]['lines'][] = $line;
                	}
                }
				elseif ($idprod == -1)
				{
					$errorQty++;
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
            $sql.= " AND entity IN (".getEntity('commande_fournisseur').")";
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
                        $line->type,
                        0,
						false,
						null,
						null,
						0,
						$line->fk_unit
                    );
                }
                if ($result < 0) {
                    $fail++;
                    $msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
                    $msg .= $order->error;
                    setEventMessages($msg, null, 'errors');
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
                    setEventMessages($msg, null, 'errors');
                }
                $i++;
            }
        }

		if($errorQty) setEventMessages($langs->trans('ErrorOrdersNotCreatedQtyTooLow'), null, 'warnings');

        if (! $fail && $id)
        {
        	$db->commit();

            setEventMessages($langs->trans('OrderCreated'), null, 'mesgs');
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
        setEventMessages($langs->trans('SelectProductWithNotNullQty'), null, 'warnings');
    }
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$title = $langs->trans('Status');

if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sqldesiredtock=$db->ifsql("pse.desiredstock IS NULL", "p.desiredstock", "pse.desiredstock");
	$sqlalertstock=$db->ifsql("pse.seuil_stock_alerte IS NULL", "p.seuil_stock_alerte", "pse.seuil_stock_alerte");
} else {
	$sqldesiredtock='p.desiredstock';
	$sqlalertstock='p.seuil_stock_alerte';
}


$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price,';
$sql.= ' p.price_ttc, p.price_base_type,p.fk_product_type,';
$sql.= ' p.tms as datem, p.duration, p.tobuy';
$sql.= ' ,p.desiredstock,p.seuil_stock_alerte';
if(!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql.= ', pse.desiredstock' ;
	$sql.= ', pse.seuil_stock_alerte' ;
}
$sql.= ' ,'.$sqldesiredtock.' as desiredstock, '.$sqlalertstock.' as alertstock,';

$sql.= ' SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").') as stock_physique';
$sql.= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
$sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s';
$sql.= ' ON (p.rowid = s.fk_product AND s.fk_entrepot IN (SELECT ent.rowid FROM '.MAIN_DB_PREFIX.'entrepot AS ent WHERE ent.entity IN('.getEntity('stock').')))';
if($fk_supplier > 0) {
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price pfp ON (pfp.fk_product = p.rowid AND pfp.fk_soc = '.$fk_supplier.')';
}
if(!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_warehouse_properties AS pse ON (p.rowid = pse.fk_product AND pse.fk_entrepot = '.$fk_entrepot.')';
}
$sql.= ' WHERE p.entity IN (' . getEntity('product') . ')';
if ($sall) $sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
    if ($type == 1) {
        $sql .= ' AND p.fk_product_type = 1';
    } else {
        $sql .= ' AND p.fk_product_type <> 1';
    }
}
if ($sref) $sql.=natural_search('p.ref', $sref);
if ($snom) $sql.=natural_search('p.label', $snom);
$sql.= ' AND p.tobuy = 1';
if (!empty($canvas)) $sql .= ' AND p.canvas = "' . $db->escape($canvas) . '"';
$sql.= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price';
$sql.= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql.= ', p.duration, p.tobuy';
$sql.= ', p.desiredstock';
$sql.= ', p.seuil_stock_alerte';
if(!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql.= ', pse.desiredstock' ;
	$sql.= ', pse.seuil_stock_alerte' ;
}
$sql.= ', s.fk_product';

if ($usevirtualstock)
{
	$sqlCommandesCli = "(SELECT ".$db->ifsql("SUM(cd.qty) IS NULL", "0", "SUM(cd.qty)")." as qty";
	$sqlCommandesCli.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
	$sqlCommandesCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (c.rowid = cd.fk_commande)";
	$sqlCommandesCli.= " WHERE c.entity IN (".getEntity('commande').")";
	$sqlCommandesCli.= " AND cd.fk_product = p.rowid";
	$sqlCommandesCli.= " AND c.fk_statut IN (1,2))";

	$sqlExpeditionsCli = "(SELECT ".$db->ifsql("SUM(ed.qty) IS NULL", "0", "SUM(ed.qty)")." as qty";
	$sqlExpeditionsCli.= " FROM ".MAIN_DB_PREFIX."expedition as e";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON (ed.fk_expedition = e.rowid)";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet as cd ON (cd.rowid = ed.fk_origin_line)";
	$sqlExpeditionsCli.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON (c.rowid = cd.fk_commande)";
	$sqlExpeditionsCli.= " WHERE e.entity IN (".getEntity('expedition').")";
	$sqlExpeditionsCli.= " AND cd.fk_product = p.rowid";
	$sqlExpeditionsCli.= " AND c.fk_statut IN (1,2))";

	$sqlCommandesFourn = "(SELECT ".$db->ifsql("SUM(cd.qty) IS NULL", "0", "SUM(cd.qty)")." as qty";
	$sqlCommandesFourn.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
	$sqlCommandesFourn.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sqlCommandesFourn.= " WHERE c.rowid = cd.fk_commande";
	$sqlCommandesFourn.= " AND c.entity IN (".getEntity('supplier_order').")";
	$sqlCommandesFourn.= " AND cd.fk_product = p.rowid";
	$sqlCommandesFourn.= " AND c.fk_statut IN (3,4))";

	$sqlReceptionFourn = "(SELECT ".$db->ifsql("SUM(fd.qty) IS NULL", "0", "SUM(fd.qty)")." as qty";
	$sqlReceptionFourn.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sqlReceptionFourn.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as fd ON (fd.fk_commande = cf.rowid)";
	$sqlReceptionFourn.= " WHERE cf.entity IN (".getEntity('supplier_order').")";
	$sqlReceptionFourn.= " AND fd.fk_product = p.rowid";
	$sqlReceptionFourn.= " AND cf.fk_statut IN (3,4))";

	$sql.= ' HAVING ((('.$db->ifsql($sqldesiredtock." IS NULL", "0", $sqldesiredtock).' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.')))';
	$sql.= ' OR ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
	$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.'))))';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql.= ' AND ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')';
		$sql.= ' - ('.$sqlCommandesCli.' - '.$sqlExpeditionsCli.') + ('.$sqlCommandesFourn.' - '.$sqlReceptionFourn.')))';
		$alertchecked = 'checked';
	}
} else {
	$sql.= ' HAVING (('.$sqldesiredtock.' >= 0 AND ('.$sqldesiredtock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
	$sql.= ' OR ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").'))))';

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql.= ' AND ('.$sqlalertstock.' >= 0 AND ('.$sqlalertstock.' > SUM('.$db->ifsql("s.reel IS NULL", "0", "s.reel").')))';
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


print load_fiche_titre($langs->trans('Replenishment'), '', 'title_generic.png');

dol_fiche_head($head, 'replenish', '', -1, '');

print $langs->trans("ReplenishmentStatusDesc").'<br>'."\n";
if ($usevirtualstock == 1)
{
	print $langs->trans("CurentSelectionMode").': ';
	print $langs->trans("CurentlyUsingVirtualStock").' - ';
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=physical&fk_supplier='.$fk_supplier.'&fk_entrepot='.$fk_entrepot.'">'.$langs->trans("UsePhysicalStock").'</a><br>';
}
if ($usevirtualstock == 0)
{
	print $langs->trans("CurentSelectionMode").': ';
	print $langs->trans("CurentlyUsingPhysicalStock").' - ';
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=virtual&fk_supplier='.$fk_supplier.'&fk_entrepot='.$fk_entrepot.'">'.$langs->trans("UseVirtualStock").'</a><br>';
}
print '<br>'."\n";

print '<form name="formFilterWarehouse" method="GET" action="">';
print '<input type="hidden" name="action" value="filter">';
print '<input type="hidden" name="sref" value="'.$sref.'">';
print '<input type="hidden" name="snom" value="'.$snom.'">';
print '<input type="hidden" name="salert" value="'.$salert.'">';
print '<input type="hidden" name="draftorder" value="'.$draftorder.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE))
{
	print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
	print $langs->trans('Warehouse').' '.$formproduct->selectWarehouses($fk_entrepot, 'fk_entrepot', '', 1);
	print '</div>';
}
print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
print $langs->trans('Supplier').' '.$form->select_company($fk_supplier, 'fk_supplier', 'fournisseur=1', 1);
print '</div>';
print '<div class="inline-block valignmiddle">';
print '<input class="button" type="submit" name="valid" value="'.$langs->trans('ToFilter').'">';
print '</div>';
print '</form>';

if ($sref || $snom || $sall || $salert || $draftorder || GETPOST('search', 'alpha')) {
	$filters = '&sref=' . $sref . '&snom=' . $snom;
	$filters .= '&sall=' . $sall;
	$filters .= '&salert=' . $salert;
	$filters .= '&draftorder=' . $draftorder;
	$filters .= '&mode=' . $mode;
	$filters .= '&fk_supplier=' . $fk_supplier;
	$filters .= '&fk_entrepot=' . $fk_entrepot;
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
	$filters .= '&draftorder=' . $draftorder;
	$filters .= '&mode=' . $mode;
	$filters .= '&fk_supplier=' . $fk_supplier;
	$filters .= '&fk_entrepot=' . $fk_entrepot;
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

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="liste" width="100%">';

$param = (isset($type)? '&type=' . $type : '');
$param .= '&fourn_id=' . $fourn_id . '&snom='. $snom . '&salert=' . $salert . '&draftorder='.$draftorder;
$param .= '&sref=' . $sref;
$param .= '&mode=' . $mode;
$param .= '&fk_supplier=' . $fk_supplier;
$param .= '&fk_entrepot=' . $fk_entrepot;

$stocklabel = $langs->trans('Stock');
if ($usevirtualstock == 1) $stocklabel = 'VirtualStock';
if ($usevirtualstock == 0) $stocklabel = 'PhysicalStock';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">'.
	'<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">'.
	'<input type="hidden" name="fk_supplier" value="' . $fk_supplier . '">'.
	'<input type="hidden" name="fk_entrepot" value="' .$fk_entrepot . '">'.
	'<input type="hidden" name="sortfield" value="' . $sortfield . '">'.
	'<input type="hidden" name="sortorder" value="' . $sortorder . '">'.
	'<input type="hidden" name="type" value="' . $type . '">'.
	'<input type="hidden" name="linecount" value="' . $num . '">'.
	'<input type="hidden" name="action" value="order">'.
	'<input type="hidden" name="mode" value="' . $mode . '">';

// Lignes des champs de filtre
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre"><input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'"></td>';
print '<td class="liste_titre"><input class="flat" type="text" name="snom" size="8" value="'.dol_escape_htmltag($snom).'"></td>';
if (!empty($conf->service->enabled) && $type == 1) print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre" align="right">&nbsp;</td>';
print '<td class="liste_titre" align="right">' . $langs->trans('AlertOnly') . '&nbsp;<input type="checkbox" id="salert" name="salert" ' . (!empty($alertchecked)?$alertchecked:'') . '></td>';
print '<td class="liste_titre" align="right">' . $langs->trans('Draft') . '&nbsp;<input type="checkbox" id="draftorder" name="draftorder" ' . (!empty($draftchecked)?$draftchecked:'') . '></td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

// Lines of title
print '<tr class="liste_titre">';
print_liste_field_titre('<input type="checkbox" onClick="toggle(this)" />', $_SERVER["PHP_SELF"], '');
print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
if (!empty($conf->service->enabled) && $type == 1) print_liste_field_titre('Duration', $_SERVER["PHP_SELF"], 'p.duration', $param, '', 'align="center"', $sortfield, $sortorder);
print_liste_field_titre('DesiredStock', $_SERVER["PHP_SELF"], 'p.desiredstock', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('StockLimitShort', $_SERVER["PHP_SELF"], 'p.seuil_stock_alerte', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], 'stock_physique', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('Ordered', $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('StockToBuy', $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print_liste_field_titre('SupplierRef', $_SERVER["PHP_SELF"], '', $param, '', 'align="right"', $sortfield, $sortorder);
print "</tr>\n";

while ($i < ($limit ? min($num, $limit) : $num))
{
	$objp = $db->fetch_object($resql);

	if (! empty($conf->global->STOCK_SUPPORTS_SERVICES) || $objp->fk_product_type == 0)
	{
		$prod->fetch($objp->rowid);
		$prod->load_stock('warehouseopen, warehouseinternal');

		// Multilangs
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$sql = 'SELECT label,description';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lang';
			$sql .= ' WHERE fk_product = ' . $objp->rowid;
			$sql .= ' AND lang = "' . $langs->getDefaultLang() . '"';
			$sql .= ' LIMIT 1';

			$resqlm = $db->query($sql);
			if ($resqlm)
			{
				$objtp = $db->fetch_object($resqlm);
				if (!empty($objtp->description)) $objp->description = $objtp->description;
				if (!empty($objtp->label)) $objp->label = $objtp->label;
			}
		}

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
		if(isset($draftchecked)){
			$result=$prod->load_stats_commande_fournisseur(0,'0,1,2,3,4');
		}else {
			$result=$prod->load_stats_commande_fournisseur(0,'1,2,3,4');
		}

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

		print '<tr class="oddeven">';

		// Select field
		//print '<td><input type="checkbox" class="check" name="' . $i . '"' . $disabled . '></td>';
		print '<td><input type="checkbox" class="check" name="choose'.$i.'"></td>';

		print '<td class="nowrap">'.$prod->getNomUrl(1, '').'</td>';

		print '<td>'.$objp->label ;
		print '<input type="hidden" name="desc' . $i . '" value="' . dol_escape_htmltag($objp->description) . '">';  // TODO Remove this and make a fetch to get description when creating order instead of a GETPOST
		print '</td>';

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
		print '<td align="right">'.	$form->select_product_fourn_price($prod->id, 'fourn'.$i, $fk_supplier).'</td>';

		print '</tr>';
	}
	$i++;
}

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';

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
