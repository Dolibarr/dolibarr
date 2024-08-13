<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *       \file       htdocs/core/class/commonorder.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of orders classes (customer and supplier)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';

/**
 *      Superclass for orders classes
 */
abstract class CommonOrder extends CommonObject
{
	use CommonIncoterm;


	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs, $conf;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<div class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', 'order');
		$return .= '</div>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'total_ht')) {
			$return .= '<div class="info-box-ref amount">'.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans('HT').'</div>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/** return nb of fines of order where products or services that can be bought
	 *
	 * @param	boolean		$ignoreFree		Ignore free lines
	 * @return	int							number of products or services on buy in a command
	 */
	public function getNbLinesProductOrServiceOnBuy($ignoreFree = false)
	{
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$product = new Product($this->db);
		$return = 0;
		foreach ($this->lines as $line) {
			if (empty($line->fk_product) && !$ignoreFree) {
				$return++;
			} elseif ((int) $line->fk_product > 0) {
				if ($product->fetch($line->fk_product) > 0) {
					if ($product->status_buy) {
						$return++;
					}
				}
			}
		}
		return $return;
	}

	/**
	 * @var string code
	 */
	public $code = "";
}

/**
 *      Superclass for orders classes
 */
abstract class CommonOrderLine extends CommonObjectLine
{
	/**
	 * Custom label of line. Not used by default.
	 * @deprecated
	 */
	public $label;

	/**
	 * Product ref
	 * @var string
	 * @deprecated Use product_ref
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * Product label
	 * @var string
	 * @deprecated Use product_label
	 * @see $product_label
	 */
	public $libelle;

	/**
	 * Product ref
	 * @var string
	 */
	public $product_ref;

	/**
	 * Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Boolean that indicates whether the product is available for sale '1' or not '0'
	 * @var int
	 */
	public $product_tosell = 0;

	/**
	 * Boolean that indicates whether the product is available for purchase '1' or not '0'
	 * @var int
	 */
	public $product_tobuy = 0;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

	/**
	 * Product use lot
	 * @var string
	 */
	public $product_tobatch;

	/**
	 * Product barcode
	 * @var string
	 */
	public $product_barcode;

	/**
	 * Quantity
	 * @var float
	 */
	public $qty;

	/**
	 * Unit price
	 * @deprecated
	 * @see $subprice
	 */
	public $price;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * Type of the product. 0 for product 1 for service
	 * @var int
	 */
	public $product_type = 0;

	/**
	 * Id of corresponding product
	 * @var int
	 */
	public $fk_product;

	/**
	 * Percent line discount
	 * @var float
	 */
	public $remise_percent;

	/**
	 * VAT code
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * VAT %
	 * @var float
	 */
	public $tva_tx;

	/**
	 * Local tax 1 %
	 * @var float
	 */
	public $localtax1_tx;

	/**
	 * Local tax 2 %
	 * @var float
	 */
	public $localtax2_tx;

	public $localtax1_type;
	public $localtax2_type;

	/**
	 * Liste d'options cumulables:
	 * Bit 0:	0 si TVA normal - 1 si TVA NPR
	 * Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
	 * @var int
	 */
	public $info_bits = 0;

	/**
	 * @var int special code
	 */
	public $special_code = 0;

	public $fk_multicurrency;
	public $multicurrency_code;
	public $multicurrency_subprice;
	public $multicurrency_total_ht;
	public $multicurrency_total_tva;
	public $multicurrency_total_ttc;
}
