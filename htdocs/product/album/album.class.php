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

class Album {
  var $db ;

  var $id ;
  var $oscid ;
  var $ref;
  var $annee;
  var $titre;
  var $description;
  var $price ;
  var $status ;

  Function Album($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  Function create($user) {

    if (strlen($this->annee))
      {
	$this->annee = 0;
      }

    $sql = "insert into ".DB_NAME_OSC.".products (products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id, products_date_added) ";
    $sql .= "values ('', '', 'Array', '', null, '', '0', '0', '8', now())";

    if ($this->db->query($sql) )
      {
	$idosc = $this->db->last_insert_id();

	$sql = "insert into ".DB_NAME_OSC.".products_to_categories (products_id, categories_id) values ($idosc, 0)";

	if ($this->db->query($sql) )
	  {

	    $sql = "insert into ".DB_NAME_OSC.".products_description (products_name, products_description, products_url, products_id, language_id) values ('".trim($this->titre)."', '".trim($this->description)."', '', $idosc, '1')";
	    
	    if ($this->db->query($sql) )	    
	      {

		$sql = "INSERT INTO llx_album (osc_id, fk_user_author) VALUES ($idosc, ".$user->id.")";
	    
		if ($this->db->query($sql) )
		  {
		    $id = $this->db->last_insert_id();
		    
		    if ( $this->update($id, $user) )
		      {
			return $id;
		      }
		  }
		else
		  {
		    print $this->db->error() . ' in ' . $sql;
		  }
		
	      }
	    else
	      {
		print $this->db->error() . ' in ' . $sql;
	      }
	    
	  }
	else
	  {
	    print $this->db->error() . ' in ' . $sql;
	  }
	
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }
    
  }

  /*
   *
   *
   *
   */
  Function linkga($id, $gaid)
  {

    $sql = "INSERT INTO llx_album_to_groupart (fk_album, fk_groupart) values ($id, $gaid)";

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   */
  Function liste_groupart()
  {
    $ga = array();

    $sql = "SELECT g.rowid, g.nom FROM llx_groupart as g, llx_album_to_groupart as l";
    $sql .= " WHERE g.rowid = l.fk_groupart AND l.fk_album = ".$this->id;
    $sql .= " ORDER BY g.nom";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->rowid] = $obj->nom;
		$i++;
	      }
	  }
	return $ga;
      }
    else
      {
	print $this->db->error();
      }    
  }
  /*
   *
   *
   *
   */
  Function updateosc()
  {

    $sql = "UPDATE ".DB_NAME_OSC.".products_description ";

    $sql .= " SET products_name = '".$this->titre."'";

    $desc .= '<br>Info supplémentaires';
    $ga = array();
    $ga = $this->liste_groupart();
    if (sizeof($ga) == 1)
    {
      foreach ($ga as $key => $value)
	{
	  $gaid = $key;
	}


      $groupart = new Groupart($this->db);
      $result = $groupart->fetch($gaid);

      if ( $result )
	{ 

	  $desc = $groupart->nom."<p>";

	  $desc .= addslashes($this->description);
	  
	  $desc .= "<p><b>Autres albums</b> : ";

	  $gas = $groupart->liste_albums();
	  $i = 0;
	  $sizegas = sizeof($gas) - 1;
	  foreach ($gas as $key => $value)
	    {
	      if ($key <> $this->id)
		{

		  $otha = new Album($this->db);
		  $otha->fetch($key);

		  $desc .= '<a href="'.OSC_CATALOG_URL.'product_info.php?products_id='.$otha->oscid.'">'.$value."</a>";
		  $i++; 
		  if ($sizegas > $i)
		    {
		      $desc .= ", ";
		    }
		}
	    }
	}
    }


    $sql .= ", products_description = '$desc'";

    $sql .= " WHERE products_id = " . $this->oscid;

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  Function update($id, $user)
  {

    if (strlen($this->annee)==0)
      {
	$this->annee = 0;
      }

    $sql = "UPDATE llx_album ";
    $sql .= " SET title = '" . trim($this->titre) ."'";
    $sql .= ", ref = '" . trim(strtoupper($this->ref)) ."'";
    $sql .= ", annee = " . $this->annee ;
    $sql .= ",description = '" . trim($this->description) ."'";

    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  Function fetch ($id) {
    
    $sql = "SELECT rowid, ref, annee, osc_id, title, description FROM llx_album WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id          = $result["rowid"];
	$this->ref         = $result["ref"];
	$this->annee       = $result["annee"];
	$this->titre       = $result["title"];
	$this->description = $result["description"];
	$this->oscid       = $result["osc_id"];
	
	$this->db->free();


	$sql = "SELECT products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id, products_date_added";
	$sql .= " FROM  ".DB_NAME_OSC.".products WHERE products_id = " . $this->oscid;
	
	$result = $this->db->query($sql) ;
	
	if ( $result )
	  {
	    $result = $this->db->fetch_array();
	    
	    $this->status = $result["products_status"];
	    $this->price  = $result["products_price"];

	    $this->db->free();
	  }
	else
	  {
	    print $this->db->error();
	  }
      }
    else
      {
	print $this->db->error();
      }
    
    return $result;
  }


  /*
   *
   *
   */
  Function delete($user) {

    $sql = "DELETE FROM ".DB_NAME_OSC.".products WHERE products_id = $idosc ";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_to_categories WHERE products_id = $idosc";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_description WHERE products_id = $idosc";
	      
    $sql = "DELETE FROM llx_album WHERE rowid = $id";
	    
    
  }


}
?>
