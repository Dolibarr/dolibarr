<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/core/boxes/box_produits.php
 *	\ingroup    produits,services
 *	\brief      Module to generate box of last products/services
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 * Class to manage the box to show last products
 */
class box_produits extends ModeleBoxes
{
	var $boxcode="lastproducts";
	var $boximg="object_product";
	var $boxlabel;
	var $depends = array("produit");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
     *  Constructor
	 */
	function __construct()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->transnoentitiesnoconv("BoxLastProducts");
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$productstatic=new Product($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastProducts",$max));

		if ($user->rights->produit->lire || $user->rights->service->lire)
		{
			$sql = "SELECT p.rowid, p.label, p.price, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql.= ' WHERE p.entity IN ('.getEntity($productstatic->element, 1).')';
			if (empty($user->rights->produit->lire)) $sql.=' AND p.fk_product_type != 0';
			if (empty($user->rights->service->lire)) $sql.=' AND p.fk_product_type != 1';
			$sql.= $db->order('p.datec', 'DESC');
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
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

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => ($objp->fk_product_type==1?'object_service':'object_product'),
                    'url' => DOL_URL_ROOT."/product/fiche.php?id=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->label,
                    'url' => DOL_URL_ROOT."/product/fiche.php?id=".$objp->rowid);

					if ($objp->price_base_type == 'HT')
					{
						$price=price($objp->price);
						$price_base_type=$langs->trans("HT");
					}
					else
					{
						$price=price($objp->price_ttc);
						$price_base_type=$langs->trans("TTC");
					}
					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
                    'text' => $price);

					$this->info_box_contents[$i][3] = array('td' => 'align="left" nowrap="nowrap"',
                    'text' => $price_base_type);

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
                    'text' => dol_print_date($datem,'day'));

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
                    'text' => $productstatic->LibStatut($objp->tosell,3,0));

                    $this->info_box_contents[$i][6] = array('td' => 'align="right" width="18"',
                    'text' => $productstatic->LibStatut($objp->tobuy,3,1));

                    $i++;
				}
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedProducts"));
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
