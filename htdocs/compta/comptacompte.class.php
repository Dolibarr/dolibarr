<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file       htdocs/compta/comptacompte.class.php
  \ingroup    compta
  \brief      Fichier de la classe des comptes comptable
  \version    $Revision$
*/


/*! \class ComptaCompte
    \brief Classe permettant la gestion des comptes généraux de compta
*/

class ComptaCompte
{
  var $db ;

  var $id ;
  var $num;
  var $intitule;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  id          id compte (0 par defaut)
   */
	 
  function ComptaCompte($DB, $id=0)
    {
      $this->db = $DB;
      $this->id   = $id ;
    }  

  /**
   *    \brief  Insère le produit en base
   *    \param  user utilisateur qui effectue l'insertion
   */
	 
  function create($user) 
    {
      if (strlen(trim($this->numero)) && strlen(trim($this->intitule)))
	{

	  $sql = "SELECT count(*)";
	  $sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux ";
	  $sql .= " WHERE numero = '" .trim($this->numero)."'";
	  
	  $result = $this->db->query($sql) ;
	  
	  if ( $result )
	    {
	      $row = $this->db->fetch_array();
	      if ($row[0] == 0)
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."compta_compte_generaux (date_creation, fk_user_author, numero,intitule)";
		  $sql .= " VALUES (now(),".$user->id.",'".$this->numero."','".$this->intitule."')";
		  
		  $result = $this->db->query($sql);
		  if ( $result )
		    {
		      $id = $this->db->last_insert_id();
		      
		      if ($id > 0)
			{
			  $this->id = $id;
			  return 0;
			}
		      else
			{
			  return -2;
			}
		    }
		  else
		    {
		      return -1;
		    }
		}
	      else
		{
		  return -3;
		}
	    }
	}
      else
	{
	  return -4;
	}
    }
}
?>
