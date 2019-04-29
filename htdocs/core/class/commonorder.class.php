<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/class/commonorder.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of orders classes (customer and supplier)
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';

/**
 *      Superclass for orders classes
 */
abstract class CommonOrder extends CommonObject
{

}

/**
 *      Superclass for orders classes
 */
abstract class CommonOrderLine extends CommonObjectLine
{
	/**
	 * Product ref
	 * @var string
	 * @deprecated Use product_ref
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * Product ref
	 * @var string
	 */
	public $product_ref;

	/**
	 * Product label
	 * @var string
	 * @deprecated Use product_label
	 * @see $product_label
	 */
	public $libelle;

	/**
	 * Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

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
	 * Description of the line
	 * @var string
	 */
	public $desc;

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

	public $special_code = 0;
}
