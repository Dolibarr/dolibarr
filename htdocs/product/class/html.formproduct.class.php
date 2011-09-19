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
	 *	\brief     Constructeur
	 *	\param     DB      Database handler
	 */
	function FormProduct($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *      \brief      Load in cache array list of warehouses
	 * 		\param		fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *      \return     int      		Nb of loaded lines, 0 if already loaded, <0 if KO
	 * 		\remarks	If fk_product is not 0, we do not use cache
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

		dol_syslog('FormProduct::loadWarehouses sql='.$sql,LOG_DEBUG);
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
	 *      \brief      Return list of possible payments modes
	 *      \param      selected        Id du mode de paiement pre-selectionne
	 *      \param      htmlname        Name of html select html
	 *      \param      filtertype      For filtre
	 *      \param      empty			1=Can be empty, 0 if not
	 * 		\param		disabled		1=Select is disabled
	 * 		\param		fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * 		\return		int				<0 if KO, Nb of product in list if OK
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filtertype='',$empty=0,$disabled=0,$fk_product=0)
	{
		global $langs,$user;

		dol_syslog("Form::selectWarehouses $selected, $htmlname, $filtertype, $empty, $disabled, $fk_product",LOG_DEBUG);

		$this->loadWarehouses($fk_product);

		print '<select class="flat"'.($disabled?' disabled="disabled"':'').' name="'.($htmlname.($disabled?'_disabled':'')).'">';
		if ($empty) print '<option value="">&nbsp;</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			print '<option value="'.$id.'"';
			// Si selected est text, on compare avec code, sinon avec id
			if ($selected == $id) print ' selected="selected"';
			print '>';
			print $arraytypes['label'];
			if ($fk_product) print ' ('.$langs->trans("Stock").': '.($arraytypes['stock']>0?$arraytypes['stock']:'?').')';
			print '</option>';
		}
		print '</select>';
		if ($disabled) print '<input type="hidden" name="'.$htmlname.'" value="'.$selected.'">';

		return count($this->cache_warehouses);
	}

	/**
	 *  \brief      Output a combo box with list of units
	 *  \param      name                Name of HTML field
	 *  \param      measuring_style     Unit to show: weight, size, surface, volume
	 *  \param      default             Force unit
	 * 	\param		adddefault			Add empty unit called "Default"
	 *  \remarks pour l'instant on ne definit pas les unites dans la base
	 */
	function select_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
		print $this->load_measuring_units($name, $measuring_style, $default, $adddefault);
	}

	/**
	 *  Return a combo box with list of units
	 *  @param  name                Name of HTML field
	 *  @param  measuring_style     Unit to show: weight, size, surface, volume
	 *  @param  default             Force unit
	 * 	@param	adddefault			Add empty unit called "Default"
	 *  @see 	For the moment, units labels are defined in measuring_units_string
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
