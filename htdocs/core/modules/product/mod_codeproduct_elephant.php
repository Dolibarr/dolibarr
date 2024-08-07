<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/core/modules/product/mod_codeproduct_elephant.php
 *       \ingroup    product
 *       \brief      File of class to manage product code with elephant rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';


/**
 *  Class to manage product code with elephant rule
 */
class mod_codeproduct_elephant extends ModeleProductCode
{
	// variables inherited from ModelProductCode class
	public $name = 'Elephant';
	public $version = 'dolibarr';

	// variables not inherited

	/**
	 *  @var string			String de recherche
	 */
	public $searchcode;

	/**
	 *  @var int			Nombre de chiffres du compteur
	 */
	public $numbitcounter;


	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_null = 0;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**
	 *  Return description of module
	 *
	 *  @param	Translate	$langs		Object langs
	 *  @return string      			Description of module
	 */
	public function info($langs)
	{
		global $conf, $mc;
		global $form;

		$langs->load("products");

		$disabled = ((!empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity) ? ' disabled' : '');

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="PRODUCT_ELEPHANT_MASK_PRODUCT">';
		$texte .= '<input type="hidden" name="param2" value="PRODUCT_ELEPHANT_MASK_SERVICE">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Product"), $langs->transnoentities("Product"));
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4c");
		$tooltip .= $langs->trans("GenericMaskCodes5");
		//$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5b");

		// Parametrage du prefix customers
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("ProductCodeModel").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value1" value="'.(getDolGlobalString('PRODUCT_ELEPHANT_MASK_PRODUCT') ? $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT : '').'"'.$disabled.'>', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.$langs->trans("Modify").'"'.$disabled.'></td>';

		$texte .= '</tr>';

		// Parametrage du prefix suppliers
		$texte .= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("ServiceCodeModel").'):</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value2" value="'.(getDolGlobalString('PRODUCT_ELEPHANT_MASK_SERVICE') ? $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE : '').'"'.$disabled.'>', $tooltip, 1, 1).'</td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs		Object langs
	 * @param	Product|string	$objproduct	Object product
	 * @param	int<-1,2>		$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return string example
	 */
	public function getExample($langs = null, $objproduct = '', $type = -1)
	{
		if (!$langs instanceof Translate) {
			$langs = $_GLOBAL['langs'];
			'@phan-var-force Translate $langs';
		}
		$exampleproduct = $exampleservice = '';

		if ($type == 0 || $type == -1) {
			$exampleproduct = $this->getNextValue($objproduct, 0);
			if (!$exampleproduct) {
				$exampleproduct = $langs->trans('NotConfigured');
			}
			if ($exampleproduct == "ErrorBadMask") {
				$langs->load("errors");
				$exampleproduct = $langs->trans($exampleproduct);
			}
		}
		if ($type == 1 || $type == -1) {
			$exampleservice = $this->getNextValue($objproduct, 1);
			if (!$exampleservice) {
				$exampleservice = $langs->trans('NotConfigured');
			}
			if ($exampleservice == "ErrorBadMask") {
				$langs->load("errors");
				$exampleservice = $langs->trans($exampleservice);
			}
		}

		if ($type == 0) {
			return $exampleproduct;
		}
		if ($type == 1) {
			return $exampleservice;
		}
		return $exampleproduct.'<br>'.$exampleservice;
	}

	/**
	 * Return next value
	 *
	 * @param	Product		$objproduct     Object product
	 * @param  	int		    $type       Produit ou service (0:product, 1:service)
	 * @return 	string|-1      			Value if OK, '' if module not configured, -1 if KO
	 */
	public function getNextValue($objproduct = null, $type = -1)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// Get Mask value
		$mask = '';
		if ($type == 0 && getDolGlobalString('PRODUCT_ELEPHANT_MASK_PRODUCT')) {
			$mask = getDolGlobalString('PRODUCT_ELEPHANT_MASK_PRODUCT');
		} elseif ($type == 1 && getDolGlobalString('PRODUCT_ELEPHANT_MASK_SERVICE')) {
			$mask = getDolGlobalString('PRODUCT_ELEPHANT_MASK_SERVICE');
		}

		if (empty($mask)) {
			$this->error = 'NotConfigured';
			return '';
		}

		$field = '';
		$where = '';
		if ($type == 0) {
			$field = 'ref';
			//$where = ' AND client in (1,2)';
		} elseif ($type == 1) {
			$field = 'ref';
			//$where = ' AND fournisseur = 1';
		} else {
			return -1;
		}

		$now = dol_now();

		if (getDolGlobalString('PRODUCT_ELEPHANT_ADD_WHERE')) {
			$where = ' AND ('.dol_string_nospecial(dol_string_unaccent($conf->global->PRODUCT_ELEPHANT_ADD_WHERE), '_', array(',', '@', '"', "|", ";", ":")).')';
		}

		$numFinal = get_next_value($db, $mask, 'product', $field, $where, '', $now);

		return  $numFinal;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return	int			0 or 1
	 */
	public function verif_prefixIsUsed()
	{
		// phpcs:enable
		global $conf;

		$mask = getDolGlobalString('PRODUCT_ELEPHANT_MASK_PRODUCT');
		if (preg_match('/\{pre\}/i', $mask)) {
			return 1;
		}

		$mask = getDolGlobalString('PRODUCT_ELEPHANT_MASK_SERVICE');
		if (preg_match('/\{pre\}/i', $mask)) {
			return 1;
		}

		return 0;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db			Database handler
	 *	@param	string		$code		Code to check/correct
	 *	@param	Product		$product	Object product
	 *  @param  int		  	$type   	0 = product , 1 = service
	 *  @return int						0 if OK
	 * 									-1 ErrorBadCustomerCodeSyntax
	 * 									-2 ErrorCustomerCodeRequired
	 * 									-3 ErrorCustomerCodeAlreadyUsed
	 * 									-4 ErrorPrefixRequired
	 * 									-5 Other (see this->error)
	 */
	public function verif($db, &$code, $product, $type)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$result = 0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && !getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED')) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null || getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED'))) {
			$result = -2;
		} else {
			// Get Mask value
			$mask = '';
			if ($type == 0) {
				$mask = !getDolGlobalString('PRODUCT_ELEPHANT_MASK_PRODUCT') ? '' : $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
			}
			if ($type == 1) {
				$mask = !getDolGlobalString('PRODUCT_ELEPHANT_MASK_SERVICE') ? '' : $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;
			}
			if (!$mask) {
				$this->error = 'NotConfigured';
				return -5;
			}

			$result = check_value($mask, $code);
			if (is_string($result)) {
				$this->error = $result;
				return -5;
			}
		}

		dol_syslog("mod_codeclient_elephant::verif type=".$type." result=".$result);
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Indicate if the code is available or not (by another third party)
	 *
	 *  @param	DoliDB		$db			Handler access base
	 *  @param	string		$code		Code a verifier
	 *  @param	Product		$product		Object product
	 *  @return	int						0 if available, <0 if KO
	 */
	public function verif_dispo($db, $code, $product)
	{
		// phpcs:enable
		$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE ref = '".$db->escape($code)."'";
		if ($product->id > 0) {
			$sql .= " AND rowid <> ".$product->id;
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
