<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/adherents/adherent_type.class.php
        \ingroup    adherent
		\brief      Fichier de la classe gérant les types d'adhérents
		\author     Rodolphe Quiedeville
		\version    $Revision$
*/

/*! \class AdherentType
		\brief      Classe gérant les types d'adhérents
*/


class AdherentType
{
  var $id;
  var $libelle;
  var $statut;
  var $cotisation;  /**< Soumis à la cotisation */
  var $errorstr;
  var $mail_valid;	/**< mail envoye lors de la validation */
  var $commentaire; /**< commentaire */
  var $vote;				/** droit de vote ? */

/*!
		\brief AdherentType
		\param DB				handler accès base de données
*/

  function AdherentType($DB)
    {
      $this->db = $DB ;
      $this->statut = 1;
    }

/*!
		\brief print_error_list
*/

	function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }

/*!
		\brief fonction qui permet de créer le status de l'adhérent
		\param userid			userid de l'adhérent
*/

	function create($userid)
    {
      /*
       *  Insertion dans la base
       */

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_type (statut)";
      $sql .= " VALUES ($this->statut)";

      $result = $this->db->query($sql);

      if ($result)
	{
	  $this->id = $this->db->last_insert_id();
	  return $this->update();
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}
    }

  function update()
    {

      $sql = "UPDATE ".MAIN_DB_PREFIX."adherent_type SET ";
      $sql .= "libelle = '".$this->libelle ."'";
      $sql .= ",statut=".$this->statut;
      $sql .= ",cotisation='".$this->cotisation."'";
      $sql .= ",note='".$this->commentaire."'";
      $sql .= ",vote='".$this->vote."'";
      $sql .= ",mail_valid='".$this->mail_valid."'";

      $sql .= " WHERE rowid = $this->id";

      $result = $this->db->query($sql);

      if ($result)
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}
    }

/*!
		\brief fonction qui permet de supprimer le status de l'adhérent
		\param rowid
*/

	function delete($rowid)
  {

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_type WHERE rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
	return 0;
      }
  }

	/*!
		\brief fonction qui permet de récupérer le status de l'adhérent
		\param rowid
*/

	function fetch($rowid)
  {
    $sql = "SELECT *";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
    $sql .= " WHERE d.rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {

	    $obj = $this->db->fetch_object();

	    $this->id             = $obj->rowid;
	    $this->libelle        = $obj->libelle;
	    $this->statut         = $obj->statut;
	    $this->cotisation     = $obj->cotisation;
	    $this->mail_valid     = $obj->mail_valid;
	    $this->commentaire    = $obj->note;
	    $this->vote    = $obj->vote;
	  }
      }
    else
      {
	print $this->db->error();
      }

  }

  function liste_array()
    {
      $projets = array();

      $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."adherent_type";

      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object();

		  $projets[$obj->rowid] = $obj->libelle;
		  $i++;
		}
	    }
	  return $projets;
	}
      else
	{
	  print $this->db->error();
	}

    }

}
?>
