<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class TelephonieService {
  var $db;

  var $id;
  var $ligne;

  function TelephonieService($DB)
  {
    global $config;

    $this->db = $DB;
    $this->error_message = '';
    $this->statuts[0] = "Inactif";
    $this->statuts[1] = "Actif";

    return 0;
  }
  /*
   *
   *
   */
  function update($user)
  {
    $this->montant = ereg_replace(",",".",$this->montant);

    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_service";
    $sql .= " SET ";
    $sql .= " libelle = '$this->libelle' ";
    $sql .= ", libelle_facture = '$this->libelle' ";
    $sql .= ", montant = '$this->montant' ";
    $sql .= ", fk_user_modif = $user->id ";
    $sql .= ", date_modif = now() ";

    $sql .= " WHERE rowid = $this->id";

    if ( $this->db->query($sql) )
      {
	return 0;
      }
    else
      {
	print $this->db->error();
	print $sql ;
	return -1;
      }
  }

  /*
   *
   *
   */
  function create($user)
  {

    $this->montant = ereg_replace(",",".",$this->montant);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_service";
    $sql .= " (ref, libelle, libelle_facture, montant, fk_user_creat, date_creat)";
    $sql .= " VALUES (";
    $sql .= " '$this->ref','$this->libelle','$this->libelle_facture','$this->montant',$user->id, now())";
    
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id();
	return 0;
      }
    else
      {
	
	$this->error_message = "Echec de la création du service !";
	dolibarr_syslog("TelephonieService::Create Error -1");
	return -1;
      }
  }
  /*
   *
   *
   */

  function fetch($id)
    {
      $sql = "SELECT s.rowid, s.libelle, s.libelle_facture, s.montant, s.statut";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_service as s";      
      $sql .= " WHERE s.rowid = ".$id;

      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->id              = $obj->rowid;
	      $this->libelle         = stripslashes($obj->libelle);
	      $this->libelle_facture = stripslashes($obj->libelle_facture);
	      $this->montant         = $obj->montant;
	      $this->statut          = $obj->statut;

	      $result = 0;
	    }
	  else
	    {
	      $result = -2;
	    }

	  $this->db->free();
	}
      else
	{
	  /* Erreur select SQL */
	  print $this->db->error();
	  $result = -1;
	}

      return $result;
  }

}

?>
