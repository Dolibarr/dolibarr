<?php
/* Copyright (C) 2021		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2022-2024	Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/member/mod_member_advanced.php
 *	\ingroup    member
 *	\brief      File with class to manage the numbering module Advanced for member references
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_member.class.php';


/**
 * 	Class to manage the numbering module Advanced for member references
 */
class mod_member_advanced extends ModeleNumRefMembers
{
	// variables inherited from ModeleNumRefMembers class
	public $name = 'Advanced';
	public $version = 'dolibarr';

	// variables not inherited

	/**
	 *  @var string
	 */
	public $prefix = 'MEM';

	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$this->code_auto = 1;
	}

	/**
	 *  Return description of numbering module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		return $langs->trans("AdvancedNumRefModelDesc", $this->prefix);
	}


	/**
	 *  Return an example of numbering module values
	 *
	 * 	@return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."2301-0001";
	}


	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object	Object we need next value for
	 *  @return boolean     			false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
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
		if (!$coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm)) {
			return true;
		} else {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}


	/**
	 *  Return next value
	 *
	 *  @param  Societe		$objsoc		Object third party
	 *  @param  ?Adherent	$object		Object we need next value for
	 *  @return	string|int<-1,0>		Value if OK, -1 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db, $conf;

		// First, we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
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
			dol_syslog("mod_member_advanced::getNextValue", LOG_DEBUG);
			return -1;
		}

		$date = empty($object->datec) ? dol_now() : $object->datec;

		$yymm = dol_print_date($date, '%y%m', 'gmt');

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04d", $max + 1);
		}

		dol_syslog("mod_member_advanced::getNextValue return ".$this->prefix.$yymm."-".$num, LOG_INFO);
		return $this->prefix.$yymm."-".$num;
	}
}
