<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2023 Frederic France      <frederic.france@netlogic.fr>
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
	public $boxcode = "lastproducts";
	public $boximg = "object_product";
	public $boxlabel = "BoxLastProducts";
	public $depends = array("produit");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));
		$tmpentry = array('enabled'=>(isModEnabled("product") || isModEnabled("service")), 'perms'=>($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire')), 'module'=>'product|service');
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

		$this->info_box_head = array(
			'text' => $langs->trans("BoxTitleLastProducts", $max).'<a class="paddingleft" href="'.DOL_URL_ROOT.'/product/list.php?sortfield=p.tms&sortorder=DESC"><span class="badge">...</span></a>',
		);

		if ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire')) {
			$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.price_base_type, p.price_ttc, p.fk_product_type, p.tms, p.tosell, p.tobuy, p.fk_price_expression, p.entity";
			$sql .= ", p.accountancy_code_sell";
			$sql .= ", p.accountancy_code_sell_intra";
			$sql .= ", p.accountancy_code_sell_export";
			$sql .= ", p.accountancy_code_buy";
			$sql .= ", p.accountancy_code_buy_intra";
			$sql .= ", p.accountancy_code_buy_export";
			$sql .= ', p.barcode';
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql .= ' WHERE p.entity IN ('.getEntity($productstatic->element).')';
			if (!$user->hasRight('produit', 'lire')) {
				$sql .= ' AND p.fk_product_type != 0';
			}
			if (!$user->hasRight('service', 'lire')) {
				$sql .= ' AND p.fk_product_type != 1';
			}
			// Add where from hooks
			if (is_object($hookmanager)) {
				$parameters = array('boxproductlist' => 1, 'boxcode' => $this->boxcode);
				$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $productstatic); // Note that $action and $object may have been modified by hook
				$sql .= $hookmanager->resPrint;
			}
			$sql .= $this->db->order('p.datec', 'DESC');
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$line = 0;
				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datem = $this->db->jdate($objp->tms);

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
					$productstatic->status = $objp->tosell;
					$productstatic->status_buy = $objp->tobuy;
					$productstatic->barcode = $objp->barcode;
					$productstatic->accountancy_code_sell = $objp->accountancy_code_sell;
					$productstatic->accountancy_code_sell_intra = $objp->accountancy_code_sell_intra;
					$productstatic->accountancy_code_sell_export = $objp->accountancy_code_sell_export;
					$productstatic->accountancy_code_buy = $objp->accountancy_code_buy;
					$productstatic->accountancy_code_buy_intra = $objp->accountancy_code_buy_intra;
					$productstatic->accountancy_code_buy_export = $objp->accountancy_code_buy_export;
					$productstatic->date_modification = $datem;

					$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('product', 'product_advance', 'read_prices') : $user->hasRight('product', 'read');
					if ($productstatic->isService()) {
						$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('service', 'service_advance', 'read_prices') : $user->hasRight('service', 'read');
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax100 maxwidth100onsmartphone"',
						'text' => $productstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax100 maxwidth100onsmartphone"',
						'text' => $objp->label,
					);
					$price = '';
					$price_base_type = '';
					if ($usercancreadprice) {
						if (!isModEnabled('dynamicprices') || empty($objp->fk_price_expression)) {
							$price_base_type = $langs->trans($objp->price_base_type);
							$price = ($objp->price_base_type == 'HT') ? price($objp->price) : $price = price($objp->price_ttc);
						} else {
							//Parse the dynamic price
							$productstatic->fetch($objp->rowid, '', '', 1);

							require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
							$priceparser = new PriceParser($this->db);
							$price_result = $priceparser->parseProduct($productstatic);
							if ($price_result >= 0) {
								if ($objp->price_base_type == 'HT') {
									$price_base_type = $langs->trans("HT");
								} else {
									$price_result = $price_result * (1 + ($productstatic->tva_tx / 100));
									$price_base_type = $langs->trans("TTC");
								}
								$price = price($price_result);
							}
						}
					}
					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => $price,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowrap"',
						'text' => $price_base_type,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, 'day', 'tzuserrel'),
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
						'text'=>$langs->trans("NoRecordedProducts"),
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
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
	 *  Method to show box
	 *
	 *  @param	array	$head       Array with properties of box title
	 *  @param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *  @return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
