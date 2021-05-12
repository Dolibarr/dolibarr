<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/import/modules_import.php
 *	\ingroup    export
 *	\brief      File of parent class for import file readers
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *	Parent class for import file readers
 */
class ModeleImports
{
<<<<<<< HEAD
    public $db;
    public $datatoimport;

    public $error='';

    public $id;           // Id of driver
	public $label;        // Label of driver
	public $extension;    // Extension of files imported by driver
	public $version;      // Version of driver

	public $label_lib;    // Label of external lib used by driver
=======
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $datatoimport;

    /**
	 * @var string Error code (or message)
	 */
	public $error='';

    /**
	 * @var int id of driver
	 */
	public $id;

    /**
     * @var string label
     */
    public $label;

	public $extension;    // Extension of files imported by driver

	/**
     * Dolibarr version of driver
     * @var string
     */
	public $version = 'dolibarr';

	public $label_lib;    // Label of external lib used by driver

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	public $version_lib;  // Version of external lib used by driver

	// Array of all drivers
	public $driverlabel=array();
<<<<<<< HEAD
	public $driverdesc=array();
	public $driverversion=array();

	public $liblabel=array();
=======

	public $driverdesc=array();

	public $driverversion=array();

	public $liblabel=array();

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	public $libversion=array();


	/**
     *  Constructor
	 */
<<<<<<< HEAD
	function __construct()
=======
    public function __construct()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	}


	/**
	 * getDriverId
	 *
	 * @return string		Id
	 */
<<<<<<< HEAD
	function getDriverId()
=======
    public function getDriverId()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->id;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label
	 */
<<<<<<< HEAD
	function getDriverLabel()
=======
    public function getDriverLabel()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->label;
	}

	/**
	 *	getDriverDesc
	 *
	 *	@return string	Description
	 */
<<<<<<< HEAD
	function getDriverDesc()
=======
    public function getDriverDesc()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string	Driver suffix
	 */
<<<<<<< HEAD
	function getDriverExtension()
=======
    public function getDriverExtension()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->extension;
	}

	/**
	 *	getDriverVersion
	 *
	 *	@return string	Driver version
	 */
<<<<<<< HEAD
	function getDriverVersion()
=======
    public function getDriverVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->version;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label of external lib
	 */
<<<<<<< HEAD
	function getLibLabel()
=======
    public function getLibLabel()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 *	@return string	Version of external lib
	 */
<<<<<<< HEAD
	function getLibVersion()
=======
    public function getLibVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    return $this->version_lib;
	}


<<<<<<< HEAD
	/**
	 *  Charge en memoire et renvoie la liste des modeles actifs
	 *
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Charge en memoire et renvoie la liste des modeles actifs
     *
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
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

		$dir=DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers imports disponibles
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
<<<<<<< HEAD
    			if (preg_match("/^import_(.*)\.modules\.php/i",$file,$reg))
=======
    			if (preg_match("/^import_(.*)\.modules\.php/i", $file, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    			{
    				$moduleid=$reg[1];

    				// Loading Class
    				$file = $dir."/import_".$moduleid.".modules.php";
    				$classname = "Import".ucfirst($moduleid);

    				require_once $file;
<<<<<<< HEAD
    				$module = new $classname($db,'');
=======
    				$module = new $classname($db, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    				// Picto
    				$this->picto[$module->id]=$module->picto;
    				// Driver properties
    				$this->driverlabel[$module->id]=$module->getDriverLabel('');
    				$this->driverdesc[$module->id]=$module->getDriverDesc('');
    				$this->driverversion[$module->id]=$module->getDriverVersion('');
    				// If use an external lib
    				$this->liblabel[$module->id]=$module->getLibLabel('');
    				$this->libversion[$module->id]=$module->getLibVersion('');

    				$i++;
    			}
    		}
        }

		return array_keys($this->driverlabel);
	}


	/**
	 *  Return picto of import driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
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
	 *  Renvoi libelle d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
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
	 *  Renvoi la description d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
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
	 *  Renvoi version d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
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
	 *	@param	string	$key	Key
	 *	@return	string
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
	 *	@param	string	$key	Key
	 *	@return	string
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
