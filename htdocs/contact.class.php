<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
  \file       htdocs/contact.class.php
  \ingroup    societe
  \brief      Fichier de la classe des contacts
  \version    $Revision$
*/

require_once (DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");



/*! \class Contact
  \brief      Classe permettant la gestion des contacts
*/

class Contact 
{
  var $bs;
  var $db;

  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $code;
  var $email;
  var $birthday;

  function Contact($DB, $id=0) 
    {
      $this->db = $DB;
      $this->id = $id;
      
      return 1;
    }

  /**
   * Création du contact
   *
   */
  function create($user)
  {
    if (!$this->socid)
      {
				$this->socid = 0;
      }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (datec, fk_soc, name, fk_user) ";
    $sql .= " VALUES (now(),$this->socid,'$this->name',$user->id)";

    if ($this->db->query($sql) )
      {
				$id = $this->db->last_insert_id();

				$this->update($id, $user);

				return $id;
      }
    else
      {
				print $this->db->error() . '<br>' . $sql;
      }
  }
  /*
   * Mise à jour des infos
   *
   */
  function update($id, $user=0)
  {
    $this->id = $id;
    $this->error = array();
    
    $this->email = trim($this->email);
    
    //commenté suite a la nouvell fonction dolibarr_print_phone
    //$this->phone_pro = ereg_replace(" ","",$this->phone_pro);
    //$this->phone_perso = ereg_replace(" ","",$this->phone_perso);
    
    if (strlen($this->phone_pro) == 0 && $this->socid > 0)
      {
	$soc = new Societe($this->db);
	$soc->fetch($this->socid);
	$this->phone_pro = $soc->tel;
      }

    $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
    $sql .= "  civilite='$this->civilite_id'";
    $sql .= ", name='$this->name'";
    $sql .= ", firstname='$this->firstname'";
    $sql .= ", address='$this->address'";
//    $sql .= ", cp='$this->cp'";
//    $sql .= ", ville='$this->ville'";
    $sql .= ", poste='$this->poste'";
    $sql .= ", fax='$this->fax'";
    $sql .= ", email='$this->email'";
    $sql .= ", note='$this->note'";
    $sql .= ", phone = '$this->phone_pro'";
    $sql .= ", phone_perso = '$this->phone_perso'";
    $sql .= ", phone_mobile = '$this->phone_mobile'";
    $sql .= ", jabberid = '$this->jabberid'";

    if ($user)
      {
        $sql .= ", fk_user_modif='".$user->id."'";
      }
    $sql .= " WHERE idp=$id";
    
    $result = $this->db->query($sql);
    
    if (!$result)
      {
	dolibarr_print_error($this->db);
      }
    
    if (defined('MAIN_MODULE_LDAP')  && MAIN_MODULE_LDAP)
      {
	$this->update_ldap($user);
      }
    return $result;
  }
  
  /**
   * Mise à jour de l'arbre ldap
   *
   */
  
  function update_ldap($user)
  {
    $this->fetch($this->id);
    
    $ds = dolibarr_ldap_connect();
    
    if ($ds)
      {
	dolibarr_ldap_setversion($ds,$version);
	
	$ldapbind = dolibarr_ldap_bind($ds);
	
	if ($ldapbind)
	  {
	    
	    $info["objectclass"][0] = "organizationalPerson";
	    $info["objectclass"][1] = "inetOrgPerson";
	    
	    $info["ou"] = People;
	    $info["cn"] = utf8_encode($this->firstname." ".$this->name);
	    $info["sn"] = utf8_encode($this->name);
	    $info["givenName"] = utf8_encode($this->firstname);
	    
	    if ($this->poste)
	      $info["title"] = utf8_encode($this->poste);
	    
	    if ($this->socid > 0)
	      {
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);
		$info["o"] = utf8_encode($soc->nom);
		
		if ($soc->client == 1)
		  $info["businessCategory"] = utf8_encode("Clients");
		elseif ($soc->client == 2)
		  $info["businessCategory"] = utf8_encode("Prospects");
		
		if ($soc->fournisseur == 1)
		  $info["businessCategory"] = utf8_encode("Fournisseurs");
		
		if ($soc->ville)
		  {
		    if ($soc->address)
		      $info["street"] = ($soc->address);
		    
		    if ($soc->cp)
		      $info["postalCode"] = ($soc->cp);
		    
		    $info["l"] = utf8_encode($soc->ville);
		  }
	      }
	    
	    if ($this->phone_pro)
	      $info["telephoneNumber"] = dolibarr_print_phone($this->phone_pro);
	    
	    if ($this->phone_perso)
	      $info["homePhone"] = dolibarr_print_phone($this->phone_perso);
	    
	    if ($this->phone_mobile)
	      $info["mobile"] = dolibarr_print_phone($this->phone_mobile);
	    
	    if ($this->note)
	      			$info["description"] = ($this->note);
	    
	    if(LDAP_SERVER_TYPE == "egroupware")
	      {
		
		$info["objectclass"][2] = "phpgwContact"; // compatibilite egroupware
		
		if ($this->email)
		  $info["rfc822Mailbox"] = $this->email;
		
		$info['uidnumber'] = $this->id;
		
		$info['phpgwTz']      = 0;
		$info['phpgwMailType'] = 'INTERNET';
		$info['phpgwMailHomeType'] = 'INTERNET';
		
		$info["uid"] = $this->id. ":".$info["sn"];
		$info["phpgwContactTypeId"] = 'n';
		$info["phpgwContactCatId"] = 0;
		$info["phpgwContactAccess"] = "public";
		$info["phpgwContactOwner"] = $user->egroupware_id;
		
		if ($this->phone_mobile)
		  $info["phpgwCellTelephoneNumber"] = dolibarr_print_phone($this->phone_mobile);
	      }
	    else
	      {
		if ($this->email)
		  $info["mail"] = $this->email;
	      }
	    
	    $dnshort = explode(",", LDAP_SERVER_DN,2);
	    
	    $dn = "cn=".$info["cn"].","."ou=".$info["ou"].",".$dnshort[1];
	    
	    $r = @ldap_delete($ds, $dn);
	    
	    if (! ldap_add($ds, $dn, $info))
	      {
		$this->error[0] = ldap_err2str(ldap_errno($ds));
		var_dump($info);
	      }
	  }
	else
	  {
	    echo "Connection au dn $dn échoué !";
	  }
	
	dolibarr_ldap_unbind($ds);
	
      }
    else
      {
	echo "Impossible de se connecter au serveur LDAP !";
      }
  }
  
  
  /*
   * Mise à jour des infos persos
   *
   */
  function update_perso($id, $user=0)
    {
      // Mis a jour contact
      $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET idp=$id ";

      if ($this->birthday>0)
	{
	  if (eregi('\-',$this->birthday))
	    {
	      // Si date = chaine
	      $sql .= ", birthday='".$this->birthday."'";
	    }        
	  else
	    {
	      // Si date = timestamp
	      $sql .= ", birthday=".$this->db->idate($this->birthday);
	    }
	}
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);
      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}
      
      // Mis a jour alerte birthday
      if ($this->birthday_alert)
	{
	  $sql = "INSERT into ".MAIN_DB_PREFIX."user_alert(type,fk_contact,fk_user) ";
	  $sql.= "values (1,".$id.",".$user->id.")";
	}
      else
	{
	  $sql = "DELETE from ".MAIN_DB_PREFIX."user_alert ";
	  $sql.= "where type=1 AND fk_contact=".$id." AND fk_user=".$user->id;
	}
      $result = $this->db->query($sql);
      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}
 
      return $result;
    }


  /*
   *
   *
   */
  function fetch($_id, $user=0) 
    {
      $sql = "SELECT c.idp, c.fk_soc, c.civilite civilite_id, c.name, c.firstname, c.address, c.birthday as birthday, poste, phone, phone_perso, phone_mobile, fax, c.email, jabberid, c.note";
      $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
      $sql .= " WHERE c.idp = ". $_id;

      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id             = $obj->idp;
	      $this->civilite_id    = $obj->civilite_id;
	      $this->name           = $obj->name;
	      $this->firstname      = $obj->firstname;
	      $this->nom            = $obj->name;
	      $this->prenom         = $obj->firstname;

	      $this->address        = $obj->address;

	      $this->societeid      = $obj->fk_soc;
	      $this->socid          = $obj->fk_soc;
	      $this->poste          = $obj->poste;

	      $this->fullname       = $this->firstname . ' ' . $this->name;
	      
	      $this->phone_pro      = dolibarr_print_phone($obj->phone);
	      $this->fax            = dolibarr_print_phone($obj->fax);
	      $this->phone_perso    = dolibarr_print_phone($obj->phone_perso);
	      $this->phone_mobile   = dolibarr_print_phone($obj->phone_mobile);

	      $this->code           = $obj->code;
	      $this->email          = $obj->email;
	      $this->jabberid       = $obj->jabberid;
	      $this->mail           = $obj->email;

	      $this->birthday       = $obj->birthday;
	      $this->birthday_alert = $obj->birthday_alert;
	      $this->note           = $obj->note;

	    }
	  $this->db->free();


	  $sql = "SELECT u.rowid ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	  $sql .= " WHERE u.fk_socpeople = ". $_id;

	  if ($this->db->query($sql))
	    {
	      if ($this->db->num_rows()) 
		{
		  $uobj = $this->db->fetch_object($result , 0);
		  
		  $this->user_id = $uobj->rowid;
		}
	    }
	  $this->db->free();


	  $sql = "SELECT count(*) ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."contact_facture";
	  $sql .= " WHERE fk_contact = ". $_id;

	  if ($this->db->query($sql))
	    {
	      if ($this->db->num_rows()) 
		{
		  $this->facturation = 1;
		}
	      else
		{
		  $this->facturation = 0;
		}
	    }
	  else
	    {
	      dolibarr_syslog("Error in Contact::fetch() id=$_id");
	    }

	  $this->db->free();

	  if ($user)
	    {
	      $sql = "SELECT fk_user";
	      $sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
	      $sql .= " WHERE fk_user = $user->id AND fk_contact = ".$_id;
	      
	      if ($this->db->query($sql)) 
		{
		  if ($this->db->num_rows())
		    {
		      $obj = $this->db->fetch_object($result , 0);
		      
		      $this->birthday_alert = 1;
		    } 
		  else 
		    {
		      print $this->db->error();
		      dolibarr_syslog("Error in Contact::fetch() id=$_id");
		    }
		}
	    }
	}
      else
	{
	  dolibarr_syslog("Error in Contact::fetch() id=$_id");
	  print $this->db->error();
	}
    }
  /*
   *
   *
   */
  function delete($id)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);

      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}

      if (defined('MAIN_MODULE_LDAP')  && MAIN_MODULE_LDAP)
	{
	  $ds = dolibarr_ldap_connect();
      
	  if ($ds)
	    {
	      ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
	      
	      $ldapbind = dolibarr_ldap_bind($ds);
	      
	      if ($ldapbind)
		{	      
		  // delete from ldap directory
		  $dn = utf8_encode("cn=".$this->old_firstname." ".$this->old_name.", ".LDAP_SERVER_DN) ;
		  
		  $r = @ldap_delete($ds, $dn);
		}
	      else
		{
		  echo "LDAP bind failed...";
		}	      	      
	      ldap_close($ds);
	    
	    }
	  else
	    {
	      echo "Unable to connect to LDAP server";
	    }
	}
      
      return $result;
    }
  /*
   * Information sur l'objet
   *
   */
  function info($id) 
    {
      $sql = "SELECT c.idp, ".$this->db->pdate("datec")." as datec, fk_user";
      $sql .= ", ".$this->db->pdate("tms")." as tms, fk_user_modif";
      $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
      $sql .= " WHERE c.idp = $id";
      
      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id                = $obj->idp;

	      $cuser = new User($this->db, $obj->fk_user);
	      $cuser->fetch();
	      $this->user_creation     = $cuser;

	      $muser = new User($this->db, $obj->fk_user_modif);
	      $muser->fetch();
	      $this->user_modification = $muser;

	      $this->date_creation     = $obj->datec;
	      $this->date_modification = $obj->tms;

	    }
	  $this->db->free();

	}
      else
	{
	  print $this->db->error();
	}
    }
}
?>
