<?php
/* Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/core/modules/barcode/mod_barcode_product_free.php
 *       \ingroup    barcode
 *       \brief      File of class to manage barcode of products entered freely by the user
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';


/**
 *	Class to manage barcode of products entered freely by the user (no generation, only unicity is checked)
 */
class mod_barcode_product_free extends ModeleNumRefBarCode
{
	public $name = 'Free'; // Model Name

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'


	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_null = 1;	// 1=can be empty
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 0;
		$this->prefixIsRequired = 0;
	}


	/**		Return description of module
	 *
	 * 		@param	Translate 		$langs		Object langs
	 * 		@return string      			Description of module
	 */
	public function info($langs)
	{
		$langs->load("admin");

		return $langs->trans("BarcodeFreeNumRefModelDesc");
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs			Object langs
	 * @param	?CommonObject	$objproduct		Object
	 * @return	string							Return string example
	 */
	public function getExample($langs = null, $objproduct = null)
	{
		return '';
	}

	/**
	 * Return next value. The barcode is entered by the user, so there is no next value.
	 *
	 * @param	?CommonObject	$objproduct 	Object product (not used)
	 * @param	string			$type    		Type of barcode (EAN, ISBN, ...)
	 * @return 	string|int<-1,-1> 				Value if OK, '' if module not configured, <0 if KO
	 */
	public function getNextValue($objproduct = null, $type = '')
	{
		return '';
	}


	/**
	 * 	Check validity of code: it can be empty and must not be used by another product
	 *
	 *	@param	DoliDB		$db					Database handler
	 *	@param	string		$code				Code to check/correct
	 *	@param	Product|Societe	$product		Object product
	 *  @param  int<0,1>  	$thirdparty_type   	0 = customer/prospect , 1 = supplier
	 *  @param	string		$type       	    type of barcode (EAN, ISBN, ...)
	 *  @return int<-7,0>						0 if OK
	 * 											-3 ErrorProductCodeAlreadyUsed
	 * 											-7 ErrorBadClass
	 */
	public function verif($db, &$code, $product, $thirdparty_type, $type)
	{
		if (!$product instanceof Product) {
			dol_syslog(get_class($this)."::verif called with ".get_class($product)." Expected Product", LOG_ERR);
			return -7;
		}

		$result = 0;
		$code = trim($code);
		if ($code !== '' && $this->verif_dispo($db, $code, $product) != 0) {
			$result = -3;
		}

		dol_syslog(get_class($this)."::verif type=".$thirdparty_type." result=".$result);
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return if a code is used (by other element)
	 *
	 *	@param	DoliDB		$db			Handler access base
	 *	@param	string		$code		Code to check
	 *	@param	Product		$product	Object product
	 *	@return	int						0 if available, <0 if KO
	 */
	public function verif_dispo($db, $code, $product)
	{
		// phpcs:enable
		$sql = "SELECT barcode FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE barcode = '".$db->escape($code)."'";
		$sql .= " AND entity IN (".getEntity('product').")";

		if ($product->id > 0) {
			$sql .= " AND rowid <> ".((int) $product->id);
		}

		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) == 0) {
				return 0;
			} else {
				return -1;
			}
		} else {
			return -2;
		}
	}
}
