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

class Livre {
  var $db ;

  var $id ;
  var $oscid ;
  var $ref;
  var $price;
  var $annee;
  var $editeurid;
  var $titre;
  var $description;
  var $price ;
  var $status ;

  Function Livre($DB, $id=0) {
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

		$sql = "INSERT INTO llx_livre (oscid, fk_user_author) VALUES ($idosc, ".$user->id.")";
	    
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

    $sql = "INSERT INTO llx_livre_to_auteur (fk_livre, fk_auteur) values ($id, $gaid)";

    if ( $this->db->query($sql) )
      {      
	return 1;
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }
  }
  /*
   *
   *
   */
  Function liste_auteur()
  {
    $ga = array();

    $sql = "SELECT a.rowid, a.nom FROM llx_auteur as a, llx_livre_to_auteur as l";
    $sql .= " WHERE a.rowid = l.fk_auteur AND l.fk_livre = ".$this->id;
    $sql .= " ORDER BY a.nom";

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
   */
  Function unlinkcategorie($categories_id)
  {

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_to_categories ";

    $sql .= " WHERE products_id=".$this->oscid;
    $sql .= " AND categories_id=".$categories_id;

    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }
    }
  /*
   *
   *
   */
  Function linkcategorie($categories_id)
  {

    $sql = "INSERT INTO ".DB_NAME_OSC.".products_to_categories ";

    $sql .= " (products_id, categories_id)";
    $sql .= " VALUES (".$this->oscid.",".$categories_id.")";

    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }
    }
  /*
   *
   *
   */
  Function listcategorie()
  {
    global $conf;

    $listecat = new Categorie($this->db);
    $cats = $listecat->liste_array();

    $pcat = array();

    $sql = "SELECT products_id, categories_id";
    $sql .= " FROM ".DB_NAME_OSC.".products_to_categories ";
    $sql .= " WHERE products_id = " . $this->oscid;

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);	       
		$pcat[$i] = $obj->categories_id;
		$i++;
	      }
	  }
      }
    
      foreach ($cats as $key => $value)
	{
	  $test = 0;
	  for ($i = 0 ; $i < $nump ; $i++)
	    {
	      if ($pcat[$i] == $key)
		{
		  $test = 1;
		}
	    }
	  if ($test)
	    {
	      print '<a href="/boutique/livre/fiche.php?id='.$this->id.'&action=delcat&catid='.$key.'">';
	      print '<img src="/theme/'.$conf->theme.'/img/editdelete.png" height="16" width="16" alt="Supprimer" border="0">';
	      print "</a><b>$value</b><br>";
	    }
	  else
	    {
	      print '<img src="/theme/'.$conf->theme.'/img/transparent.png" height="16" width="16" alt="Supprimer" border="0">';
	      print "$value<br>";
	    }
	}
  }
  /*
   *
   *
   */
  Function update_status($status)
  {
    $sql = "UPDATE ".DB_NAME_OSC.".products ";
    $sql .= " SET products_status = ".$status;
    $sql .= " WHERE products_id = " . $this->oscid;

    if ( $this->db->query($sql) )
      {
	$sql = "UPDATE llx_livre ";
	$sql .= " SET status = ".$status;
	$sql .= " WHERE rowid = " . $this->id;
	
	if ( $this->db->query($sql) )
	  {
	    return 1;
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
  Function updateosc()
  {
    $desc = trim(addslashes($this->description));
    $desc .= "<p>";

    $auteurs = array();
    $auteurs = $this->liste_auteur();
    foreach ($auteurs as $key => $value)
      {
	$auteursid = $key;
	$auteur = new Auteur($this->db);
	$result = $auteur->fetch($auteursid);

	if ( $result )
	  { 
	    $liste_auteurs .= '<a href="">'.addslashes($auteur->nom)."</a>, ";
	  }
      }
    $desc .= 'Auteur';

    if (sizeof($auteurs)>1)
      {
	$desc .= "s";
      }
    $desc .= ' : ' . substr($liste_auteurs, 0, strlen($liste_auteurs) - 2);

    $desc .= '<br>Année de parution : '.$this->annee;


    $editeur = new Editeur($this->db);
    $result = $editeur->fetch($this->editeurid);
    if (result)
      {
	$desc .= '<br>Editeur : ' . addslashes($editeur->nom);
      }


    $sql = "UPDATE ".DB_NAME_OSC.".products_description ";

    $sql .= " SET products_name = '".$this->titre."'";

    $sql .= ", products_description = '$desc'";

    $sql .= " WHERE products_id = " . $this->oscid;

    if ( $this->db->query($sql) )
      {
	$sql = "UPDATE ".DB_NAME_OSC.".products ";
	$sql .= "SET products_model = '".$this->ref."'";
	$sql .= ", products_price = ".$this->price."";

	$sql .= " WHERE products_id = " . $this->oscid;

	if ( $this->db->query($sql) )
	  {
	    return 1;
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
   */
  Function liste_auteur()
  {
    $ga = array();

    $sql = "SELECT a.rowid, a.nom FROM llx_auteur as a, llx_livre_to_auteur as l";
    $sql .= " WHERE a.rowid = l.fk_auteur AND l.fk_livre = ".$this->id;
    $sql .= " ORDER BY a.nom";

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
   */
  Function update($id, $user)
  {

    if (strlen($this->annee)==0)
      {
	$this->annee = 0;
      }

    $sql = "UPDATE llx_livre ";
    $sql .= " SET title = '" . trim($this->titre) ."'";
    $sql .= ", ref = '" . trim(strtoupper($this->ref)) ."'";
    $sql .= ", prix = " . $this->price ."";
    $sql .= ", annee = " . $this->annee ;
    $sql .= ", fk_editeur = " . $this->editeurid ;
    $sql .= ", description = '" . trim($this->description) ."'";

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
  Function fetch ($id, $oscid=0) {
    
    $sql = "SELECT rowid, fk_editeur, ref, prix, annee, oscid, title, description FROM llx_livre";
    if ($id)
      {
	$sql .= " WHERE rowid = $id";
      }
    if ($oscid)
      {
	$sql .= " WHERE oscid = $oscid";
      }

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id          = $result["rowid"];
	$this->ref         = $result["ref"];
	$this->price       = $result["prix"];
	$this->annee       = $result["annee"];
	$this->editeurid   = $result["fk_editeur"];
	$this->titre       = stripslashes($result["title"]);
	$this->description = stripslashes($result["description"]);
	$this->oscid       = $result["oscid"];
	
	$this->db->free();


	$sql = "SELECT products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id, products_date_added";
	$sql .= " FROM  ".DB_NAME_OSC.".products WHERE products_id = " . $this->oscid;
	
	$result = $this->db->query($sql) ;
	
	if ( $result )
	  {
	    $result = $this->db->fetch_array();
	    
	    $this->status = $result["products_status"];

	    if ($this->status)
	      {
		$this->status_text = "En vente";
	      }
	    else
	      {
		$this->status_text = "Cet article n'est pas en vente";
	      }

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
	      
    $sql = "DELETE FROM llx_livre WHERE rowid = $id";
	    
    
  }


}
?>
