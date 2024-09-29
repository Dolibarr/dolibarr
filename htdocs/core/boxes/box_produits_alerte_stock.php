<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005-2012 Maxime Kohlhaas      <mko@atm-consulting.fr>
 * Copyright (C) 2015-2021 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
	public $boxcode = "productsalertstock";
	public $boximg = "object_product";
	public $boxlabel = "BoxProductsAlertStock";
	public $depends = array("produit");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;

		$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));
		$tmpentry = array('enabled' => ((isModEnabled("product") || isModEnabled("service")) && isModEnabled('stock')), 'perms' => $user->hasRight('stock', 'lire'), 'module' => 'product|service|stock');
		$showmode = isVisibleToUserType(($user->socid > 0 ? 1 : 0), $tmpentry, $listofmodulesforexternal);
		$this->hidden = ($showmode != 1);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf, $hookmanager;

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$productstatic = new Product($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleProductsAlertStock", $max));

		if (($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire')) && $user->hasRight('stock', 'lire')) {
			$sql = "SELECT p.rowid, p.label, p.price, p.ref, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy, p.barcode, p.seuil_stock_alerte, p.entity,";
			$sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,";
			$sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,";
			$sql .= " SUM(".$this->db->ifsql("s.reel IS NULL", "0", "s.reel").") as total_stock";
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as s on p.rowid = s.fk_product";
			$sql .= ' WHERE p.entity IN ('.getEntity($productstatic->element).')';
			$sql .= " AND p.seuil_stock_alerte > 0";
			if (!$user->hasRight('produit', 'lire')) {
				$sql .= ' AND p.fk_product_type <> 0';
			}
			if (!$user->hasRight('service', 'lire')) {
				$sql .= ' AND p.fk_product_type <> 1';
			}
			// Add where from hooks
			if (is_object($hookmanager)) {
				$parameters = array('boxproductalertstocklist' => 1, 'boxcode' => $this->boxcode);
				$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $productstatic); // Note that $action and $object may have been modified by hook
				$sql .= $hookmanager->resPrint;
			}
			$sql .= " GROUP BY p.rowid, p.ref, p.label, p.price, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy, p.barcode, p.seuil_stock_alerte, p.entity,";
			$sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,";
			$sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export";
			$sql .= " HAVING SUM(".$this->db->ifsql("s.reel IS NULL", "0", "s.reel").") < p.seuil_stock_alerte";
			$sql .= $this->db->order('p.seuil_stock_alerte', 'DESC');
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$langs->load("stocks");
				$num = $this->db->num_rows($result);
				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datem = $this->db->jdate($objp->tms);
					$price = '';
					$price_base_type = '';

					// Multilangs
					if (getDolGlobalInt('MAIN_MULTILANGS')) { // si l'option est active
						$sqld = "SELECT label";
						$sqld .= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sqld .= " WHERE fk_product = ".((int) $objp->rowid);
						$sqld .= " AND lang = '".$this->db->escape($langs->getDefaultLang())."'";
						$sqld .= " LIMIT 1";

						$resultd = $this->db->query($sqld);
						if ($resultd) {
							$objtp = $this->db->fetch_object($resultd);
							if (isset($objtp->label) && $objtp->label != '') {
								$objp->label = $objtp->label;
							}
						}
					}
					$productstatic->id = $objp->rowid;
					$productstatic->ref = $objp->ref;
					$productstatic->type = $objp->fk_product_type;
					$productstatic->label = $objp->label;
					$productstatic->entity = $objp->entity;
					$productstatic->barcode = $objp->barcode;
					$productstatic->status = $objp->tosell;
					$productstatic->status_buy = $objp->tobuy;
					$productstatic->accountancy_code_sell = $objp->accountancy_code_sell;
					$productstatic->accountancy_code_sell_intra = $objp->accountancy_code_sell_intra;
					$productstatic->accountancy_code_sell_export = $objp->accountancy_code_sell_export;
					$productstatic->accountancy_code_buy = $objp->accountancy_code_buy;
					$productstatic->accountancy_code_buy_intra = $objp->accountancy_code_buy_intra;
					$productstatic->accountancy_code_buy_export = $objp->accountancy_code_buy_export;

					$this->info_box_contents[$line][] = array(
						'td' => '',
						'text' => $productstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax100 maxwidth150onsmartphone"',
						'text' => $objp->label,
					);

					if (!isModEnabled('dynamicprices') || empty($objp->fk_price_expression)) {
						$price_base_type = $langs->trans($objp->price_base_type);
						$price = ($objp->price_base_type == 'HT') ? price($objp->price) : $price = price($objp->price_ttc);
					} else { //Parse the dynamic price
						$productstatic->fetch($objp->rowid, '', '', 1);

						require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
						$priceparser = new PriceParser($this->db);
						$price_result = $priceparser->parseProduct($productstatic);
						if ($price_result >= 0) {
							if ($objp->price_base_type == 'HT') {
								$price_base_type = $langs->trans("HT");
							} else {
								$price_result *= (1 + ($productstatic->tva_tx / 100));
								$price_base_type = $langs->trans("TTC");
							}
							$price = price($price_result);
						}
					}

					/*$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => $price.' '.$price_base_type,
					);*/

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall"',
						'text' => price2num($objp->total_stock, 'MS').' / '.$objp->seuil_stock_alerte,
						'text2' => img_warning($langs->transnoentitiesnoconv("StockLowerThanLimit", $objp->seuil_stock_alerte)),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => '<span class="statusrefsell">'.$productstatic->LibStatut($objp->tosell, 3, 0).'</span>',
						'asis' => 1
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => '<span class="statusrefbuy">'.$productstatic->LibStatut($objp->tobuy, 3, 1).'</span>',
						'asis' => 1
					);

					$line++;
				}
				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoTooLowStockProducts"),
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}



	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
