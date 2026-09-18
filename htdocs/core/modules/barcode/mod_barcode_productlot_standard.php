<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011       Juanjo Menent	        <jmenent@2byte.es>
 * Copyright (C) 2026       Corentin Carayon        <corentin.carayon@atm-consulting.fr>
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
 *       \file       htdocs/core/modules/barcode/mod_barcode_productlot_standard.php
 *       \ingroup    barcode
 *       \brief      File of class to manage lot/serial number barcode numbering with standard rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';


/**
 *	Class to manage lot/serial number barcode with standard rule
 */
class mod_barcode_productlot_standard extends ModeleNumRefBarCode
{
	public $name = 'Standard'; // Model Name

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var ?string
	 */
	public $searchcode; // Search string

	/**
	 * @var ?int
	 */
	public $numbitcounter; // Number of digits in the counter

	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_null = 1;	// A lot without barcode stays valid: generation is non-blocking
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**		Return description of module
	 *
	 * 		@param	Translate 		$langs		Object langs
	 * 		@return string      				Description of module
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
		$texte .= '<input type="hidden" name="param1" value="BARCODE_STANDARD_PRODUCTLOT_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("BarCode"), $langs->transnoentities("BarCode"));
		$tooltip .= $langs->trans("GenericMaskCodes1");
		$tooltip .= $langs->trans("GenericMaskCodes3EAN");
		$tooltip .= '<strong>'.$langs->trans("Example").':</strong><br>';
		$tooltip .= '05{0000000000}? (for internal use)<br>';

		// Mask parameter
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value1" value="'.getDolGlobalString('BARCODE_STANDARD_PRODUCTLOT_MASK').'"'.$disabled.'>', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';
		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.$langs->trans("Modify").'"'.$disabled.'></td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs		Object langs
	 * @param	?CommonObject	$objlot		Object lot/serial number
	 * @return	string						Return string example
	 */
	public function getExample($langs = null, $objlot = null)
	{
		if (!$langs instanceof Translate) {
			$langs = $GLOBALS['langs'];
			'@phan-var-force Translate $langs';
		}
		$examplebarcode = $this->getNextValue($objlot, '');
		if (!$examplebarcode) {
			$examplebarcode = $langs->trans('NotConfigured');
		}
		if ($examplebarcode == "ErrorBadMask") {
			$langs->load("errors");
			$examplebarcode = $langs->trans($examplebarcode);
		}

		return $examplebarcode;
	}

	/**
	 *  Return literal barcode type code from numerical rowid type of barcode
	 *
	 *	@param	DoliDB	$db         Database
	 *  @param  int  	$type       Type of barcode (EAN, ISBN, ...) as rowid
	 *  @return string
	 */
	public function literalBarcodeType($db, $type = 0)
	{
		global $conf;
		$out = '';

		$sql = "SELECT rowid, code, libelle as label";
		$sql .= " FROM ".$db->prefix()."c_barcode_type";
		$sql .= " WHERE rowid = ".((int) $type);
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				$out = $obj->label; // Take the label corresponding to the type rowid in the database
			}
			$db->free($resql);
		} else {
			dol_syslog(get_class($this)."::literalBarcodeType ".$db->lasterror(), LOG_ERR);
		}

		return $out;
	}

	/**
	 * Return next value
	 *
	 * @param	?CommonObject	$objlot		Object lot/serial number
	 * @param	string			$type		Type of barcode (EAN, ISBN, ...) as rowid
	 * @return 	string|int<-1,-1>			Value if OK, '' if module not configured, <0 if KO
	 */
	public function getNextValue($objlot = null, $type = '')
	{
		global $db;

		if (is_object($objlot) && !$objlot instanceof Productlot) {
			dol_syslog(get_class($this)."::getNextValue used on incompatible ".get_class($objlot), LOG_ERR);
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php'; // to be able to call function barcode_gen_ean_sum($ean)

		// Get barcode type configuration for lots if $type not set
		if (empty($type)) {
			$type = getDolGlobalString('PRODUCTLOT_DEFAULT_BARCODE_TYPE');
		}

		// Get Mask value
		$mask = getDolGlobalString('BARCODE_STANDARD_PRODUCTLOT_MASK');

		if (empty($mask)) {
			$this->error = 'NotConfigured';
			return '';
		}

		$field = 'barcode';
		$where = '';

		$now = dol_now();

		$numFinal = get_next_value($db, $mask, 'product_lot', $field, $where, '', $now);

		// Begin barcode with key: for barcode with key (EAN13...) calculate and substitute the last character (* or ?) used in the mask by the key
		if ((substr($numFinal, -1) == '*') or (substr($numFinal, -1) == '?')) {
			$literaltype = $this->literalBarcodeType($db, (int) $type);
			switch ($literaltype) {
				case 'EAN13': // EAN13 rowid = 2
					if (strlen($numFinal) == 13) { // be sure that the mask length is correct for EAN13
						$ean = substr($numFinal, 0, 12); // take first 12 digits
						$eansum = barcode_gen_ean_sum($ean);
						$ean .= $eansum; // substitute the last character by the key
						$numFinal = $ean;
					}
					break;
					// Other barcode cases with key could be written here
				default:
					break;
			}
		}
		// End barcode with key

		return $numFinal;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db					Database handler
	 *	@param	string		$code				Code to check/correct
	 *	@param	Productlot	$lot				Object lot/serial number
	 *  @param  int<0,1>  	$thirdparty_type   	Not used here, kept for signature compatibility
	 *  @param	string		$type       	    Literal type of barcode (EAN13, ISBN, ...)
	 *  @return int<-7,0>						0 if OK
	 * 											-1 ErrorBadBarCodeSyntax
	 * 											-3 ErrorBarCodeAlreadyUsed
	 * 											-7 ErrorBadClass
	 */
	public function verif($db, &$code, $lot, $thirdparty_type, $type)
	{
		if (!$lot instanceof Productlot) {
			dol_syslog(get_class($this)."::verif called with ".get_class($lot)." Expected Productlot", LOG_ERR);
			return -7;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$result = 0;
		$code = strtoupper(trim($code));

		// No -2 branch here: code_null is 1, an empty barcode is always accepted
		if (empty($code)) {
			$result = 0;
		} elseif ($this->verif_syntax($code, $type) < 0) {
			$result = -1;
		} elseif ($this->verif_dispo($db, $code, $lot) != 0) {
			$result = -3;
		}

		dol_syslog(get_class($this)."::verif result=".$result);

		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return if a code is used (by another lot/serial number)
	 *
	 *	@param	DoliDB		$db			Handler access base
	 *	@param	string		$code		Code to check
	 *	@param	Productlot	$lot		Object lot/serial number
	 *	@return	int						0 if available, <0 if KO
	 */
	public function verif_dispo($db, $code, $lot)
	{
		// phpcs:enable
		$sql = "SELECT barcode FROM ".$db->prefix()."product_lot";
		$sql .= " WHERE barcode = '".$db->escape($code)."'";
		$sql .= " AND entity IN (".getEntity('product_lot').")";

		if ($lot->id > 0) {
			$sql .= " AND rowid <> ".((int) $lot->id);
		}

		$resql = $db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this)."::verif_dispo ".$db->lasterror(), LOG_ERR);
			return -2;
		}

		$result = ($db->num_rows($resql) == 0) ? 0 : -1;
		$db->free($resql);

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return if a barcode value match syntax
	 *
	 *	@param	string	$codefortest	Code to check syntax
	 *  @param	string	$typefortest	Literal type of barcode (EAN13, ISBN, ...)
	 *	@return	int						0 if OK, <0 if KO
	 */
	public function verif_syntax($codefortest, $typefortest)
	{
		// phpcs:enable
		// Get Mask value
		$mask = getDolGlobalString('BARCODE_STANDARD_PRODUCTLOT_MASK');
		if (!$mask) {
			$this->error = 'NotConfigured';
			return -1;
		}

		dol_syslog(get_class($this).'::verif_syntax codefortest='.$codefortest." typefortest=".$typefortest);

		$newcodefortest = $codefortest;

		// Special case, if mask is on 12 digits instead of 13, we remove last char into code to test
		if (in_array($typefortest, array('EAN13', 'ISBN'))) {	// We remove the CRC char not included into mask
			$reg = array();
			if (preg_match('/\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}/i', $mask, $reg)) {
				if (strlen($reg[1]) == 12) {
					$newcodefortest = substr($newcodefortest, 0, 12);
				}
				dol_syslog(get_class($this).'::verif_syntax newcodefortest='.$newcodefortest);
			}
		}

		$result = check_value($mask, $newcodefortest);
		if (is_string($result)) {
			$this->error = $result;
			return -1;
		}

		return $result;
	}
}
