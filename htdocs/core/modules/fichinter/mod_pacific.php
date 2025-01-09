<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/core/modules/fichinter/mod_pacific.php
 *  \ingroup    Intervention card
 *  \brief      File with Pacific numbering module for interventions
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';

/**
 *	Class to manage numbering of intervention cards with rule Pacific.
 */
class mod_pacific extends ModeleNumRefFicheinter
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefix = 'FI';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated Use $name, getName()
	 * @see $name
	 */
	public $nom = 'pacific';

	/**
	 * @var string model name
	 */
	public $name = 'pacific';


	/**
	 *  Return description of numbering module
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
	 *  Return an example of numbering
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
		global $langs, $conf, $db;

		$langs->load("bills");

		$fayymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql .= " WHERE entity = ".$conf->entity;

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			if ($row) {
				$fayymm = substr($row[0], 0, 6);
				$max = $row[0];
			}
		}
		if (!$fayymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $fayymm)) {
			return true;
		} else {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe|string		$objsoc     Object thirdparty
	 *  @param  Fichinter|string	$object		Object we need next value for
	 *	@return string|int<-1,0>    			Next value if OK, <=0 if KO
	 */
	public function getNextValue($objsoc = '', $object = '')
	{
		global $db, $conf;

		// First, we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter";
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
		}

		//$date=time();
		$date = '';
		if (!empty($object->datec)) {
			$date = $object->datec;
		}
		$yymm = dol_print_date($date, "%y%m");

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		} else {
			$num = sprintf("%04d", $max + 1);
		}

		return $this->prefix.$yymm."-".$num;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	Fichinter	$objforref	Object for number to search
	 *  @return string      			Next free value
	 *  @deprecated see getNextValue
	 */
	public function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}
