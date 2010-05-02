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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/product/html.formproduct.class.php
 *	\brief      Fichier de la classe des fonctions predefinie de composants html
 *	\version	$Id$
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
	 *      \return     int      	Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function loadWarehouses()
	{
		global $langs;

		if (sizeof($this->cache_warehouses)) return 0;    // Cache already loaded

		$sql  = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql .= " WHERE statut = 1";
		$sql .= " ORDER BY e.label";

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
	 *      \brief      Retourne la liste des modes de paiements possibles
	 *      \param      selected        Id du mode de paiement pre-selectionne
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *      \param      empty			1=peut etre vide, 0 sinon
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filtertype='',$empty=0)
	{
		global $langs,$user;

		dol_syslog("Form::selectWarehouses $selected, $htmlname, $filtertype, $format",LOG_DEBUG);

		$this->loadWarehouses();

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			print '<option value="'.$id.'"';
			// Si selected est text, on compare avec code, sinon avec id
			if ($selected == $id) print ' selected="true"';
			print '>';
			print $arraytypes['label'];
			print '</option>';
		}
		print '</select>';
	}
	
	/**
	 *  \brief      Selection des unites de mesure
	 *  \param      name                Nom champ html
	 *  \param      measuring_style     Le style de mesure : weight, volume,...
	 *  \param      default             Forcage de l'unite
	 *  \remarks pour l'instant on ne definit pas les unites dans la base
	 */
	function select_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
		print $this->load_measuring_units($name, $measuring_style, $default, $adddefault);
	}
	
	/**
	 *  \brief      Selection des unites de mesure
	 *  \param      name                Nom champ html
	 *  \param      measuring_style     Le style de mesure : weight, volume,...
	 *  \param      default             Forcage de l'unite
	 *  \remarks pour l'instant on ne definit pas les unites dans la base
	 */
	function load_measuring_units($name='measuring_units', $measuring_style='', $default='0', $adddefault=0)
	{
		global $langs,$conf,$mysoc;
		$langs->load("other");
		
		$return='';

		if ($measuring_style == 'weight')
		{
			$measuring_units[3] = $langs->trans("WeightUnitton");
			$measuring_units[0] = $langs->trans("WeightUnitkg");
			$measuring_units[-3] = $langs->trans("WeightUnitg");
			$measuring_units[-6] = $langs->trans("WeightUnitmg");
		}
		else if ($measuring_style == 'size')
		{
			$measuring_units[0] = $langs->trans("SizeUnitm");
			$measuring_units[-1] = $langs->trans("SizeUnitdm");
			$measuring_units[-2] = $langs->trans("SizeUnitcm");
			$measuring_units[-3] = $langs->trans("SizeUnitmm");
		}
		else if ($measuring_style == 'surface')
		{
			$measuring_units[0] = $langs->trans("SurfaceUnitm2");
			$measuring_units[-2] = $langs->trans("SurfaceUnitdm2");
			$measuring_units[-4] = $langs->trans("SurfaceUnitcm2");
			$measuring_units[-6] = $langs->trans("SurfaceUnitmm2");
		}
		else if ($measuring_style == 'volume')
		{
			$measuring_units[0] = $langs->trans("VolumeUnitm3");
			$measuring_units[-3] = $langs->trans("VolumeUnitdm3");
			$measuring_units[-6] = $langs->trans("VolumeUnitcm3");
			$measuring_units[-9] = $langs->trans("VolumeUnitmm3");
		}

		$return.= '<select class="flat" name="'.$name.'">';
		if ($adddefault) $return.= '<option value="0">'.$langs->trans("Default").'</option>';

		foreach ($measuring_units as $key => $value)
		{
			$return.= '<option value="'.$key.'"';
			if ($key == $default)
			{
				$return.= ' selected="true"';
			}
			$return.= '>'.$value.'</option>';
		}
		$return.= '</select>';
		
		return $return;
	}

}

?>
