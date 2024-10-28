<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
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
class ModeleExports extends CommonDocGenerator    // This class can't be abstract as there is instance properties loaded by listOfAvailableExportFormat
{
	/**
	 * @var string ID ex: csv, tsv, excel...
	 */
	public $id = 'NOT IMPLEMENTED';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array<string,string>
	 */
	public $driverlabel = array();

	/**
	 * @var array<string,string>
	 */
	public $driverdesc = array();

	/**
	 * @var array<string,string>
	 */
	public $driverversion = array();

	/**
	 * @var array<string,string>
	 */
	public $liblabel = array();

	/**
	 * @var array<string,string>
	 */
	public $libversion = array();

	/**
	 * @var string picto
	 */
	public $picto;

	/**
	 * @var array<string,string> Module key/picto pairs
	 */
	public $pictos;

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
	 * @var int col
	 */
	public $col;

	/**
	 * @var int<0,1> disabled
	 */
	public $disabled;

	/**
	 *  Load into memory list of available export format
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	string[]					List of templates (same content as array this->driverlabel)
	 */
	public function listOfAvailableExportFormat($db, $maxfilenamelength = 0)
	{
		global $langs;

		dol_syslog(get_class($this)."::listOfAvailableExportFormat");

		$dir = DOL_DOCUMENT_ROOT."/core/modules/export/";
		$handle = opendir($dir);

		// Recherche des fichiers drivers exports disponibles
		$i = 0;
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				$reg = array();
				if (preg_match("/^export_(.*)\.modules\.php$/i", $file, $reg)) {
					$moduleid = $reg[1];
					if ($moduleid == 'csv') {
						continue;	// This may happen if on old file export_csv.modules.php was not correctly deleted
					}

					// Loading Class
					$file = $dir."export_".$moduleid.".modules.php";
					$classname = "Export".ucfirst($moduleid);

					require_once $file;
					if (class_exists($classname)) {
						$module = new $classname($db);
						'@phan-var-force ModeleExports $module';
						// var_dump($classname);

						// Picto
						$this->pictos[$module->id] = $module->picto;
						// Driver properties
						$this->driverlabel[$module->id] = $module->getDriverLabel().(empty($module->disabled) ? '' : ' __(Disabled)__'); // '__(Disabled)__' is a key
						if (method_exists($module, 'getDriverLabelBis')) {
							// @phan-suppress-next-line PhanUndeclaredMethod
							$labelBis = $module->getDriverLabelBis();
							if ($labelBis) {
								$this->driverlabel[$module->id] .= ' <span class="opacitymedium">('.$labelBis.')</span>';
							}
						}
						$this->driverdesc[$module->id] = $module->getDriverDesc();
						$this->driverversion[$module->id] = $module->getDriverVersion();
						// If use an external lib
						$this->liblabel[$module->id] = $module->getLibLabel();
						$this->libversion[$module->id] = $module->getLibVersion();
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
	public function getPictoForKey($key)
	{
		return $this->pictos[$key];
	}

	/**
	 *  Return label of driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Label
	 */
	public function getDriverLabelForKey($key)
	{
		return $this->driverlabel[$key];
	}

	/**
	 *  Renvoi le descriptif d'un driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Description
	 */
	public function getDriverDescForKey($key)
	{
		return $this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver export
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Driver version
	 */
	public function getDriverVersionForKey($key)
	{
		return $this->driverversion[$key];
	}

	/**
	 *  Renvoi label of driver lib
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Label of library
	 */
	public function getLibLabelForKey($key)
	{
		return $this->liblabel[$key];
	}

	/**
	 *  Return version of driver lib
	 *
	 *  @param	string	$key	Key of driver
	 *  @return	string			Version of library
	 */
	public function getLibVersionForKey($key)
	{
		// phpcs:enable
		return $this->libversion[$key];
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output record line into file
	 *
	 *  @param	array<string,string>	$array_selected_sorted	Array with list of field to export
	 *  @param	Resource				$objp					A record from a fetch with all fields from select
	 *  @param	Translate				$outputlangs			Object lang to translate values
	 *  @param	array<string,string>	$array_types			Array with types of fields
	 * 	@return	int												Return integer <0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}


	/**
	 * getDriverLabel
	 *
	 * @return 	string			Return driver label
	 */
	public function getDriverLabel()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	/**
	 * getDriverDesc
	 *
	 * @return string
	 */
	public function getDriverDesc()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	/**
	 * getDriverVersion
	 *
	 * @return string
	 */
	public function getDriverVersion()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	/**
	 * getLibLabel
	 *
	 * @return string
	 */
	public function getLibLabel()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	/**
	 * getLibVersion
	 *
	 * @return string
	 */
	public function getLibVersion()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	/**
	 * getDriverExtension
	 *
	 * @return string
	 */
	public function getDriverExtension()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return 'Not implemented';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Open output file
	 *
	 * 	@param		string		$file			File name to generate
	 *  @param		Translate	$outputlangs	Output language object
	 *	@return		int							Return integer <0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write header
	 *
	 *  @param      Translate	$outputlangs        Object lang to translate values
	 * 	@return		int								Return integer <0 if KO, >0 if OK
	 */
	public function write_header($outputlangs)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output title line into file
	 *
	 *  @param	array<string,string>	$array_export_fields_label	Array with list of label of fields
	 *  @param	array<string,string>	$array_selected_sorted		Array with list of field to export
	 *  @param	Translate				$outputlangs    			Object lang to translate values
	 *  @param	array<string,string>	$array_types				Array with types of fields
	 * 	@return	int													Return integer <0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write footer
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function write_footer($outputlangs)
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Close Excel file
	 *
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function close_file()
	{
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->error = $msg;
		return -1;
	}
}
