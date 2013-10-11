<?php
/* Copyright (C) 2013   Laurent Destaileur	<ely@users.sourceforge.net>
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
 *  \file       htdocs/product/stock/massstockmove.php
 *  \ingroup    stock
 *  \brief      This page allows to select several products, then incoming warehouse and 
 *  			outgoing warehouse and create all stock movements for this.  
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
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

$action = GETPOST('action','alpha');
$sref = GETPOST('sref', 'alpha');
$snom = GETPOST('snom', 'alpha');
$sall = GETPOST('sall', 'alpha');
$type = GETPOST('type','int');
$tobuy = GETPOST('tobuy', 'int');
$salert = GETPOST('salert', 'alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}
$limit = $conf->liste_limit;
$offset = $limit * $page ;

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

if ($action == 'createmovement' && isset($_POST['valid']))
{




}


/*
 * View
 */

$form=new Form($db);
$prodstatic = new Product($db);
$warehousestatic = new Entrepot($db);

$title = $langs->trans('MassMovement');

llxHeader('', $title, $helpurl, '');

print_fiche_titre($langs->trans("MassStockMovement")).'<br><br>';

print $langs->trans("SelectProductInAndOutWareHouse").'<br>'; 


// Form to add a line
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' .$_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="addline">';

print '<table class="liste" width="100%">';

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Product'),$_SERVER["PHP_SELF"],'',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Qty'),$_SERVER["PHP_SELF"],'',$param,'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseSource'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseTarget'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>';

print '<tr>';
// Product
print '<td>';
$filtertype=0;
if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
print $form->select_produits('','productid',$filtertype);
print '</td>';
// Qty
print '<td align="center"><input type="input" size="4" class="flat"></td>';
// In warehouse
print '<td>';
print '</td>';
// Out warehouse
print '<td>';
print '</td>';
// Button to add line
print '<td align="right"><input type="submit" class="button" name="addline" value="'.dol_escape_htmltag($langs->trans("Add")).'"></td>';

print '</tr>';
print '</table>';

print '</form>';

print '<br>';

// List movement prepared
print '<table class="liste" width="100%">';

// Lignes des titres
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ProductRef'),$_SERVER["PHP_SELF"],'p.ref',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('ProductLabel'),$_SERVER["PHP_SELF"],'p.label',$param,'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Qty'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseSource'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('WarehouseTarget'),$_SERVER["PHP_SELF"],'',$param,'','align="right"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>';

// Lignes des champs de filtre
/*print '<tr class="liste_titre">'.
'<td class="liste_titre">&nbsp;</td>'.
'<td class="liste_titre">'.
'<input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'">'.
'</td>'.
'<td class="liste_titre">'.
'<input class="flat" type="text" name="snom" size="8" value="'.dol_escape_htmltag($snom).'">'.
'</td>';
if (!empty($conf->service->enabled) && $type == 1)
{
	print '<td class="liste_titre">&nbsp;</td>';
}
print '<td class="liste_titre">&nbsp;</td>'.
	'<td class="liste_titre" align="right">' . $langs->trans('AlertOnly') . '&nbsp;<input type="checkbox" name="salert" ' . $alertchecked . '></td>'.
	'<td class="liste_titre" align="right">&nbsp;</td>'.
	'<td class="liste_titre">&nbsp;</td>'.
	'<td class="liste_titre" align="right">'.
	'<input type="image" class="liste_titre" name="button_search"'.
	'src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" alt="' . $langs->trans("Search") . '">'.
	'<input type="image" class="liste_titre" name="button_removefilter"
	src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">'.
	'</td>'.
	'</tr>';
*/






print '</table>';
		
// Generate
$value=$langs->trans("RecordMovement");
print '<div class="center"><input class="button" type="submit" name="valid" value="'.$value.'"></div>';


print '</form>';


llxFooter();

$db->close();
?>