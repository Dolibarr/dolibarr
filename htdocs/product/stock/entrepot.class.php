<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/product/stock/entrepot.class.php
		\ingroup    stock
		\brief      Fichier de la classe de gestion des entrepots
		\version    $Revision$
*/


/**     \class      Entrepot
		\brief      Classe permettant la gestion des entrepots
*/

class Entrepot
{
  var $db;
  var $error;
  
  var $id;
  var $libelle;
  var $description;
  var $statut;
  var $lieu;
  var $address;
  var $cp;
  var $ville;
  var $pays_id;

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
  		  $this->error = "Libellé obligatoire";
		  return 0;
	  }
	  
      $this->db->begin();
      
	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (datec, fk_user_author)";
	  $sql .= " VALUES (now(),".$user->id.")";

	  $result=$this->db->query($sql);
	  if ($result)
	    {
	      $id = $this->db->last_insert_id(MAIN_DB_PREFIX."entrepot");	      
	      if ($id > 0)
		{
		  $this->id = $id;

		  if ( $this->update($id, $user) > 0)
		    {
		      $this->db->commit();
		      return $id;
		    }
		  else
		    {
		      $this->db->rollback();
		      return -3;
		    }
		}
		else {
            $this->error="Failed to get insert id";
  	        return -2;
		}
	    }
	  else
	    {
          $this->error="Failed to insert warehouse";
          $this->db->rollback();
	      return -1;
	    }

    }

  /*
   *    \brief      Mise a jour des information d'un entrepot
   *    \param      id      id de l'entrepot à modifier
   *    \param      user
   */
  function update($id, $user)
    {
      $this->libelle=trim($this->libelle);
      $this->description=trim($this->description);

      $this->lieu=trim($this->lieu);
      $this->address=trim($this->address);
      $this->cp=trim($this->cp);
      $this->ville=trim($this->ville);
      $this->pays_id=trim($this->pays_id);
      
	  $sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
	  $sql .= " SET label = '" . $this->libelle ."'";
	  $sql .= ",description = '" . $this->description ."'";
	  $sql .= ",statut = " . $this->statut ;
	  $sql .= ",description = '" . $this->description ."'";
	  $sql .= ",lieu = '" . $this->lieu ."'";
	  $sql .= ",address = '" . $this->address ."'";
	  $sql .= ",cp = '" . $this->cp ."'";
	  $sql .= ",ville = '" . $this->ville ."'";
	  $sql .= ",fk_pays = " . $this->pays_id?$this->pays_id:'0' ;
	  $sql .= " WHERE rowid = " . $id;
	  
	  if ( $this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
          $this->error=$this->db->error()." sql=$sql";;
	      return -1;
	    }
	}


  /**
   *    \brief      Recupéeration de la base d'un entrepot
   *    \param      id      id de l'entrepot a récupérer
   */
  function fetch ($id)
    {    
        $sql  = "SELECT rowid, label, description, statut, lieu, address, cp, ville, fk_pays";
        $sql .= " FROM ".MAIN_DB_PREFIX."entrepot";
        $sql .= " WHERE rowid = $id";
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $obj=$this->db->fetch_object($result);
        
            $this->id             = $obj->rowid;
            $this->ref            = $obj->rowid;
            $this->libelle        = $obj->label;
            $this->description    = $obj->description;
            $this->statut         = $obj->statut;
            $this->lieu           = $obj->lieu; 
            $this->address        = $obj->address;
            $this->cp             = $obj->cp;
            $this->ville          = $obj->ville;
            $this->pays_id        = $obj->pays_id;
        
            $this->db->free($result);
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
   }


  /*
   * \brief     Charge les informations d'ordre info dans l'objet entrepot
   * \param     id      id de l'entrepot a charger
   */
  function info($id) 
    {
      $sql  = "SELECT e.rowid, ".$this->db->pdate("datec")." as datec,";
      $sql .= " ".$this->db->pdate("tms")." as datem,";
      $sql .= " fk_user_author";
      $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
      $sql .= " WHERE e.rowid = ".$id;
      
      $result=$this->db->query($sql);
      if ($result) 
	{
	  if ($this->db->num_rows($result)) 
	    {
	      $obj = $this->db->fetch_object($result);

	      $this->id = $obj->rowid;

          if ($obj->fk_user_author) {
    	      $cuser = new User($this->db, $obj->fk_user_author);
    	      $cuser->fetch();
    	      $this->user_creation     = $cuser;
          }
          
          if ($obj->fk_user_valid) {
    	      $vuser = new User($this->db, $obj->fk_user_valid);
    	      $vuser->fetch();
    	      $this->user_validation = $vuser;
          }
          
	      $this->date_creation     = $obj->datec;
	      $this->date_modification = $obj->datem;

	    }
	    
	  $this->db->free($result);

	}
      else
	{
	  dolibarr_print_error($this->db);
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
