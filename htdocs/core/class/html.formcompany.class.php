<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/class/html.formcompany.class.php
 *  \ingroup    core
 *	\brief      File of class to build HTML component for third parties management
 */


/**
 *	Class to build HTML component for third parties management
 *	Only common components are here.
 */
class FormCompany
{
	var $db;
	var $error;



	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		return 1;
	}


	/**
	 *    	Return list of labels (translated) of third parties type
	 *
	 *		@param	int		$mode		0=Return id+label, 1=Return code+label
	 *      @param  string	$filter     Add a SQL filter to select
	 *    	@return array      			Array of types
	 */
	function typent_array($mode=0, $filter='')
	{
		global $langs;

		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_typent";
		$sql.= " WHERE active = 1";
		if ($filter) $sql.=" ".$filter;
		$sql.= " ORDER by id";
		dol_syslog(get_class($this).'::typent_array sql='.$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				if (! $mode) $key=$objp->id;
				else $key=$objp->code;
				if ($langs->trans($objp->code) != $objp->code) $effs[$key] = $langs->trans($objp->code);
				else $effs[$key] = $objp->libelle;
				if ($effs[$key]=='-') $effs[$key]='';
				$i++;
			}
			$this->db->free($resql);
		}

		return $effs;
	}

	/**
	 *	Renvoie la liste des types d'effectifs possibles (pas de traduction car nombre)
	 *
	 *	@param	int		$mode		0=renvoi id+libelle, 1=renvoi code+libelle
	 *  @return array				Array of types d'effectifs
	 */
	function effectif_array($mode=0)
	{
		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_effectif";
		$sql.= " WHERE active = 1";
		$sql .= " ORDER BY id ASC";
		dol_syslog(get_class($this).'::effectif_array sql='.$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				if (! $mode) $key=$objp->id;
				else $key=$objp->code;

				$effs[$key] = $objp->libelle!='-'?$objp->libelle:'';
				$i++;
			}
			$this->db->free($resql);
		}
		return $effs;
	}


	/**
	 *  Affiche formulaire de selection des modes de reglement
	 *
	 *  @param	int		$page        	Page
	 *  @param  int		$selected    	Id or code preselected
	 *  @param  string	$htmlname   	Nom du formulaire select
	 *	@param	int		$empty			Add empty value in list
	 *	@return	void
	 */
	function form_prospect_level($page, $selected='', $htmlname='prospect_level_id', $empty=0)
	{
		global $langs;

		print '<form method="post" action="'.$page.'">';
		print '<input type="hidden" name="action" value="setprospectlevel">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		print '<tr><td>';

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';

		dol_syslog(get_class($this).'::form_prospect_level',LOG_DEBUG);
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				print '<option value="'.$obj->code.'"';
				if ($selected == $obj->code) print ' selected="selected"';
				print '>';
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				print $level;
				print '</option>';

				$i++;
			}
		}
		else dol_print_error($this->db);
		print '</select>';

		print '</td>';
		print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr></table></form>';
	}

	/**
	 *   Retourne la liste deroulante des departements/province/cantons tout pays confondu ou pour un pays donne.
	 *   Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
	 *   La cle de la liste est le code (il peut y avoir plusieurs entree pour
	 *   un code donnee mais dans ce cas, le champ pays differe).
	 *   Ainsi les liens avec les departements se font sur un departement independemment de son nom.
	 *
	 *   @param     string	$selected        	Code state preselected
	 *   @param     int		$country_codeid     0=list for all countries, otherwise country code or country rowid to show
	 *   @param     string	$htmlname			Id of department
	 *   @return	void
	 */
	function select_departement($selected='',$country_codeid=0, $htmlname='state_id')
	{
		print $this->select_state($selected,$country_codeid, $htmlname);
	}

	/**
	 *    Retourne la liste deroulante des departements/province/cantons tout pays confondu ou pour un pays donne.
	 *    Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
	 *    La cle de la liste est le code (il peut y avoir plusieurs entree pour
	 *    un code donnee mais dans ce cas, le champ pays differe).
	 *    Ainsi les liens avec les departements se font sur un departement independemment de son nom.
	 *
	 *    @param	string	$selected        	Code state preselected (mus be state id) 
	 *    @param    string	$country_codeid    	Country code or id: 0=list for all countries, otherwise country code or country rowid to show
	 *    @param    string	$htmlname			Id of department
	 * 	  @return	string						String with HTML select
	 */
	function select_state($selected='',$country_codeid=0, $htmlname='state_id')
	{
		global $conf,$langs,$user;

		dol_syslog(get_class($this)."::select_departement selected=".$selected.", country_codeid=".$country_codeid,LOG_DEBUG);

		$langs->load("dict");

		$out='';

		// On recherche les departements/cantons/province active d'une region et pays actif
		$sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as country, p.code as country_code FROM";
		$sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1";
		if ($country_codeid && is_numeric($country_codeid))   $sql .= " AND p.rowid = '".$country_codeid."'";
		if ($country_codeid && ! is_numeric($country_codeid)) $sql .= " AND p.code = '".$country_codeid."'";
		$sql .= " ORDER BY p.code, d.code_departement";

		dol_syslog(get_class($this)."::select_departement sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if (!empty($htmlname)) $out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($country_codeid) $out.= '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			dol_syslog(get_class($this)."::select_departement num=".$num,LOG_DEBUG);
			if ($num)
			{
				$country='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					if ($obj->code == '0')		// Le code peut etre une chaine
					{
						$out.= '<option value="0">&nbsp;</option>';
					}
					else {
						if (! $country || $country != $obj->country)
						{
							// Affiche la rupture si on est en mode liste multipays
							if (! $country_codeid && $obj->country_code)
							{
								$out.= '<option value="-1" disabled="disabled">----- '.$obj->country." -----</option>\n";
								$country=$obj->country;
							}
						}

						if ((! empty($selected) && $selected == $obj->rowid)
						 || (empty($selected) && ! empty($conf->global->MAIN_FORCE_DEFAULT_STATE_ID) && $conf->global->MAIN_FORCE_DEFAULT_STATE_ID == $obj->rowid))
						{
							$out.= '<option value="'.$obj->rowid.'" selected="selected">';
						}
						else
						{
							$out.= '<option value="'.$obj->rowid.'">';
						}
						// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
						$out.= $obj->code . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
						$out.= '</option>';
					}
					$i++;
				}
			}
			if (! empty($htmlname)) $out.= '</select>';
			if (! empty($htmlname) && $user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}


	/**
	 *   Retourne la liste deroulante des regions actives dont le pays est actif
	 *   La cle de la liste est le code (il peut y avoir plusieurs entree pour
	 *   un code donnee mais dans ce cas, le champ pays et lang differe).
	 *   Ainsi les liens avec les regions se font sur une region independemment de son nom.
	 *
	 *   @param		string		$selected		Preselected value
	 *   @param		string		$htmlname		Name of HTML select field
	 *   @return	void
	 */
	function select_region($selected='',$htmlname='region_id')
	{
		global $conf,$langs;
		$langs->load("dict");

		$sql = "SELECT r.rowid, r.code_region as code, r.nom as libelle, r.active, p.code as country_code, p.libelle as country FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p";
		$sql.= " WHERE r.fk_pays=p.rowid AND r.active = 1 and p.active = 1";
		$sql.= " ORDER BY p.code, p.libelle ASC";

		dol_syslog(get_class($this)."::select_region sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				$country='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj->code == 0) {
						print '<option value="0">&nbsp;</option>';
					}
					else {
						if ($country == '' || $country != $obj->country)
						{
							// Show break
							$key=$langs->trans("Country".strtoupper($obj->country_code));
							$valuetoshow=($key != "Country".strtoupper($obj->country_code))?$obj->country_code." - ".$key:$obj->country;
							print '<option value="-1" disabled="disabled">----- '.$valuetoshow." -----</option>\n";
							$country=$obj->country;
						}

						if ($selected > 0 && $selected == $obj->code)
						{
							print '<option value="'.$obj->code.'" selected="selected">'.$obj->libelle.'</option>';
						}
						else
						{
							print '<option value="'.$obj->code.'">'.$obj->libelle.'</option>';
						}
					}
					$i++;
				}
			}
			print '</select>';
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return combo list with people title
	 *
	 *  @param  string	$selected   	Title preselected
	 * 	@param	string	$htmlname		Name of HTML select combo field
	 *  @return	string					String with HTML select
	 */
	function select_civility($selected='',$htmlname='civilite_id')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$out='';

		$sql = "SELECT rowid, code, civilite, active FROM ".MAIN_DB_PREFIX."c_civilite";
		$sql.= " WHERE active = 1";

		dol_syslog("Form::select_civility sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$out.= '<select class="flat" name="'.$htmlname.'">';
			$out.= '<option value="">&nbsp;</option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($selected == $obj->code)
					{
						$out.= '<option value="'.$obj->code.'" selected="selected">';
					}
					else
					{
						$out.= '<option value="'.$obj->code.'">';
					}
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$out.= ($langs->trans("Civility".$obj->code)!="Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->civilite!='-'?$obj->civilite:''));
					$out.= '</option>';
					$i++;
				}
			}
			$out.= '</select>';
			if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}

	/**
	 *    Retourne la liste deroulante des formes juridiques tous pays confondus ou pour un pays donne.
	 *    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays.
	 *
	 *    @param	string		$selected        	Code forme juridique a pre-selectionne
	 *    @param    mixed		$country_codeid		0=liste tous pays confondus, sinon code du pays a afficher
	 *    @param    string		$filter          	Add a SQL filter on list
	 *    @return	void
	 */
	function select_forme_juridique($selected='', $country_codeid=0, $filter='')
	{
		print $this->select_juridicalstatus($selected, $country_codeid, $filter);
	}

	/**
	 *    Retourne la liste deroulante des formes juridiques tous pays confondus ou pour un pays donne.
	 *    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
	 *
	 *    @param	string		$selected        	Code forme juridique a pre-selectionne
	 *    @param    int			$country_codeid     0=liste tous pays confondus, sinon code du pays a afficher
     *    @param    string		$filter          	Add a SQL filter on list
     *    @return	string							String with HTML select
	 */
	function select_juridicalstatus($selected='', $country_codeid=0, $filter='')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$out='';

		// On recherche les formes juridiques actives des pays actifs
		$sql  = "SELECT f.rowid, f.code as code , f.libelle as nom, f.active, p.libelle as country, p.code as country_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE f.fk_pays=p.rowid";
		$sql .= " AND f.active = 1 AND p.active = 1";
		if ($country_codeid) $sql .= " AND p.code = '".$country_codeid."'";
		if ($filter) $sql .= " ".$filter;
		$sql .= " ORDER BY p.code, f.code";

		dol_syslog("Form::select_forme_juridique sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$out.= '<div id="particulier2" class="visible">';
			$out.= '<select class="flat" name="forme_juridique_code">';
			if ($country_codeid) $out.= '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				$country='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					if ($obj->code == 0) {
						$out.= '<option value="0">&nbsp;</option>';
					}
					else {
						if (! $country || $country != $obj->country) {
							// Affiche la rupture si on est en mode liste multipays
							if (! $country_codeid && $obj->country_code) {
								$out.= '<option value="0">----- '.$obj->country." -----</option>\n";
								$country=$obj->country;
							}
						}

						if ($selected > 0 && $selected == $obj->code)
						{
							$out.= '<option value="'.$obj->code.'" selected="selected">';
						}
						else
						{
							$out.= '<option value="'.$obj->code.'">';
						}
						// Si translation exists, we use it, otherwise we use default label in database
						$out.= $obj->code . ' - ';
						$out.= ($langs->trans("JuridicalStatus".$obj->code)!="JuridicalStatus".$obj->code?$langs->trans("JuridicalStatus".$obj->code):($obj->nom!='-'?$obj->nom:''));	// $obj->nom is alreay in output charset (converted by database driver)
						$out.= '</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			$out.= '</div>';
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}


	/**
	 *    Return list of third parties
	 *
	 *  @param  Object		$object         Object we try to find contacts
	 *  @param  string		$var_id         Name of id field
	 *  @param  string		$selected       Pre-selected third party
	 *  @param  string		$htmlname       Name of HTML form
	 * 	@param	array		$limitto		Disable answers that are not id in this array list
	 *  @param	int			$forceid		This is to force another object id than object->id
	 * 	@return	void
	 * 	TODO obsolete ?
	 * 	cette fonction doit utiliser du javascript quoi qu'il en soit !
	 * 	autant utiliser le système combobox sans rechargement de page non ?
	 */
	function selectCompaniesForNewContact($object, $var_id, $selected='', $htmlname='newcompany', $limitto='', $forceid=0)
	{
		global $conf, $langs;

		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom FROM";
		$sql.= " ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
		if ($selected && $conf->use_javascript_ajax && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) $sql.= " AND rowid = ".$selected;
		else
		{
			// For ajax search we limit here. For combo list, we limit later
			if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT
			&& is_array($limitto) && count($limitto))
			{
				$sql.= " AND rowid IN (".join(',',$limitto).")";
			}
		}
		$sql.= " ORDER BY nom ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($conf->use_javascript_ajax && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT))
			{
				$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

				$socid=0;
				if ($selected)
				{
					$obj = $this->db->fetch_object($resql);
					$socid = $obj->rowid?$obj->rowid:'';
				}

				// We call a page after a small delay when a new input has been selected
				$javaScript = "window.location=\'".$_SERVER['PHP_SELF']."?".$var_id."=".($forceid>0?$forceid:$object->id)."&amp;".$htmlname."=\' + document.getElementById(\'".$htmlname."\').value;";
                $htmloption = 'onChange="ac_delay(\''.$javaScript.'\',\'500\');"';                              // When we select with mouse
				$htmloption.= 'onKeyUp="if (event.keyCode== 13) { ac_delay(\''.$javaScript.'\',\'500\'); }"';   // When we select with keyboard

				print "\n".'<!-- Input text for third party with Ajax.Autocompleter (selectCompaniesForNewContact) -->'."\n";
				print '<table class="nobordernopadding"><tr class="nobordernopadding">';
				print '<td class="nobordernopadding">';
				if ($obj->rowid == 0)
				{
					//$langs->load("companies");
					//print '<input type="text" size="30" id="'.$htmlname.'_label" name="'.$htmlname.'" value="'.$langs->trans("SelectCompany").'" '.$htmloption.' />';
					print '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="" '.$htmloption.' />';
				}
				else
				{
					print '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$obj->nom.'" '.$htmloption.' />';
				}
				print ajax_autocompleter(($socid?$socid:-1),$htmlname,DOL_URL_ROOT.'/societe/ajaxcompanies.php','',$minLength);
				print '</td>';
				print '</tr>';
				print '</table>';
				print "\n";
				return $socid;
			}
			else
			{
				$javaScript = "window.location='".$_SERVER['PHP_SELF']."?".$var_id."=".($forceid>0?$forceid:$object->id)."&amp;".$htmlname."=' + form.".$htmlname.".options[form.".$htmlname.".selectedIndex].value;";
				print '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" onChange="'.$javaScript.'">';
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						if ($i == 0) $firstCompany = $obj->rowid;
						$disabled=0;
						if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid,$limitto)) $disabled=1;
						if ($selected > 0 && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'"';
							if ($disabled) print ' disabled="disabled"';
							print ' selected="selected">'.dol_trunc($obj->nom,24).'</option>';
							$firstCompany = $obj->rowid;
						}
						else
						{
							print '<option value="'.$obj->rowid.'"';
							if ($disabled) print ' disabled="disabled"';
							print '>'.dol_trunc($obj->nom,24).'</option>';
						}
						$i ++;
					}
				}
				print "</select>\n";
				return $firstCompany;
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     *  Return a select list with types of contacts
     *
     *  @param	Object		$object         Object to use to find type of contact
     *  @param  string		$selected       Default selected value
     *  @param  string		$htmlname		HTML select name
     *  @param  string		$source			Source ('internal' or 'external')
     *  @param  string		$order			Sort criteria
     *  @param  int			$showempty      1=Add en empty line
     *  @return	void
     */
	function selectTypeContact($object, $selected, $htmlname = 'type', $source='internal', $order='code', $showempty=0)
	{
		if (is_object($object) && method_exists($object, 'liste_type_contact'))
		{
			$lesTypes = $object->liste_type_contact($source, $order, 0, 1);
			print '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($showempty) print '<option value="0"></option>';
			foreach($lesTypes as $key=>$value)
			{
				print '<option value="'.$key.'"';
				if ($key == $selected)
					print ' selected';
				print '>'.$value.'</option>';
			}
			print "</select>\n";
		}
	}

	/**
	 *    Return a select list with zip codes and their town
	 *
	 *    @param	string		$selected				Preselected value
	 *    @param    string		$htmlname				HTML select name
	 *    @param    string		$fields					Fields
	 *    @param    int			$fieldsize				Field size
	 *    @param    int			$disableautocomplete    1 To disable autocomplete features
	 *    @return	void
	 */
	function select_ziptown($selected='', $htmlname='zipcode', $fields='', $fieldsize=0, $disableautocomplete=0)
	{
		global $conf;

		$out='';

		$size='';
		if (!empty($fieldsize)) $size='size="'.$fieldsize.'"';

		if ($conf->use_javascript_ajax && empty($disableautocomplete))	$out.= ajax_multiautocompleter($htmlname,$fields,DOL_URL_ROOT.'/core/ajax/ziptown.php')."\n";
		$out.= '<input id="'.$htmlname.'" type="text" name="'.$htmlname.'" '.$size.' value="'.$selected.'">'."\n";

		return $out;
	}

    /**
     *  Return HTML string to use as input of professional id into a HTML page (siren, siret, etc...)
     *
     *  @param	int		$idprof         1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
     *  @param  string	$htmlname       Name of HTML select
     *  @param  string	$preselected    Default value to show
     *  @param  string	$country_code   FR, IT, ...
     *  @return	string					HTML string with prof id
     */
    function get_input_id_prof($idprof,$htmlname,$preselected,$country_code)
    {
        global $conf,$langs;

        $formlength=0;
        if (empty($conf->global->MAIN_DISABLEPROFIDRULES)) {
        	if ($country_code == 'FR')
        	{
        		if (isset($idprof)) {
        			if ($idprof==1) $formlength=9;
        			else if ($idprof==2) $formlength=14;
        			else if ($idprof==3) $formlength=5;      // 4 chiffres et 1 lettre depuis janvier
        			else if ($idprof==4) $formlength=32;     // No maximum as we need to include a town name in this id
        		}
        	}
        	else if ($country_code == 'ES')
        	{
        		if ($idprof==1) $formlength=9;  //CIF/NIF/NIE 9 digits
        		if ($idprof==2) $formlength=12; //NASS 12 digits without /
        		if ($idprof==3) $formlength=5;  //CNAE 5 digits
        		if ($idprof==4) $formlength=32; //depend of college
        	}
        }

        $selected=$preselected;
        if (! $selected && isset($idprof)) {
        	if ($idprof==1 && ! empty($this->idprof1)) $selected=$this->idprof1;
        	else if ($idprof==2 && ! empty($this->idprof2)) $selected=$this->idprof2;
        	else if ($idprof==3 && ! empty($this->idprof3)) $selected=$this->idprof3;
        	else if ($idprof==4 && ! empty($this->idprof4)) $selected=$this->idprof4;
        }

        $maxlength=$formlength;
        if (empty($formlength)) { $formlength=24; $maxlength=128; }

        $out = '<input type="text" name="'.$htmlname.'" size="'.($formlength+1).'" maxlength="'.$maxlength.'" value="'.$selected.'">';

        return $out;
    }

}

?>
