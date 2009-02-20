<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/includes/boxes/box_produits.php
 *	\ingroup    produits,services
 *	\brief      Module de generation de l'affichage de la box produits
 *	\version	$Id$
 */

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");
include_once(DOL_DOCUMENT_ROOT."/product.class.php");


class box_produits extends ModeleBoxes {

	var $boxcode="lastproducts";
	var $boximg="object_product";
	var $boxlabel;
	var $depends = array("produit");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_produits()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("BoxLastProducts");
	}

	/**
	 *      \brief      Charge les donn�es en m�moire pour affichage ult�rieur
	 *      \param      $max        Nombre maximum d'enregistrements � charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;
		
		include_once(DOL_DOCUMENT_ROOT."/product.class.php");
		$productstatic=new Product($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastProducts",$max));

		if ($user->rights->produit->lire)
		{
			$sql = "SELECT p.rowid, p.label, p.price, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.envente";
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			if ($conf->categorie->enabled && !$user->rights->categorie->voir)
			{
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
				$sql.= " WHERE IFNULL(c.visible,1)=1";
			}
			$sql .= " ORDER BY p.datec DESC";
			$sql .= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$datem=$db->jdate($obj->tms);

					// Multilangs
					if ($conf->global->MAIN_MULTILANGS) // si l'option est active
					{
						$sqld = "SELECT label FROM ".MAIN_DB_PREFIX."product_det";
						$sqld.= " WHERE fk_product=".$objp->rowid." AND lang='". $langs->getDefaultLang() ."'";
						$sqld.= " LIMIT 1";

						$resultd = $db->query($sqld);
						if ($resultd)
						{
							$objtp = $db->fetch_object($resultd);
							if ($objtp->label != '') $objp->label = $objtp->label;
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

					$this->info_box_contents[$i][3] = array('td' => 'align="center" width="20" nowrap="nowrap"',
                    'text' => $price_base_type);

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
                    'text' => dol_print_date($datem,'day'));

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
                    'text' => $productstatic->LibStatut($objp->envente,3));

					$i++;
				}
			}
			else {
				dol_print_error($db);
			}
		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
	 
}

?>
