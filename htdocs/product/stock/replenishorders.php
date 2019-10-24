<?php
/*
 * Copyright (C) 2013	Cédric Salvador	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2014	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2019	Juanjo Menent   <jmenent@2byte.es>
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
require_once DOL_DOCUMENT_ROOT.'/product/stock/lib/replenishment.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');

$sall = GETPOST('search_all', 'alphanohtml');
$sref = GETPOST('search_ref', 'alpha');
$snom = GETPOST('search_nom', 'alpha');
$suser = GETPOST('search_user', 'alpha');
$sttc = GETPOST('search_ttc', 'alpha');
$page = GETPOST('page', 'int');
$sproduct = GETPOST('sproduct', 'int');
$search_dateyear = GETPOST('search_dateyear', 'int');
$search_datemonth = GETPOST('search_datemonth', 'int');
$search_dateday = GETPOST('search_dateday', 'int');
$search_date = dol_mktime(0, 0, 0, $search_datemonth, $search_dateday, $search_dateyear);

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
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

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
{
    $sall="";
    $sref="";
    $snom="";
    $suser="";
    $sttc="";
    $search_date='';
    $search_datemonth='';
    $search_dateday='';
    $search_dateyear='';
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

dol_fiche_head($head, 'replenishorders', '', -1, '');

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

if ($search_datemonth > 0)
{
	if ($search_dateyear > 0 && empty($search_dateday))
		$sql.= " AND cf.date_creation BETWEEN '".$db->idate(dol_get_first_day($search_dateyear,$search_datemonth,false))."' AND '".$db->idate(dol_get_last_day($search_dateyear,$search_datemonth,false))."'";
		else if ($search_dateyear > 0 && ! empty($search_dateday))
			$sql.= " AND cf.date_creation BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_datemonth, $search_dateday, $search_dateyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_datemonth, $search_dateday, $search_dateyear))."'";
			else
				$sql.= " AND date_format(cf.date_creation, '%m') = '".$search_datemonth."'";
}
else if ($search_dateyear > 0)
{
	$sql.= " AND cf.date_creation BETWEEN '".$db->idate(dol_get_first_day($search_dateyear,1,false))."' AND '".$db->idate(dol_get_last_day($search_dateyear,12,false))."'";
}
if ($sall) $sql .= natural_search(array('cf.ref','cf.note'), $sall);
if (!empty($socid)) $sql .= ' AND s.rowid = ' . $socid;
if (GETPOST('statut', 'int')) {
    $sql .= ' AND fk_statut = ' . GETPOST('statut', 'int');
}
$sql .= ' GROUP BY cf.rowid, cf.ref, cf.date_creation, cf.fk_statut';
$sql .= ', cf.total_ttc, cf.fk_user_author, u.login, s.rowid, s.nom';
$sql .= $db->order($sortfield, $sortorder);
if (! $sproduct) {
	$sql .= $db->plimit($limit+1, $offset);
}

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

	print $langs->trans("ReplenishmentOrdersDesc").'<br><br>';

    print_barre_liste('', $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, 0, '');

    $param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
    if ($sref) $param.='&search_ref='.urlencode($sref);
    if ($snom) $param.='&search_nom='.urlencode($snom);
    if ($suser) $param.='&search_user='.urlencode($suser);
    if ($sttc) $param.='&search_ttc='.urlencode($sttc);
    if ($search_dateyear) $param.='&search_dateyear='.urlencode($search_dateyear);
    if ($search_datemonth) $param.='&search_datemonth='.urlencode($search_datemonth);
    if ($search_dateday) $param.='&search_dateday='.urlencode($search_dateday);
    if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);


    print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';

    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre_filter">';
    print '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_ref" value="' . dol_escape_htmltag($sref) . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_nom" value="' . dol_escape_htmltag($snom) . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_user" value="' . dol_escape_htmltag($suser) . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         '<input type="text" class="flat" name="search_ttc" value="' . dol_escape_htmltag($sttc) . '">'.
         '</td>'.
         '<td class="liste_titre">'.
         $form->select_date($search_date, 'search_date', 0, 0, 1, '', 1, 0, 1, 0, '').
         '</td>'.
         '<td class="liste_titre" align="right">';
         $searchpicto=$form->showFilterAndCheckAddButtons(0);
         print $searchpicto;
         '</td>'.
         '</tr>';

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
             $sortorder
             );
         print_liste_field_titre(
             'OrderCreation',
             $_SERVER['PHP_SELF'],
             'cf.date_creation',
             '',
             $param,
             '',
             $sortfield,
             $sortorder
             );
         print_liste_field_titre(
             'Status',
             $_SERVER['PHP_SELF'],
             'cf.fk_statut',
             '',
             $param,
             'align="right"',
             $sortfield,
             $sortorder
             );
         print '</tr>';

    $userstatic = new User($db);

	while ($i < min($num,$sproduct?$num:$conf->liste_limit))
    {
        $obj = $db->fetch_object($resql);

        $showline = dolDispatchToDo($obj->rowid) && (!$sproduct || in_array($sproduct, getProducts($obj->rowid)));

        if ($showline)
        {
            $href = DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $obj->rowid;
            print '<tr>'.
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
