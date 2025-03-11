<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	    \file       htdocs/core/modules/societe/modules_societe.class.php
 *		\ingroup    societe
 *		\brief      File with parent class of submodules to manage numbering and document generation
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class for third parties models of doc generators
 */
abstract class ModeleThirdPartyDoc extends CommonDocGenerator
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$db                 Database handler
	 *  @param  int<0,max>	$maxfilenamelength  Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'company';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param	Societe		$object				Object source to build document
	 *	@param	Translate	$outputlangs		Lang output object
	 *	@param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	int<0,1>	$hidedetails		Do not show line details
	 *	@param	int<0,1>	$hidedesc			Do not show desc
	 *	@param	int<0,1>	$hideref			Do not show ref
	 *	@return	int<-1,1>						1 if OK, <=0 if KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
	// phpcs:enable
}

/**
 *		Parent class for third parties code generators
 */
abstract class ModeleThirdPartyCode extends CommonNumRefGenerator
{
	/**
	 * Constructor
	 *
	 *  @param DoliDB       $db     Database object
	 */
	abstract public function __construct($db);


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs		Object langs
	 * @param	Societe|string	$objsoc		Object thirdparty
	 * @param	int<-1,2>		$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return string example
	 */
	//abstract public function getExample($langs = null, $objsoc = '', $type = -1);


	/**
	 *  Return next value available
	 *
	 *	@param	Societe|string	$objsoc		Object thirdparty
	 *	@param	int				$type		Type
	 *  @return string      				Value
	 */
	public function getNextValue($objsoc = '', $type = -1)
	{
		global $langs;
		return $langs->trans("Function_getNextValue_InModuleNotWorking");
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$dbs				Database handler
	 *  @param  int<0,max>	$maxfilenamelength	Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
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
	 *  @param	Societe		$soc		Third party object
	 *  @param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
	 *  @return	string					HTML translated description
	 */
	public function getToolTip($langs, $soc, $type)
	{
		$langs->loadLangs(array("admin", "companies"));

		$strikestart = '';
		$strikeend = '';
		if (getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED') && !empty($this->code_null)) {
			$strikestart = '<strike>';
			$strikeend = '</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
		}

		$s = '';
		if ($type == -1) {
			$s .= $langs->trans("Name").': <b>'.$this->getName($langs).'</b><br>';
		} elseif ($type == 0) {
			$s .= $langs->trans("CustomerCodeDesc").'<br>';
		} elseif ($type == 1) {
			$s .= $langs->trans("SupplierCodeDesc").'<br>';
		}
		if ($type != -1) {
			$s .= $langs->trans("ValidityControledByModule").': <b>'.$this->getName($langs).'</b><br>';
		}
		$s .= '<br>';
		$s .= '<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
		if ($type == 0) {
			$s .= $langs->trans("RequiredIfCustomer").': '.$strikestart;
			$s .= yn($this->code_null ? 0 : 1, 1, 2).$strikeend;
			$s .= '<br>';
		} elseif ($type == 1) {
			$s .= $langs->trans("RequiredIfSupplier").': '.$strikestart;
			$s .= yn($this->code_null ? 0 : 1, 1, 2).$strikeend;
			$s .= '<br>';
		} elseif ($type == -1) {
			$s .= $langs->trans("Required").': '.$strikestart;
			$s .= yn($this->code_null ? 0 : 1, 1, 2).$strikeend;
			$s .= '<br>';
		}
		$s .= $langs->trans("CanBeModifiedIfOk").': ';
		$s .= yn($this->code_modifiable, 1, 2);
		$s .= '<br>';
		$s .= $langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide, 1, 2).'<br>';
		$s .= $langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
		$s .= '<br>';
		if ($type == 0 || $type == -1) {
			$nextval = $this->getNextValue($soc, 0);
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Customer").')' : '').': <b>'.$nextval.'</b><br>';
		}
		if ($type == 1 || $type == -1) {
			$nextval = $this->getNextValue($soc, 1);
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Supplier").')' : '').': <b>'.$nextval.'</b>';
		}
		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return    int	    0=no, 1=yes
	 */
	public function verif_prefixIsUsed()
	{
		// phpcs:enable
		return 0;
	}

	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db		Database handler
	 *	@param	string		$code	Code to check/correct
	 *	@param	Societe		$soc	Object third party
	 *  @param  int<0,1>  	$type   0 = customer/prospect , 1 = supplier
	 *  @return int<-6,0>			0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 * 								-5 NotConfigured - Setup empty so any value may be ok or not
	 * 								-6 Other (see this->error)
	 */
	abstract public function verif($db, &$code, $soc, $type);
}


/**
 *		Parent class for third parties accountancy code generators
 */
abstract class ModeleAccountancyCode extends CommonNumRefGenerator
{
	/**
	 * @var string
	 */
	public $code;

	/**
	 *  Return description of module parameters
	 *
	 *  @param	Translate	$langs      Output language
	 *  @param	Societe		$soc		Third party object
	 *  @param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
	 *  @return	string					HTML translated description
	 */
	public function getToolTip($langs, $soc, $type)
	{
		global $db;

		$langs->load("admin");

		$s = '';
		if ($type == -1) {
			$s .= $langs->trans("Name").': <b>'.$this->name.'</b><br>';
			$s .= $langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
		}
		//$s.='<br>';
		//$s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
		$s .= '<br>';
		if ($type == 0 || $type == -1) {
			$result = $this->get_code($db, $soc, 'customer');
			$nextval = $this->code;
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Customer").')' : '').': <b>'.$nextval.'</b><br>';
		}
		if ($type == 1 || $type == -1) {
			$result = $this->get_code($db, $soc, 'supplier');
			$nextval = $this->code;
			if (empty($nextval)) {
				$nextval = $langs->trans("Undefined");
			}
			$s .= $langs->trans("NextValue").($type == -1 ? ' ('.$langs->trans("Supplier").')' : '').': <b>'.$nextval.'</b>';
		}
		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB	$db             Database handler
	 *  @param  Societe	$societe        Third party object
	 *  @param  string	$type			'customer' or 'supplier'
	 *  @return	int<-1,1>				>=0 if success, -1 if failure
	 */
	public function get_code($db, $societe, $type = '')
	{
		// phpcs:enable
		global $langs;

		dol_syslog(get_class($this)."::get_code".$langs->trans("NotAvailable"), LOG_ERR);
		return -1;
	}
}
