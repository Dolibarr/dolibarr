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

class DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function DolibarrModules($DB)
  {
    $this->db = $DB ;
  }
  /*
   *
   *
   *
   */

  function _init($array_sql)
  {
    /*
     *  Activation du module:
     *  Insère les constantes dans llx_const
     */
    $err = 0;


    $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = '".$this->const_name."', value='1', visible = 0";

    if (!$this->db->query($sql))
      {
	$err++;
      }

    // Ajout des boxes dans llx_boxes_def
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
	        // Si non trouve
            if (strlen($note)){
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible) VALUES ('$name','$type','$val','$note','$visible')";
            }elseif (strlen($val))
            {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,visible) VALUES ('$name','$type','$val','$visible')";
            }
            else
            {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,visible) VALUES ('$name','$type','$visible')";
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
  /*
   *
   *
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
