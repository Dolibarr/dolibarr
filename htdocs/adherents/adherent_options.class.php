<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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

/*
 * Classe de gestion de la table des champs optionels
 */
class AdherentOptions
{
  var $id;
  var $db;
  /*
   * Tableau contenant le nom des champs en clef et la definition de
   * ces champs
   */
  var $attribute_name;

  var $errorstr;
  /*
   * Constructor
   *
   */
  Function AdherentOptions($DB, $id='') 
    {
      $this->db = $DB ;
      $this->id = $id;
      $this->errorstr = array();
      $this->attribute_name = array();
    }
  /*
   * Print error_list
   *
   *
   */
  Function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }
  /*
   * Check argument
   *
   */
  Function check($minimum=0) 
    {
      $err = 0;

      if (strlen(trim($this->societe)) == 0)
	{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	    {
	      $error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	      $err++;
	    }
	}

      if (strlen(trim($this->adresse)) == 0)
	{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
	}

      /*
       * Return errors
       *
       */

      if ($err)
	{
	  $this->errorstr = $error_string;
	  return 0;
	}
      else
	{
	  return 1;
	}

    }
  /*
   * Création d'un attribut optionnel supplementaire
   * Ceci correspond a une modification de la table 
   * et pas a un rajout d'enregistrement
   * Prend en argument : le nom de l'attribut et eventuellemnt son
   * type et sa longueur
   *
   */
  Function create($attrname,$type='varchar',$length=255) {
    /*
     *  Insertion dans la base
     */
    $sql = "ALTER TABLE llx_adherent_options ";
    $sql .= " ADD $attrname $type($length)";
      
    if ($this->db->query($sql)) 
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

  /*
   * Suppression d'un attribut
   *
   */
  Function delete($attrname)

  {
    $sql = "ALTER TABLE llx_adherent_options DROP COLUMN $attrname";

    if ( $this->db->query( $sql) )
      {
	return 1;
      }
    else
      {
	print "Err : ".$this->db->error();
	print "<h2><br>$sql<br></h2>";
	return 0;
      }

  }
  
  /*
   * fetch optional attribute name
   */
  Function fetch_name_optionals()
  {
    $array_name_options=array();
    $sql = "SHOW COLUMNS FROM llx_adherent_options";

    if ( $this->db->query( $sql) )
      {
      if ($this->db->num_rows())
	{
	while ($tab = $this->db->fetch_object())
	  {
	  if ($tab->Field != 'optid' && $tab->Field != 'tms' && $tab->Field != 'adhid')
	    {
	      // we can add this attribute to adherent object
	      $array_name_options[]=$tab->Field;
	      $this->attribute_name[$tab->Field]=$tab->Type;
	    }
	  }
	return $array_name_options;
      }else{
	return array();
      }
    }else{
      print $this->db->error();
      return array() ;
    }
    
  }
}
?>
