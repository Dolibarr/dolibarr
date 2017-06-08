<?php
/* Copyright (C) 2016		ATM Consulting			<support@atm-consulting.fr>
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
 *	\file       htdocs/inventory/list.php
 *	\ingroup    product
 *	\brief      File of class to manage inventory
 */
 
require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/product/inventory/listview.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/lib/inventory.lib.php';

$langs->load("stock");
$langs->load("inventory");

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = (GETPOST("page",'int')?GETPOST("page", 'int'):0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="i.title";
if (! $sortorder) $sortorder="ASC";

if (empty($user->rights->stock->lire)) accessforbidden();


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('',$langs->trans('inventoryListTitle'),'','');

echo '<form name="formListInvetory" action="'.$_SERVER['PHP_SELF'].'" method="post" >';

$inventory = new Inventory($db);
$list = new ListView($db, 'listInventory');

$THide = array('label','title');

echo $list->render(Inventory::getSQL('All'), array(
    'param' => array(
        'limit' => $limit,
        'offset' => $offset,
        'sortfield' => $sortfield,
        'sortorder'=> $sortorder,
        'page'=>$page
    ),
    'limit' => array(
	    'nbLine' => $limit,
	),
	'allow-field-select' => true,
    'link'=>array(
        'fk_warehouse'=>'<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id=@val@">'.img_picto('','object_stock.png','',0).' @label@</a>'
    ),
    'translate'=>array(),
    'hide'=>$THide,
    'type'=>array(
        'datec'=>'date',
        'tms'=>'datetime',
        'date_inventory'=>'date'
    ),
    'list'=>array(
        'title'=>$langs->trans('inventoryListTitle'),
        'messageNothing'=>$langs->trans('inventoryListEmpty'),
		'image' => 'title_products.png'
    ),
    'title'=>array(
        'rowid'=>$langs->trans('Title'),
		'date_inventory'=>$langs->trans('InventoryDate'),
        'fk_warehouse'=>$langs->trans('Warehouse'),
        'datec'=>$langs->trans('DateCreation'),
        'tms'=>$langs->trans('DateModification'),
        'status'=>$langs->trans('Status')
    ),
    'eval'=>array(
        'status' => '(@val@ ? img_picto("'.$langs->trans("inventoryValidate").'", "statut4") : img_picto("'.$langs->trans("inventoryDraft").'", "statut3"))',
        'rowid'=>'Inventory::getLink(@val@)'
    ),
	'position' => array(
		'text-align' => array('status' => 'right')
		
	),
    'search'=>array(
		'rowid' => array('search_type' => true, 'table' => array('i'), 'field' => array('title')),
		'date_inventory'=>array('search_type' => 'calendars', 'table' => array('i'), 'field' => array('date_inventory')),
		'status'=>array('search_type' => array(1=>$langs->trans("inventoryValidate"), 0=>$langs->trans("inventoryDraft")))
    )
));


/*if (!empty($user->rights->stock->create))
{
    print '<div class="tabsAction">';
    print '<a class="butAction" href="inventory.php?action=create">'.$langs->trans('inventoryCreate').'</a>';
    print '</div>';
}*/

echo '</form>';

llxFooter('');
$db->close();