<?php
/* Copyright (C) - 2013-2015 Jean-François FERRY	<jfefe@aternatik.fr>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *       \file       resource/class/html.formresource.class.php
 *       \ingroup    core
 *       \brief      Class file to manage forms into resource module
 */
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/resource/class/dolresource.class.php";


/**
 * Class to manage forms for the module resource
 *
 * \remarks Utilisation: $formresource = new FormResource($db)
 * \remarks $formplace->proprietes=1 ou chaine ou tableau de valeurs
 */
class FormResource
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var array<string,string>
	 */
	public $substit = array();

	/**
	 * @var array<string,mixed>
	 */
	public $param = array();

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output html form to select a resource
	 *
	 *	@param	int		$selected		Preselected resource id
	 *	@param	string	$htmlname		Name of field in form
	 *  @param	string	$filter			Optional filters criteria (example: 's.rowid <> x')
	 *	@param	int		$showempty		Add an empty field
	 * 	@param	int		$showtype		Show third party type in combo list (customer, prospect or supplier)
	 * 	@param	int		$forcecombo		Force to use combo box
	 *  @param	array<array{method:string,url:string,htmlname:string,params:array<string,string>}>	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @param	string	$filterkey		Filter on key value
	 *  @param	int<0,2>	$outputmode		0=HTML select string, 1=Array, 2=without form tag
	 *  @param	int		$limit			Limit number of answers, 0 for no limit
	 *  @param	string	$morecss		More css
	 * 	@param	bool	$multiple		add [] in the name of element and add 'multiple' attribute
	 * 	@return	string|array<array{key:int,value:int,label:string}>			HTML string with
	 */
	public function select_resource_list($selected = 0, $htmlname = 'fk_resource', $filter = '', $showempty = 0, $showtype = 0, $forcecombo = 0, $event = [], $filterkey = '', $outputmode = 0, $limit = 20, $morecss = 'minwidth100', $multiple = false)
	{
		// phpcs:enable
		global $conf, $langs;

		$out = '';
		$outarray = array();

		$resourcestat = new Dolresource($this->db);

		$resources_used = $resourcestat->fetchAll('ASC', 't.rowid', $limit, 0, $filter);

		if (!empty($selected) && !is_array($selected)) {
			$selected = array($selected);
		}

		if ($outputmode != 2) {
			$out = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';
		}

		if ($resourcestat) {
			// Construct $out and $outarray
			$out .= '<select id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').'>'."\n";
			if ($showempty) {
				$out .= '<option value="-1">&nbsp;</option>'."\n";
			}

			$num = 0;
			if (is_array($resourcestat->lines)) {
				$num = count($resourcestat->lines);
			}

			//var_dump($resourcestat->lines);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$resourceclass = ucfirst($resourcestat->lines[$i]->element);

					$label = $resourcestat->lines[$i]->ref ? $resourcestat->lines[$i]->ref : ''.$resourcestat->lines[$i]->label;
					if ($resourceclass != 'Dolresource') {
						$label .= ' ('.$langs->trans($resourceclass).')';
					}

					// Test if entry is the first element of $selected.
					// @phan-suppress-next-line PhanTypeExpectedObjectPropAccess
					if ((isset($selected[0]) && is_object($selected[0]) && $selected[0]->id == $resourcestat->lines[$i]->id) || ((!isset($selected[0]) || !is_object($selected[0])) && !empty($selected) && in_array($resourcestat->lines[$i]->id, $selected))) {
						$out .= '<option value="'.$resourcestat->lines[$i]->id.'" selected>'.$label.'</option>';
					} else {
						$out .= '<option value="'.$resourcestat->lines[$i]->id.'">'.$label.'</option>';
					}

					array_push($outarray, array('key' => (int) $resourcestat->lines[$i]->id, 'value' => (int) $resourcestat->lines[$i]->id, 'label' => (string) $label));

					$i++;
					if (($i % 10) == 0) {
						$out .= "\n";
					}
				}
			}
			$out .= '</select>'."\n";

			if (!empty($conf->use_javascript_ajax) && getDolGlobalString('RESOURCE_USE_SEARCH_TO_SELECT') && !$forcecombo) {
				//$minLength = (is_numeric($conf->global->RESOURCE_USE_SEARCH_TO_SELECT)?$conf->global->RESOURCE_USE_SEARCH_TO_SELECT:2);
				$out .= ajax_combobox($htmlname, $event, $conf->global->RESOURCE_USE_SEARCH_TO_SELECT);
			} else {
				$out .= ajax_combobox($htmlname);
			}

			if ($outputmode != 2) {
				$out .= '<input type="submit" class="button" value="'.$langs->trans("Search").'"> &nbsp; &nbsp; ';

				$out .= '</form>';
			}
		} else {
			dol_print_error($this->db);
		}

		if ($outputmode && $outputmode != 2) {
			return $outarray;
		}
		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return html list of tickets type
	 *
	 *  @param	string	$selected       Id du type pre-selectionne
	 *  @param  string	$htmlname       Nom de la zone select
	 *  @param  string	$filtertype     To filter on field type in llx_c_ticket_type (array('code'=>xx,'label'=>zz))
	 *  @param  int		$format         0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
	 *  @param  int		$empty			1=peut etre vide, 0 sinon
	 *  @param	int		$noadmininfo	0=Add admin info, 1=Disable admin info
	 *  @param  int		$maxlength      Max length of label
	 *  @param	int		$usejscombo		1=Use jscombo, 0=No js combo
	 *  @param	string	$morecss		Add more css
	 * 	@return	void
	 */
	public function select_types_resource($selected = '', $htmlname = 'type_resource', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0, $usejscombo = 0, $morecss = 'minwidth100')
	{
		// phpcs:enable
		global $langs, $user;

		$resourcestat = new Dolresource($this->db);

		dol_syslog(get_class($this)."::select_types_resource ".$selected.", ".$htmlname.", ".$filtertype.", ".$format, LOG_DEBUG);

		$filterarray = array();

		if ($filtertype != '' && $filtertype != '-1') {
			$filterarray = explode(',', $filtertype);
		}

		$resourcestat->loadCacheCodeTypeResource();
		print '<select id="select'.$htmlname.'" class="flat maxwidthonsmartphone select_'.$htmlname.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'">';
		if ($empty) {
			print '<option value="">&nbsp;</option>';
		}
		if (is_array($resourcestat->cache_code_type_resource) && count($resourcestat->cache_code_type_resource)) {
			foreach ($resourcestat->cache_code_type_resource as $id => $arraytypes) {
				// We discard empty line if showempty is on because an empty line has already been output.
				if ($empty && empty($arraytypes['code'])) {
					continue;
				}

				if ($format == 0) {
					print '<option value="'.$id.'"';
				} elseif ($format == 1) {
					print '<option value="'.$arraytypes['code'].'"';
				} elseif ($format == 2) {
					print '<option value="'.$arraytypes['code'].'"';
				} elseif ($format == 3) {
					print '<option value="'.$id.'"';
				}
				// Si selected est text, on compare avec code, sinon avec id
				if (!empty($selected) && preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) {
					print ' selected';
				} elseif ($selected == $id) {
					print ' selected';
				}
				print '>';
				if ($format == 0) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 1) {
					$value = $arraytypes['code'];
				} elseif ($format == 2) {
					$value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
				} elseif ($format == 3) {
					$value = $arraytypes['code'];
				}
				if (empty($value)) {
					$value = '&nbsp;';
				}
				print $value;
				print '</option>';
			}
		}
		print '</select>';
		if ($usejscombo) {
			print ajax_combobox("select".$htmlname);
		}

		if ($user->admin && !$noadmininfo) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return a select list with zip codes and their town
	 *
	 *    @param	string		$selected				Preselected value
	 *    @param    string		$htmlname				HTML select name
	 *    @param    string[]	$fields					Array with key of fields to refresh after selection
	 *    @param    int			$fieldsize				Field size
	 *    @param    int			$disableautocomplete    1 To disable ajax autocomplete features (browser autocomplete may still occurs)
	 *    @param	string		$moreattrib				Add more attribute on HTML input field
	 *    @param    string      $morecss                More css
	 *    @return	string
	 */
	public function select_ziptown($selected = '', $htmlname = 'zipcode', $fields = array(), $fieldsize = 0, $disableautocomplete = 0, $moreattrib = '', $morecss = '')
	{
		// phpcs:enable
		global $conf;

		$out = '';

		$size = '';
		if (!empty($fieldsize)) {
			$size = 'size="' . $fieldsize . '"';
		}

		if ($conf->use_javascript_ajax && empty($disableautocomplete)) {
			$out .= ajax_multiautocompleter($htmlname, $fields, DOL_URL_ROOT . '/core/ajax/ziptown.php') . "\n";
			$moreattrib .= ' autocomplete="off"';
		}
		$out .= '<input id="' . $htmlname . '" class="maxwidthonsmartphone' . ($morecss ? ' ' . $morecss : '') . '" type="text"' . ($moreattrib ? ' ' . $moreattrib : '') . ' name="' . $htmlname . '" ' . $size . ' value="' . $selected . '">' . "\n";

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Returns the drop-down list of departments/provinces/cantons for all countries or for a given country.
	 *   In the case of an all-country list, the display breaks on the country.
	 *   The key of the list is the code (there can be several entries for a given code but in this case, the country field differs).
	 *   Thus the links with the departments are done on a department independently of its name.
	 *
	 *    @param	int		$selected        	Code state preselected (mus be state id)
	 *    @param    integer	$country_codeid    	Country code or id: 0=list for all countries, otherwise country code or country rowid to show
	 *    @param    string	$htmlname			Id of department. If '', we want only the string with <option>
	 *    @param	string	$morecss			Add more css
	 * 	  @return	string						String with HTML select
	 *    @see select_country()
	 */
	public function select_state($selected = 0, $country_codeid = 0, $htmlname = 'state_id', $morecss = 'maxwidth200onsmartphone  minwidth300')
	{
		// phpcs:enable
		global $conf, $langs, $user;

		dol_syslog(get_class($this) . "::select_departement selected=" . $selected . ", country_codeid=" . $country_codeid, LOG_DEBUG);

		$langs->load("dict");

		$out = '';

		// Search active departements/cantons/province of a region and actif country
		$sql = "SELECT d.rowid, d.code_departement as code, d.nom as name, d.active, c.label as country, c.code as country_code, r.nom as region_name FROM";
		$sql .= " " . $this->db->prefix() . "c_departements as d, " . $this->db->prefix() . "c_regions as r," . $this->db->prefix() . "c_country as c";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND c.active = 1";
		if ($country_codeid && is_numeric($country_codeid)) {
			$sql .= " AND c.rowid = '" . $this->db->escape($country_codeid) . "'";
		}
		if ($country_codeid && !is_numeric($country_codeid)) {
			$sql .= " AND c.code = '" . $this->db->escape($country_codeid) . "'";
		}
		$sql .= " ORDER BY c.code, d.code_departement";

		$result = $this->db->query($sql);
		if ($result) {
			if (!empty($htmlname)) {
				$out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '">';
			}
			if ($country_codeid) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			dol_syslog(get_class($this) . "::select_departement num=" . $num, LOG_DEBUG);
			if ($num) {
				$country = '';
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);
					if ($obj->code == '0') {		// Le code peut etre une chaine
						$out .= '<option value="0">&nbsp;</option>';
					} else {
						if (!$country || $country != $obj->country) {
							// Show break if we are in list with multiple countries
							if (!$country_codeid && $obj->country_code) {
								$out .= '<option value="-1" disabled data-html="----- ' . $obj->country . ' -----">----- ' . $obj->country . " -----</option>\n";
								$country = $obj->country;
							}
						}

						if (!empty($selected) && $selected == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '" selected>';
						} else {
							$out .= '<option value="' . $obj->rowid . '">';
						}

						// If translation exists use it, otherwise use default name
						if (
							getDolGlobalString('MAIN_SHOW_STATE_CODE') &&
							(getDolGlobalInt('MAIN_SHOW_STATE_CODE') == 1 || getDolGlobalInt('MAIN_SHOW_STATE_CODE') == 2 || getDolGlobalString('MAIN_SHOW_STATE_CODE') === 'all')
						) {
							if (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1) {
								$out .= $obj->region_name . ' - ' . $obj->code . ' - ' . ($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
							} else {
								$out .= $obj->code . ' - ' . ($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
							}
						} else {
							if (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1) {
								$out .= $obj->region_name . ' - ' . ($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
							} else {
								$out .= ($langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : ($obj->name != '-' ? $obj->name : ''));
							}
						}

						$out .= '</option>';
					}
					$i++;
				}
			}
			if (!empty($htmlname)) {
				$out .= '</select>';
			}
			if (!empty($htmlname) && $user->admin) {
				$out .= ' ' . info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
		} else {
			dol_print_error($this->db);
		}

		// Make select dynamic
		if (!empty($htmlname)) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname);
		}

		return $out;
	}
}
