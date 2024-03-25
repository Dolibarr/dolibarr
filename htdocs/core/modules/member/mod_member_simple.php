<?php
/* Copyright (C) 2021		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2022-2024	Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/member/mod_member_simple.php
 *	\ingroup    member
 *	\brief      File with class to manage the numbering module Simple for member references
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_member.class.php';


/**
 * 	Class to manage the numbering module Simple for member references
 */
class mod_member_simple extends ModeleNumRefMembers
{

	// variables inherited from ModeleNumRefMembers class
	public $name = 'Simple';
	public $version = 'dolibarr';

	// variables not inherited

	/**
	 * @var string
	 */
	public $prefix = '';

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
		return $langs->trans("SimpleRefNumRefModelDesc");
	}


	/**
	 *  Return an example of numbering module values
	 *
	 * 	@return     string      Example
	 */
	public function getExample()
	{
		return "1";
	}


	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *	@param	CommonObject	$object	Object we need next value for
	 *  @return boolean     			false if KO (there is a conflict), true if OK
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$sql = "SELECT MAX(CAST(ref AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
		$sql .= " WHERE entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$coyymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if (!$coyymm || preg_match('/[0-9][0-9][0-9][0-9]/i', $coyymm)) {
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
	 *  @param  Adherent	$object		Object we need next value for
	 *  @return	string|-1				Value if OK, -1 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $conf, $db;

		// the ref of a member is the rowid
		$sql = "SELECT MAX(CAST(ref AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
		$sql .= " WHERE entity = ".(int) $conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$max = intval($obj->max) + 1;
			} else {
				$max = 1;
			}
		} else {
			dol_syslog("mod_member_simple::getNextValue", LOG_DEBUG);
			return -1;
		}
		$max = str_pad((string) $max, getDolGlobalInt('MEMBER_MOD_SIMPLE_LPAD'), "0", STR_PAD_LEFT);
		return $max;
	}
}
