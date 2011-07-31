<?php
/* Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file       htdocs/includes/modules/expedition/mod_expedition_safor.php
 *  \ingroup    expedition
 *  \brief      File of class to manage shipments numbering rules Safor
 *  \version    $Id: mod_expedition_safor.php,v 1.3 2011/07/31 23:28:14 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/expedition/modules_expedition.php");

/**	    \class      mod_commande_safor
 *      \brief      Class to manage expedition numbering rules Safor
 */
class mod_expedition_safor extends ModelNumRefExpedition
{
	var $version='dolibarr';
	var $prefix='SH';
	var $error='';
	var $nom='Safor';


	/**
	 *	Return default description of numbering model
	 *	@return     string      text description
	 */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
    }


	/**
	 *	Return numbering example
	 *	@return     string      Example
	 */
	function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *	Test if existing numbers make problems with numbering
	 *	@return     boolean     false if conflit, true if ok
	 */
	function canBeActivated()
	{
		global $conf,$langs;

		$coyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($coyymm && ! preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$coyymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 *	Return next value
	 *	@param      objsoc      third party object
	 *	@param		shipment	shipment object
	 *	@return     string      Value if OK, 0 if KO
	 */
	function getNextValue($objsoc,$shipment)
	{
		global $db,$conf;

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
		$sql.= " WHERE ref like '".$this->prefix."____-%'";
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
			dol_syslog("mod_expedition_safor::getNextValue sql=".$sql);
			return -1;
		}

		$date=time();
		$yymm = strftime("%y%m",$date);
		$num = sprintf("%04s",$max+1);

		dol_syslog("mod_expedition_safor::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}

	/**
	 *Return next free value
	 *	@param      objsoc      Object third party
	 *	@param		objforref	Object for number to search
	 *	@return     string      Next free value
	 */
	function expedition_get_num($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}
?>
