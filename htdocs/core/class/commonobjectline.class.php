<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/core/class/commonobjectline.class.php
 *  \ingroup    core
 *  \brief      File of the superclass of classes of lines of business objects (invoice, contract, proposal, orders, etc. ...)
 */


/**
 *  Parent class for class inheritance lines of business objects
 *  This class is useless for the moment so no inherit are done on it
 */
abstract class CommonObjectLine extends CommonObject
{
	/**
	 * Id of the line
	 * @var int
	 */
	public $id;

	/**
	 * Id of the line
	 * @var int
	 * @deprecated Try to use id property as possible (even if field into database is still rowid)
	 * @see $id
	 */
	public $rowid;

	/**
	 * Product/service unit code ('km', 'm', 'p', ...)
	 * @var string
	 */
	public $fk_unit;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Returns the label, shot_label or code found in units dictionary from ->fk_unit.
	 *  A langs->trans() must be called on result to get translated value.
	 *
	 * 	@param	string $type 	Label type ('long', 'short' or 'code'). This can be a translation key.
	 *	@return	string|int 		<0 if KO, label if OK (Example: 'long', 'short' or 'unitCODE')
	 */
	public function getLabelOfUnit($type = 'long')
	{
		global $langs;

		if (!$this->fk_unit) {
			return '';
		}

		$langs->load('products');

		$label_type = 'label';

		$label_type = 'label';
		if ($type == 'short') $label_type = 'short_label';
		elseif ($type == 'code') $label_type = 'code';

		$sql = 'select '.$label_type.', code from '.MAIN_DB_PREFIX.'c_units where rowid='.$this->fk_unit;
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0) {
			$res = $this->db->fetch_array($resql);
			if ($label_type == 'code') $label = 'unit'.$res['code'];
			else $label = $res[$label_type];
			$this->db->free($resql);
			return $label;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			dol_syslog(get_class($this)."::getLabelOfUnit Error ".$this->error, LOG_ERR);
			return -1;
		}
	}
	// Currently we need function at end of file CommonObject for all object lines. Should find a way to avoid duplicate code.

	// For the moment we use the extends on CommonObject until PHP min is 5.4 so use Traits.

	/**
	 * Function to show lines of extrafields with output datas.
	 * This function is responsible to output the <tr> and <td> according to correct number of columns received into $params['colspan']
	 *
	 * @param 	Extrafields $extrafields    Extrafield Object
	 * @param 	string      $mode           Show output ('view') or input ('create' or 'edit') for extrafield
	 * @param 	array       $params         Optional parameters. Example: array('style'=>'class="oddeven"', 'colspan'=>$colspan)
	 * @param 	string      $keysuffix      Suffix string to add after name and id of field (can be used to avoid duplicate names)
	 * @param 	string      $keyprefix      Prefix string to add before name and id of field (can be used to avoid duplicate names)
	 * @param	string		$onetrtd		All fields in same tr td. Used by objectline_create.tpl.php for example.
	 * @return 	string
	 */
	public function showOptionals($extrafields, $mode = 'view', $params = null, $keysuffix = '', $keyprefix = '', $onetrtd = 0)
	{
		global $db, $conf, $langs, $action, $form, $hookmanager;

		if (!is_object($form)) $form = new Form($db);

		$out = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('showOptionals', $parameters, $this, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook))
		{
			if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label']) > 0)
			{
				$out .= "\n";
				$out .= '<!-- showOptionals --> ';
				$out .= "\n";

				$extrafields_collapse_num = '';
				$e = 0;
				foreach ($extrafields->attributes[$this->table_element]['label'] as $key=>$label)
				{
					// Show only the key field in params
					if (is_array($params) && array_key_exists('onlykey', $params) && $key != $params['onlykey']) continue;

					// Test on 'enabled' ('enabled' is different than 'list' = 'visibility')
					$enabled = 1;
					if ($enabled && isset($extrafields->attributes[$this->table_element]['enabled'][$key]))
					{
						$enabled = dol_eval($extrafields->attributes[$this->table_element]['enabled'][$key], 1);
					}
					if (empty($enabled)) continue;

					$visibility = 1;
					if ($visibility && isset($extrafields->attributes[$this->table_element]['list'][$key]))
					{
						$visibility = dol_eval($extrafields->attributes[$this->table_element]['list'][$key], 1);
					}

					$perms = 1;
					if ($perms && isset($extrafields->attributes[$this->table_element]['perms'][$key]))
					{
						$perms = dol_eval($extrafields->attributes[$this->table_element]['perms'][$key], 1);
					}

					if (($mode == 'create') && abs($visibility) != 1 && abs($visibility) != 3) continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
					elseif (($mode == 'edit') && abs($visibility) != 1 && abs($visibility) != 3 && abs($visibility) != 4) continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list and <> 4 = not visible at the creation
					elseif ($mode == 'view' && empty($visibility)) continue;
					if (empty($perms)) continue;
					// Load language if required
					if (!empty($extrafields->attributes[$this->table_element]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$this->table_element]['langfile'][$key]);
					}

					switch ($mode) {
						case "view":
							$value = $this->array_options["options_".$key.$keysuffix]; // Value may be clean or formated later
							break;
						case "create":
						case "edit":
							// We get the value of property found with GETPOST so it takes into account:
							// default values overwrite, restore back to list link, ... (but not 'default value in database' of field)
							$check = 'alphanohtml';
							if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text'))) {
								$check = 'restricthtml';
							}
							$getposttemp = GETPOST($keyprefix.'options_'.$key.$keysuffix, $check, 3); // GETPOST can get value from GET, POST or setup of default values overwrite.
							// GETPOST("options_" . $key) can be 'abc' or array(0=>'abc')
							if (is_array($getposttemp) || $getposttemp != '' || GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix))
							{
								if (is_array($getposttemp)) {
									// $getposttemp is an array but following code expects a comma separated string
									$value = implode(",", $getposttemp);
								} else {
									$value = $getposttemp;
								}
							} else {
								$value = $this->array_options["options_".$key]; // No GET, no POST, no default value, so we take value of object.
							}
							//var_dump($keyprefix.' - '.$key.' - '.$keysuffix.' - '.$keyprefix.'options_'.$key.$keysuffix.' - '.$this->array_options["options_".$key.$keysuffix].' - '.$getposttemp.' - '.$value);
							break;
					}

					if ($extrafields->attributes[$this->table_element]['type'][$key] == 'separate')
					{
						$extrafields_collapse_num = '';
						$extrafield_param = $extrafields->attributes[$this->table_element]['param'][$key];
						if (!empty($extrafield_param) && is_array($extrafield_param)) {
							$extrafield_param_list = array_keys($extrafield_param['options']);

							if (count($extrafield_param_list) > 0) {
								$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

								if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
									$extrafields_collapse_num = $extrafields->attributes[$this->table_element]['pos'][$key];
								}
							}
						}

						$out .= $extrafields->showSeparator($key, $this, 0, 'line');
					} else {
						$class = (!empty($extrafields->attributes[$this->table_element]['hidden'][$key]) ? 'hideobject ' : '');
						$csstyle = '';
						if (is_array($params) && count($params) > 0) {
							if (array_key_exists('class', $params)) {
								$class .= $params['class'].' ';
							}
							if (array_key_exists('style', $params)) {
								$csstyle = $params['style'];
							}
						}

						// add html5 elements
						$domData  = ' data-element="extrafield"';
						$domData .= ' data-targetelement="'.$this->element.'"';
						$domData .= ' data-targetid="'.$this->id.'"';

						$html_id = (empty($this->id) ? '' : 'extrarow-'.$this->element.'_'.$key.'_'.$this->id);

						// Convert date into timestamp format (value in memory must be a timestamp)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('date', 'datetime')))
						{
							$datenotinstring = $this->array_options['options_'.$key];
							if (!is_numeric($this->array_options['options_'.$key]))	// For backward compatibility
							{
								$datenotinstring = $this->db->jdate($datenotinstring);
							}
							$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix)) ? dol_mktime(GETPOST($keyprefix.'options_'.$key.$keysuffix."hour", 'int', 3), GETPOST($keyprefix.'options_'.$key.$keysuffix."min", 'int', 3), 0, GETPOST($keyprefix.'options_'.$key.$keysuffix."month", 'int', 3), GETPOST($keyprefix.'options_'.$key.$keysuffix."day", 'int', 3), GETPOST($keyprefix.'options_'.$key.$keysuffix."year", 'int', 3)) : $datenotinstring;
						}
						// Convert float submited string into real php numeric (value in memory must be a php numeric)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('price', 'double')))
						{
							$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? price2num($value) : $this->array_options['options_'.$key];
						}

						// HTML, text, select, integer and varchar: take into account default value in database if in create mode
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text', 'varchar', 'select', 'int')))
						{
							if ($action == 'create') $value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? $value : $extrafields->attributes[$this->table_element]['default'][$key];
						}

						$labeltoshow = $langs->trans($label);
						$helptoshow = $langs->trans($extrafields->attributes[$this->table_element]['help'][$key]);

						$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="'.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$extrafields_collapse_num.(!empty($this->id)?'_'.$this->id:'').'" '.$domData.' >';
						$out .= '<div style="display: inline-block; padding-right:4px" class="wordbreak';
						//$out .= "titlefield";
						//if (GETPOST('action', 'restricthtml') == 'create') $out.='create';
						// BUG #11554 : For public page, use red dot for required fields, instead of bold label
						$tpl_context = isset($params["tpl_context"]) ? $params["tpl_context"] : "none";
						if ($tpl_context == "public") {	// Public page : red dot instead of fieldrequired characters
							$out .= '">';
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) $out .= $form->textwithpicto($labeltoshow, $helptoshow);
							else $out .= $labeltoshow;
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) $out .= '&nbsp;<font color="red">*</font>';
						} else {
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) $out .= ' fieldrequired';
							$out .= '">';
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) $out .= $form->textwithpicto($labeltoshow, $helptoshow);
							else $out .= $labeltoshow;
						}
						$out .= '</div>';

						$html_id = !empty($this->id) ? $this->element.'_extras_'.$key.'_'.$this->id : '';

						$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').'style="display: inline-block" class="'.$this->element.'_extras_'.$key.'">';

						switch ($mode) {
							case "view":
								$out .= $extrafields->showOutputField($key, $value);
								break;
							case "create":
								$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								break;
							case "edit":
								$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								break;
						}

						$out .= '</div>';

						/*for($ii = 0; $ii < ($colspan - 1); $ii++)
						{
							$out .='<td class="'.$this->element.'_extras_'.$key.'"></td>';
						}*/

						if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) $out .= '</div>';
						else $out .= '</div>';
						$e++;
					}
				}
				$out .= "\n";
				// Add code to manage list depending on others
				if (!empty($conf->use_javascript_ajax)) {
					$out .= '
					<script>
					jQuery(document).ready(function() {
						function showOptions(child_list, parent_list, orig_select)
						{
							var val = $("select[name=\""+parent_list+"\"]").val();
							var parentVal = parent_list + ":" + val;
							if(val > 0) {
								var options = orig_select.find("option[parent=\""+parentVal+"\"]").clone();
								$("select[name=\""+child_list+"\"] option[parent]").remove();
								$("select[name=\""+child_list+"\"]").append(options);
							} else {
								var options = orig_select.find("option[parent]").clone();
								$("select[name=\""+child_list+"\"] option[parent]").remove();
								$("select[name=\""+child_list+"\"]").append(options);
							}
						}
						function setListDependencies() {
							jQuery("select option[parent]").parent().each(function() {
								var orig_select = {};
								var child_list = $(this).attr("name");
								orig_select[child_list] = $(this).clone();
								var parent = $(this).find("option[parent]:first").attr("parent");
								var infos = parent.split(":");
								var parent_list = infos[0];
								$("select[name=\""+parent_list+"\"]").change(function() {
									showOptions(child_list, parent_list, orig_select[child_list]);
								});
							});
						}

						setListDependencies();
					});
					</script>'."\n";
				}

				$out .= '<!-- /showOptionals --> '."\n";
			}
		}

		$out .= $hookmanager->resPrint;

		return $out;
	}
}
