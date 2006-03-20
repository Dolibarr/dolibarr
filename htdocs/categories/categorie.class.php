<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Davoleau Brice       <brice.davoleau@gmail.com>
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006      Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 *
 */

require_once(DOL_DOCUMENT_ROOT."/product.class.php");


class Categorie
{
  var $db;

  var $id;
  var $label;
  var $description;
  var $statut;

  /**
   * Constructeur
   * db : accès base de données
   * id : id de la catégorie
   */
  function Categorie($db, $id=-1)
  {
    $this->db = $db;
    $this->id = $id;

    if ($id != -1) $this->fetch ($this->id);
  }
	
  /**
   * Charge la catégorie
   * id : id de la catégorie à charger
   */
  function fetch($id)
  {
    $sql = "SELECT rowid, label, description, fk_statut";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie WHERE rowid = ".$id;

    $resql  = $this->db->query ($sql);

    if ($resql)
    {
	     $res = $this->db->fetch_array($resql);

	     $this->id		      = $res['rowid'];
	     $this->label		    = $res['label'];
	     $this->description	= stripslashes($res['description']);
	     $this->statut      = $res['fk_statut'];

	     $this->db->free($resql);
    }
    else
    {
	     dolibarr_print_error ($this->db);
	     return -1;
    }
	$sql = "SELECT fk_categorie_mere";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_association WHERE fk_categorie_fille = '".$id."'";

    $resql  = $this->db->query ($sql);

    if ($resql)
    {
	     $res = $this->db->fetch_array($resql);
	     $this->id_mere = $res['fk_categorie_mere'];
	     
    }
    else
    {
	     dolibarr_print_error ($this->db);
	     return -1;
    }
  }

  /**
   * Ajoute la catégorie dans la base de données
   * retour : -1 : erreur SQL
   *          -2 : nouvel ID inconnu
   *          -3 : catégorie invalide
   */
  function create()
  {
		global $langs;
		$langs->load('categories');
	if ($this->already_exists ())
    {
		 $this->error=$langs->trans("ImpossibleAddCat");
		 $this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
		 return -1;
    }
	
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label, description) ";
    $sql .= "VALUES ('".str_replace("'","''",$this->label)."', '".str_replace("'","''",$this->description)."')";
		

    $res  = $this->db->query ($sql);
    if ($res)
    {
	     $id = $this->db->last_insert_id (MAIN_DB_PREFIX."categorie");

	      if ($id > 0)
	      {
	         $this->id = $id;
			 if($this->id_mere != "")
			{
				if($this->add_fille() < 0)
				{
					 $this->error=$langs->trans("ImpossibleAssociateCategory");
					return -1;
				}
					
			}
	         return $id;
	      }
	      else
	      {
	         return -2;
	      }
    }
    else
    {
	      dolibarr_print_error ($this->db);
	      return -1;
    }
  }
	
  /**
   * Mise à jour de la catégorie
   * retour :  1 : OK
   *          -1 : erreur SQL
   *          -2 : catégorie invalide
   */
  function update()
  {
    $sql = 'delete from '.MAIN_DB_PREFIX.'categorie_association';
    $sql .= ' WHERE fk_categorie_mere  = "'.$this->id.'" or fk_categorie_fille = "'.$this->id.'"';
	
    if (! $this->db->query($sql))
    {
	    dolibarr_print_error($this->db);
	     return -1;
    }
	if($this->id_mere !="" && $this->id_mere!=$this->id)
	{
		
		$sql = 'insert into '.MAIN_DB_PREFIX.'categorie_association(fk_categorie_mere,fk_categorie_fille)';
		$sql .= ' VALUES ("'.$this->id_mere.'","'.$this->id.'")';
		if (! $this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			 return -1;
		}
	}
	$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
    $sql.= " SET label = '".trim(str_replace("'","''",$this->label))."'";
    
    if (strlen (trim($this->description)) > 0)
	     $sql .= ", description = '".trim(str_replace("'","''",$this->description))."'";
    $sql .= " WHERE rowid = ".$this->id;

    if ($this->db->query($sql))
    {
	    return 1;
    }
    else
    {
	     dolibarr_print_error($this->db);
	     return -1;
    }
  }

  /**
   * Supprime la catégorie
   * Les produits et sous-catégories deviennent orphelins
   * si $all = false, et sont (seront :) supprimés sinon
   * TODO : imp. $all
   */
  function remove ($all = false)
  {

    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product ";
    $sql .= "WHERE fk_categorie = ".$this->id;
    
    if (!$this->db->query($sql))
    {
	     dolibarr_print_error($this->db);
	     return -1;
    }

    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association ";
    $sql .= "WHERE fk_categorie_mere  = ".$this->id;
    $sql .= "   OR fk_categorie_fille = ".$this->id;
    
    if (!$this->db->query($sql))
    {
	     dolibarr_print_error($this->db);
	     return -1;
    }

    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie ";
    $sql .= "WHERE rowid = ".$this->id;
    
    if (!$this->db->query($sql))
    {
	     dolibarr_print_error($this->db);
	     return -1;
    }
    else
    {
	     return 1;
    }

  }
	
	
  /**
   * Ajout d'une sous-catégorie
   * $fille : objet catégorie
   * retour :  1 : OK
   *          -2 : $fille est déjà dans la famille de $this
   *          -3 : catégorie ($this ou $fille) invalide
   */
  function add_fille()
  {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association (fk_categorie_mere, fk_categorie_fille)";
    $sql.= " VALUES (".$this->id_mere.", ".$this->id.")";

    if ($this->db->query($sql))
    {
	      return 1;
    }
    else
    {
	      dolibarr_print_error($this->db);
	      return -1;
    }
  }
	 
  /**
   * Suppression d'une sous-catégorie (seulement "désassociation")
   * $fille : objet catégorie
   * retour :  1 : OK
   *          -3 : catégorie ($this ou $fille) invalide
   */
  function del_fille($fille)
  {
    if (!$this->check() || !$fille->check())
    {
	     return -3;
    }

    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
    $sql .= " WHERE fk_categorie_mere = ".$this->id." and fk_categorie_fille = ".$fille->id;

    if ($this->db->query($sql))
    {
       return 1;
    }
    else
    {
       dolibarr_print_error($this->db);
       return -1;
    }
  }
	 
  /**
   * Ajout d'un produit à la catégorie
   * retour :  1 : OK
   *          -1 : erreur SQL
   *          -2 : id non renseigné
   */
  function add_product($prod)
  {
    if ($this->id == -1)
    {
      return -2;
    }
		
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."categorie_product (fk_categorie, fk_product)";
    $sql .= " VALUES (".$this->id.", ".$prod->id.")";

    if ($this->db->query($sql))
    {
	     return 1;
    }
    else
    {
       dolibarr_print_error($this->db);
       return -1;
    }
  }
	
  /**
   * Suppresion d'un produit de la catégorie 
   * @param $prod est un objet de type produit
   * retour :  1 : OK
   *          -1 : erreur SQL
   */
  function del_product($prod)
  {
    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product";
    $sql .= " WHERE fk_categorie = ".$this->id;
    $sql .= " AND   fk_product   = ".$prod->id;

    if ($this->db->query($sql))
    {
      return 1;
    }
    else
    {
       dolibarr_print_error($this->db);
       return -1;
    }
  }
	
  /**
   * Retourne les produits de la catégorie
   */
  function get_products()
  {
    $sql  = "SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product ";
    $sql .= "WHERE fk_categorie = ".$this->id;

    $res  = $this->db->query($sql);

    if ($res)
    {
       $prods = array();
       while ($rec = $this->db->fetch_array ($res))
	     {
	        $prod = new Product ($this->db, $rec['fk_product']);
	        $prod->fetch ($prod->id);
	        $prods[] = $prod;
	     }
	       return $prods;
    }
    else
    {
        dolibarr_print_error ($this->db);
        return -1;
    }
  }
	/**
   * Retourne les filles de la catégorie
   */
  function get_filles ()
  {
    $sql  = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association ";
    $sql .= "WHERE fk_categorie_mere = ".$this->id;

    $res  = $this->db->query ($sql);

    if ($res)
      {
        $cats = array ();
        while ($rec = $this->db->fetch_array ($res))
          {
            $cat = new Categorie ($this->db, $rec['fk_categorie_fille']);
            $cats[] = $cat;
          }
        return $cats;
      }
    else
      {
        dolibarr_print_error ($this->db);
        return -1;
      }
  }
  /**
   * Retourne les filles de la catégorie structurée pour l'arbo
   */
  function get_filles_arbo ($id_mere)
  {
	$sql  = "SELECT c.rowid, c.label as label,ca.fk_categorie_fille as id FROM ";
	$sql .= MAIN_DB_PREFIX."categorie as c,".MAIN_DB_PREFIX."categorie_association as ca";
    $sql .= " WHERE c.rowid = ca.fk_categorie_fille and ca.fk_categorie_mere = '".$id_mere."'";
    $res  = $this->db->query ($sql);

    if ($res)
     {
			$cat = array();
			while ($rec = $this->db->fetch_array ($res))
			 {
					$cat[$rec['label']]= array(0=>$rec['id']);
					foreach($this -> get_filles_arbo($rec['id']) as $kf=>$vf)
							$cat[$rec['label']][$kf] = $vf;
			 }
			 return $cat;
      }
    else
    {
				dolibarr_print_error ($this->db);
				return -1;
     }
  }
	
  /**
   * La catégorie $fille est-elle une fille de cette catégorie ?
   */
  function is_fille ($fille)
  {
    $sql  = "SELECT count(fk_categorie_fille) FROM ".MAIN_DB_PREFIX."categorie_association ";
    $sql .= "WHERE fk_categorie_mere = ".$this->id." AND fk_categorie_fille = ".$fille->id;

    $res  = $this->db->query ($sql);
		
    $n    = $this->db->fetch_array ($res);
		
    return ($n[0] > 0);
  }
	
  /**
   * Retourne toutes les catégories qui n'ont pas d'enfants ;-)
   */
  function get_steriles_categories ()
  {
    $sql = "SELECT fk_categorie_mere,fk_categorie_fille FROM ";
	$sql .= MAIN_DB_PREFIX."categorie_association";
	$res = $this->db->query ($sql);
	$cats_sterile = array();
	while ($record = $this->db->fetch_array ($res))
	{
	    $cats_sterile[] = $record['fk_categorie_mere'];
		$cats_sterile[] = $record['fk_categorie_fille'];
	 }
	$sql = "SELECT rowid,label FROM ";
	$sql .= MAIN_DB_PREFIX."categorie";

    $res = $this->db->query ($sql);

    if ($res)
      {
	$cats = array ();
	while ($record = $this->db->fetch_array ($res))
	{
	   if(! in_array($record['rowid'],$cats_sterile))
			$cats[$record['label']] = array(0=>$record['rowid']);
	 }
	return $cats;
      }
    else
      {
	dolibarr_print_error ($this->db);
	return -1;
      }
  }
  /**
   * fonction récursive uniquement utilisée par get_arbo_each_cate
   * Recompose l'arborescence des catégories
   */
  function fetch_cate_arbo($cate,$compl_path="")
{
			$this->res;
			$this->mere_encours;
			foreach($cate as $nom_mere => $desc_mere)
			{
						// on est dans une sous-catégorie
						if(is_array($desc_mere))
							$this->res[]= array($this->mere_encours.$compl_path." -> ".$nom_mere,$desc_mere[0]);
						else if($nom_mere != "0")
							$this->res[]= array($this->mere_encours.$compl_path." -> ".$nom_mere,$desc_mere);
						if(sizeof($desc_mere) >1)
						{
							$this ->fetch_cate_arbo($desc_mere," -> ".$nom_mere);
						}
			}
	}
	/**
   * reconstruit l'arborescence des catégorie sous la forme d'un tableau
   * 
   */
function get_arbo_each_cate()
{
		if(is_array($this -> cates))
		{
			foreach($this -> cates as $nom_mere => $desc_mere)
			{
				$this->mere_encours = $nom_mere;
				$this->res[]= array($nom_mere,$desc_mere[0]);
				if(sizeof($desc_mere) >1)
					$this ->fetch_cate_arbo($desc_mere);
			}
			sort($this->res);
		}
		return $this->res;
}
/**
   * Retourne l'arborescence des catégories, id et nom
   * sous la forme d'un tableau
   */
  function get_categories_arbo ()
  {
		$cates_steriles = $this -> get_steriles_categories();
		$meres = $this -> get_all_meres();
		foreach($meres as $k=>$v)
		{
			foreach($this -> get_filles_arbo($v[0]) as $kf=>$vf)
				$meres[$k][$kf] = $vf;

		}
		
		// on concatène tout ça
		foreach($meres as $k=>$v)
		{
			
			$this -> cates[$k]=$v;
		}
		foreach($cates_steriles  as $k=>$v)
		{
			// print "<br>xxxxxxxx".$k;
			$this -> cates[$k]=$v;
			
		}
		
		
		

		
  }
  /**
   * Retourne toutes les catégories qui ont au moins 1 fille 
   */
  function get_all_meres()
  {
   	$sql  = "SELECT fk_categorie_fille as id FROM ";
	$sql  .= MAIN_DB_PREFIX."categorie_association";
	 $res = $this->db->query ($sql);
	 if ($res)
      {
			$ids_fille = array ();
			while ($record = $this->db->fetch_array ($res))
			 {
				$ids_fille[] = $record['id'];
			 }
      }
    else
     {
			dolibarr_print_error ($this->db);
			return -1;	
     }
   
	$sql  = "SELECT c.label as label,c.rowid,ca.fk_categorie_mere as id FROM ";
	$sql  .= MAIN_DB_PREFIX."categorie_association as ca,";
	$sql  .= MAIN_DB_PREFIX."categorie as c";
	$sql  .= " where c.rowid=ca.fk_categorie_mere";
    $res = $this->db->query ($sql);
    if ($res)
      {
	$cats = array ();
	while ($record = $this->db->fetch_array ($res))
	 {
			 if(! in_array($record['id'],$ids_fille))
			$cats[$record['label']] = array(0=>$record['id']);
	 }
		return $cats;
      }
    else
      {
	dolibarr_print_error ($this->db);
	return -1;
      }
  }
	/**
   * Retourne toutes les catégories
   */
  function get_all_categories ()
  {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";

    $res = $this->db->query ($sql);

    if ($res)
      {
        $cats = array ();
        while ($record = $this->db->fetch_array ($res))
          {
            $cat = new Categorie ($this->db, $record['rowid']);
            $cats[$record['rowid']] = $cat;
          }
        return $cats;
      }
    else
      {
        dolibarr_print_error ($this->db);
        return -1;
      }
  }
  /**
   * Retourne le nombre total de catégories
   */
  function get_nb_categories ()
  {
    $sql = "SELECT count(rowid) FROM ".MAIN_DB_PREFIX."categorie";
    $res = $this->db->query ($sql);
	
    if ($res)
     {
		$res = $this->db->fetch_array ();
		return $res[0];
     }
    else
      {
	dolibarr_print_error ($this->db);
	return -1;
      }
  }
	
  /**
   * Vérifie si une catégorie porte le label $label
   */
  function already_exists()
  {    
    $sql = "SELECT count(c.rowid)";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_association as ca";
    $sql.= " WHERE c.label = '".str_replace("'","''",$this -> label)."'";
	if($this -> id_mere != "")
	{
    	$sql.= " AND c.rowid = ca.fk_categorie_fille";
    	$sql.= " AND ca.fk_categorie_mere = '".$this -> id_mere."'";
	}

    $res  = $this->db->query ($sql);
	if($res)
	{
    	$res  = $this->db->fetch_array ($res);
		if($res[0] > 0)
			return true;
		else
			return false;
	}
	else
    {
	      dolibarr_print_error ($this->db);
	      return -1;
    }
    
  }

  /**
   * Retourne les catégories de premier niveau
   */
  function get_main_categories ()
  {
    $allcats = $this->get_all_categories ();
    $maincats = array ();
    $filles   = array ();
		
    $sql = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association";
    $res = $this->db->query ($sql);
    while ($res = $this->db->fetch_array ($res))
    {
	     $filles[] = $res['fk_categorie_fille'];
    }

    foreach ($allcats as $cat)
    {
	     if (!in_array ($cat->id, $filles))
	     {
	         $maincats[] = $cat;
	     }
	     else
	     {
	     }
    }
		
    return $maincats;
  }

  /**
   * Retourne les chemin de la catégorie, avec les noms des catégories
   * séparés par $sep (" >> " par défaut)
   */
  function print_all_ways ($sep = " &gt;&gt; ", $url='')
  {
    $ways = array ();
		
    foreach ($this->get_all_ways () as $way)
      {
	$w = array ();
	foreach ($way as $cat)
	  {
	    if ($url == '')
	      {
		$w[] = "<a href='".DOL_URL_ROOT."/categories/viewcat.php?id=".$cat->id."'>".$cat->label."</a>";
	      }
	    else
	      {
		$w[] = "<a href='".DOL_URL_ROOT."/$url?catid=".$cat->id."'>".$cat->label."</a>";
	      }
	  }
	$ways[] = implode ($sep, $w);
      }

    return $ways;
  }
	

  /**
   * get_primary_way() affiche le chemin le plus court pour se rendre à un produit
   */
  function get_primary_way($id)
  {
    $primary_way = Array("taille"=>-1,"chemin"=>Array());
    $meres = $this->containing($id);
    foreach ($meres as $mere)
      {
	foreach ($mere->get_all_ways() as $way)
	  {
	    if(sizeof($way)<$primary_way["taille"] || $primary_way["taille"]<0)
	      {
		$primary_way["taille"] = sizeOf($way);
		$primary_way["chemin"] = $way;
	      }
	  }
      }
    return $primary_way["chemin"];

  }

  /**
   * print_primary_way() affiche le chemin le plus court pour se rendre à un produit
   */
  function print_primary_way($id, $sep= " &gt;&gt; ",$url)
  {
    $primary_way = Array();
    $way = $this->get_primary_way($id);
    $w = array();
    foreach ($way as $cat)
      {
	if ($url == '')
	  {
	    $w[] = "<a href='".DOL_URL_ROOT."/categories/viewcat.php?id=".$cat->id."'>".$cat->label."</a>";
	  }
	else
	  {
	    $w[] = "<a href='".DOL_URL_ROOT."/$url?catid=".$cat->id."'>".$cat->label."</a>";
	  }
      }
    
    return implode($sep, $w);
  }
  /**
   * Retourne un tableau contenant la liste des catégories mères
   */
  function get_meres ()
  {
    $meres = array ();

    $sql  = "SELECT fk_categorie_mere FROM ".MAIN_DB_PREFIX."categorie_association ";
    $sql .= "WHERE fk_categorie_fille = ".$this->id;

    $res  = $this->db->query ($sql);

    while ($cat = $this->db->fetch_array ($res))
      {
	$meres[] = new Categorie ($this->db, $cat['fk_categorie_mere']);
      }

    return $meres;
  }
	
  /**
   * Retourne dans un tableau tous les chemins possibles pour arriver à la catégorie
   * en partant des catégories principales, représentés par des tableaux de catégories
   */
  function get_all_ways ()
  {
    $ways = array ();

    foreach ($this->get_meres () as $mere)
      {
	foreach ($mere->get_all_ways () as $way)
	  {
	    $w   = $way;
	    $w[] = $this;

	    $ways[] = $w;
	  }
      }

    if (sizeof ($ways) == 0)
      $ways[0][0] = $this;

    return $ways;
  }
	
  /**
   * Retourne les catégories contenant le produit $id
   */
  function containing ($id)
  {
    $cats = array ();
		
    $sql  = "SELECT fk_categorie FROM ".MAIN_DB_PREFIX."categorie_product ";
    $sql .= "WHERE  fk_product = ".$id;

    $res = $this->db->query ($sql);
		
    if ($res)
      {
	while ($cat = $this->db->fetch_array ($res))
	  {
	    $cats[] = new Categorie ($this->db, $cat['fk_categorie']);
	  }

	return $cats;
      }
    else
      {
	dolibarr_print_error ($this->db);
	return -1;
      }
  }
	
	  /**
   * Retourne les catégories contenant le produit $ref
   */
  function containing_ref ($ref)
  {
    $cats = array ();
		
    $sql = "SELECT c.fk_categorie, c.fk_product, p.rowid, p.ref";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_product as c, ".MAIN_DB_PREFIX."product as p";
    $sql.= " WHERE  p.ref = '".$ref."' AND c.fk_product = p.rowid";

    $res = $this->db->query ($sql);
		
    if ($res)
    {
	     while ($cat = $this->db->fetch_array ($res))
	     {
	        $cats[] = new Categorie ($this->db, $cat['fk_categorie']);
	     }

	  return $cats;
    }
    else
    {
	     dolibarr_print_error ($this->db);
	     return -1;
    }
  }
	
  /**
   * Retourne les catégories dont le nom correspond à $nom
   * ajoute des wildcards sauf si $exact = true
   */
  function rechercher_par_nom ($nom, $exact = false)
  {
    $cats = array ();
		
    if (!$exact)
      {
	$nom = '%'.str_replace ('*', '%', $nom).'%';
      }

    $sql  = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie ";
    $sql .= "WHERE label LIKE '".$nom."'";

    $res  = $this->db->query ($sql);

    if ($res)
      {
	while ($id = $this->db->fetch_array ($res))
	  {
	    $cats[] = new Categorie ($this->db, $id['rowid']);
	  }

	return $cats;
      }
    else
      {
	return 0;
      }
  }
}
?>
