<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo  <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin 		<patrick.raguin@gmail.com>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
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
 *	\file       htdocs/core/class/html.formother.class.php
 *  \ingroup    core
 *	\brief      Fichier de la class des functions predefinie de composants html autre
 */


/**
 *	Class permettant la generation de composants html autre
 *	Only common components are here.
 */
class FormOther
{
	private $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return the HTML code for scanner tool.
	 * This must be called into an existing <form>
	 *
	 * @param	string	$jstoexecuteonadd	Name of javascript function to call once the barcode scanning session is complete and user has click on "Add".
	 * @param	string	$mode				'all' (both product and lot barcode) or 'product' (product barcode only) or 'lot' (lot number only)
	 * @param	int		$warehouseselect	0 (disable warehouse select) or 1 (enable warehouse select)
	 * @return	string						HTML component
	 */
	public function getHTMLScannerForm($jstoexecuteonadd = 'barcodescannerjs', $mode = 'all', $warehouseselect = 0)
	{
		global $langs;

		$out = '';

		$out .= '<!-- Popup for mass barcode scanning -->'."\n";
		$out .= '<div class="div-for-modal-topright" style="padding: 15px">';
		$out .= '<center>'.img_picto('', 'barcode', 'class="pictofixedwidth"').'<strong>Barcode scanner tool...</strong></center><br>';

		if ($mode == 'product') {
			$out .= '<input type="hidden" name="barcodemode" value="barcodeforproduct" id="barcodeforproduct">';
		} elseif ($mode == 'lot') {
			$out .= '<input type="hidden" name="barcodemode" value="barcodeforlotserial" id="barcodeforlotserial">';
		} else {	// $mode = 'all'
			$out .= '<input type="radio" name="barcodemode" value="barcodeforautodetect" id="barcodeforautodetect" checked="checked"> <label for="barcodeforautodetect">Autodetect if we scan a product barcode or a lot/serial barcode</label><br>';
			$out .= '<input type="radio" name="barcodemode" value="barcodeforproduct" id="barcodeforproduct"> <label for="barcodeforproduct">Scan a product barcode</label><br>';
			$out .= '<input type="radio" name="barcodemode" value="barcodeforlotserial" id="barcodeforlotserial"> <label for="barcodeforlotserial">Scan a product lot or serial number</label><br>';
		}
		$stringaddbarcode = $langs->trans("QtyToAddAfterBarcodeScan", "tmphtml");
		$htmltoreplaceby = '<select name="selectaddorreplace"><option selected value="add">'.$langs->trans("Add").'</option><option value="replace">'.$langs->trans("ToReplace").'</option></select>';
		$stringaddbarcode = str_replace("tmphtml", $htmltoreplaceby, $stringaddbarcode);
		$out .= $stringaddbarcode.': <input type="text" name="barcodeproductqty" class="width40 right" value="1"><br>';
		if ($warehouseselect > 0) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($this->db);
			$formproduct->loadWarehouses();
			$out .= img_picto('', 'stock', 'class="pictofixedwidth"');
			$out .= $formproduct->selectWarehouses('', "warehousenew", '', 0, 0, 0, '', 0, 1);
			$out .= '<br>';
			$out .= '<br>';
		}
		$out .= '<textarea type="text" name="barcodelist" class="centpercent" autofocus rows="'.ROWS_3.'" placeholder="'.dol_escape_htmltag($langs->trans("ScanOrTypeOrCopyPasteYourBarCodes")).'"></textarea>';

		/*print '<br>'.$langs->trans("or").'<br>';

		print '<br>';

		print '<input type="text" name="barcodelotserial" class="width200"> &nbsp; &nbsp; Qty <input type="text" name="barcodelotserialqty" class="width50 right" value="1"><br>';
		*/
		$out .= '<br>';
		$out .= '<center>';
		$out .= '<input type="submit" class="button marginleftonly marginrightonly" id ="exec'.dol_escape_js($jstoexecuteonadd).'" name="addscan" value="'.dol_escape_htmltag($langs->trans("Add")).'">';
		$out .= '<input type="submit" class="button marginleftonly marginrightonly" name="cancel" value="'.dol_escape_htmltag($langs->trans("CloseWindow")).'">';
		$out .= '</center>';
		$out .= '<br>';
		$out .= '<div type="text" id="scantoolmessage" class="scantoolmessage ok nopadding"></div>';

		$out .= '<script nonce="'.getNonce().'">';
		$out .= 'jQuery("#barcodeforautodetect, #barcodeforproduct, #barcodeforlotserial").click(function(){';
		$out .= 'console.log("select choice");';
		$out .= 'jQuery("#scantoolmessage").text("");';
		$out .= '});'."\n";
		$out .= '$("#exec'.dol_escape_js($jstoexecuteonadd).'").click(function(){
			console.log("We call js to execute \''.dol_escape_js($jstoexecuteonadd).'\'");
			'.dol_escape_js($jstoexecuteonadd).'();
			return false;	/* We want to stay on the scan tool */
		})';
		$out .= '</script>';

		$out .= '</center>';
		$out .= '</div>';

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return HTML select list of export models
	 *
	 *    @param    string	$selected          Id modele pre-selectionne
	 *    @param    string	$htmlname          Nom de la zone select
	 *    @param    string	$type              Type des modeles recherches
	 *    @param    int		$useempty          Show an empty value in list
	 *    @param    int		$fk_user           User we want templates
	 *    @return	void
	 */
	public function select_export_model($selected = '', $htmlname = 'exportmodelid', $type = '', $useempty = 0, $fk_user = null)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$sql = "SELECT rowid, label, fk_user";
		$sql .= " FROM ".$this->db->prefix()."export_model";
		$sql .= " WHERE type = '".$this->db->escape($type)."'";
		if (!getDolGlobalString('EXPORTS_SHARE_MODELS')) {	// EXPORTS_SHARE_MODELS means all templates are visible, whatever is owner.
			$sql .= " AND fk_user IN (0, ".((int) $fk_user).")";
		}
		$sql .= " ORDER BY label";
		$result = $this->db->query($sql);
		if ($result) {
			print '<select class="flat minwidth200" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($useempty) {
				print '<option value="-1">&nbsp;</option>';
			}

			$tmpuser = new User($this->db);

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);

				$label = $obj->label;
				if ($obj->fk_user == 0) {
					$label .= ' <span class="opacitymedium">('.$langs->trans("Everybody").')</span>';
				} elseif ($obj->fk_user > 0) {
					$tmpuser->fetch($obj->fk_user);
					$label .= ' <span class="opacitymedium">('.$tmpuser->getFullName($langs).')</span>';
				}

				if ($selected == $obj->rowid) {
					print '<option value="'.$obj->rowid.'" selected data-html="'.dol_escape_htmltag($label).'">';
				} else {
					print '<option value="'.$obj->rowid.'" data-html="'.dol_escape_htmltag($label).'">';
				}
				print $label;
				print '</option>';
				$i++;
			}
			print "</select>";
			print ajax_combobox($htmlname);
		} else {
			dol_print_error($this->db);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of export models
	 *
	 *    @param    string	$selected          Id modele pre-selectionne
	 *    @param    string	$htmlname          Nom de la zone select
	 *    @param    string	$type              Type des modeles recherches
	 *    @param    int		$useempty          Affiche valeur vide dans liste
	 *    @param    int		$fk_user           User that has created the template
	 *    @return	void
	 */
	public function select_import_model($selected = '', $htmlname = 'importmodelid', $type = '', $useempty = 0, $fk_user = null)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$sql = "SELECT rowid, label, fk_user";
		$sql .= " FROM ".$this->db->prefix()."import_model";
		$sql .= " WHERE type = '".$this->db->escape($type)."'";
		if (!getDolGlobalString('EXPORTS_SHARE_MODELS')) {	// EXPORTS_SHARE_MODELS means all templates are visible, whatever is owner.
			$sql .= " AND fk_user IN (0, ".((int) $fk_user).")";
		}
		$sql .= " ORDER BY label";
		$result = $this->db->query($sql);
		if ($result) {
			print '<select class="flat minwidth200" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($useempty) {
				print '<option value="-1">&nbsp;</option>';
			}

			$tmpuser = new User($this->db);

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);

				$label = $obj->label;
				if ($obj->fk_user == 0) {
					$label .= ' <span class="opacitymedium">('.$langs->trans("Everybody").')</span>';
				} elseif ($obj->fk_user > 0) {
					$tmpuser->fetch($obj->fk_user);
					$label .= ' <span class="opacitymedium">('.$tmpuser->getFullName($langs).')</span>';
				}

				if ($selected == $obj->rowid) {
					print '<option value="'.$obj->rowid.'" selected data-html="'.dol_escape_htmltag($label).'">';
				} else {
					print '<option value="'.$obj->rowid.'" data-html="'.dol_escape_htmltag($label).'">';
				}
				print $label;
				print '</option>';
				$i++;
			}
			print "</select>";
			print ajax_combobox($htmlname);
		} else {
			dol_print_error($this->db);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of ecotaxes with label
	 *
	 *    @param	string	$selected   Preselected ecotaxes
	 *    @param    string	$htmlname	Name of combo list
	 *    @return	integer
	 */
	public function select_ecotaxes($selected = '', $htmlname = 'ecotaxe_id')
	{
		// phpcs:enable
		global $langs;

		$sql = "SELECT e.rowid, e.code, e.label, e.price, e.organization,";
		$sql .= " c.label as country";
		$sql .= " FROM ".$this->db->prefix()."c_ecotaxe as e,".$this->db->prefix()."c_country as c";
		$sql .= " WHERE e.active = 1 AND e.fk_pays = c.rowid";
		$sql .= " ORDER BY country, e.organization ASC, e.code ASC";

		dol_syslog(get_class($this).'::select_ecotaxes', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			print '<option value="-1">&nbsp;</option>'."\n";
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if ($selected && $selected == $obj->rowid) {
						print '<option value="'.$obj->rowid.'" selected>';
					} else {
						print '<option value="'.$obj->rowid.'">';
						//print '<option onmouseover="showtip(\''.$obj->label.'\')" onMouseout="hidetip()" value="'.$obj->rowid.'">';
					}
					$selectOptionValue = $obj->code.' - '.$obj->label.' : '.price($obj->price).' '.$langs->trans("HT").' ('.$obj->organization.')';
					print $selectOptionValue;
					print '</option>';
					$i++;
				}
			}
			print '</select>';
			return 0;
		} else {
			dol_print_error($this->db);
			return 1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of revenue stamp for country
	 *
	 *    @param	string	$selected   	Value of preselected revenue stamp
	 *    @param    string	$htmlname   	Name of combo list
	 *    @param    string	$country_code   Country Code
	 *    @return	string					HTML select list
	 */
	public function select_revenue_stamp($selected = '', $htmlname = 'revenuestamp', $country_code = '')
	{
		// phpcs:enable
		global $langs;

		$out = '';

		$sql = "SELECT r.taux, r.revenuestamp_type";
		$sql .= " FROM ".$this->db->prefix()."c_revenuestamp as r,".$this->db->prefix()."c_country as c";
		$sql .= " WHERE r.active = 1 AND r.fk_pays = c.rowid";
		$sql .= " AND c.code = '".$this->db->escape($country_code)."'";

		dol_syslog(get_class($this).'::select_revenue_stamp', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$out .= '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			$out .= '<option value="0">&nbsp;</option>'."\n";
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if (($selected && $selected == $obj->taux) || $num == 1) {
						$out .= '<option value="'.$obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '').'"'.($obj->revenuestamp_type == 'percent' ? ' data-type="percent"' : '').' selected>';
					} else {
						$out .= '<option value="'.$obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '').'"'.($obj->revenuestamp_type == 'percent' ? ' data-type="percent"' : '').'>';
					}
					$out .= $obj->taux.($obj->revenuestamp_type == 'percent' ? '%' : '');
					$out .= '</option>';
					$i++;
				}
			}
			$out .= '</select>';
			return $out;
		} else {
			dol_print_error($this->db);
			return '';
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return a HTML select list to select a percent
	 *
	 *    @param	integer	$selected      	pourcentage pre-selectionne
	 *    @param    string	$htmlname      	nom de la liste deroulante
	 *    @param	int		$disabled		Disabled or not
	 *    @param    int		$increment     	increment value
	 *    @param    int		$start         	start value
	 *    @param    int		$end           	end value
	 *    @param    int     $showempty      Add also an empty line
	 *    @return   string					HTML select string
	 */
	public function select_percent($selected = 0, $htmlname = 'percent', $disabled = 0, $increment = 5, $start = 0, $end = 100, $showempty = 0)
	{
		// phpcs:enable
		$return = '<select class="flat maxwidth75 right" name="'.$htmlname.'" '.($disabled ? 'disabled' : '').'>';
		if ($showempty) {
			$return .= '<option value="-1"'.(($selected == -1 || $selected == '') ? ' selected' : '').'>&nbsp;</option>';
		}

		for ($i = $start; $i <= $end; $i += $increment) {
			if ($selected != '' && (int) $selected == $i) {
				$return .= '<option value="'.$i.'" selected>';
			} else {
				$return .= '<option value="'.$i.'">';
			}
			$return .= $i.' % ';
			$return .= '</option>';
		}

		$return .= '</select>';

		return $return;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return select list for categories (to use in form search selectors)
	 *
	 * @param	string		$type			Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 * @param   integer		$selected     	Preselected value
	 * @param   string		$htmlname      	Name of combo list
	 * @param	int			$nocateg		Show also an entry "Not categorized"
	 * @param   int|string  $showempty      Add also an empty line
	 * @param   string  	$morecss        More CSS
	 * @return  string			        	Html combo list code
	 * @see	select_all_categories()
	 */
	public function select_categories($type, $selected = 0, $htmlname = 'search_categ', $nocateg = 0, $showempty = 1, $morecss = '')
	{
		// phpcs:enable
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		// For backward compatibility
		if (is_numeric($type)) {
			dol_syslog(__METHOD__.': using numeric value for parameter type is deprecated. Use string code instead.', LOG_WARNING);
		}

		// Load list of "categories"
		$static_categs = new Categorie($this->db);
		$tab_categs = $static_categs->get_full_arbo($type);

		$moreforfilter = '';

		// Print a select with each of them
		$moreforfilter .= '<select class="flat minwidth100'.($morecss ? ' '.$morecss : '').'" id="select_categ_'.$htmlname.'" name="'.$htmlname.'">';
		if ($showempty) {
			$textforempty = ' ';
			if (!empty($conf->use_javascript_ajax)) {
				$textforempty = '&nbsp;'; // If we use ajaxcombo, we need &nbsp; here to avoid to have an empty element that is too small.
			}
			if (!is_numeric($showempty)) {
				$textforempty = $showempty;
			}
			$moreforfilter .= '<option class="optiongrey" value="'.($showempty < 0 ? $showempty : -1).'"'.($selected == $showempty ? ' selected' : '');
			//$moreforfilter .= ' data-html="'.dol_escape_htmltag($textforempty).'"';
			$moreforfilter .= '>'.dol_escape_htmltag($textforempty).'</option>'."\n";
		}

		if (is_array($tab_categs)) {
			foreach ($tab_categs as $categ) {
				$moreforfilter .= '<option value="'.$categ['id'].'"';
				if ($categ['id'] == $selected) {
					$moreforfilter .= ' selected';
				}
				$moreforfilter .= ' data-html="'.dol_escape_htmltag(img_picto('', 'category', 'class="pictofixedwidth" style="color: #'.$categ['color'].'"').dol_trunc($categ['fulllabel'], 50, 'middle')).'"';
				$moreforfilter .= '>'.dol_trunc($categ['fulllabel'], 50, 'middle').'</option>';
			}
		}
		if ($nocateg) {
			$langs->load("categories");
			$moreforfilter .= '<option value="-2"'.($selected == -2 ? ' selected' : '').'>- '.$langs->trans("NotCategorized").' -</option>';
		}
		$moreforfilter .= '</select>';

		// Enhance with select2
		if ($conf->use_javascript_ajax) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox('select_categ_'.$htmlname);
			$moreforfilter .= $comboenhancement;
		}

		return $moreforfilter;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return select list for categories (to use in form search selectors)
	 *
	 *  @param	int|string	$selected     		Preselected value
	 *  @param  string		$htmlname      		Name of combo list (example: 'search_sale')
	 *  @param  User		$user           	Object user
	 *  @param	int			$showstatus			0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
	 *  @param	int|string	$showempty			1=show also an empty value or text to show for empty
	 *  @param	string		$morecss			More CSS
	 *  @param	int			$norepresentative	Show also an entry "Not categorized"
	 *  @return string							Html combo list code
	 */
	public function select_salesrepresentatives($selected, $htmlname, $user, $showstatus = 0, $showempty = 1, $morecss = '', $norepresentative = 0)
	{
		// phpcs:enable
		global $conf, $langs, $hookmanager;
		global $action;

		$langs->load('users');

		$out = '';

		$reshook = $hookmanager->executeHooks('addSQLWhereFilterOnSelectSalesRep', array(), $this, $action);

		// Select each sales and print them in a select input
		$out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.$htmlname.'" name="'.$htmlname.'">';
		if ($showempty) {
			$textforempty = ' ';
			if (!is_numeric($showempty)) {
				$textforempty = $showempty;
			}
			if (!empty($conf->use_javascript_ajax) && $textforempty == ' ') {
				$textforempty = '&nbsp;'; // If we use ajaxcombo, we need &nbsp; here to avoid to have an empty element that is too small.
			}
			$out .= '<option class="optiongrey" value="'.($showempty < 0 ? $showempty : -1).'"'.($selected == $showempty ? ' selected' : '').'>'.$textforempty.'</option>'."\n";
		}

		// Get list of users allowed to be viewed
		$sql_usr = "SELECT u.rowid, u.lastname, u.firstname, u.statut as status, u.login, u.photo, u.gender, u.entity, u.admin";
		$sql_usr .= " FROM ".$this->db->prefix()."user as u";

		if (getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE')) {
			if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
				$sql_usr .= " WHERE u.entity IS NOT NULL"; // Show all users
			} else {
				$sql_usr .= " WHERE EXISTS (SELECT ug.fk_user FROM ".$this->db->prefix()."usergroup_user as ug WHERE u.rowid = ug.fk_user AND ug.entity IN (".getEntity('usergroup')."))";
				$sql_usr .= " OR u.entity = 0"; // Show always superadmin
			}
		} else {
			$sql_usr .= " WHERE u.entity IN (".getEntity('user').")";
		}

		if (!$user->hasRight('user', 'user', 'lire')) {
			$sql_usr .= " AND u.rowid = ".((int) $user->id);
		}
		if (!empty($user->socid)) {
			$sql_usr .= " AND u.fk_soc = ".((int) $user->socid);
		}
		if (getDolUserString('USER_HIDE_NONEMPLOYEE_IN_COMBOBOX', getDolGlobalString('USER_HIDE_NONEMPLOYEE_IN_COMBOBOX'))) {
			$sql_usr .= " AND u.employee <> 0";
		}
		if (getDolUserString('USER_HIDE_EXTERNAL_IN_COMBOBOX', getDolGlobalString('USER_HIDE_EXTERNAL_IN_COMBOBOX'))) {
			$sql_usr .= " AND u.fk_soc IS NULL";
		}
		if (getDolUserString('USER_HIDE_INACTIVE_IN_COMBOBOX', getDolGlobalString('USER_HIDE_INACTIVE_IN_COMBOBOX'))) {	// Can be set in setup of module User.
			$sql_usr .= " AND u.statut <> 0";
		}

		//Add hook to filter on user (for example on usergroup define in custom modules)
		if (!empty($reshook)) {
			$sql_usr .= $hookmanager->resArray[0];
		}

		// Add existing sales representatives of thirdparty of external user
		if (!$user->hasRight('user', 'user', 'lire') && $user->socid) {
			$sql_usr .= " UNION ";
			$sql_usr .= "SELECT u2.rowid, u2.lastname, u2.firstname, u2.statut as status, u2.login, u2.photo, u2.gender, u2.entity, u2.admin";
			$sql_usr .= " FROM ".$this->db->prefix()."user as u2, ".$this->db->prefix()."societe_commerciaux as sc";

			if (getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE')) {
				if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
					$sql_usr .= " WHERE u2.entity IS NOT NULL"; // Show all users
				} else {
					$sql_usr .= " WHERE EXISTS (SELECT ug2.fk_user FROM ".$this->db->prefix()."usergroup_user as ug2 WHERE u2.rowid = ug2.fk_user AND ug2.entity IN (".getEntity('usergroup')."))";
				}
			} else {
				$sql_usr .= " WHERE u2.entity IN (".getEntity('user').")";
			}

			$sql_usr .= " AND u2.rowid = sc.fk_user AND sc.fk_soc = ".((int) $user->socid);

			//Add hook to filter on user (for example on usergroup define in custom modules)
			if (!empty($reshook)) {
				$sql_usr .= $hookmanager->resArray[1];
			}
		}

		if (!getDolGlobalString('MAIN_FIRSTNAME_NAME_POSITION')) {	// MAIN_FIRSTNAME_NAME_POSITION is 0 means firstname+lastname
			$sql_usr .= " ORDER BY status DESC, firstname ASC, lastname ASC";
		} else {
			$sql_usr .= " ORDER BY status DESC, lastname ASC, firstname ASC";
		}
		//print $sql_usr;exit;

		$resql_usr = $this->db->query($sql_usr);
		if ($resql_usr) {
			$userstatic = new User($this->db);

			while ($obj_usr = $this->db->fetch_object($resql_usr)) {
				$userstatic->id = $obj_usr->rowid;
				$userstatic->lastname = $obj_usr->lastname;
				$userstatic->firstname = $obj_usr->firstname;
				$userstatic->photo = $obj_usr->photo;
				$userstatic->status = $obj_usr->status;
				$userstatic->entity = $obj_usr->entity;
				$userstatic->admin = $obj_usr->admin;

				$labeltoshow = dolGetFirstLastname($obj_usr->firstname, $obj_usr->lastname);
				if (empty($obj_usr->firstname) && empty($obj_usr->lastname)) {
					$labeltoshow = $obj_usr->login;
				}

				$out .= '<option value="'.$obj_usr->rowid.'"';
				if ($obj_usr->rowid == $selected) {
					$out .= ' selected';
				}
				$out .= ' data-html="';
				$outhtml = $userstatic->getNomUrl(-3, '', 0, 1, 24, 1, 'login', '', 1).' ';
				if ($showstatus >= 0 && $obj_usr->status == 0) {
					$outhtml .= '<strike class="opacitymediumxxx">';
				}
				$outhtml .= $labeltoshow;
				if ($showstatus >= 0 && $obj_usr->status == 0) {
					$outhtml .= '</strike>';
				}
				$out .= dol_escape_htmltag($outhtml);
				$out .= '">';

				$out .= $labeltoshow;
				// Complete name with more info
				$moreinfo = 0;
				if (getDolGlobalString('MAIN_SHOW_LOGIN')) {
					$out .= ($moreinfo ? ' - ' : ' (').$obj_usr->login;
					$moreinfo++;
				}
				if ($showstatus >= 0) {
					if ($obj_usr->status == 1 && $showstatus == 1) {
						$out .= ($moreinfo ? ' - ' : ' (').$langs->trans('Enabled');
						$moreinfo++;
					}
					if ($obj_usr->status == 0) {
						$out .= ($moreinfo ? ' - ' : ' (').$langs->trans('Disabled');
						$moreinfo++;
					}
				}
				$out .= ($moreinfo ? ')' : '');
				$out .= '</option>';
			}
			$this->db->free($resql_usr);
		} else {
			dol_print_error($this->db);
		}

		if ($norepresentative) {
			$langs->load("companies");
			$out .= '<option value="-2"'.($selected == -2 ? ' selected' : '').'>- '.$langs->trans("NoSalesRepresentativeAffected").' -</option>';
		}

		$out .= '</select>';

		// Enhance with select2
		if ($conf->use_javascript_ajax) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

			$comboenhancement = ajax_combobox($htmlname);
			if ($comboenhancement) {
				$out .= $comboenhancement;
			}
		}

		return $out;
	}

	/**
	 *	Return list of project and tasks
	 *
	 *	@param  int		$selectedtask   		Pre-selected task
	 *  @param  int		$projectid				Project id
	 * 	@param  string	$htmlname    			Name of html select
	 * 	@param	int		$modeproject			1 to restrict on projects owned by user
	 * 	@param	int		$modetask				1 to restrict on tasks associated to user
	 * 	@param	int		$mode					0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 *  @param  int		$useempty       		0=Allow empty values
	 *  @param	int		$disablechildoftaskid	1=Disable task that are child of the provided task id
	 *  @param	string	$filteronprojstatus		Filter on project status ('-1'=no filter, '0,1'=Draft+Validated status)
	 *  @param	string	$morecss				More css
	 *  @return	void
	 */
	public function selectProjectTasks($selectedtask = 0, $projectid = 0, $htmlname = 'task_parent', $modeproject = 0, $modetask = 0, $mode = 0, $useempty = 0, $disablechildoftaskid = 0, $filteronprojstatus = '', $morecss = '')
	{
		global $user, $langs;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

		//print $modeproject.'-'.$modetask;
		$task = new Task($this->db);
		$tasksarray = $task->getTasksArray($modetask ? $user : 0, $modeproject ? $user : 0, $projectid, 0, $mode, '', $filteronprojstatus);
		if ($tasksarray) {
			print '<select class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($useempty) {
				print '<option value="0">&nbsp;</option>';
			}
			$j = 0;
			$level = 0;
			$this->_pLineSelect($j, 0, $tasksarray, $level, $selectedtask, $projectid, $disablechildoftaskid);
			print '</select>';

			print ajax_combobox($htmlname);
		} else {
			print '<div class="warning">'.$langs->trans("NoProject").'</div>';
		}
	}

	/**
	 * Write lines of a project (all lines of a project if parent = 0)
	 *
	 * @param 	int		$inc					Cursor counter
	 * @param 	int		$parent					Id of parent task we want to see
	 * @param 	array	$lines					Array of task lines
	 * @param 	int		$level					Level
	 * @param 	int		$selectedtask			Id selected task
	 * @param 	int		$selectedproject		Id selected project
	 * @param	int		$disablechildoftaskid	1=Disable task that are child of the provided task id
	 * @return	void
	 */
	private function _pLineSelect(&$inc, $parent, $lines, $level = 0, $selectedtask = 0, $selectedproject = 0, $disablechildoftaskid = 0)
	{
		global $langs, $user, $conf;

		$lastprojectid = 0;

		$numlines = count($lines);
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent == $parent) {
				//var_dump($selectedproject."--".$selectedtask."--".$lines[$i]->fk_project."_".$lines[$i]->id);		// $lines[$i]->id may be empty if project has no lines

				// Break on a new project
				if ($parent == 0) {	// We are on a task at first level
					if ($lines[$i]->fk_project != $lastprojectid) {	// Break found on project
						if ($i > 0) {
							print '<option value="0" disabled>----------</option>';
						}
						print '<option value="'.$lines[$i]->fk_project.'_0"';
						if ($selectedproject == $lines[$i]->fk_project) {
							print ' selected';
						}

						$labeltoshow = $lines[$i]->projectref;
						//$labeltoshow .= ' '.$lines[$i]->projectlabel;
						if (empty($lines[$i]->public)) {
							//$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')</span>';
							$labeltoshow = img_picto($lines[$i]->projectlabel, 'project', 'class="pictofixedwidth"').$labeltoshow;
						} else {
							//$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')</span>';
							$labeltoshow = img_picto($lines[$i]->projectlabel, 'projectpub', 'class="pictofixedwidth"').$labeltoshow;
						}

						print ' data-html="'.dol_escape_htmltag($labeltoshow).'"';
						print '>'; // Project -> Task
						print $labeltoshow;
						print "</option>\n";

						$lastprojectid = $lines[$i]->fk_project;
						$inc++;
					}
				}

				$newdisablechildoftaskid = $disablechildoftaskid;

				// Print task
				if (isset($lines[$i]->id)) {		// We use isset because $lines[$i]->id may be null if project has no task and are on root project (tasks may be caught by a left join). We enter here only if '0' or >0
					// Check if we must disable entry
					$disabled = 0;
					if ($disablechildoftaskid && (($lines[$i]->id == $disablechildoftaskid || $lines[$i]->fk_task_parent == $disablechildoftaskid))) {
						$disabled++;
						if ($lines[$i]->fk_task_parent == $disablechildoftaskid) {
							$newdisablechildoftaskid = $lines[$i]->id; // If task is child of a disabled parent, we will propagate id to disable next child too
						}
					}

					print '<option value="'.$lines[$i]->fk_project.'_'.$lines[$i]->id.'"';
					if (($lines[$i]->id == $selectedtask) || ($lines[$i]->fk_project.'_'.$lines[$i]->id == $selectedtask)) {
						print ' selected';
					}
					if ($disabled) {
						print ' disabled';
					}

					$labeltoshow = $lines[$i]->projectref;
					//$labeltoshow .= ' '.$lines[$i]->projectlabel;
					if (empty($lines[$i]->public)) {
						//$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')</span>';
						$labeltoshow = img_picto($lines[$i]->projectlabel, 'project', 'class="pictofixedwidth"').$labeltoshow;
					} else {
						//$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')</span>';
						$labeltoshow = img_picto($lines[$i]->projectlabel, 'projectpub', 'class="pictofixedwidth"').$labeltoshow;
					}
					if ($lines[$i]->id) {
						$labeltoshow .= ' > ';
					}
					for ($k = 0; $k < $level; $k++) {
						$labeltoshow .= "&nbsp;&nbsp;&nbsp;";
					}
					$labeltoshow .= $lines[$i]->ref.' '.$lines[$i]->label;

					print ' data-html="'.dol_escape_htmltag($labeltoshow).'"';
					print '>';
					print $labeltoshow;
					print "</option>\n";
					$inc++;
				}

				$level++;
				if ($lines[$i]->id) {
					$this->_pLineSelect($inc, $lines[$i]->id, $lines, $level, $selectedtask, $selectedproject, $newdisablechildoftaskid);
				}
				$level--;
			}
		}
	}


	/**
	 *  Output a HTML thumb of color or a text if not defined.
	 *
	 *  @param	string		$color				String with hex (FFFFFF) or comma RGB ('255,255,255')
	 *  @param	string		$textifnotdefined	Text to show if color not defined
	 *  @return	string							Show color string
	 *  @see selectColor()
	 */
	public static function showColor($color, $textifnotdefined = '')
	{
		$textcolor = 'FFF';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		if (colorIsLight($color)) {
			$textcolor = '000';
		}

		$color = colorArrayToHex(colorStringToArray($color, array()), '');

		if ($color) {
			return '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; color: #'.$textcolor.'; background-color: #'.$color.'" value="'.$color.'">';
		} else {
			return $textifnotdefined;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output a HTML code to select a color
	 *
	 *  @param	string		$set_color		Pre-selected color
	 *  @param	string		$prefix			Name of HTML field
	 *  @param	string		$form_name		Deprecated. Not used.
	 *  @param	int			$showcolorbox	1=Show color code and color box, 0=Show only color code
	 *  @param 	array		$arrayofcolors	Array of colors. Example: array('29527A','5229A3','A32929','7A367A','B1365F','0D7813')
	 *  @return	void
	 *  @deprecated Use instead selectColor
	 *  @see selectColor()
	 */
	public function select_color($set_color = '', $prefix = 'f_color', $form_name = '', $showcolorbox = 1, $arrayofcolors = [])
	{
		// phpcs:enable
		print $this->selectColor($set_color, $prefix, $form_name, $showcolorbox, $arrayofcolors);
	}

	/**
	 *  Output a HTML code to select a color. Field will return an hexa color like '334455'.
	 *
	 *  @param	string		$set_color				Pre-selected color with format '#......'
	 *  @param	string		$prefix					Name of HTML field
	 *  @param	null|''		$form_name				Deprecated. Not used.
	 *  @param	int			$showcolorbox			1=Show color code and color box, 0=Show only color code
	 *  @param 	string[]	$arrayofcolors			Array of possible colors to choose in the selector. All colors are possible if empty. Example: array('29527A','5229A3','A32929','7A367A','B1365F','0D7813')
	 *  @param	string		$morecss				Add css style into input field
	 *  @param	string		$setpropertyonselect	Set this CSS property after selecting a color
	 *  @param	string		$default				Default color
	 *  @return	string
	 *  @see showColor()
	 */
	public static function selectColor($set_color = '', $prefix = 'f_color', $form_name = '', $showcolorbox = 1, $arrayofcolors = [], $morecss = '', $setpropertyonselect = '', $default = '')
	{
		// Deprecation warning
		if ($form_name) {
			dol_syslog(__METHOD__.": form_name parameter is deprecated", LOG_WARNING);
		}

		global $langs, $conf;

		$out = '';

		if (!is_array($arrayofcolors) || count($arrayofcolors) < 1) {
			// Case of selection of any color
			$langs->load("other");
			if (empty($conf->dol_use_jmobile) && !empty($conf->use_javascript_ajax) && !getDolGlobalInt('MAIN_USE_HTML5_COLOR_SELECTOR')) {
				$out .= '<link rel="stylesheet" media="screen" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css" />';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/jpicker-1.1.6.js"></script>';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">
	             jQuery(document).ready(function(){
					var originalhex = null;
	                $(\'#colorpicker'.$prefix.'\').jPicker( {
		                window: {
		                  title: \''.dol_escape_js($langs->trans("SelectAColor")).'\', /* any title for the jPicker window itself - displays "Drag Markers To Pick A Color" if left null */
		                  effects:
		                    {
		                    type: \'show\', /* effect used to show/hide an expandable picker. Acceptable values "slide", "show", "fade" */
		                    speed:
		                    {
		                      show: \'fast\', /* duration of "show" effect. Acceptable values are "fast", "slow", or time in ms */
		                      hide: \'fast\' /* duration of "hide" effect. Acceptable values are "fast", "slow", or time in ms */
		                    }
		                    },
		                  position:
		                    {
		                    x: \'screenCenter\', /* acceptable values "left", "center", "right", "screenCenter", or relative px value */
		                    y: \'center\' /* acceptable values "top", "bottom", "center", or relative px value */
		                    },
		                },
		                images: {
		                    clientPath: \''.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/images/\',
		                    picker: { file: \'../../../../../theme/common/colorpicker.png\', width: 14, height: 14 }
		          		},
		                localization: // alter these to change the text presented by the picker (e.g. different language)
		                  {
		                    text:
		                    {
		                      title: \''.dol_escape_js($langs->trans("SelectAColor")).'\',
		                      newColor: \''.dol_escape_js($langs->trans("New")).'\',
		                      currentColor: \''.dol_escape_js($langs->trans("Current")).'\',
		                      ok: \''.dol_escape_js($langs->trans("Validate")).'\',
		                      cancel: \''.dol_escape_js($langs->trans("Cancel")).'\'
		                    }
		                  }
				        },
						function(color, context) { console.log("close color selector"); },
						function(color, context) { var hex = color.val(\'hex\'); console.log("new color selected in jpicker "+hex+" setpropertyonselect='.dol_escape_js($setpropertyonselect).'");';
				if ($setpropertyonselect) {
					$out .= 'if (originalhex == null) {';
					$out .= ' 	originalhex = getComputedStyle(document.querySelector(":root")).getPropertyValue(\'--'.dol_escape_js($setpropertyonselect).'\');';
					$out .= '   console.log("original color is saved into originalhex = "+originalhex);';
					$out .= '}';
					$out .= 'if (hex != null) {';
					$out .= '	document.documentElement.style.setProperty(\'--'.dol_escape_js($setpropertyonselect).'\', \'#\'+hex);';
					$out .= '}';
				}
				$out .= '},
						function(color, context) {
							console.log("cancel selection of color");';
				if ($setpropertyonselect) {
					$out .= 'if (originalhex != null) {
								console.log("Restore old color "+originalhex);
								document.documentElement.style.setProperty(\'--'.dol_escape_js($setpropertyonselect).'\', originalhex);
							}';
				}
				$out .= '
						}
					);
				 });
	             </script>';
				$out .= '<input id="colorpicker'.$prefix.'" name="'.$prefix.'" size="6" maxlength="7" class="flat valignmiddle'.($morecss ? ' '.$morecss : '').'" type="text" value="'.dol_escape_htmltag($set_color).'" />';
			} else {
				$color = ($set_color !== '' ? $set_color : ($default !== '' ? $default : 'FFFFFF'));
				$out .= '<input id="colorpicker'.$prefix.'" name="'.$prefix.'" size="6" maxlength="7" class="flat input-nobottom colorselector valignmiddle '.($morecss ? ' '.$morecss : '').'" type="color" data-default="'.$default.'" value="'.dol_escape_htmltag(preg_match('/^#/', $color) ? $color : '#'.$color).'" />';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">
	             jQuery(document).ready(function(){
					var originalhex = null;
					jQuery("#colorpicker'.$prefix.'").on(\'change\', function() {
						var hex = jQuery("#colorpicker'.$prefix.'").val();
						console.log("new color selected in input color "+hex+" setpropertyonselect='.dol_escape_js($setpropertyonselect).'");';
				if ($setpropertyonselect) {
					$out .= 'if (originalhex == null) {';
					$out .= ' 	originalhex = getComputedStyle(document.querySelector(":root")).getPropertyValue(\'--'.dol_escape_js($setpropertyonselect).'\');';
					$out .= '   console.log("original color is saved into originalhex = "+originalhex);';
					$out .= '}';
					$out .= 'if (hex != null) {';
					$out .= '	document.documentElement.style.setProperty(\'--'.dol_escape_js($setpropertyonselect).'\', hex);';
					$out .= '}';
				}
				$out .= '
					});
				});
				</script>';
			}
		} else {
			// In most cases, this is not used. We used instead function with no specific list of colors
			if (empty($conf->dol_use_jmobile) && !empty($conf->use_javascript_ajax)) {
				$out .= '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.css" type="text/css" media="screen" />';
				$out .= '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.js" type="text/javascript"></script>';
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">
	             jQuery(document).ready(function(){
	                 jQuery(\'#colorpicker'.$prefix.'\').colorpicker({
	                     size: 14,
	                     label: \'\',
	                     hide: true
	                 });
	             });
	             </script>';
			}
			$out .= '<select id="colorpicker'.$prefix.'" class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$prefix.'">';
			//print '<option value="-1">&nbsp;</option>';
			foreach ($arrayofcolors as $val) {
				$out .= '<option value="'.$val.'"';
				if ($set_color == $val) {
					$out .= ' selected';
				}
				$out .= '>'.$val.'</option>';
			}
			$out .= '</select>';
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create an image for color
	 *
	 *	@param	string	$color		Color of image
	 *	@param	string	$module 	Name of module
	 *	@param	string	$name		Name of image
	 *	@param	int		$x 			Largeur de l'image en pixels
	 *	@param	int		$y      	Hauteur de l'image en pixels
	 *	@return	void
	 */
	public function CreateColorIcon($color, $module, $name, $x = 12, $y = 12)
	{
		// phpcs:enable
		global $conf;

		$file = $conf->$module->dir_temp.'/'.$name.'.png';

		// We create temp directory
		if (!file_exists($conf->$module->dir_temp)) {
			dol_mkdir($conf->$module->dir_temp);
		}

		// On cree l'image en vraies couleurs
		$image = imagecreatetruecolor($x, $y);

		$color = substr($color, 1, 6);

		$red = hexdec(substr($color, 0, 2));    // Red channel conversion
		$green  = hexdec(substr($color, 2, 2)); // Green channel conversion
		$blue  = hexdec(substr($color, 4, 2));  // Blue channel conversion

		$couleur = imagecolorallocate($image, $red, $green, $blue);
		//print $red.$green.$blue;
		imagefill($image, 0, 0, $couleur); // Fill the image
		// Create the colr and store it in a variable to maintain it
		imagepng($image, $file); // Returns an image in PNG format
		imagedestroy($image);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Return HTML combo list of week
	 *
	 *    	@param	string		$selected          Preselected value
	 *    	@param  string		$htmlname          Nom de la zone select
	 *    	@param  int			$useempty          Affiche valeur vide dans liste
	 *    	@return	string
	 */
	public function select_dayofweek($selected = '', $htmlname = 'weekid', $useempty = 0)
	{
		// phpcs:enable
		global $langs;

		$week = array(
			0 => $langs->trans("Day0"),
			1 => $langs->trans("Day1"),
			2 => $langs->trans("Day2"),
			3 => $langs->trans("Day3"),
			4 => $langs->trans("Day4"),
			5 => $langs->trans("Day5"),
			6 => $langs->trans("Day6")
		);

		$select_week = '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($useempty) {
			$select_week .= '<option value="-1">&nbsp;</option>';
		}
		foreach ($week as $key => $val) {
			if ($selected == $key) {
				$select_week .= '<option value="'.$key.'" selected>';
			} else {
				$select_week .= '<option value="'.$key.'">';
			}
			$select_week .= $val;
			$select_week .= '</option>';
		}
		$select_week .= '</select>';

		$select_week .= ajax_combobox($htmlname);

		return $select_week;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Return HTML combo list of month
	 *
	 *      @param  string      $selected          	Preselected value
	 *      @param  string      $htmlname          	Name of HTML select object
	 *      @param  int         $useempty          	Show empty in list
	 *      @param  int         $longlabel         	Show long label
	 *      @param	string		$morecss			More Css
	 *  	@param  bool		$addjscombo			Add js combo
	 *      @return string
	 */
	public function select_month($selected = '', $htmlname = 'monthid', $useempty = 0, $longlabel = 0, $morecss = 'minwidth50 maxwidth75imp valignmiddle', $addjscombo = false)
	{
		// phpcs:enable
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		if ($longlabel) {
			$montharray = monthArray($langs, 0); // Get array
		} else {
			$montharray = monthArray($langs, 1);
		}

		$select_month = '<select class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($useempty) {
			$select_month .= '<option value="0">&nbsp;</option>';
		}
		foreach ($montharray as $key => $val) {
			if ($selected == $key) {
				$select_month .= '<option value="'.$key.'" selected>';
			} else {
				$select_month .= '<option value="'.$key.'">';
			}
			$select_month .= $val;
			$select_month .= '</option>';
		}
		$select_month .= '</select>';

		// Add code for jquery to use multiselect
		if ($addjscombo) {
			// Enhance with select2
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$select_month .= ajax_combobox($htmlname);
		}

		return $select_month;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return HTML combo list of years
	 *
	 *  @param  string		$selected       Preselected value (''=current year, -1=none, year otherwise)
	 *  @param  string		$htmlname       Name of HTML select object
	 *  @param  int			$useempty       Affiche valeur vide dans liste
	 *  @param  int			$min_year       Offset of minimum year into list (by default current year -10)
	 *  @param  int		    $max_year		Offset of maximum year into list (by default current year + 5)
	 *  @param	int			$offset			Offset
	 *  @param	int			$invert			Invert
	 *  @param	string		$option			Option
	 *  @param	string		$morecss		More CSS
	 *  @param  bool		$addjscombo		Add js combo
	 *  @return	void
	 *  @deprecated
	 */
	public function select_year($selected = '', $htmlname = 'yearid', $useempty = 0, $min_year = 10, $max_year = 5, $offset = 0, $invert = 0, $option = '', $morecss = 'valignmiddle maxwidth75imp', $addjscombo = false)
	{
		// phpcs:enable
		print $this->selectyear($selected, $htmlname, $useempty, $min_year, $max_year, $offset, $invert, $option, $morecss, $addjscombo);
	}

	/**
	 *	Return HTML combo list of years
	 *
	 *  @param  string	$selected       Preselected value (''=current year, -1=none, year otherwise)
	 *  @param  string	$htmlname       Name of HTML select object
	 *  @param  int	    $useempty       Affiche valeur vide dans liste
	 *  @param  int	    $min_year		Offset of minimum year into list (by default current year -10)
	 *  @param  int	    $max_year       Offset of maximum year into list (by default current year + 5)
	 *  @param	int		$offset			Offset
	 *  @param	int		$invert			Invert
	 *  @param	string	$option			Option
	 *  @param	string	$morecss		More css
	 *  @param  bool	$addjscombo		Add js combo
	 *  @return	string
	 */
	public function selectyear($selected = '', $htmlname = 'yearid', $useempty = 0, $min_year = 10, $max_year = 5, $offset = 0, $invert = 0, $option = '', $morecss = 'valignmiddle width75', $addjscombo = false)
	{
		$out = '';

		$currentyear = idate("Y") + $offset;
		$max_year = $currentyear + $max_year;
		$min_year = $currentyear - $min_year;
		if (empty($selected) && empty($useempty)) {
			$selected = $currentyear;
		}

		$out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.' >';
		if ($useempty) {
			$selected_html = '';
			if ($selected == '') {
				$selected_html = ' selected';
			}
			$out .= '<option value=""'.$selected_html.'>&nbsp;</option>';
		}
		if (!$invert) {
			for ($y = $max_year; $y >= $min_year; $y--) {
				$selected_html = '';
				if ($selected > 0 && $y == $selected) {
					$selected_html = ' selected';
				}
				$out .= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
			}
		} else {
			for ($y = $min_year; $y <= $max_year; $y++) {
				$selected_html = '';
				if ($selected > 0 && $y == $selected) {
					$selected_html = ' selected';
				}
				$out .= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
			}
		}
		$out .= "</select>\n";

		// Add code for jquery to use multiselect
		if ($addjscombo) {
			// Enhance with select2
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlname);
		}

		return $out;
	}


	/**
	 * 	Get array with HTML tabs with widgets/boxes of a particular area including personalized choices of user.
	 *  Class 'Form' must be known.
	 *
	 * 	@param	   User         $user		 Object User
	 * 	@param	   string       $areacode    Code of area for pages - 0 = Home page ... See getListOfPagesForBoxes()
	 *	@return    array                     array('selectboxlist'=>, 'boxactivated'=>, 'boxlista'=>, 'boxlistb'=>)
	 */
	public static function getBoxesArea($user, $areacode)
	{
		global $conf, $langs, $db;

		include_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

		$confuserzone = 'MAIN_BOXES_'.$areacode;

		// $boxactivated will be array of boxes enabled into global setup
		// $boxidactivatedforuser will be array of boxes chose by user

		$selectboxlist = '';
		$boxactivated = InfoBox::listBoxes($db, 'activated', $areacode, (empty($user->conf->$confuserzone) ? null : $user), array(), 0); // Search boxes of common+user (or common only if user has no specific setup)

		$boxidactivatedforuser = array();
		foreach ($boxactivated as $box) {
			if (empty($user->conf->$confuserzone) || $box->fk_user == $user->id) {
				$boxidactivatedforuser[$box->id] = $box->id; // We keep only boxes to show for user
			}

			if (!empty($box->lang)) {
				$langs->loadLangs(array($box->lang));
				$box->boxlabel = $langs->transnoentitiesnoconv($box->boxlabel);
			}
		}


		// Define selectboxlist
		$arrayboxtoactivatelabel = array();
		if (!empty($user->conf->$confuserzone)) {
			$boxorder = '';
			$langs->load("boxes"); // Load label of boxes
			foreach ($boxactivated as $box) {
				if (!empty($boxidactivatedforuser[$box->id])) {
					continue; // Already visible for user
				}

				$label = $langs->transnoentitiesnoconv($box->boxlabel);
				//if (preg_match('/graph/',$box->class)) $label.=' ('.$langs->trans("Graph").')';
				if (preg_match('/graph/', $box->class) && $conf->browser->layout != 'phone') {
					$label .= ' <span class="fas fa-chart-bar"></span>';
				}
				$arrayboxtoactivatelabel[$box->id] = array('label' => $label, 'data-html' => img_picto('', $box->boximg, 'class="pictofixedwidth"').$langs->trans($label)); // We keep only boxes not shown for user, to show into combo list
			}
			foreach ($boxidactivatedforuser as $boxid) {
				if (empty($boxorder)) {
					$boxorder .= 'A:';
				}
				$boxorder .= $boxid.',';
			}

			//var_dump($boxidactivatedforuser);

			// Class Form must have been already loaded
			$selectboxlist .= '<!-- Form with select box list -->'."\n";
			$selectboxlist .= '<form id="addbox" name="addbox" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$selectboxlist .= '<input type="hidden" name="token" value="'.newToken().'">';
			$selectboxlist .= '<input type="hidden" name="addbox" value="addbox">';
			$selectboxlist .= '<input type="hidden" name="userid" value="'.$user->id.'">';
			$selectboxlist .= '<input type="hidden" name="areacode" value="'.$areacode.'">';
			$selectboxlist .= '<input type="hidden" name="boxorder" value="'.$boxorder.'">';
			$selectboxlist .= Form::selectarray('boxcombo', $arrayboxtoactivatelabel, -1, $langs->trans("ChooseBoxToAdd").'...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth300 hideonprint', 0, 'hidden selected', 0, 0);
			if (empty($conf->use_javascript_ajax)) {
				$selectboxlist .= ' <input type="submit" class="button" value="'.$langs->trans("AddBox").'">';
			}
			$selectboxlist .= '</form>';
			if (!empty($conf->use_javascript_ajax)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$selectboxlist .= ajax_combobox("boxcombo");
			}
		}

		// Javascript code for dynamic actions
		if (!empty($conf->use_javascript_ajax)) {
			$selectboxlist .= '<script nonce="'.getNonce().'" type="text/javascript">

	        // To update list of activated boxes
	        function updateBoxOrder(closing) {
	        	var left_list = cleanSerialize(jQuery("#boxhalfleft").sortable("serialize"));
	        	var right_list = cleanSerialize(jQuery("#boxhalfright").sortable("serialize"));
	        	var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
	        	if (boxorder==\'A:A-B:B\' && closing == 1)	// There is no more boxes on screen, and we are after a delete of a box so we must hide title
	        	{
	        		jQuery.ajax({
	        			url: \''.DOL_URL_ROOT.'/core/ajax/box.php?closing=1&boxorder=\'+boxorder+\'&zone='.$areacode.'&userid=\'+'.$user->id.',
	        			async: false
	        		});
	        		// We force reload to be sure to get all boxes into list
	        		window.location.search=\'mainmenu='.GETPOST("mainmenu", "aZ09").'&leftmenu='.GETPOST('leftmenu', "aZ09").'&action=delbox&token='.newToken().'\';
	        	}
	        	else
	        	{
	        		jQuery.ajax({
	        			url: \''.DOL_URL_ROOT.'/core/ajax/box.php?closing=\'+closing+\'&boxorder=\'+boxorder+\'&zone='.$areacode.'&userid=\'+'.$user->id.',
	        			async: true
	        		});
	        	}
	        }

	        jQuery(document).ready(function() {
	        	jQuery("#boxcombo").change(function() {
	        	var boxid=jQuery("#boxcombo").val();
	        		if (boxid > 0) {
						console.log("A box widget has been selected for addition, we call ajax page to add it.")
	            		var left_list = cleanSerialize(jQuery("#boxhalfleft").sortable("serialize"));
	            		var right_list = cleanSerialize(jQuery("#boxhalfright").sortable("serialize"));
	            		var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
	    				jQuery.ajax({
	    					url: \''.DOL_URL_ROOT.'/core/ajax/box.php?boxorder=\'+boxorder+\'&boxid=\'+boxid+\'&zone='.$areacode.'&userid='.$user->id.'\'
	    		        }).done(function() {
	        				window.location.search=\'mainmenu='.GETPOST("mainmenu", "aZ09").'&leftmenu='.GETPOST('leftmenu', "aZ09").'\';
						});
	                }
	        	});';
			if (!count($arrayboxtoactivatelabel)) {
				$selectboxlist .= 'jQuery("#boxcombo").hide();';
			}
			$selectboxlist .= '

	        	jQuery("#boxhalfleft, #boxhalfright").sortable({
	    	    	handle: \'.boxhandle\',
	    	    	revert: \'invalid\',
	       			items: \'.boxdraggable\',
					containment: \'document\',
	        		connectWith: \'#boxhalfleft, #boxhalfright\',
	        		stop: function(event, ui) {
		        		console.log("We moved box so we call updateBoxOrder with ajax actions");
	        			updateBoxOrder(1);  /* 1 to avoid message after a move */
	        		}
	    		});

	        	jQuery(".boxclose").click(function() {
	        		var self = this;	// because JQuery can modify this
	        		var boxid = self.id.substring(8);
					if (boxid > 0) {
		        		var label = jQuery(\'#boxlabelentry\'+boxid).val();
		        		console.log("We close box "+boxid);
	    	    		jQuery(\'#boxto_\'+boxid).remove();
	        			jQuery(\'#boxcombo\').append(new Option(label, boxid));
	        			updateBoxOrder(1);  /* 1 to avoid message after a remove */
					}
	        	});

        	});'."\n";

			$selectboxlist .= '</script>'."\n";
		}

		// Define boxlista and boxlistb
		$boxlista = '';
		$boxlistb = '';
		$nbboxactivated = count($boxidactivatedforuser);

		if ($nbboxactivated) {
			// Load translation files required by the page
			$langs->loadLangs(array("boxes", "projects"));

			$emptybox = new ModeleBoxes($db);

			$boxlista .= "\n<!-- Box left container -->\n";

			// Define $box_max_lines
			$box_max_lines = getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));

			$ii = 0;
			foreach ($boxactivated as $key => $box) {
				if ((!empty($user->conf->$confuserzone) && $box->fk_user == 0) || (empty($user->conf->$confuserzone) && $box->fk_user != 0)) {
					continue;
				}
				if (empty($box->box_order) && $ii < ($nbboxactivated / 2)) {
					$box->box_order = 'A'.sprintf("%02d", ($ii + 1)); // When box_order was not yet set to Axx or Bxx and is still 0
				}
				if (preg_match('/^A/i', $box->box_order)) { // column A
					$ii++;
					//print 'box_id '.$boxactivated[$ii]->box_id.' ';
					//print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
					// Show box
					$box->loadBox($box_max_lines);
					$boxlista .= $box->showBox(null, null, 1);
				}
			}

			if ($conf->browser->layout != 'phone') {
				$emptybox->box_id = 'A';
				$emptybox->info_box_head = array();
				$emptybox->info_box_contents = array();
				$boxlista .= $emptybox->showBox(array(), array(), 1);
			}
			$boxlista .= "<!-- End box left container -->\n";

			$boxlistb .= "\n<!-- Box right container -->\n";

			$ii = 0;
			foreach ($boxactivated as $key => $box) {
				if ((!empty($user->conf->$confuserzone) && $box->fk_user == 0) || (empty($user->conf->$confuserzone) && $box->fk_user != 0)) {
					continue;
				}
				if (empty($box->box_order) && $ii < ($nbboxactivated / 2)) {
					$box->box_order = 'B'.sprintf("%02d", ($ii + 1)); // When box_order was not yet set to Axx or Bxx and is still 0
				}
				if (preg_match('/^B/i', $box->box_order)) { // colonne B
					$ii++;
					//print 'box_id '.$boxactivated[$ii]->box_id.' ';
					//print 'box_order '.$boxactivated[$ii]->box_order.'<br>';
					// Show box
					$box->loadBox($box_max_lines);
					$boxlistb .= $box->showBox(null, null, 1);
				}
			}

			if ($conf->browser->layout != 'phone') {
				$emptybox->box_id = 'B';
				$emptybox->info_box_head = array();
				$emptybox->info_box_contents = array();
				$boxlistb .= $emptybox->showBox(array(), array(), 1);
			}

			$boxlistb .= "<!-- End box right container -->\n";
		}

		return array('selectboxlist' => count($boxactivated) ? $selectboxlist : '', 'boxactivated' => $boxactivated, 'boxlista' => $boxlista, 'boxlistb' => $boxlistb);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return a HTML select list of a dictionary
	 *
	 *  @param  string	$htmlname          	Name of select zone
	 *  @param	string	$dictionarytable	Dictionary table
	 *  @param	string	$keyfield			Field for key
	 *  @param	string	$labelfield			Label field
	 *  @param	string	$selected			Selected value
	 *  @param  int		$useempty          	1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
	 *  @param  string  $moreattrib         More attributes on HTML select tag
	 * 	@return	void
	 */
	public function select_dictionary($htmlname, $dictionarytable, $keyfield = 'code', $labelfield = 'label', $selected = '', $useempty = 0, $moreattrib = '')
	{
		// phpcs:enable
		global $langs, $conf;

		$langs->load("admin");

		$sql = "SELECT rowid, ".$keyfield.", ".$labelfield;
		$sql .= " FROM ".$this->db->prefix().$dictionarytable;
		$sql .= " ORDER BY ".$labelfield;

		dol_syslog(get_class($this)."::select_dictionary", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				print '<select id="select'.$htmlname.'" class="flat selectdictionary" name="'.$htmlname.'"'.($moreattrib ? ' '.$moreattrib : '').'>';
				if ($useempty == 1 || ($useempty == 2 && $num > 1)) {
					print '<option value="-1">&nbsp;</option>';
				}

				while ($i < $num) {
					$obj = $this->db->fetch_object($result);
					if ($selected == $obj->rowid || $selected == $obj->{$keyfield}) {
						print '<option value="'.$obj->{$keyfield}.'" selected>';
					} else {
						print '<option value="'.$obj->{$keyfield}.'">';
					}
					$label = ($langs->trans($dictionarytable.$obj->{$keyfield}) != $dictionarytable.$obj->{$labelfield} ? $langs->trans($dictionarytable.$obj->{$keyfield}) : $obj->{$labelfield});
					print $label;
					print '</option>';
					$i++;
				}
				print "</select>";
			} else {
				print $langs->trans("DictionaryEmpty");
			}
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Return an html string with a select combo box to choose yes or no
	 *
	 *	@param	string		$htmlname		Name of html select field
	 *	@param	string		$value			Pre-selected value
	 *	@param	int			$option			0 return automatic/manual, 1 return 1/0
	 *	@param	bool		$disabled		true or false
	 *  @param	int      	$useempty		1=Add empty line
	 *	@return	string						See option
	 */
	public function selectAutoManual($htmlname, $value = '', $option = 0, $disabled = false, $useempty = 0)
	{
		global $langs;

		$automatic = "automatic";
		$manual = "manual";
		if ($option) {
			$automatic = "1";
			$manual = "0";
		}

		$disabled = ($disabled ? ' disabled' : '');

		$resultautomanual = '<select class="flat width100" id="'.$htmlname.'" name="'.$htmlname.'"'.$disabled.'>'."\n";
		if ($useempty) {
			$resultautomanual .= '<option value="-1"'.(($value < 0) ? ' selected' : '').'>&nbsp;</option>'."\n";
		}
		if (("$value" == 'automatic') || ($value == 1)) {
			$resultautomanual .= '<option value="'.$automatic.'" selected>'.$langs->trans("Automatic").'</option>'."\n";
			$resultautomanual .= '<option value="'.$manual.'">'.$langs->trans("Manual").'</option>'."\n";
		} else {
			$selected = (($useempty && $value != '0' && $value != 'manual') ? '' : ' selected');
			$resultautomanual .= '<option value="'.$automatic.'">'.$langs->trans("Automatic").'</option>'."\n";
			$resultautomanual .= '<option value="'.$manual.'"'.$selected.'>'.$langs->trans("Manual").'</option>'."\n";
		}
		$resultautomanual .= '</select>'."\n";
		return $resultautomanual;
	}


	/**
	 * Return HTML select list to select a group by field
	 *
	 * @param 	mixed	$object				Object analyzed
	 * @param	array	$search_groupby		Array of preselected fields
	 * @param	array	$arrayofgroupby		Array of groupby to fill
	 * @param	string	$morecss			More CSS
	 * @param	string  $showempty          '1' or 'text'
	 * @return string						HTML string component
	 */
	public function selectGroupByField($object, $search_groupby, &$arrayofgroupby, $morecss = 'minwidth200 maxwidth250', $showempty = '1')
	{
		global $langs, $extrafields, $form;

		$arrayofgroupbylabel = array();
		foreach ($arrayofgroupby as $key => $val) {
			$arrayofgroupbylabel[$key] = $val['label'];
		}
		$result = $form->selectarray('search_groupby', $arrayofgroupbylabel, $search_groupby, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $result;
	}

	/**
	 * Return HTML select list to select a group by field
	 *
	 * @param 	mixed	$object				Object analyzed
	 * @param	array	$search_xaxis		Array of preselected fields
	 * @param	array	$arrayofxaxis		Array of groupby to fill
	 * @param	string  $showempty          '1' or 'text'
	 * @param	string	$morecss			More css
	 * @return 	string						HTML string component
	 */
	public function selectXAxisField($object, $search_xaxis, &$arrayofxaxis, $showempty = '1', $morecss = 'minwidth250 maxwidth500')
	{
		global $form;

		$arrayofxaxislabel = array();
		foreach ($arrayofxaxis as $key => $val) {
			$arrayofxaxislabel[$key] = $val['label'];
		}
		$result = $form->selectarray('search_xaxis', $arrayofxaxislabel, $search_xaxis, $showempty, 0, 0, '', 0, 0, 0, '', $morecss, 1);

		return $result;
	}
}
