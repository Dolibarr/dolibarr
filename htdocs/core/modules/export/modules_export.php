<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 */

/**
 *	\file       htdocs/core/modules/export/modules_export.php
 *	\ingroup    export
 *	\brief      File of parent class for export modules
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for export modules
 */
class ModeleExports extends CommonDocGenerator    // This class can't be abstract as there is instance propreties loaded by liste_modeles
{
<<<<<<< HEAD
	var $error='';

	var $driverlabel=array();
	var $driverversion=array();

	var $liblabel=array();
	var $libversion=array();


=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	public $driverlabel=array();

	public $driverversion=array();

	public $liblabel=array();

	public $libversion=array();


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Load into memory list of available export format
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates (same content than array this->driverlabel)
	 */
<<<<<<< HEAD
	function liste_modeles($db,$maxfilenamelength=0)
	{
=======
	public function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		dol_syslog(get_class($this)."::liste_modeles");

		$dir=DOL_DOCUMENT_ROOT."/core/modules/export/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers exports disponibles
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
<<<<<<< HEAD
    			if (preg_match("/^export_(.*)\.modules\.php$/i",$file,$reg))
=======
    			if (preg_match("/^export_(.*)\.modules\.php$/i", $file, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    			{
    				$moduleid=$reg[1];

    				// Loading Class
    				$file = $dir."export_".$moduleid.".modules.php";
    				$classname = "Export".ucfirst($moduleid);

    				require_once $file;
    				if (class_exists($classname))
    				{
        				$module = new $classname($db);

        				// Picto
        				$this->picto[$module->id]=$module->picto;
        				// Driver properties
        				$this->driverlabel[$module->id]=$module->getDriverLabel().(empty($module->disabled)?'':' __(Disabled)__');	// '__(Disabled)__' is a key
        				$this->driverdesc[$module->id]=$module->getDriverDesc();
        				$this->driverversion[$module->id]=$module->getDriverVersion();
        				// If use an external lib
        				$this->liblabel[$module->id]=$module->getLibLabel();
        				$this->libversion[$module->id]=$module->getLibVersion();
    				}
    				$i++;
    			}
    		}
    		closedir($handle);
        }

        asort($this->driverlabel);

		return $this->driverlabel;
	}


	/**
	 *  Return picto of export driver
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Picto string
	 */
<<<<<<< HEAD
	function getPictoForKey($key)
=======
	public function getPictoForKey($key)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->picto[$key];
	}

	/**
	 *  Renvoi libelle d'un driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Label
	 */
<<<<<<< HEAD
	function getDriverLabelForKey($key)
=======
	public function getDriverLabelForKey($key)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->driverlabel[$key];
	}

	/**
	 *  Renvoi le descriptif d'un driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Description
	 */
<<<<<<< HEAD
	function getDriverDescForKey($key)
=======
	public function getDriverDescForKey($key)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Driver version
	 */
<<<<<<< HEAD
	function getDriverVersionForKey($key)
=======
	public function getDriverVersionForKey($key)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->driverversion[$key];
	}

	/**
	 *  Renvoi libelle de librairie externe du driver
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Label of library
	 */
<<<<<<< HEAD
	function getLibLabelForKey($key)
=======
	public function getLibLabelForKey($key)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->liblabel[$key];
	}

	/**
	 *  Renvoi version de librairie externe du driver
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Version of library
	 */
<<<<<<< HEAD
	function getLibVersionForKey($key)
	{
		return $this->libversion[$key];
	}

}


=======
	public function getLibVersionForKey($key)
	{
		return $this->libversion[$key];
	}
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
