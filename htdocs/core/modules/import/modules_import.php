<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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

	/**
	 * @var string
	 */
	public $datatoimport;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string[] warnings codes (or messages)
	 */
	public $warnings = array();

	/**
	 * @var string Code of driver
	 */
	public $id;

	/**
	 * @var string label of driver
	 */
	public $label;

	/**
	 * @var string Extension of files imported by driver
	 */
	public $extension;

	/**
	 * Dolibarr version of driver
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * PHP minimal version required by driver
	 * @var array{0:int,1:int}
	 */
	public $phpmin = array(7, 0);

	/**
	 * Label of external lib used by driver
	 * @var string
	 */
	public $label_lib;

	/**
	 * Version of external lib used by driver
	 * @var string
	 */
	public $version_lib;

	// Array of all drivers
	/**
	 * @var string[]
	 */
	public $driverlabel = array();

	/**
	 * @var string[]
	 */
	public $driverdesc = array();

	/**
	 * @var string[]
	 */
	public $driverversion = array();

	/**
	 * @var string[]
	 */
	public $drivererror = array();

	/**
	 * @var string[]
	 */
	public $liblabel = array();

	/**
	 * @var string[]
	 */
	public $libversion = array();

	/**
	 * @var string charset
	 */
	public $charset;

	/**
	 * @var string picto
	 */
	public $picto;

	/**
	 * @var string description
	 */
	public $desc;

	/**
	 * @var string escape
	 */
	public $escape;

	/**
	 * @var string enclosure
	 */
	public $enclosure;

	/**
	 * @var Societe thirdparty
	 */
	public $thirdpartyobject;

	/**
	 * @var	array	Element mapping from table name
	 */
	public static $mapTableToElement = MODULE_MAPPING;

	/**
	 *  Constructor
	 */
	public function __construct()
	{
		global $hookmanager;

		if (is_object($hookmanager)) {
			$hookmanager->initHooks(array('import'));
			$parameters = array();
			$reshook = $hookmanager->executeHooks('constructModeleImports', $parameters, $this);
			if ($reshook >= 0 && !empty($hookmanager->resArray)) {
				foreach ($hookmanager->resArray as $mapList) {
					self::$mapTableToElement[$mapList['table']] = $mapList['element'];
				}
			}
		}
	}

	/**
	 * getDriverId
	 *
	 * @return string		Code of driver
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


	/**
	 *  Load into memory list of available import format
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public function listOfAvailableImportFormat($db, $maxfilenamelength = 0)
	{
		dol_syslog(get_class($this)."::listOfAvailableImportFormat");

		$dir = DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle = opendir($dir);

		// Search list ov drivers available and qualified
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				$reg = array();
				if (preg_match("/^import_(.*)\.modules\.php/i", $file, $reg)) {
					$moduleid = $reg[1];

					// Loading Class
					$file = $dir."/import_".$moduleid.".modules.php";
					$classname = "Import".ucfirst($moduleid);

					require_once	$file;
					$module = new $classname($db, '');

					// Picto
					$this->picto[$module->id] = $module->picto;
					// Driver properties
					$this->driverlabel[$module->id] = $module->getDriverLabel('');
					$this->driverdesc[$module->id] = $module->getDriverDesc('');
					$this->driverversion[$module->id] = $module->getDriverVersion('');
					$this->drivererror[$module->id] = $module->error ? $module->error : '';
					// If use an external lib
					$this->liblabel[$module->id] = ($module->error ? '<span class="error">'.$module->error.'</span>' : $module->getLibLabel(''));
					$this->libversion[$module->id] = $module->getLibVersion('');
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
		return	$this->picto[$key];
	}

	/**
	 *  Return label of driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverLabelForKey($key)
	{
		return	$this->driverlabel[$key];
	}

	/**
	 *  Return description of import drivervoi la description d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverDescForKey($key)
	{
		return	$this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverVersionForKey($key)
	{
		return	$this->driverversion[$key];
	}

	/**
	 *  Renvoi libelle de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getLibLabelForKey($key)
	{
		return	$this->liblabel[$key];
	}

	/**
	 *  Renvoi version de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getLibVersionForKey($key)
	{
		return	$this->libversion[$key];
	}

	/**
	 * Get element from table name with prefix
	 *
	 * @param 	string	$tableNameWithPrefix	Table name with prefix
	 * @return 	string							Element name or table element as default
	 */
	public function getElementFromTableWithPrefix($tableNameWithPrefix)
	{
		$tableElement = preg_replace('/^'.preg_quote($this->db->prefix(), '/').'/', '', $tableNameWithPrefix);
		$element = $tableElement;

		if (isset(self::$mapTableToElement[$tableElement])) {
			$element = self::$mapTableToElement[$tableElement];
		}

		return	$element;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Open input file
	 *
	 *	@param	string	$file       Path of filename
	 *  @return int                 Return integer <0 if KO, >=0 if OK
	 */
	public function import_open_file($file)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return nb of records. File must be closed.
	 *
	 *	@param	string	$file       Path of filename
	 *  @return	int					Return integer <0 if KO, >=0 if OK
	 */
	public function import_get_nb_of_lines($file)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Input header line from file
	 *
	 *  @return     int     Return integer <0 if KO, >=0 if OK
	 */
	public function import_read_header()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return array of next record in input file.
	 *
	 *  @return	array<string,array{val:mixed,type:int<-1,1>}>|boolean     Array of field values. Data are UTF8 encoded. [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=not empty string)
	 */
	public function import_read_record()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return array();
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Close file handle
	 *
	 *  @return int
	 */
	public function import_close_file()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Insert a record into database
	 *
	 *	@param	array<string,array{val:mixed,type:int<-1,1>}>|boolean	$arrayrecord                    Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 *	@param	array<string,string>	$array_match_file_to_database   Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 *	@param	Object		$objimport                      Object import (contains objimport->array_import_tables, objimport->array_import_fields, objimport->array_import_convertvalue, ...)
	 *	@param	int	$maxfields					Max number of fields to use
	 *	@param	string		$importid			Import key
	 *	@param	string[]	$updatekeys			Array of keys to use to try to do an update first before insert. This field are defined into the module descriptor.
	 *	@return	int								Return integer <0 if KO, >0 if OK
	 */
	public function import_insert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}
}
