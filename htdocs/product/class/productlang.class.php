<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/product/class/productlang.class.php
 *	\ingroup    produit
 *	\brief      File of the class of a translation of a product (a row of llx_product_lang)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class of a translation of a product (a row of llx_product_lang).
 *
 * The translations themselves are read and written by Product::getMultiLangs(), Product::setMultiLangs() and
 * Product::delMultiLangs(). This class only gives them an object to manage the extrafields of a translation
 * (table llx_product_lang_extrafields) with the common methods of CommonObject.
 */
class ProductLang extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'product';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'product_lang';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_lang';

	/**
	 * @var int<0,1> 0=No test on entity, 1=Test with field entity
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int<0,1> Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var int ID of the product
	 */
	public $fk_product;

	/**
	 * @var string Code of the language (en_US, fr_FR, ...)
	 */
	public $lang;

	/**
	 * @var string Translated label
	 */
	public $label;

	/**
	 * @var string Translated description
	 */
	public $description;

	/**
	 * @var string Translated note
	 */
	public $note;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load a translation of a product from its id, or from the product and the language.
	 * The extrafields are not loaded, use fetch_optionals() for that.
	 *
	 * @param	int		$id				Id of the translation (row of llx_product_lang)
	 * @param	int		$fk_product		Id of the product, used with $lang when $id is not provided
	 * @param	string	$lang			Code of the language, used with $fk_product when $id is not provided
	 * @return	int						Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = 0, $fk_product = 0, $lang = '')
	{
		$sql = "SELECT rowid, fk_product, lang, label, description, note";
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		if ($id > 0) {
			$sql .= " WHERE rowid = ".((int) $id);
		} else {
			$sql .= " WHERE fk_product = ".((int) $fk_product)." AND lang = '".$this->db->escape($lang)."'";
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return 0;
		}

		$this->id = (int) $obj->rowid;
		$this->fk_product = (int) $obj->fk_product;
		$this->lang = $obj->lang;
		$this->label = $obj->label;
		$this->description = $obj->description;
		$this->note = $obj->note;

		return 1;
	}
}
