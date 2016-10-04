<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016	   Francis Appels       <francis.appels@yahoo.com>
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


/**
 *	Class with static methods for building HTML components related to products
 *	Only components common to products and services must be here.
 */
class FormProduct
{
	var $db;
	var $error;

	// Cache arrays
	var $cache_warehouses=array();


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
	 * Load in cache array list of warehouses
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		$fk_product		    Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @param	string	$batch			    Add quantity of batch stock in label for product with batch name batch, batch name precedes batch_id. Nothing if ''.
	 * @param	int		$status		      	additional filter on status other then 1
	 * @param	boolean	$sumStock		    sum total stock of a warehouse, default true
	 * @return  int  		    		    Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function loadWarehouses($fk_product=0, $batch = '', $status=null, $sumStock = true)
	{
		global $conf, $langs;

		if (empty($fk_product) && count($this->cache_warehouses)) return 0;    // Cache already loaded and we do not want a list with information specific to a product

		$sql = "SELECT e.rowid, e.label, e.description";
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
		$sql.= " WHERE e.entity IN (".getEntity('stock', 1).")";
		if (!empty($status))
		{
			$sql.= " AND e.statut IN (1, ".$status.")";
		}
		else
		{
			$sql.= " AND e.statut = 1";
		}
		
		if ($sumStock && empty($fk_product)) $sql.= " GROUP BY e.rowid, e.label, e.description";
		$sql.= " ORDER BY e.label";

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
				$this->cache_warehouses[$obj->rowid]['description'] = $obj->description;
				$this->cache_warehouses[$obj->rowid]['stock'] = $obj->stock;
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

	/**
	 *  Return list of warehouses
	 *
	 *  @param	int		$selected       Id of preselected warehouse ('' for no value, 'ifone'=select value if one value otherwise no value)
	 *  @param  string	$htmlname       Name of html select html
	 *  @param  string	$filtertype     For filter, additional filter on status other then 1
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 * 	@param	int		$disabled		1=Select is disabled
	 * 	@param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *  @param	string	$empty_label	Empty label if needed (only if $empty=1)
	 *  @param	int		$showstock		1=show stock count
	 *  @param	int		$forcecombo		force combo iso ajax select2
	 *  @param	array	$events			events to add to select2
	 *  @param  string  $morecss        Add more css classes
	 * 	@return	string					HTML select
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filtertype='',$empty=0,$disabled=0,$fk_product=0,$empty_label='', $showstock=0, $forcecombo=0, $events=array(), $morecss='minwidth200')
	{
		global $conf,$langs,$user;

		dol_syslog(get_class($this)."::selectWarehouses $selected, $htmlname, $filtertype, $empty, $disabled, $fk_product, $empty_label, $showstock, $forcecombo, $morecss",LOG_DEBUG);
		
		$out='';
		
		$this->loadWarehouses($fk_product, '', + $filtertype); // filter on numeric status
		$nbofwarehouses=count($this->cache_warehouses);

		if ($conf->use_javascript_ajax && ! $forcecombo)
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events);
			$out.= $comboenhancement;
			$nodatarole=($comboenhancement?' data-role="none"':'');
		}
		
		$out.='<select class="flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled':'').' id="'.$htmlname.'" name="'.($htmlname.($disabled?'_disabled':'')).'"'.$nodatarole.'>';
		if ($empty) $out.='<option value="-1">'.($empty_label?$empty_label:'&nbsp;').'</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			$out.='<option value="'.$id.'"';
			if ($selected == $id || ($selected == 'ifone' && $nbofwarehouses == 1)) $out.=' selected';
			$out.='>';
			$out.=$arraytypes['label'];
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
		print $this->load_measuring_units($name, $measuring_style, $default, $adddefault);
	}

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
		global $langs,$conf,$mysoc;
		$langs->load("other");

		$return='';

		$measuring_units=array();
		if ($measuring_style == 'weight') $measuring_units=array(-6=>1,-3=>1,0=>1,3=>1,99=>1);
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

}

