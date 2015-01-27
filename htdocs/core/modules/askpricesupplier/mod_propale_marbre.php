<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *    	\file       htdocs/core/modules/propale/mod_propale_marbre.php
 *		\ingroup    propale
 *		\brief      File of class to manage commercial proposal numbering rules Marbre
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/propale/modules_propale.php';


/**
 *	Class to manage customer order numbering rules Marbre
 */
class mod_propale_marbre extends ModeleNumRefPropales
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefix='PR';
	var $error='';
	var $nom = "Marbre";


    /**
     *  Return description of numbering module
     *
     *  @return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
    }


	/**
	 *  Return an example of numbering module values
	 *
	 *  @return     string      Example
	 */
	function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *  Test si les numeros deje en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $conf,$langs,$db;

		$pryymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."propal";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $pryymm = substr($row[0],0,6); $max=$row[0]; }
		}

		if (! $pryymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$pryymm))
		{
			return true;
		}
		else
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
			return false;
		}
	}

	/**
	 *  Return next value
	 *
	 *  @param	Societe		$objsoc     Object third party
	 * 	@param	Propal		$propal		Object commercial proposal
	 *  @return string      			Next value
	 */
	function getNextValue($objsoc,$propal)
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."propal";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
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
			dol_syslog(get_class($this)."::getNextValue", LOG_DEBUG);
			return -1;
		}

		$date = time();
		$yymm = strftime("%y%m",$date);

		if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
		else $num = sprintf("%04s",$max+1);

		dol_syslog(get_class($this)."::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}

	/**
	 *  Return next free value
	 *
	 *  @param	Societe		$objsoc      	Object third party
	 * 	@param	Object		$objforref		Object for number to search
	 *  @return string      				Next free value
	 */
	function getNumRef($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}
