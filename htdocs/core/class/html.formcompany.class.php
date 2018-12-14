<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Rui Strecht			<rui.strecht@aliartalentos.com>
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
	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    	Return list of labels (translated) of third parties type
	 *
	 *		@param	int		$mode		0=Return id+label, 1=Return code+label
	 *      @param  string	$filter     Add a SQL filter to select
	 *    	@return array      			Array of types
	 */
	function typent_array($mode=0, $filter='')
	{
        // phpcs:enable
		global $langs,$mysoc;

		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_typent";
		$sql.= " WHERE active = 1 AND (fk_country IS NULL OR fk_country = ".(empty($mysoc->country_id)?'0':$mysoc->country_id).")";
		if ($filter) $sql.=" ".$filter;
		$sql.= " ORDER by position, id";
		dol_syslog(get_class($this).'::typent_array', LOG_DEBUG);
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Renvoie la liste des types d'effectifs possibles (pas de traduction car nombre)
	 *
	 *	@param	int		$mode		0=renvoi id+libelle, 1=renvoi code+libelle
	 *	@param  string	$filter     Add a SQL filter to select
	 *  @return array				Array of types d'effectifs
	 */
	function effectif_array($mode=0, $filter='')
	{
        // phpcs:enable
		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_effectif";
		$sql.= " WHERE active = 1";
		if ($filter) $sql.=" ".$filter;
		$sql .= " ORDER BY id ASC";
		dol_syslog(get_class($this).'::effectif_array', LOG_DEBUG);
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		global $user, $langs;

		print '<form method="post" action="'.$page.'">';
		print '<input type="hidden" name="action" value="setprospectlevel">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		dol_syslog(get_class($this).'::form_prospect_level',LOG_DEBUG);
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		$resql = $this->db->query($sql);
		if ($resql)
		{
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
		}
		else dol_print_error($this->db);
		if (! empty($htmlname) && $user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		print '</form>';
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
		print $this->select_state($selected,$country_codeid, $htmlname);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Retourne la liste deroulante des departements/province/cantons tout pays confondu ou pour un pays donne.
	 *    Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
	 *    La cle de la liste est le code (il peut y avoir plusieurs entree pour
	 *    un code donnee mais dans ce cas, le champ pays differe).
	 *    Ainsi les liens avec les departements se font sur un departement independemment de son nom.
	 *
	 *    @param	string	$selected        	Code state preselected (mus be state id)
	 *    @param    integer	$country_codeid    	Country code or id: 0=list for all countries, otherwise country code or country rowid to show
	 *    @param    string	$htmlname			Id of department. If '', we want only the string with <option>
	 * 	  @return	string						String with HTML select
	 *    @see select_country
	 */
	function select_state($selected='',$country_codeid=0, $htmlname='state_id')
	{
        // phpcs:enable
		global $conf,$langs,$user;

		dol_syslog(get_class($this)."::select_departement selected=".$selected.", country_codeid=".$country_codeid,LOG_DEBUG);

		$langs->load("dict");

		$out='';

		// Serch departements/cantons/province active d'une region et pays actif
		$sql = "SELECT d.rowid, d.code_departement as code, d.nom as name, d.active, c.label as country, c.code as country_code, r.nom as region_name FROM";
		$sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND c.active = 1";
		if ($country_codeid && is_numeric($country_codeid))   $sql .= " AND c.rowid = '".$this->db->escape($country_codeid)."'";
		if ($country_codeid && ! is_numeric($country_codeid)) $sql .= " AND c.code = '".$this->db->escape($country_codeid)."'";
		$sql .= " ORDER BY c.code, d.code_departement";

		$result=$this->db->query($sql);
		if ($result)
		{
			if (!empty($htmlname)) $out.= '<select id="'.$htmlname.'" class="flat maxwidth200onsmartphone minwidth300" name="'.$htmlname.'">';
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
								$out.= '<option value="-1" disabled>----- '.$obj->country." -----</option>\n";
								$country=$obj->country;
							}
						}

						if ((! empty($selected) && $selected == $obj->rowid)
						 || (empty($selected) && ! empty($conf->global->MAIN_FORCE_DEFAULT_STATE_ID) && $conf->global->MAIN_FORCE_DEFAULT_STATE_ID == $obj->rowid))
						{
							$out.= '<option value="'.$obj->rowid.'" selected>';
						}
						else
						{
							$out.= '<option value="'.$obj->rowid.'">';
						}

						// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
						if (!empty($conf->global->MAIN_SHOW_STATE_CODE) &&
						($conf->global->MAIN_SHOW_STATE_CODE == 1 || $conf->global->MAIN_SHOW_STATE_CODE == 2 || $conf->global->MAIN_SHOW_STATE_CODE === 'all')) {
							if(!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1) {
								$out.= $obj->region_name . ' - ' . $obj->code . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->name!='-'?$obj->name:''));
							}
							else {
								$out.= $obj->code . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->name!='-'?$obj->name:''));
							}
						}
						else {
							if(!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1) {
								$out.= $obj->region_name . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->name!='-'?$obj->name:''));
							}
							else {
								$out.= ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->name!='-'?$obj->name:''));
							}
						}

						$out.= '</option>';
					}
					$i++;
				}
			}
			if (! empty($htmlname)) $out.= '</select>';
			if (! empty($htmlname) && $user->admin) $out.= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}

		// Make select dynamic
		if (! empty($htmlname))
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname);
		}

		return $out;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *   Retourne la liste deroulante des regions actives dont le pays est actif
	 *   La cle de la liste est le code (il peut y avoir plusieurs entree pour
	 *   un code donnee mais dans ce cas, le champ pays et lang differe).
	 *   Ainsi les liens avec les regions se font sur une region independemment de son name.
	 *
	 *   @param		string		$selected		Preselected value
	 *   @param		string		$htmlname		Name of HTML select field
	 *   @return	void
	 */
	function select_region($selected='',$htmlname='region_id')
	{
        // phpcs:enable
		global $conf,$langs;
		$langs->load("dict");

		$sql = "SELECT r.rowid, r.code_region as code, r.nom as label, r.active, c.code as country_code, c.label as country";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_country as c";
		$sql.= " WHERE r.fk_pays=c.rowid AND r.active = 1 and c.active = 1";
		$sql.= " ORDER BY c.code, c.label ASC";

		dol_syslog(get_class($this)."::select_region", LOG_DEBUG);
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
							print '<option value="-1" disabled>----- '.$valuetoshow." -----</option>\n";
							$country=$obj->country;
						}

						if ($selected > 0 && $selected == $obj->code)
						{
							print '<option value="'.$obj->code.'" selected>'.$obj->label.'</option>';
						}
						else
						{
							print '<option value="'.$obj->code.'">'.$obj->label.'</option>';
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return combo list with people title
	 *
	 *  @param  string	$selected   	Title preselected
	 * 	@param	string	$htmlname		Name of HTML select combo field
	 *  @param  string  $morecss        Add more css on SELECT element
	 *  @return	string					String with HTML select
	 */
	function select_civility($selected='',$htmlname='civility_id',$morecss='maxwidth100')
	{
        // phpcs:enable
		global $conf,$langs,$user;
		$langs->load("dict");

		$out='';

		$sql = "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_civility";
		$sql.= " WHERE active = 1";

		dol_syslog("Form::select_civility", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$out.= '<select class="flat'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" id="'.$htmlname.'">';
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
						$out.= '<option value="'.$obj->code.'" selected>';
					}
					else
					{
						$out.= '<option value="'.$obj->code.'">';
					}
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$out.= ($langs->trans("Civility".$obj->code)!="Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->label!='-'?$obj->label:''));
					$out.= '</option>';
					$i++;
				}
			}
			$out.= '</select>';
			if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Retourne la liste deroulante des formes juridiques tous pays confondus ou pour un pays donne.
	 *    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays.
	 *
	 *    @param	string		$selected        	Code forme juridique a pre-selectionne
	 *    @param    mixed		$country_codeid		0=liste tous pays confondus, sinon code du pays a afficher
	 *    @param    string		$filter          	Add a SQL filter on list
	 *    @return	void
	 *    @deprecated Use print xxx->select_juridicalstatus instead
	 *    @see select_juridicalstatus()
	 */
	function select_forme_juridique($selected='', $country_codeid=0, $filter='')
	{
        // phpcs:enable
		print $this->select_juridicalstatus($selected, $country_codeid, $filter);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Retourne la liste deroulante des formes juridiques tous pays confondus ou pour un pays donne.
	 *    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
	 *
	 *    @param	string		$selected        	Preselected code of juridical type
	 *    @param    int			$country_codeid     0=list for all countries, otherwise list only country requested
     *    @param    string		$filter          	Add a SQL filter on list
     *    @param	string		$htmlname			HTML name of select
     *    @return	string							String with HTML select
	 */
	function select_juridicalstatus($selected='', $country_codeid=0, $filter='', $htmlname='forme_juridique_code')
	{
        // phpcs:enable
		global $conf,$langs,$user;
		$langs->load("dict");

		$out='';

		// On recherche les formes juridiques actives des pays actifs
		$sql  = "SELECT f.rowid, f.code as code , f.libelle as label, f.active, c.label as country, c.code as country_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE f.fk_pays=c.rowid";
		$sql .= " AND f.active = 1 AND c.active = 1";
		if ($country_codeid) $sql .= " AND c.code = '".$country_codeid."'";
		if ($filter) $sql .= " ".$filter;
		$sql .= " ORDER BY c.code";

		dol_syslog(get_class($this)."::select_juridicalstatus", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$out.= '<div id="particulier2" class="visible">';
			$out.= '<select class="flat minwidth200" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($country_codeid) $out.= '<option value="0">&nbsp;</option>';	// When country_codeid is set, we force to add an empty line because it does not appears from select. When not set, we already get the empty line from select.

			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$i = 0;
				$country=''; $arraydata=array();
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					if ($obj->code)		// We exclude empty line, we will add it later
					{
						$labelcountry=(($langs->trans("Country".$obj->country_code)!="Country".$obj->country_code) ? $langs->trans("Country".$obj->country_code) : $obj->country);
						$labeljs=(($langs->trans("JuridicalStatus".$obj->code)!="JuridicalStatus".$obj->code) ? $langs->trans("JuridicalStatus".$obj->code) : ($obj->label!='-'?$obj->label:''));	// $obj->label is already in output charset (converted by database driver)
						$arraydata[$obj->code]=array('code'=>$obj->code, 'label'=>$labeljs, 'label_sort'=>$labelcountry.'_'.$labeljs, 'country_code'=>$obj->country_code, 'country'=>$labelcountry);
					}
					$i++;
				}

				$arraydata=dol_sort_array($arraydata, 'label_sort', 'ASC');
				if (empty($country_codeid))	// Introduce empty value (if $country_codeid not empty, empty value was already added)
				{
					$arraydata[0]=array('code'=>0, 'label'=>'', 'label_sort'=>'_', 'country_code'=>'', 'country'=>'');
				}

				foreach($arraydata as $key => $val)
				{
					if (! $country || $country != $val['country'])
					{
						// Show break when we are in multi country mode
						if (empty($country_codeid) && $val['country_code'])
						{
							$out.= '<option value="0" disabled class="selectoptiondisabledwhite">----- '.$val['country']." -----</option>\n";
							$country=$val['country'];
						}
					}

					if ($selected > 0 && $selected == $val['code'])
					{
						$out.= '<option value="'.$val['code'].'" selected>';
					}
					else
					{
						$out.= '<option value="'.$val['code'].'">';
					}
					// If translation exists, we use it, otherwise we use default label in database
					$out.= $val['label'];
					$out.= '</option>';
				}
			}
			$out.= '</select>';
			if ($user->admin) $out.= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

		    // Make select dynamic
        	include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	        $out .= ajax_combobox($htmlname);

			$out.= '</div>';
		}
		else
		{
			dol_print_error($this->db);
		}

		return $out;
	}


	/**
	 *  Output list of third parties.
	 *
	 *  @param  object		$object         Object we try to find contacts
	 *  @param  string		$var_id         Name of id field
	 *  @param  string		$selected       Pre-selected third party
	 *  @param  string		$htmlname       Name of HTML form
	 * 	@param	array		$limitto		Disable answers that are not id in this array list
	 *  @param	int			$forceid		This is to force another object id than object->id
     *  @param	string		$moreparam		String with more param to add into url when noajax search is used.
     *  @param	string		$morecss		More CSS on select component
	 * 	@return int 						The selected third party ID
	 */
	function selectCompaniesForNewContact($object, $var_id, $selected='', $htmlname='newcompany', $limitto='', $forceid=0, $moreparam='', $morecss='')
	{
		global $conf, $langs;

		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT))
		{
			// Use Ajax search
			$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

			$socid=0; $name='';
			if ($selected > 0)
			{
				$tmpthirdparty=new Societe($this->db);
				$result = $tmpthirdparty->fetch($selected);
				if ($result > 0)
				{
					$socid = $selected;
					$name = $tmpthirdparty->name;
				}
			}


			$events=array();
			// Add an entry 'method' to say 'yes, we must execute url with param action = method';
			// Add an entry 'url' to say which url to execute
			// Add an entry htmlname to say which element we must change once url is called
			// Add entry params => array('cssid' => 'attr') to say to remov or add attribute attr if answer of url return  0 or >0 lines
			// To refresh contacts list on thirdparty list change
			$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));

			if (count($events))	// If there is some ajax events to run once selection is done, we add code here to run events
			{
				print '<script type="text/javascript">
				jQuery(document).ready(function() {
					$("#search_'.$htmlname.'").change(function() {
						var obj = '.json_encode($events).';
						$.each(obj, function(key,values) {
							if (values.method.length) {
								runJsCodeForEvent'.$htmlname.'(values);
							}
						});
						/* Clean contact */
						$("div#s2id_contactid>a>span").html(\'\');
					});

					// Function used to execute events when search_htmlname change
					function runJsCodeForEvent'.$htmlname.'(obj) {
						var id = $("#'.$htmlname.'").val();
						var method = obj.method;
						var url = obj.url;
						var htmlname = obj.htmlname;
						var showempty = obj.showempty;
						console.log("Run runJsCodeForEvent-'.$htmlname.' from selectCompaniesForNewContact id="+id+" method="+method+" showempty="+showempty+" url="+url+" htmlname="+htmlname);
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
					};
				});
				</script>';
			}

			print "\n".'<!-- Input text for third party with Ajax.Autocompleter (selectCompaniesForNewContact) -->'."\n";
			print '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$name.'" />';
			print ajax_autocompleter(($socid?$socid:-1), $htmlname, DOL_URL_ROOT.'/societe/ajaxcompanies.php', '', $minLength, 0);
			return $socid;
		}
		else
		{
			// Search to list thirdparties
			$sql = "SELECT s.rowid, s.nom as name FROM";
			$sql.= " ".MAIN_DB_PREFIX."societe as s";
			$sql.= " WHERE s.entity IN (".getEntity('societe').")";
			// For ajax search we limit here. For combo list, we limit later
			if (is_array($limitto) && count($limitto))
			{
				$sql.= " AND s.rowid IN (".join(',',$limitto).")";
			}
			$sql.= " ORDER BY s.nom ASC";

			$resql = $this->db->query($sql);
			if ($resql)
			{
				print '<select class="flat'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"';
				if ($conf->use_javascript_ajax)
				{
					$javaScript = "window.location='".$_SERVER['PHP_SELF']."?".$var_id."=".($forceid>0?$forceid:$object->id).$moreparam."&".$htmlname."=' + form.".$htmlname.".options[form.".$htmlname.".selectedIndex].value;";
					print ' onChange="'.$javaScript.'"';
				}
				print '>';
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
							if ($disabled) print ' disabled';
							print ' selected>'.dol_trunc($obj->name,24).'</option>';
							$firstCompany = $obj->rowid;
						}
						else
						{
							print '<option value="'.$obj->rowid.'"';
							if ($disabled) print ' disabled';
							print '>'.dol_trunc($obj->name,24).'</option>';
						}
						$i ++;
					}
				}
				print "</select>\n";
				return $firstCompany;
			}
			else
			{
				dol_print_error($this->db);
				print 'Error sql';
			}
		}
	}

    /**
     *  Return a select list with types of contacts
     *
     *  @param	object		$object         Object to use to find type of contact
     *  @param  string		$selected       Default selected value
     *  @param  string		$htmlname		HTML select name
     *  @param  string		$source			Source ('internal' or 'external')
     *  @param  string		$sortorder		Sort criteria ('position', 'code', ...)
     *  @param  int			$showempty      1=Add en empty line
     *  @param  string      $morecss        Add more css to select component
     *  @return	void
     */
	function selectTypeContact($object, $selected, $htmlname = 'type', $source='internal', $sortorder='position', $showempty=0, $morecss='')
	{
	    global $user, $langs;

		if (is_object($object) && method_exists($object, 'liste_type_contact'))
		{
			$lesTypes = $object->liste_type_contact($source, $sortorder, 0, 1);
			print '<select class="flat valignmiddle'.($morecss?' '.$morecss:'').'" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($showempty) print '<option value="0"></option>';
			foreach($lesTypes as $key=>$value)
			{
				print '<option value="'.$key.'"';
				if ($key == $selected) print ' selected';
				print '>'.$value.'</option>';
			}
			print "</select>";
			if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
			print "\n";
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Return a select list with zip codes and their town
	 *
	 *    @param	string		$selected				Preselected value
	 *    @param    string		$htmlname				HTML select name
	 *    @param    string		$fields					Fields
	 *    @param    int			$fieldsize				Field size
	 *    @param    int			$disableautocomplete    1 To disable ajax autocomplete features (browser autocomplete may still occurs)
	 *    @param	string		$moreattrib				Add more attribute on HTML input field
	 *    @param    string      $morecss                More css
	 *    @return	string
	 */
	function select_ziptown($selected='', $htmlname='zipcode', $fields='', $fieldsize=0, $disableautocomplete=0, $moreattrib='',$morecss='')
	{
        // phpcs:enable
		global $conf;

		$out='';

		$size='';
		if (!empty($fieldsize)) $size='size="'.$fieldsize.'"';

		if ($conf->use_javascript_ajax && empty($disableautocomplete))
		{
			$out.= ajax_multiautocompleter($htmlname,$fields,DOL_URL_ROOT.'/core/ajax/ziptown.php')."\n";
			$moreattrib.=' autocomplete="off"';
		}
		$out.= '<input id="'.$htmlname.'" class="maxwidthonsmartphone'.($morecss?' '.$morecss:'').'" type="text"'.($moreattrib?' '.$moreattrib:'').' name="'.$htmlname.'" '.$size.' value="'.$selected.'">'."\n";

		return $out;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
    function get_input_id_prof($idprof,$htmlname,$preselected,$country_code,$morecss='maxwidth100onsmartphone quatrevingtpercent')
    {
        // phpcs:enable
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

        $out = '<input type="text" '.($morecss?'class="'.$morecss.'" ':'').'name="'.$htmlname.'" id="'.$htmlname.'" maxlength="'.$maxlength.'" value="'.$selected.'">';

        return $out;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     * Return a HTML select with localtax values for thirdparties
     *
     * @param 	int 		$local			LocalTax
     * @param 	int 		$selected		Preselected value
     * @param 	string      $htmlname		HTML select name
     * @return	void
     */
    function select_localtax($local, $selected, $htmlname)
    {
        // phpcs:enable
        $tax=get_localtax_by_third($local);

        $num = $this->db->num_rows($tax);
        $i = 0;
    	if ($num)
    	{
    		$valors=explode(":", $tax);

    		if (count($valors) > 1)
    		{
    			//montar select
    			print '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
    			while ($i <= (count($valors))-1)
    			{
    				if ($selected == $valors[$i])
    				{
    					print '<option value="'.$valors[$i].'" selected>';
    				}
    				else
    				{
    					print '<option value="'.$valors[$i].'">';
    				}
    				print $valors[$i];
    				print '</option>';
    				$i++;
    			}
    			print'</select>';
    		}
    	}
    }
}
