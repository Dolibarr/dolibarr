<?php
/* Copyright (C) 2017 Maxime Kohlhaas <support@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *  \file       htdocs/core/modules/expensereport/mod_expensereport_jade.php
 *  \ingroup    expensereport
 *  \brief      File of class to manage expensereport numbering rules Jade
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';

/**
 *	Class to manage expensereport numbering rules Jade
 */
class mod_expensereport_jade extends ModeleNumRefExpenseReport
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefix = 'ER';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Jade';

	/**
	 * @var string model name
	 */
	public $name = 'Jade';


	/**
	 *  Return description of numbering model
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
	}


	/**
	 *  Returns an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object		Object we need next value for
	 *  @return boolean     				false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if ($coyymm && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  Object			$object		Object we need next value for
	 *  @return string|int<0>      			Next value if OK, 0 if KO
	 */
	public function getNextValue($object)
	{
		global $db, $conf;

		// For backward compatibility and restore old behavior to get ref of expense report
		if (getDolGlobalString('EXPENSEREPORT_USE_OLD_NUMBERING_RULE')) {
			$fuser = null;
			if ($object->fk_user_author > 0) {
				$fuser = new User($db);
				$fuser->fetch($object->fk_user_author);
			}

			$expld_car = (!getDolGlobalString('NDF_EXPLODE_CHAR')) ? "-" : $conf->global->NDF_EXPLODE_CHAR;
			$num_car = (!getDolGlobalString('NDF_NUM_CAR_REF')) ? "5" : $conf->global->NDF_NUM_CAR_REF;

			$sql = 'SELECT MAX(de.ref_number_int) as max';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport de';

			$result = $db->query($sql);

			if ($db->num_rows($result) > 0) {
				$objp = $db->fetch_object($result);
				$newref = $objp->max;
				$newref++;
				while (strlen($newref) < $num_car) {
					$newref = "0".$newref;
				}
			} else {
				$newref = 1;
				while (strlen((string) $newref) < $num_car) {
					$newref = "0".$newref;
				}
			}

			$ref_number_int = (int) $newref;

			$user_author_infos = dolGetFirstLastname($fuser->firstname, $fuser->lastname);

			$prefix = "ER";
			if (getDolGlobalString('EXPENSE_REPORT_PREFIX')) {
				$prefix = getDolGlobalString('EXPENSE_REPORT_PREFIX');
			}
			$newref = str_replace(' ', '_', $user_author_infos).$expld_car.$prefix.$newref.$expld_car.dol_print_date($object->date_debut, '%y%m%d');

			$sqlbis = 'UPDATE '.MAIN_DB_PREFIX.'expensereport SET ref_number_int = '.((int) $ref_number_int).' WHERE rowid = '.((int) $object->id);
			$resqlbis = $db->query($sqlbis);
			if (!$resqlbis) {
				dol_print_error($db, $resqlbis);
				exit;
			}

			dol_syslog("mod_expensereport_jade::getNextValue return ".$newref);
			return $newref;
		}

		// First we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " AND entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max);
			} else {
				$max = 0;
			}
		} else {
			dol_syslog("mod_expensereport_jade::getNextValue", LOG_DEBUG);
			return 0;
		}

		$date = $object->date_valid; // $object->date does not exists
		if (empty($date)) {
			$this->error = 'Date valid not defined';
			return 0;
		}

		$yymm = dol_print_date($date, "%y%m");

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04d", $max + 1);
		}

		dol_syslog("mod_expensereport_jade::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
