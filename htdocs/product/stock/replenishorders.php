<?php
/*
 * Copyright (C) 2013	Cédric Salvador	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014	Regis Houssin	<regis.houssin@capnetworks.com>
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
 *  \file       htdocs/product/stock/replenishorders.php
 *  \ingroup    stock
 *  \brief      Page to list replenishment orders
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once './lib/replenishment.lib.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');

$sref = GETPOST('search_ref', 'alpha');
$snom = GETPOST('search_nom', 'alpha');
$suser = GETPOST('search_user', 'alpha');
$sttc = GETPOST('search_ttc', 'alpha');
$sall = GETPOST('search_all', 'alpha');
$sdate = GETPOST('search_date', 'alpha');
$page = GETPOST('page', 'int');
$sproduct = GETPOST('sproduct', 'int');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (!$sortorder) $sortorder = 'DESC';
if (!$sortfield) $sortfield = 'cf.date_creation';
$page = GETPOST("page");
if ($page < 0) $page = 0;
$offset = $limit * $page;



/*
 * Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $sall="";
    $sref="";
    $snom="";
    $suser="";
    $sttc="";
    $sdate='';
    $sproduct=0;
}



/*
 * View
 */

$form = new Form($db);

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
$texte = $langs->trans('ReplenishmentOrders');

llxHeader('', $texte, $helpurl, '');

print load_fiche_titre($langs->trans('Replenishment'), '', 'title_generic.png');

$head = array();
$head[0][0] = DOL_URL_ROOT.'/product/stock/replenish.php';
$head[0][1] = $langs->trans('Status');
$head[0][2] = 'replenish';
$head[1][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
$head[1][1] = $texte;
$head[1][2] = 'replenishorders';

dol_fiche_head($head, 'replenishorders', '', 0, '');

$commandestatic = new CommandeFournisseur($db);

$sql = 'SELECT s.rowid as socid, s.nom as name, cf.date_creation as dc,';
$sql.= ' cf.rowid, cf.ref, cf.fk_statut, cf.total_ttc, cf.fk_user_author,';
$sql.= ' u.login';
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'commande_fournisseur as cf';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON cf.fk_user_author = u.rowid';
if (!$user->rights->societe->client->voir && !$socid) {
    $sql.= ', ' . MAIN_DB_PREFIX . 'societe_commerciaux as sc';
}
$sql.= ' WHERE cf.fk_soc = s.rowid ';
$sql.= ' AND cf.entity = ' . $conf->entity;
if ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) {
    $sql .= ' AND cf.fk_statut < 3';
} elseif ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) {
    $sql .= ' AND cf.fk_statut < 6';	// We want also status 5, we will keep them visible if dispatching is not yet finished (tested with function dolDispatchToDo).
} else {
    $sql .= ' AND cf.fk_statut < 5';
}
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= ' AND s.rowid = sc.fk_soc AND sc.fk_user = ' . $user->id;
}
if ($sref) $sql .= natural_search('cf.ref', $sref);
if ($snom) $sql .= natural_search('s.nom', $snom);
if ($suser) $sql .= natural_search('u.login', $suser);
if ($sttc) $sql .= natural_search('cf.total_ttc', $sttc, 1);
if ($sdate)
{
    if (GETPOST('search_datemonth', 'int') && GETPOST('search_dateday', 'int') && GETPOST('search_dateyear', 'int'))
    {
	    $date = dol_mktime(0, 0, 0, GETPOST('search_datemonth', 'int'), GETPOST('search_dateday', 'int'), GETPOST('search_dateyear', 'int'));
    }
    else
    {
        $date = dol_stringtotime($sdate);
    }
    $sql .= " AND cf.date_creation = '" . $db->idate($date) . "'";
}
if ($sall) $sql .= natural_search(array('cf.ref','cf.note'), $sall);
if (!empty($socid)) $sql .= ' AND s.rowid = ' . $socid;
if (GETPOST('statut', 'int')) {
    $sql .= ' AND fk_statut = ' . GETPOST('statut', 'int');
}
$sql .= ' GROUP BY cf.rowid, cf.ref, cf.date_creation, cf.fk_statut';
$sql .= ', cf.total_ttc, cf.fk_user_author, u.login, s.rowid, s.nom';
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit+1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

	print $langs->trans("ReplenishmentOrdersDesc").'<br><br>';

    print_barre_liste('', $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, 0, '');

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';

    print '<table class="noborder" width="100%">'.
         '<tr class="liste_titre">';
    print_liste_field_titre(
    		$langs->trans('Ref'),
    		$_SERVER['PHP_SELF'],
    		'cf.ref',
    		'',
    		'',
    		'',
    		$sortfield,
    		$sortorder
    );
    print_liste_field_titre(
    		$langs->trans('Company'),
    		$_SERVER['PHP_SELF'],
    		's.nom',
    		'',
    		'',
    		'',
    		$sortfield,
    		$sortorder
    );
    print_liste_field_titre(
    		$langs->trans('Author'),
    		$_SERVER['PHP_SELF'],
    		'u.login',
    		'',
    		'',
    		'',
    		$sortfield,
    		$sortorder
    );
    print_liste_field_titre(
    		$langs->trans('AmountTTC'),
    		$_SERVER['PHP_SELF'],
    		'cf.total_ttc',
    		'',
    		'',
    		'',
    		$sortfield,
    		$sortorder
    );
    print_liste_field_titre(
    		$langs->trans('OrderCreation'),
    		$_SERVER['PHP_SELF'],
    		'cf.date_creation',
    		'',
    		'',
    		'',
    		$sortfield,
    		$sortorder
    );
    print_liste_field_titre(
    		$langs->trans('Status'),
    		$_SERVER['PHP_SELF'],
    		'cf.fk_statut',
    		'',
    		'',
    		'align="right"',
    		$sortfield,
    		$sortorder
    );
    print '</tr>'.

         '<tr class="liste_titre">'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_ref" value="' . $sref . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_nom" value="' . $snom . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_user" value="' . $suser . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_ttc" value="' . $sttc . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         $form->select_date('', 'search_date', 0, 0, 1, '', 1, 0, 1, 0, '').
         '</td>'.
         '<td class="liste_titre" align="right">';
         $searchpitco=$form->showFilterAndCheckAddButtons(0);
         print $searchpitco;
         '</td>'.
         '</tr>';

    $var = true;
    $userstatic = new User($db);

    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($resql);
        $var = !$var;

        $showline = dolDispatchToDo($obj->rowid) && (!$sproduct || in_array($sproduct, getProducts($obj->rowid)));

        if ($showline)
        {
            $href = DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $obj->rowid;
            print '<tr ' . $bc[$var] . '>'.
            // Ref
                 '<td>'.
                 '<a href="' . $href . '">'.
                 img_object($langs->trans('ShowOrder'), 'order') . ' ' . $obj->ref.
                 '</a></td>';

            // Company
            $href = DOL_URL_ROOT . '/fourn/card.php?socid=' . $obj->socid;
            print '<td>'.
                 '<a href="' . $href .'">'.
                 img_object($langs->trans('ShowCompany'), 'company'). ' '.
                 $obj->name . '</a></td>';

            // Author
            $userstatic->id = $obj->fk_user_author;
            $userstatic->login = $obj->login;
            if ($userstatic->id) {
                $txt = $userstatic->getLoginUrl(1);
            } else {
                $txt =  '&nbsp;';
            }
            print '<td>'.
                 $txt.
                 '</td>'.
            // Amount
                 '<td>'.
                 price($obj->total_ttc).
                 '</td>';

            // Date
            if ($obj->dc) {
                $date =  dol_print_date($db->jdate($obj->dc), 'dayhour');
            } else {
                $date =  '-';
            }
            print '<td>'.
                 $date.
                 '</td>'.
            // Statut
                 '<td align="right">'.
                 $commandestatic->LibStatut($obj->fk_statut, 5).
                 '</td>'.
                 '</tr>';
        }
        $i++;
    }
    print '</table>'.
         '</form>';

    $db->free($resql);

    dol_fiche_end();
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
