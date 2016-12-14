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

ini_set('memory_limit', '512M');

require_once DOL_DOCUMENT_ROOT.'/core/class/listview.class.php';
require_once DOL_DOCUMENT_ROOT.'/inventory/class/inventory.class.php';
require_once DOL_DOCUMENT_ROOT.'/inventory/lib/inventory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

set_time_limit(0);

if(!$user->rights->inventory->read) accessforbidden();

$langs->load("inventory");

_action();

function _action() 
{
	global $user, $db, $conf, $langs;	
	
	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	$action=GETPOST('action');
	
	switch($action) {
		case 'list':
			_list();

			break;
		
		case 'create':
			if (!$user->rights->inventory->create) accessforbidden();
			
			$inventory = new Inventory($db);
			
			_card_warehouse( $inventory);

			break;
		
		case 'confirmCreate':
			if (!$user->rights->inventory->create) accessforbidden();
		
			$inventory = new Inventory($db);
			$inventory->set_values($_POST);
			
            $fk_inventory = $inventory->create($user);
            $fk_category = (int)GETPOST('fk_category');
            $fk_supplier = (int)GETPOST('fk_supplier');
            $fk_warehouse = (int)GETPOST('fk_warehouse');
			$only_prods_in_stock = (int)GETPOST('OnlyProdsInStock');
            
			$e = new Entrepot($db);
			$e->fetch($fk_warehouse);
			$TChildWarehouses = array($fk_warehouse);
			$e->get_children_warehouses($fk_warehouse, $TChildWarehouses);
			
			$sql = 'SELECT ps.fk_product, ps.fk_entrepot 
			     FROM '.MAIN_DB_PREFIX.'product_stock ps 
			     INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = ps.fk_product) 
                 LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product cp ON (cp.fk_product = p.rowid)
				 LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price pfp ON (pfp.fk_product = p.rowid)
			     WHERE ps.fk_entrepot IN ('.implode(', ', $TChildWarehouses).')';
			
            if($fk_category>0) $sql.= " AND cp.fk_categorie=".$fk_category;
			if($fk_supplier>0) $sql.= " AND pfp.fk_soc=".$fk_supplier;
			if($only_prods_in_stock>0) $sql.= ' AND ps.reel > 0';
			
			$sql.=' GROUP BY ps.fk_product, ps.fk_entrepot
					ORDER BY p.ref ASC,p.label ASC';
                 
                 
			$res = $db->query($sql);
			if($res) {
				while($obj = $db->fetch_object($res)){
				
	                $inventory->add_product($obj->fk_product, $obj->fk_entrepot);
				}
			}
			
			$inventory->update($user);
			
			header('Location: '.dol_buildpath('inventory/inventory.php?id='.$inventory->id.'&action=edit', 1));
			break;
			
		case 'edit':
			if (!$user->rights->inventory->write) accessforbidden();
			
			
			$inventory = new Inventory($db);
			$inventory->fetch(GETPOST('id'));
			
			_card($inventory, GETPOST('action'));
			
			break;
			
		case 'save':
			if (!$user->rights->inventory->write) accessforbidden();
			
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			$inventory->set_values($_REQUEST);
			
			if ($inventory->errors)
			{
				setEventMessage($inventory->errors, 'errors');
				_fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'edit');
			}
			else 
			{
				$inventory->save($PDOdb);
				header('Location: '.dol_buildpath('inventory/inventory.php?id='.$inventory->getId().'&action=view', 1));
			}
			
			break;
			
		case 'regulate':
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
            
            if($inventory->status == 0) {
                $inventory->status = 1;
                $inventory->save($PDOdb);
                
                _fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'view');
                
            
            }
            else {
               _fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'view');
            }
            
			break;
			
		case 'changePMP':
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			$inventory->changePMP($PDOdb);
			
			_fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'view');
			
			break;
			
		case 'add_line':
			if (!$user->rights->inventory->write) accessforbidden();
			
			
			$id = __get('id', 0, 'int');
			$fk_warehouse = __get('fk_warehouse', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			$type = (!empty($conf->use_javascript_ajax) && !empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT) ? 'string' : 'int'); //AA heu ?
			
			$fk_product = __get('fk_product', 0, $type);
			
			if ($fk_product)
			{
				$product = new Product($db);
				$product->fetch($fk_product);	// ! ref TODO vérifier quand même			
				if($product->type != 0) {
					setEventMessage($langs->trans('ThisIsNotAProduct'),'errors');
				}
				else{
					
					//Check product not already exists
					$alreadyExists = false;
					foreach ($inventory->Inventorydet as $invdet)
					{
						if ($invdet->fk_product == $product->id
							&& $invdet->fk_warehouse == $fk_warehouse)
						{
							$alreadyExists = true;
							break;
						}
					}
					
					if (!$alreadyExists)
					{
					    $inventory->add_product($PDOdb, $product->id, $fk_warehouse);
                        
					}
					else
					{
						setEventMessage($langs->trans('inventoryWarningProductAlreadyExists'), 'warnings');
					}
					
				}
				
				$inventory->save($PDOdb);
				$inventory->sort_det();
			}
			
			_fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'edit');
			
			break;
			
		case 'delete_line':
			if (!$user->rights->inventory->write) accessforbidden();
			
			
			//Cette action devrais se faire uniquement si le status de l'inventaire est à 0 mais aucune vérif
			$rowid = __get('rowid', 0, 'int');
			$Inventorydet = new Inventory($db);
			$Inventorydet->load($PDOdb, $rowid);
			$Inventorydet->delete($PDOdb);
			
			$id = __get('id', 0, 'int');
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			_fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'edit');
			
			break;
        case 'flush':
            if (!$user->rights->inventory->create) accessforbidden();
            
            
            $id = __get('id', 0, 'int');
            
            $inventory = new Inventory($db);
            $inventory->load($PDOdb, $id);
            
            $inventory->deleteAllLine($PDOdb);
            
            setEventMessage('Inventaire vidé');
            
            _fiche($PDOdb, $user, $db, $conf, $langs, $inventory, 'edit');
           
            
            break;
		case 'delete':
			if (!$user->rights->inventory->create) accessforbidden();
            
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			$inventory->delete($PDOdb);
			
			header('Location: '.dol_buildpath('/inventory/inventory.php', 1));
			exit;
			//_list();
			
		case 'printDoc':
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			generateODT($PDOdb, $db, $conf, $langs, $inventory);
			break;
			
		case 'exportCSV':
			
			$id = __get('id', 0, 'int');
			
			$inventory = new Inventory($db);
			$inventory->load($PDOdb, $id);
			
			exportCSV($inventory);
			
			exit;
			break;
			
		default:
			if (!$user->rights->inventory->write) accessforbidden();
				
			$id = GETPOST('id');
				
			$inventory = new Inventory($db);
			$inventory->fetch($id);
				
			_card($inventory, $action );
				
			
			break;
	}
	
}

function _list() 
{
	
	global $db, $conf, $langs, $user;
		
	llxHeader('',$langs->trans('inventoryListTitle'),'','');
	
	$inventory = new Inventory($db);
	$l = new ListView($db,'listInventory');

	$THide = array('label');

	echo $l->render(Inventory::getSQL('All'), array(
		'limit'=>array(
			'nbLine'=>'30'
		)
		,'subQuery'=>array()
		,'link'=>array(
			'fk_warehouse'=>'<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id=@val@">'.img_picto('','object_stock.png','',0).' @label@</a>'
		)
		,'translate'=>array()
		,'hide'=>$THide
		,'type'=>array(
			'date_cre'=>'date'
			,'date_maj'=>'datetime'
			,'date_inventory'=>'date'
		)
		,'liste'=>array(
			'titre'=>$langs->trans('inventoryListTitle')
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'noheader'=> (int)isset($_REQUEST['fk_soc']) | (int)isset($_REQUEST['fk_product'])
			,'messageNothing'=>$langs->trans('inventoryListEmpty')
			,'picto_search'=>img_picto('','search.png', '', 0)
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
	));


	if ($user->rights->inventory->create)
	{
		print '<div class="tabsAction">';
		print '<a class="butAction" href="inventory.php?action=create">'.$langs->trans('inventoryCreate').'</a>';
		print '</div>';
	}

	llxFooter('');
}

function _card_warehouse(&$inventory)
{
	global $langs,$conf,$db, $user, $form;
	
	dol_include_once('/categories/class/categorie.class.php');    
        
	llxHeader('',$langs->trans('inventorySelectWarehouse'),'','');
	print dol_get_fiche_head(inventoryPrepareHead($inventory));
	
	echo '<form name="confirmCreate" action="'.$_SERVER['PHP_SELF'].'" method="post" />';
	echo '<input type="hidden" name="action" value="confirmCreate" />';
	
    $formproduct = new FormProduct($db);
    
    ?>
    <table class="border" width="100%" >
        <tr>
            <td><?php echo $langs->trans('Title') ?></td>
            <td><input type="text" name="title" value="" size="50" /></td> 
        </tr>
        <tr>
            <td><?php echo $langs->trans('Date') ?></td>
            <td><?php echo $form->select_date(time(),'date_inventory'); ?></td> 
        </tr>
        
        <tr>
            <td><?php echo $langs->trans('inventorySelectWarehouse') ?></td>
            <td><?php echo $formproduct->selectWarehouses('', 'fk_warehouse') ?></td> 
        </tr>
        
        <tr>
            <td><?php echo $langs->trans('SelectCategory') ?></td>
            <td><?php echo $form->select_all_categories(0,'', 'fk_category') ?></td> 
        </tr>
        <tr>
            <td><?php echo $langs->trans('SelectFournisseur') ?></td>
            <td><?php echo $form->select_thirdparty('','fk_supplier','s.fournisseur = 1') ?></td> 
        </tr>
        <tr>
            <td><?php echo $langs->trans('OnlyProdsInStock') ?></td>
            <td><input type="checkbox" name="OnlyProdsInStock" value="1"></td> 
        </tr>
        
    </table>
    <?php
    
	print '<div class="tabsAction">';
	print '<input type="submit" class="butAction" value="'.$langs->trans('inventoryConfirmCreate').'" />';
	print '</div>';
	
	echo '</form>';
	
	llxFooter('');
}

function _card(&$inventory, $mode='edit')
{
	global $langs, $conf, $db, $user;
	
	llxHeader('',$langs->trans('inventoryEdit'),'','');
	
	$warehouse = new Entrepot($db);
	$warehouse->fetch($inventory->fk_warehouse);
	
	print dol_get_fiche_head(inventoryPrepareHead($inventory, $langs->trans('inventoryOfWarehouse', $warehouse->libelle), '&action='.$mode));
	
	$lines = array();
	_card_line($inventory, $lines, $mode);
	
	print '<b>'.$langs->trans('inventoryOnDate')." ".$inventory->get_date('date_inventory').'</b><br><br>';
	
	$inventoryTPL = array(
		'id'=> $inventory->id
		,'date_cre' => $inventory->get_date('date_cre', 'd/m/Y')
		,'date_maj' => $inventory->get_date('date_maj', 'd/m/Y H:i')
		,'fk_warehouse' => $inventory->fk_warehouse
		,'status' => $inventory->status
		,'entity' => $inventory->entity
		,'amount' => price( round($inventory->amount,2) )
		,'amount_actual'=>price (round($inventory->amount_actual,2))
		
	);
	
	$can_validate = !empty($user->rights->inventory->validate);
	$view_url = dol_buildpath('/inventory/inventory.php', 1);
	
	$view = array(
		'mode' => $mode
		,'url' => dol_buildpath('/inventory/inventory.php', 1)
		,'can_validate' => (int) $user->rights->inventory->validate
		,'is_already_validate' => (int) $inventory->status
		,'token'=>$_SESSION['newtoken']
	);
	
	include './tpl/inventory.tpl.php';
	
	llxFooter('');
}


function _card_line(&$inventory, &$lines, $mode)
{
	global $db;
	$inventory->amount_actual = 0;
	
	$TCacheEntrepot = array();

	foreach ($inventory->Inventorydet as $k => $Inventorydet)
	{
	    
        $product = & $Inventorydet->product;
		$stock = $Inventorydet->qty_stock;
	
        $pmp = $Inventorydet->pmp;
		$pmp_actual = $pmp * $stock;
		$inventory->amount_actual+=$pmp_actual;

        $last_pa = $Inventorydet->pa;
		$current_pa = $Inventorydet->current_pa;
        
		$e = new Entrepot($db);
		if(!empty($TCacheEntrepot[$Inventorydet->fk_warehouse])) $e = $TCacheEntrepot[$Inventorydet->fk_warehouse];
		elseif($e->fetch($Inventorydet->fk_warehouse) > 0) $TCacheEntrepot[$e->id] = $e;
		
		$qty = (float)GETPOST('qty_to_add')[$k];
		
		$lines[]=array(
			'produit' => $product->getNomUrl(1).'&nbsp;-&nbsp;'.$product->label
			,'entrepot'=>$e->getNomUrl(1)
			,'barcode' => $product->barcode
			,'qty' =>($mode == 'edit' ? '<input type="text" name="qty_to_add['.$k.']" value="'.$qty.'" size="8" style="text-align:center;" />' : $qty )
                        .($mode =='edit' ? '<a id="a_save_qty_'.$k.'" href="javascript:save_qty('.$k.')">'.img_picto($langs->trans('Add'), 'plus16@inventory').'</a>' : '')
			,'qty_view' => $Inventorydet->qty_view ? $Inventorydet->qty_view : 0
			,'qty_stock' => $stock
			,'qty_regulated' => $Inventorydet->qty_regulated ? $Inventorydet->qty_regulated : 0
			,'action' => ($user->rights->inventory->write ? '<a onclick="if (!confirm(\'Confirmez-vous la suppression de la ligne ?\')) return false;" href="'.dol_buildpath('inventory/inventory.php?id='.$inventory->getId().'&action=delete_line&rowid='.$Inventorydet->getId(), 1).'">'.img_picto($langs->trans('inventoryDeleteLine'), 'delete').'</a>' : '')
			,'pmp_stock'=>round($pmp_actual,2)
            ,'pmp_actual'=> round($pmp * $Inventorydet->qty_view,2)
			,'pmp_new'=>(!empty($user->rights->inventory->changePMP) ? '<input type="text" name="new_pmp['.$k.']" value="'.$Inventorydet->new_pmp.'" size="8" style="text-align:right;" />'
					.($mode =='edit' ? '<a id="a_save_new_pmp_'.$k.'" href="javascript:save_pmp('.$k.')">'.img_picto($langs->trans('Save'), 'bt-save.png@inventory').'</a>' : '') : '')
            ,'pa_stock'=>round($last_pa * $stock,2)
            ,'pa_actual'=>round($last_pa * $Inventorydet->qty_view,2)
			,'current_pa_stock'=>round($current_pa * $stock,2)
			,'current_pa_actual'=>round($current_pa * $Inventorydet->qty_view,2)
          
            ,'k'=>$k
            ,'id'=>$Inventorydet->id
				
		);
	}

}

function exportCSV(&$inventory) {
	global $conf;
	
	header('Content-Type: application/octet-stream');
    header('Content-disposition: attachment; filename=inventory-'. $inventory->getId().'-'.date('Ymd-His').'.csv');
    header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
	
	echo 'Ref;Label;barcode;qty theorique;PMP;dernier PA;';
	if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) echo 'PA courant;';
	echo 'qty réelle;PMP;dernier PA;';
	if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) echo 'PA courant;';
	echo 'qty regulée;'."\r\n";
	
	foreach ($inventory->Inventorydet as $k => $Inventorydet)
	{
		$product = & $Inventorydet->product;
		$stock = $Inventorydet->qty_stock;
	
        $pmp = $Inventorydet->pmp;
		$pmp_actual = $pmp * $stock;
		$inventory->amount_actual+=$pmp_actual;

        $last_pa = $Inventorydet->pa;
        $current_pa = $Inventorydet->current_pa;
		
		if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) {
			$row=array(
				'produit' => $product->ref
				,'label'=>$product->label
				,'barcode' => $product->barcode
				,'qty_stock' => $stock
				,'pmp_stock'=>round($pmp_actual,2)
	            ,'pa_stock'=>round($last_pa * $stock,2)
				,'current_pa_stock'=>round($current_pa * $stock,2)
			    ,'qty_view' => $Inventorydet->qty_view ? $Inventorydet->qty_view : 0
				,'pmp_actual'=>round($pmp * $Inventorydet->qty_view,2)
	            ,'pa_actual'=>round($last_pa * $Inventorydet->qty_view,2)
	        	,'current_pa_actual'=>round($current_pa * $Inventorydet->qty_view,2)    
				,'qty_regulated' => $Inventorydet->qty_regulated ? $Inventorydet->qty_regulated : 0
				
			);
			
		}
		else{
			$row=array(
				'produit' => $product->ref
				,'label'=>$product->label
				,'barcode' => $product->barcode
				,'qty_stock' => $stock
				,'pmp_stock'=>round($pmp_actual,2)
	            ,'pa_stock'=>round($last_pa * $stock,2)
	            ,'qty_view' => $Inventorydet->qty_view ? $Inventorydet->qty_view : 0
				,'pmp_actual'=>round($pmp * $Inventorydet->qty_view,2)
	            ,'pa_actual'=>round($last_pa * $Inventorydet->qty_view,2)
	            
				,'qty_regulated' => $Inventorydet->qty_regulated ? $Inventorydet->qty_regulated : 0
				
		);
			
		}
		
		
		echo '"'.implode('";"', $row).'"'."\r\n";
		
	}
	
	exit;
}

function _footerList($view,$total_pmp,$total_pmp_actual,$total_pa,$total_pa_actual, $total_current_pa,$total_current_pa_actual) {
	global $conf,$user,$langs;
	
	    if ($view['can_validate'] == 1) { ?>
        <tr style="background-color:#dedede;">
            <th colspan="3">&nbsp;</th>
            <?php if (! empty($conf->barcode->enabled)) { ?>
					<th align="center">&nbsp;</td>
			<?php } ?>
            <th align="right"><?php echo price($total_pmp) ?></th>
            <th align="right"><?php echo price($total_pa) ?></th>
            <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th align="right">'.price($total_current_pa).'</th>';   	
					 }
			?>
            <th>&nbsp;</th>
            <th align="right"><?php echo price($total_pmp_actual) ?></th>
            <?php
            if(!empty($user->rights->inventory->changePMP)) {
               	echo '<th>&nbsp;</th>';	
			}
			?>
            <th align="right"><?php echo price($total_pa_actual) ?></th>
            <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th align="right">'.price($total_current_pa_actual).'</th>';   	
					 }
			?>

            <th>&nbsp;</th>
            <?php if ($view['is_already_validate'] != 1) { ?>
            <th>&nbsp;</th>
            <?php } ?>
        </tr>
        <?php } 
}
function _headerList($view) {
	global $conf,$user,$langs;
	
	?>
			<tr style="background-color:#dedede;">
				<th align="left" width="20%">&nbsp;&nbsp;Produit</th>
				<th align="center">Entrepôt</td>
				<?php if (! empty($conf->barcode->enabled)) { ?>
					<th align="center">Code-barre</td>
				<?php } ?>
				<?php if ($view['can_validate'] == 1) { ?>
					<th align="center" width="20%">Quantité théorique</th>
					<?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th align="center" width="20%" colspan="3">Valeur théorique</th>';   	
					 }
					 else {
					 	echo '<th align="center" width="20%" colspan="2">Valeur théorique</th>';
					 }
					 
					?>
					
				<?php } ?>
				    <th align="center" width="20%">Quantité réelle</th>
				<?php if ($view['can_validate'] == 1) { ?>
				    
				    <?php
				    
				     $colspan = 2;
					 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) $colspan++;
				     if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) $colspan++;
					
	                 echo '<th align="center" width="20%" colspan="'.$colspan.'">Valeur réelle</th>';
					 
					?>
						
					<th align="center" width="15%">Quantité régulée</th>
				<?php } ?>
				<?php if ($view['is_already_validate'] != 1) { ?>
					<th align="center" width="5%">#</th>
				<?php } ?>
				<th align="center" width="5%"></th>
			</tr>
			<?php if ($view['can_validate'] == 1) { ?>
	    	<tr style="background-color:#dedede;">
	    	    <th colspan="<?php echo empty($conf->barcode->enabled) ? 3 : 4;  ?>">&nbsp;</th>
	    	    <th>PMP</th>
	    	    <th>Dernier PA</th>
	    	    <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th>PA courant</th>';   	
					 }
					 
				?>
	    	    <th>&nbsp;</th>
	    	    <th>PMP</th>
	    	    <?php
	    	    if(!empty($user->rights->inventory->changePMP)) {
	    	    	echo '<th rel="newPMP">'.$langs->trans('ColumnNewPMP').'</th>';
	    	    }
	    	    ?>
	            <th>Dernier PA</th>
	            <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th>PA courant</th>';   	
					 }
					 
				?>
	            <th>&nbsp;</th>
	            <?php if ($view['is_already_validate'] != 1) { ?>
	            <th>&nbsp;</th>
	            <?php } ?>
	    	</tr>
	    	<?php 
	} 
	
}
