<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/html.formproduct.class.php
 *	\brief      Fichier de la classe des fonctions prédéfinie de composants html
 *	\version	$Id$
 */


/**
 *	\class      FormProduct
 *	\brief      Classe permettant la génération de composants html
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
	 \brief     Constructeur
	 \param     DB      handler d'accès base de donnée
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
		
		dolibarr_syslog('FormProduct::loadWarehouses sql='.$sql,LOG_DEBUG);
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
			dolibarr_print_error($this->db);
			return -1;
		}
	}
	
	/**
	 *      \brief      Retourne la liste des modes de paiements possibles
	 *      \param      selected        Id du mode de paiement pré-sélectionné
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *      \param      empty			1=peut etre vide, 0 sinon
	 */
	function selectWarehouses($selected='',$htmlname='idwarehouse',$filtertype='',$empty=0)
	{
		global $langs,$user;

		dolibarr_syslog("Form::selectWarehouses $selected, $htmlname, $filtertype, $format",LOG_DEBUG);

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

}

?>
