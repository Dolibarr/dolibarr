<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/bom/mod_bom_standard.php
 *  \ingroup    bom
 *  \brief      File of class to manage customer order numbering rules standard
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/bom/modules_bom.php';

/**
 *	Class to manage customer order numbering rules standard
 */
class mod_bom_standard extends ModeleNumRefboms
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'

	public $prefix='BOM';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string name
	 */
	public $name='standard';


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
    }


	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *  Test si les numeros deje en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	public function canBeActivated()
	{
		global $conf,$langs,$db;

		$coyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."bom";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
	public function getNextValue($objsoc, $object)
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."bom_bom";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog("mod_bom_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date=$object->date;
		$yymm = strftime("%y%m", $date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s", $max+1);

		dol_syslog("mod_bom_standard::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
