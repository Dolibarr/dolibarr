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

class CommercialTelephonie {
  var $db;
  var $id;

  /**
   * Créateur
   *
   *
   */
  function CommercialTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;

    return 0;
  }
  /**
   *
   *
   */
  function create()
    {
      $error = 0;

      if (strlen(trim($this->nom)) == 0)
	{
	  $this->error_string["nom"] = "Valeur manquante";
	  $error++;
	}
      if (strlen(trim($this->prenom)) == 0)
	{
	  $this->error_string["prenom"] = "Valeur manquante";
	  $error++;
	}

   
      if ($error == 0)
	{

	  $nuser = new User($this->db);
	  $nuser->nom = trim($this->nom);
	  $nuser->prenom = trim($this->prenom);
	  $nuser->admin = 0;
	  $nuser->email = trim($this->email);
	  $nuser->login = substr($this->nom,0,3).substr($this->prenom,0,3);

	  $uid = $nuser->create();

	  if ($uid > 0)
	    {
	      $nuser->SetInGroup(TELEPHONIE_GROUPE_COMMERCIAUX_ID);

	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux ";
	      $sql .= " (fk_distributeur, fk_user)";
	      
	      $sql .= " VALUES ('".$this->distri."','$uid')";
	      
	      
	      if ($this->db->query($sql))
		{
		  
		}
	      else
		{
		  dol_syslog("DistributeurTelephonie::Create");
		  $this->error_string["prenom"] = "Erreur SQL : $sql";
		  $this->error_string["nom"] = $this->db->error();
		  $error++;
		}
	      
	    }
	  else
	    {
	      $this->error_string["prenom"] = "Erreur création user";
	      $this->error_string["nom"] = $user->error();
	      $error++;
	    }

	}
      else
	{
	  
	}
      
      return $error;
      
    }




}
?>
