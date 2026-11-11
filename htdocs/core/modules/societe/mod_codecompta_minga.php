<?php

/* Copyright (C) 2026		Joachim Kueter			<joachim.kueter@bloxera.com>
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
 *	\file       htdocs/core/modules/societe/mod_codecompta_minga.php
 *	\ingroup    societe
 *	\brief      File of class to manage accountancy code of thirdparties with Minga rules
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *	Class to manage accountancy code of thirdparties with Minga rules.
 *	Codes are numeric and taken from a range of numbers (one range for customers, one for suppliers),
 *	like the debtor (10000-69999) and creditor (70000-99999) accounts of the German charts SKR03/SKR04.
 */
class mod_codecompta_minga extends ModeleAccountancyCode
{
	/**
	 * @var string model name
	 */
	public $name = 'Minga';

	/**
	 * @var string
	 */
	public $code;

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var int
	 */
	public $position = 40;


	/**
	 * @var 'code'|'next'|'random'|'fixed' Method used to choose the number in the range
	 */
	public $method;

	/**
	 * @var array<'customer'|'supplier',array{start:int,end:int}> Range of numbers for each type of thirdparty
	 */
	public $ranges = array();

	/**
	 * @var int Number of last digits of the customer/supplier code used by method 'code', 0 for all
	 */
	public $nbdigits;

	/**
	 * @var string[] List of methods
	 */
	const METHODS = array('code', 'next', 'random', 'fixed');

	/**
	 * Max number of random picks before scanning the range for a free number
	 */
	const RANDOM_TRIES = 50;


	/**
	 * 	Constructor
	 */
	public function __construct()
	{
		$this->ranges = array(
			'customer' => array(
				'start' => $this->cleanNumber(getDolGlobalString('COMPANY_MINGA_CUSTOMER_START'), 10000),
				'end' => $this->cleanNumber(getDolGlobalString('COMPANY_MINGA_CUSTOMER_END'), 69999),
			),
			'supplier' => array(
				'start' => $this->cleanNumber(getDolGlobalString('COMPANY_MINGA_SUPPLIER_START'), 70000),
				'end' => $this->cleanNumber(getDolGlobalString('COMPANY_MINGA_SUPPLIER_END'), 99999),
			),
		);

		$method = getDolGlobalString('COMPANY_MINGA_METHOD', 'code');
		$this->method = in_array($method, self::METHODS) ? $method : 'code';

		// Hidden option to use only the last digits of codes made of a date and a counter without separator (CU26070435 with 4 gives 435)
		$this->nbdigits = max(0, getDolGlobalInt('COMPANY_MINGA_CODE_LAST_DIGITS'));
	}

	/**
	 * Return the number made of the digits of a string, or the default value if there is no digit
	 *
	 * @param	string	$value		Value to clean
	 * @param	int		$default	Default value
	 * @return	int					Number
	 */
	private function cleanNumber($value, $default)
	{
		$value = preg_replace('/\D/', '', $value);
		return ($value === '' ? $default : (int) $value);
	}


	/**
	 * Return description of module
	 *
	 * @param	Translate	$langs		Object langs
	 * @return	string   		   		Description of module
	 */
	public function info($langs)
	{
		global $form;

		$langs->loadLangs(array("admin", "companies"));

		$texte = $langs->trans("ModuleCompanyCode".$this->name)."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="COMPANY_MINGA_CUSTOMER_START">';
		$texte .= '<input type="hidden" name="param2" value="COMPANY_MINGA_CUSTOMER_END">';
		$texte .= '<input type="hidden" name="param3" value="COMPANY_MINGA_SUPPLIER_START">';
		$texte .= '<input type="hidden" name="param4" value="COMPANY_MINGA_SUPPLIER_END">';
		$texte .= '<input type="hidden" name="param5" value="COMPANY_MINGA_METHOD">';
		$texte .= '<table class="nobordernopadding centpercent">';
		$texte .= '<tr><td>';
		$s1 = '<input type="text" class="flat width75" name="value1" value="'.$this->ranges['customer']['start'].'">';
		$s2 = '<input type="text" class="flat width75" name="value2" value="'.$this->ranges['customer']['end'].'">';
		$s3 = '<input type="text" class="flat width75" name="value3" value="'.$this->ranges['supplier']['start'].'">';
		$s4 = '<input type="text" class="flat width75" name="value4" value="'.$this->ranges['supplier']['end'].'">';
		// trans remove html entities
		$texte .= $langs->trans("ModuleCompanyCodeCustomer".$this->name, '{s1}', '{s2}')."<br>\n";
		$texte .= $langs->trans("ModuleCompanyCodeSupplier".$this->name, '{s3}', '{s4}')."<br>\n";
		$texte = str_replace(array('{s1}', '{s2}', '{s3}', '{s4}'), array($s1, $s2, $s3, $s4), $texte);

		$arrayofmethods = array();
		foreach (self::METHODS as $method) {
			$arrayofmethods[$method] = $langs->trans("AccountancyCodeMingaMethod".ucfirst($method));
		}
		$texte .= $form->textwithpicto($langs->trans("AccountancyCodeMingaMethod"), $langs->trans("AccountancyCodeMingaMethodHelp"), 1, 'help', '', 1).' ';
		$texte .= $form->selectarray('value5', $arrayofmethods, $this->method, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
		if ($this->nbdigits > 0) {
			$texte .= "<br>\n".$langs->trans('COMPANY_MINGA_CODE_LAST_DIGITS').' = '.$this->nbdigits."\n";
		}
		$texte .= '</td>';
		$texte .= '<td class="right"><input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.$langs->trans("Modify").'"></td>';
		$texte .= '</tr></table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	?Translate		$langs		Object langs
	 * @param	Societe|string	$objsoc		Object thirdparty
	 * @param	int<-1,2>		$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string						Return string example
	 */
	public function getExample($langs = null, $objsoc = '', $type = -1)
	{
		$examples = array();
		foreach (array('customer' => 'CU2607-0435', 'supplier' => 'SU2607-0012') as $typetouse => $codeexample) {
			$start = $this->ranges[$typetouse]['start'];
			$end = $this->ranges[$typetouse]['end'];
			if ($this->method == 'code') {
				$examples[] = $codeexample.' &rarr; '.($start + (int) $this->getNumberFromCode($codeexample));
			} elseif ($this->method == 'next') {
				$examples[] = $start.', '.($start + 1).', ...';
			} elseif ($this->method == 'random') {
				$examples[] = $start.' ... '.$end;
			} else {
				$examples[] = (string) $start;
			}
		}

		return implode("<br>\n", $examples);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB		$db             Database handler
	 *  @param  ?Societe	$societe		Third party object
	 *  @param  'customer'|'supplier'|''	$type	'customer' or 'supplier'
	 *  @return	int							>=0 if OK, <0 if KO
	 */
	public function get_code($db, $societe, $type = '')
	{
		// phpcs:enable
		global $langs;

		$this->db = $db;
		$this->code = '';

		if ($type != 'customer' && $type != 'supplier') {
			$this->error = 'Bad value for parameter type';
			return -1;
		}

		$start = $this->ranges[$type]['start'];
		$end = $this->ranges[$type]['end'];

		dol_syslog(__METHOD__." search code for type=".$type." method=".$this->method." range=".$start."-".$end." company=".(!empty($societe->name) ? $societe->name : ''));

		// Same account for all thirdparties of this type (collective account), so no check if it is already used
		if ($this->method == 'fixed') {
			$this->code = (string) $start;
			dol_syslog(__METHOD__." found code=".$this->code);
			return 1;
		}

		$code = -1;
		if ($start <= $end) {
			$used = $this->getUsedNumbers($db, $start, $end);
			if (!is_array($used)) {
				return -1;
			}

			if ($this->method == 'code') {
				$number = $this->getNumberFromCode(is_object($societe) ? (string) ($type == 'customer' ? $societe->code_client : $societe->code_fournisseur) : '');
				if ($number !== '' && $start + (int) $number <= $end && !isset($used[$start + (int) $number])) {
					$code = $start + (int) $number;
				} else {
					// Derived numbers grow from the start of the range, so the fallback is taken from the end to avoid future collisions
					$code = $this->findFreeNumber($used, $start, $end, $end, -1);
				}
			} elseif ($this->method == 'random') {
				for ($i = 0; $i < self::RANDOM_TRIES && $code < 0; $i++) {
					$candidate = random_int($start, $end);
					if (!isset($used[$candidate])) {
						$code = $candidate;
					}
				}
				if ($code < 0) {
					$code = $this->findFreeNumber($used, $start, $end, random_int($start, $end), 1);
				}
			} else {
				// Lowest free number of the range, so numbers already used further in the range (set manually, fallbacks of method 'code', ...) do not move the sequence
				$code = $this->findFreeNumber($used, $start, $end, $start, 1);
			}
		}

		if ($code < 0) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorNoFreeAccountancyCodeInRange", $start, $end);
			dol_syslog(__METHOD__." ".$this->error, LOG_WARNING);
			return -1;
		}

		$this->code = (string) $code;
		dol_syslog(__METHOD__." found code=".$this->code);
		return 1;
	}

	/**
	 * Return the last group of digits of a thirdparty code ('CU2607-0435' gives '435'),
	 * limited to its last $this->nbdigits digits if set
	 *
	 * @param	string	$code	Customer or supplier code
	 * @return	string			Number without leading zeros, '' if the code contains no digit
	 */
	public function getNumberFromCode($code)
	{
		$reg = array();
		if (preg_match('/(\d+)\D*$/', $code, $reg)) {
			return (string) ((int) ($this->nbdigits > 0 ? substr($reg[1], -$this->nbdigits) : $reg[1]));
		}
		return '';
	}

	/**
	 * Load the numbers of the range already used as customer or supplier accountancy code.
	 * Both columns are checked because customer and supplier accounts share the same numbering in accounting software (DATEV, ...).
	 *
	 * @param	DoliDB	$db		Database handler
	 * @param	int		$start	Start of range
	 * @param	int		$end	End of range
	 * @return	array<int,int>|int<-1,-1>	Array with used numbers as keys, -1 if error
	 */
	private function getUsedNumbers($db, $start, $end)
	{
		if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
			$table = 'societe_perentity';
			$fields = array('accountancy_code_customer', 'accountancy_code_supplier');
		} else {
			$table = 'societe';
			$fields = array('code_compta', 'code_compta_fournisseur');
		}

		$used = array();
		foreach ($fields as $field) {
			$sql = "SELECT ".$db->sanitize($field)." as code FROM ".MAIN_DB_PREFIX.$table;
			$sql .= " WHERE entity IN (".getEntity('societe').")";
			if (strlen((string) $start) == strlen((string) $end)) {
				// Strings of digits with the same length sort like numbers. Other values are filtered below.
				$sql .= " AND ".$db->sanitize($field)." >= '".$db->escape((string) $start)."'";
				$sql .= " AND ".$db->sanitize($field)." <= '".$db->escape((string) $end)."'";
			} else {
				$sql .= " AND ".$db->sanitize($field)." IS NOT NULL";
			}

			$resql = $db->query($sql);
			if (!$resql) {
				$this->error = $db->lasterror();
				return -1;
			}
			while ($obj = $db->fetch_object($resql)) {
				$code = trim((string) $obj->code);
				if (ctype_digit($code) && (int) $code >= $start && (int) $code <= $end) {
					$used[(int) $code] = 1;
				}
			}
			$db->free($resql);
		}

		return $used;
	}

	/**
	 * Return the first free number of the range, starting at $from and moving in direction $step.
	 * When a bound of the range is reached, the search continues from the other bound.
	 *
	 * @param	array<int,int>	$used	Array with used numbers as keys
	 * @param	int				$min	Start of range
	 * @param	int				$max	End of range
	 * @param	int				$from	Number to start the search from
	 * @param	int<-1,1>		$step	1 to search upward, -1 to search downward
	 * @return	int						Free number, -1 if the range is full
	 */
	private function findFreeNumber($used, $min, $max, $from, $step)
	{
		$candidate = $from;
		// Each used number moves the candidate by one, so count($used) + 1 tries are enough to find a free number if there is one
		$nbtries = min(count($used), $max - $min) + 1;
		for ($i = 0; $i < $nbtries; $i++) {
			if (!isset($used[$candidate])) {
				return $candidate;
			}
			$candidate += $step;
			if ($candidate > $max) {
				$candidate = $min;
			} elseif ($candidate < $min) {
				$candidate = $max;
			}
		}
		return -1;
	}
}
