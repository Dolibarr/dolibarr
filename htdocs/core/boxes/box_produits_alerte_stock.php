<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005-2012 Maxime Kohlhaas      <mko@atm-consulting.fr>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
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
 *	\file       htdocs/core/boxes/box_produits_alerte_stock.php
 *	\ingroup    produits
 *	\brief      Module to generate box of products with too low stock
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 * Class to manage the box to show too low stocks products
 */
class box_produits_alerte_stock extends ModeleBoxes
{
	var $boxcode="productsalertstock";
	var $boximg="object_product";
	var $boxlabel="BoxProductsAlertStock";
	var $depends = array("produit");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
	    global $conf,$user;

	    $this->db = $db;

	    $listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
	    $tmpentry=array('enabled'=>((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && ! empty($conf->stock->enabled)), 'perms'=>($user->rights->stock->lire), 'module'=>'product|service|stock');
	    $showmode=isVisibleToUserType(($user->societe_id > 0 ? 1 : 0), $tmpentry, $listofmodulesforexternal);
	    $this->hidden=($showmode != 1);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf, $hookmanager;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$productstatic=new Product($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleProductsAlertStock",$max));

		if (($user->rights->produit->lire || $user->rights->service->lire) && $user->rights->stock->lire)
		{
			$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy, p.seuil_stock_alerte, p.entity,";
			$sql.= " SUM(".$db->ifsql("s.reel IS NULL","0","s.reel").") as total_stock";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as s on p.rowid = s.fk_product";
			$sql.= ' WHERE p.entity IN ('.getEntity($productstatic->element).')';
			$sql.= " AND p.tosell = 1 AND p.seuil_stock_alerte > 0";
			if (empty($user->rights->produit->lire)) $sql.=' AND p.fk_product_type != 0';
			if (empty($user->rights->service->lire)) $sql.=' AND p.fk_product_type != 1';
			// Add where from hooks
    		if (is_object($hookmanager))
    		{
			    $parameters=array('boxproductalertstocklist'=>1);
    		    $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
    		    $sql.=$hookmanager->resPrint;
    		}
		    $sql.= " GROUP BY p.rowid, p.ref, p.label, p.price, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy, p.seuil_stock_alerte, p.entity";
			$sql.= " HAVING SUM(".$db->ifsql("s.reel IS NULL","0","s.reel").") < p.seuil_stock_alerte";
			$sql.= $db->order('p.seuil_stock_alerte', 'DESC');
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$langs->load("stocks");
				$num = $db->num_rows($result);
				$line = 0;
                while ($line < $num) {
					$objp = $db->fetch_object($result);
					$datem=$db->jdate($objp->tms);

					// Multilangs
					if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
					{
						$sqld = "SELECT label";
						$sqld.= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sqld.= " WHERE fk_product=".$objp->rowid;
						$sqld.= " AND lang='". $langs->getDefaultLang() ."'";
						$sqld.= " LIMIT 1";

						$resultd = $db->query($sqld);
						if ($resultd)
						{
							$objtp = $db->fetch_object($resultd);
							if (isset($objtp->label) && $objtp->label != '')
								$objp->label = $objtp->label;
						}
					}
                    $productstatic->id = $objp->rowid;
                    $productstatic->ref = $objp->ref;
                    $productstatic->type = $objp->fk_product_type;
                    $productstatic->label = $objp->label;
					$productstatic->entity = $objp->entity;

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $productstatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $objp->label,
                    );

                    if (empty($conf->dynamicprices->enabled) || empty($objp->fk_price_expression))
                    {
                        $price_base_type=$langs->trans($objp->price_base_type);
                        $price=($objp->price_base_type == 'HT')?price($objp->price):$price=price($objp->price_ttc);
	                }
	                else //Parse the dynamic price
	               	{
						$productstatic->fetch($objp->rowid, '', '', 1);
	                    $priceparser = new PriceParser($this->db);
	                    $price_result = $priceparser->parseProduct($productstatic);
	                    if ($price_result >= 0) {
							if ($objp->price_base_type == 'HT')
							{
								$price_base_type=$langs->trans("HT");
							}
							else
							{
								$price_result = $price_result * (1 + ($productstatic->tva_tx / 100));
								$price_base_type=$langs->trans("TTC");
							}
							$price=price($price_result);
	                    }
	               	}

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => $price,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="nowrap"',
                        'text' => $price_base_type,
                    );

					$this->info_box_contents[$line][] = array(
					    'td' => 'align="center"',
                    'text' => $objp->total_stock . ' / '.$objp->seuil_stock_alerte,
					'text2'=>img_warning($langs->transnoentitiesnoconv("StockLowerThanLimit", $objp->seuil_stock_alerte)));

					$this->info_box_contents[$line][] = array(
					    'td' => 'align="right" width="18"',
                        'text' => '<span class="statusrefsell">'.$productstatic->LibStatut($objp->tosell,3,0).'<span>',
					    'asis' => 1
					);

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => '<span class="statusrefbuy">'.$productstatic->LibStatut($objp->tobuy,3,0).'<span>',
                        'asis' => 1
                    );

                    $line++;
                }
                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'align="center"',
                        'text'=>$langs->trans("NoTooLowStockProducts"),
                    );

				$db->free($result);
			}
			else
			{
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
			}
		}
		else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left" class="nohover opacitymedium"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

