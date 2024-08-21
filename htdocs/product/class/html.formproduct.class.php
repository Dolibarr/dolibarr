<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2017 Francis Appels       <francis.appels@yahoo.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/product/class/html.formproduct.class.php
 *	\brief      File for class with methods for building product related HTML components
 */

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

/**
 *	Class with static methods for building HTML components related to products
 *	Only components common to products and services must be here.
 */
class FormProduct
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	// Cache arrays
	public $cache_warehouses = array();
	public $cache_lot = array();
	public $cache_workstations = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Load in cache array list of warehouses
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		    $fk_product			Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @param	string	    $batch				Add quantity of batch stock in label for product with batch name batch, batch name precedes batch_id. Nothing if ''.
	 * @param	string	    $status				warehouse status filter, following comma separated filter options can be used
	 *                      				    'warehouseopen' = select products from open warehouses,
	 *                      				    'warehouseclosed' = select products from closed warehouses,
	 *                      				    'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 * @param	boolean	    $sumStock		    sum total stock of a warehouse, default true
	 * @param	array       $exclude            warehouses ids to exclude
	 * @param   bool|int    $stockMin           [=false] Value of minimum stock to filter (only warehouse with stock > stockMin are loaded) or false not not filter by minimum stock
	 * @param   string      $orderBy            [='e.ref'] Order by
	 * @return  int                             Nb of loaded lines, 0 if already loaded, <0 if KO
	 * @throws  Exception
	 */
	public function loadWarehouses($fk_product = 0, $batch = '', $status = '', $sumStock = true, $exclude = array(), $stockMin = false, $orderBy = 'e.ref')
	{
		global $conf, $langs;

		if (empty($fk_product) && count($this->cache_warehouses)) {
			return 0; // Cache already loaded and we do not want a list with information specific to a product
		}

		$warehouseStatus = array();

		if (preg_match('/warehouseclosed/', $status)) {
			$warehouseStatus[] = Entrepot::STATUS_CLOSED;
		}
		if (preg_match('/warehouseopen/', $status)) {
			$warehouseStatus[] = Entrepot::STATUS_OPEN_ALL;
		}
		if (preg_match('/warehouseinternal/', $status)) {
			$warehouseStatus[] = Entrepot::STATUS_OPEN_INTERNAL;
		}

		$sql = "SELECT e.rowid, e.ref as label, e.description, e.fk_parent";
		if (!empty($fk_product) && $fk_product > 0) {
			if (!empty($batch)) {
				$sql .= ", pb.qty as stock";
			} else {
				$sql .= ", ps.reel as stock";
			}
		} elseif ($sumStock) {
			$sql .= ", sum(ps.reel) as stock";
		}
		$sql .= " FROM ".$this->db->prefix()."entrepot as e";
		$sql .= " LEFT JOIN ".$this->db->prefix()."product_stock as ps on ps.fk_entrepot = e.rowid";
		if (!empty($fk_product) && $fk_product > 0) {
			$sql .= " AND ps.fk_product = ".((int) $fk_product);
			if (!empty($batch)) {
				$sql .= " LEFT JOIN ".$this->db->prefix()."product_batch as pb on pb.fk_product_stock = ps.rowid AND pb.batch = '".$this->db->escape($batch)."'";
			}
		}
		$sql .= " WHERE e.entity IN (".getEntity('stock').")";
		if (count($warehouseStatus)) {
			$sql .= " AND e.statut IN (".$this->db->sanitize(implode(',', $warehouseStatus)).")";
		} else {
			$sql .= " AND e.statut = 1";
		}

		if (is_array($exclude) && !empty($exclude)) {
			$sql .= ' AND e.rowid NOT IN('.$this->db->sanitize(implode(',', $exclude)).')';
		}

		// minimum stock
		if ($stockMin !== false) {
			if (!empty($fk_product) && $fk_product > 0) {
				if (!empty($batch)) {
					$sql .= " AND pb.qty > ".((float) $stockMin);
				} else {
					$sql .= " AND ps.reel > ".((float) $stockMin);
				}
			}
		}

		if ($sumStock && empty($fk_product)) {
			$sql .= " GROUP BY e.rowid, e.ref, e.description, e.fk_parent";

			// minimum stock
			if ($stockMin !== false) {
				$sql .= " HAVING sum(ps.reel) > ".((float) $stockMin);
			}
		}
		$sql .= " ORDER BY ".$orderBy;

		dol_syslog(get_class($this).'::loadWarehouses', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($sumStock) {
					$obj->stock = price2num($obj->stock, 5);
				}
				$this->cache_warehouses[$obj->rowid]['id'] = $obj->rowid;
				$this->cache_warehouses[$obj->rowid]['label'] = $obj->label;
				$this->cache_warehouses[$obj->rowid]['parent_id'] = $obj->fk_parent;
				$this->cache_warehouses[$obj->rowid]['description'] = $obj->description;
				$this->cache_warehouses[$obj->rowid]['stock'] = $obj->stock;
				$i++;
			}

			// Full label init
			foreach ($this->cache_warehouses as $obj_rowid => $tab) {
				$this->cache_warehouses[$obj_rowid]['full_label'] = $this->get_parent_path($tab);
			}

			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Load in cache array list of workstations
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		    $fk_product			Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @param	array       $exclude            warehouses ids to exclude
	 * @param   string      $orderBy            [='e.ref'] Order by
	 * @return  int                             Nb of loaded lines, 0 if already loaded, <0 if KO
	 * @throws  Exception
	 */
	public function loadWorkstations($fk_product = 0, $exclude = array(), $orderBy = 'w.ref')
	{
		global $conf, $langs;

		if (empty($fk_product) && count($this->cache_workstations)) {
			return 0; // Cache already loaded and we do not want a list with information specific to a product
		}

		$sql = "SELECT w.rowid, w.ref as ref, w.label as label, w.type, w.nb_operators_required,w.thm_operator_estimated,w.thm_machine_estimated";
		$sql .= " FROM ".$this->db->prefix()."workstation_workstation as w";
		$sql .= " WHERE 1 = 1";
		if (!empty($fk_product) && $fk_product > 0) {
			$sql .= " AND w.fk_product = ".((int) $fk_product);
		}
		$sql .= " AND w.entity IN (".getEntity('workstation').")";

		if (is_array($exclude) && !empty($exclude)) {
			$sql .= ' AND w.rowid NOT IN('.$this->db->sanitize(implode(',', $exclude)).')';
		}

		$sql .= " ORDER BY ".$orderBy;

		dol_syslog(get_class($this).'::loadWorkstations', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$this->cache_workstations[$obj->rowid]['id'] = $obj->rowid;
				$this->cache_workstations[$obj->rowid]['ref'] = $obj->ref;
				$this->cache_workstations[$obj->rowid]['label'] = $obj->label;
				$this->cache_workstations[$obj->rowid]['type'] = $obj->type;
				$this->cache_workstations[$obj->rowid]['nb_operators_required'] = $obj->nb_operators_required;
				$this->cache_workstations[$obj->rowid]['thm_operator_estimated'] = $obj->thm_operator_estimated;
				$this->cache_workstations[$obj->rowid]['thm_machine_estimated'] = $obj->thm_machine_estimated;
				$i++;
			}

			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return full path to current warehouse in $tab (recursive function)
	 *
	 * @param	array	$tab			warehouse data in $this->cache_warehouses line
	 * @param	string	$final_label	full label with all parents, separated by ' >> ' (completed on each call)
	 * @return	string					full label with all parents, separated by ' >> '
	 */
	private function get_parent_path($tab, $final_label = '')
	{
		//phpcs:enable
		if (empty($final_label)) {
			$final_label = $tab['label'];
		}

		if (empty($tab['parent_id'])) {
			return $final_label;
		} else {
			if (!empty($this->cache_warehouses[$tab['parent_id']])) {
				$final_label = $this->cache_warehouses[$tab['parent_id']]['label'].' >> '.$final_label;
				return $this->get_parent_path($this->cache_warehouses[$tab['parent_id']], $final_label);
			}
		}

		return $final_label;
	}

	/**
	 *  Return list of warehouses
	 *
	 *  @param  string|int|array  $selected     Id of preselected warehouse ('' or '-1' for no value, 'ifone' and 'ifonenodefault' = select value if one value otherwise no value, '-2' to use the default value from setup)
	 *  @param  string      $htmlname           Name of html select html
	 *  @param  string      $filterstatus       warehouse status filter, following comma separated filter options can be used
	 *                                          'warehouseopen' = select products from open warehouses,
	 *                                          'warehouseclosed' = select products from closed warehouses,
	 *                                          'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 *  @param  int		    $empty			    1=Can be empty, 0 if not
	 * 	@param	int		    $disabled		    1=Select is disabled
	 * 	@param	int		    $fk_product		    Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *  @param	string	    $empty_label	    Empty label if needed (only if $empty=1)
	 *  @param	int		    $showstock		    1=Show stock count
	 *  @param	int	    	$forcecombo		    1=Force combo iso ajax select2
	 *  @param	array	    $events			    Events to add to select2
	 *  @param  string      $morecss            Add more css classes to HTML select
	 *  @param	array	    $exclude            Warehouses ids to exclude
	 *  @param  int         $showfullpath       1=Show full path of name (parent ref into label), 0=Show only ref of current warehouse
	 *  @param  bool|int    $stockMin           [=false] Value of minimum stock to filter (only warehouse with stock > stockMin are loaded) or false not not filter by minimum stock
	 *  @param  string      $orderBy            [='e.ref'] Order by
	 *  @param	int			$multiselect		1=Allow multiselect
	 * 	@return string					        HTML select
	 *
	 *  @throws Exception
	 */
	public function selectWarehouses($selected = '', $htmlname = 'idwarehouse', $filterstatus = '', $empty = 0, $disabled = 0, $fk_product = 0, $empty_label = '', $showstock = 0, $forcecombo = 0, $events = array(), $morecss = 'minwidth200', $exclude = array(), $showfullpath = 1, $stockMin = false, $orderBy = 'e.ref', $multiselect = 0)
	{
		global $conf, $langs, $user, $hookmanager;

		dol_syslog(get_class($this)."::selectWarehouses " . (is_array($selected) ? 'selected is array' : $selected) . ", $htmlname, $filterstatus, $empty, $disabled, $fk_product, $empty_label, $showstock, $forcecombo, $morecss", LOG_DEBUG);

		$out = '';
		if (!getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
			$filterstatus = '';
		}
		if (!empty($fk_product) && $fk_product > 0) {
			$this->cache_warehouses = array();
		}

		$this->loadWarehouses($fk_product, '', $filterstatus, true, $exclude, $stockMin, $orderBy);
		$nbofwarehouses = count($this->cache_warehouses);

		if ($conf->use_javascript_ajax && !$forcecombo) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out .= $comboenhancement;
		}

		if (strpos($htmlname, 'search_') !== 0) {
			if (empty($user->fk_warehouse) || $user->fk_warehouse == -1) {
				if (is_scalar($selected) && ($selected == '-2' || $selected == 'ifone') && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE')) {
					$selected = getDolGlobalString('MAIN_DEFAULT_WAREHOUSE');
				}
			} else {
				if (is_scalar($selected) && ($selected == '-2' || $selected == 'ifone') && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER')) {
					$selected = $user->fk_warehouse;
				}
			}
		}

		$out .= '<select '.($multiselect ? 'multiple ' : '').'class="flat'.($morecss ? ' '.$morecss : '').'"'.($disabled ? ' disabled' : '');
		$out .= ' id="'.$htmlname.'" name="'.($htmlname.($multiselect ? '[]' : '').($disabled ? '_disabled' : '')).'"';
		//$out .= ' placeholder="todo"'; 	// placeholder for select2 must be added by setting the id+placeholder js param when calling select2
		$out .= '>';
		if ($empty) {
			$out .= '<option value="-1">'.($empty_label ? $empty_label : '&nbsp;').'</option>';
		}
		foreach ($this->cache_warehouses as $id => $arraytypes) {
			$label = '';
			if ($showfullpath) {
				$label .= $arraytypes['full_label'];
			} else {
				$label .= $arraytypes['label'];
			}
			if (($fk_product || ($showstock > 0)) && ($arraytypes['stock'] != 0 || ($showstock > 0))) {
				if ($arraytypes['stock'] <= 0) {
					$label .= ' <span class="text-warning">('.$langs->trans("Stock").':'.$arraytypes['stock'].')</span>';
				} else {
					$label .= ' <span class="opacitymedium">('.$langs->trans("Stock").':'.$arraytypes['stock'].')</span>';
				}
			}

			$out .= '<option value="'.$id.'"';
			if (is_array($selected)) {
				if (in_array($id, $selected)) {
					$out .= ' selected';
				}
			} else {
				if ($selected == $id || (!empty($selected) && preg_match('/^ifone/', $selected) && $nbofwarehouses == 1)) {
					$out .= ' selected';
				}
			}
			$out .= ' data-html="'.dol_escape_htmltag($label).'"';
			$out .= '>';
			$out .= $label;
			$out .= '</option>';
		}
		$out .= '</select>';
		if ($disabled) {
			$out .= '<input type="hidden" name="'.$htmlname.'" value="'.(($selected > 0) ? $selected : '').'">';
		}

		$parameters = array(
			'selected' => $selected,
			'htmlname' => $htmlname,
			'filterstatus' => $filterstatus,
			'empty' => $empty,
			'disabled ' => $disabled,
			'fk_product' => $fk_product,
			'empty_label' => $empty_label,
			'showstock' => $showstock,
			'forcecombo' => $forcecombo,
			'events' => $events,
			'morecss' => $morecss,
			'exclude' => $exclude,
			'showfullpath' => $showfullpath,
			'stockMin' => $stockMin,
			'orderBy' => $orderBy
		);

		$reshook = $hookmanager->executeHooks('selectWarehouses', $parameters, $this);
		if ($reshook > 0) {
			$out = $hookmanager->resPrint;
		} elseif ($reshook == 0) {
			$out .= $hookmanager->resPrint;
		}

		return $out;
	}

	/**
	 *  Return list of workstations
	 *
	 *  @param  string|int  $selected           Id of preselected warehouse ('' or '-1' for no value, 'ifone' and 'ifonenodefault' = select value if one value otherwise no value, '-2' to use the default value from setup)
	 *  @param  string      $htmlname           Name of html select html
	 *  @param  int		    $empty			    1=Can be empty, 0 if not
	 * 	@param	int		    $disabled		    1=Select is disabled
	 * 	@param	int		    $fk_product		    Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *  @param	string	    $empty_label	    Empty label if needed (only if $empty=1)
	 *  @param	int	    	$forcecombo		    1=Force combo iso ajax select2
	 *  @param	array	    $events			            Events to add to select2
	 *  @param  string      $morecss                    Add more css classes to HTML select
	 *  @param	array	    $exclude            Warehouses ids to exclude
	 *  @param  int         $showfullpath       1=Show full path of name (parent ref into label), 0=Show only ref of current warehouse
	 *  @param  string      $orderBy            [='e.ref'] Order by
	 * 	@return string					        HTML select
	 *
	 *  @throws Exception
	 */
	public function selectWorkstations($selected = '', $htmlname = 'idworkstations', $empty = 0, $disabled = 0, $fk_product = 0, $empty_label = '', $forcecombo = 0, $events = array(), $morecss = 'minwidth200', $exclude = array(), $showfullpath = 1, $orderBy = 'e.ref')
	{
		global $conf, $langs, $user, $hookmanager;

		dol_syslog(get_class($this)."::selectWorkstations $selected, $htmlname, $empty, $disabled, $fk_product, $empty_label, $forcecombo, $morecss", LOG_DEBUG);

		$filterstatus = '';
		$out = '';
		if (!empty($fk_product) && $fk_product > 0) {
			$this->cache_workstations = array();
		}

		$this->loadWorkstations($fk_product);
		$nbofworkstations = count($this->cache_workstations);

		if ($conf->use_javascript_ajax && !$forcecombo) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out .= $comboenhancement;
		}

		if (strpos($htmlname, 'search_') !== 0) {
			if (empty($user->fk_workstation) || $user->fk_workstation == -1) {
				if (($selected == '-2' || $selected == 'ifone') && getDolGlobalString('MAIN_DEFAULT_WORKSTATION')) {
					$selected = getDolGlobalString('MAIN_DEFAULT_WORKSTATION');
				}
			} else {
				if (($selected == '-2' || $selected == 'ifone') && getDolGlobalString('MAIN_DEFAULT_WORKSTATION')) {
					$selected = $user->fk_workstation;
				}
			}
		}

		$out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'"'.($disabled ? ' disabled' : '').' id="'.$htmlname.'" name="'.($htmlname.($disabled ? '_disabled' : '')).'">';
		if ($empty) {
			$out .= '<option value="-1">'.($empty_label ? $empty_label : '&nbsp;').'</option>';
		}
		foreach ($this->cache_workstations as $id => $arraytypes) {
			$label = $arraytypes['label'];

			$out .= '<option value="'.$id.'"';
			if ($selected == $id || (preg_match('/^ifone/', $selected) && $nbofworkstations == 1)) {
				$out .= ' selected';
			}
			$out .= ' data-html="'.dol_escape_htmltag($label).'"';
			$out .= '>';
			$out .= $label;
			$out .= '</option>';
		}
		$out .= '</select>';
		if ($disabled) {
			$out .= '<input type="hidden" name="'.$htmlname.'" value="'.(($selected > 0) ? $selected : '').'">';
		}

		$parameters = array(
			'selected' => $selected,
			'htmlname' => $htmlname,
			'filterstatus' => $filterstatus,
			'empty' => $empty,
			'disabled ' => $disabled,
			'fk_product' => $fk_product,
			'empty_label' => $empty_label,
			'forcecombo' => $forcecombo,
			'events' => $events,
			'morecss' => $morecss,
			'exclude' => $exclude,
			'showfullpath' => $showfullpath,
			'orderBy' => $orderBy
		);

		$reshook = $hookmanager->executeHooks('selectWorkstations', $parameters, $this);
		if ($reshook > 0) {
			$out = $hookmanager->resPrint;
		} elseif ($reshook == 0) {
			$out .= $hookmanager->resPrint;
		}

		return $out;
	}

	/**
	 *    Display form to select warehouse
	 *
	 *    @param    string      $page        Page
	 *    @param    string|int  $selected    Id of warehouse
	 *    @param    string      $htmlname    Name of select html field
	 *    @param    int         $addempty    1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
	 *    @return   void
	 */
	public function formSelectWarehouses($page, $selected = '', $htmlname = 'warehouse_id', $addempty = 0)
	{
		global $langs;
		if ($htmlname != "none") {
			print '<form method="POST" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setwarehouse">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<table class="nobordernopadding">';
			print '<tr><td>';
			print $this->selectWarehouses($selected, $htmlname, '', $addempty);
			print '</td>';
			print '<td class="left"><input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		} else {
			if ($selected) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$warehousestatic = new Entrepot($this->db);
				$warehousestatic->fetch($selected);
				print $warehousestatic->getNomUrl();
			} else {
				print "&nbsp;";
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output a combo box with list of units
	 *  Currently the units are not define in the DB
	 *
	 *  @param	string		$name               Name of HTML field
	 *  @param	string		$measuring_style    Unit to show: weight, size, surface, volume, time
	 *  @param  string		$selected            Preselected value
	 * 	@param	int			$adddefault			Add empty unit called "Default"
	 *  @param  int         $mode               1=Use short label as value, 0=Use rowid
	 * 	@return	void
	 *  @deprecated
	 */
	public function select_measuring_units($name = 'measuring_units', $measuring_style = '', $selected = '0', $adddefault = 0, $mode = 0)
	{
		//phpcs:enable
		print $this->selectMeasuringUnits($name, $measuring_style, $selected, $adddefault, $mode);
	}

	/**
	 *  Return a combo box with list of units
	 *  Units labels are defined in llx_c_units
	 *
	 *  @param  string		$name                Name of HTML field
	 *  @param  string		$measuring_style     Unit to show: weight, size, surface, volume, time
	 *  @param  string		$selected            Preselected value
	 *  @param  int|string	$adddefault			 1=Add empty unit called "Default", ''=Add empty value
	 *  @param  int         $mode                1=Use short label as value, 0=Use rowid, 2=Use scale (power)
	 *  @param	string		$morecss			 More CSS
	 *  @return string|-1
	 */
	public function selectMeasuringUnits($name = 'measuring_units', $measuring_style = '', $selected = '0', $adddefault = 0, $mode = 0, $morecss = 'minwidth75 maxwidth125')
	{
		global $langs, $db;

		$langs->load("other");

		$return = '';

		// TODO Use a cache
		require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
		$measuringUnits = new CUnits($db);

		$filter = array();
		$filter['t.active'] = 1;
		if ($measuring_style) {
			$filter['t.unit_type'] = $measuring_style;
		}

		$result = $measuringUnits->fetchAll(
			'',
			'',
			0,
			0,
			$filter
		);
		if ($result < 0) {
			dol_print_error($db);
			return -1;
		} else {
			$return .= '<select class="flat'.($morecss ? ' '.$morecss : '').'" name="'.$name.'" id="'.$name.'">';
			if ($adddefault || $adddefault === '') {
				$return .= '<option value="0"'.($selected === '0' ? ' selected' : '').'>'.($adddefault ? '('.$langs->trans("Default").')' : '').'</option>';
			}

			foreach ($measuringUnits->records as $lines) {
				$return .= '<option value="';
				if ($mode == 1) {
					$return .= $lines->short_label;
				} elseif ($mode == 2) {
					$return .= $lines->scale;
				} else {
					$return .= $lines->id;
				}
				$return .= '"';
				if ($mode == 1 && $lines->short_label == $selected) {
					$return .= ' selected';
				} elseif ($mode == 2 && $lines->scale == $selected) {
					$return .= ' selected';
				} elseif ($mode == 0 && $lines->id == $selected) {
					$return .= ' selected';
				}
				$return .= '>';
				if ($measuring_style == 'time') {
					$return .= $langs->trans(ucfirst($lines->label));
				} else {
					$return .= $langs->trans($lines->label);
				}
				$return .= '</option>';
			}
			$return .= '</select>';
		}

		$return .= ajax_combobox($name);

		return $return;
	}

	/**
	 *  Return a combo box with list of units
	 *  NAture of product labels are defined in llx_c_product_nature
	 *
	 *  @param  string		$name                Name of HTML field
	 *  @param  string		$selected             Preselected value
	 *  @param  int         $mode                1=Use label as value, 0=Use code
	 *  @param  int         $showempty           1=show empty value, 0= no
	 *  @return string|int
	 */
	public function selectProductNature($name = 'finished', $selected = '', $mode = 0, $showempty = 1)
	{
		global $langs, $db;

		$langs->load('products');

		$return = '';

		// TODO Use a cache
		require_once DOL_DOCUMENT_ROOT.'/core/class/cproductnature.class.php';
		$productNature = new CProductNature($db);

		$filter = array();
		$filter['t.active'] = 1;

		$result = $productNature->fetchAll('', '', 0, 0, $filter);

		if ($result < 0) {
			dol_print_error($db);
			return -1;
		} else {
			$return .= '<select class="flat" name="'.$name.'" id="'.$name.'">';
			if ($showempty || ($selected == '' || $selected == '-1')) {
				$return .= '<option value="-1"';
				if ($selected == '' || $selected == '-1') {
					$return .= ' selected';
				}
				$return .= '></option>';
			}
			if (!empty($productNature->records) && is_array($productNature->records)) {
				foreach ($productNature->records as $lines) {
					$return .= '<option value="';
					if ($mode == 1) {
						$return .= $lines->label;
					} else {
						$return .= $lines->code;
					}

					$return .= '"';

					if ($mode == 1 && $lines->label == $selected) {
						$return .= ' selected';
					} elseif ($lines->code == $selected) {
						$return .= ' selected';
					}

					$return .= '>';
					$return .= $langs->trans($lines->label);
					$return .= '</option>';
				}
			}
			$return .= '</select>';
		}

		$return .= ajax_combobox($name);

		return $return;
	}

	/**
	 *  Return list of lot numbers (stock from product_batch) with stock location and stock qty
	 *
	 *  @param	string|int	$selected	Id of preselected lot stock id ('' for no value, 'ifone'=select value if one value otherwise no value)
	 *  @param  string	$htmlname		Name of html select html
	 *  @param  string	$filterstatus	lot status filter, following comma separated filter options can be used
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 * 	@param	int		$disabled		1=Select is disabled
	 * 	@param	int		$fk_product		show lot numbers of product with id fk_product. All from objectLines if 0.
	 * 	@param	int		$fk_entrepot	filter lot numbers for warehouse with id fk_entrepot. All if 0.
	 * 	@param	array	$objectLines	Only cache lot numbers for products in lines of object. If no lines only for fk_product. If no fk_product, all.
	 *  @param	string	$empty_label	Empty label if needed (only if $empty=1)
	 *  @param	int		$forcecombo		1=Force combo iso ajax select2
	 *  @param	array	$events			Events to add to select2
	 *  @param  string  $morecss		Add more css classes to HTML select
	 *
	 * 	@return	string					HTML select
	 */
	public function selectLotStock($selected = '', $htmlname = 'batch_id', $filterstatus = '', $empty = 0, $disabled = 0, $fk_product = 0, $fk_entrepot = 0, $objectLines = array(), $empty_label = '', $forcecombo = 0, $events = array(), $morecss = 'minwidth200')
	{
		global $conf, $langs;

		dol_syslog(get_class($this)."::selectLotStock $selected, $htmlname, $filterstatus, $empty, $disabled, $fk_product, $fk_entrepot, $empty_label, $forcecombo, $morecss", LOG_DEBUG);

		$out = '';
		$productIdArray = array();
		if (!is_array($objectLines) || !count($objectLines)) {
			if (!empty($fk_product) && $fk_product > 0) {
				$productIdArray[] = (int) $fk_product;
			}
		} else {
			foreach ($objectLines as $line) {
				if ($line->fk_product) {
					$productIdArray[] = $line->fk_product;
				}
			}
		}

		$nboflot = $this->loadLotStock($productIdArray);

		if ($conf->use_javascript_ajax && !$forcecombo) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out .= $comboenhancement;
		}

		$out .= '<select class="flat'.($morecss ? ' '.$morecss : '').'"'.($disabled ? ' disabled' : '').' id="'.$htmlname.'" name="'.($htmlname.($disabled ? '_disabled' : '')).'">';
		if ($empty) {
			$out .= '<option value="-1">'.($empty_label ? $empty_label : '&nbsp;').'</option>';
		}
		if (!empty($fk_product) && $fk_product > 0) {
			$productIdArray = array((int) $fk_product); // only show lot stock for product
		} else {
			foreach ($this->cache_lot as $key => $value) {
				$productIdArray[] = $key;
			}
		}

		foreach ($productIdArray as $productId) {
			foreach ($this->cache_lot[$productId] as $id => $arraytypes) {
				if (empty($fk_entrepot) || $fk_entrepot == $arraytypes['entrepot_id']) {
					$label = $arraytypes['entrepot_label'].' - ';
					$label .= $arraytypes['batch'];
					if ($arraytypes['qty'] <= 0) {
						$label .= ' <span class=\'text-warning\'>('.$langs->trans("Stock").' '.$arraytypes['qty'].')</span>';
					} else {
						$label .= ' <span class=\'opacitymedium\'>('.$langs->trans("Stock").' '.$arraytypes['qty'].')</span>';
					}

					$out .= '<option value="'.$id.'"';

					if ($selected == $id || ($selected == 'ifone' && $nboflot == 1)) {
						$out .= ' selected';
					}
					$out .= ' data-html="'.dol_escape_htmltag($label).'"';
					$out .= '>';
					$out .= $label;
					$out .= '</option>';
				}
			}
		}
		$out .= '</select>';
		if ($disabled) {
			$out .= '<input type="hidden" name="'.$htmlname.'" value="'.(($selected > 0) ? $selected : '').'">';
		}

		return $out;
	}



	/**
	 *  Return list of lot numbers (stock from product_batch) for product and warehouse.
	 *
	 *  @param  string	$htmlname		Name of key that is inside attribute "list" of an input text field.
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 *  @param	int		$fk_product		show lot numbers of product with id fk_product. All from objectLines if 0.
	 *  @param	int		$fk_entrepot	filter lot numbers for warehouse with id fk_entrepot. All if 0.
	 *  @param	array	$objectLines	Only cache lot numbers for products in lines of object. If no lines only for fk_product. If no fk_product, all.
	 *  @return	string					HTML datalist
	 */
	public function selectLotDataList($htmlname = 'batch_id', $empty = 0, $fk_product = 0, $fk_entrepot = 0, $objectLines = array())
	{
		global $langs, $hookmanager;

		dol_syslog(get_class($this)."::selectLotDataList $htmlname, $empty, $fk_product, $fk_entrepot", LOG_DEBUG);

		$out = '';
		$productIdArray = array();
		if (!is_array($objectLines) || !count($objectLines)) {
			if (!empty($fk_product) && $fk_product > 0) {
				$productIdArray[] = (int) $fk_product;
			}
		} else {
			foreach ($objectLines as $line) {
				if ($line->fk_product) {
					$productIdArray[] = $line->fk_product;
				}
			}
		}

		$nboflot = $this->loadLotStock($productIdArray);

		if (!empty($fk_product) && $fk_product > 0) {
			$productIdArray = array((int) $fk_product); // only show lot stock for product
		} else {
			foreach ($this->cache_lot as $key => $value) {
				$productIdArray[] = $key;
			}
		}

		if (empty($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('productdao'));
		$parameters = array('productIdArray' => $productIdArray, 'htmlname' => $htmlname);
		$reshook = $hookmanager->executeHooks('selectLotDataList', $parameters, $this);
		if ($reshook < 0) {
			return $hookmanager->error;
		} elseif ($reshook > 0) {
			return $hookmanager->resPrint;
		} else {
			$out .= $hookmanager->resPrint;
		}

		$out .= '<datalist id="'.$htmlname.'" >';
		foreach ($productIdArray as $productId) {
			if (array_key_exists($productId, $this->cache_lot)) {
				foreach ($this->cache_lot[$productId] as $id => $arraytypes) {
					if (empty($fk_entrepot) || $fk_entrepot == $arraytypes['entrepot_id']) {
						$label = $arraytypes['entrepot_label'] . ' - ';
						$label .= $arraytypes['batch'];
						$out .= '<option data-warehouse="'.dol_escape_htmltag($label).'" value="' . $arraytypes['batch'] . '">(' . $langs->trans('Stock Total') . ': ' . $arraytypes['qty'] . ')</option>';
					}
				}
			}
		}
		$out .= '</datalist>';

		return $out;
	}


	/**
	 * Load in cache array list of lot available in stock from a given list of products
	 *
	 * @param	array	$productIdArray		array of product id's from who to get lot numbers. A
	 *
	 * @return	int							Nb of loaded lines, 0 if nothing loaded, <0 if KO
	 */
	private function loadLotStock($productIdArray = array())
	{
		global $conf, $langs;

		$cacheLoaded = false;
		if (empty($productIdArray)) {
			// only Load lot stock for given products
			$this->cache_lot = array();
			return 0;
		}
		if (count($productIdArray) && count($this->cache_lot)) {
			// check cache already loaded for product id's
			foreach ($productIdArray as $productId) {
				$cacheLoaded = !empty($this->cache_lot[$productId]);
			}
		}
		if ($cacheLoaded) {
			return count($this->cache_lot);
		} else {
			// clear cache
			$this->cache_lot = array();
			$productIdList = implode(',', $productIdArray);

			$batch_count = 0;
			global $hookmanager;
			if (empty($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
				$hookmanager = new HookManager($this->db);
			}
			$hookmanager->initHooks(array('productdao'));
			$parameters = array('productIdList' => $productIdList);
			$reshook = $hookmanager->executeHooks('loadLotStock', $parameters, $this);
			if ($reshook < 0) {
				$this->error = $hookmanager->error;
				return -1;
			}
			if (!empty($hookmanager->resArray['batch_list']) && is_array($hookmanager->resArray['batch_list'])) {
				$this->cache_lot = $hookmanager->resArray['batch_list'];
				$batch_count = (int) $hookmanager->resArray['batch_count'];
			}
			if ($reshook > 0) {
				return $batch_count;
			}

			$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, pb.qty, e.ref as label, ps.fk_product";
			$sql .= " FROM ".$this->db->prefix()."product_batch as pb";
			$sql .= " LEFT JOIN ".$this->db->prefix()."product_stock as ps on ps.rowid = pb.fk_product_stock";
			$sql .= " LEFT JOIN ".$this->db->prefix()."entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (".getEntity('stock').")";
			if (!empty($productIdList)) {
				$sql .= " WHERE ps.fk_product IN (".$this->db->sanitize($productIdList).")";
			}
			$sql .= " ORDER BY e.ref, pb.batch";

			dol_syslog(get_class($this).'::loadLotStock', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$this->cache_lot[$obj->fk_product][$obj->rowid]['id'] = $obj->rowid;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['batch'] = $obj->batch;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['entrepot_id'] = $obj->fk_entrepot;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['entrepot_label'] = $obj->label;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['qty'] = $obj->qty;
					$i++;
				}

				return $batch_count + $num;
			} else {
				dol_print_error($this->db);
				return -1;
			}
		}
	}
}
