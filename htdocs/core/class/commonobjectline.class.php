<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/core/class/commonobjectline.class.php
 *  \ingroup    core
 *  \brief      File of the superclass of classes of lines of business objects (invoice, contract, proposal, orders, etc. ...)
 */


/**
 *  Parent class for class inheritance lines of business objects
 *  This class is useless for the moment so no inherit are done on it
 *
 *  TODO For the moment we use the extends on CommonObject until PHP min is 5.4 so we can use Traits.
 */
abstract class CommonObjectLine extends CommonObject
{
	/**
	 * @var string ID to identify parent CommonObject type (element name)
	 */
	public $parent_element = '';

	/**
	 * @var string Attribute related to parent CommonObject rowid (many2one)
	 */
	public $fk_parent_attribute = '';

	/**
	 * Id of the line
	 * @var int
	 */
	public $id;

	/**
	 * Id of the line
	 * @var int
	 * @deprecated Try to use id property as possible (even if field into database is still rowid)
	 * @see $id
	 */
	public $rowid;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'line';

	/**
	 * @var int|null                ID of the unit of measurement (rowid in llx_c_units table)
	 * @see measuringUnitString()
	 * @see getLabelOfUnit()
	 */
	public $fk_unit;

	public $date_debut_prevue;
	public $date_debut_reel;
	public $date_fin_prevue;
	public $date_fin_reel;

	public $weight;
	public $weight_units;
	public $width;
	public $width_units;
	public $height;
	public $height_units;
	public $length;
	public $length_units;
	public $surface;
	public $surface_units;
	public $volume;
	public $volume_units;

	public $multilangs;

	/**
	 * @var int type in line
	 */
	public $product_type;

	/**
	 * @var int product id in line (when line is linked to a product or service)
	 */
	public $fk_product;

	/**
	 * Description of the line
	 * @var string
	 */
	public $desc;

	/**
	 * Description of the line
	 * @var string
	 * @deprecated
	 * @see $desc
	 */
	public $description;

	/**
	 * @var Product Object product to store full product object after a fetch_product() on a line
	 */
	public $product;

	/**
	 * @var string reference in product table
	 */
	public $product_ref;

	/**
	 * @var string label in product table
	 */
	public $product_label;

	/**
	 * @var string barcode in product table
	 */
	public $product_barcode;

	/**
	 * @var string description in product table
	 */
	public $product_desc;

	/**
	 * @var int type in product table
	 */
	public $fk_product_type;

	/**
	 * @var float Quantity
	 */
	public $qty;
	public $duree;
	public $remise_percent;

	/**
	 * List of cumulative options:
	 * Bit 0:	0 for common VAT - 1 if VAT french NPR
	 * Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
	 * @var int
	 */
	public $info_bits;

	/**
	 * @var int special code
	 */
	public $special_code;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;
	public $tva_tx;

	/**
	 * @var int multicurrency id
	 */
	public $fk_multicurrency;

	/**
	 * @var string Multicurrency code
	 */
	public $multicurrency_code;

	/**
	 * @var float Multicurrency subprice
	 */
	public $multicurrency_subprice;

	/**
	 * @var float Multicurrency total without tax
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float Multicurrency total vat
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float Multicurrency total with tax
	 */
	public $multicurrency_total_ttc;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Returns the label, short_label or code found in units dictionary from ->fk_unit.
	 *  A langs->trans() must be called on result to get translated value.
	 *
	 * 	@param	string $type 	Label type ('long', 'short' or 'code'). This can be a translation key.
	 *	@return	string|int 		Return integer <0 if KO, label if OK (Example: 'long', 'short' or 'unitCODE')
	 */
	public function getLabelOfUnit($type = 'long')
	{
		global $langs;

		if (empty($this->fk_unit)) {
			return '';
		}

		$langs->load('products');

		$label_type = 'label';
		if ($type == 'short') {
			$label_type = 'short_label';
		} elseif ($type == 'code') {
			$label_type = 'code';
		}

		$sql = "SELECT ".$label_type.", code from ".$this->db->prefix()."c_units where rowid = ".((int) $this->fk_unit);

		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0 && $res = $this->db->fetch_array($resql)) {
			if ($label_type == 'code') {
				$label = 'unit'.$res['code'];
			} else {
				$label = $res[$label_type];
			}
			$this->db->free($resql);
			return $label;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::getLabelOfUnit Error ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Empty function to prevent errors on call of this function. Must be overload if useful
	 *
	 * @param  string      		$sortorder    	Sort Order
	 * @param  string      		$sortfield    	Sort field
	 * @param  int         		$limit        	Limit the number of lines returned
	 * @param  int         		$offset       	Offset
	 * @param  string|array		$filter       	Filter as an Universal Search string.
	 * 											Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string      		$filtermode   	No more used
	 * @return array|int        	         	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		return 0;
	}
}
