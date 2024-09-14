<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	Patrice Andreani		<pandreani@easya.solutions>
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
 */


/**
 * \file       htdocs/webportal/class/html.formwebportal.class.php
 * \ingroup    webportal
 * \brief      File of class with all html predefined components for WebPortal
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

/**
 * Class to manage generation of HTML components
 * Only common components for WebPortal must be here.
 */
class FormWebPortal extends Form
{
	/**
	 * @var DoliDB Database
	 */
	public $db;

	/**
	 * @var array $infofiles Array of file info
	 */
	public $infofiles; // Used to return information by function getDocumentsLink


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Html for input with label
	 *
	 * @param	string	$type			Type of input : button, checkbox, color, email, hidden, month, number, password, radio, range, tel, text, time, url, week
	 * @param	string	$name			Name
	 * @param	string	$value			[=''] Value
	 * @param	string	$id				[=''] Id
	 * @param	string	$morecss		[=''] Class
	 * @param	string	$moreparam		[=''] Add attributes (checked, required, etc)
	 * @param	string	$label			[=''] Label
	 * @param	string	$addInputLabel	[=''] Add label for input
	 * @return  string	Html for input with label
	 */
	public function inputType($type, $name, $value = '', $id = '', $morecss = '', $moreparam = '', $label = '', $addInputLabel = '')
	{
		$out = '';
		if ($label != '') {
			$out .= '<label for="' . $id . '">';
		}
		$out .= '<input type="' . $type . '"';
		$out .= ($morecss ? ' class="' . $morecss . '"' : '');
		if ($id != '') {
			$out .= ' id="' . $id . '"';
		}
		$out .= ' name="' . $name . '"';
		$out .= ' value="' . $value . '"';
		$out .= ($moreparam ? ' ' . $moreparam : '');
		$out .= ' />' . $addInputLabel;
		if ($label != '') {
			$out .= $label . '</label>';
		}

		return $out;
	}

	/**
	 * Input for date
	 *
	 * @param	string	$name			Name of html input
	 * @param	string	$value			[=''] Value of input (format : YYYY-MM-DD)
	 * @param	string	$placeholder	[=''] Placeholder for input (keep empty for no label)
	 * @param	string	$id				[=''] Id
	 * @param	string	$morecss		[=''] Class
	 * @param	string	$moreparam		[=''] Add attributes (checked, required, etc)
	 * @return	string  Html for input date
	 */
	public function inputDate($name, $value = '', $placeholder = '', $id = '', $morecss = '', $moreparam = '')
	{
		$out = '';

		$out .= '<input';
		if ($placeholder != '' && $value == '') {
			// to show a placeholder on date input
			$out .= ' type="text" placeholder="' . $placeholder . '" onfocus="(this.type=\'date\')"';
		} else {
			$out .= ' type="date"';
		}
		$out .= ($morecss ? ' class="' . $morecss . '"' : '');
		if ($id != '') {
			$out .= ' id="' . $id . '"';
		}
		$out .= ' name="' . $name . '"';
		$out .= ' value="' . $value . '"';
		$out .= ($moreparam ? ' ' . $moreparam : '');

		$out .= '>';

		return $out;
	}

	/**
	 * Return a HTML select string, built from an array of key+value.
	 * Note: Do not apply langs->trans function on returned content, content may be entity encoded twice.
	 *
	 * @param	string				$htmlname				Name of html select area.
	 * @param	array				$array					Array like array(key => value) or array(key=>array('label'=>..., 'data-...'=>..., 'disabled'=>..., 'css'=>...))
	 * @param	string|string[]		$id						Preselected key or preselected keys for multiselect. Use 'ifone' to autoselect record if there is only one record.
	 * @param	int|string			$show_empty				0 no empty value allowed, 1 or string to add an empty value into list (If 1: key is -1 and value is '' or '&nbsp;', If placeholder string: key is -1 and value is the string), <0 to add an empty value with key that is this value.
	 * @param	int					$key_in_label			1 to show key into label with format "[key] value"
	 * @param	int					$value_as_key			1 to use value as key
	 * @param	string				$moreparam				Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	 * @param	int					$translate				1=Translate and encode value
	 * @param	int					$maxlen					Length maximum for labels
	 * @param	int					$disabled				Html select box is disabled
	 * @param	string				$sort					'ASC' or 'DESC' = Sort on label, '' or 'NONE' or 'POS' = Do not sort, we keep original order
	 * @param	string				$morecss				Add more class to css styles
	 * @param	int					$addjscombo				Add js combo
	 * @param	string				$moreparamonempty		Add more param on the empty option line. Not used if show_empty not set
	 * @param	int					$disablebademail		1=Check if a not valid email, 2=Check string '---', and if found into value, disable and colorize entry
	 * @param	int					$nohtmlescape			No html escaping.
	 * @return	string				HTML select string.
	 */
	public static function selectarray($htmlname, $array, $id = '', $show_empty = 0, $key_in_label = 0, $value_as_key = 0, $moreparam = '', $translate = 0, $maxlen = 0, $disabled = 0, $sort = '', $morecss = 'minwidth75', $addjscombo = 1, $moreparamonempty = '', $disablebademail = 0, $nohtmlescape = 0)
	{
		global $langs;

		if ($value_as_key) {
			$array = array_combine($array, $array);
		}

		$out = '';

		$idname = str_replace(array('[', ']'), array('', ''), $htmlname);
		$out .= '<select id="' . preg_replace('/^\./', '', $idname) . '"' . ($disabled ? ' disabled="disabled"' : '') . ' class="' . ($morecss ? ' ' . $morecss : '') . '"';
		$out .= ' name="' . preg_replace('/^\./', '', $htmlname) . '"' . ($moreparam ? ' ' . $moreparam : '');
		$out .= '>' . "\n";

		if ($show_empty) {
			$textforempty = ' ';
			if (!is_numeric($show_empty)) {
				$textforempty = $show_empty;
			}
			$out .= '<option value="' . ($show_empty < 0 ? $show_empty : -1) . '"' . ($id == $show_empty ? ' selected' : '') . '>' . $textforempty . '</option>' . "\n";
		}

		if (is_array($array)) {
			// Translate
			if ($translate) {
				foreach ($array as $key => $value) {
					if (!is_array($value)) {
						$array[$key] = $langs->trans($value);
					} else {
						$array[$key]['label'] = $langs->trans($value['label']);
					}
				}
			}

			// Sort
			if ($sort == 'ASC') {
				asort($array);
			} elseif ($sort == 'DESC') {
				arsort($array);
			}

			foreach ($array as $key => $tmpvalue) {
				if (is_array($tmpvalue)) {
					$value = $tmpvalue['label'];
					$disabled = empty($tmpvalue['disabled']) ? '' : ' disabled';
				} else {
					$value = $tmpvalue;
					$disabled = '';
				}

				if ($key_in_label) {
					$selectOptionValue = dol_escape_htmltag($key . ' - ' . ($maxlen ? dol_trunc($value, $maxlen) : $value));
				} else {
					$selectOptionValue = dol_escape_htmltag($maxlen ? dol_trunc($value, $maxlen) : $value);
					if ($value == '' || $value == '-') {
						$selectOptionValue = '&nbsp;';
					}
				}

				$out .= '<option value="' . $key . '"';
				$out .= $disabled;
				if (is_array($id)) {
					if (in_array($key, $id) && !$disabled) {
						$out .= ' selected'; // To preselect a value
					}
				} else {
					$id = (string) $id; // if $id = 0, then $id = '0'
					if ($id != '' && ($id == $key || ($id == 'ifone' && count($array) == 1)) && !$disabled) {
						$out .= ' selected'; // To preselect a value
					}
				}
				if (is_array($tmpvalue)) {
					foreach ($tmpvalue as $keyforvalue => $valueforvalue) {
						if (preg_match('/^data-/', $keyforvalue)) {
							$out .= ' ' . $keyforvalue . '="' . dol_escape_htmltag($valueforvalue) . '"';
						}
					}
				}
				$out .= '>';
				$out .= $selectOptionValue;
				$out .= "</option>\n";
			}
		}

		$out .= "</select>";

		return $out;
	}

	/**
	 * Show a Document icon with link(s)
	 * You may want to call this into a div like this:
	 * print '<div class="inline-block valignmiddle">'.$formfile->getDocumentsLink($element_doc, $filename, $filedir).'</div>';
	 *
	 * @param string $modulepart 'propal', 'facture', 'facture_fourn', ...
	 * @param string $modulesubdir Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if file is not into subdir of module.
	 * @param string $filedir Full path to directory to scan
	 * @param string $filter Filter filenames on this regex string (Example: '\.pdf$')
	 * @param string $morecss Add more css to the download picto
	 * @param int    $allfiles 0=Only generated docs, 1=All files
	 * @return    string                Output string with HTML link of documents (might be empty string). This also fill the array ->infofiles
	 */
	public function getDocumentsLink($modulepart, $modulesubdir, $filedir, $filter = '', $morecss = '', $allfiles = 0)
	{
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$out = '';

		$context = Context::getInstance();
		if (!$context) {
			return '';
		}

		$this->infofiles = array('nboffiles' => 0, 'extensions' => array(), 'files' => array());

		$entity = 1; // Without multicompany

		// Get object entity
		if (isModEnabled('multicompany')) {
			$regs = array();
			preg_match('/\/([0-9]+)\/[^\/]+\/' . preg_quote($modulesubdir, '/') . '$/', $filedir, $regs);
			$entity = ((!empty($regs[1]) && $regs[1] > 1) ? $regs[1] : 1); // If entity id not found in $filedir this is entity 1 by default
		}

		// Get list of files starting with name of ref (Note: files with '^ref\.extension' are generated files, files with '^ref-...' are uploaded files)
		if ($allfiles || getDolGlobalString('MAIN_SHOW_ALL_FILES_ON_DOCUMENT_TOOLTIP')) {
			$filterforfilesearch = '^' . preg_quote(basename($modulesubdir), '/');
		} else {
			$filterforfilesearch = '^' . preg_quote(basename($modulesubdir), '/') . '\.';
		}
		$file_list = dol_dir_list($filedir, 'files', 0, $filterforfilesearch, '\.meta$|\.png$'); // We also discard .meta and .png preview

		//var_dump($file_list);
		// For ajax treatment
		$out .= '<!-- html.formwebportal::getDocumentsLink -->' . "\n";
		if (!empty($file_list)) {
			$tmpout = '';

			// Loop on each file found
			$found = 0;
			$i = 0;
			foreach ($file_list as $file) {
				$i++;
				if ($filter && !preg_match('/' . $filter . '/i', $file["name"])) {
					continue; // Discard this. It does not match provided filter.
				}

				$found++;
				// Define relative path for download link (depends on module)
				$relativepath = $file["name"]; // Cas general
				if ($modulesubdir) {
					$relativepath = $modulesubdir . "/" . $file["name"]; // Cas propal, facture...
				}
				// Autre cas
				if ($modulepart == 'donation') {
					$relativepath = get_exdir($modulesubdir, 2, 0, 0, null, 'donation') . $file["name"];
				}
				if ($modulepart == 'export') {
					$relativepath = $file["name"];
				}

				$this->infofiles['nboffiles']++;
				$this->infofiles['files'][] = $file['fullname'];
				$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
				if (empty($this->infofiles[$ext])) {
					$this->infofiles['extensions'][$ext] = 1;
				} else {
					$this->infofiles['extensions'][$ext]++;
				}

				// Download
				$url = $context->getControllerUrl('document') . '&modulepart=' . $modulepart . '&entity=' . $entity . '&file=' . urlencode($relativepath) . '&soc_id=' . $context->logged_thirdparty->id;
				$tmpout .= '<a href="' . $url . '"' . ($morecss ? ' class="' . $morecss . '"' : '') . ' role="downloadlink"';
				$mime = dol_mimetype($relativepath, '', 0);
				if (preg_match('/text/', $mime)) {
					$tmpout .= ' target="_blank" rel="noopener noreferrer"';
				}
				$tmpout .= '>';
				$tmpout .= img_mime($relativepath, $file["name"]);
				$tmpout .= strtoupper($ext);
				$tmpout .= '</a>';
			}

			if ($found) {
				$out .= $tmpout;
			}
		}

		return $out;
	}

	/**
	 * Show a Signature icon with link
	 * You may want to call this into a div like this:
	 * print '<div class="inline-block valignmiddle">'.$formfile->getDocumentsLink($element_doc, $filename, $filedir).'</div>';
	 *
	 * @param string $modulepart 'proposal', 'facture', 'facture_fourn', ...
	 * @param Object $object Object linked to the document to be signed
	 * @param string $morecss Add more css to the download picto
	 * @return    string                Output string with HTML link of signature (might be empty string).
	 */
	public function getSignatureLink($modulepart, $object, $morecss = '')
	{
		global $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
		$out = '<!-- html.formwebportal::getSignatureLink -->' . "\n";
		$url = getOnlineSignatureUrl(0, $modulepart, $object->ref, 1, $object);
		if (!empty($url)) {
			$out .= '<a target="_blank" rel="noopener noreferrer" href="' . $url . '"' . ($morecss ? ' class="' . $morecss . '"' : '') . ' role="signaturelink">';
			$out .= '<i class="fa fa-file-signature"></i>';
			$out .= $langs->trans("Sign");
			$out .= '</a>';
		}
		return $out;
	}

	/**
	 * Generic method to select a component from a combo list.
	 * Can use autocomplete with ajax after x key pressed or a full combo, depending on setup.
	 * This is the generic method that will replace all specific existing methods.
	 *
	 * @param 	string 	$objectdesc 			'ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'. For hard coded custom needs. Try to prefer method using $objectfield instead of $objectdesc.
	 * @param 	string 	$htmlname 				Name of HTML select component
	 * @param 	int 	$preselectedvalue 		Preselected value (ID of element)
	 * @param 	string 	$showempty 				''=empty values not allowed, 'string'=value show if we allow empty values (for example 'All', ...)
	 * @param 	string 	$searchkey 				Search criteria
	 * @param 	string 	$placeholder 			Place holder
	 * @param 	string 	$morecss 				More CSS
	 * @param 	string 	$moreparams 			More params provided to ajax call
	 * @param 	int 	$forcecombo 			Force to load all values and output a standard combobox (with no beautification)
	 * @param 	int 	$disabled 				1=Html component is disabled
	 * @param 	string 	$selected_input_value 	Value of preselected input text (for use with ajax)
	 * @param	string	$objectfield			Object:Field that contains the definition (in table $fields or $extrafields). Example: 'Object:xxx' or 'Module_Object:xxx' or 'Object:options_xxx' or 'Module_Object:options_xxx'
	 * @return  string	                      	Return HTML string
	 * @see selectForFormsList(), select_thirdparty_list()
	 */
	public function selectForForms($objectdesc, $htmlname, $preselectedvalue, $showempty = '', $searchkey = '', $placeholder = '', $morecss = '', $moreparams = '', $forcecombo = 0, $disabled = 0, $selected_input_value = '', $objectfield = '')
	{
		global $conf;

		$objecttmp = null;

		// Example of value for $objectdec:
		// Bom:bom/class/bom.class.php:0:t.status=1
		// Bom:bom/class/bom.class.php:0:t.status=1:ref
		// Bom:bom/class/bom.class.php:0:(t.status:=:1):ref
		$InfoFieldList = explode(":", $objectdesc, 4);
		$vartmp = (empty($InfoFieldList[3]) ? '' : $InfoFieldList[3]);
		$reg = array();
		if (preg_match('/^.*:(\w*)$/', $vartmp, $reg)) {
			$InfoFieldList[4] = $reg[1];    // take the sort field
		}
		$InfoFieldList[3] = preg_replace('/:\w*$/', '', $vartmp);    // take the filter field

		$classname = $InfoFieldList[0];
		$classpath = $InfoFieldList[1];
		$filter = empty($InfoFieldList[3]) ? '' : $InfoFieldList[3];
		$sortfield = empty($InfoFieldList[4]) ? '' : $InfoFieldList[4];

		if (!empty($classpath)) {
			dol_include_once($classpath);

			if ($classname && class_exists($classname)) {
				$objecttmp = new $classname($this->db);

				// Make some replacement
				$sharedentities = getEntity(strtolower($classname));
				$filter = str_replace(
					array('__ENTITY__', '__SHARED_ENTITIES__'),
					array($conf->entity, $sharedentities),
					$filter
				);
			}
		}
		if (!is_object($objecttmp)) {
			dol_syslog('Error bad setup of type for field ' . implode(',', $InfoFieldList), LOG_WARNING);
			return 'Error bad setup of type for field ' . implode(',', $InfoFieldList);
		}

		dol_syslog(__METHOD__ . ' filter=' . $filter, LOG_DEBUG);
		$out = '';
		// Immediate load of table record.
		$out .= $this->selectForFormsList($objecttmp, $htmlname, $preselectedvalue, $showempty, $searchkey, $placeholder, $morecss, $moreparams, $forcecombo, 0, $disabled, $sortfield, $filter);

		return $out;
	}

	/**
	 * Output html form to select an object.
	 * Note, this function is called by selectForForms or by ajax selectobject.php
	 *
	 * @param Object 		$objecttmp 			Object to know the table to scan for combo.
	 * @param string 		$htmlname 			Name of HTML select component
	 * @param int 			$preselectedvalue 	Preselected value (ID of element)
	 * @param string 		$showempty 			''=empty values not allowed, 'string'=value show if we allow empty values (for example 'All', ...)
	 * @param string 		$searchkey 			Search value
	 * @param string 		$placeholder 		Place holder
	 * @param string 		$morecss 			More CSS
	 * @param string 		$moreparams 		More params provided to ajax call
	 * @param int 			$forcecombo 		Force to load all values and output a standard combobox (with no beautification)
	 * @param int 			$outputmode 		0=HTML select string, 1=Array
	 * @param int 			$disabled 			1=Html component is disabled
	 * @param string 		$sortfield 			Sort field
	 * @param string 		$filter 			Add more filter (Universal Search Filter)
	 * @return string|array                     Return HTML string
	 * @see selectForForms()
	 */
	public function selectForFormsList($objecttmp, $htmlname, $preselectedvalue, $showempty = '', $searchkey = '', $placeholder = '', $morecss = '', $moreparams = '', $forcecombo = 0, $outputmode = 0, $disabled = 0, $sortfield = '', $filter = '')
	{
		global $conf, $langs, $hookmanager;

		$prefixforautocompletemode = $objecttmp->element;
		if ($prefixforautocompletemode == 'societe') {
			$prefixforautocompletemode = 'company';
		}
		$confkeyforautocompletemode = strtoupper($prefixforautocompletemode) . '_USE_SEARCH_TO_SELECT'; // For example COMPANY_USE_SEARCH_TO_SELECT

		if (in_array($objecttmp->element, array('adherent_type'))) {
			$fieldstoshow = 't.libelle';
		}
		if (!empty($objecttmp->fields)) {    // For object that declare it, it is better to use declared fields (like societe, contact, ...)
			$tmpfieldstoshow = '';
			foreach ($objecttmp->fields as $key => $val) {
				if (! (int) dol_eval($val['enabled'], 1, 1, '1')) {
					continue;
				}
				if (!empty($val['showoncombobox'])) {
					$tmpfieldstoshow .= ($tmpfieldstoshow ? ',' : '') . 't.' . $key;
				}
			}
			if ($tmpfieldstoshow) {
				$fieldstoshow = $tmpfieldstoshow;
			}
		} elseif (!in_array($objecttmp->element, array('adherent_type'))) {
			// For backward compatibility
			$objecttmp->fields['ref'] = array('type' => 'varchar(30)', 'label' => 'Ref', 'showoncombobox' => 1);
		}

		if (empty($fieldstoshow)) {
			if (isset($objecttmp->fields['ref'])) {
				$fieldstoshow = 't.ref';
			} else {
				$langs->load("errors");
				$this->error = $langs->trans("ErrorNoFieldWithAttributeShowoncombobox");
				return $langs->trans('ErrorNoFieldWithAttributeShowoncombobox');
			}
		}

		$out = '';
		$outarray = array();
		$tmparray = array();

		$num = 0;

		// Search data
		$sql = "SELECT t.rowid, " . $fieldstoshow . " FROM " . $this->db->prefix() . $objecttmp->table_element . " as t";
		if (isset($objecttmp->ismultientitymanaged)) {
			if (!is_numeric($objecttmp->ismultientitymanaged)) {
				$tmparray = explode('@', $objecttmp->ismultientitymanaged);
				$sql .= " INNER JOIN " . $this->db->prefix() . $tmparray[1] . " as parenttable ON parenttable.rowid = t." . $tmparray[0];
			}
		}

		// Add where from hooks
		$parameters = array(
			'object' => $objecttmp,
			'htmlname' => $htmlname,
			'filter' => $filter,
			'searchkey' => $searchkey
		);

		$reshook = $hookmanager->executeHooks('selectForFormsListWhere', $parameters); // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql .= $hookmanager->resPrint;
		} else {
			$sql .= " WHERE 1=1";
			if (isset($objecttmp->ismultientitymanaged)) {
				if ($objecttmp->ismultientitymanaged == 1) {
					$sql .= " AND t.entity IN (" . getEntity($objecttmp->table_element) . ")";
				}
				if (!is_numeric($objecttmp->ismultientitymanaged)) {
					$sql .= " AND parenttable.entity = t." . $tmparray[0];
				}
			}
			if ($searchkey != '') {
				$sql .= natural_search(explode(',', $fieldstoshow), $searchkey);
			}

			if ($filter) {     // Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
				$errormessage = '';
				$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
				if ($errormessage) {
					return 'Error forging a SQL request from an universal criteria: ' . $errormessage;
				}
			}
		}
		$sql .= $this->db->order($sortfield ? $sortfield : $fieldstoshow, "ASC");

		// Build output string
		$resql = $this->db->query($sql);
		if ($resql) {
			// Construct $out and $outarray
			$out .= '<select id="' . $htmlname . '" class="' . ($morecss ? ' ' . $morecss : '') . '"' . ($disabled ? ' disabled="disabled"' : '') . ($moreparams ? ' ' . $moreparams : '') . ' name="' . $htmlname . '">' . "\n";

			// Warning: Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'. Seems it is no more true with selec2 v4
			$textifempty = '&nbsp;';

			//if (!empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
			if (getDolGlobalString($confkeyforautocompletemode)) {
				if ($showempty && !is_numeric($showempty)) {
					$textifempty = $langs->trans($showempty);
				} else {
					$textifempty .= $langs->trans("All");
				}
			}
			if ($showempty) {
				$out .= '<option value="-1">' . $textifempty . '</option>' . "\n";
			}

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$label = '';
					$labelhtml = '';
					$tmparray = explode(',', $fieldstoshow);
					$oldvalueforshowoncombobox = 0;
					foreach ($tmparray as $key => $val) {
						$val = preg_replace('/t\./', '', $val);
						$label .= (($label && $obj->$val) ? ($oldvalueforshowoncombobox != $objecttmp->fields[$val]['showoncombobox'] ? ' - ' : ' ') : '');
						$labelhtml .= (($label && $obj->$val) ? ($oldvalueforshowoncombobox != $objecttmp->fields[$val]['showoncombobox'] ? ' - ' : ' ') : '');
						$label .= $obj->$val;
						$labelhtml .= $obj->$val;

						$oldvalueforshowoncombobox = empty($objecttmp->fields[$val]['showoncombobox']) ? 0 : $objecttmp->fields[$val]['showoncombobox'];
					}
					if (empty($outputmode)) {
						if ($preselectedvalue > 0 && $preselectedvalue == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '" selected data-html="' . dol_escape_htmltag($labelhtml, 0, 0, '', 0, 1) . '">' . dol_escape_htmltag($label, 0, 0, '', 0, 1) . '</option>';
						} else {
							$out .= '<option value="' . $obj->rowid . '" data-html="' . dol_escape_htmltag($labelhtml, 0, 0, '', 0, 1) . '">' . dol_escape_htmltag($label, 0, 0, '', 0, 1) . '</option>';
						}
					} else {
						array_push($outarray, array('key' => $obj->rowid, 'value' => $label, 'label' => $label));
					}

					$i++;
					if (($i % 10) == 0) {
						$out .= "\n";
					}
				}
			}

			$out .= '</select>' . "\n";
		} else {
			dol_print_error($this->db);
		}

		$this->result = array('nbofelement' => $num);

		if ($outputmode) {
			return $outarray;
		}

		return $out;
	}

	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField for common object
	 *
	 * @param array|null 	$val 			Array of properties for field to show
	 * @param string 		$key 			Key of attribute
	 * @param string|array 	$value 			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param string 		$moreparam 		To add more parameters on html input tag
	 * @param string 		$keysuffix 		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string 		$keyprefix 		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string 		$morecss 		Value for css to define style/length of field. May also be a numeric.
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs;

		$out = '';
		$param = array();
		$reg = array();
		$size = !empty($val['size']) ? $val['size'] : 0;
		// see common object class
		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^varchar\((\d+)\)/', $val['type'], $reg)) {
			$param['options'] = array();
			$type = 'text';
			$size = $reg[1];
		} elseif (preg_match('/^varchar/', $val['type'])) {
			$param['options'] = array();
			$type = 'text';
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $val['type'])) {
			$param['options'] = array();
			$type = 'double';
		} else {
			$param['options'] = array();
			$type = $val['type'];
		}

		// Special case that force options and type ($type can be integer, varchar, ...)
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$param['options'] = $val['arrayofkeyval'];
			$type = $val['type'] == 'checkbox' ? 'checkbox' : 'select';
		}

		//$label = $val['label'];
		$default = (!empty($val['default']) ? $val['default'] : '');
		$computed = (!empty($val['computed']) ? $val['computed'] : '');
		//$unique = (!empty($val['unique']) ? $val['unique'] : 0);
		$required = (!empty($val['required']) ? $val['required'] : 0);
		$notNull = (!empty($val['notnull']) ? $val['notnull'] : 0);

		//$langfile = (!empty($val['langfile']) ? $val['langfile'] : '');
		//$list = (!empty($val['list']) ? $val[$key]['list'] : 0);
		$hidden = (in_array(abs($val['visible']), array(0, 2)) ? 1 : 0);

		//$objectid = $this->id;

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) {
				return '<span>' . $langs->trans("AutomaticallyCalculated") . '</span>';
			} else {
				return '';
			}
		}

		// Set value of $morecss. For this, we use in priority showsize from parameters, then $val['css'] then autodefine
		if (empty($morecss) && !empty($val['css'])) {
			$morecss = $val['css'];
		}

		$htmlName = $keyprefix . $key . $keysuffix;
		$htmlId = $htmlName;
		//$moreparam .= (!empty($required)  ? ' required' : '');
		switch ($type) {
			case 'date':
			case 'datetime':
				// separate value YYYY-MM-DD HH:ii:ss to date and time
				$valueDate = '';
				$valueTime = '';
				$dateArr = explode(' ', $value);
				if (count($dateArr) > 0) {
					$valueDate = $dateArr[0];
					if (isset($dateArr[1])) {
						$valueTime = $dateArr[1];
					}
				}
				$out = $this->inputDate($htmlName, $valueDate, '', $htmlId, $morecss, $moreparam);

				if ($type == 'datetime') {
					//$moreparam .= ' step="1"'; to show seconds
					$out .= ' ' . $this->inputType('time', $htmlName.'_time', $valueTime, $htmlId, $morecss, $moreparam);
				}
				break;

			case 'integer':
				$out = $this->inputType('number', $htmlName, dol_escape_htmltag($value), $htmlId, $morecss, $moreparam);
				break;

			case 'text':
			case 'html':
				$moreparam .= ($size > 0 ? ' maxlength="' . $size . '"' : '');
				$out = $this->inputType('text', $htmlName, dol_escape_htmltag($value), $htmlId, $morecss, $moreparam);
				break;

			case 'email':
				$out = $this->inputType('email', $htmlName, dol_escape_htmltag($value), $htmlId, $morecss, $moreparam);
				break;

			case 'tel':
				$out = $this->inputType('tel', $htmlName, dol_escape_htmltag($value), $htmlId, $morecss, $moreparam);
				break;

			case 'url':
				$out = $this->inputType('url', $htmlName, dol_escape_htmltag($value), $htmlId, $morecss, $moreparam);
				break;

			case 'price':
				if (!empty($value)) {
					$value = price($value); // $value in memory is a php numeric, we format it into user number format.
				}
				$addInputLabel = ' ' . $langs->getCurrencySymbol($conf->currency);
				$out = $this->inputType('text', $htmlName, $value, $htmlId, $morecss, $moreparam, '', $addInputLabel);
				break;

			case 'double':
				if (!empty($value)) {
					$value = price($value); // $value in memory is a php numeric, we format it into user number format.
				}
				$out = $this->inputType('text', $htmlName, $value, $htmlId, $morecss, $moreparam);
				break;

			case 'password':
				$out = $this->inputType('password', $htmlName, $value, $htmlId, $morecss, $moreparam);
				break;

			case 'radio':
				foreach ($param['options'] as $keyopt => $valopt) {
					$htmlId = $htmlName . '_' . $keyopt;
					$htmlMoreParam = $moreparam . ($value == $keyopt ? ' checked' : '');
					$out .= $this->inputType('radio', $htmlName, $keyopt, $htmlId, $morecss, $htmlMoreParam, $valopt) . '<br>';
				}
				break;

			case 'select':
				$out = '<select class="' . $morecss . '" name="' . $htmlName . '" id="' . $htmlId . '"' . ($moreparam ? ' ' . $moreparam : '') . ' >';
				if ($default == '' || $notNull != 1) {
					$out .= '<option value="0">&nbsp;</option>';
				}
				foreach ($param['options'] as $keyb => $valb) {
					if ($keyb == '') {
						continue;
					}
					if (strpos($valb, "|") !== false) {
						list($valb, $parent) = explode('|', $valb);
					}
					$out .= '<option value="' . $keyb . '"';
					$out .= (((string) $value == $keyb) ? ' selected' : '');
					$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
					$out .= '>' . $valb . '</option>';
				}
				$out .= '</select>';
				break;
			case 'sellist':
				$out = '<select class="' . $morecss . '" name="' . $htmlName . '" id="' . $htmlId . '"' . ($moreparam ? ' ' . $moreparam : '') . '>';

				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				// 5 : id category type
				// 6 : ids categories list separated by comma for category root
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if (!$filter_categorie) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT " . $keyList;
					$sql .= " FROM " . $this->db->prefix() . $InfoFieldList[0];
					if (!empty($InfoFieldList[4])) {
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);

						//We have to join on extrafield table
						if (strpos($InfoFieldList[4], 'extra') !== false) {
							$sql .= " as main, " . $this->db->prefix() . $InfoFieldList[0] . "_extrafields as extra";
							$sqlwhere .= " WHERE extra.fk_object=main." . $InfoFieldList[2] . " AND " . $InfoFieldList[4];
						} else {
							$sqlwhere .= " WHERE " . $InfoFieldList[4];
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = " . ((int) $conf->entity);
					}
					$sql .= $sqlwhere;
					//print $sql;

					$sql .= ' ORDER BY ' . implode(', ', $fields_label);

					dol_syslog(get_class($this) . '::showInputField type=sellist', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$out .= '<option value="0">&nbsp;</option>';
						$num = $this->db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							// Several field into label (eq table:code|libelle:rowid)
							$notrans = false;
							$fields_label = explode('|', $InfoFieldList[1]);
							if (count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow . ' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if ($value == $obj->rowid) {
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow = dol_trunc($translabel) . ' ';
									} else {
										$labeltoshow = dol_trunc($obj->$field_toshow) . ' ';
									}
								}
								$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 18);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]});
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}
								if ($value == $obj->rowid) {
									$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
									$isDependList = 1;
								}

								$out .= '<option value="' . $obj->rowid . '"';
								$out .= ($value == $obj->rowid ? ' selected' : '');
								$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
								$out .= '>' . $labeltoshow . '</option>';
							}

							$i++;
						}
						$this->db->free($resql);
					} else {
						$out .= 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
					$categorytype = $InfoFieldList[5];
					if (is_numeric($categorytype)) {
						$categorytype = Categorie::$MAP_ID_TO_CODE[$categorytype]; // For backward compatibility
					}
					$data = $this->select_all_categories($categorytype, '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out .= '<option value="0">&nbsp;</option>';
					foreach ($data as $data_key => $data_value) {
						$out .= '<option value="' . $data_key . '"';
						$out .= ($value == $data_key ? ' selected' : '');
						$out .= '>' . $data_value . '</option>';
					}
				}
				$out .= '</select>';
				break;

			case 'link':
				$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
				$showempty = (($required && $default != '') ? '0' : '1');

				$out = $this->selectForForms($param_list[0], $htmlName, $value, $showempty, '', '', $morecss, $moreparam, 0, empty($val['disabled']) ? 0 : 1);

				break;

			default:
				if (!empty($hidden)) {
					$out = $this->inputType('hidden', $htmlName, $value, $htmlId);
				}
				break;
		}

		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param CommonObject $object Common object
	 * @param array $val Array of properties of field to show
	 * @param string $key Key of attribute
	 * @param string|string[] $value Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param string $moreparam To add more parameters on html input tag
	 * @param string $keysuffix Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string $keyprefix Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param mixed $morecss Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputFieldForObject($object, $val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs;

		$label = empty($val['label']) ? '' : $val['label'];
		$type = empty($val['type']) ? '' : $val['type'];
		$css = empty($val['css']) ? '' : $val['css'];
		$picto = empty($val['picto']) ? '' : $val['picto'];
		$reg = array();

		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
			$css = $reg[1];
		} elseif (preg_match('/varchar/', $type)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
		}
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$type = $val['type'] == 'checkbox' ? 'checkbox' : 'select';
		}
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
		}

		$default = empty($val['default']) ? '' : $val['default'];
		$computed = empty($val['computed']) ? '' : $val['computed'];
		$unique = empty($val['unique']) ? '' : $val['unique'];
		$required = empty($val['required']) ? '' : $val['required'];
		$param = array();
		$param['options'] = array();

		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$param['options'] = $val['arrayofkeyval'];
		}
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
			$stringforoptions = $reg[1] . ':' . $reg[2];
			if ($reg[1] == 'User') {
				$stringforoptions .= ':-1';
			}
			$param['options'] = array($stringforoptions => $stringforoptions);
		} elseif (preg_match('/^sellist:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] => 'N');
			$type = 'chkbxlst';
		}

		$langfile = empty($val['langfile']) ? '' : $val['langfile'];
		$list = (empty($val['list']) ? '' : $val['list']);
		$help = (empty($val['help']) ? '' : $val['help']);
		$hidden = (($val['visible'] == 0) ? 1 : 0); // If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) {
			return '';
		}

		// If field is a computed field, value must become result of compute
		if ($computed) {
			// Make the eval of compute string
			//var_dump($computed);
			$value = (string) dol_eval($computed, 1, 0, '2');
		}

		// Format output value differently according to properties of field
		//
		// First the cases that do not use $value from the arguments:
		//
		if (in_array($key, array('rowid', 'ref'))) {
			if (property_exists($object, 'ref')) {
				$value = $object->ref;
			} elseif (property_exists($object, 'id')) {
				$value = $object->id;
			} else {
				$value = '';
			}
		} elseif ($key == 'status' && method_exists($object, 'getLibStatut')) {
			$value = $object->getLibStatut(3);
			//
			// Then the cases where $value is an array
			//
		} elseif (is_array($value)) {
			// Handle array early to get type identification solve for static
			// analysis
			if ($type == 'array') {
				$value = implode('<br>', $value);
			} else {
				dol_syslog(__METHOD__."Unexpected type=".$type." for array value=".((string) json_encode($value)), LOG_ERR);
			}
			//
			// Then the cases where $value is not an array (hence string)
			//
		} elseif ($type == 'date') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'day');    // We suppose dates without time are always gmt (storage of course + output)
			} else {
				$value = '';
			}
		} elseif ($type == 'datetime' || $type == 'timestamp') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'dayhour', 'tzuserrel');
			} else {
				$value = '';
			}
		} elseif ($type == 'duration') {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
			if (!is_null($value) && $value !== '') {
				$value = convertSecondToTime((int) $value, 'allhourmin');
			}
		} elseif ($type == 'double' || $type == 'real') {
			if (!is_null($value) && $value !== '') {
				$value = price($value);
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked ';
			}
			$value = '<input type="checkbox" ' . $checked . ' ' . ($moreparam ? $moreparam : '') . ' readonly disabled>';
		} elseif ($type == 'mail' || $type == 'email') {
			$value = dol_print_email($value, 0, 0, 0, 64, 1, 1);
		} elseif ($type == 'url') {
			$value = dol_print_url($value, '_blank', 32, 1);
		} elseif ($type == 'phone') {
			$value = dol_print_phone($value, '', 0, 0, '', '&nbsp;', 'phone');
		} elseif ($type == 'ip') {
			$value = dol_print_ip($value, 0);
		} elseif ($type == 'price') {
			if (!is_null($value) && $value !== '') {
				$value = price($value, 0, $langs, 0, 0, -1, $conf->currency);
			}
		} elseif ($type == 'select') {
			$value = isset($param['options'][$value]) ? $param['options'][$value] : '';
		} elseif ($type == 'sellist') {
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT " . $keyList;
			$sql .= ' FROM ' . $this->db->prefix() . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			if ($selectkey == 'rowid' && empty($value)) {
				$sql .= " WHERE " . $selectkey . " = 0";
			} elseif ($selectkey == 'rowid') {
				$sql .= " WHERE " . $selectkey . " = " . ((int) $value);
			} else {
				$sql .= " WHERE " . $selectkey . " = '" . $this->db->escape($value) . "'";
			}

			dol_syslog(__METHOD__ . ' type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output
					$numrows = $this->db->num_rows($resql);
					if ($numrows) {
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);

						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ($fields_label as $field_toshow) {
								$translabel = '';
								if (!empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$value .= dol_trunc($translabel, 18) . ' ';
								} else {
									$value .= $obj->$field_toshow . ' ';
								}
							}
						} else {
							$translabel = '';
							if (!empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$value = dol_trunc($translabel, 18);
							} else {
								$value = $obj->{$InfoFieldList[1]};
							}
						}
					}
				} else {
					require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

					$toprint = array();
					$obj = $this->db->fetch_object($resql);
					$c = new Categorie($this->db);
					$c->fetch($obj->rowid);
					$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
					foreach ($ways as $way) {
						$toprint[] = '<li>' . img_object('', 'category') . ' ' . $way . '</li>';
					}
					$value = '<div><ul>' . implode(' ', $toprint) . '</ul></div>';
				}
			} else {
				dol_syslog(__METHOD__ . ' error ' . $this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'radio') {
			$value = $param['options'][$value];
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$value = '';
			if (is_array($value_arr) && count($value_arr) > 0) {
				$toprint = array();
				foreach ($value_arr as $valueval) {
					if (!empty($valueval)) {
						$toprint[] = '<li>' . $param['options'][$valueval] . '</li>';
					}
				}
				if (!empty($toprint)) {
					$value = '<div><ul>' . implode(' ', $toprint) . '</ul></div>';
				}
			}
		} elseif ($type == 'chkbxlst') {
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT " . $keyList;
			$sql .= ' FROM ' . $this->db->prefix() . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(__METHOD__ . ' type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output
					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							if (is_array($fields_label) && count($fields_label) > 1) {
								foreach ($fields_label as $field_toshow) {
									$translabel = '';
									if (!empty($obj->$field_toshow)) {
										$translabel = $langs->trans($obj->$field_toshow);
									}
									if ($translabel != $field_toshow) {
										$toprint[] = '<li>' . dol_trunc($translabel, 18) . '</li>';
									} else {
										$toprint[] = '<li>' . $obj->$field_toshow . '</li>';
									}
								}
							} else {
								$translabel = '';
								if (!empty($obj->{$InfoFieldList[1]})) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								}
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$toprint[] = '<li>' . dol_trunc($translabel, 18) . '</li>';
								} else {
									$toprint[] = '<li>' . $obj->{$InfoFieldList[1]} . '</li>';
								}
							}
						}
					}
				} else {
					require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							$c = new Categorie($this->db);
							$c->fetch($obj->rowid);
							$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
							foreach ($ways as $way) {
								$toprint[] = '<li>' . img_object('', 'category') . ' ' . $way . '</li>';
							}
						}
					}
				}
				$value = '<div><ul>' . implode(' ', $toprint) . '</ul></div>';
			} else {
				dol_syslog(__METHOD__ . ' error ' . $this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'link') {
			// only if something to display (perf)
			if ($value) {
				$param_list = array_keys($param['options']); // Example: $param_list='ObjectName:classPath:-1::customer'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];
				if (!empty($classpath)) {
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname)) {
						$object = new $classname($this->db);
						$result = $object->fetch($value);
						$value = '';
						if ($result > 0) {
							if (property_exists($object, 'label')) {
								$value = $object->label;
							} elseif (property_exists($object, 'libelle')) {
								$value = $object->libelle;
							} elseif (property_exists($object, 'nom')) {
								$value = $object->nom;
							}
						}
					}
				} else {
					dol_syslog(__METHOD__ . ' Error bad setup of field', LOG_WARNING);
					return 'Error bad setup of field';
				}
			} else {
				$value = '';
			}
		} elseif ($type == 'password') {
			$value = preg_replace('/./i', '*', $value);
		} else {    // text|html|varchar
			$value = dol_htmlentitiesbr($value);
		}

		$out = $value;

		return $out;
	}
}
