<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2024-2025 MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Frédéric France      <frederic.france@free.fr>
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
	 * @var ?int		ID of the unit of measurement (rowid in llx_c_units table)
	 * @see measuringUnitString()
	 * @see getLabelOfUnit()
	 */
	public $fk_unit;

	/**
	 * @var int|''
	 */
	public $date_debut_prevue;
	/**
	 * @var int|''
	 */
	public $date_debut_reel;
	/**
	 * @var int|''
	 */
	public $date_fin_prevue;
	/**
	 * @var int|''
	 */
	public $date_fin_reel;


	/**
	 * @var float|string
	 */
	public $weight;

	/**
	 * @var int|string
	 */
	public $weight_units;	// scale -3, 0, 3, 6
	/**
	 * @var float|string
	 */
	public $length;
	/**
	 * @var int|string
	 */
	public $length_units;	// scale -3, 0, 3, 6
	/**
	 * @var float|string
	 */
	public $width;
	/**
	 * @var int|string
	 */
	public $width_units;	// scale -3, 0, 3, 6
	/**
	 * @var float|string|null
	 */
	public $height;
	/**
	 * @var int|string|null
	 */
	public $height_units;	// scale -3, 0, 3, 6
	/**
	 * @var float|string|null
	 */
	public $surface;
	/**
	 * @var int|string|null
	 */
	public $surface_units;	// scale -3, 0, 3, 6
	/**
	 * @var float|string|null
	 */
	public $volume;
	/**
	 * @var int|string|null
	 */
	public $volume_units;	// scale -3, 0, 3, 6
	/**
	 * @var ?array<string,array<string,string>>
	 */
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
	/**
	 * @var int
	 */
	public $duree;
	/**
	 * @var float|string
	 */
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

	/**
	 * Unit price including taxes
	 * @var float
	 */
	public $subprice_ttc;

	/**
	 * @var float|string
	 */
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
	 * @var float Multicurrency subprice without taxes
	 */
	public $multicurrency_subprice;

	/**
	 * @var float Multicurrency subprice including taxes
	 */
	public $multicurrency_subprice_ttc;

	/**
	 * @var float Multicurrency total without tax
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float Multicurrency total vat
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float|string Multicurrency total localtax1
	 */
	public $multicurrency_total_localtax1;	// not in database

	/**
	 * @var float|string Multicurrency total localtax2
	 */
	public $multicurrency_total_localtax2;	// not in database

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
	 * Reads the units dictionary to return the translation code of a unit (if type='code'), or translated long label (if type='long') or short label (if type='short').
	 * TODO Duplicate of getLabelOfUnit() in product.class.php
	 *
	 * @param  	string 			$type 			Code type ('code', 'long' or 'short')
	 * @param	Translate|null	$outputlangs	Language to use for long label translation
	 * @param	int				$noentities		No entities
	 * @return 	string|int 						Return integer <0 if KO, code or label of unit if OK.
	 */
	public function getLabelOfUnit($type = 'long', $outputlangs = null, $noentities = 0)
	{
		global $langs;

		if (empty($this->fk_unit)) {
			return '';
		}
		if (empty($outputlangs)) {
			$outputlangs = $langs;
		}

		$outputlangs->load('products');
		$label = '';

		$sql = "SELECT code, label, short_label FROM ".$this->db->prefix()."c_units where rowid = ".((int) $this->fk_unit);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->error();
			dol_syslog(get_class($this)."::getLabelOfUnit Error ".$this->error, LOG_ERR);
			return -1;
		} elseif ($this->db->num_rows($resql) > 0 && $res = $this->db->fetch_array($resql)) {
			if ($type == 'short') {
				if ($noentities) {
					$label = $outputlangs->transnoentitiesnoconv($res['short_label']);
				} else {
					$label = $outputlangs->trans($res['short_label']);
				}
			} elseif ($type == 'code') {
				$label = $res['code'];
			} else {
				if ($outputlangs->trans('unit'.$res['code']) == 'unit'.$res['code']) {
					// No translation available
					$label = $res['label'];
				} else {
					// Return the translated value
					if ($noentities) {
						$label = $outputlangs->transnoentitiesnoconv('unit'.$res['code']);
					} else {
						$label = $outputlangs->trans('unit'.$res['code']);
					}
				}
			}
		}
		$this->db->free($resql);

		return $label;
	}

	/**
	 * Empty function to prevent errors on call of this function. Must be overload if useful
	 *
	 * @param  string      		$sortorder    	Sort Order
	 * @param  string      		$sortfield    	Sort field
	 * @param  int         		$limit        	Limit the number of lines returned
	 * @param  int         		$offset       	Offset
	 * @param  string|string[]	$filter       	Filter as an Universal Search string.
	 * 											Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string      		$filtermode   	No more used
	 * @return self[]|int<-1,-1>        	         	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		return -1;  // NOK because nothing done.
	}

	/**
	 * Return clickable link of object line (optionally with picto)
	 * May (should) also return information about the associated "parent" object.
	 * To overload
	 *
	 * @param      int			$withpicto                Add picto into link
	 * @return     string          			          String with URL
	 */
	public function getNomUrl($withpicto = 0)
	{
		$parentattribute = $this->fk_parent_attribute;

		/*
		if ($parentattribute) {
			return 'Parent #'.$this->$parentattribute.' - Line #'.$this->id;
		} else {
			return 'Line #'.$this->id;
		}
		*/

		$parent_element_properties = getElementProperties($this->parent_element);
		$parent_classname = $parent_element_properties['classname'];
		$parent_element = new $parent_classname($this->db);
		/** @var CommonObject $parent_element */
		$parentattribute = $this->fk_parent_attribute;
		if ($parentattribute && method_exists($parent_element, 'fetch')) {
			$parent_element->fetch($this->$parentattribute); // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
		}

		return $parent_element->getNomUrl($withpicto).' - Line #'.$this->id; // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
	}
}
