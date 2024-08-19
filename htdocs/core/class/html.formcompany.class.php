<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Rui Strecht			<rui.strecht@aliartalentos.com>
 * Copyright (C) 2020       Open-Dsi         	<support@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/core/class/html.formcompany.class.php
 *  \ingroup    core
 *	\brief      File of class to build HTML component for third parties management
 */


/**
 *	Class to build HTML component for third parties management
 *	Only common components are here.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';


/**
 * Class of forms component to manage companies
 */
class FormCompany extends Form
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Return list of labels (translated) of third parties type
	 *
	 *		@param	int		$mode		0=Return id+label, 1=Return code+label
	 *      @param  string	$filter     Add a SQL filter to select. Data must not come from user input.
	 *    	@return array      			Array of types
	 */
	public function typent_array($mode = 0, $filter = '')
	{
		// phpcs:enable
		global $langs, $mysoc;

		$effs = array();

		$sql = "SELECT id, code, libelle as label";
		$sql .= " FROM " . $this->db->prefix() . "c_typent";
		$sql .= " WHERE active = 1 AND (fk_country IS NULL OR fk_country = " . (empty($mysoc->country_id) ? '0' : $mysoc->country_id) . ")";
		if ($filter) {
			$sql .= " " . $filter;
		}
		$sql .= " ORDER by position, id";
		dol_syslog(get_class($this) . '::typent_array', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				if (!$mode) {
					$key = $objp->id;
				} else {
					$key = $objp->code;
				}
				if ($langs->trans($objp->code) != $objp->code) {
					$effs[$key] = $langs->trans($objp->code);
				} else {
					$effs[$key] = $objp->label;
				}
				if ($effs[$key] == '-') {
					$effs[$key] = '';
				}
				$i++;
			}
			$this->db->free($resql);
		}

		return $effs;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return the list of entries for staff (no translation, it is number ranges)
	 *
	 *	@param	int		$mode		0=return id+label, 1=return code+Label
	 *	@param  string	$filter     Add a SQL filter to select. Data must not come from user input.
	 *  @return array				Array of types d'effectifs
	 */
	public function effectif_array($mode = 0, $filter = '')
	{
		// phpcs:enable
		$effs = array();

		$sql = "SELECT id, code, libelle as label";
		$sql .= " FROM " . $this->db->prefix() . "c_effectif";
		$sql .= " WHERE active = 1";
		if ($filter) {
			$sql .= " " . $filter;
		}
		$sql .= " ORDER BY id ASC";
		dol_syslog(get_class($this) . '::effectif_array', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				if (!$mode) {
					$key = $objp->id;
				} else {
					$key = $objp->code;
				}

				$effs[$key] = $objp->label != '-' ? $objp->label : '';
				$i++;
			}
			$this->db->free($resql);
		}
		//return natural sorted list
		natsort($effs);
		return $effs;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Affiche formulaire de selection des modes de reglement
	 *
	 *  @param	int		$page        	Page
	 *  @param  int		$selected    	Id or code preselected
	 *  @param  string	$htmlname   	Nom du formulaire select
	 *	@param	int		$empty			Add empty value in list
	 *	@return	void
	 */
	public function form_prospect_level($page, $selected = 0, $htmlname = 'prospect_level_id', $empty = 0)
	{
		// phpcs:enable
		global $user, $langs;

		print '<form method="post" action="' . $page . '">';
		print '<input type="hidden" name="action" value="setprospectlevel">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		dol_syslog(get_class($this) . '::form_prospect_level', LOG_DEBUG);
		$sql = "SELECT code, label";
		$sql .= " FROM " . $this->db->prefix() . "c_prospectlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY sortorder";
		$resql = $this->db->query($sql);
		if ($resql) {
			$options = array();

			if ($empty) {
				$options[''] = '';
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$level = $langs->trans($obj->code);

				if ($level == $obj->code) {
					$level = $langs->trans($obj->label);
				}

				$options[$obj->code] = $level;
			}

			print Form::selectarray($htmlname, $options, $selected);
		} else {
			dol_print_error($this->db);
		}
		if (!empty($htmlname) && $user->admin) {
			print ' ' . info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '<input type="submit" class="button button-save valignmiddle small" value="' . $langs->trans("Modify") . '">';
		print '</form>';
	}

	/**
	 *  Affiche formulaire de selection des niveau de prospection pour les contacts
	 *
	 *  @param	int		$page        	Page
	 *  @param  int		$selected    	Id or code preselected
	 *  @param  string	$htmlname   	Nom du formulaire select
	 *	@param	int		$empty			Add empty value in list
	 *	@return	void
	 */
	public function formProspectContactLevel($page, $selected = 0, $htmlname = 'prospect_contact_level_id', $empty = 0)
	{
		global $user, $langs;

		print '<form method="post" action="' . $page . '">';
		print '<input type="hidden" name="action" value="setprospectcontactlevel">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT code, label";
		$sql .= " FROM " . $this->db->prefix() . "c_prospectcontactlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY sortorder";
		$resql = $this->db->query($sql);
		if ($resql) {
			$options = array();

			if ($empty) {
				$options[''] = '';
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$level = $langs->trans($obj->code);

				if ($level == $obj->code) {
					$level = $langs->trans($obj->label);
				}

				$options[$obj->code] = $level;
			}

			print Form::selectarray($htmlname, $options, $selected);
		} else {
			dol_print_error($this->db);
		}
		if (!empty($htmlname) && $user->admin) {
			print ' ' . info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '<input type="submit" class="button button-save valignmiddle small" value="' . $langs->trans("Modify") . '">';
		print '</form>';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Returns the drop-down list of departments/provinces/cantons for all countries or for a given country.
	 *   In the case of an all-country list, the display breaks on the country.
	 *   The key of the list is the code (there can be several entries for a given code but in this case, the country field differs).
	 *   Thus the links with the departments are done on a department independently of its name.
	 *
	 *   @param     string	$selected        	Code state preselected
	 *   @param     int		$country_codeid     0=list for all countries, otherwise country code or country rowid to show
	 *   @param     string	$htmlname			Id of department
	 *   @return	void
	 */
	public function select_departement($selected = '', $country_codeid = 0, $htmlname = 'state_id')
	{
		// phpcs:enable
		print $this->select_state($selected, $country_codeid, $htmlname);
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

		// Search departements/cantons/province active d'une region et pays actif
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

						// Si traduction existe, on l'utilise, sinon on prend le libelle par default
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Returns the drop-down list of departments/provinces/cantons for all countries or for a given country.
	 *   In the case of an all-country list, the display breaks on the country.
	 *   The key of the list is the code (there can be several entries for a given code but in this case, the country field differs).
	 *   Thus the links with the departments are done on a department independently of its name.
	 *
	 *    @param	string		$parent_field_id        Parent select name to monitor
	 *    @param	integer		$selected        	Code state preselected (mus be state id)
	 *    @param    integer		$country_codeid    	Country code or id: 0=list for all countries, otherwise country code or country rowid to show
	 *    @param    string		$htmlname			Id of department. If '', we want only the string with <option>
	 *    @param	string		$morecss			Add more css
	 * 	  @return	string						String with HTML select
	 *    @see select_country()
	 */
	public function select_state_ajax($parent_field_id = 'country_id', $selected = 0, $country_codeid = 0, $htmlname = 'state_id', $morecss = 'maxwidth200onsmartphone  minwidth300')
	{
		$html = '<script>';
		$html .= '$("select[name=\"'.$parent_field_id.'\"]").change(function(){
				$.ajax( "'.dol_buildpath('/core/ajax/ziptown.php', 2).'", { data:{ selected: $("select[name=\"'.$htmlname.'\"]").val(), country_codeid: $(this).val(), htmlname:"'.$htmlname.'", morecss:"'.$morecss.'" } } )
				.done(function(msg) {
					$("span#target_'.$htmlname.'").html(msg);
				})
			});';
		return $html.'</script><span id="target_'.$htmlname.'">'.$this->select_state($selected, $country_codeid, $htmlname, $morecss).'</span>';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Provides the dropdown of the active regions including the actif country.
	 *   The key of the list is the code (there may be more than one entry for a
	 *   code but in that case the fields country and language are different).
	 *   un code donnee mais dans ce cas, le champ pays et lang differe).
	 *   This way the links with the regions are made independent of its name.
	 *
	 *   @param		string		$selected		Preselected value
	 *   @param		string		$htmlname		Name of HTML select field
	 *   @return	void
	 */
	public function select_region($selected = '', $htmlname = 'region_id')
	{
		// phpcs:enable
		global $conf, $langs;
		$langs->load("dict");

		$sql = "SELECT r.rowid, r.code_region as code, r.nom as label, r.active, c.code as country_code, c.label as country";
		$sql .= " FROM " . $this->db->prefix() . "c_regions as r, " . $this->db->prefix() . "c_country as c";
		$sql .= " WHERE r.fk_pays=c.rowid AND r.active = 1 and c.active = 1";
		$sql .= " ORDER BY c.code, c.label ASC";

		dol_syslog(get_class($this) . "::select_region", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			print '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				$country = '';
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if ($obj->code == 0) {
						print '<option value="0">&nbsp;</option>';
					} else {
						if ($country == '' || $country != $obj->country) {
							// Show break
							$key = $langs->trans("Country" . strtoupper($obj->country_code));
							$valuetoshow = ($key != "Country" . strtoupper($obj->country_code)) ? $obj->country_code . " - " . $key : $obj->country;
							print '<option value="-2" disabled>----- ' . $valuetoshow . " -----</option>\n";
							$country = $obj->country;
						}

						if ($selected > 0 && $selected == $obj->code) {
							print '<option value="' . $obj->code . '" selected>' . $obj->label . '</option>';
						} else {
							print '<option value="' . $obj->code . '">' . $obj->label . '</option>';
						}
					}
					$i++;
				}
			}
			print '</select>';
			print ajax_combobox($htmlname);
		} else {
			dol_print_error($this->db);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return combo list with people title
	 *
	 *  @param  string	$selected   	Civility/Title code preselected
	 * 	@param	string	$htmlname		Name of HTML select combo field
	 *  @param  string  $morecss        Add more css on SELECT element
	 *  @param	int		$addjscombo		Add js combo
	 *  @return	string					String with HTML select
	 */
	public function select_civility($selected = '', $htmlname = 'civility_id', $morecss = 'maxwidth150', $addjscombo = 1)
	{
		// phpcs:enable
		global $conf, $langs, $user;
		$langs->load("dict");

		$out = '';

		$sql = "SELECT rowid, code, label, active FROM " . $this->db->prefix() . "c_civility";
		$sql .= " WHERE active = 1";

		dol_syslog("Form::select_civility", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$out .= '<select class="flat' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '" id="' . $htmlname . '">';
			$out .= '<option value="">&nbsp;</option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if ($selected == $obj->code) {
						$out .= '<option value="' . $obj->code . '" selected>';
					} else {
						$out .= '<option value="' . $obj->code . '">';
					}
					// If translation exists, we use it, otherwise, we use the hard coded label
					$out .= ($langs->trans("Civility" . $obj->code) != "Civility" . $obj->code ? $langs->trans("Civility" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
					$out .= '</option>';
					$i++;
				}
			}
			$out .= '</select>';
			if ($user->admin) {
				$out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			if ($addjscombo) {
				// Enhance with select2
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname);
			}
		} else {
			dol_print_error($this->db);
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return the list of all juridical entity types for all countries or a specific country.
	 *    A country separator is included in case the list for all countries is returned.
	 *
	 *    @param	string		$selected        	Preselected code for juridical type
	 *    @param    mixed		$country_codeid		0=All countries, else the code of the country to display
	 *    @param    string		$filter          	Add a SQL filter on list
	 *    @return	void
	 *    @deprecated Use print xxx->select_juridicalstatus instead
	 *    @see select_juridicalstatus()
	 */
	public function select_forme_juridique($selected = '', $country_codeid = 0, $filter = '')
	{
		// phpcs:enable
		print $this->select_juridicalstatus($selected, $country_codeid, $filter);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return the list of all juridical entity types for all countries or a specific country.
	 *    A country separator is included in case the list for all countries is returned.
	 *
	 *    @param	string		$selected        	Preselected code of juridical type
	 *    @param    int			$country_codeid     0=list for all countries, otherwise list only country requested
	 *    @param    string		$filter          	Add a SQL filter on list. Data must not come from user input.
	 *    @param	string		$htmlname			HTML name of select
	 *    @param	string		$morecss			More CSS
	 *    @return	string							String with HTML select
	 */
	public function select_juridicalstatus($selected = '', $country_codeid = 0, $filter = '', $htmlname = 'forme_juridique_code', $morecss = '')
	{
		// phpcs:enable
		global $conf, $langs, $user;
		$langs->load("dict");

		$out = '';

		// Lookup the active juridical types for the active countries
		$sql  = "SELECT f.rowid, f.code as code , f.libelle as label, f.active, c.label as country, c.code as country_code";
		$sql .= " FROM " . $this->db->prefix() . "c_forme_juridique as f, " . $this->db->prefix() . "c_country as c";
		$sql .= " WHERE f.fk_pays=c.rowid";
		$sql .= " AND f.active = 1 AND c.active = 1";
		if ($country_codeid) {
			$sql .= " AND c.code = '" . $this->db->escape($country_codeid) . "'";
		}
		if ($filter) {
			$sql .= " " . $filter;
		}
		$sql .= " ORDER BY c.code";

		dol_syslog(get_class($this) . "::select_juridicalstatus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$out .= '<div id="particulier2" class="visible">';
			$out .= '<select class="flat minwidth200' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '" id="' . $htmlname . '">';
			if ($country_codeid) {
				$out .= '<option value="0">&nbsp;</option>'; // When country_codeid is set, we force to add an empty line because it does not appears from select. When not set, we already get the empty line from select.
			}

			$num = $this->db->num_rows($resql);
			if ($num) {
				$i = 0;
				$country = '';
				$arraydata = array();
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					if ($obj->code) {		// We exclude empty line, we will add it later
						$labelcountry = (($langs->trans("Country" . $obj->country_code) != "Country" . $obj->country_code) ? $langs->trans("Country" . $obj->country_code) : $obj->country);
						$labeljs = (($langs->trans("JuridicalStatus" . $obj->code) != "JuridicalStatus" . $obj->code) ? $langs->trans("JuridicalStatus" . $obj->code) : ($obj->label != '-' ? $obj->label : '')); // $obj->label is already in output charset (converted by database driver)
						$arraydata[$obj->code] = array('code' => $obj->code, 'label' => $labeljs, 'label_sort' => $labelcountry . '_' . $labeljs, 'country_code' => $obj->country_code, 'country' => $labelcountry);
					}
					$i++;
				}

				$arraydata = dol_sort_array($arraydata, 'label_sort', 'ASC');
				if (empty($country_codeid)) {	// Introduce empty value (if $country_codeid not empty, empty value was already added)
					$arraydata[0] = array('code' => 0, 'label' => '', 'label_sort' => '_', 'country_code' => '', 'country' => '');
				}

				foreach ($arraydata as $key => $val) {
					if (!$country || $country != $val['country']) {
						// Show break when we are in multi country mode
						if (empty($country_codeid) && $val['country_code']) {
							$out .= '<option value="0" disabled class="selectoptiondisabledwhite">----- ' . $val['country'] . " -----</option>\n";
							$country = $val['country'];
						}
					}

					if ($selected > 0 && $selected == $val['code']) {
						$out .= '<option value="' . $val['code'] . '" selected>';
					} else {
						$out .= '<option value="' . $val['code'] . '">';
					}
					// If translation exists, we use it, otherwise we use default label in database
					$out .= $val['label'];
					$out .= '</option>';
				}
			}
			$out .= '</select>';
			if ($user->admin) {
				$out .= ' ' . info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			// Make select dynamic
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname);

			$out .= '</div>';
		} else {
			dol_print_error($this->db);
		}

		return $out;
	}


	/**
	 *  Output list of third parties.
	 *
	 *  @param  object		$object         Object we try to find contacts
	 *  @param  string		$var_id         Name of id field
	 *  @param  int 		$selected       Pre-selected third party
	 *  @param  string		$htmlname       Name of HTML form
	 * 	@param	array		$limitto		Disable answers that are not id in this array list
	 *  @param	int			$forceid		This is to force another object id than object->id
	 *  @param	string		$moreparam		String with more param to add into url when noajax search is used.
	 *  @param	string		$morecss		More CSS on select component
	 * 	@return int 						The selected third party ID
	 */
	public function selectCompaniesForNewContact($object, $var_id, $selected = 0, $htmlname = 'newcompany', $limitto = [], $forceid = 0, $moreparam = '', $morecss = '')
	{
		global $conf, $hookmanager;

		if (!empty($conf->use_javascript_ajax) && getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT')) {
			// Use Ajax search
			$minLength = (is_numeric(getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT')) ? $conf->global->COMPANY_USE_SEARCH_TO_SELECT : 2);

			$socid = 0;
			$name = '';
			if ($selected > 0) {
				$tmpthirdparty = new Societe($this->db);
				$result = $tmpthirdparty->fetch($selected);
				if ($result > 0) {
					$socid = $selected;
					$name = $tmpthirdparty->name;
				}
			}


			$events = array();
			// Add an entry 'method' to say 'yes, we must execute url with param action = method';
			// Add an entry 'url' to say which url to execute
			// Add an entry htmlname to say which element we must change once url is called
			// Add entry params => array('cssid' => 'attr') to say to remov or add attribute attr if answer of url return  0 or >0 lines
			// To refresh contacts list on thirdparty list change
			$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));

			if (count($events)) {	// If there is some ajax events to run once selection is done, we add code here to run events
				print '<script nonce="' . getNonce() . '" type="text/javascript">
				jQuery(document).ready(function() {
					$("#search_' . $htmlname . '").change(function() {
						var obj = ' . json_encode($events) . ';
						$.each(obj, function(key,values) {
							if (values.method.length) {
								runJsCodeForEvent' . $htmlname . '(values);
							}
						});

						$(this).trigger("blur");
					});

					// Function used to execute events when search_htmlname change
					function runJsCodeForEvent' . $htmlname . '(obj) {
						var id = $("#' . $htmlname . '").val();
						var method = obj.method;
						var url = obj.url;
						var htmlname = obj.htmlname;
						var showempty = obj.showempty;
						console.log("Run runJsCodeForEvent-' . $htmlname . ' from selectCompaniesForNewContact id="+id+" method="+method+" showempty="+showempty+" url="+url+" htmlname="+htmlname);
						$.getJSON(url,
							{
								action: method,
								id: id,
								htmlname: htmlname
							},
							function(response) {
								if (response != null)
								{
									console.log("Change select#"+htmlname+" with content "+response.value)
									$.each(obj.params, function(key,action) {
										if (key.length) {
											var num = response.num;
											if (num > 0) {
												$("#" + key).removeAttr(action);
											} else {
												$("#" + key).attr(action, action);
											}
										}
									});
									$("select#" + htmlname).html(response.value);
								}
							}
						);
					}
				});
				</script>';
			}

			print "\n" . '<!-- Input text for third party with Ajax.Autocompleter (selectCompaniesForNewContact) -->' . "\n";
			print '<input type="text" size="30" id="search_' . $htmlname . '" name="search_' . $htmlname . '" value="' . $name . '" />';
			print ajax_autocompleter((string) ($socid ? $socid : -1), $htmlname, DOL_URL_ROOT . '/societe/ajax/ajaxcompanies.php', '', $minLength, 0);
			return $socid;
		} else {
			// Search to list thirdparties
			$sql = "SELECT s.rowid, s.nom as name ";
			if (getDolGlobalString('SOCIETE_ADD_REF_IN_LIST')) {
				$sql .= ", s.code_client, s.code_fournisseur";
			}
			if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
				$sql .= ", s.address, s.zip, s.town";
				$sql .= ", dictp.code as country_code";
			}
			$sql .= " FROM " . $this->db->prefix() . "societe as s";
			if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
				$sql .= " LEFT JOIN " . $this->db->prefix() . "c_country as dictp ON dictp.rowid = s.fk_pays";
			}
			$sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";
			// For ajax search we limit here. For combo list, we limit later
			if (is_array($limitto) && count($limitto)) {
				$sql .= " AND s.rowid IN (" . $this->db->sanitize(implode(',', $limitto)) . ")";
			}
			// Add where from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('selectCompaniesForNewContactListWhere', $parameters); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
			$sql .= " ORDER BY s.nom ASC";

			$resql = $this->db->query($sql);
			if ($resql) {
				print '<select class="flat' . ($morecss ? ' ' . $morecss : '') . '" id="' . $htmlname . '" name="' . $htmlname . '"';
				if ($conf->use_javascript_ajax) {
					$javaScript = "window.location='" . dol_escape_js($_SERVER['PHP_SELF']) . "?" . $var_id . "=" . ($forceid > 0 ? $forceid : $object->id) . $moreparam . "&" . $htmlname . "=' + form." . $htmlname . ".options[form." . $htmlname . ".selectedIndex].value;";
					print ' onChange="' . $javaScript . '"';
				}
				print '>';
				print '<option value="-1">&nbsp;</option>';

				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($i == 0) {
							$firstCompany = $obj->rowid;
						}
						$disabled = 0;
						if (is_array($limitto) && count($limitto) && !in_array($obj->rowid, $limitto)) {
							$disabled = 1;
						}
						if ($selected > 0 && $selected == $obj->rowid) {
							print '<option value="' . $obj->rowid . '"';
							if ($disabled) {
								print ' disabled';
							}
							print ' selected>' . dol_escape_htmltag($obj->name, 0, 0, '', 0, 1) . '</option>';
							$firstCompany = $obj->rowid;
						} else {
							print '<option value="' . $obj->rowid . '"';
							if ($disabled) {
								print ' disabled';
							}
							print '>' . dol_escape_htmltag($obj->name, 0, 0, '', 0, 1) . '</option>';
						}
						$i++;
					}
				}
				print "</select>\n";
				print ajax_combobox($htmlname);
				return $firstCompany;
			} else {
				dol_print_error($this->db);
				return 0;
			}
		}
	}

	/**
	 *  Return a select list with types of contacts
	 *
	 *  @param	object		$object         	Object to use to find type of contact
	 *  @param  string		$selected       	Default selected value
	 *  @param  string		$htmlname			HTML select name
	 *  @param  string		$source				Source ('internal' or 'external')
	 *  @param  string		$sortorder			Sort criteria ('position', 'code', ...)
	 *  @param  int			$showempty      	1=Add en empty line
	 *  @param  string      $morecss        	Add more css to select component
	 *  @param  int      	$output         	0=return HTML, 1= direct print
	 *  @param	int			$forcehidetooltip	Force hide tooltip for admin
	 *  @return	string|void						Depending on $output param, return the HTML select list (recommended method) or nothing
	 */
	public function selectTypeContact($object, $selected, $htmlname = 'type', $source = 'internal', $sortorder = 'position', $showempty = 0, $morecss = '', $output = 1, $forcehidetooltip = 0)
	{
		global $user, $langs;

		$out = '';
		if (is_object($object) && method_exists($object, 'liste_type_contact')) {
			$lesTypes = $object->liste_type_contact($source, $sortorder, 0, 1);	// List of types into c_type_contact for element=$object->element

			$out .= '<select class="flat valignmiddle' . ($morecss ? ' ' . $morecss : '') . '" name="' . $htmlname . '" id="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			foreach ($lesTypes as $key => $value) {
				$out .= '<option value="' . $key . '"';
				if ($key == $selected) {
					$out .= ' selected';
				}
				$out .= '>' . $value . '</option>';
			}
			$out .= "</select>";
			if ($user->admin && empty($forcehidetooltip)) {
				$out .= ' ' . info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			$out .= ajax_combobox($htmlname);

			$out .= "\n";
		}
		if (empty($output)) {
			return $out;
		} else {
			print $out;
		}
	}

	/**
	 * showContactRoles on view and edit mode
	 *
	 * @param 	string 		$htmlname 		Html component name and id
	 * @param 	Contact 	$contact 		Contact Object
	 * @param 	string 		$rendermode 	view, edit
	 * @param 	array		$selected 		$key=>$val $val is selected Roles for input mode
	 * @param	string		$morecss		More css
	 * @param	string		$placeholder	Placeholder text (used when $rendermode is 'edit')
	 * @return 	string   					String with contacts roles
	 */
	public function showRoles($htmlname, Contact $contact, $rendermode = 'view', $selected = array(), $morecss = 'minwidth500', $placeholder = '')
	{
		if ($rendermode === 'view') {
			$toprint = array();
			foreach ($contact->roles as $key => $val) {
				$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb;">' . $val['label'] . '</li>';
			}
			return '<div class="select2-container-multi-dolibarr" style="width: 90%;" id="' . $htmlname . '"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		}

		if ($rendermode === 'edit') {	// A multiselect combo list
			$contactType = $contact->listeTypeContacts('external', '', 1, '', '', 'agenda'); // We exclude agenda as there is no contact on such element
			if (count($selected) > 0) {
				$newselected = array();
				foreach ($selected as $key => $val) {
					if (is_array($val) && array_key_exists('id', $val) && in_array($val['id'], array_keys($contactType))) {
						$newselected[] = $val['id'];
					} else {
						break;
					}
				}
				if (count($newselected) > 0) {
					$selected = $newselected;
				}
			}
			return $this->multiselectarray($htmlname, $contactType, $selected, 0, 0, $morecss, 0, '90%', '', '', $placeholder);
		}

		return 'ErrorBadValueForParameterRenderMode'; // Should not happened
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return a select list with zip codes and their town
	 *
	 *    @param	string		$selected				Preselected value
	 *    @param    string		$htmlname				HTML select name
	 *    @param    array		$fields					Array with key of fields to refresh after selection
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
	 *  Return HTML string to use as input of professional id into a HTML page (siren, siret, etc...)
	 *
	 *  @param	int		$idprof         1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *  @param  string	$htmlname       Name of HTML select
	 *  @param  string	$preselected    Default value to show
	 *  @param  string	$country_code   FR, IT, ...
	 *  @param  string  $morecss        More css
	 *  @return	string					HTML string with prof id
	 */
	public function get_input_id_prof($idprof, $htmlname, $preselected, $country_code, $morecss = 'maxwidth200')
	{
		// phpcs:enable
		global $conf, $langs, $hookmanager;

		$formlength = 0;
		if (!getDolGlobalString('MAIN_DISABLEPROFIDRULES')) {
			if ($country_code == 'FR') {
				if (isset($idprof)) {
					if ($idprof == 1) {
						$formlength = 9;
					} elseif ($idprof == 2) {
						$formlength = 14;
					} elseif ($idprof == 3) {
						$formlength = 5; // 4 chiffres et 1 lettre depuis janvier
					} elseif ($idprof == 4) {
						$formlength = 32; // No maximum as we need to include a town name in this id
					}
				}
			} elseif ($country_code == 'ES') {
				if ($idprof == 1) {
					$formlength = 9; //CIF/NIF/NIE 9 digits
				}
				if ($idprof == 2) {
					$formlength = 12; //NASS 12 digits without /
				}
				if ($idprof == 3) {
					$formlength = 5; //CNAE 5 digits
				}
				if ($idprof == 4) {
					$formlength = 32; //depend of college
				}
			}
		}

		$selected = $preselected;
		if (!$selected && isset($idprof)) {
			if ($idprof == 1 && !empty($this->idprof1)) {
				$selected = $this->idprof1;
			} elseif ($idprof == 2 && !empty($this->idprof2)) {
				$selected = $this->idprof2;
			} elseif ($idprof == 3 && !empty($this->idprof3)) {
				$selected = $this->idprof3;
			} elseif ($idprof == 4 && !empty($this->idprof4)) {
				$selected = $this->idprof4;
			}
		}

		$maxlength = $formlength;
		if (empty($formlength)) {
			$formlength = 24;
			$maxlength = 128;
		}

		$out = '';

		// Execute hook getInputIdProf to complete or replace $out
		$parameters = array('formlength' => $formlength, 'selected' => $preselected, 'idprof' => $idprof, 'htmlname' => $htmlname, 'country_code' => $country_code);
		$reshook = $hookmanager->executeHooks('getInputIdProf', $parameters);
		if (empty($reshook)) {
			$out .= '<input type="text" ' . ($morecss ? 'class="' . $morecss . '" ' : '') . 'name="' . $htmlname . '" id="' . $htmlname . '" maxlength="' . $maxlength . '" value="' . $selected . '">';
		}
		$out .= $hookmanager->resPrint;

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return a HTML select with localtax values for thirdparties
	 *
	 * @param 	int 		$local			LocalTax
	 * @param 	float 		$selected		Preselected value
	 * @param 	string      $htmlname		HTML select name
	 * @return	void
	 */
	public function select_localtax($local, $selected, $htmlname)
	{
		// phpcs:enable
		$tax = get_localtax_by_third($local);

		if ($tax) {
			$valors = explode(":", $tax);
			$nbvalues = count($valors);

			if ($nbvalues > 1) {
				//montar select
				print '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
				$i = 0;
				while ($i < $nbvalues) {
					if ($selected == $valors[$i]) {
						print '<option value="' . $valors[$i] . '" selected>';
					} else {
						print '<option value="' . $valors[$i] . '">';
					}
					print $valors[$i];
					print '</option>';
					$i++;
				}
				print '</select>';
			}
		}
	}

	/**
	 * Return a HTML select for thirdparty type
	 *
	 * @param int 		$selected 		Selected value
	 * @param string 	$htmlname 		HTML select name
	 * @param string 	$htmlidname 	HTML select id
	 * @param string 	$typeinput 		HTML output
	 * @param string 	$morecss 		More css
	 * @param string	$allowempty		Allow empty value or not
	 * @return string 					HTML string
	 */
	public function selectProspectCustomerType($selected, $htmlname = 'client', $htmlidname = 'customerprospect', $typeinput = 'form', $morecss = '', $allowempty = '')
	{
		global $conf, $langs;
		if (getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && !isModEnabled('fournisseur')) {
			return '';
		}

		$out = '<select class="flat ' . $morecss . '" name="' . $htmlname . '" id="' . $htmlidname . '">';
		if ($typeinput == 'form') {
			if ($allowempty || ($selected == '' || $selected == '-1')) {
				$out .= '<option value="-1">';
				if (is_numeric($allowempty)) {
					$out .= '&nbsp;';
				} else {
					$out .= $langs->trans($allowempty);
				}
				$out .= '</option>';
			}
			if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
				$out .= '<option value="2"' . ($selected == 2 ? ' selected' : '') . '>' . $langs->trans('Prospect') . '</option>';
			}
			if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
				$out .= '<option value="3"' . ($selected == 3 ? ' selected' : '') . '>' . $langs->trans('ProspectCustomer') . '</option>';
			}
			if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
				$out .= '<option value="1"' . ($selected == 1 ? ' selected' : '') . '>' . $langs->trans('Customer') . '</option>';
			}
			$out .= '<option value="0"' . ((string) $selected == '0' ? ' selected' : '') . '>' . $langs->trans('NorProspectNorCustomer') . '</option>';
		} elseif ($typeinput == 'list') {
			$out .= '<option value="-1"' . (($selected == '' || $selected == '-1') ? ' selected' : '') . '>&nbsp;</option>';
			if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
				$out .= '<option value="2,3"' . ($selected == '2,3' ? ' selected' : '') . '>' . $langs->trans('Prospect') . '</option>';
			}
			if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
				$out .= '<option value="1,3"' . ($selected == '1,3' ? ' selected' : '') . '>' . $langs->trans('Customer') . '</option>';
			}
			if (isModEnabled("fournisseur")) {
				$out .= '<option value="4"' . ($selected == '4' ? ' selected' : '') . '>' . $langs->trans('Supplier') . '</option>';
			}
			$out .= '<option value="0"' . ($selected == '0' ? ' selected' : '') . '>' . $langs->trans('Other') . '</option>';
		} elseif ($typeinput == 'admin') {
			if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
				$out .= '<option value="3"' . ($selected == 3 ? ' selected' : '') . '>' . $langs->trans('ProspectCustomer') . '</option>';
			}
			if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
				$out .= '<option value="1"' . ($selected == 1 ? ' selected' : '') . '>' . $langs->trans('Customer') . '</option>';
			}
		}
		$out .= '</select>';
		$out .= ajax_combobox($htmlidname);

		return $out;
	}

	/**
	 *  Output html select to select third-party type
	 *
	 *  @param	string	$page       	Page
	 *  @param  string	$selected   	Id preselected
	 *  @param  string	$htmlname		Name of HTML select
	 *  @param  string	$filter         optional filters criteras
	 *  @param  int     $nooutput       No print output. Return it only.
	 *  @return	void|string
	 */
	public function formThirdpartyType($page, $selected = '', $htmlname = 'socid', $filter = '', $nooutput = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		$out = '';
		if ($htmlname != "none") {
			$out .= '<form method="post" action="' . $page . '">';
			$out .= '<input type="hidden" name="action" value="set_thirdpartytype">';
			$out .= '<input type="hidden" name="token" value="' . newToken() . '">';
			$sortparam = (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
			$out .= $this->selectarray($htmlname, $this->typent_array(0, $filter), $selected, 1, 0, 0, '', 0, 0, 0, $sortparam, '', 1);
			$out .= '<input type="submit" class="button smallpaddingimp valignmiddle" value="' . $langs->trans("Modify") . '">';
			$out .= '</form>';
		} else {
			if ($selected > 0) {
				$arr = $this->typent_array(0);
				$typent = empty($arr[$selected]) ? '' : $arr[$selected];
				$out .= $typent;
			} else {
				$out .= "&nbsp;";
			}
		}

		if ($nooutput) {
			return $out;
		} else {
			print $out;
		}
	}

	/**
	 *  Output html select to select prospect status
	 *
	 *  @param  string			$htmlname		Name of HTML select
	 *  @param	Societe|null	$prospectstatic Prospect object
	 *  @param  int				$statusprospect	status of prospect
	 *  @param  int				$idprospect     id of prospect
	 *  @param  string  		$mode      		select if we want activate de html part or js
	 *  @return	void
	 */
	public function selectProspectStatus($htmlname, $prospectstatic, $statusprospect, $idprospect, $mode = "html")
	{
		global $user, $langs;

		if ($mode === "html") {
			$actioncode = empty($prospectstatic->cacheprospectstatus[$statusprospect]) ? '' : $prospectstatic->cacheprospectstatus[$statusprospect]['code'];
			$actionpicto = empty($prospectstatic->cacheprospectstatus[$statusprospect]['picto']) ? '' : $prospectstatic->cacheprospectstatus[$statusprospect]['picto'];

			//print $prospectstatic->LibProspCommStatut($statusprospect, 2, $prospectstatic->cacheprospectstatus[$statusprospect]['label'], $prospectstatic->cacheprospectstatus[$statusprospect]['picto']);
			print img_action('', $actioncode, $actionpicto, 'class="inline-block valignmiddle paddingright pictoprospectstatus"');
			print '<select class="flat selectprospectstatus maxwidth150" id="'. $htmlname.$idprospect .'" data-socid="'.$idprospect.'" name="' . $htmlname .'"';
			if (!$user->hasRight('societe', 'creer')) {
				print ' disabled';
			}
			print '>';
			foreach ($prospectstatic->cacheprospectstatus as $key => $val) {
				//$titlealt = (empty($val['label']) ? 'default' : $val['label']);
				$label = $val['label'];
				if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
					//$titlealt = $val['label'];
					$label = (($langs->trans("StatusProspect".$val['code']) != "StatusProspect".$val['code']) ? $langs->trans("StatusProspect".$val['code']) : $label);
				} else {
					$label = (($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id']) ? $langs->trans("StatusProspect".$val['id']) : $label);
				}
				print '<option value="'.$val['id'].'" data-html="'.dol_escape_htmltag(img_action('', $val['code'], $val['picto']).' '.$label).'" title="'.dol_escape_htmltag($label).'"'.($statusprospect == $val['id'] ? ' selected' : '').'>';
				print dol_escape_htmltag($label);
				print '</option>';
			}
			print '</select>';
			print ajax_combobox($htmlname.$idprospect);
		} elseif ($mode === "js") {
			print '<script>
				jQuery(document).ready(function() {
					$(".selectprospectstatus").on("change", function() {
						console.log("We change a value into a field selectprospectstatus");
						var statusid = $(this).val();
						var prospectid = $(this).attr("data-socid");
						var image = $(this).prev(".pictoprospectstatus");
						$.ajax({
							type: "POST",
							url: \'' . DOL_URL_ROOT . '/core/ajax/ajaxstatusprospect.php\',
							data: { id: statusid, prospectid: prospectid, token: \''. newToken() .'\', action: \'updatestatusprospect\' },
							success: function(response) {
								console.log(response.img);
								image.replaceWith(response.img);
							},
							error: function() {
								console.error("Error on status prospect");
							},
					});
				});
			});
			</script>';
		}
	}
}
