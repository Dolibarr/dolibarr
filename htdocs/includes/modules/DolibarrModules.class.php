<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*! \file htdocs/includes/modules/DolibarrModules.class.php
        \brief      Fichier de description et activation des modules Dolibarr
*/


/*! \class DolibarrModules
        \brief      Classe mère des classes de description et activation des modules Dolibarr
*/
class DolibarrModules
{
   var $db;         // Handler d'accès aux base
   var $boxes;      // Tableau des boites
   var $const;      // Tableau des constantes


   /*!  \brief      Constructeur
    *   \param      DB      handler d'accès base
    */
  function DolibarrModules($DB)
  {
    $this->db = $DB ;
  }


   /*!  \brief      Fonction d'activation. Insère en base les constantes et boites du module
    *   \param      array_sql       tableau de requete sql a exécuter à l'activation
    */
  function _init($array_sql)
  {
    // Insère les constantes
    $err = 0;
    $sql_del = "delete from ".MAIN_DB_PREFIX."const where name = '".$this->const_name."';";
		$this->db->query($sql_del);
		$sql ="insert into ".MAIN_DB_PREFIX."const (name,value,visible) values
		('".$this->const_name."','1',0);";
    //$sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = '".$this->const_name."', value='1', visible = 0";

    if (!$this->db->query($sql))
      {
	$err++;
      }

    // Insère les boxes dans llx_boxes_def
    foreach ($this->boxes as $key => $value)
      {
	$titre = $this->boxes[$key][0];
	$file  = $this->boxes[$key][1];

	$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."boxes_def WHERE name ='".$titre."'";

	if ( $this->db->query($sql) )
	  {
	    $row = $this->db->fetch_row($sql);	    
	    if ($row[0] == 0)
	      {
		$sql = "insert into ".MAIN_DB_PREFIX."boxes_def (name, file) values ('".$titre."','".$file."')";
		if (! $this->db->query($sql) )
		  {
		    $err++;
		  }
	      }
	  }
	else
	  {
	    $err++;
	  }
      }

    foreach ($this->const as $key => $value)
      {
	$name   = $this->const[$key][0];
	$type   = $this->const[$key][1];
	$val    = $this->const[$key][2];
	$note   = $this->const[$key][3];
	$visible= $this->const[$key][4]||'0';

	$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."const WHERE name ='".$name."'";

	if ( $this->db->query($sql) )
	  {
	    $row = $this->db->fetch_row($sql);
	    
	    if ($row[0] == 0)
	      {
	        // Si non trouve // '$visible'
            if (strlen($note)){
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible) VALUES ('$name','$type','$val','$note',0);";
            }elseif (strlen($val))
            {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,visible) VALUES ('$name','$type','$val',0);";
            }
            else
            {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,visible) VALUES ('$name','$type',0);";
            }
            
            if (! $this->db->query($sql) )
            {
            $err++;
            }
	      }
	  }
	else
	  {
	    $err++;
	  }
      }


    /*
     *
     */

    for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
      {
	if (! $this->db->query($array_sql[$i]))
	  {
	    $err++;
	  }
      }

    if ($err > 0)
      {
	return 0;
      }
    else
      {
	return 1;
      }
  }


   /*!  \brief      Fonction de désactivation. Supprime de la base les constantes et boites du module
    *   \param      array_sql       tableau de requete sql a exécuter à la désactivation
    */
  function _remove($array_sql)
  {
    $err = 0;

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = '".$this->const_name."'";

    if (!$this->db->query($sql))
      {
	$err++;
      }

    for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
      {
	
	if (!$this->db->query($array_sql[$i]))
	  {
	    $err++;
	  }
      }

    /*
     * Boites
     */
    foreach ($this->boxes as $key => $value)
      {
	$titre = $this->boxes[$key][0];
	$file  = $this->boxes[$key][1];

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = '".$file."'";
	if (! $this->db->query($sql) )
	  {
	    $err++;
	  }
      }

    if ($err > 0)
      {
	return 0;
      }
    else
      {
	return 1;
      }
  }

}
?>
