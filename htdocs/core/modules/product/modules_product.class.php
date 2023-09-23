<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *  \file       htdocs/core/modules/product/modules_product.class.php
 *  \ingroup    contract
 *  \brief      File with parent class for generating products to PDF and File of class to manage product numbering
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFProduct extends CommonDocGenerator
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$dbs     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($dbs, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'product';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($dbs, $type, $maxfilenamelength);
		return $list;
	}
}

/**
 * Class template for classes of numbering product
 */
abstract class ModeleProductCode extends CommonNumRefGenerator
{
	/**
	 *  Return next value available
	 *
	 *	@param	Product		$objproduct		Object product
	 *	@param	int			$type		Type
	 *  @return string      			Value
	 */
	public function getNextValue($objproduct = 0, $type = -1)
	{
		global $langs;
		return $langs->trans("Function_getNextValue_InModuleNotWorking");
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi la liste des modeles de numérotation
	 *
	 *  @param	DoliDB	$dbs     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array|int					List of numbers
	 */
	public static function liste_modeles($dbs, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$list = array();
		$sql = "";

		$resql = $dbs->query($sql);
		if ($resql) {
			$num = $dbs->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $dbs->fetch_row($resql);
				$list[$row[0]] = $row[1];
				$i++;
			}
		} else {
			return -1;
		}
		return $list;
	}

	/**
	 *  Return description of module parameters
	 *
	 *  @param	Translate	$langs      Output language
	 *  @param	Product		$product	Product object
	 *  @param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
	 *  @return	string					HTML translated description
	 */
	public function getToolTip($langs, $product, $type)
	{
		global $conf;

		$langs->loadLangs(array("admin", "companies"));

		$strikestart = '';
		$strikeend = '';
		if (!empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && !empty($this->code_null)) {
			$strikestart = '<strike>';
			$strikeend = '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
		}
		$s = '';
		if ($type == -1) {
			$s .= $langs->trans("Name").': <b>'.$this->getNom($langs).'</b><br>';
			$s .= $langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
		} elseif ($type == 0) {
			$s .= $langs->trans("ProductCodeDesc").'<br>';
		} elseif ($type == 1) {
			$s .= $langs->trans("ServiceCodeDesc").'<br>';
		}
		if ($type != -1) {
			$s .= $langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
		}
		$s .= '<br>';
		$s .= '<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
		if ($type == 0) {
			$s .= $langs->trans("RequiredIfProduct").': '.$strikestart;
			$s .= yn(!$this->code_null, 1, 2).$strikeend;
			$s .= '<br>';
		} elseif ($type == 1) {
			$s .= $langs->trans("RequiredIfService").': '.$strikestart;
			$s .= yn(!$this->code_null, 1, 2).$strikeend;
			$s .= '<br>';
		} elseif ($type == -1) {
			$s .= $langs->trans("Required").': '.$strikestart;
			$s .= yn(!$this->code_null, 1, 2).$strikeend;
			$s .= '<br>';
		}
		$s .= $langs->trans("CanBeModifiedIfOk").': ';
		$s .= yn($this->code_modifiable, 1, 2);
		$s .= '<br>';
		$s .= $langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide, 1, 2).'<br>';
		$s .= $langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
		$s .= '<br>';
		if ($type == 0 || $type == -1) {
			$nextval = $this->getNextValue($product, 0);
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Product").')' : '').': <b>'.$nextval.'</b><br>';
		}
		if ($type == 1 || $type == -1) {
			$nextval = $this->getNextValue($product, 1);
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Service").')' : '').': <b>'.$nextval.'</b>';
		}
		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return	int		0=no, 1=yes
	 */
	public function verif_prefixIsUsed()
	{
		// phpcs:enable
		return 0;
	}
}
