<?php
/* Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file    comm/mailing/class/html.formadvtargetemailing.class.php
 * \ingroup mailing
 * \brief   File for the class with functions for the building of HTML components for advtargetemailing
 */

/**
 * Class to manage building of HTML components
 */
class FormAdvTargetEmailing extends Form
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

	/**
	 * Affiche un champs select contenant une liste
	 *
	 * @param array $selected_array à preselectionner
	 * @param string $htmlname select field
	 * @return string select field
	 */
	public function multiselectProspectionStatus($selected_array = array(), $htmlname = 'cust_prospect_status')
	{
		global $conf, $langs;
		$options_array = array();

		$sql = "SELECT code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY sortorder";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$level = $langs->trans($obj->code);
				if ($level == $obj->code) {
					$level = $langs->trans($obj->label);
				}
				$options_array[$obj->code] = $level;

				$i++;
			}
		} else {
			dol_print_error($this->db);
		}
		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return combo list of activated countries, into language of user
	 *
	 * @param string    $htmlname of html select object
	 * @param array     $selected_array or Code or Label of preselected country
	 * @return string   HTML string with select
	 */
	public function multiselectState($htmlname = 'state_id', $selected_array = array())
	{
		global $conf, $langs;

		$langs->load("dict");
		$maxlength = 0;

		$out = '';
		$stateArray = array();
		$label = array();

		$options_array = array();

		$sql = "SELECT d.rowid as rowid, d.code_departement as code, d.nom as department, r.nom as region";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_departements d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions r on d.fk_region=r.code_region";
		$sql .= " WHERE d.active = 1 AND d.code_departement<>'' AND r.code_region<>''";
		//$sql .= " ORDER BY r.nom ASC, d.nom ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				$foundselected = false;

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$stateArray [$i] ['rowid'] = $obj->rowid;
					$stateArray [$i] ['code'] = $obj->code;
					$stateArray [$i] ['label'] = $obj->region.'/'.$obj->department;
					$label[$i] = $stateArray[$i]['label'];
					$i++;
				}

				$array1_sort_order = SORT_ASC;
				array_multisort($label, $array1_sort_order, $stateArray);

				foreach ($stateArray as $row) {
					$label = dol_trunc($row['label'], $maxlength, 'middle');
					if ($row['code']) {
						$label .= ' ('.$row['code'].')';
					}

					$options_array[$row['rowid']] = $label;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return combo list of activated countries, into language of user
	 *
	 * @param string    $htmlname of html select object
	 * @param array     $selected_array or Code or Label of preselected country
	 * @return string   HTML string with select
	 */
	public function multiselectCountry($htmlname = 'country_id', $selected_array = array())
	{
		global $conf, $langs;

		$langs->load("dict");
		$maxlength = 0;

		$out = '';
		$countryArray = array();
		$label = array();

		$options_array = array();

		$sql = "SELECT rowid, code as code_iso, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_country";
		$sql .= " WHERE active = 1 AND code<>''";
		$sql .= " ORDER BY code ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				$foundselected = false;

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$countryArray [$i] ['rowid'] = $obj->rowid;
					$countryArray [$i] ['code_iso'] = $obj->code_iso;
					$countryArray [$i] ['label'] = ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso) != "Country".$obj->code_iso ? $langs->transnoentitiesnoconv("Country".$obj->code_iso) : ($obj->label != '-' ? $obj->label : ''));
					$label[$i] = $countryArray[$i]['label'];
					$i++;
				}

				$array1_sort_order = SORT_ASC;
				array_multisort($label, $array1_sort_order, $countryArray);

				foreach ($countryArray as $row) {
					$label = dol_trunc($row['label'], $maxlength, 'middle');
					if ($row['code_iso']) {
						$label .= ' ('.$row['code_iso'].')';
					}

					$options_array[$row['rowid']] = $label;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return select list for categories (to use in form search selectors)
	 *
	 * @param string $htmlname control name
	 * @param array $selected_array array of data
	 * @param User $user User action
	 * @return string combo list code
	 */
	public function multiselectselectSalesRepresentatives($htmlname, $selected_array, $user)
	{
		global $conf;

		$options_array = array();

		$sql_usr = '';
		$sql_usr .= "SELECT DISTINCT u2.rowid, u2.lastname as name, u2.firstname, u2.login";
		$sql_usr .= " FROM ".MAIN_DB_PREFIX."user as u2, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql_usr .= " WHERE u2.entity IN (0,".$conf->entity.")";
		$sql_usr .= " AND u2.rowid = sc.fk_user";
		if (getDolGlobalString('USER_HIDE_INACTIVE_IN_COMBOBOX')) {
			$sql_usr .= " AND u2.statut <> 0";
		}
		if (getDolGlobalString('USER_HIDE_NONEMPLOYEE_IN_COMBOBOX')) {
			$sql_usr .= " AND u2.employee<>0 ";
		}
		if (getDolGlobalString('USER_HIDE_EXTERNAL_IN_COMBOBOX')) {
			$sql_usr .= " AND u2.fk_soc IS NULL ";
		}
		$sql_usr .= " ORDER BY name ASC";
		// print $sql_usr;exit;

		$resql_usr = $this->db->query($sql_usr);
		if ($resql_usr) {
			while ($obj_usr = $this->db->fetch_object($resql_usr)) {
				$label = $obj_usr->firstname." ".$obj_usr->name." (".$obj_usr->login.')';

				$options_array [$obj_usr->rowid] = $label;
			}
			$this->db->free($resql_usr);
		} else {
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return select list for categories (to use in form search selectors)
	 *
	 * @param string $htmlname of combo list (example: 'search_sale')
	 * @param array $selected_array selected array
	 * @return string combo list code
	 */
	public function multiselectselectLanguage($htmlname = '', $selected_array = array())
	{
		global $conf, $langs;

		$options_array = array();

		$langs_available = $langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);

		foreach ($langs_available as $key => $value) {
			$label = $value;
			$options_array[$key] = $label;
		}
		asort($options_array);
		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return multiselect list of entities for extrafield type sellist
	 *
	 * @param string $htmlname control name
	 * @param array<string,string> $sqlqueryparam array
	 * @param string[] $selected_array array
	 *
	 *  @return	string HTML combo
	 */
	public function advMultiselectarraySelllist($htmlname, $sqlqueryparam = array(), $selected_array = array())
	{
		$options_array = array();

		if (is_array($sqlqueryparam)) {
			$param_list = array_keys($sqlqueryparam);
			$InfoFieldList = explode(":", $param_list[0], 4);

			// 0 1 : Table name
			// 1 2 : Name of field that contains the label
			// 2 3 : Key fields name (if differ of rowid)
			// 3 4 : Where clause filter on column or table extrafield, syntax field='value' or extra.field=value

			$keyList = 'rowid';
			if (count($InfoFieldList) >= 3) {
				if (strpos($InfoFieldList[3], 'extra.') !== false) {
					$keyList = 'main.'.$InfoFieldList[2].' as rowid';
				} else {
					$keyList = $InfoFieldList[2].' as rowid';
				}
			}

			$sql = "SELECT ".$this->db->sanitize($keyList).", ".$this->db->sanitize($InfoFieldList[1]);
			$sql .= " FROM ".$this->db->sanitize(MAIN_DB_PREFIX.$InfoFieldList[0]);
			if (!empty($InfoFieldList[3])) {
				$errorstr = '';
				// We have to join on extrafield table
				if (strpos($InfoFieldList[3], 'extra') !== false) {
					$sql .= ' as main, '.$this->db->sanitize(MAIN_DB_PREFIX.$InfoFieldList[0]).'_extrafields as extra';
					$sql .= " WHERE extra.fk_object=main.".$this->db->sanitize(empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2]);
					$sql .= " AND ".forgeSQLFromUniversalSearchCriteria($InfoFieldList[3], $errorstr, 1);
				} else {
					$sql .= " WHERE ".forgeSQLFromUniversalSearchCriteria($InfoFieldList[3], $errorstr, 1);
				}
			}
			if (!empty($InfoFieldList[1])) {
				$sql .= " ORDER BY nom";
			}
			// $sql.= ' WHERE entity = '.$conf->entity;

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						$fieldtoread = $InfoFieldList[1];
						$labeltoshow = dol_trunc($obj->$fieldtoread, 90);
						$options_array[$obj->rowid] = $labeltoshow;
						$i++;
					}
				}
				$this->db->free($resql);
			}
		}

		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 *  Return combo list with people title
	 *
	 * 	@param	string $htmlname	       Name of HTML select combo field
	 *  @param  array  $selected_array     Array
	 *  @return	string                     HTML combo
	 */
	public function multiselectCivility($htmlname = 'civilite_id', $selected_array = array())
	{
		global $conf, $langs, $user;
		$langs->load("dict");

		$options_array = array();

		$sql = "SELECT rowid, code, label as civilite, active FROM ".MAIN_DB_PREFIX."c_civility";
		$sql .= " WHERE active = 1";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					// If a translation exists, we use it, else we use the default label
					$label = ($langs->trans("Civility".$obj->code) != "Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->civilite != '-' ? $obj->civilite : ''));

					$options_array[$obj->code] = $label;

					$i++;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return multiselect list of entities.
	 *
	 * @param string $htmlname select
	 * @param array $options_array to manage
	 * @param array $selected_array to manage
	 * @param int $showempty show empty
	 * @return string HTML combo
	 */
	public function advMultiselectarray($htmlname, $options_array = array(), $selected_array = array(), $showempty = 0)
	{
		global $conf, $langs;

		$form = new Form($this->db);
		$return = $form->multiselectarray($htmlname, $options_array, $selected_array, 0, 0, '', 0, 295);
		return $return;
	}

	/**
	 * Return a combo list to select emailing target selector
	 *
	 * @param	string 		$htmlname 		control name
	 * @param	integer 	$selected  		default selected
	 * @param	integer 	$showempty 		empty lines
	 * @param	string		$type_element	Type element. Example: 'mailing'
	 * @param	string		$morecss		More CSS
	 * @return	string 						HTML combo
	 */
	public function selectAdvtargetemailingTemplate($htmlname = 'template_id', $selected = 0, $showempty = 0, $type_element = 'mailing', $morecss = '')
	{
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT c.rowid, c.name, c.fk_element";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_advtarget as c";
		$sql .= " WHERE type_element = '".$this->db->escape($type_element)."'";
		$sql .= " ORDER BY c.name";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$out .= '<select id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
			if ($showempty) {
				$out .= '<option value=""></option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$label = $obj->name;
					if (empty($label)) {
						$label = $obj->fk_element;
					}

					if ($selected > 0 && $selected == $obj->rowid) {
						$out .= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					} else {
						$out .= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error($this->db);
		}
		$this->db->free($resql);
		return $out;
	}
}
