<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

class Propal_Model_Pdf {
  
  var $nom;

  function Propal_Model_Pdf($DB)
    {
      $this->db = $DB ;
    }
  /*
   *
   *
   *
   */

  function liste_array()
    {
      $projets = array();

      $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."propal_model_pdf";

      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
	      
		  $projets[$obj->nom] = $obj->nom;
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
