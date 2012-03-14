<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *    	\file       htdocs/core/modules/propale/mod_propale_marbre.php
 *		\ingroup    propale
 *		\brief      File of class to manage commercial proposal numbering rules Marbre
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/propale/modules_propale.php");


/**	    \class      mod_propale_marbre
 *		\brief      Class to manage customer order numbering rules Marbre
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


	/**     \brief      Test si les numeros deje en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $conf,$langs;

		$pryymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
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

	/**		\brief      Return next value
	 *      \param      objsoc      Object third party
	 * 		\param		propal		Object commercial proposal
	 *   	\return     string      Valeur
	 */
	function getNextValue($objsoc,$propal)
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";	// This is standard SQL
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
			dol_syslog("mod_propale_marbre::getNextValue sql=".$sql);
			return -1;
		}

		$date = time();
		$yymm = strftime("%y%m",$date);
		$num = sprintf("%04s",$max+1);

		dol_syslog("mod_propale_marbre::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}

	/**		\brief      Return next free value
	 *      	\param      objsoc      Object third party
	 * 		\param		objforref	Object for number to search
	 *   	\return     string      Next free value
	 */
	function getNumRef($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}

?>
