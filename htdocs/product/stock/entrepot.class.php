<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/product/stock/entrepot.class.php
		\ingroup    stock
		\brief      Fichier de la classe de gestion des entrepots
		\version    $Revision$
*/


/**     \class      Entrepot
		\brief      Classe permettant la gestion des entrepots
*/

class Entrepot
{
  var $db ;

  var $id ;
  var $libelle;
  var $description;
  var $statut;

  /*
   *    \brief      Constructeur de l'objet entrepot
   *    \param      DB      Handler d'accès à la base de donnée
   */
  function Entrepot($DB)
    {
        global $langs;
        $this->db = $DB;
        
        $this->statuts[0] = $langs->trans("Closed");
        $this->statuts[1] = $langs->trans("Opened");
    }

  /*
   *    \brief      Creation d'un entrepot en base
   *    \param      Objet user qui crée l'entrepot
   */
  function create($user) 
    {
	  // Si libelle non defini, erreur
	  if ($this->libelle == '') {
  		  $this->mesg_error = "Libellé obligatoire";
		  return 0;
	  }
	  
      if ($this->db->begin())
	{
	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (datec, fk_user_author)";
	  $sql .= " VALUES (now(),".$user->id.")";

	  if ($this->db->query($sql) )
	    {
	      $id = $this->db->last_insert_id();	      
	      if ($id > 0)
		{
		  $this->id = $id;

		  if ( $this->update($id, $user) )
		    {
		      $this->db->commit();
		      return $id;
		    }
		  else
		    {
		      $this->db->rollback();
		    }


		}
	    }
	  else
	    {
	      return -1;
	    }
	}
    }

  /*
   *    \brief      Mise a jour des information d'un entrepot
   *    \param      id      id de l'entrepot à modifier
   *    \param      user
   */
  function update($id, $user)
    {
      if (strlen(trim($this->libelle)))
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
	  $sql .= " SET label = '" . trim($this->libelle) ."'";
	  $sql .= ",description = '" . trim($this->description) ."'";
	  $sql .= ",statut = " . $this->statut ;
	  
	  $sql .= " WHERE rowid = " . $id;
	  
	  if ( $this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
	      return -1;
	    }
	}
      else
	{
	  $this->mesg_error = "Vous devez indiquer une référence";
	  return 0;
	}
    }

  /**
   *    \brief      Recupéeration de la base d'un entrepot
   *    \param      id      id de l'entrepot a récupérer
   */
  function fetch ($id)
    {    
      $sql = "SELECT rowid, label, description, statut";
      $sql .= " FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id             = $result["rowid"];
	  $this->ref            = $result["ref"];
	  $this->libelle        = stripslashes($result["label"]);
	  $this->description    = stripslashes($result["description"]);
	  $this->statut         = $result["statut"];

	  $this->db->free();
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  return -1;
	}
  }

  /**
   *    \brief      Renvoie la liste des entrepôts ouverts
   */
  function list_array()
  {
    $liste = array();

    $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."entrepot WHERE statut = 1";

      $result = $this->db->query($sql) ;
      $i = 0;
      $num = $this->db->num_rows();

      if ( $result )
	{
	  while ($i < $num)
	    {
	      $row = $this->db->fetch_row($i);
	      $liste[$row[0]] = $row[1];
	      $i++;
	    }
	  $this->db->free();
	}
      return $liste;
  }

  /**
   *    \brief      Renvoie le stock (nombre de produits) de l'entrepot
   */
    function nb_products()
    {
        $sql = "SELECT sum(reel) FROM llx_product_stock WHERE fk_entrepot = ".$this->id;
        
          $result = $this->db->query($sql) ;
        
          if ( $result )
        {
          $row = $this->db->fetch_row(0);
          return $row[0];
        
          $this->db->free();
        }
          else
        {
          return 0;
        }
    }


    /**
     *    \brief      Retourne le libellé du statut d'un entrepot (ouvert, fermé)
     *    \return     string      Libellé
     */
    function getLibStatut()
    {
    	return $this->LibStatut($this->statut);
    }
    
    /**
     *    \brief      Renvoi le libellé d'un statut donné
     *    \param      statut      id statut
     *    \return     string      Libellé
     */
    function LibStatut($statut)
    {
        return $this->statuts[$statut];
    }
  
}
?>
