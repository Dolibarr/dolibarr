<?php
/* Copyright (C) 2011 Florian HENRY  <florian.henry.mail@gmail.com>
 *
 * Code of this page is mostly inspired from module category
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
 *  \file       htdocs/assortment/class/assortment_category.class.php
 *  \ingroup    assortment
 *  \brief      Set Assortment by category by product or customer pages
 *  \version    $Id: assortment_category.class.php,v 1.0 2011/01/01 eldy Exp $
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");


/**
 *      \class      Skeleton_class
 *      \brief      Put here description of your class
 *		\remarks	Put here some comments
 */
class Assortment_Category extends Categorie
{
	/**
	 * 	Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *	Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *				id = id de la categorie
	 *				id_mere = id de la categorie mere
	 *				id_children = tableau des id enfant
	 *				label = nom de la categorie
	 *				fulllabel = nom avec chemin complet de la categorie
	 *				fullpath = chemin complet compose des id
	 *	@param      type		      Type of categories (0=product, 1=suppliers, 2=customers, 3=members)
     *  @param      objectid       	  all category link to the assortment of the product or customer/supplier
	 *	@return		array		      Array of categories
	 */
	function get_full_arbo_assort($type,$objectid=0)
	{
		$this->cats = array();

		// Charge tableau des meres
		$sql = "SELECT fk_categorie_mere as id_mere, fk_categorie_fille as id_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";

		// Load array this->motherof
		dol_syslog("Assortment::get_full_arbo_assort build motherof array sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_fille]=$obj->id_mere;
			}
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label as label, ca.fk_categorie_fille as rowid_fille";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";		
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " ON c.rowid=ca.fk_categorie_mere";
		if ($type == 0) // For supplier/customer assortment link
		{
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product as catlink ON c.rowid = catlink.fk_categorie ";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."assortment as assort ON assort.fk_soc='".$objectid."' and assort.fk_prod=catlink.fk_product";
		}
		if ($type == 1) // for product with supplier assortment link
		{
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_fournisseur as catlink ON c.rowid = catlink.fk_categorie ";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."assortment as assort ON catlink.fk_societe=assort.fk_soc and assort.fk_prod='".$objectid."'";
		}
		if ($type == 2) // for product with Customer assortment link 
		{
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_societe as catlink ON c.rowid = catlink.fk_categorie ";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."assortment as assort ON catlink.fk_societe=assort.fk_soc and assort.fk_prod='".$objectid."'";
		}
		
		$sql.= " WHERE c.type = ".$type;
		$sql.= " ORDER BY c.label, c.rowid";

		dol_syslog("Assortment_Categorie::get_full_arbo_assort get category list sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				if (isset($this->motherof[$obj->rowid])) $this->cats[$obj->rowid]['id_mere'] = $this->motherof[$obj->rowid];
				$this->cats[$obj->rowid]['label'] = $obj->label;

				if ($obj->rowid_fille)
				{
					$this->cats[$obj->rowid]['id_children'][]=$obj->rowid_fille;
				}
				$i++;

			}
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}

		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog("Assortment_Categorie::get_full_arbo_assort call to build_path_from_id_categ", LOG_DEBUG);
		foreach($this->cats as $key => &$val)
		{
			$catways = new Categorie($this->db);
			$catways->fetch($key);

			foreach ($catways->get_all_ways () as $wayassort)
			{
				$wassort = array ();
				foreach ($wayassort as $catassort)
				{
					if (!in_array($catassort->label,$wassort))
					{
						$wassort[] = $catassort->label;
					}
				}
				
				$val["fulllabel"] .= implode (" &gt;&gt; ", $wassort);
			}
		}

		dol_syslog("Categorie::get_full_arbo_assort dol_sort_array", LOG_DEBUG);
		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);
		
		return $this->cats;
	}
   
}
?>
