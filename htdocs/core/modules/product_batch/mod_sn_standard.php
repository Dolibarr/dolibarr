<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2021       Christophe Battarel			<christophe@altairis.fr>
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
 *  \file       htdocs/core/modules/product_batch/mod_sn_standard.php
 *  \ingroup    productbatch
 *  \brief      File of class to manage SN numbering rules standard
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';

/**
 *	Class to manage MO numbering rules standard
 */
class mod_sn_standard extends ModeleNumRefBatch
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefix = 'SN';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string name
	 */
	public $name = 'sn_standard';


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
	 *	@param	CommonObject	$object	Object we need next value for
	 *  @return boolean     			false if KO (there is a conflict), true if OK
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = '';
		$max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(batch FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lot";
		$sql .= " WHERE batch LIKE '".$db->escape($this->prefix)."____-%'";
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
		if ($max && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', (string) $max)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	?Societe	$objsoc		Object thirdparty
	 *  @param  ?Productlot	$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, <=0
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db, $conf;

		// First, we get the max value
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(batch FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lot";
		$sql .= " WHERE batch LIKE '".$db->escape($this->prefix)."____-%'";
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
			dol_syslog("mod_sn_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date = dol_now();
		$yymm = dol_print_date($date, "%y%m");

		if ($max >= (pow(10, 4) - 1)) {
			$num = $max + 1;
		} else { // If counter > 9999, we do not format on 4 chars, we take number as it is
			$num = sprintf("%04d", $max + 1);
		}

		dol_syslog("mod_sn_standard::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
