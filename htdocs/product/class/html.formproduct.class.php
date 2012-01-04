<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\class      FormProduct
 *	\brief      Class with static methods for building HTML components related to products
 *	\remarks	Only common components must be here.
 */
class FormProduct
{
	var $db;
	var $error;

	// Cache arrays
	var $cache_warehouses=array();

	var $tva_taux_value;
	var $tva_taux_libelle;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function FormProduct($db)
	{
		$this->db = $db;

		return 1;
	}


	/**
	 * Load in cache array list of warehouses
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @return  int  		    		Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function loadWarehouses($fk_product=0)
	{
		global $langs;

		if (empty($fk_product) && count($this->cache_warehouses)) return 0;    // Cache already loaded and we do not want a list with information specific to a product

		$sql = "SELECT e.rowid, e.label";
		if ($fk_product) $sql.= ", ps.reel";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		if ($fk_product)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.fk_entrepot = e.rowid";
			$sql.= " AND ps.fk_product = '".$fk_product."'";
		}
		$sql.= " WHERE statut = 1";
		$sql.= " ORDER BY e.label";

		dol_syslog(get_class($this).'::loadWarehouses sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->cache_warehouses[$obj->rowid]['id'] =$obj->rowid;
				$this->cache_warehouses[$obj->rowid]['label']=$obj->label;
				if ($fk_product) $this->cache_warehouses[$obj->rowid]['stock']=$obj->reel;
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
	 *  Return list of possible payments modes
	 *
	 *  @param	int		$selected       Id du mode de paiement pre-selectionne
	 *  @param  string	$htmlname       Name of html select html
	 *  @param  string	$filtertype     For filter
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 * 	@param	int		$disabled		1=Select is disabled
	 * 	@param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * 	@return	string					HTML select
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filtertype='',$empty=0,$disabled=0,$fk_product=0)
	{
		global $langs,$user;

		dol_syslog(get_class($this)."::selectWarehouses $selected, $htmlname, $filtertype, $empty, $disabled, $fk_product",LOG_DEBUG);

		$this->loadWarehouses($fk_product);

		$out='<select class="flat"'.($disabled?' disabled="disabled"':'').' id="'.$htmlname.'" name="'.($htmlname.($disabled?'_disabled':'')).'">';
		if ($empty) $out.='<option value="">&nbsp;</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			$out.='<option value="'.$id.'"';
			// Si selected est text, on compare avec code, sinon avec id
			if ($selected == $id) $out.=' selected="selected"';
			$out.='>';
			$out.=$arraytypes['label'];
			if ($fk_product) $out.=' ('.$langs->trans("Stock").': '.($arraytypes['stock']>0?$arraytypes['stock']:'?').')';
			$out.='</option>';
		}
		$out.='</select>';
		if ($disabled) $out.='<input type="hidden" name="'.$htmlname.'" value="'.$selected.'">';

		//count($this->cache_warehouses);
		return $out;
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
	 * 	@return	void
	 */
	function load_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
		global $langs,$conf,$mysoc;
		$langs->load("other");

		$return='';

		$measuring_units=array();
		if ($measuring_style == 'weight') $measuring_units=array(-6=>1,-3=>1,0=>1,3=>1,99=>1);
		else if ($measuring_style == 'size') $measuring_units=array(-3=>1,-2=>1,-1=>1,0=>1,99=>1,98=>1);
        else if ($measuring_style == 'surface') $measuring_units=array(-6=>1,-4=>1,-2=>1,0=>1);
		else if ($measuring_style == 'volume') $measuring_units=array(-9=>1,-6=>1,-3=>1,0=>1,97=>1,99=>1,/* 98=>1 */);  // Liter is not used as already available with dm3

		$return.= '<select class="flat" name="'.$name.'">';
		if ($adddefault) $return.= '<option value="0">'.$langs->trans("Default").'</option>';

		foreach ($measuring_units as $key => $value)
		{
			$return.= '<option value="'.$key.'"';
			if ($key == $default)
			{
				$return.= ' selected="selected"';
			}
			//$return.= '>'.$value.'</option>';
			$return.= '>'.measuring_units_string($key,$measuring_style).'</option>';
		}
		$return.= '</select>';

		return $return;
	}

}

?>
