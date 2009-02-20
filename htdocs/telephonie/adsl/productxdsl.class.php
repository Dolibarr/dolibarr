<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class ProductXdsl {
  var $db;
  var $id;

  /**
   * Constructeur de la classe
   *
   */
  function ProductXdsl($DB, $id=0, $user=0)
  {
    global $config;
    $this->id = $id;
    $this->db = $DB;
    $this->error_message = '';

    return 1;
  }
  /**
   * Cree le product dans la base de donnees
   *
   */
  function Create($user)
  {
    if ($this->prodid > 0)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_adsl_type";
	$sql .= " (fk_product, commande_active, intitule)";
	$sql .= " VALUES (";
	$sql .= " $this->prodid,1,'".addslashes($this->intitule)."')";	
	
	$resql = $this->db->query($sql);

	if ( $resql )
	  {
	    $this->id = $this->db->last_insert_id($resql);
	    return 0;
	  }
	else
	  {
	    dol_syslog("ProductXdsl::Create Error -3");
	    return -3;	    
	  }
      }
    else
      {
	dol_syslog("ProductXdsl::Create Error -2");
	return -2;
      }
  }
  /**
   *
   *
   */
  function SwitchCommandeActive($id)
  {
    $sql= "UPDATE ".MAIN_DB_PREFIX."telephonie_adsl_type as t";
    $sql.= " SET commande_active = abs(commande_active -1) WHERE t.rowid =".$id.";";

    $resql=$this->db->query($sql);

    if ($resql)
      {
	return 0;
      }
    else
      {
	dol_syslog("ProductXdsl::SwithCommandeActive Error -20", LOG_ERR);
	return -20;
      }

  }
  /**
   * Retourne la liste des produits
   *
   */
  function ListArray()
  {
    $arr = array();

    $sql = "SELECT t.rowid, t.intitule, t.commande_active";
    $sql.= " FROM ".MAIN_DB_PREFIX."product as p,".MAIN_DB_PREFIX."telephonie_adsl_type as t";
    $sql.= " WHERE p.rowid=t.fk_product;";

    $resql=$this->db->query($sql);

    if ($resql)
      {
	while ($obj=$this->db->fetch_object($resql))
	  {
	    $arr[$obj->rowid]['rowid'] = $obj->rowid;
	    $arr[$obj->rowid]['intitule'] = stripslashes($obj->intitule);
	    $arr[$obj->rowid]['commande_active'] = $obj->commande_active;
	  }

      }
    else 
      {
	dol_print_error($this->db);
	$this->error=$this->db->error();

      }

    return $arr;    
  }
 
}
?>
