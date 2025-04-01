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
	 * Constructor
	 */
	public function __construct()
	{
		global $conf, $mysoc;
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
		return "2025VT0001";
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
		global $conf, $langs, $db;

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
	 *  @param  BookKeeping	$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, -1 if KO
	 */
	public function getNextValue(BookKeeping $object)
	{
		global $db, $conf;

		$prefix = $this->getPrefix($object);
		$posindice = strlen($prefix) + 1;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM {$db->prefix()}accounting_bookkeeping";
		$sql .= " WHERE ref LIKE '{$db->escape($prefix)}%'";
		$sql .= " AND entity = ".getEntity($object->element);


		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			dol_syslog("mod_bookkeeping_argon::getNextValue", LOG_DEBUG);
			return -1;
		}
		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04d", $max + 1);
		}

		dol_syslog("mod_bookkeeping_argon::getNextValue return {$prefix}{$num}");
		return "{$prefix}{$num}";
	}

	/**
	 * Returns the prefix for current Bookkeeping object
	 * Year used in prefix is the beginning fiscal year.
	 *
	 * @param BookKeeping $object	Book keeping record
	 * @return string Prefix for this bookkeeping object
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
