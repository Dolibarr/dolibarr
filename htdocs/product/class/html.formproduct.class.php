<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2017 Francis Appels       <francis.appels@yahoo.com>
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
 *	\file       htdocs/product/class/html.formproduct.class.php
 *	\brief      Fichier de la classe des fonctions predefinie de composants html
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
	public $error='';

	// Cache arrays
	var $cache_warehouses=array();
	var $cache_lot=array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Load in cache array list of warehouses
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		$fk_product		    Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @param	string	$batch			    Add quantity of batch stock in label for product with batch name batch, batch name precedes batch_id. Nothing if ''.
	 * @param	string	$status		      	warehouse status filter, following comma separated filter options can be used
     *										'warehouseopen' = select products from open warehouses,
	 *										'warehouseclosed' = select products from closed warehouses,
	 *										'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 * @param	boolean	$sumStock		    sum total stock of a warehouse, default true
	 * @param	array	$exclude		    warehouses ids to exclude
	 * @return  int  		    		    Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function loadWarehouses($fk_product=0, $batch = '', $status='', $sumStock = true, $exclude='')
	{
		global $conf, $langs;

		if (empty($fk_product) && count($this->cache_warehouses)) return 0;    // Cache already loaded and we do not want a list with information specific to a product

		if (is_array($exclude))	$excludeGroups = implode("','",$exclude);

		$warehouseStatus = array();

		if (preg_match('/warehouseclosed/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_CLOSED;
		}
		if (preg_match('/warehouseopen/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_OPEN_ALL;
		}
		if (preg_match('/warehouseinternal/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_OPEN_INTERNAL;
		}

		$sql = "SELECT e.rowid, e.ref as label, e.description, e.fk_parent";
		if (!empty($fk_product))
		{
			if (!empty($batch))
			{
				$sql.= ", pb.qty as stock";
			}
			else
			{
				$sql.= ", ps.reel as stock";
			}
		}
		else if ($sumStock)
		{
			$sql.= ", sum(ps.reel) as stock";
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.fk_entrepot = e.rowid";
		if (!empty($fk_product))
		{
			$sql.= " AND ps.fk_product = '".$fk_product."'";
			if (!empty($batch))
            {
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_batch as pb on pb.fk_product_stock = ps.rowid AND pb.batch = '".$batch."'";
            }
		}
		$sql.= " WHERE e.entity IN (".getEntity('stock').")";
		if (count($warehouseStatus))
		{
			$sql.= " AND e.statut IN (".$this->db->escape(implode(',',$warehouseStatus)).")";
		}
		else
		{
			$sql.= " AND e.statut = 1";
		}

		if(!empty($exclude)) $sql.= ' AND e.rowid NOT IN('.$this->db->escape(implode(',', $exclude)).')';

		if ($sumStock && empty($fk_product)) $sql.= " GROUP BY e.rowid, e.ref, e.description, e.fk_parent";
		$sql.= " ORDER BY e.ref";

		dol_syslog(get_class($this).'::loadWarehouses', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if ($sumStock) $obj->stock = price2num($obj->stock,5);
				$this->cache_warehouses[$obj->rowid]['id'] =$obj->rowid;
				$this->cache_warehouses[$obj->rowid]['label']=$obj->label;
				$this->cache_warehouses[$obj->rowid]['parent_id']=$obj->fk_parent;
				$this->cache_warehouses[$obj->rowid]['description'] = $obj->description;
				$this->cache_warehouses[$obj->rowid]['stock'] = $obj->stock;
				$i++;
			}

			// Full label init
			foreach($this->cache_warehouses as $obj_rowid=>$tab) {
				$this->cache_warehouses[$obj_rowid]['full_label'] = $this->get_parent_path($tab);
			}

			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return full path to current warehouse in $tab (recursive function)
	 *
	 * @param	array	$tab			warehouse data in $this->cache_warehouses line
	 * @param	String	$final_label	full label with all parents, separated by ' >> ' (completed on each call)
	 * @return	String					full label with all parents, separated by ' >> '
	 */
    private function get_parent_path($tab, $final_label='')
    {
        //phpcs:enable
		if(empty($final_label)) $final_label = $tab['label'];

		if(empty($tab['parent_id'])) return $final_label;
		else {
			if(!empty($this->cache_warehouses[$tab['parent_id']])) {
				$final_label = $this->cache_warehouses[$tab['parent_id']]['label'].' >> '.$final_label;
				return $this->get_parent_path($this->cache_warehouses[$tab['parent_id']], $final_label);
			}
		}

		return $final_label;
	}

	/**
	 *  Return list of warehouses
	 *
	 *  @param	int		$selected       Id of preselected warehouse ('' for no value, 'ifone'=select value if one value otherwise no value)
	 *  @param  string	$htmlname       Name of html select html
	 *  @param  string	$filterstatus   warehouse status filter, following comma separated filter options can be used
     *									'warehouseopen' = select products from open warehouses,
	 *									'warehouseclosed' = select products from closed warehouses,
	 *									'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 * 	@param	int		$disabled		1=Select is disabled
	 * 	@param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *  @param	string	$empty_label	Empty label if needed (only if $empty=1)
	 *  @param	int		$showstock		1=Show stock count
	 *  @param	int		$forcecombo		1=Force combo iso ajax select2
	 *  @param	array	$events			Events to add to select2
	 *  @param  string  $morecss        Add more css classes to HTML select
	 *  @param	array	$exclude		Warehouses ids to exclude
	 *  @param  int     $showfullpath   1=Show full path of name (parent ref into label), 0=Show only ref of current warehouse
	 * 	@return	string					HTML select
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filterstatus='',$empty=0,$disabled=0,$fk_product=0,$empty_label='', $showstock=0, $forcecombo=0, $events=array(), $morecss='minwidth200', $exclude='', $showfullpath=1)
	{
		global $conf,$langs,$user;

		dol_syslog(get_class($this)."::selectWarehouses $selected, $htmlname, $filterstatus, $empty, $disabled, $fk_product, $empty_label, $showstock, $forcecombo, $morecss",LOG_DEBUG);

		$out='';
		if (empty($conf->global->ENTREPOT_EXTRA_STATUS)) $filterstatus = '';
		$this->loadWarehouses($fk_product, '', $filterstatus, true, $exclude);
		$nbofwarehouses=count($this->cache_warehouses);

		if ($conf->use_javascript_ajax && ! $forcecombo)
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out.= $comboenhancement;
		}

		$out.='<select class="flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled':'').' id="'.$htmlname.'" name="'.($htmlname.($disabled?'_disabled':'')).'">';
		if ($empty) $out.='<option value="-1">'.($empty_label?$empty_label:'&nbsp;').'</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			$out.='<option value="'.$id.'"';
			if ($selected == $id || ($selected == 'ifone' && $nbofwarehouses == 1)) $out.=' selected';
			$out.='>';
			if ($showfullpath) $out.=$arraytypes['full_label'];
			else $out.=$arraytypes['label'];
			if (($fk_product || ($showstock > 0)) && ($arraytypes['stock'] != 0 || ($showstock > 0))) $out.=' ('.$langs->trans("Stock").':'.$arraytypes['stock'].')';
			$out.='</option>';
		}
		$out.='</select>';
		if ($disabled) $out.='<input type="hidden" name="'.$htmlname.'" value="'.(($selected>0)?$selected:'').'">';

		return $out;
	}

    /**
     *    Display form to select warehouse
     *
     *    @param    string  $page        Page
     *    @param    int     $selected    Id of warehouse
     *    @param    string  $htmlname    Name of select html field
     *    @param    int     $addempty    1=Add an empty value in list, 2=Add an empty value in list only if there is more than 2 entries.
     *    @return   void
     */
    function formSelectWarehouses($page, $selected='', $htmlname='warehouse_id', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none") {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setwarehouse">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->selectWarehouses($selected, $htmlname, '', $addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        } else {
            if ($selected) {
                require_once DOL_DOCUMENT_ROOT .'/product/stock/class/entrepot.class.php';
                $warehousestatic=new Entrepot($this->db);
                $warehousestatic->fetch($selected);
                print $warehousestatic->getNomUrl();
            } else {
                print "&nbsp;";
            }
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Output a combo box with list of units
	 *  pour l'instant on ne definit pas les unites dans la base
	 *
	 *  @param	string		$name               Name of HTML field
	 *  @param	string		$measuring_style    Unit to show: weight, size, surface, volume
	 *  @param  string		$default            Force unit
	 * 	@param	int			$adddefault			Add empty unit called "Default"
	 * 	@return	void
	 */
	function select_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
        //phpcs:enable
		print $this->load_measuring_units($name, $measuring_style, $default, $adddefault);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return a combo box with list of units
	 *  For the moment, units labels are defined in measuring_units_string
	 *
	 *  @param	string		$name                Name of HTML field
	 *  @param  string		$measuring_style     Unit to show: weight, size, surface, volume
	 *  @param  string		$default             Force unit
	 * 	@param	int			$adddefault			Add empty unit called "Default"
	 * 	@return	string
	 */
	function load_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
        //phpcs:enable
		global $langs,$conf,$mysoc;
		$langs->load("other");

		$return='';

		$measuring_units=array();
		if ($measuring_style == 'weight') $measuring_units=array(-6=>1,-3=>1,0=>1,3=>1,98=>1,99=>1);
		else if ($measuring_style == 'size') $measuring_units=array(-3=>1,-2=>1,-1=>1,0=>1,98=>1,99=>1);
        else if ($measuring_style == 'surface') $measuring_units=array(-6=>1,-4=>1,-2=>1,0=>1,98=>1,99=>1);
		else if ($measuring_style == 'volume') $measuring_units=array(-9=>1,-6=>1,-3=>1,0=>1,88=>1,89=>1,97=>1,99=>1,/* 98=>1 */);  // Liter is not used as already available with dm3

		$return.= '<select class="flat" name="'.$name.'">';
		if ($adddefault) $return.= '<option value="0">'.$langs->trans("Default").'</option>';

		foreach ($measuring_units as $key => $value)
		{
			$return.= '<option value="'.$key.'"';
			if ($key == $default)
			{
				$return.= ' selected';
			}
			//$return.= '>'.$value.'</option>';
			$return.= '>'.measuring_units_string($key,$measuring_style).'</option>';
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *  Return list of lot numbers (stock from product_batch) with stock location and stock qty
	 *
	 *  @param	int		$selected		Id of preselected lot stock id ('' for no value, 'ifone'=select value if one value otherwise no value)
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
	function selectLotStock($selected='',$htmlname='batch_id',$filterstatus='',$empty=0,$disabled=0,$fk_product=0,$fk_entrepot=0,$objectLines = array(),$empty_label='', $forcecombo=0, $events=array(), $morecss='minwidth200')
	{
		global $langs;

		dol_syslog(get_class($this)."::selectLot $selected, $htmlname, $filterstatus, $empty, $disabled, $fk_product, $fk_entrepot, $empty_label, $showstock, $forcecombo, $morecss",LOG_DEBUG);

		$out='';
		$productIdArray = array();
		if (! is_array($objectLines) || ! count($objectLines))
		{
			if (! empty($fk_product)) $productIdArray[] = $fk_product;
		}
		else
		{
			foreach ($objectLines as $line) {
				if ($line->fk_product) $productIdArray[] = $line->fk_product;
			}
		}

		$nboflot = $this->loadLotStock($productIdArray);

		if ($conf->use_javascript_ajax && ! $forcecombo)
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out.= $comboenhancement;
		}

		$out.='<select class="flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled':'').' id="'.$htmlname.'" name="'.($htmlname.($disabled?'_disabled':'')).'">';
		if ($empty) $out.='<option value="-1">'.($empty_label?$empty_label:'&nbsp;').'</option>';
		if (! empty($fk_product))
		{
			$productIdArray = array($fk_product); // only show lot stock for product
		}
		else
		{
			foreach($this->cache_lot as $key => $value)
			{
				$productIdArray[] = $key;
			}
		}

		foreach($productIdArray as $productId)
		{
			foreach($this->cache_lot[$productId] as $id => $arraytypes)
			{
				if (empty($fk_entrepot) || $fk_entrepot == $arraytypes['entrepot_id'])
				{
					$out.='<option value="'.$id.'"';
					if ($selected == $id || ($selected == 'ifone' && $nboflot == 1)) $out.=' selected';
					$out.='>';
					$out.=$arraytypes['entrepot_label'].' - ';
					$out.=$arraytypes['batch'];
					$out.=' ('.$langs->trans("Stock").':'.$arraytypes['qty'].')';
					$out.='</option>';
				}
			}
		}
		$out.='</select>';
		if ($disabled) $out.='<input type="hidden" name="'.$htmlname.'" value="'.(($selected>0)?$selected:'').'">';

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
		if (empty($productIdArray))
		{
			// only Load lot stock for given products
			$this->cache_lot = array();
			return 0;
		}
		if (count($productIdArray) && count($this->cache_lot))
		{
			// check cache already loaded for product id's
			foreach ($productIdArray as $productId)
			{
				$cacheLoaded = ! empty($this->cache_lot[$productId]) ? true : false;
			}
		}
		if ($cacheLoaded)
		{
			return count($this->cache_lot);
		}
		else
		{
			// clear cache
			$this->cache_lot = array();
			$productIdList = implode(',', $productIdArray);
			$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, pb.qty, e.ref as label, ps.fk_product";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.rowid = pb.fk_product_stock";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (".getEntity('stock').")";
			if (!empty($productIdList))
			{
				$sql.= " WHERE ps.fk_product IN (".$productIdList.")";
			}
			$sql.= " ORDER BY e.ref, pb.batch";

			dol_syslog(get_class($this).'::loadLotStock', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$this->cache_lot[$obj->fk_product][$obj->rowid]['id'] =$obj->rowid;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['batch']=$obj->batch;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['entrepot_id']=$obj->fk_entrepot;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['entrepot_label']=$obj->label;
					$this->cache_lot[$obj->fk_product][$obj->rowid]['qty'] = $obj->qty;
					$i++;
				}

				return $num;
			}
			else
			{
				dol_print_error($this->db);
				return -1;
			}
		}
	}
}
