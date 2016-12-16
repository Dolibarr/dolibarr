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
 *	\file       htdocs/inventory/inventory.php
 *	\ingroup    product
 *	\brief      File of class to manage inventory
 */
 
require_once '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/listview.class.php';
require_once DOL_DOCUMENT_ROOT.'/inventory/class/inventory.class.php';
require_once DOL_DOCUMENT_ROOT.'/inventory/lib/inventory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

$langs->load("stock");
$langs->load("inventory");

if(!$user->rights->inventory->read) accessforbidden();

_list();


function _list() 
{
	
	global $db, $conf, $langs, $user;
		
	llxHeader('',$langs->trans('inventoryListTitle'),'','');
	
	echo '<form name="formListInvetory" action="'.$_SERVER['PHP_SELF'].'" method="post" >';
	
	$inventory = new Inventory($db);
	$l = new ListView($db,'listInventory');

	$THide = array('label','title');

	echo $l->render(Inventory::getSQL('All'), array(
		'link'=>array(
			'fk_warehouse'=>'<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id=@val@">'.img_picto('','object_stock.png','',0).' @label@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array(
			'datec'=>'date'
			,'tms'=>'datetime'
			,'date_inventory'=>'date'
		)
		,'list'=>array(
			'title'=>$langs->trans('inventoryListTitle')
			,'messageNothing'=>$langs->trans('inventoryListEmpty')
		)
		,'title'=>array(
			'rowid'=>$langs->trans('Title')
			,'fk_warehouse'=>$langs->trans('Warehouse')
			,'date_inventory'=>$langs->trans('InventoryDate')
			,'datec'=>$langs->trans('DateCreation')
			,'tms'=>$langs->trans('DateUpdate')
			,'status'=>$langs->trans('Status')
		)
		,'eval'=>array(
			'status' => '(@val@ ? img_picto("'.$langs->trans("inventoryValidate").'", "statut4") : img_picto("'.$langs->trans("inventoryDraft").'", "statut3"))'
			,'rowid'=>'Inventory::getLink(@val@)'
		)
		,'search'=>array(
				'date_inventory'=>'calendar'
				,'title'=>true
				
		)
	));


	if ($user->rights->inventory->create)
	{
		print '<div class="tabsAction">';
		print '<a class="butAction" href="inventory.php?action=create">'.$langs->trans('inventoryCreate').'</a>';
		print '</div>';
	}

	echo '</form>';
	
	llxFooter('');
}