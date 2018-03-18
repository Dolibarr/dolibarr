<?php
/* Copyright (C) 2014  Florian Henry   <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    comm/mailing/class/html.formadvtragetemaling.class.php
 * \ingroup mailing
 * \brief   Fichier de la classe des fonctions predefinie de composants html advtargetemaling
 */

/**
 * Class to manage building of HTML components
 */
class FormAdvTargetEmailing extends Form
{
	var $db;
	var $error;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
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
	function multiselectProspectionStatus($selected_array = array(), $htmlname = 'cust_prospect_status') {
		global $conf, $langs;
		$options_array = array();

		$sql = "SELECT code, label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_prospectlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY sortorder";
		dol_syslog ( get_class( $this ) . '::multiselectProspectionStatus sql=' . $sql, LOG_DEBUG );
		$resql = $this->db->query( $sql );
		if ($resql) {
			$num = $this->db->num_rows( $resql );
			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object( $resql );

				$level = $langs->trans( $obj->code );
				if ($level == $obj->code)
					$level = $langs->trans( $obj->label );
				$options_array[$obj->code] = $level;

				$i ++;
			}
		} else {
			dol_print_error($this->db);
		}
		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return combo list of activated countries, into language of user
	 *
	 * @param string $htmlname of html select object
	 * @param array $selected_array or Code or Label of preselected country
	 * @return string HTML string with select
	 */
	function multiselectCountry($htmlname = 'country_id', $selected_array=array()) {
		global $conf, $langs;

		$langs->load("dict");
		$maxlength = 0;

		$out = '';
		$countryArray = array();
		$label = array ();

		$options_array = array();

		$sql = "SELECT rowid, code as code_iso, label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_country";
		$sql .= " WHERE active = 1 AND code<>''";
		$sql .= " ORDER BY code ASC";

		dol_syslog(get_class($this) . "::select_country sql=" . $sql);
		$resql = $this->db->query($sql);
		if ($resql) {

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				$foundselected = false;

				while ($i < $num) {
					$obj = $this->db->fetch_object ( $resql );
					$countryArray [$i] ['rowid'] = $obj->rowid;
					$countryArray [$i] ['code_iso'] = $obj->code_iso;
					$countryArray [$i] ['label'] = ($obj->code_iso && $langs->transnoentitiesnoconv("Country" . $obj->code_iso ) != "Country" . $obj->code_iso ? $langs->transnoentitiesnoconv ( "Country" . $obj->code_iso ) : ($obj->label != '-' ? $obj->label : ''));
					$label[$i] = $countryArray[$i]['label'];
					$i ++;
				}

				array_multisort($label, SORT_ASC, $countryArray);

				foreach ($countryArray as $row) {
					$label = dol_trunc($row['label'], $maxlength, 'middle');
					if ($row['code_iso'])
						$label .= ' (' . $row['code_iso'] . ')';

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
	function multiselectselectSalesRepresentatives($htmlname, $selected_array, $user) {

		global $conf;

		$options_array = array ();

        $sql_usr  = '';
		$sql_usr .= "SELECT DISTINCT u2.rowid, u2.lastname as name, u2.firstname, u2.login";
		$sql_usr .= " FROM " . MAIN_DB_PREFIX . "user as u2, " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
		$sql_usr .= " WHERE u2.entity IN (0," . $conf->entity . ")";
		$sql_usr .= " AND u2.rowid = sc.fk_user ";

		if (! empty ( $conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX ))
			$sql_usr .= " AND u2.statut<>0 ";
		$sql_usr .= " ORDER BY name ASC";
		// print $sql_usr;exit;

		$resql_usr = $this->db->query ( $sql_usr );
		if ($resql_usr) {
			while ( $obj_usr = $this->db->fetch_object ( $resql_usr ) ) {

				$label = $obj_usr->firstname . " " . $obj_usr->name . " (" . $obj_usr->login . ')';

				$options_array [$obj_usr->rowid] = $label;

			}
			$this->db->free ( $resql_usr );
		} else {
			dol_print_error ( $this->db );
		}

		return $this->advMultiselectarray ( $htmlname, $options_array, $selected_array );
	}

	/**
	 * Return select list for categories (to use in form search selectors)
	 *
	 * @param string $htmlname of combo list (example: 'search_sale')
	 * @param array $selected_array selected array
	 * @return string combo list code
	 */
	function multiselectselectLanguage($htmlname='', $selected_array=array()) {

		global $conf,$langs;

		$options_array = array ();

		$langs_available=$langs->get_available_languages(DOL_DOCUMENT_ROOT,12);

		foreach ($langs_available as $key => $value)
		{
			$label = $value;
			$options_array[$key] = $label;
		}
		asort($options_array);
		return $this->advMultiselectarray($htmlname, $options_array, $selected_array);
	}

	/**
	 * Return multiselect list of entities for extrafeild type sellist
	 *
	 * @param string $htmlname control name
	 * @param array $sqlqueryparam array
	 * @param array $selected_array array
	 *
	 *  @return	string HTML combo
	 */
	function advMultiselectarraySelllist($htmlname, $sqlqueryparam = array(), $selected_array = array())
	{
		$options_array=array();

		if (is_array($sqlqueryparam))
		{
			$param_list = array_keys ( $sqlqueryparam );
			$InfoFieldList = explode ( ":", $param_list [0] );

			// 0 1 : tableName
			// 1 2 : label field name Nom du champ contenant le libelle
			// 2 3 : key fields name (if differ of rowid)
			// 3 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value

			$keyList = 'rowid';

			if (count ( $InfoFieldList ) >= 3) {
				if (strpos ( $InfoFieldList [3], 'extra.' ) !== false) {
					$keyList = 'main.' . $InfoFieldList [2] . ' as rowid';
				} else {
					$keyList = $InfoFieldList [2] . ' as rowid';
				}
			}

			$sql = 'SELECT ' . $keyList . ', ' . $InfoFieldList [1];
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList [0];
			if (! empty ( $InfoFieldList [3] )) {

				// We have to join on extrafield table
				if (strpos ( $InfoFieldList [3], 'extra' ) !== false) {
					$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList [0] . '_extrafields as extra';
					$sql .= ' WHERE  extra.fk_object=main.' . $InfoFieldList [2] . ' AND ' . $InfoFieldList [3];
				} else {
					$sql .= ' WHERE ' . $InfoFieldList [3];
				}
			}
			if (! empty($InfoFieldList[1])) {
				$sql .= " ORDER BY nom";
			}
			// $sql.= ' WHERE entity = '.$conf->entity;

			dol_syslog(get_class($this) . "::".__METHOD__,LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {

				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num) {
					while ( $i < $num ) {
						$obj = $this->db->fetch_object ( $resql );
						$labeltoshow = dol_trunc ( $obj->$InfoFieldList [1], 90 );
						$options_array[$obj->rowid]=$labeltoshow;
						$i ++;
					}
				}
				$this->db->free ( $resql );
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
	function multiselectCivility($htmlname='civilite_id',$selected_array = array())
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$options_array=array();


		$sql = "SELECT rowid, code, label as civilite, active FROM ".MAIN_DB_PREFIX."c_civility";
		$sql.= " WHERE active = 1";

		dol_syslog(get_class($this)."::".__METHOD__,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$label= ($langs->trans("Civility".$obj->code)!="Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->civilite!='-'?$obj->civilite:''));


					$options_array[$obj->code]=$label;

					$i++;
				}
			}

		}
		else
		{
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray ( $htmlname, $options_array, $selected_array );
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
	function advMultiselectarray($htmlname, $options_array = array(), $selected_array = array(), $showempty = 0) {
		global $conf, $langs;

		$form=new Form($this->db);
		$return = $form->multiselectarray($htmlname, $options_array, $selected_array,0,0,'',0,295);
		return $return;
	}

	/**
	 *  Return combo list with customer categories
	 *
	 *  @param  string	$htmlname   Name of categorie
	 * 	@param	array	$selected_array	value selected
	 *  @return	string HTML combo
	 */
	function multiselectCustomerCategories($htmlname='cust_cat',$selected_array = array())
	{
		return $this->multiselectCategories($htmlname,$selected_array,2);
	}

	/**
	 *  Return combo list with customer contact
	 *
	 *  @param  string	$htmlname   Name of categorie
	 * 	@param	array	$selected_array	value selected
	 *  @return	string HTML combo
	 */
	function multiselectContactCategories($htmlname='contact_cat',$selected_array = array())
	{
		return $this->multiselectCategories($htmlname,$selected_array,4);
	}

	/**
	 *  Return combo list of categories
	 *
	 *  @param  string	$htmlname   Name of categorie
	 * 	@param	array	$selected_array	value selected
	 * 	@param	int	$type	type
	 *  @return	string HTML combo
	 */
	public function multiselectCategories($htmlname='',$selected_array = array(), $type=0)
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$options_array=array();

		$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type=".$type;

		dol_syslog(get_class($this)."::".__METHOD__,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$options_array[$obj->rowid]=$obj->label;

					$i++;
				}
			}

		}
		else
		{
			dol_print_error($this->db);
		}

		return $this->advMultiselectarray( $htmlname, $options_array, $selected_array );
	}

	/**
	 * selectAdvtargetemailingTemplate
	 *
	 * @param string $htmlname control name
	 * @param integer $selected  defaut selected
	 * @param integer $showempty empty lines
	 *
	 * @return	string HTML combo
	 */
	public function selectAdvtargetemailingTemplate($htmlname='template_id',$selected=0,$showempty=0) {
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT c.rowid, c.name, c.fk_mailing";
		$sql .= " FROM " . MAIN_DB_PREFIX . "advtargetemailing as c";
		$sql .= " ORDER BY c.name";

		dol_syslog ( get_class ( $this ) . "::".__METHOD__, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {


			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value=""></option>';
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
					$label = $obj->name;
					if (empty($label)) {
						$label=$obj->fk_mailing;
					}

					if ($selected > 0 && $selected == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error ( $this->db );
		}
		$this->db->free ( $resql );
		return $out;
	}
}