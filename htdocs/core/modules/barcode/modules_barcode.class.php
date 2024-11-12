<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   \file       htdocs/core/modules/barcode/modules_barcode.class.php
 *   \ingroup    barcode
 *   \brief      File with parent classes for barcode document modules and numbering modules
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class for barcode document generators (image)
 *
 *	@property 'development'|'experimental'|'dolibarr' $version Dolibarr version of loaded document
 */
abstract class ModeleBarCode
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * Return if a model can be used or not
	 *
	 * @return		boolean     true if model can be used
	 */
	public function isEnabled()
	{
		return true;
	}


	/**
	 *  Return description
	 *
	 *  @param  Translate   $langs      Lang object to use for output
	 *  @return string                  Descriptive text
	 */
	abstract public function info($langs);


	/**
	 *	Save an image file on disk (with no output)
	 *
	 *	@param	   string	    $code		      Value to encode
	 *	@param	   string	    $encoding	      Mode of encoding ('QRCODE', 'EAN13', ...)
	 *	@param	   string	    $readable	      Code can be read
	 *	@param	   integer		$scale			  Scale (not used with this engine)
	 *  @param     integer      $nooutputiferror  No output if error (not used with this engine)
	 *	@return	   int			                  Return integer <0 if KO, >0 if OK
	 */
	public function writeBarCode($code, $encoding, $readable = 'Y', $scale = 1, $nooutputiferror = 0)
	{
		return -1;	// Error by default, this method must be implemented by the driver
	}

	/**
	 *  Return true if encoding is supported
	 *
	 *  @param  string  $encoding       Encoding norm
	 *  @return int                     >0 if supported, 0 if not
	 */
	abstract public function encodingIsSupported($encoding);
}


/**
 *	Parent class for barcode numbering models
 *
 *	@property string $nom Name for the GeneratorModel
 */
abstract class ModeleNumRefBarCode extends CommonNumRefGenerator
{
	// variables inherited from CommonNumRefGenerator
	/**
	 * @var int<0,1>
	 */
	public $code_null;


	/**
	 *  Return next value available
	 *
	 *	@param	?CommonObject	$objcommon	Object Product, Thirdparty
	 *	@param	string			$type		Type of barcode (EAN, ISBN, ...)
	 *  @return string						Value
	 */
	public function getNextValue($objcommon = null, $type = '')
	{
		global $langs;
		return $langs->trans("Function_getNextValue_InModuleNotWorking");
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param   ?Translate		$langs			Object langs
	 * @param   ?CommonObject	$object			Object product
	 * @return  string							Return string example
	 */
	abstract public function getExample($langs = null, $object = null);


	/**
	 *      Return description of module parameters
	 *
	 *      @param	Translate	$langs      Output language
	 *		@param	?Societe	$soc		Third party object
	 *		@param	int			$type		-1=Nothing, 0=Product, 1=Service
	 *		@return	string					HTML translated description
	 */
	public function getToolTip($langs, $soc, $type)
	{
		$langs->loadLangs(array("admin", "companies"));

		$s = '';
		$s .= $langs->trans("Name").': <b>'.$this->name.'</b><br>';
		$s .= $langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
		if ($type != -1) {
			$s .= $langs->trans("ValidityControledByModule").': <b>'.$this->getName($langs).'</b><br>';
		}
		$s .= '<br>';
		$s .= '<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
		if ($type == 0) {
			$s .= $langs->trans("RequiredIfProduct").': ';
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '<strike>';
			}
			$s .= yn($this->code_null ? 0 : 1, 1, 2);
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
			}
			$s .= '<br>';
		}
		if ($type == 1) {
			$s .= $langs->trans("RequiredIfService").': ';
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '<strike>';
			}
			$s .= yn($this->code_null ? 0 : 1, 1, 2);
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
			}
			$s .= '<br>';
		}
		if ($type == -1) {
			$s .= $langs->trans("Required").': ';
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '<strike>';
			}
			$s .= yn($this->code_null ? 0 : 1, 1, 2);
			if (getDolGlobalString('MAIN_BARCODE_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
				$s .= '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
			}
			$s .= '<br>';
		}
		/*$s.=$langs->trans("CanBeModifiedIfOk").': ';
		$s.=yn($this->code_modifiable,1,2);
		$s.='<br>';
		$s.=$langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide,1,2).'<br>';
		*/
		$s .= $langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
		$s .= '<br>';

		$nextval = $this->getNextValue($soc, '');
		if (empty($nextval)) {
			$nextval = $langs->trans("Undefined");
		}
		$s .= $langs->trans("NextValue").': <b>'.$nextval.'</b><br>';

		return $s;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db					Database handler
	 *	@param	string		$code				Code to check/correct
	 *	@param	Product|Societe	$object		Object product or ThirdParty
	 *  @param  int<0,1>  	$thirdparty_type   	0 = customer/prospect , 1 = supplier
	 *  @param	string		$type       	    type of barcode (EAN, ISBN, ...)
	 *  @return int<-7,0>						0 if OK
	 * 											-1 ErrorBadCustomerCodeSyntax
	 * 											-2 ErrorCustomerCodeRequired
	 * 											-3 ErrorCustomerCodeAlreadyUsed
	 * 											-4 ErrorPrefixRequired
	 * 											-7 ErrorBadClass
	 */
	abstract public function verif($db, &$code, $object, $thirdparty_type, $type);
}
