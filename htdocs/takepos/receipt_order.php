<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2019		JC Prieto			<jcprieto@virtual20.com>
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

//V20
//Order ticket template


if(empty($place)){		//Comes from takeposmobile
	require '../main.inc.php';	// Load $user and permissions
	
	$langs->loadLangs(array("main", "cashdesk", 'products'));
	$langs->load('takepos@takepos');//V20
	
	$place = GETPOST('place', 'int');
	
	$sql="SELECT f.rowid, f.total_ttc, t.label FROM ".MAIN_DB_PREFIX."facture AS f , ".MAIN_DB_PREFIX."takepos_floor_tables AS t ".
		"WHERE f.facnumber='(PROV-POS-".$place.")' AND t.rowid=".$place;	//V20
		
	$resql = $db->query($sql);
	$row = $db->fetch_array($resql);
	$placeid = $row['rowid'];
	$placelabel=$row['label'];	//V20
	
	if (!$placeid)  $placeid = 0; // not necessary
	else
	{
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
	    $invoice = new Facture($db);
	    $invoice->fetch($placeid);
	}
	$mobile=true;	//Comes from takeposmobile
}


	include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

    $headerorder = '<html><br><b>'.$langs->trans('Site').': '. $placelabel.'<br><table width="90%"><thead><tr><th align="left" width="5%">'. $langs->trans("Qty").'</th><th align="left" width="90%">'.$langs->trans("Product") . '</th></tr></thead><tbody>';
    $footerorder = '</tbody></table><br>' . dol_print_date(dol_now(), 'dayhour') . '<br><br><br></html>';
    $order_receipt_printer1 = "";
    $order_receipt_printer2 = "";
    $catsprinter1 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_1);
    $catsprinter2 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_2);
    $parentline=0;
    foreach($invoice->lines as $line)
    {
        if ($line->special_code == "3") { continue; }
        
        if($line->special_code == "9" && $line->fk_parent_line==$parentline){	//v20
        	$count=1;
        }else{
	        $c = new Categorie($db);
	        $existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
	        $result = array_intersect($catsprinter1, $existing);
	        $count = count($result);
	        if($count > 0 )	$parentline=$line->id;	//V20
        }
        if ($count > 0 ) {
        	
            $sql = "UPDATE " . MAIN_DB_PREFIX . "facturedet set special_code='3' where rowid=$line->rowid";
            $db->query($sql);
            //$order_receipt_printer1.= '<tr>' . $line->product_label . '<td align="right">' . $line->qty . '</td></tr>';
            //$order_receipt_printer1.= '<tr><td>' . $line->product_label . '</td><td align="right">' . $line->qty.'</td></tr>';
            
            //V20: Is plate of menu?
	        if ($line->special_code == "9" || $line->product_label==''){
	        	$order_receipt_printer1.= '<tr><td></td>';
	        	$order_receipt_printer1.= '<td align="left">'.$line->desc.'</td></tr>'; 
	        }
	        else {
	        	$order_receipt_printer1.= '<tr><td align="middle">' . $line->qty.'</td>';
	        	$order_receipt_printer1.= '<td align="left">'.$line->product_label.'</td></tr>'; 	
	        }
	        
	       
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer1.="<tr><td></td><td>(".$line->array_options['options_order_notes'].")";	//V20
			$order_receipt_printer1.='</td></tr>';
        }
    }

    if($mobile)	print $headerorder .'<br>'. $order_receipt_printer1 . $footerorder; 
