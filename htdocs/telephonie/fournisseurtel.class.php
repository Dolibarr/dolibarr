<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class FournisseurTelephonie {
  var $db;

  var $id;

  function FournisseurTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;
    $this->classdir = DOL_DOCUMENT_ROOT.'/telephonie/fournisseur/commande/';
    return 1;
  }
  /**
   *
   *
   */
  function create()
  {
    $res = 0;
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " (nom, email_commande, commande_active)";
    $sql .= " VALUES ('".$this->nom."','".$this->email_commande."',1)";

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   */
  function update()
  {
    $res = 0;

    $sql = "UPDATE  ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " SET ";
    $sql .= " email_commande = '".$this->email_commande."'";
    $sql .= ", num_client = '".$this->num_client."'";
    $sql .= ", class_commande = '".$this->methode_commande."'";
    $sql .= ", commande_bloque = '".$this->commande_bloque."'";

    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   */
  function fetch($id)
    {
      $this->id = $id;

      $sql = "SELECT f.rowid, f.nom, f.email_commande, f.commande_active";
      $sql .= ", f.class_commande, f.commande_bloque";
      $sql .= ", f.num_client";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
      $sql .= " WHERE f.rowid = ".$this->id;
	  
      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->nom             = stripslashes($obj->nom);
	      $this->num_client      = $obj->num_client;
	      $this->email_commande  = $obj->email_commande;
	      $this->commande_enable = $obj->commande_active;
	      $this->class_commande  = $obj->class_commande;
	      $this->commande_bloque = $obj->commande_bloque;

	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("FournisseurTelephonie::Fetch Erreur id=".$this->id);
	      return -1;
	    }
	}
      else
	{
	  dolibarr_syslog("FournisseurTelephonie::Fetch Erreur SQL id=".$this->id);
	  return -2;
	}
    }
  /**
   *
   *
   */
  function active()
  {
    $res = 0;
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " SET  commande_active = 1";
    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   */
  function desactive()
  {
    $res = 0;
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " SET  commande_active = 0";
    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   *
   *
   */
  function array_methode()
  {
    clearstatcache();
 
    $handle=opendir($this->classdir);

    $arr = array();

    while (($file = readdir($handle))!==false)
      {

	dolibarr_syslog($file);
	
	if (is_readable($this->classdir.$file) && substr($file, 0, 8) == 'commande' && substr($file, -10) == '.class.php')
	  {

	    $name = substr($file, 9, strlen($file) -19);

	    $filebis = $this->classdir . $file;
      
	    // Chargement de la classe de numérotation
	    $classname = "CommandeMethode".ucfirst($name);

	    require_once($filebis);
	    
	    $obj = new $classname($this->db);

	    $arr[$name] = $obj->nom;
	  }
	
      }
    return $arr;
  }
  /**
   *
   *
   *
   *
   */
  function CreateCommande($user)
  {
    dolibarr_syslog("FournisseurTelephonie::CreateCommande User:$user->id");

    $fileclass = $this->classdir.'commande.'.$this->class_commande.'.class.php';

    require_once($fileclass);

    $classname = "CommandeMethode".ucfirst($this->class_commande);

    dolibarr_syslog("FournisseurTelephonie::CreateCommande user $classname");

    $ct = new $classname($this->db, $user, $this);
	
    $result = $ct->create();
	
    return $result;
  }

}
?>
