<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
        \file       htdocs/cactioncomm.class.php
        \ingroup    commercial
        \brief      Fichier de la classe des types d'actions commerciales
        \version    $Revision$
*/


/*!     \class      CActionComm
	    \brief      Classe permettant la gestion des différents types d'actions commerciales
*/

class CActioncomm {
  var $id;
  var $libelle;

  /**
   *    \brief      Constructeur
   *    \param      db          Handler d'accès base de donnée
   */
  function CActioncomm($DB=0)
    {
      $this->db = $DB;
    }

  /**
   *    \brief      Charge l'objet type d'action depuis la base
   *    \param      db          handle d'accès base
   *    \param      id          id du type d'action à récupérer
   *    \return     int         1=ok, 0=aucune action, -1=erreur
   */
  function fetch($db, $id)
    {

      $sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE id=$id;";
      
      if ($db->query($sql) )
	{
	  if ($db->num_rows())
	    {
	      $obj = $db->fetch_object();
	      
	      $this->id = $id;
	      $this->libelle = $obj->libelle;
	      
	      $db->free();

	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
      else
	{
	  print $db->error();
	  return -1;
	}    
    }

  /*
   *    \brief      Renvoi la liste des type d'actions existant
   *    \param      active      1 ou 0 pour un filtre sur l'etat actif ou non ('' par defaut)
   *    \return     array       tableau des types d'actions actifs
   */
  function liste_array($active='')
  {
    $ga = array();

    $sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_actioncomm";
    if ($active != '') {
        $sql.=" WHERE active=$active";
    }
    $sql .= " ORDER BY id";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object();
		
		$ga[$obj->id] = $obj->libelle;
		$i++;
	      }
	  }
	return $ga;
      }
    else
      {
    dolibarr_print_error($this->db);
      }    
  }

  
  /*
   *    \brief      Renvoie le nom d'un type d'action
   *    \param      id          id du type d'action
   *    \return     string      libelle du type d'action
   */
  function get_nom($id)
    {

      $sql = "SELECT libelle nom FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE id='$id';";
      
      $result = $this->db->query($sql);
      
    if ($result)
      {
    	if ($this->db->num_rows())
    	  {
    	    $obj = $this->db->fetch_object($result);
    	    return $obj->nom;
    	  }
    	$this->db->free();
       }
     else {
        dolibarr_print_error($db);   
       }    
       
    }
   
  
}    
?>
