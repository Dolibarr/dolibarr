<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/lib/sendings.lib.php
 *	\ingroup    expedition
 *	\brief      Library for expedition module
 */
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function shipping_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$langs->load("sendings");
	$langs->load("deliveries");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/expedition/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("SendingCard");
	$head[$h][2] = 'shipping';
	$h++;

	if ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire)
	{
		// delivery link
		$object->fetchObjectLinked($object->id,$object->element);
		if (count($object->linkedObjectsIds['delivery']) >  0)		// If there is a delivery
		{
		    // Take first one element of array 
		    $tmp = reset($object->linkedObjectsIds['delivery']);
		    
			$head[$h][0] = DOL_URL_ROOT."/livraison/card.php?id=".$tmp;
			$head[$h][1] = $langs->trans("DeliveryCard");
			$head[$h][2] = 'delivery';
			$h++;
		}
	}

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
	    $objectsrc = $object;
	    if ($object->origin == 'commande' && $object->origin_id > 0)
	    {
	        $objectsrc = new Commande($db);
	        $objectsrc->fetch($object->origin_id);
	    }
	    $nbContact = count($objectsrc->liste_contact(-1,'internal')) + count($objectsrc->liste_contact(-1,'external'));
	    $head[$h][0] = DOL_URL_ROOT."/expedition/contact.php?id=".$object->id;
    	$head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
    	$head[$h][2] = 'contact';
    	$h++;
	}
	
    $nbNote = 0;
    if (!empty($object->note_private)) $nbNote++;
    if (!empty($object->note_public)) $nbNote++;
	$head[$h][0] = DOL_URL_ROOT."/expedition/note.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Notes");
	if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
	$head[$h][2] = 'note';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery','remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function delivery_prepare_head($object)
{
	global $langs, $conf, $user;

	$langs->load("sendings");
	$langs->load("deliveries");

	$h = 0;
	$head = array();

	if ($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/expedition/card.php?id=".$object->origin_id;
		$head[$h][1] = $langs->trans("SendingCard");
		$head[$h][2] = 'shipping';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/livraison/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("DeliveryCard");
	$head[$h][2] = 'delivery';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/expedition/contact.php?id=".$object->origin_id;
	$head[$h][1] = $langs->trans("ContactsAddresses");
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/expedition/note.php?id=".$object->origin_id;
	$head[$h][1] = $langs->trans("Notes");
	$head[$h][2] = 'note';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   				to remove a tab
    // complete_head_from_modules  use $object->id for this link so we temporary change it
    $tmpObjectId = $object->id;
    $object->id = $object->origin_id;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery','remove');

	$object->id = $tmpObjectId;
	return $head;
}

/**
 * List sendings and receive receipts
 *
 * @param   string		$origin			Origin ('commande', ...)
 * @param	int			$origin_id		Origin id
 * @param	string		$filter			Filter
 * @return	int							<0 if KO, >0 if OK
 */
function show_list_sending_receive($origin,$origin_id,$filter='')
{
	global $db, $conf, $langs, $bc;
	global $form;

	$product_static=new Product($db);
	$expedition=new Expedition($db);
	$warehousestatic=new Entrepot($db);
	
	$sql = "SELECT obj.rowid, obj.fk_product, obj.label, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked, obj.date_start, obj.date_end,";
	$sql.= " ed.rowid as edrowid, ed.qty as qty_shipped, ed.fk_expedition as expedition_id, ed.fk_origin_line, ed.fk_entrepot as warehouse_id,";
	$sql.= " e.rowid as sendingid, e.ref as exp_ref, e.date_creation, e.date_delivery, e.date_expedition,";
	//if ($conf->livraison_bon->enabled) $sql .= " l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received,";
	$sql.= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tobatch as product_tobatch,';
	$sql.= ' p.description as product_desc';
	$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
	$sql.= ", ".MAIN_DB_PREFIX."expedition as e";
	$sql.= ", ".MAIN_DB_PREFIX.$origin."det as obj";
	//if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
	//TODO Add link to expeditiondet_batch
	$sql.= " WHERE e.entity IN (".getEntity('expedition', 1).")";
	$sql.= " AND obj.fk_".$origin." = ".$origin_id;
	$sql.= " AND obj.rowid = ed.fk_origin_line";
	$sql.= " AND ed.fk_expedition = e.rowid";
	if ($filter) $sql.= $filter;

	$sql.= " ORDER BY obj.fk_product";

	dol_syslog("show_list_sending_receive", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num)
		{
			if ($filter) print load_fiche_titre($langs->trans("OtherSendingsForSameOrder"));
			else print load_fiche_titre($langs->trans("SendingsAndReceivingForSameOrder"));

			print '<table class="liste" width="100%">';
			print '<tr class="liste_titre">';
			//print '<td align="left">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="left">'.$langs->trans("SendingSheet").'</td>';
			print '<td align="left">'.$langs->trans("Description").'</td>';
			print '<td align="center">'.$langs->trans("DateCreation").'</td>';
			print '<td align="center">'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td align="center">'.$langs->trans("QtyPreparedOrShipped").'</td>';
			if (! empty($conf->stock->enabled))
			{
                print '<td>'.$langs->trans("Warehouse").'</td>';
			}
			/*TODO Add link to expeditiondet_batch
			if (! empty($conf->productbatch->enabled))
			{
			    print '<td>';
			    print '</td>';
			}*/
			if (! empty($conf->livraison_bon->enabled))
			{
				print '<td>'.$langs->trans("DeliveryOrder").'</td>';
				//print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
				print '<td align="right">'.$langs->trans("DeliveryDate").'</td>';
			}
			print "</tr>\n";

			$var=True;
			while ($i < $num)
			{
				$var=!$var;
				$objp = $db->fetch_object($resql);
				print "<tr ".$bc[$var].">";

				// Sending id
				print '<td align="left" class="nowrap">';
				print '<a href="'.DOL_URL_ROOT.'/expedition/card.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->exp_ref.'<a>';
				print '</td>';

				// Description
				if ($objp->fk_product > 0)
				{
					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
					{
						$object = new $origin($db);
						$object->fetch($origin_id);
						$object->fetch_thirdparty();

						$prod = new Product($db);
						$prod->id=$objp->fk_product;
						$prod->getMultiLangs();

						$outputlangs = $langs;
						$newlang='';
						if (empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
						if (empty($newlang)) $newlang=$object->thirdparty->default_lang;
						if (! empty($newlang))
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
					}
					else
					{
						$label = (! empty($objp->label)?$objp->label:$objp->product_label);
					}

					print '<td>';

					// Show product and description
					$product_static->type=$objp->fk_product_type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
					$product_static->status_batch=$objp->product_tobatch;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$label;
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					{
						print (! empty($objp->description) && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}

					print '</td>';
				}
				else
				{
					print "<td>";
					if ($objp->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($objp->label)) {
						$text.= ' <strong>'.$objp->label.'</strong>';
						print $form->textwithtooltip($text,$objp->description,3,'','',$i);
					} else {
						print $text.' '.nl2br($objp->description);
					}

					// Show range
					print_date_range($objp->date_start,$objp->date_end);
					print "</td>\n";
				}

				//print '<td align="center">'.$objp->qty_asked.'</td>';

				// Date creation
				print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->date_creation),'day').'</td>';

				// Date shipping creation
				print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->date_delivery),'day').'</td>';

				// Qty shipped
				print '<td align="center">'.$objp->qty_shipped.'</td>';

				// Warehouse
				if (! empty($conf->stock->enabled))
				{
				    print '<td>';
    				if ($objp->warehouse_id > 0)
    				{
        				$warehousestatic->fetch($objp->warehouse_id);
        				print $warehousestatic->getNomUrl(1);
    				}
    				print '</td>';
				}
				
				// Batch number managment
				/*TODO Add link to expeditiondet_batch
				if (! empty($conf->productbatch->enabled))
				{
				    var_dump($objp->edrowid);
				    $lines[$i]->detail_batch
				    if (isset($lines[$i]->detail_batch))
				    {
				        print '<td>';
				        if ($lines[$i]->product_tobatch)
				        {
				            $detail = '';
				            foreach ($lines[$i]->detail_batch as $dbatch)
				            {
				                $detail.= $langs->trans("DetailBatchFormat",$dbatch->batch,dol_print_date($dbatch->eatby,"day"),dol_print_date($dbatch->sellby,"day"),$dbatch->dluo_qty).'<br/>';
				            }
				            print $form->textwithtooltip(img_picto('', 'object_barcode').' '.$langs->trans("DetailBatchNumber"),$detail);
				        }
				        else
				        {
				            print $langs->trans("NA");
				        }
				        print '</td>';
				    } else {
				        print '<td></td>';
				    }
				}*/			
				
				// Informations on receipt
				if (! empty($conf->livraison_bon->enabled))
				{
					include_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
					$expedition->id=$objp->sendingid;
					$expedition->fetchObjectLinked($expedition->id,$expedition->element);
					//var_dump($expedition->linkedObjects);

					$receiving='';
					if (count($expedition->linkedObjects['delivery']) > 0) $receiving=reset($expedition->linkedObjects['delivery']);   // Take first link

					if (! empty($receiving))
					{
						// $expedition->fk_origin_line = id of det line of order
						// $receiving->fk_origin_line = id of det line of order
						// $receiving->origin may be 'shipping'
						// $receiving->origin_id may be id of shipping

						// Ref
						print '<td>';
						print $receiving->getNomUrl($db);
						//print '<a href="'.DOL_URL_ROOT.'/livraison/card.php?id='.$livraison_id.'">'.img_object($langs->trans("ShowReceiving"),'sending').' '.$objp->livraison_ref.'<a>';
						print '</td>';
						// Qty received
						//print '<td align="center">';
						// TODO No solution for the moment to link a line det of receipt with a line det of shipping,
						// so no way to know the qty received for this line of shipping.
						//print $langs->trans("FeatureNotYetAvailable");
						//print '</td>';
						// Date shipping real
						print '<td align="right">';
						print dol_print_date($receiving->date_delivery,'day');
						print '</td>';
					}
					else
					{
						//print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
					}
				}
				print '</tr>';
				$i++;
			}

			print '</table>';
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	return 1;
}

