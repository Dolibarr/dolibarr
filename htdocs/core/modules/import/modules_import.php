<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
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

	public $version_lib;  // Version of external lib used by driver

	// Array of all drivers
	public $driverlabel=array();

	public $driverdesc=array();

	public $driverversion=array();

	public $liblabel=array();

	public $libversion=array();


	/**
     *  Constructor
	 */
    public function __construct()
	{
	}


	/**
	 * getDriverId
	 *
	 * @return string		Id
	 */
    public function getDriverId()
	{
	    return $this->id;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label
	 */
    public function getDriverLabel()
	{
	    return $this->label;
	}

	/**
	 *	getDriverDesc
	 *
	 *	@return string	Description
	 */
    public function getDriverDesc()
	{
	    return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string	Driver suffix
	 */
    public function getDriverExtension()
	{
	    return $this->extension;
	}

	/**
	 *	getDriverVersion
	 *
	 *	@return string	Driver version
	 */
    public function getDriverVersion()
	{
	    return $this->version;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label of external lib
	 */
    public function getLibLabel()
	{
	    return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 *	@return string	Version of external lib
	 */
    public function getLibVersion()
	{
	    return $this->version_lib;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Charge en memoire et renvoie la liste des modeles actifs
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
    public function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		dol_syslog(get_class($this)."::liste_modeles");

		$dir=DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle=opendir($dir);

		// Recherche des fichiers drivers imports disponibles
		$i=0;
        if (is_resource($handle))
        {
    		while (($file = readdir($handle))!==false)
    		{
    			if (preg_match("/^import_(.*)\.modules\.php/i", $file, $reg))
    			{
    				$moduleid=$reg[1];

    				// Loading Class
    				$file = $dir."/import_".$moduleid.".modules.php";
    				$classname = "Import".ucfirst($moduleid);

    				require_once $file;
    				$module = new $classname($db, '');

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
    public function getPictoForKey($key)
	{
		return $this->picto[$key];
	}

	/**
	 *  Renvoi libelle d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
    public function getDriverLabelForKey($key)
	{
		return $this->driverlabel[$key];
	}

	/**
	 *  Renvoi la description d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
    public function getDriverDescForKey($key)
	{
		return $this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
    public function getDriverVersionForKey($key)
	{
		return $this->driverversion[$key];
	}

	/**
	 *  Renvoi libelle de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
    public function getLibLabelForKey($key)
	{
		return $this->liblabel[$key];
	}

	/**
	 *  Renvoi version de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
    public function getLibVersionForKey($key)
	{
		return $this->libversion[$key];
	}
}
