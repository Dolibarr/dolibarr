<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once(DOL_DOCUMENT_ROOT.'/telephonie/telephonie.tarif.grille.class.php');

class FournisseurTelephonie {
  var $db;
  var $id;

  /**
   * Créateur
   *
   *
   */
  function FournisseurTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;
    $this->classdir = DOL_DOCUMENT_ROOT.'/telephonie/fournisseur/commande/';
    $this->cdrformatdir = DOL_DOCUMENT_ROOT.'/telephonie/fournisseur/cdrformat/';
    return 1;
  }
  /**
   *
   *
   */
  function create($user)
  {
    $res = 0;

    if ($this->grille == 0)
      {
	$grille = new TelephonieTarifGrille($this->db);

	if ($grille->CreateGrille($user, $this->nom, 'achat') <> 0)
	  {
	    $res = -2;
	  }
	$this->grille = $grille->id;
      }

    if ($res == 0)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_fournisseur";
	$sql .= " (nom, email_commande, commande_active, class_commande,fk_tarif_grille,cdrformat)";
	$sql .= " VALUES ('".$this->nom."','".$this->email_commande."',1,'".$this->methode_commande."','".$this->grille."','".$this->cdrformat."');";
	
	if ($this->db->query($sql) )
	  {
	    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_fournisseur");
	    @mkdir(DOL_DATA_ROOT."/telephonie/cdr/atraiter/".$this->id);
	  }
	else
	  {
	    $res = -1;
	  }
      }
    return $res;
  }
  /**
   * Mets a jour les informations dans la base de donnees
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
    $sql .= ", cdrformat = '".$this->cdrformat."'";
    $sql .= ", fk_tarif_grille='".$this->grille."'";
    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }

    /* Cree le repertoire d'upload des CDR */
    if (!is_dir(DOL_DATA_ROOT."/telephonie/cdr/atraiter/".$this->id))
    {
      @mkdir(DOL_DATA_ROOT."/telephonie/cdr/atraiter/".$this->id);
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
      $sql .= ", f.class_commande, f.commande_bloque, f.fk_tarif_grille";
      $sql .= ", f.num_client, f.cdrformat";
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
	      $this->cdrformat       = $obj->cdrformat;
	      $this->grille          = $obj->fk_tarif_grille;

	      return 0;
	    }
	  else
	    {
	      dol_syslog("FournisseurTelephonie::Fetch Erreur id=".$this->id);
	      return -1;
	    }
	}
      else
	{
	  dol_syslog("FournisseurTelephonie::Fetch Erreur SQL id=".$this->id);
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
   * Retourne la liste des classe de format de commande
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
   * Retourne la liste des classe de format de commande
   */
  function array_cdrformat()
  {
    clearstatcache();
    $handle=opendir($this->cdrformatdir);
    $arr = array();

    while (($file = readdir($handle))!==false)
      {
	if (is_readable($this->cdrformatdir.$file) && substr($file, 0, 9) == 'cdrformat' && substr($file, -10) == '.class.php')
	  {
	    $name = substr($file, 10, strlen($file) -20);
	    $filebis = $this->classdir . $file;
      
	    // Chargement de la classe de numérotation
	    $classname = "CdrFormat".ucfirst($name);

	    require_once($this->cdrformatdir.$file);
	    
	    $obj = new $classname($this->db);
	    
	    $arr[$name] = $obj->nom;
	  }
	
      }
    return $arr;
  }
  /**
   * Crée une commande pour ce fournisseur
   *
   *
   *
   */
  function CreateCommande($user)
  {
    dol_syslog("FournisseurTelephonie::CreateCommande User:$user->id");

    $fileclass = $this->classdir.'commande.'.$this->class_commande.'.class.php';

    require_once($fileclass);

    $classname = "CommandeMethode".ucfirst($this->class_commande);

    dol_syslog("FournisseurTelephonie::CreateCommande user $classname");

    $ct = new $classname($this->db, $user, $this);
	
    $result = $ct->create();
	
    return $result;
  }
  /**
   * Retourne un tableau des founisseurs actifs
   *
   *
   */
  function getActives()
  {
    $fourns = array();
    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $resql = $this->db->query($sql);

    if ($resql)
      {
	while ($obj = $this->db->fetch_object($resql))
	  {
	    $fourns[$obj->rowid] = stripslashes($obj->nom);
	  }
      }
	
    $this->db->free($resql);

    return $fourns;
  }
}
?>
