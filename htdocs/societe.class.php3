<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Societe {
  var $db;

  var $id;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $tel;
  var $fax;
  var $url;
  var $siren;
  var $client;
  var $note;
  var $fournisseur;
 

  Function Societe($DB, $id=0) {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    $this->client = 0;
    $this->fournisseur = 0;
    return 1;
  }
  /*
   *
   *
   *
   */
  Function create() {

    print $this->url;

    $sql = "INSERT INTO llx_societe (nom, datec, datea, client) ";
    $sql .= " VALUES ('".trim($this->nom)."', now(), now(), $this->client);";

    if ($this->db->query($sql) ) {
      $id = $this->db->last_insert_id();

      $this->update($id);

      return $id;
    }
  }
  /*
   *
   *
   *
   */
  Function update($id)
    {

      $sql = "UPDATE llx_societe ";
      $sql .= " SET nom = '" . trim($this->nom) ."'";
      $sql .= ",address = '" . trim($this->adresse) ."'";
      $sql .= ",cp = '" . trim($this->cp) ."'";
      $sql .= ",ville = '" . trim($this->ville) ."'";
      $sql .= ",tel = '" . trim($this->tel) ."'";
      $sql .= ",fax = '" . trim($this->fax) ."'";
      $sql .= ",url = '" . trim($this->url) ."'";
      $sql .= ",siren = '" . trim($this->siren) ."'";
      $sql .= ",client = " . $this->client ;
      $sql .= ",fournisseur = " . $this->fournisseur ;
      $sql .= " WHERE idp = " . $id .";";
      
      if (! $this->db->query($sql)) 
      {
	print $this->db->error();
      }
    }
  /*
   *
   *
   *
   */
  Function factures_impayes()
    {
      $facimp = array();
      /*
       * Lignes
       */      
      $sql = "SELECT f.rowid";
      $sql .= " FROM llx_facture as f WHERE f.fk_soc = ".$this->id;
      $sql .= " AND f.fk_statut = 1 AND f.paye = 0";

      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $objp = $this->db->fetch_object($i);
	      $array_push($facimp, $objp->rowid);
	      $i++;
	      print $i;
	    }
	  
	  $this->db->free();
	} 
      return $facimp;
    }

  /*
   *
   *
   */
  Function fetch($socid) {
    $this->id = $socid;

    $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc,";

    $sql .= " s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren, client, fournisseur";

    $sql .= " FROM llx_societe as s";
    $sql .= " WHERE s.idp = ".$this->id;

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);

	$this->nom = stripslashes($obj->nom);
	$this->adresse =  stripslashes($obj->address);
	$this->cp = $obj->cp;
	$this->ville =  stripslashes($obj->ville);

	$this->url = $obj->url;
	$this->tel = $obj->tel;
	$this->fax = $obj->fax;

	$this->siren = $obj->siren;

	$this->client = $obj->client;
	$this->fournisseur = $obj->fournisseur;

	$this->note = $obj->note;

      }
      $this->db->free();
    } else {
      print $this->db->error();
    }
  }
  /*
   *
   *
   *
   */

  Function attribute_prefix()
    {
      $sql = "SELECT nom FROM llx_societe WHERE idp = $this->id";
      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->num_rows() )
	    {
	      $nom = $this->db->result(0,0);
	      $this->db->free();
	      
	      $prefix = strtoupper(substr($nom, 0, 2));
      
	      $sql = "SELECT count(*) FROM llx_societe WHERE prefix_comm = '$prefix'";
	      if ( $this->db->query( $sql) )
		{
		  if ( $this->db->result(0, 0) )
		    {
		      $this->db->free();
		    }
		  else
		    {
		      $this->db->free();

		      $sql = "UPDATE llx_societe set prefix_comm='$prefix' WHERE idp=$this->id";
		      
		      if ( $this->db->query( $sql) )
			{
			  
			}
		      else
			{
			  print $this->db->error();
			}
		    }
		}
	      else
		{
		  print $this->db->error();
		}
	    }
	}
      else
	{
	  print $this->db->error();
	}
      return $prefix;
    }
  /*
   *
   *
   *
   */

  Function get_nom($id)
    {

      $sql = "SELECT nom FROM llx_societe WHERE idp=$id;";
      
      $result = $this->db->query($sql);
      
    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object($result , 0);
	    
	    $this->nom = $obj->nom;
	    
	  }
	$this->db->free();
      }
  }
  /*
   *
   *
   */
  Function contact_email_array()
    {
      $contact_email = array();

      $sql = "SELECT idp, email, name, firstname FROM socpeople WHERE fk_soc = $this->id";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
	      
		  $contact_email[$obj->idp] = "$obj->firstname $obj->name &lt;$obj->email&gt;";
		  $i++;
		}
	    }
	  return $contact_email;
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
  Function contact_get_email($rowid)
    {

      $sql = "SELECT idp, email, name, firstname FROM socpeople WHERE idp = $rowid";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      
	      $obj = $this->db->fetch_object(0);
	      
	      $contact_email = "$obj->firstname $obj->name <$obj->email>";

	    }
	  return $contact_email;
	}
      else
	{
	  print $this->db->error();
	  print "<p>$rowid";
	}
      
    }

}

?>
