<?php
/* Copyright (C) 2018	   Quentin Vial-Gouteyron    <quentin.vial-gouteyron@atm-consulting.fr>
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
 *  \file       htdocs/core/modules/reception/mod_reception_beryl.php
 *  \ingroup    reception
 *  \brief      File of class to manage shipments numbering rules Beryl
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';

/**
 *	Class to manage reception numbering rules Beryl
 */
class mod_reception_beryl extends ModelNumRefReception
{
	public $version = 'dolibarr';
	public $prefix = 'RCP';
	public $error = '';
	public $nom = 'Beryl';


	/**
	 *	Return default description of numbering model
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
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *	Test if existing numbers make problems with numbering
	 *
	 *	@param	CommonObject	$object	Object we need next value for
	 *  @return boolean     			false if KO (there is a conflict), true if OK
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."reception";
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
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc		Third party object
	 *	@param	Reception	$reception	Reception object
	 *	@return string|int<-1,0>		Value if OK, -1 if KO
	 */
	public function getNextValue($objsoc, $reception)
	{
		global $db, $conf;

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."reception";
		$sql .= " WHERE ref like '".$db->escape($this->prefix)."____-%'";
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
			dol_syslog("mod_reception_beryl::getNextValue", LOG_DEBUG);
			return -1;
		}

		$date = time();
		$yymm = dol_print_date($date, "%y%m");

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04d", $max + 1);
		}

		dol_syslog("mod_reception_beryl::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
