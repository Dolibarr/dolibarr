<?php

<<<<<<< HEAD
    require '../../../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
  
    $get = GETPOST('get');
    $put = GETPOST('put');
    
=======
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';

$get = GETPOST('get', 'alpha');
$put = GETPOST('put', 'alpha');

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    switch ($put)
    {
        case 'qty':
        	if (empty($user->rights->stock->creer)) { echo -1; exit; }
<<<<<<< HEAD
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet($db);
            if( $det->fetch( $fk_det_inventory))
            {
                $det->qty_view+=GETPOST('qty');
                $res = $det->update($user);
                
=======

            $fk_det_inventory = GETPOST('fk_det_inventory');

            $det = new InventoryLine($db);
            if( $det->fetch($fk_det_inventory))
            {
                $det->qty_view+=GETPOST('qty');
                $res = $det->update($user);

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                echo $det->qty_view;
            }
            else
            {
                echo -2;
<<<<<<< HEAD
            }            
           
            break;
			
        case 'pmp':
        	if (empty($user->rights->stock->creer) || empty($user->rights->stock->changePMP)) { echo -1; exit; }
            
            $fk_det_inventory = GETPOST('fk_det_inventory');
            
            $det = new Inventorydet($db);
            if( $det->fetch( $fk_det_inventory))
            {
                $det->new_pmp=price2num(GETPOST('pmp'));
                $det->update($user);
                
=======
            }

            break;

        case 'pmp':
        	if (empty($user->rights->stock->creer) || empty($user->rights->stock->changePMP)) { echo -1; exit; }

            $fk_det_inventory = GETPOST('fk_det_inventory');

            $det = new InventoryLine($db);
            if( $det->fetch($fk_det_inventory))
            {
                $det->new_pmp=price2num(GETPOST('pmp'));
                $det->update($user);

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                echo $det->new_pmp;
            }
            else
            {
                echo -2;
<<<<<<< HEAD
            }            
            
            break;
    }
 
=======
            }

            break;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
