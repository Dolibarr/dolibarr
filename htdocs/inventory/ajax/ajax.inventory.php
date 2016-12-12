<?php

    require('../config.php');
    require('../class/inventory.class.php');

    
    $get = GETPOST('get');
    $put = GETPOST('put');
    
    $PDOdb=new TPDOdb;
    
    switch ($put) {
        case 'qty':
            if (!$user->rights->inventory->write) { echo -1; exit; }
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet;
            if( $det->load($PDOdb, $fk_det_inventory)) {
                $det->qty_view+=GETPOST('qty');
                $det->save($PDOdb);
                
                echo $det->qty_view;
            }
            else {
                echo -2;
            }            
            
            break;
			
        case 'pmp':
            if (!$user->rights->inventory->write || !$user->rights->inventory->changePMP) { echo -1; exit; }
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet;
            if( $det->load($PDOdb, $fk_det_inventory)) {
                $det->new_pmp=price2num(GETPOST('pmp'));
                $det->save($PDOdb);
                
                echo $det->new_pmp;
            }
            else {
                echo -2;
            }            
            
            break;
        
    }
 
