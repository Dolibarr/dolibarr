<?PHP
/* Copyright (C) 2003 Brian Fraval <brian@fraval.org>
 * Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   * Suppression d'une societe. 
   * TODO: Cette fonction n'est pas utilisée.. 
   * Attente des contraintes d'intégrité dans MySql
   */
  Function delete($id)
    {
      $sql = "DELETE from llx_societe ";
      $sql .= " WHERE idp = " . $id .";";

      if (! $this->db->query($sql))
	{
	  print $this->db->error();
	}

      // Suppression du répertoire document
      $docdir = SOCIETE_OUTPUTDIR . "/$id";

      // Cette fonction permet de supprimer le répertoire de la societe
      // Meme s'il contient des documents.
      function deldir($dir){
	$current_dir = opendir($dir);
	while($entryname = readdir($current_dir)){
	  if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!="..")){
	    deldir("${dir}/${entryname}");
	  }elseif($entryname != "." and $entryname!=".."){
	    unlink("${dir}/${entryname}");
	  }
	}
	closedir($current_dir);
	rmdir(${dir});
      } 

      if (file_exists ($docdir))
	{
	  deldir($docdir);
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
  Function fetch($socid)
    {
      $this->id = $socid;

      $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc, prefix_comm,";
      $sql .= " s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren, client, fournisseur";
      $sql .= " FROM llx_societe as s";
      $sql .= " WHERE s.idp = ".$this->id;

      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->nom = stripslashes($obj->nom);
	      $this->adresse =  stripslashes($obj->address);
	      $this->cp = $obj->cp;
	      $this->ville =  stripslashes($obj->ville);
	      
	      $this->url = $obj->url;
	      $this->nom_url = '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$this->id.'">'.$obj->nom.'</a>';
	      $this->tel = $obj->tel;
	      $this->fax = $obj->fax;
	      
	      $this->siren = $obj->siren;

	      $this->prefix_comm = $obj->prefix_comm;
	      
	      $this->client = $obj->client;
	      $this->fournisseur = $obj->fournisseur;
	      
	      $this->note = $obj->note;

	      return 1;
	      
	    }
	  $this->db->free();
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

      $sql = "SELECT idp, email, name, firstname FROM llx_socpeople WHERE fk_soc = $this->id";
      
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
  Function contact_array()
    {
      $contacts = array();

      $sql = "SELECT idp, name, firstname FROM llx_socpeople WHERE fk_soc = $this->id";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
	      
		  $contacts[$obj->idp] = "$obj->firstname $obj->name";
		  $i++;
		}
	    }
	  return $contacts;
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

      $sql = "SELECT idp, email, name, firstname FROM llx_socpeople WHERE idp = $rowid";
      
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
