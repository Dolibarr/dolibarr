<?PHP
/* Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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

class Societe {
  var $db;

  var $id;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $departement_id;
  var $pays_id;
  var $tel;
  var $fax;
  var $url;
  var $siren;
  var $forme_juridique_code;
  var $forme_juridique;
  var $client;
  var $note;
  var $fournisseur;
 

  Function Societe($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    $this->client = 0;
    $this->fournisseur = 0;
    $this->effectif_id  = 0;
    $this->forme_juridique_code  = 0;

    return 1;
  }
  /*
   *
   *
   *
   */
  Function create()
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, datec, datea, client) ";
    $sql .= " VALUES ('".trim($this->nom)."', now(), now(), '$this->client');";

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
    if (strlen(trim($this->nom)) == 0)
      {
	$this->nom = "VALEUR MANQUANTE";
      }

    if (strlen(trim($this->capital)) == 0)
      {
	$this->capital = 0;
      }

    $this->tel = ereg_replace(" ","",$this->tel);
    $this->tel = ereg_replace("\.","",$this->tel);
    $this->fax = ereg_replace(" ","",$this->fax);
    $this->fax = ereg_replace("\.","",$this->fax);


    /*
     * TODO simpliste pour l'instant mais remplit 95% des cas
     * à améliorer
     */

    if ($this->departement_id == -1 && $this->pays_id == 1)
      {
	if (strlen(trim($this->cp)) == 5)
	  {
	    $depid = departement_rowid($this->db, 
				       substr(trim($this->cp),0,2), 
				       $this->pays_id);
	    if ($depid > 0)
	      {
		$this->departement_id = $depid;
	      }
	  }
      }
    

    $sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
    $sql .= " SET nom = '" . trim($this->nom) ."'";
    $sql .= ",address = '" . trim($this->adresse) ."'";
    $sql .= ",cp = '" . trim($this->cp) ."'";
    $sql .= ",ville = '" . trim($this->ville) ."'";
    $sql .= ",fk_departement = '" . $this->departement_id ."'";
    $sql .= ",fk_pays = '" . $this->pays_id ."'";
    $sql .= ",tel = '" . $this->tel ."'";
    $sql .= ",fax = '" . $this->fax ."'";
    $sql .= ",url = '" . trim($this->url) ."'";
    $sql .= ",siren = '" . trim($this->siren) ."'";
    $sql .= ",siret = '" . trim($this->siret) ."'";
    $sql .= ",ape = '" . trim($this->ape) ."'";
    $sql .= ",prefix_comm = '" . trim($this->prefix_comm) ."'";
    $sql .= ",tva_intra = '" . trim($this->tva_intra) ."'";
    $sql .= ",capital = '" . $this->capital ."'";
    $sql .= ",fk_effectif = '" . $this->effectif_id ."'";
    $sql .= ",fk_forme_juridique = '" . $this->forme_juridique_code ."'";
    $sql .= ",client = '" . $this->client ."'";
    $sql .= ",fournisseur = '" . $this->fournisseur ."'";
    $sql .= " WHERE idp = '" . $id ."';";
    if ($this->db->query($sql)) 
      {
	return 0;
      }
    else
      {
	if ($this->db->errno() == 1062)
	  {
	    // Doublons sur le prefix commercial
	    return -1;
	  }
	print $this->db->error();
      }
  }

  /*
   *
   *
   */
  Function fetch($socid)
    {
      $this->id = $socid;

      $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc, prefix_comm";
      $sql .= ",". $this->db->pdate("s.tms")." as date_update";
      $sql .= ", s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren, client, fournisseur";
      $sql .= ", s.siret, s.capital, s.ape, s.tva_intra, s.rubrique, s.fk_effectif";
      $sql .= ", e.libelle as effectif, e.id as effectif_id";
      $sql .= ", s.fk_forme_juridique as forme_juridique_code, fj.libelle as forme_juridique";
      $sql .= ", s.fk_departement, s.fk_pays, s.fk_stcomm";
      $sql .= ", p.libelle as pays";
      $sql .= ", st.libelle as stcomm";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
      $sql .= ", ".MAIN_DB_PREFIX."c_effectif as e";
      $sql .= ", ".MAIN_DB_PREFIX."c_pays as p";
      $sql .= ", ".MAIN_DB_PREFIX."c_stcomm as st";
      $sql .= ", ".MAIN_DB_PREFIX."c_forme_juridique as fj";
      $sql .= " WHERE s.idp = ".$this->id;
      $sql .= " AND s.fk_stcomm = st.id";
      $sql .= " AND s.fk_effectif = e.id";
      $sql .= " AND s.fk_pays = p.rowid";
      $sql .= " AND s.fk_forme_juridique = fj.code";

      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->date_update = $obj->date_update;

	      $this->nom = stripslashes($obj->nom);
	      $this->adresse =  stripslashes($obj->address);
	      $this->cp = $obj->cp;
	      $this->ville =  stripslashes($obj->ville);

	      $this->adresse_full =  stripslashes($obj->address) . "\n". $obj->cp . " ". stripslashes($obj->ville);

	      $this->departement_id = $obj->fk_departement;
	      $this->pays_id = $obj->fk_pays;
	      $this->pays = $obj->fk_pays?$obj->pays:'';

	      $this->stcomm_id = $obj->fk_stcomm; // statut commercial
	      $this->statut_commercial = $obj->stcomm; // statut commercial

	      $this->url = $obj->url;

	      $this->tel = $obj->tel;
	      $this->fax = $obj->fax;
	      
	      $this->siren     = $obj->siren;
	      $this->siret     = $obj->siret;
	      $this->ape       = $obj->ape;
	      $this->capital   = $obj->capital;

	      $this->tva_intra      = $obj->tva_intra;
	      $this->tva_intra_code = substr($obj->tva_intra,0,2);
	      $this->tva_intra_num  = substr($obj->tva_intra,2);

	      $this->effectif       = $obj->effectif;
	      $this->effectif_id    = $obj->effectif_id;

	      $this->forme_juridique_code= $obj->forme_juridique_code;
	      $this->forme_juridique     = $obj->forme_juridique;

	      $this->prefix_comm = $obj->prefix_comm;
	      
	      $this->client = $obj->client;
	      $this->fournisseur = $obj->fournisseur;

	      if ($this->client == 1)
		{

		  $this->nom_url = '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$this->id.'">'.$obj->nom.'</a>';
		}
	      elseif($this->client == 2)
		{
		  $this->nom_url = '<a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$this->id.'">'.$obj->nom.'</a>';
		}
	      else
		{
		  $this->nom_url = '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$this->id.'">'.$obj->nom.'</a>';
		}

	      $this->rubrique = $obj->rubrique;
	      
	      $this->note = $obj->note;

	      return 1;	      
	    }
	  else
	    {
	      print "Aucun enregistrement trouvé<br>$sql";
	      return -2;
	    }

	  $this->db->free();
	}
      else
	{
	  /* Erreur select SQL */
	  print $this->db->error();
	  return -1;
	}
  }

  /*
   * Suppression d'une societe. 
   * TODO: Cette fonction n'est pas utilisée.. 
   * Attente des contraintes d'intégrité dans MySql
   */
  Function delete($id)
    {
      $sql = "DELETE from ".MAIN_DB_PREFIX."societe ";
      $sql .= " WHERE idp = " . $id .";";

      if (! $this->db->query($sql))
	{
	  print $this->db->error();
	}

      // Suppression du répertoire document
      $docdir = SOCIETE_OUTPUTDIR . "/$id";

      // Cette fonction permet de supprimer le répertoire de la societe
      // Meme s'il contient des documents.
      function deldir($dir)
	{
	  $current_dir = opendir($dir);
	  while($entryname = readdir($current_dir))
	    {
	      if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!=".."))
		{
		  deldir("${dir}/${entryname}");
		}
	      elseif($entryname != "." and $entryname!="..")
		{
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
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = '".$this->id . "'";
      $sql .= " AND f.fk_statut = '1' AND f.paye = '0'";

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
   *
   */

  Function attribute_prefix()
    {
      $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE idp = '$this->id'";
      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->num_rows() )
	    {
	      $nom = preg_replace("/[[:punct:]]/","",$this->db->result(0,0));
	      $this->db->free();
	      
	      $prefix = strtoupper(substr($nom, 0, 4));
      
	      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."societe WHERE prefix_comm = '$prefix'";
	      if ( $this->db->query( $sql) )
		{
		  if ( $this->db->result(0, 0) )
		    {
		      $this->db->free();
		    }
		  else
		    {
		      $this->db->free();

		      $sql = "UPDATE ".MAIN_DB_PREFIX."societe set prefix_comm='$prefix' WHERE idp='$this->id'";
		      
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
  /**
   * Définit la société comme un client
   *
   *
   */
  Function set_as_client()
    {
      if ($this->id)
	{
	  $sql  = "UPDATE ".MAIN_DB_PREFIX."societe ";
	  $sql .= " SET client = 1";
	  $sql .= " WHERE idp = " . $this->id .";";
	  
	  return $this->db->query($sql);
	}
    }

  /*
   * Renvoie le nom d'une societe a partir d'un id
   *
   */
  Function get_nom($id)
    {

      $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."societe WHERE idp='$id';";
      
      $result = $this->db->query($sql);
      
    if ($result)
      {
    	if ($this->db->num_rows())
    	  {
    	    $obj = $this->db->fetch_object($result , 0);
    	    return $obj->nom;
    	  }
    	$this->db->free();
       }
     else {
        dolibarr_print_error($db);   
       }    
       
    }

  /*
   *
   *
   */
  Function contact_email_array()
    {
      $contact_email = array();

      $sql = "SELECT idp, email, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '$this->id'";
      
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

      $sql = "SELECT idp, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '$this->id'";
      
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

      $sql = "SELECT idp, email, name, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE idp = '$rowid'";
      
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
  /*
   *
   *
   */
  Function effectif_array()
    {
      $effs = array();
      /*
       * Lignes
       */      
      $sql = "SELECT id, libelle";
      $sql .= " FROM ".MAIN_DB_PREFIX."c_effectif";
      $sql .= " ORDER BY id ASC";
      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $objp = $this->db->fetch_object($i);
	      $effs[$objp->id] = $objp->libelle;
	      $i++;
	    }
	  $this->db->free();
	} 
      return $effs;
    }
  /*
   *
   *
   */
  Function forme_juridique_array()
    {
      $fj = array();
      /*
       * Lignes
       */      
      $sql = "SELECT code, libelle";
      $sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique";
      $sql .= " ORDER BY code ASC";
      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $objp = $this->db->fetch_object($i);
	      $fj[$objp->code] = $objp->libelle;
	      $i++;	  
	    }
	  $this->db->free();
	} 
      return $fj;
    }
}

?>
