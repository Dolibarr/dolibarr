<?php

    require '../../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/inventory/class/inventory.class.php';
    
    $get = GETPOST('get');
    $put = GETPOST('put');
    
    switch ($put) {
        case 'qty':
            if (!$user->rights->inventory->write) { echo -1; exit; }
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet($db);
            if( $det->fetch( $fk_det_inventory)) {
                $det->qty_view+=GETPOST('qty');
                $det->update($user);
                
                echo $det->qty_view;
            }
            else {
                echo -2;
            }            
            
            break;
			
        case 'pmp':
            if (!$user->rights->inventory->write || !$user->rights->inventory->changePMP) { echo -1; exit; }
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet($db);
            if( $det->fetch( $fk_det_inventory)) {
                $det->new_pmp=price2num(GETPOST('pmp'));
                $det->update($user);
                
                echo $det->new_pmp;
            }
            else {
                echo -2;
            }            
            
            break;
        
    }
 
