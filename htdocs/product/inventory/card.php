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
 *	\file       htdocs/inventory/card.php
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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


$langs->load('stock');
$langs->load('inventory');

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=(GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm','alpha');
$socid=GETPOST('socid','int');
if (! empty($user->societe_id)) $socid=$user->societe_id;

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Protection if external user
if ($user->societe_id > 0)
{
    //accessforbidden();
}
$result = restrictedArea($user, 'stock', $id);


$object = new Inventory($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('inventorycard'));



/*
 * Actions
 */

$parameters=array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($cancel)
    {
        if ($action != 'addlink')
        {
            $urltogo=$backtopage?$backtopage:dol_buildpath('/product/inventory/list.php',1);
            header("Location: ".$urltogo);
            exit;
        }
        if ($id > 0 || ! empty($ref)) $ret = $object->fetch($id,$ref);
        $action='';
    }
    
    if ($action == 'confirmCreate')
    {
		if (empty($user->rights->stock->creer)) accessforbidden();
	
		if ($cancel)
		{
		    $urltogo=$backtopage?$backtopage:dol_buildpath('/product/inventory/list.php',1);
		    header("Location: ".$urltogo);
		    exit;
		}
		
		$error=0;
		
		$object->setValues($_POST);
		
        $fk_inventory = $object->create($user);
		if ($fk_inventory>0)
		{
        	$fk_category = (int) GETPOST('fk_category');
        	$fk_supplier = (int) GETPOST('fk_supplier');
        	$fk_warehouse = (int) GETPOST('fk_warehouse');
        	$only_prods_in_stock = (int) GETPOST('OnlyProdsInStock');
        	
        	$object->addProductsFor($fk_warehouse,$fk_category,$fk_supplier,$only_prods_in_stock);
        	$object->update($user);
        	
        	header('Location: '.dol_buildpath('/product/inventory/card.php?id='.$object->id.'&action=edit', 1));
            exit;        	
        }
        else
        {
        	setEventMessage($object->error,'errors');
        	header('Location: '.dol_buildpath('/product/inventory/card.php?action=create', 1));
        	exit;
        }
    }
    
    switch($action) {
    	case 'save':
    		if (!$user->rights->stock->creer) accessforbidden();
    		
    		
    		$id = GETPOST('id');
    		
    		$object = new Inventory($db);
    		$object->fetch($id);
    		
    		$object->setValues($_REQUEST);
    		
    		if ($object->errors)
    		{
    			setEventMessage($object->errors, 'errors');
    			$action = 'edit';
    		}
    		else 
    		{
    			$object->udpate($user);
    			header('Location: '.dol_buildpath('/product/inventory/card.php?id='.$object->getId().'&action=view', 1));
    			exit;
    		}
    		
    		break;
    		
    	case 'confirm_regulate':
    		if (!$user->rights->stock->creer) accessforbidden();
    		$id = GETPOST('id');
    		
    		$object = new Inventory($db);
    		$object->fetch($id);
            
            if($object->status == 0) {
                $object->status = 1;
                $object->update($user);
                
                $action='view';
            }
            else {
               $action='view';
            }
            
    		break;
    		
    	case 'confirm_changePMP':
    		
    		$id = GETPOST('id');
    		
    		$object = new Inventory($db);
    		$object->fetch( $id );
    		
    		$object->changePMP($user);
    		
    		$action='view';
    		
    		break;
    		
    	case 'add_line':
    		if (!$user->rights->stock->creer) accessforbidden();
    		
    		$id = GETPOST('id');
    		$fk_warehouse = GETPOST('fk_warehouse');
    		
    		$object = new Inventory($db);
    		$object->fetch( $id );
    		
    		$fk_product = GETPOST('fk_product');
    		if ($fk_product>0)
    		{
    			$product = new Product($db);
    			if($product->fetch($fk_product)<=0 || $product->type != 0) {
    				setEventMessage($langs->trans('ThisIsNotAProduct'),'errors');
    			}
    			else{
    				
    				//Check product not already exists
    				$alreadyExists = false;
    				if(!empty($object->Inventorydet)) {
    					foreach ($object->Inventorydet as $invdet)
    					{
    						if ($invdet->fk_product == $product->id
    							&& $invdet->fk_warehouse == $fk_warehouse)
    						{
    							$alreadyExists = true;
    							break;
    						}
    					}
    				}
    				if (!$alreadyExists)
    				{
    				    if($object->addProduct($product->id, $fk_warehouse)) {
    				    	setEventMessage($langs->trans('ProductAdded'));
    				    }
    				}
    				else
    				{
    					setEventMessage($langs->trans('inventoryWarningProductAlreadyExists'), 'warnings');
    				}
    				
    			}
    			
    			$object->update($user);
    			$object->sortDet();
    		}
    		
    		$action='edit';
    		
    		break;
    		
    	case 'confirm_delete_line':
    		if (!$user->rights->stock->creer) accessforbidden();
    		
    		
    		//Cette action devrais se faire uniquement si le status de l'inventaire est à 0 mais aucune vérif
    		$rowid = GETPOST('rowid');
    		$objectdet = new Inventorydet($db);
    		if($objectdet->fetch($rowid)>0) {
    			$objectdet->delete($user);
    			setEventMessage("ProductDeletedFromInventory");
    		}
    		$id = GETPOST('id');
    		$object = new Inventory($db);
    		$object->fetch( $id);
    		
    		$action='edit';
    		
    		break;
        case 'confirm_flush':
            if (!$user->rights->stock->creer) accessforbidden();
            
            
            $id = GETPOST('id');
            
            $object = new Inventory($db);
            $object->fetch($id);
            
            $object->deleteAllLine($user);
            
            setEventMessage($langs->trans('InventoryFlushed'));
            
            $action='edit';
            
            break;
    	case 'confirm_delete':
    		if (!$user->rights->stock->supprimer) accessforbidden();
            
    		$id = GETPOST('id');
    		
    		$object = new Inventory($db);
    		$object->fetch($id);
    		
    		$object->delete($user);
    		
    		setEventMessage($langs->trans('InventoryDeleted'));
    		
    		header('Location: '.dol_buildpath('/inventory/list.php', 1));
    		exit;
    		
    		break;
    	/*case 'exportCSV':
    		
    		$id = GETPOST('id');
    		
    		$object = new Inventory($db);
    		$object->fetch($id);
    		
    		_exportCSV($object);
    		
    		exit;
    		break;
    		*/
    }
}


/*
 * Views
 */

$form=new Form($db);

llxHeader('',$langs->trans('Inventory'),'','');
	
if ($action == 'create')
{
    if (empty($user->rights->stock->creer)) accessforbidden();

    print load_fiche_titre($langs->trans("NewInventory"));
	
    echo '<form name="confirmCreate" action="'.$_SERVER['PHP_SELF'].'" method="post" />';
	echo '<input type="hidden" name="action" value="confirmCreate" />';
	
	dol_fiche_head();
	
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
    
    dol_fiche_end();
    
    print '<div class="center">';
    print '<input type="submit" class="button" name="create" value="'.$langs->trans('inventoryConfirmCreate').'" />';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'" />';
    print '</div>';
    
	echo '</form>';
	
}

if ($action == 'view' || $action == 'edit' ||  empty($action))
{
    $object = new Inventory($db);
    $result = $object->fetch($id);
    if ($result < 0) dol_print_error($db, $object->error, $object->errors);
    
    $warehouse = new Entrepot($db);
    $warehouse->fetch($object->fk_warehouse);
    
    
    
	if($action == 'changePMP')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ApplyNewPMP'), $langs->trans('ConfirmApplyNewPMP', $object->getTitle()), 'confirm_changePMP', array(),'no',1);
	}
	else if($action == 'flush')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('FlushInventory'),$langs->trans('ConfirmFlushInventory',$object->getTitle()),'confirm_flush',array(),'no',1);
	}
	else if($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('Delete'),$langs->trans('ConfirmDelete',$object->getTitle()),'confirm_delete',array(),'no',1);
	}
	else if($action == 'delete_line')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&rowid='.GETPOST('rowid'),$langs->trans('DeleteLine'),$langs->trans('ConfirmDeleteLine',$object->getTitle()),'confirm_delete_line',array(),'no',1);
	}
	else if($action == 'regulate')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('RegulateStock'),$langs->trans('ConfirmRegulateStock',$object->getTitle()),'confirm_regulate',array(),'no',1);
	}
	
	print dol_get_fiche_head(inventoryPrepareHead($object, $langs->trans('inventoryOfWarehouse', $warehouse->libelle), empty($action) ? '': '&action='.$action));
	
	$lines = array();
	card_line($object, $lines, $action);
	
	print $langs->trans('Ref')." ".$object->ref.'<br>';
	print $langs->trans('Date')." ".$object->getDate('date_inventory').'<br><br>';
	
	$objectTPL = array(
	    'id'=> $object->id
	    ,'ref'=> $object->ref
		,'date_cre' => $object->getDate('date_cre', 'd/m/Y')
		,'date_maj' => $object->getDate('date_maj', 'd/m/Y H:i')
		,'fk_warehouse' => $object->fk_warehouse
		,'status' => $object->status
		,'entity' => $object->entity
		,'amount' => price( round($object->amount,2) )
		,'amount_actual'=>price (round($object->amount_actual,2))
		
	);
	
	$can_validate = !empty($user->rights->stock->validate);
	$view_url = dol_buildpath('/product/inventory/card.php', 1);
	
	$view = array(
		'mode' => $action
		,'url' => dol_buildpath('/product/inventory/card.php', 1)
		,'can_validate' => (int) $user->rights->stock->validate
		,'is_already_validate' => (int) $object->status
		,'token'=>$_SESSION['newtoken']
	);
	
	include './tpl/inventory.tpl.php';
}

// End of page
llxFooter();
$db->close();



function card_line(&$inventory, &$lines, $mode)
{
	global $db,$langs,$user,$conf;
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
		
		$qtytoadd = GETPOST('qty_to_add', 'array');
		$qty = (float) $qtytoadd[$k];
		
		$lines[]=array(
			'produit' => $product->getNomUrl(1).'&nbsp;-&nbsp;'.$product->label,
			'entrepot'=>$e->getNomUrl(1),
			'barcode' => $product->barcode,
			'qty' =>($mode == 'edit' ? '<input type="text" name="qty_to_add['.$k.']" value="'.$qty.'" size="8" style="text-align:center;" /> <a id="a_save_qty_'.$k.'" href="javascript:save_qty('.$k.')">'.img_picto($langs->trans('Add'), 'edit_add').'</a>' : '' ),
			'qty_view' => ($Inventorydet->qty_view ? $Inventorydet->qty_view : 0),
			'qty_stock' => $stock,
			'qty_regulated' => ($Inventorydet->qty_regulated ? $Inventorydet->qty_regulated : 0),
			'action' => ($user->rights->stock->write && $mode=='edit' ? '<a href="'.dol_buildpath('/product/inventory/card.php?id='.$inventory->id.'&action=delete_line&rowid='.$Inventorydet->id, 1).'">'.img_picto($langs->trans('inventoryDeleteLine'), 'delete').'</a>' : ''),
			'pmp_stock'=>round($pmp_actual,2),
            'pmp_actual'=> round($pmp * $Inventorydet->qty_view,2),
			'pmp_new'=>(!empty($user->rights->stock->changePMP) && $mode == 'edit' ? '<input type="text" name="new_pmp['.$k.']" value="'.$Inventorydet->new_pmp.'" size="8" style="text-align:right;" /> <a id="a_save_new_pmp_'.$k.'" href="javascript:save_pmp('.$k.')">'.img_picto($langs->trans('Save'), 'bt-save.png@inventory').'</a>' :  price($Inventorydet->new_pmp)),
            'pa_stock'=>round($last_pa * $stock,2),
            'pa_actual'=>round($last_pa * $Inventorydet->qty_view,2),
			'current_pa_stock'=>round($current_pa * $stock,2),
			'current_pa_actual'=>round($current_pa * $Inventorydet->qty_view,2),
            'k'=>$k,
            'id'=>$Inventorydet->id
		);
	}

}


/*
function _exportCSV(&$inventory) 
{
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
*/

function _footerList($view,$total_pmp,$total_pmp_actual,$total_pa,$total_pa_actual, $total_current_pa,$total_current_pa_actual) 
{
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
            if(!empty($user->rights->stock->changePMP)) {
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


function _headerList($view) 
{
	global $conf,$user,$langs;
	
	?>
			<tr style="background-color:#dedede;">
				<th class="titlefield"><?php echo $langs->trans('Product'); ?></th>
				<th><?php echo $langs->trans('Warehouse'); ?></th>
				<?php if (! empty($conf->barcode->enabled)) { ?>
					<th align="center"><?php echo $langs->trans('Barcode'); ?></th>
				<?php } ?>
				<?php if ($view['can_validate'] == 1) { ?>
					<th align="center" width="20%"><?php echo $langs->trans('TheoricalQty'); ?></th>
					<?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th align="center" width="20%" colspan="3">'.$langs->trans('TheoricalValue').'</th>';   	
					 }
					 else {
					 	echo '<th align="center" width="20%" colspan="2">'.$langs->trans('TheoricalValue').'</th>';
					 }
					 
					?>
					
				<?php } ?>
				    <th align="center" width="20%"><?php echo $langs->trans('RealQty'); ?></th>
				<?php if ($view['can_validate'] == 1) { ?>
				    
				    <?php
				    
				     $colspan = 2;
					 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) $colspan++;
				     if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)) $colspan++;
					
	                 echo '<th align="center" width="20%" colspan="'.$colspan.'">'.$langs->trans('RealValue').'</th>';
					 
					?>
						
					<th align="center" width="15%"><?php echo $langs->trans('RegulatedQty'); ?></th>
				<?php } ?>
				<?php if ($view['is_already_validate'] != 1) { ?>
					<th align="center" width="5%">#</th>
				<?php } ?>
				
			</tr>
			<?php if ($view['can_validate'] == 1) { ?>
	    	<tr style="background-color:#dedede;">
	    	    <th colspan="<?php echo empty($conf->barcode->enabled) ? 3 : 4;  ?>">&nbsp;</th>
	    	    <th><?php echo $langs->trans('PMP'); ?></th>
	    	    <th><?php echo $langs->trans('LastPA'); ?></th>
	    	    <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th>'.$langs->trans('CurrentPA').'</th>';   	
					 }
					 
				?>
	    	    <th>&nbsp;</th>
	    	    <th><?php echo $langs->trans('PMP'); ?></th>
	    	    <?php
	    	    if(!empty($user->rights->stock->changePMP)) {
	    	    	echo '<th rel="newPMP">'.$langs->trans('ColumnNewPMP').'</th>';
	    	    }
	    	    ?>
	            <th><?php echo $langs->trans('LastPA'); ?></th>
	            <?php
	                 if(!empty($conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA)){
	              		echo '<th>'.$langs->trans('CurrentPA').'</th>';   	
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
