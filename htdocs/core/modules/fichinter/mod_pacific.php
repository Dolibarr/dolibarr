<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/core/modules/fichinter/mod_pacific.php
 *  \ingroup    fiche intervention
 *  \brief      File with Pacific numbering module for interventions
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/fichinter/modules_fichinter.php';

/**
 *	Class to manage numbering of intervention cards with rule Pacific.
 */
class mod_pacific extends ModeleNumRefFicheinter
{
<<<<<<< HEAD
    var $version='dolibarr';        // 'development', 'experimental', 'dolibarr'
	var $prefix='FI';
	var $error='';
	var $nom = 'pacific';
=======
    /**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';        // 'development', 'experimental', 'dolibarr'

	public $prefix='FI';

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='pacific';

	/**
	 * @var string model name
	 */
	public $name='pacific';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *  Return description of numbering module
	 *
     *  @return     string      Text with description
     */
<<<<<<< HEAD
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
=======
    public function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

	/**
	 *  Renvoi un exemple de numerotation
	 *
	 *  @return     string      Example
	 */
<<<<<<< HEAD
	function getExample()
=======
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->prefix."0501-0001";
	}

	/**
	 *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *  de conflits qui empechera cette numerotation de fonctionner.
	 *
	 *  @return     boolean     false si conflit, true si ok
	 */
<<<<<<< HEAD
	function canBeActivated()
=======
	public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs,$conf,$db;

		$langs->load("bills");

		$fayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " WHERE entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
<<<<<<< HEAD
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if (! $fayymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$fayymm))
=======
			if ($row) { $fayymm = substr($row[0], 0, 6); $max=$row[0]; }
		}
		if (! $fayymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $fayymm))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			return true;
		}
		else
		{
			$langs->load("errors");
<<<<<<< HEAD
			$this->error=$langs->trans('ErrorNumRefModel',$max);
=======
			$this->error=$langs->trans('ErrorNumRefModel', $max);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return false;
		}
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe		$objsoc     Object thirdparty
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
<<<<<<< HEAD
	function getNextValue($objsoc=0,$object='')
=======
	public function getNextValue($objsoc = 0, $object = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $db,$conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
		$sql.= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}

		//$date=time();
		$date=$object->datec;
<<<<<<< HEAD
		$yymm = strftime("%y%m",$date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s",$max+1);
=======
		$yymm = strftime("%y%m", $date);

    	if ($max >= (pow(10, 4) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    	else $num = sprintf("%04s", $max+1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $this->prefix.$yymm."-".$num;
	}

	/**
	 * 	Return next free value
	 *
	 *  @param	Societe	$objsoc     Object third party
	 * 	@param	Object	$objforref	Object for number to search
	 *  @return string      		Next free value
	 */
<<<<<<< HEAD
	function getNumRef($objsoc,$objforref)
	{
		return $this->getNextValue($objsoc,$objforref);
	}

}

=======
	public function getNumRef($objsoc, $objforref)
	{
		return $this->getNextValue($objsoc, $objforref);
	}
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
