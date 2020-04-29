<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * 	\file 		htdocs/core/class/extralanguages.class.php
 *	\ingroup    core
 *	\brief      File of class to manage extra fields
 */


/**
 *	Class to manage standard extra languages
 */
class ExtraLanguages
{
	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
     * @var array New array to store extralanguages definition
     */
	public $attributes;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Array of Error code (or message)
	 */
	public $errors = array();

    /**
	 * @var string DB Error number
	 */
	public $errno;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	*/
	public function __construct($db)
	{
		$this->db = $db;
		$this->error = '';
		$this->errors = array();
		$this->attributes = array();
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Load array this->attributes with list of fields per object that need an alternate translation.
	 *  You can set variable MAIN_USE_ALTERNATE_TRANSLATION_FOR=elementA:fieldname,fieldname2;elementB:...
	 *  Example: MAIN_USE_ALTERNATE_TRANSLATION_FOR=societe:name,town;contact:firstname,lastname
	 *
	 * 	@param	string		$elementtype		Type of element ('' = all, 'adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...).
	 * 	@param	boolean		$forceload			Force load of extra fields whatever is status of cache.
	 * 	@return	array							Array of attributes keys+label for all extra fields.
	 */
	public function fetch_name_extralanguages($elementtype, $forceload = false)
	{
        // phpcs:enable
		global $conf;

		if (empty($elementtype)) return array();

		if ($elementtype == 'thirdparty')     $elementtype = 'societe';
		if ($elementtype == 'contact')        $elementtype = 'socpeople';
		if ($elementtype == 'order_supplier') $elementtype = 'commande_fournisseur';


		$array_name_label = array();
		if (!empty($conf->global->MAIN_USE_ALTERNATE_TRANSLATION_FOR)) {
			$tmpelement = explode(';', $conf->global->MAIN_USE_ALTERNATE_TRANSLATION_FOR);
			foreach ($tmpelement as $elementstring) {
				$reg = array();
				preg_match('/^(.*):(.*)$/', $elementstring, $reg);
				$element = $reg[1];
				$array_name_label[$element] = array();
				$tmpfields = explode(',', $reg[2]);
				foreach ($tmpfields as $field) {
					//var_dump($fields);
					//$tmpkeyvar = explode(':', $fields);
					//$array_name_label[$element][$tmpkeyvar[0]] = $tmpkeyvar[1];
					$array_name_label[$element][$field] = $field;
				}
			}
		}
		//var_dump($array_name_label);
		$this->attributes = $array_name_label;

		return $array_name_label;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of common object
	 *
	 * @param  string  $key            			Key of attribute
	 * @param  string  $value          			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      			To add more parametes on html input tag
	 * @param  string  $keysuffix      			Prefix string to add after name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      			Suffix string to add before name and id of field (can be used to avoid duplicate names)
	 * @param  string  $morecss        			More css (to defined size of field. Old behaviour: may also be a numeric)
	 * @param  int     $objectid       			Current object id
	 * @param  string  $extrafieldsobjectkey	If defined (for example $object->table_element), use the new method to get extrafields data
	 * @param  string  $mode                    1=Used for search filters
	 * @return string
	 */
	public function showInputField($key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '', $objectid = 0, $extrafieldsobjectkey = '', $mode = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		$out = '';

		if (!preg_match('/options_$/', $keyprefix))	// Because we work on extrafields, we add 'options_' to prefix if not already added
		{
			$keyprefix = $keyprefix.'options_';
		}

		if (!empty($extrafieldsobjectkey))
		{
			$label = $this->attributes[$extrafieldsobjectkey]['label'][$key];
			$type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
			$size = $this->attributes[$extrafieldsobjectkey]['size'][$key];
			$default = $this->attributes[$extrafieldsobjectkey]['default'][$key];
			$computed = $this->attributes[$extrafieldsobjectkey]['computed'][$key];
			$unique = $this->attributes[$extrafieldsobjectkey]['unique'][$key];
			$required = $this->attributes[$extrafieldsobjectkey]['required'][$key];
			$param = $this->attributes[$extrafieldsobjectkey]['param'][$key];
			$perms = dol_eval($this->attributes[$extrafieldsobjectkey]['perms'][$key], 1);
			$langfile = $this->attributes[$extrafieldsobjectkey]['langfile'][$key];
			$list = dol_eval($this->attributes[$extrafieldsobjectkey]['list'][$key], 1);
			$totalizable = $this->attributes[$extrafieldsobjectkey]['totalizable'][$key];
			$help = $this->attributes[$extrafieldsobjectkey]['help'][$key];
			$hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}
		else	// Old usage
		{
			$label = $this->attribute_label[$key];
			$type = $this->attribute_type[$key];
			$size = $this->attribute_size[$key];
			$elementtype = $this->attribute_elementtype[$key]; // Seems not used
			$default = $this->attribute_default[$key];
			$computed = $this->attribute_computed[$key];
			$unique = $this->attribute_unique[$key];
			$required = $this->attribute_required[$key];
			$param = $this->attribute_param[$key];
			$langfile = $this->attribute_langfile[$key];
			$list = $this->attribute_list[$key];
			$totalizable = $this->attribute_totalizable[$key];
			$hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}

		if ($computed)
		{
			if (!preg_match('/^search_/', $keyprefix)) return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			else return '';
		}

		if (empty($morecss))
		{
			if ($type == 'date')
			{
				$morecss = 'minwidth100imp';
			}
			elseif ($type == 'datetime' || $type == 'link')
			{
				$morecss = 'minwidth200imp';
			}
			elseif (in_array($type, array('int', 'integer', 'double', 'price')))
			{
				$morecss = 'maxwidth75';
			}
			elseif ($type == 'password')
			{
				$morecss = 'maxwidth100';
			}
			elseif ($type == 'url')
			{
				$morecss = 'minwidth400';
			}
			elseif ($type == 'boolean')
			{
				$morecss = '';
			}
			else
			{
				if (round($size) < 12)
				{
					$morecss = 'minwidth100';
				}
				elseif (round($size) <= 48)
				{
					$morecss = 'minwidth200';
				}
				else
				{
					$morecss = 'minwidth400';
				}
			}
		}

		if (in_array($type, array('date', 'datetime')))
		{
			$tmp = explode(',', $size);
			$newsize = $tmp[0];

			$showtime = in_array($type, array('datetime')) ? 1 : 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') $value = '-1';

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
		}
		elseif (in_array($type, array('int', 'integer')))
		{
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$newsize.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
		}
		elseif (preg_match('/varchar/', $type))
		{
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$size.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
		}
		elseif (in_array($type, array('mail', 'phone', 'url')))
		{
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
		}
		elseif ($type == 'text')
		{
			if (!preg_match('/search_/', $keyprefix))		// If keyprefix is search_ or search_options_, we must just use a simple text field
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			}
			else
			{
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		}
		elseif ($type == 'html')
		{
			if (!preg_match('/search_/', $keyprefix))		// If keyprefix is search_ or search_options_, we must just use a simple text field
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			}
			else
			{
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		}
		elseif ($type == 'boolean')
		{
			if (empty($mode))
			{
				$checked = '';
				if (!empty($value)) {
					$checked = ' checked value="1" ';
				} else {
					$checked = ' value="1" ';
				}
				$out = '<input type="checkbox" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
			}
			else
			{
				$out .= $form->selectyesno($keyprefix.$key.$keysuffix, $value, 1, false, 1);
			}
		}
		elseif ($type == 'price')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> '.$langs->getCurrencySymbol($conf->currency);
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
		}
		elseif ($type == 'select')
		{
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			$out .= '<option value="0">&nbsp;</option>';
			foreach ($param['options'] as $key => $val)
			{
				if ((string) $key == '') continue;
				list($val, $parent) = explode('|', $val);
				$out .= '<option value="'.$key.'"';
				$out .= (((string) $value == (string) $key) ? ' selected' : '');
				$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
				$out .= '>';
				if ($langfile && $val) $out .= $langs->trans($val);
				else $out .= $val;
				$out .= '</option>';
			}
			$out .= '</select>';
		}
		elseif ($type == 'sellist')
		{
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if (is_array($param['options']))
			{
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
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');


				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4]))
				{
					if (strpos($InfoFieldList[4], 'extra.') !== false)
					{
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3]))
				{
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}

                $filter_categorie = false;
                if (count($InfoFieldList) > 5) {
                    if ($InfoFieldList[0] == 'categorie') {
                        $filter_categorie = true;
                    }
                }

                if ($filter_categorie === false) {
                    $fields_label = explode('|', $InfoFieldList[1]);
                    if (is_array($fields_label)) {
                        $keyList .= ', ';
                        $keyList .= implode(', ', $fields_label);
                    }

                    $sqlwhere = '';
                    $sql = 'SELECT '.$keyList;
                    $sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
                    if (!empty($InfoFieldList[4])) {
                        // can use curent entity filter
                        if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
                            $InfoFieldList[4] = str_replace('$ENTITY$', $conf->entity, $InfoFieldList[4]);
                        }
                        // can use SELECT request
                        if (strpos($InfoFieldList[4], '$SEL$') !== false) {
                            $InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
                        }

                        // current object id can be use into filter
                        if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
                            $InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
                        } else {
                            $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                        }
                        //We have to join on extrafield table
                        if (strpos($InfoFieldList[4], 'extra') !== false) {
                            $sql .= ' as main, '.MAIN_DB_PREFIX.$InfoFieldList[0].'_extrafields as extra';
                            $sqlwhere .= ' WHERE extra.fk_object=main.'.$InfoFieldList[2].' AND '.$InfoFieldList[4];
                        } else {
                            $sqlwhere .= ' WHERE '.$InfoFieldList[4];
                        }
                    } else {
                        $sqlwhere .= ' WHERE 1=1';
                    }
                    // Some tables may have field, some other not. For the moment we disable it.
                    if (in_array($InfoFieldList[0], array('tablewithentity'))) {
                        $sqlwhere .= ' AND entity = '.$conf->entity;
                    }
                    $sql .= $sqlwhere;
                    //print $sql;

                    $sql .= ' ORDER BY '.implode(', ', $fields_label);

                    dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
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
                            if (is_array($fields_label) && count($fields_label) > 1) {
                                $notrans = true;
                                foreach ($fields_label as $field_toshow) {
                                    $labeltoshow .= $obj->$field_toshow.' ';
                                }
                            } else {
                                $labeltoshow = $obj->{$InfoFieldList[1]};
                            }
                            $labeltoshow = dol_trunc($labeltoshow, 45);

                            if ($value == $obj->rowid) {
                            	if (!$notrans) {
	                                foreach ($fields_label as $field_toshow) {
	                                    $translabel = $langs->trans($obj->$field_toshow);
	                                    $labeltoshow = dol_trunc($translabel, 18).' ';
	                                }
                            	}
                                $out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
                            } else {
                                if (!$notrans) {
                                    $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                                    $labeltoshow = dol_trunc($translabel, 18);
                                }
                                if (empty($labeltoshow)) $labeltoshow = '(not defined)';

                                if (!empty($InfoFieldList[3]) && $parentField) {
                                    $parent = $parentName.':'.$obj->{$parentField};
                                }

                                $out .= '<option value="'.$obj->rowid.'"';
                                $out .= ($value == $obj->rowid ? ' selected' : '');
                                $out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
                                $out .= '>'.$labeltoshow.'</option>';
                            }

                            $i++;
                        }
                        $this->db->free($resql);
                    } else {
                        print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
                    }
                } else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    $data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
                    $out .= '<option value="0">&nbsp;</option>';
                    foreach ($data as $data_key => $data_value) {
                        $out .= '<option value="'.$data_key.'"';
                        $out .= ($value == $data_key ? ' selected' : '');
                        $out .= '>'.$data_value.'</option>';
                    }
                }
			}
			$out .= '</select>';
		}
		elseif ($type == 'checkbox')
		{
			$value_arr = explode(',', $value);
			$out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ?null:$param['options']), $value_arr, '', 0, '', 0, '100%');
		}
		elseif ($type == 'radio')
		{
			$out = '';
			foreach ($param['options'] as $keyopt => $val)
			{
				$out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
				$out .= ' value="'.$keyopt.'"';
				$out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$val.'</label><br>';
			}
		}
		elseif ($type == 'chkbxlst')
		{
			if (is_array($value)) {
				$value_arr = $value;
			}
			else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
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
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list ($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

                $filter_categorie = false;
                if (count($InfoFieldList) > 5) {
                    if ($InfoFieldList[0] == 'categorie') {
                        $filter_categorie = true;
                    }
                }

                if ($filter_categorie === false) {
                    $fields_label = explode('|', $InfoFieldList[1]);
                    if (is_array($fields_label)) {
                        $keyList .= ', ';
                        $keyList .= implode(', ', $fields_label);
                    }

                    $sqlwhere = '';
                    $sql = 'SELECT '.$keyList;
                    $sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
                    if (!empty($InfoFieldList[4])) {
                        // can use SELECT request
                        if (strpos($InfoFieldList[4], '$SEL$') !== false) {
                            $InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
                        }

                        // current object id can be use into filter
                        if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
                            $InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
                        } elseif (preg_match("#^.*list.php$#", $_SERVER["PHP_SELF"])) {
                            // Pattern for word=$ID$
                            $word = '\b[a-zA-Z0-9-\.-_]+\b=\$ID\$';

                            // Removing space arount =, ( and )
                            $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

                            $nbPreg = 1;
                            // While we have parenthesis
                            while ($nbPreg != 0) {
                                // Init des compteurs
                                $nbPregRepl = $nbPregSel = 0;
                                // On retire toutes les parenthèses sans = avant
                                $InfoFieldList[4] = preg_replace('#([^=])(\([^)^(]*('.$word.')[^)^(]*\))#', '$1 $3 ', $InfoFieldList[4], -1, $nbPregRepl);
                                // On retire les espaces autour des = et parenthèses
                                $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);
                                // On retire toutes les parenthèses avec = avant
                                $InfoFieldList[4] = preg_replace('#\b[a-zA-Z0-9-\.-_]+\b=\([^)^(]*('.$word.')[^)^(]*\)#', '$1 ', $InfoFieldList[4], -1, $nbPregSel);
                                // On retire les espaces autour des = et parenthèses
                                $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

                                // Calcul du compteur général pour la boucle
                                $nbPreg = $nbPregRepl + $nbPregSel;
                            }

                            // Si l'on a un AND ou un OR, avant ou après
                            preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
                            while (!empty($matchCondition[0])) {
                                // If the two sides differ but are not empty
                                if (!empty($matchCondition[1]) && !empty($matchCondition[3]) && $matchCondition[1] != $matchCondition[3]) {
                                    // Nobody sain would do that without parentheses
                                    $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                                } else {
                                    if (!empty($matchCondition[1])) {
                                        $boolCond = (($matchCondition[1] == "AND") ? ' AND TRUE ' : ' OR FALSE ');
                                        $InfoFieldList[4] = str_replace($matchCondition[0], $boolCond.$matchCondition[3], $InfoFieldList[4]);
                                    } elseif (!empty($matchCondition[3])) {
                                        $boolCond = (($matchCondition[3] == "AND") ? ' TRUE AND ' : ' FALSE OR');
                                        $InfoFieldList[4] = str_replace($matchCondition[0], $boolCond, $InfoFieldList[4]);
                                    } else {
                                        $InfoFieldList[4] = " TRUE ";
                                    }
                                }

                                // Si l'on a un AND ou un OR, avant ou après
                                preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
                            }
                        } else {
                            $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                        }

                        // We have to join on extrafield table
                        if (strpos($InfoFieldList[4], 'extra.') !== false) {
                            $sql .= ' as main, '.MAIN_DB_PREFIX.$InfoFieldList[0].'_extrafields as extra';
                            $sqlwhere .= ' WHERE extra.fk_object=main.'.$InfoFieldList[2].' AND '.$InfoFieldList[4];
                        } else {
                            $sqlwhere .= ' WHERE '.$InfoFieldList[4];
                        }
                    } else {
                        $sqlwhere .= ' WHERE 1=1';
                    }
                    // Some tables may have field, some other not. For the moment we disable it.
                    if (in_array($InfoFieldList[0], array('tablewithentity'))) {
                        $sqlwhere .= ' AND entity = '.$conf->entity;
                    }
                    // $sql.=preg_replace('/^ AND /','',$sqlwhere);
                    // print $sql;

                    $sql .= $sqlwhere;
                    dol_syslog(get_class($this).'::showInputField type=chkbxlst', LOG_DEBUG);
                    $resql = $this->db->query($sql);
                    if ($resql) {
                        $num = $this->db->num_rows($resql);
                        $i = 0;

                        $data = array();

                        while ($i < $num) {
                            $labeltoshow = '';
                            $obj = $this->db->fetch_object($resql);

                            $notrans = false;
                            // Several field into label (eq table:code|libelle:rowid)
                            $fields_label = explode('|', $InfoFieldList[1]);
                            if (is_array($fields_label)) {
                                $notrans = true;
                                foreach ($fields_label as $field_toshow) {
                                    $labeltoshow .= $obj->$field_toshow.' ';
                                }
                            } else {
                                $labeltoshow = $obj->{$InfoFieldList[1]};
                            }
                            $labeltoshow = dol_trunc($labeltoshow, 45);

                            if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                                foreach ($fields_label as $field_toshow) {
                                    $translabel = $langs->trans($obj->$field_toshow);
                                    if ($translabel != $obj->$field_toshow) {
                                        $labeltoshow = dol_trunc($translabel, 18).' ';
                                    } else {
                                        $labeltoshow = dol_trunc($obj->$field_toshow, 18).' ';
                                    }
                                }

                                $data[$obj->rowid] = $labeltoshow;
                            } else {
                                if (!$notrans) {
                                    $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                                    if ($translabel != $obj->{$InfoFieldList[1]}) {
                                        $labeltoshow = dol_trunc($translabel, 18);
                                    } else {
                                        $labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
                                    }
                                }
                                if (empty($labeltoshow))
                                    $labeltoshow = '(not defined)';

                                if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                                    $data[$obj->rowid] = $labeltoshow;
                                }

                                if (!empty($InfoFieldList[3]) && $parentField) {
                                    $parent = $parentName.':'.$obj->{$parentField};
                                }

                                $data[$obj->rowid] = $labeltoshow;
                            }

                            $i++;
                        }
                        $this->db->free($resql);

                        $out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
                    } else {
                        print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
                    }
                } else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    $data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
                    $out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
                }
			}
		}
		elseif ($type == 'link')
		{
			$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'
			$showempty = (($required && $default != '') ? 0 : 1);
			$out = $form->selectForForms($param_list[0], $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss);
		}
		elseif ($type == 'password')
		{
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out = '<input style="display:none" type="text" name="fakeusernameremembered">'; // Hidden field to reduce impact of evil Google Chrome autopopulate bug.
			$out .= '<input autocomplete="new-password" type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */
		/*if (! empty($help) && $keyprefix != 'search_options_') {
			$out .= $form->textwithpicto('', $help, 1, 'help', '', 0, 3);
		}*/
		return $out;
	}


	/**
	 * Return HTML string to put an output field into a page
	 *
	 * @param   string	$key            		Key of attribute
	 * @param   string	$value          		Value to show
	 * @param	string	$moreparam				To add more parameters on html input tag (only checkbox use html input for output rendering)
	 * @param	string	$extrafieldsobjectkey	If defined (for example $object->table_element), function uses the new method to get extrafields data
	 * @return	string							Formated value
	 */
	public function showOutputField($key, $value, $moreparam = '', $extrafieldsobjectkey = '')
	{
		global $conf, $langs;

		if (!empty($extrafieldsobjectkey))
		{
			$label = $this->attributes[$extrafieldsobjectkey]['label'][$key];
			$type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
			$size = $this->attributes[$extrafieldsobjectkey]['size'][$key];
			$default = $this->attributes[$extrafieldsobjectkey]['default'][$key];
			$computed = $this->attributes[$extrafieldsobjectkey]['computed'][$key];
			$unique = $this->attributes[$extrafieldsobjectkey]['unique'][$key];
			$required = $this->attributes[$extrafieldsobjectkey]['required'][$key];
			$param = $this->attributes[$extrafieldsobjectkey]['param'][$key];
			$perms = dol_eval($this->attributes[$extrafieldsobjectkey]['perms'][$key], 1);
			$langfile = $this->attributes[$extrafieldsobjectkey]['langfile'][$key];
			$list = dol_eval($this->attributes[$extrafieldsobjectkey]['list'][$key], 1);
			$help = $this->attributes[$extrafieldsobjectkey]['help'][$key];
			$hidden = (empty($list) ? 1 : 0); // If $list empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}
		else	// Old usage
		{
			//dol_syslog("Warning: parameter 'extrafieldsobjectkey' is missing", LOG_WARNING);
			$label = $this->attribute_label[$key];
			$type = $this->attribute_type[$key];
			$size = $this->attribute_size[$key];
			$default = $this->attribute_default[$key];
			$computed = $this->attribute_computed[$key];
			$unique = $this->attribute_unique[$key];
			$required = $this->attribute_required[$key];
			$param = $this->attribute_param[$key];
			$perms = dol_eval($this->attribute_perms[$key], 1);
			$langfile = $this->attribute_langfile[$key];
			$list = dol_eval($this->attribute_list[$key], 1);
			$help = ''; // Not supported with old syntax
			$hidden = (empty($list) ? 1 : 0); // If $list empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}

		if ($hidden) return ''; // This is a protection. If field is hidden, we should just not call this method.

		//if ($computed) $value =		// $value is already calculated into $value before calling this method

		$showsize = 0;
		if ($type == 'date')
		{
			$showsize = 10;
			$value = dol_print_date($value, 'day');
		}
		elseif ($type == 'datetime')
		{
			$showsize = 19;
			$value = dol_print_date($value, 'dayhour');
		}
		elseif ($type == 'int')
		{
			$showsize = 10;
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {
				//$value=price($value);
				$sizeparts = explode(",", $size);
				$number_decimals = $sizeparts[1];
				$value = price($value, 0, $langs, 0, 0, $number_decimals, '');
			}
		}
		elseif ($type == 'boolean')
		{
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked ';
			}
			$value = '<input type="checkbox" '.$checked.' '.($moreparam ? $moreparam : '').' readonly disabled>';
		}
		elseif ($type == 'mail')
		{
			$value = dol_print_email($value, 0, 0, 0, 64, 1, 1);
		}
		elseif ($type == 'url')
		{
			$value = dol_print_url($value, '_blank', 32, 1);
		}
		elseif ($type == 'phone')
		{
			$value = dol_print_phone($value, '', 0, 0, '', '&nbsp;', 'phone');
		}
		elseif ($type == 'price')
		{
			$value = price($value, 0, $langs, 0, 0, -1, $conf->currency);
		}
		elseif ($type == 'select')
		{
			if ($langfile && $param['options'][$value]) $value = $langs->trans($param['options'][$value]);
			else $value = $param['options'][$value];
		}
		elseif ($type == 'sellist')
		{
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3)
			{
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT '.$keyList;
			$sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false)
			{
				$sql .= ' as main';
			}
			if ($selectkey == 'rowid' && empty($value)) {
				$sql .= " WHERE ".$selectkey."=0";
			} elseif ($selectkey == 'rowid') {
				$sql .= " WHERE ".$selectkey."=".$this->db->escape($value);
			} else {
				$sql .= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$value = ''; // value was used, so now we reste it to use it to build final output

				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|', $InfoFieldList[1]);

				if (is_array($fields_label) && count($fields_label) > 1)
				{
					foreach ($fields_label as $field_toshow)
					{
						$translabel = '';
						if (!empty($obj->$field_toshow)) {
							$translabel = $langs->trans($obj->$field_toshow);
						}
						if ($translabel != $field_toshow) {
							$value .= dol_trunc($translabel, 18).' ';
						} else {
							$value .= $obj->$field_toshow.' ';
						}
					}
				}
				else
				{
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
			else dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
		}
		elseif ($type == 'radio')
		{
			$value = $param['options'][$value];
		}
		elseif ($type == 'checkbox')
		{
			$value_arr = explode(',', $value);
			$value = '';
			$toprint = array();
			if (is_array($value_arr))
			{
				foreach ($value_arr as $keyval=>$valueval) {
					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$param['options'][$valueval].'</li>';
				}
			}
			$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		}
		elseif ($type == 'chkbxlst')
		{
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT '.$keyList;
			$sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output
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
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
								} else {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->$field_toshow.'</li>';
								}
							}
						} else {
							$translabel = '';
							if (!empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
							} else {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->{$InfoFieldList[1]}.'</li>';
							}
						}
					}
				}
				$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
			} else {
				dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
			}
		}
		elseif ($type == 'link')
		{
			$out = '';

			// Only if something to display (perf)
			if ($value)		// If we have -1 here, pb is into insert, not into ouptut (fix insert instead of changing code here to compensate)
			{
				$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];
				if (!empty($classpath))
				{
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname))
					{
						$object = new $classname($this->db);
						$object->fetch($value);
						$value = $object->getNomUrl(3);
					}
				}
				else
				{
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
		}
		elseif ($type == 'text')
		{
			$value = dol_htmlentitiesbr($value);
		}
		elseif ($type == 'html')
		{
			$value = dol_htmlentitiesbr($value);
		}
		elseif ($type == 'password')
		{
			$value = dol_trunc(preg_replace('/./i', '*', $value), 8, 'right', 'UTF-8', 1);
		}
		else
		{
			$showsize = round($size);
			if ($showsize > 48) $showsize = 48;
		}

		//print $type.'-'.$size;
		$out = $value;

		return $out;
	}

	/**
	 * Return tag to describe alignement to use for this extrafield
	 *
	 * @param   string	$key            		Key of attribute
	 * @param	string	$extrafieldsobjectkey	If defined, use the new method to get extrafields data
	 * @return	string							Formated value
	 */
	public function getAlignFlag($key, $extrafieldsobjectkey = '')
	{
		global $conf, $langs;

		if (!empty($extrafieldsobjectkey)) $type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
		else $type = $this->attribute_type[$key];

		$align = '';

        if ($type == 'date')
		{
			$align = "center";
		}
		elseif ($type == 'datetime')
		{
			$align = "center";
		}
		elseif ($type == 'int')
		{
			$align = "right";
		}
		elseif ($type == 'price')
		{
			$align = "right";
		}
		elseif ($type == 'double')
		{
			$align = "right";
		}
		elseif ($type == 'boolean')
		{
			$align = "center";
		}
		elseif ($type == 'radio')
		{
			$align = "center";
		}
		elseif ($type == 'checkbox')
		{
			$align = "center";
		}
		elseif ($type == 'price')
		{
			$align = "right";
		}

		return $align;
	}

	/**
	 * Return HTML string to print separator extrafield
	 *
	 * @param   string	$key            Key of attribute
	 * @param	string	$object			Object
	 * @param	int		$colspan		Value of colspan to use (it must includes the first column with title)
	 * @return 	string					HTML code with line for separator
	 */
	public function showSeparator($key, $object, $colspan = 2)
	{
		global $langs;

		$out = '<tr id="trextrafieldseparator'.$key.'" class="trextrafieldseparator trextrafieldseparator'.$key.'"><td colspan="'.$colspan.'"><strong>';
		$out .= $langs->trans($this->attributes[$object->table_element]['label'][$key]);
		$out .= '</strong></td></tr>';

        $extrafield_param = $this->attributes[$object->table_element]['param'][$key];
        if (!empty($extrafield_param) && is_array($extrafield_param)) {
            $extrafield_param_list = array_keys($extrafield_param['options']);

            if (count($extrafield_param_list) > 0) {
                $extrafield_collapse_display_value = intval($extrafield_param_list[0]);
                if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
                	// Set the collapse_display status to cookie in priority or if ignorecollapsesetup is 1, if cookie and ignorecollapsesetup not defined, use the setup.
                	$collapse_display = ((isset($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key]) || GETPOST('ignorecollapsesetup', 'int')) ? ($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key] ? true : false) : ($extrafield_collapse_display_value == 2 ? false : true));
                    $extrafields_collapse_num = $this->attributes[$object->table_element]['pos'][$key];

                    $out .= '<!-- Add js script to manage the collpase/uncollapse of extrafields separators '.$key.' -->';
                    $out .= '<script type="text/javascript">';
                    $out .= 'jQuery(document).ready(function(){';
                    if ($collapse_display === false) {
                        $out .= '   jQuery("#trextrafieldseparator'.$key.' td").prepend("<span class=\"cursorpointer fa fa-plus-square\"></span>&nbsp;");'."\n";
                        $out .= '   jQuery(".trextrafields_collapse'.$extrafields_collapse_num.'").hide();'."\n";
                    } else {
                    	$out .= '   jQuery("#trextrafieldseparator'.$key.' td").prepend("<span class=\"cursorpointer fa fa-minus-square\"></span>&nbsp;");'."\n";
                    	$out .= '   document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
                    }
                    $out .= '   jQuery("#trextrafieldseparator'.$key.'").click(function(){'."\n";
                    $out .= '       jQuery(".trextrafields_collapse'.$extrafields_collapse_num.'").toggle(300, function(){'."\n";
                    $out .= '           if (jQuery(".trextrafields_collapse'.$extrafields_collapse_num.'").is(":hidden")) {'."\n";
                    $out .= '               jQuery("#trextrafieldseparator'.$key.' td span").addClass("fa-plus-square").removeClass("fa-minus-square");'."\n";
                    $out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=0; path='.$_SERVER["PHP_SELF"].'"'."\n";
                    $out .= '           } else {'."\n";
                    $out .= '               jQuery("#trextrafieldseparator'.$key.' td span").addClass("fa-minus-square").removeClass("fa-plus-square");'."\n";
                    $out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
                    $out .= '           }'."\n";
                    $out .= '       });';
                    $out .= '   });';
                    $out .= '});';
                    $out .= '</script>';
                }
            }
        }

		return $out;
	}

	/**
	 * Fill array_options property of object by extrafields value (using for data sent by forms)
	 *
	 * @param   array	$extralabels    Deprecated (old $array of extrafields, now set this to null)
	 * @param   object	$object         Object
	 * @param	string	$onlykey		Only the following key is filled. When we make update of only one extrafield ($action = 'update_extras'), calling page must set this to avoid to have other extrafields being reset.
	 * @return	int						1 if array_options set, 0 if no value, -1 if error (field required missing for example)
	 */
	public function setOptionalsFromPost($extralabels, &$object, $onlykey = '')
	{
		global $_POST, $langs;

		$nofillrequired = 0; // For error when required field left blank
		$error_field_required = array();

		if (is_array($this->attributes[$object->table_element]['label'])) $extralabels = $this->attributes[$object->table_element]['label'];

		if (is_array($extralabels))
		{
			// Get extra fields
			foreach ($extralabels as $key => $value)
			{
				if (!empty($onlykey) && $key != $onlykey) continue;

				$key_type = $this->attributes[$object->table_element]['type'][$key];
				if ($key_type == 'separate') continue;

				$enabled = 1;
				if (isset($this->attributes[$object->table_element]['list'][$key]))
				{
					$enabled = dol_eval($this->attributes[$object->table_element]['list'][$key], 1);
				}
				$perms = 1;
				if (isset($this->attributes[$object->table_element]['perms'][$key]))
				{
					$perms = dol_eval($this->attributes[$object->table_element]['perms'][$key], 1);
				}
				if (empty($enabled)) continue;
				if (empty($perms)) continue;

				if ($this->attributes[$object->table_element]['required'][$key])	// Value is required
				{
					// Check if empty without using GETPOST, value can be alpha, int, array, etc...
				    if ((!is_array($_POST["options_".$key]) && empty($_POST["options_".$key]) && $this->attributes[$object->table_element]['type'][$key] != 'select' && $_POST["options_".$key] != '0')
				        || (!is_array($_POST["options_".$key]) && empty($_POST["options_".$key]) && $this->attributes[$object->table_element]['type'][$key] == 'select')
						|| (is_array($_POST["options_".$key]) && empty($_POST["options_".$key])))
					{
						//print 'ccc'.$value.'-'.$this->attributes[$object->table_element]['required'][$key];
						$nofillrequired++;
						$error_field_required[] = $langs->transnoentitiesnoconv($value);
					}
				}

				if (in_array($key_type, array('date')))
				{
					// Clean parameters
					// TODO GMT date in memory must be GMT so we should add gm=true in parameters
					$value_key = dol_mktime(0, 0, 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				elseif (in_array($key_type, array('datetime')))
				{
					// Clean parameters
					// TODO GMT date in memory must be GMT so we should add gm=true in parameters
					$value_key = dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				elseif (in_array($key_type, array('checkbox', 'chkbxlst')))
				{
					$value_arr = GETPOST("options_".$key, 'array'); // check if an array
					if (!empty($value_arr)) {
						$value_key = implode($value_arr, ',');
					} else {
						$value_key = '';
					}
				}
				elseif (in_array($key_type, array('price', 'double')))
				{
					$value_arr = GETPOST("options_".$key, 'alpha');
					$value_key = price2num($value_arr);
				}
				else
				{
					$value_key = GETPOST("options_".$key);
					if (in_array($key_type, array('link')) && $value_key == '-1') $value_key = '';
				}

				$object->array_options["options_".$key] = $value_key;
			}

			if ($nofillrequired) {
				$langs->load('errors');
				setEventMessages($langs->trans('ErrorFieldsRequired').' : '.implode(', ', $error_field_required), null, 'errors');
				return -1;
			}
			else {
				return 1;
			}
		}
		else {
			return 0;
		}
	}

	/**
	 * return array_options array of data of extrafields value of object sent by a search form
	 *
	 * @param  array|string		$extrafieldsobjectkey  	array of extrafields (old usage) or value of object->table_element (new usage)
	 * @param  string			$keyprefix      		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string			$keysuffix      		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return array|int								array_options set or 0 if no value
	 */
	public function getOptionalsFromPost($extrafieldsobjectkey, $keyprefix = '', $keysuffix = '')
	{
		global $_POST;

		if (is_string($extrafieldsobjectkey) && is_array($this->attributes[$extrafieldsobjectkey]['label']))
		{
			$extralabels = $this->attributes[$extrafieldsobjectkey]['label'];
		}
		else
		{
			$extralabels = $extrafieldsobjectkey;
		}

		if (is_array($extralabels))
		{
			$array_options = array();

			// Get extra fields
			foreach ($extralabels as $key => $value)
			{
				$key_type = '';
				if (is_string($extrafieldsobjectkey))
				{
					$key_type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
				}

				if (in_array($key_type, array('date', 'datetime')))
				{
					if (!GETPOSTISSET($keysuffix."options_".$key.$keyprefix."year")) continue; // Value was not provided, we should not set it.
					// Clean parameters
					$value_key = dol_mktime(GETPOST($keysuffix."options_".$key.$keyprefix."hour", 'int'), GETPOST($keysuffix."options_".$key.$keyprefix."min", 'int'), 0, GETPOST($keysuffix."options_".$key.$keyprefix."month", 'int'), GETPOST($keysuffix."options_".$key.$keyprefix."day", 'int'), GETPOST($keysuffix."options_".$key.$keyprefix."year", 'int'));
				}
				elseif (in_array($key_type, array('checkbox', 'chkbxlst')))
				{
					if (!GETPOSTISSET($keysuffix."options_".$key.$keyprefix)) continue; // Value was not provided, we should not set it.
					$value_arr = GETPOST($keysuffix."options_".$key.$keyprefix);
					// Make sure we get an array even if there's only one checkbox
					$value_arr = (array) $value_arr;
					$value_key = implode(',', $value_arr);
				}
				elseif (in_array($key_type, array('price', 'double', 'int')))
				{
					if (!GETPOSTISSET($keysuffix."options_".$key.$keyprefix)) continue; // Value was not provided, we should not set it.
					$value_arr = GETPOST($keysuffix."options_".$key.$keyprefix);
					$value_key = price2num($value_arr);
				}
				else
				{
					if (!GETPOSTISSET($keysuffix."options_".$key.$keyprefix)) continue; // Value was not provided, we should not set it.
					$value_key = GETPOST($keysuffix."options_".$key.$keyprefix);
				}

				$array_options[$keysuffix."options_".$key] = $value_key; // No keyprefix here. keyprefix is used only for read.
			}

			return $array_options;
		}

		return 0;
	}
}
