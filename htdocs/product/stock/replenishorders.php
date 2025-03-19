<?php
/*
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019	    Juanjo Menent           <jmenent@2byte.es>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/replenishorders.php
 *  \ingroup    stock
 *  \brief      Page to list replenishment orders
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/lib/replenishment.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'replenishorders'; // To manage different context of search

$sall = GETPOST('search_all', 'alphanohtml');
$sref = GETPOST('search_ref', 'alpha');
$snom = GETPOST('search_nom', 'alpha');
$suser = GETPOST('search_user', 'alpha');
$sttc = GETPOST('search_ttc', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$search_product = GETPOSTINT('search_product');
$search_dateyear = GETPOSTINT('search_dateyear');
$search_datemonth = GETPOSTINT('search_datemonth');
$search_dateday = GETPOSTINT('search_dateday');
$search_date = dol_mktime(0, 0, 0, $search_datemonth, $search_dateday, $search_dateyear);
$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (!$sortorder) {
	$sortorder = 'DESC';
}
if (!$sortfield) {
	$sortfield = 'cf.date_creation';
}
$page = GETPOSTINT('page') ? GETPOSTINT('page') : 0;
if ($page < 0) {
	$page = 0;
}
$offset = $limit * $page;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$sall = "";
	$sref = "";
	$snom = "";
	$suser = "";
	$sttc = "";
	$search_date = '';
	$search_datemonth = '';
	$search_dateday = '';
	$search_dateyear = '';
	$search_product = 0;
}



/*
 * View
 */

$form = new Form($db);

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
$texte = $langs->trans('ReplenishmentOrders');

llxHeader('', $texte, $helpurl, '', 0, 0, '', '', '', 'mod-product page-stock_replenishorders');

print load_fiche_titre($langs->trans('Replenishment'), '', 'stock');

$head = array();

$head[0][0] = DOL_URL_ROOT.'/product/stock/replenish.php';
$head[0][1] = $langs->trans('MissingStocks');
$head[0][2] = 'replenish';

$head[1][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
$head[1][1] = $texte;
$head[1][2] = 'replenishorders';

print dol_get_fiche_head($head, 'replenishorders', '', -1, '');

$commandestatic = new CommandeFournisseur($db);

$sql = 'SELECT s.rowid as socid, s.nom as name, cf.date_creation as dc,';
$sql .= ' cf.rowid, cf.ref, cf.fk_statut, cf.total_ttc, cf.fk_user_author,';
$sql .= ' u.login';
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'commande_fournisseur as cf';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON cf.fk_user_author = u.rowid';
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ', '.MAIN_DB_PREFIX.'societe_commerciaux as sc';
}
$sql .= ' WHERE cf.fk_soc = s.rowid ';
$sql .= ' AND cf.entity = '.$conf->entity;
if (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER')) {
	$sql .= ' AND cf.fk_statut < 3';
} elseif (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')) {
	$sql .= ' AND cf.fk_statut < 6'; // We want also status 5, we will keep them visible if dispatching is not yet finished (tested with function dolDispatchToDo).
} else {
	$sql .= ' AND cf.fk_statut < 5';
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ' AND s.rowid = sc.fk_soc AND sc.fk_user = '.((int) $user->id);
}
if ($sref) {
	$sql .= natural_search('cf.ref', $sref);
}
if ($snom) {
	$sql .= natural_search('s.nom', $snom);
}
if ($suser) {
	natural_search(array('u.lastname', 'u.firstname', 'u.login'), $suser);
}
if ($sttc) {
	$sql .= natural_search('cf.total_ttc', $sttc, 1);
}
$sql .= dolSqlDateFilter('cf.date_creation', $search_dateday, $search_datemonth, $search_dateyear);
if ($sall) {
	$sql .= natural_search(array('cf.ref', 'cf.note'), $sall);
}
if (!empty($socid)) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if (GETPOSTINT('statut')) {
	$sql .= ' AND fk_statut = '.GETPOSTINT('statut');
}
$sql .= ' GROUP BY cf.rowid, cf.ref, cf.date_creation, cf.fk_statut';
$sql .= ', cf.total_ttc, cf.fk_user_author, u.login, s.rowid, s.nom';
$sql .= $db->order($sortfield, $sortorder);
if (!$search_product) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ReplenishmentOrdersDesc").'</span><br class="hideonsmartphone">';

	print_barre_liste('', $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, 0, '');

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($sref) {
		$param .= '&search_ref='.urlencode($sref);
	}
	if ($snom) {
		$param .= '&search_nom='.urlencode($snom);
	}
	if ($suser) {
		$param .= '&search_user='.urlencode($suser);
	}
	if ($sttc) {
		$param .= '&search_ttc='.urlencode($sttc);
	}
	if ($search_dateyear) {
		$param .= '&search_dateyear='.urlencode((string) ($search_dateyear));
	}
	if ($search_datemonth) {
		$param .= '&search_datemonth='.urlencode((string) ($search_datemonth));
	}
	if ($search_dateday) {
		$param .= '&search_dateday='.urlencode((string) ($search_dateday));
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_ref" value="'.dol_escape_htmltag($sref).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_nom" value="'.dol_escape_htmltag($snom).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_user" value="'.dol_escape_htmltag($suser).'">';
	print '</td>';
	print '<td class="liste_titre right">';
	print '<input type="text" class="flat width75" name="search_ttc" value="'.dol_escape_htmltag($sttc).'">';
	print '</td>';
	print '<td class="liste_titre center">';
	print $form->selectDate($search_date, 'search_date', 0, 0, 1, '', 1, 0, 0, '');
	print '</td>';
	print '<td class="liste_titre right">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre(
		'Ref',
		$_SERVER['PHP_SELF'],
		'cf.ref',
		'',
		$param,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		'Company',
		$_SERVER['PHP_SELF'],
		's.nom',
		'',
		$param,
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		'Author',
		$_SERVER['PHP_SELF'],
		'u.login',
		'',
		'',
		'',
		$sortfield,
		$sortorder
	);
	print_liste_field_titre(
		'AmountTTC',
		$_SERVER['PHP_SELF'],
		'cf.total_ttc',
		'',
		$param,
		'',
		$sortfield,
		$sortorder,
		'right '
	);
	print_liste_field_titre(
		'OrderCreation',
		$_SERVER['PHP_SELF'],
		'cf.date_creation',
		'',
		$param,
		'',
		$sortfield,
		$sortorder,
		'center '
	);
	print_liste_field_titre(
		'Status',
		$_SERVER['PHP_SELF'],
		'cf.fk_statut',
		'',
		$param,
		'',
		$sortfield,
		$sortorder,
		'right '
	);
	print '</tr>';

	$userstatic = new User($db);

	while ($i < min($num, $search_product ? $num : $conf->liste_limit)) {
		$obj = $db->fetch_object($resql);

		$showline = dolDispatchToDo($obj->rowid) && (!$search_product || in_array($search_product, getProducts($obj->rowid)));

		if ($showline) {
			$href = DOL_URL_ROOT.'/fourn/commande/card.php?id='.$obj->rowid;

			print '<tr>';

			// Ref
			print '<td>';
			print '<a href="'.$href.'">'.img_object($langs->trans('ShowOrder'), 'order').' '.$obj->ref.'</a>';
			print '</td>';

			// Company
			$href = DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->socid;
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->name).'"><a href="'.$href.'">'.img_object($langs->trans('ShowCompany'), 'company').' '.$obj->name.'</a></td>';

			// Author
			$userstatic->id = $obj->fk_user_author;
			$userstatic->login = $obj->login;
			if ($userstatic->id) {
				$txt = $userstatic->getLoginUrl(1);
			} else {
				$txt = '&nbsp;';
			}
			print '<td>'.$txt.'</td>';

			// Amount
			print '<td class="right"><span class="amount">'.price($obj->total_ttc).'</span></td>';

			// Date
			if ($obj->dc) {
				$date = dol_print_date($db->jdate($obj->dc), 'dayhour', 'tzuserrel');
			} else {
				$date = '-';
			}
			print '<td class="center">'.$date.'</td>';

			// Statut
			print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, 5).'</td>';

			print '</tr>';
		}
		$i++;
	}
	print '</table>';
	print '</div>';

	print '</form>';

	$db->free($resql);

	print dol_get_fiche_end();
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
