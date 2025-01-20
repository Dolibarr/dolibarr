<?php
/* Copyright (C) 2005-2011  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Charles-Fr BENKE    <charles.fr@benke.fr>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/exports/class/export.class.php
 *	\ingroup    export
 *	\brief      File of class to manage exports
 */


/**
 *	Class to manage exports
 */
class Export
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string[]
	 */
	public $array_export_icon;

	/**
	 * @var bool[]
	 */
	public $array_export_perms;


	/**
	 * @var string Last error message
	 */
	public $error;
	/**
	 * @var string Last error code
	 */
	public $errno;
	/**
	 * @var string[] Error messages
	 */
	public $errors;

	/**
	 * @var array<int,string>
	 */
	public $array_export_code = array(); // Tableau de "idmodule_numexportprofile"
	/**
	 * @var string[]
	 */
	public $array_export_code_for_sort = array(); // Tableau de "idmodule_numexportprofile"
	/**
	 * @var DolibarrModules[]
	 */
	public $array_export_module = array(); // Tableau de "nom de modules"
	/**
	 * @var string[]
	 */
	public $array_export_label = array(); // Tableau de "libelle de lots"
	/**
	 * @var string[]
	 */
	public $array_export_sql_start = array(); // Tableau des "requetes sql"
	/**
	 * @var string[]
	 */
	public $array_export_sql_end = array(); // Tableau des "requetes sql"
	/**
	 * @var string[]
	 */
	public $array_export_sql_order = array(); // Tableau des "requetes sql"

	/**
	 * @var array<int,array<string,string>>
	 */
	public $array_export_fields = array(); // Tableau des listes de champ+libelle a exporter
	/**
	 * @var array<int,array<string,string>>
	 */
	public $array_export_TypeFields = array(); // Tableau des listes de champ+Type de filtre
	/**
	 * @var array<int,array<string,string>>
	 */
	public $array_export_FilterValue = array(); // Tableau des listes de champ+Valeur a filtrer
	/**
	 * @var array<int,array<string,string>>
	 */
	public $array_export_entities = array(); // Tableau des listes de champ+alias a exporter
	/**
	 * @var array<int,array<string,string>>
	 */
	public $array_export_dependencies = array(); // array of list of entities that must take care of the DISTINCT if a field is added into export
	/**
	 * @var array<array<array{rule:string,file:string,classfile:string,class:string,method:string,method_params:string[]}>>
	 */
	public $array_export_special = array(); // array of special operations to do on field
	/**
	 * @var array<array<string,string>>
	 */
	public $array_export_examplevalues = array(); // array with examples for fields
	/**
	 * @var array<int,array<string,string>|''>
	 */
	public $array_export_help = array(); // array with tooltip help for fields

	// To store export templates
	/**
	 * @var string List of fields in the export profile
	 */
	public $hexa;
	/**
	 * @var string List of search criteria in the export profile
	 */
	public $hexafiltervalue;
	/**
	 * @var string
	 */
	public $datatoexport;
	/**
	 * @var string Name of export profile
	 */
	public $model_name;
	/**
	 * @var int
	 */
	public $fk_user;

	/**
	 * @var string
	 */
	public $sqlusedforexport;


	/**
	 *    Constructor
	 *
	 *    @param  	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Load an exportable dataset
	 *
	 *    @param  	User		$user      	Object user making export
	 *    @param  	string		$filter    	Load a particular dataset only
	 *    @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function load_arrays($user, $filter = '')
	{
		// phpcs:enable
		global $langs, $conf, $mysoc;

		dol_syslog(get_class($this)."::load_arrays user=".$user->id." filter=".$filter);

		$i = 0;

		// Define list of modules directories into modulesdir
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$modulesdir = dolGetModulesDirs();

		foreach ($modulesdir as $dir) {
			// Search available exports
			$handle = @opendir(dol_osencode($dir));
			if (is_resource($handle)) {
				// Search module files
				while (($file = readdir($handle)) !== false) {
					$reg = array();
					if (is_readable($dir.$file) && preg_match("/^(mod.*)\.class\.php$/i", $file, $reg)) {
						$modulename = $reg[1];

						// Defined if module is enabled
						$enabled = true;
						$part = strtolower(preg_replace('/^mod/i', '', $modulename));
						if ($part == 'propale') {
							$part = 'propal';
						}
						if (empty($conf->$part->enabled)) {
							$enabled = false;
						}

						if ($enabled) {
							// Loading Class
							$file = $dir.$modulename.".class.php";
							$classname = $modulename;
							require_once $file;
							$module = new $classname($this->db);
							'@phan-var-force DolibarrModules $module';

							if (isset($module->export_code) && is_array($module->export_code)) {
								foreach ($module->export_code as $r => $value) {
									//print $i.'-'.$filter.'-'.$modulename.'-'.join(',',$module->export_code).'<br>';
									if ($filter && ($filter != $module->export_code[$r])) {
										continue;
									}

									// Test if condition to show are ok
									if (!empty($module->export_enabled[$r]) && !verifCond($module->export_enabled[$r])) {
										continue;
									}

									// Test if permissions are ok
									$bool = true;
									if (isset($module->export_permission)) {
										foreach ($module->export_permission[$r] as $val) {
											$perm = $val;
											//print_r("$perm[0]-$perm[1]-$perm[2]<br>");
											if (!empty($perm[2])) {
												$bool = isset($user->rights->{$perm[0]}->{$perm[1]}->{$perm[2]}) ? $user->rights->{$perm[0]}->{$perm[1]}->{$perm[2]} : false;
											} elseif (!empty($perm[1])) {
												$bool = isset($user->rights->{$perm[0]}->{$perm[1]}) ? $user->rights->{$perm[0]}->{$perm[1]} : false;
											} else {
												$bool = false;
											}
											if ($perm[0] == 'user' && $user->admin) {
												$bool = true;
											}
											if (!$bool) {
												break;
											}
										}
									}
									//print $bool." $perm[0]"."<br>";

									// Permissions ok
									//	          if ($bool)
									//	          {
									// Charge fichier lang en rapport
									$langtoload = $module->getLangFilesArray();
									if (is_array($langtoload)) {
										foreach ($langtoload as $key) {
											$langs->load($key);
										}
									}


									// Module
									$this->array_export_module[$i] = $module;
									// Permission
									$this->array_export_perms[$i] = $bool;
									// Icon
									$this->array_export_icon[$i] = (isset($module->export_icon[$r]) ? $module->export_icon[$r] : $module->picto);
									// Code of the export dataset / Code du dataset export
									$this->array_export_code[$i] = $module->export_code[$r];
									// Define a key for sort
									$this->array_export_code_for_sort[$i] = $module->module_position.'_'.$module->export_code[$r]; // Add a key into the module
									// Export Dataset Label / Libelle du dataset export
									$this->array_export_label[$i] = $module->getExportDatasetLabel($r);
									// Table of fields to export / Tableau des champ a exporter (cle=champ, valeur=libelle)
									$this->array_export_fields[$i] = $module->export_fields_array[$r];
									// Table of fields to be filtered (key=field, value1=data type) Verifies that the module has filters
									$this->array_export_TypeFields[$i] = (isset($module->export_TypeFields_array[$r]) ? $module->export_TypeFields_array[$r] : '');
									// Table of entities to export (key=field, value=entity)
									$this->array_export_entities[$i] = $module->export_entities_array[$r];
									// Table of entities requiring to abandon DISTINCT (key=entity, valeur=field id child records)
									$this->array_export_dependencies[$i] = (!empty($module->export_dependencies_array[$r]) ? $module->export_dependencies_array[$r] : '');
									// Table of special field operations / Tableau des operations speciales sur champ
									$this->array_export_special[$i] = (!empty($module->export_special_array[$r]) ? $module->export_special_array[$r] : '');
									// Array of examples
									$this->array_export_examplevalues[$i] = (!empty($module->export_examplevalues_array[$r]) ? $module->export_examplevalues_array[$r] : null);
									// Array of help tooltips
									$this->array_export_help[$i] = (!empty($module->export_help_array[$r]) ? $module->export_help_array[$r] : '');

									// SQL dataset query / Requete SQL du dataset
									$this->array_export_sql_start[$i] = $module->export_sql_start[$r];
									$this->array_export_sql_end[$i] = $module->export_sql_end[$r];
									$this->array_export_sql_order[$i] = (!empty($module->export_sql_order[$r]) ? $module->export_sql_order[$r] : null);
									//$this->array_export_sql[$i]=$module->export_sql[$r];

									// @phan-suppress-next-line PhanUndeclaredProperty
									dol_syslog(get_class($this)."::load_arrays loaded for module ".$modulename." with index ".$i.", dataset=".$module->export_code[$r].", nb of fields=".(property_exists($module, 'export_fields_code') && !empty($module->export_fields_code[$r]) ? count($module->export_fields_code[$r]) : ''));
									$i++;
									//	          }
								}
							}
						}
					}
				}
				closedir($handle);
			}
		}

		return 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Build the sql export request.
	 *      Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *      @param      int		$indice				Indice of export
	 *      @param      array<string,string>	$array_selected     Filter fields on array of fields to export
	 *      @param      array<string,string>	$array_filterValue  Filter records on array of value for fields
	 *      @return		string						SQL String. Example "select s.rowid as r_rowid, s.status as s_status from ..."
	 */
	public function build_sql($indice, $array_selected, $array_filterValue)
	{
		// phpcs:enable
		// Build the sql request
		$sql = $this->array_export_sql_start[$indice];
		$i = 0;

		//print_r($array_selected);
		foreach ($this->array_export_fields[$indice] as $key => $value) {
			if (!array_key_exists($key, $array_selected)) {
				continue; // Field not selected
			}
			if (preg_match('/^none\./', $key)) {
				continue; // A field that must not appears into SQL
			}
			if ($i > 0) {
				$sql .= ', ';
			} else {
				$i++;
			}

			if (strpos($key, ' as ') === false) {
				$newfield = $key.' as '.str_replace(array('.', '-', '(', ')'), '_', $key);
			} else {
				$newfield = $key;
			}

			$sql .= $newfield;
		}
		$sql .= $this->array_export_sql_end[$indice];

		// Add the WHERE part. Filtering into sql if a filtering array is provided
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			$sqlWhere = '';
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if (preg_match('/GROUP_CONCAT/i', $key)) {
					continue;
				}
				if ($value != '') {
					$sqlWhere .= " AND ".$this->build_filterQuery($this->array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
				}
			}
			$sql .= $sqlWhere;
		}

		// Add the sort order
		$sql .= $this->array_export_sql_order[$indice];

		// Add the HAVING part.
		if (is_array($array_filterValue) && !empty($array_filterValue)) {
			// Loop on each condition to add
			foreach ($array_filterValue as $key => $value) {
				if (preg_match('/GROUP_CONCAT/i', $key) and $value != '') {
					$sql .= " HAVING ".$this->build_filterQuery($this->array_export_TypeFields[$indice][$key], $key, $array_filterValue[$key]);
				}
			}
		}

		return $sql;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Build the conditional string from filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter
	 *      @param		string	$NameField		Name of the field to filter
	 *      @param		string	$ValueField		Value of the field for filter. Must not be ''
	 *      @return		string					SQL string of then field ex : "field='xxx'"
	 */
	public function build_filterQuery($TypeField, $NameField, $ValueField)
	{
		// phpcs:enable
		$NameField = sanitizeVal($NameField, 'aZ09');
		$szFilterQuery = '';

		//print $TypeField." ".$NameField." ".$ValueField;
		$InfoFieldList = explode(":", $TypeField);
		// build the input field on depend of the type of file
		switch ($InfoFieldList[0]) {
			case 'Text':
				if (!(strpos($ValueField, '%') === false)) {
					$szFilterQuery = " ".$this->db->sanitize($NameField)." LIKE '".$this->db->escape($ValueField)."'";
				} else {
					$szFilterQuery = " ".$this->db->sanitize($NameField)." = '".$this->db->escape($ValueField)."'";
				}
				break;
			case 'Date':
				if (strpos($ValueField, "+") > 0) {
					// mode plage
					$ValueArray = explode("+", $ValueField);
					$szFilterQuery = "(".$this->conditionDate($NameField, trim($ValueArray[0]), ">=");
					$szFilterQuery .= " AND ".$this->conditionDate($NameField, trim($ValueArray[1]), "<=").")";
				} else {
					if (is_numeric(substr($ValueField, 0, 1))) {
						$szFilterQuery = $this->conditionDate($NameField, trim($ValueField), "=");
					} else {
						$szFilterQuery = $this->conditionDate($NameField, trim(substr($ValueField, 1)), substr($ValueField, 0, 1));
					}
				}
				break;
			case 'Duree':
				break;
			case 'Numeric':
				// if there is a signe +
				if (strpos($ValueField, "+") > 0) {
					// mode plage
					$ValueArray = explode("+", $ValueField);
					$szFilterQuery = "(".$NameField." >= ".((float) $ValueArray[0]);
					$szFilterQuery .= " AND ".$NameField." <= ".((float) $ValueArray[1]).")";
				} else {
					if (is_numeric(substr($ValueField, 0, 1))) {
						$szFilterQuery = " ".$NameField." = ".((float) $ValueField);
					} else {
						$szFilterQuery = " ".$NameField.substr($ValueField, 0, 1).((float) substr($ValueField, 1));
					}
				}
				break;
			case 'Boolean':
				$szFilterQuery = " ".$NameField."=".(is_numeric($ValueField) ? $ValueField : ($ValueField == 'yes' ? 1 : 0));
				break;
			case 'FormSelect':
				if (is_numeric($ValueField) && $ValueField > 0) {
					$szFilterQuery = " ".$NameField." = ".((float) $ValueField);
				} else {
					$szFilterQuery = " 1=1";	// Test always true
				}
				break;
			case 'Status':
			case 'List':
				if (is_numeric($ValueField)) {
					$szFilterQuery = " ".$NameField." = ".((float) $ValueField);
				} else {
					if (!(strpos($ValueField, '%') === false)) {
						$szFilterQuery = " ".$NameField." LIKE '".$this->db->escape($ValueField)."'";
					} else {
						$szFilterQuery = " ".$NameField." = '".$this->db->escape($ValueField)."'";
					}
				}
				break;
			default:
				dol_syslog("Error we try to forge an sql export request with a condition on a field with type ".$InfoFieldList[0]." (defined into module descriptor) but this type is unknown/not supported. It looks like a bug into module descriptor.", LOG_ERR);
		}

		return $szFilterQuery;
	}

	/**
	 *  conditionDate
	 *
	 *  @param 	string	$Field		Field operand 1
	 *  @param 	string	$Value		Value operand 2
	 *  @param 	string	$Sens		Comparison operator
	 *  @return string
	 */
	public function conditionDate($Field, $Value, $Sens)
	{
		// TODO date_format is forbidden, not performant and not portable. Use instead $Value to forge the range date.
		if (strlen($Value) == 4) {
			$Condition = " date_format(".$Field.",'%Y') ".$Sens." '".$this->db->escape($Value)."'";
		} elseif (strlen($Value) == 6) {
			$Condition = " date_format(".$Field.",'%Y%m') ".$Sens." '".$this->db->escape($Value)."'";
		} else {
			$Condition = " date_format(".$Field.",'%Y%m%d') ".$Sens." '".$this->db->escape($Value)."'";
		}
		return $Condition;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Build an input field used to filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter. Example: Text, Date, List:c_country:label:rowid, List:c_stcom:label:code, Numeric or Number, Boolean
	 *      @param		string	$NameField		Name of the field to filter
	 *      @param		string	$ValueField		Initial value of the field to filter
	 *      @return		string					html string of the input field ex : "<input type=text name=... value=...>"
	 */
	public function build_filterField($TypeField, $NameField, $ValueField)
	{
		// phpcs:enable
		global $langs, $form;

		$szFilterField = '';
		$InfoFieldList = explode(":", $TypeField);

		// build the input field on depend of the type of file
		switch ($InfoFieldList[0]) {
			case 'Text':
			case 'Date':
				$szFilterField = '<input type="text" name="'.$NameField.'" value="'.$ValueField.'">';
				break;
			case 'Duree':
			case 'Numeric':
			case 'Number':
				// Must be a string text to allow to use comparison strings like "<= 99.9"
				$szFilterField = '<input type="text" size="6" name="'.$NameField.'" value="'.$ValueField.'">';
				break;
			case 'Status':
				$szFilterField = '<input type="number" size="6" name="'.$NameField.'" value="'.$ValueField.'">';
				break;
			case 'Boolean':
				$szFilterField = '<select name="'.$NameField.'" id="'.dol_escape_all($NameField).'" class="flat width75 maxwidth75">';
				$szFilterField .= '<option ';
				if ($ValueField == '') {
					$szFilterField .= ' selected ';
				}
				$szFilterField .= ' value="">&nbsp;</option>';

				$szFilterField .= '<option ';
				if ($ValueField == 'yes' || $ValueField == '1') {
					$szFilterField .= ' selected ';
				}
				$szFilterField .= ' value="1">'.yn(1).'</option>';

				$szFilterField .= '<option ';
				if ($ValueField == 'no' || $ValueField == '0') {
					$szFilterField .= ' selected ';
				}
				$szFilterField .= ' value="0">'.yn(0).'</option>';
				$szFilterField .= "</select>";
				$szFilterField .= ajax_combobox(dol_escape_all($NameField));
				break;
			case 'FormSelect':
				//var_dump($NameField);
				if ($InfoFieldList[1] == 'select_company') {
					$szFilterField .= $form->select_company('', $NameField, '', 1, 0, 0, [], 0, 'maxwidth200');
				} elseif ($InfoFieldList[1] == 'selectcontacts') {
					//$szFilterField .= $form->selectcontacts(0, '', $NameField, '&nbsp;', '', '', 0, 'maxwidth200');
					$szFilterField .= $form->select_contact(0, '', $NameField, '&nbsp;', '', '', 0, 'minwidth100imp maxwidth200', true);
				} elseif ($InfoFieldList[1] == 'select_dolusers') {
					$szFilterField .= $form->select_dolusers('', $NameField, 1, null, 0, '', '', '', 0, 0, "", 0, "", "maxwidth200");
				}
				break;
			case 'List':
				// 0 : Type of the field / Type du champ
				// 1 : Name of the table / Nom de la table
				// 2 : Name of the field containing the label / Nom du champ contenant le libelle
				// 3 : Name of field with key (if it is not "rowid"). Used this field as key for combo list.
				// 4 : Name of element for getEntity().

				if (!empty($InfoFieldList[3])) {
					$keyList = $InfoFieldList[3];
				} else {
					$keyList = 'rowid';
				}
				$sql = "SELECT ".$keyList." as rowid, ".$InfoFieldList[2]." as label".(empty($InfoFieldList[3]) ? "" : ", ".$InfoFieldList[3]." as code");
				if ($InfoFieldList[1] == 'c_stcomm') {
					$sql = "SELECT id as id, ".$keyList." as rowid, ".$InfoFieldList[2]." as label".(empty($InfoFieldList[3]) ? "" : ", ".$InfoFieldList[3].' as code');
				}
				if ($InfoFieldList[1] == 'c_country') {
					$sql = "SELECT ".$keyList." as rowid, ".$InfoFieldList[2]." as label, code as code";
				}
				$sql .= " FROM ".MAIN_DB_PREFIX.$InfoFieldList[1];
				if (!empty($InfoFieldList[4])) {
					$sql .= ' WHERE entity IN ('.getEntity($InfoFieldList[4]).')';
				}

				$resql = $this->db->query($sql);
				if ($resql) {
					$szFilterField = '<select class="minwidth300 maxwidth500" name="'.$NameField.'" id="'.dol_escape_all($NameField).'">';
					$szFilterField .= '<option value="0">&nbsp;</option>';
					$num = $this->db->num_rows($resql);

					$i = 0;
					if ($num) {
						while ($i < $num) {
							$obj = $this->db->fetch_object($resql);
							if ($obj->label == '-') {
								// Discard entry '-'
								$i++;
								continue;
							}
							//var_dump($InfoFieldList[1]);
							$labeltoshow = $obj->label;
							if ($InfoFieldList[1] == 'c_stcomm') {
								$langs->load("companies");
								$labeltoshow = (($langs->trans("StatusProspect".$obj->id) != "StatusProspect".$obj->id) ? $langs->trans("StatusProspect".$obj->id) : $obj->label);
							}
							if ($InfoFieldList[1] == 'c_country') {
								//var_dump($sql);
								$langs->load("dict");
								$labeltoshow = (($langs->trans("Country".$obj->code) != "Country".$obj->code) ? $langs->trans("Country".$obj->code) : $obj->label);
							}
							if (!empty($ValueField) && $ValueField == $obj->rowid) {
								$szFilterField .= '<option value="'.$obj->rowid.'" selected data-html="'.dolPrintHTMLForAttribute($labeltoshow).'">'.dolPrintHTML($labeltoshow).'</option>';
							} else {
								$szFilterField .= '<option value="'.$obj->rowid.'" data-html="'.dolPrintHTMLForAttribute($labeltoshow).'">'.$labeltoshow.'</option>';
							}
							$i++;
						}
					}
					$szFilterField .= "</select>";
					$szFilterField .= ajax_combobox(dol_escape_all($NameField));

					$this->db->free($resql);
				} else {
					dol_print_error($this->db);
				}
				break;
		}

		return $szFilterField;
	}

	/**
	 *  Build an input field used to filter the query
	 *
	 *  @param      string  $TypeField      Type of Field to filter
	 *  @return     string                  html string of the input field ex : "<input type=text name=... value=...>"
	 */
	public function genDocFilter($TypeField)
	{
		global $langs;

		$szMsg = '';
		$InfoFieldList = explode(":", $TypeField);
		// build the input field on depend of the type of file
		switch ($InfoFieldList[0]) {
			case 'Text':
				$szMsg = $langs->trans('ExportStringFilter');
				break;
			case 'Date':
				$szMsg = $langs->trans('ExportDateFilter');
				break;
			case 'Duree':
				break;
			case 'Numeric':
				$szMsg = $langs->trans('ExportNumericFilter');
				break;
			case 'Boolean':
				break;
			case 'List':
				break;
		}
		return $szMsg;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Build export file.
	 *      File is built into directory $conf->export->dir_temp.'/'.$user->id
	 *      Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *      @param      User		$user               User that export
	 *      @param      string		$model              Export format
	 *      @param      string		$datatoexport       Name of dataset to export
	 *      @param      array<string,string>	$array_selected     Filter on array of fields to export
	 *      @param      array<string,string>	$array_filterValue  Filter on array of fields with a filter
	 *      @param		string		$sqlquery			If set, transmit the sql request for select (otherwise, sql request is generated from arrays)
	 * 		@param		string		$separator			separator to fill $objmodel->separator with the new separator
	 *      @return		int								Return integer <0 if KO, >0 if OK
	 */
	public function build_file($user, $model, $datatoexport, $array_selected, $array_filterValue, $sqlquery = '', $separator = '')
	{
		// phpcs:enable
		global $conf, $langs, $mysoc;

		$indice = 0;
		asort($array_selected);

		dol_syslog(__METHOD__." ".$model.", ".$datatoexport.", ".implode(",", $array_selected));

		// Check parameters or context properties
		if (empty($this->array_export_fields) || !is_array($this->array_export_fields)) {
			$this->error = "ErrorBadParameter";
			dol_syslog($this->error, LOG_ERR);
			return -1;
		}

		// Creation of class to export using model ExportXXX
		$dir = DOL_DOCUMENT_ROOT."/core/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once $dir.$file;
		$objmodel = new $classname($this->db);
		'@phan-var-force ModeleExports $objmodel';

		if (in_array($model, array('csvutf8', 'csviso')) && !empty($separator) && property_exists($objmodel, 'separator')) {
			// @phan-suppress-next-line PhanUndeclaredProperty
			$objmodel->separator = $separator;
		}

		if (!empty($sqlquery)) {
			$sql = $sqlquery;
		} else {
			// Define value for indice from $datatoexport
			$foundindice = 0;
			foreach ($this->array_export_code as $key => $dataset) {
				if ($datatoexport == $dataset) {
					$indice = $key;
					$foundindice++;
					//print "Found indice = ".$indice." for dataset=".$datatoexport."\n";
					break;
				}
			}
			if (empty($foundindice)) {
				$this->error = "ErrorBadParameter can't find dataset ".$datatoexport." into preload arrays this->array_export_code";
				return -1;
			}
			$sql = $this->build_sql($indice, $array_selected, $array_filterValue);
		}

		// Run the SQL
		$this->sqlusedforexport = $sql;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			//$this->array_export_label[$indice]
			if (getDolGlobalString('EXPORT_PREFIX_SPEC')) {
				$filename = getDolGlobalString('EXPORT_PREFIX_SPEC') . "_".$datatoexport;
			} else {
				$filename = "export_".$datatoexport;
			}
			if (getDolGlobalString('EXPORT_NAME_WITH_DT')) {
				$filename .= dol_print_date(dol_now(), '%Y%m%d%_%H%M');
			}
			$filename .= '.'.$objmodel->getDriverExtension();
			$dirname = $conf->export->dir_temp.'/'.$user->id;

			$outputlangs = clone $langs; // We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value

			// Open file
			dol_mkdir($dirname);
			$result = $objmodel->open_file($dirname."/".$filename, $outputlangs);

			if ($result >= 0) {
				// Generate header
				$objmodel->write_header($outputlangs);

				// Generate title line
				$objmodel->write_title($this->array_export_fields[$indice], $array_selected, $outputlangs, isset($this->array_export_TypeFields[$indice]) ? $this->array_export_TypeFields[$indice] : null);

				while ($obj = $this->db->fetch_object($resql)) {
					// Process special operations
					if (!empty($this->array_export_special[$indice])) {
						foreach ($this->array_export_special[$indice] as $key => $value) {
							if (!array_key_exists($key, $array_selected)) {
								continue; // Field not selected
							}

							// TODO: Not sure why the original was not using $value directly.
							$item = $this->array_export_special[$indice][$key];
							// Operation NULLIFNEG
							if (is_string($item) && $item == 'NULLIFNEG') { // @phan-suppress-current-line PhanTypeComparisonFromArray
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								if ($obj->$alias < 0) {
									$obj->$alias = '';
								}
							} elseif (is_string($item) && $item == 'ZEROIFNEG') {
								// Operation ZEROIFNEG
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								if ($obj->$alias < 0) {
									$obj->$alias = '0';
								}
							} elseif (is_string($item) && $item == 'getNumOpenDays') {
								// Operation GETNUMOPENDAYS (for Holiday module)
								include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								$obj->$alias = num_open_day(dol_stringtotime($obj->d_date_debut, 1), dol_stringtotime($obj->d_date_fin, 1), 0, 1, $obj->d_halfday, $mysoc->country_code);
							} elseif (is_string($item) && $item == 'getRemainToPay') {
								// Operation INVOICEREMAINTOPAY
								//$alias=$this->array_export_alias[$indice][$key];
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								$remaintopay = '';
								if ($obj->f_rowid > 0) {
									global $tmpobjforcomputecall;
									if (!is_object($tmpobjforcomputecall)) {
										include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
										$tmpobjforcomputecall = new Facture($this->db);
									}
									$tmpobjforcomputecall->id = $obj->f_rowid;
									$tmpobjforcomputecall->total_ttc = $obj->f_total_ttc;
									$tmpobjforcomputecall->close_code = $obj->f_close_code;
									$remaintopay = $tmpobjforcomputecall->getRemainToPay();
								}
								$obj->$alias = $remaintopay;
							} elseif (is_array($item) &&
								array_key_exists('rule', $item) &&
								$item['rule'] == 'compute'
							) {
								// Custom compute
								$alias = str_replace(array('.', '-', '(', ')'), '_', $key);
								$value = '';
								if (!empty($item['class']) &&
									!empty($item['classfile']) &&
									!empty($item['method'])
								) {
									if (!dol_include_once($item['classfile'])) {
										$this->error = "Computed field bad configuration: {$item['classfile']} not found";
										return -1;
									}

									if (!class_exists($item['class'])) {
										$this->error = "Computed field bad configuration: {$item['class']} class doesn't exist";
										return -1;
									}

									$className = $item['class'];
									$tmpObject = new $className($this->db);
									'@phan-var-force CommonObject $tmpObject';

									if (!method_exists($tmpObject, $item['method'])) {
										$this->error = "Computed field bad configuration: {$item['method']} method doesn't exist";
										return -1;
									}

									$methodName = dol_escape_all($item['method']);
									$params = [];
									if (!empty($item['method_params'])) {
										// Example used for export of "Stocks and location (warehouse) with batch" in field "Date of last movement"
										foreach ($item['method_params'] as $paramName) {
											if (property_exists($obj, $paramName)) {
												$params[] = $obj->$paramName;
											} else {
												$params[] = $paramName;
											}
										}
									}
									//var_dump($tmpObject);var_dump($methodName);var_dump($params);exit;
									$value = $tmpObject->$methodName(...$params);
								}
								$obj->$alias = $value;
							} else {
								// TODO FIXME
								// Export of compute field does not work. $obj contains $obj->alias_field and formula may contains $obj->field
								// Also the formula may contains objects of class that are not loaded.
								$computestring = is_string($item) ? $item : json_encode($item);
								//$tmp = (string) dol_eval($computestring, 1, 0, '2');
								//$obj->$alias = $tmp;

								$this->error = "ERROPNOTSUPPORTED. Operation ".$computestring." not supported. Export of 'computed' extrafields is not yet supported, please remove field.";
								return -1;
							}
						}
					}
					// end of special operation processing
					$objmodel->write_record($array_selected, $obj, $outputlangs, isset($this->array_export_TypeFields[$indice]) ? $this->array_export_TypeFields[$indice] : null);
				}

				// Generate Footer
				$objmodel->write_footer($outputlangs);

				// Close file
				$objmodel->close_file();

				return 1;
			} else {
				$this->error = $objmodel->error;
				dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
				return -1;
			}
		} else {
			$this->error = $this->db->error()." - sql=".$sql;
			return -1;
		}
	}

	/**
	 *  Save an export model in database
	 *
	 *  @param		User	$user 	Object user that save
	 *  @return		int				Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		dol_syslog("Export.class.php::create");

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'export_model (';
		$sql .= 'label,';
		$sql .= 'type,';
		$sql .= 'field,';
		$sql .= 'fk_user,';
		$sql .= 'filter';
		$sql .= ') VALUES (';
		$sql .= "'".$this->db->escape($this->model_name)."',";
		$sql .= " '".$this->db->escape($this->datatoexport)."',";
		$sql .= " '".$this->db->escape($this->hexa)."',";
		$sql .= ' '.(isset($this->fk_user) ? (int) $this->fk_user : 'null').",";
		$sql .= " '".$this->db->escape($this->hexafiltervalue)."'";
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->errno = $this->db->lasterrno();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load an export profil from database
	 *
	 *  @param      int		$id		Id of profil to load
	 *  @return     int				Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = 'SELECT em.rowid, em.label, em.type, em.field, em.filter';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'export_model as em';
		$sql .= ' WHERE em.rowid = '.((int) $id);

		dol_syslog("Export::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->model_name = $obj->label;
				$this->datatoexport = $obj->type;

				$this->hexa = $obj->field;
				$this->hexafiltervalue = $obj->filter;

				return 1;
			} else {
				$this->error = "ModelNotFound";
				return -2;
			}
		} else {
			dol_print_error($this->db);
			return -3;
		}
	}


	/**
	 *	Delete object in database
	 *
	 *	@param      User		$user        	User that delete
	 *  @param      int			$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."export_model";
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output list all export models
	 *  --TODO Move this into a class htmlxxx.class.php--
	 *
	 *	@return	void
	 */
	public function list_export_model()
	{
		// phpcs:enable
		global $conf, $langs;

		$sql = "SELECT em.rowid, em.field, em.label, em.type, em.filter";
		$sql .= " FROM ".MAIN_DB_PREFIX."export_model as em";
		$sql .= " ORDER BY rowid";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$keyModel = array_search($obj->type, $this->array_export_code);
				print "<tr>";
				print '<td><a href=export.php?step=2&action=select_model&exportmodelid='.$obj->rowid.'&datatoexport='.$obj->type.'>'.$obj->label.'</a></td>';
				print '<td>';
				print img_object($this->array_export_module[$keyModel]->getName(), $this->array_export_icon[$keyModel]).' ';
				print $this->array_export_module[$keyModel]->getName().' - ';
				// recover export name / recuperation du nom de l'export

				$string = $langs->trans($this->array_export_label[$keyModel]);
				print($string != $this->array_export_label[$keyModel] ? $string : $this->array_export_label[$keyModel]);
				print '</td>';
				//print '<td>'.$obj->type.$keyModel.'</td>';
				print '<td>'.str_replace(',', ' , ', $obj->field).'</td>';
				if (!empty($obj->filter)) {
					$filter = json_decode($obj->filter, true);
					print '<td>'.str_replace(',', ' , ', $filter['field']).'</td>';
					print '<td>'.str_replace(',', ' , ', $filter['value']).'</td>';
				}
				// remove export / suppression de l'export
				print '<td class="right">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=deleteprof&token='.newToken().'&id='.$obj->rowid.'">';
				print img_delete();
				print '</a>';
				print "</tr>";

				$i++;
			}
		} else {
			dol_print_error($this->db);
		}
	}
}
