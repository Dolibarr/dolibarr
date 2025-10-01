<?php
/* Copyright (C) 2025	   Jean-Rémi Taponier   <jean-remi@netlogic.fr>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/modules/accountancy/mod_bookkeeping_argon.php
 *  \ingroup    accountancy
 *  \brief      File of class to manage Bookkeeping numbering rules Argon
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/accountancy/modules_accountancy.php';

/**
 *	Class to manage Bookkeeping numbering rules Argon
 */
class mod_bookkeeping_argon extends ModeleNumRefBookkeeping
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string name
	 */
	public $name = 'Argon';

	/**
	 * @var int	position
	 */
	public $position = 50;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Nothing
	}

	/**
	 *  Return description of numbering module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs): string
	{
		global $langs;
		return $langs->trans("BookkeepingNumRefModelDesc");
	}


	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample(): string
	{
		return "2501VT00001";
	}


	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object		Object we need next value for
	 *  @return boolean     				false if conflict, true if ok
	 */
	public function canBeActivated($object): bool
	{
		global $langs;

		$max = '';

		if (get_class($object) !== 'BookKeeping') {
			return false;
		}

		$prefix = $this->getPrefix($object);
		// If prefix size is not 7, prefix is not correct (YYYYCCC)
		if (!empty($prefix) || strlen($prefix) !== 7) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	BookKeeping	$object		Object we need next value for
	 * 	@param  string		$mode		'next' for next value or 'last' for last value
	 *  @return string|int<-1,0>		Value if OK, -1 if KO
	 */
	public function getNextValue(BookKeeping $object, $mode = 'next')
	{
		global $conf, $db;

		// Get mask
		$mask = '{yy}{mm}{jj}{00000@99}';

		$where = '';

		// Get entities
		//$entity = getEntity('invoicenumber', 1, $object);
		$entity = $conf->entity;	// In accountancy, we never share entities
		$numFinal = get_next_value($db, $mask, 'accounting_bookkeeping', 'ref', $where, null, $object->doc_date, $mode, false, null, (string) $entity, $object);
		if (!preg_match('/([0-9])+/', $numFinal)) {
			$this->error = $numFinal;
		}

		return $numFinal;
	}

	/**
	 * Returns the prefix for current Bookkeeping object
	 * Year used in prefix is the beginning fiscal year.
	 *
	 * @param 	BookKeeping $object		Book keeping record
	 * @return 	string 					Prefix for this bookkeeping object
	 */
	private function getPrefix(BookKeeping $object): string
	{
		$fiscalStartMonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$docYear = (int) dol_print_date($object->doc_date, '%Y');
		$docMonth = (int) dol_print_date($object->doc_date, '%m');
		$docFiscalYear = $docMonth < $fiscalStartMonth ? ($docYear - 1) : $docYear;
		return $docFiscalYear .  str_pad($object->code_journal, 3, "0", STR_PAD_LEFT);
	}
}
