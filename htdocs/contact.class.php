<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin  <regis.houssin@cap-networks.com>
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

/**
        \file       htdocs/contact.class.php
        \ingroup    societe
        \brief      Fichier de la classe des contacts
        \version    $Revision$
*/

require_once (DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");



/**
        \class      Contact
        \brief      Classe permettant la gestion des contacts
*/

class Contact 
{
    var $db;
    var $error;
    
    var $id;
    var $fullname;
    var $nom;
    var $prenom;
    var $name;
    var $firstname;
    var $address;
    var $cp;
    var $ville;
    var $fk_pays;
    
    var $code;
    var $email;
    var $birthday;

    /**
     *      \brief      Constructeur de l'objet contact
     *      \param      DB      Habler d'accès base
     *      \param      id      Id contact
     */
    function Contact($DB, $id=0) 
    {
        $this->db = $DB;
        $this->id = $id;
        
        return 1;
    }

    /**
     *      \brief      Ajout d'un contact en base
     *      \param      user        Utilisateur qui effectue l'ajout
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
        $this->name=trim($this->name);
        if (! $this->socid)
        {
            $this->socid = 0;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (datec, fk_soc, name, fk_user)";
        $sql.= " VALUES (now(),$this->socid,'$this->name',$user->id)";
    
        if ($this->db->query($sql) )
        {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");
    
            $ret=$this->update($id, $user);
            if ($ret < 0)
            {
                $this->error=$this->db->error();
                return -2;
            }                
            return $id;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /*
     *      \brief      Mise à jour des infos
     *      \param      id          id du contact à mettre à jour
     *      \param      user        Utilisateur qui effectue la mise à jour
     *      \return     int         <0 si erreur, >0 si ok
     */
    function update($id, $user=0)
    {
        dolibarr_syslog("Contact::Update id=".$id,LOG_DEBUG);

        $this->id = $id;
    
        $this->name=trim($this->name);
        $this->firstname=trim($this->firstname);
        $this->email=trim($this->email);
        $this->phone_pro=trim($this->phone_pro);
    
        if ($this->phone_pro && $this->socid > 0)
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
        $sql .= ", cp='$this->cp'";
        $sql .= ", ville='$this->ville'";
        $sql .= ", fk_pays='$this->fk_pays'";
        $sql .= ", poste='$this->poste'";
        $sql .= ", fax='$this->fax'";
        $sql .= ", email='$this->email'";
        $sql .= ", note='$this->note'";
        $sql .= ", phone = '$this->phone_pro'";
        $sql .= ", phone_perso = '$this->phone_perso'";
        $sql .= ", phone_mobile = '$this->phone_mobile'";
        $sql .= ", jabberid = '$this->jabberid'";
        if ($user) $sql .= ", fk_user_modif='".$user->id."'";
        $sql .= " WHERE idp=".$id;
    
        $result = $this->db->query($sql);
        if (! $result)
        {
            $this->error=$this->db->error();
            return -1;
        }
    
        if ($conf->ldap->enabled)
        {
            if ($conf->global->LDAP_CONTACT_ACTIVE)
            {
                $this->update_ldap($user);
            }
    
        }
        return 1;
    }
  
  /**
   *    \brief      Mise à jour de l'arbre ldap
   *    \param      user        Utilisateur qui effectue la mise à jour
   *
   */
  function update_ldap($user)
  {
    $info = array();
    dolibarr_syslog("Contact::update_ldap",LOG_DEBUG);
    
    $this->fetch($this->id);
    
    $ds = dolibarr_ldap_connect();
    
    if ($ds)
      {
	$ldapbind = dolibarr_ldap_bind($ds);
	
	if ($ldapbind)
	  {
	    if (LDAP_SERVER_TYPE == 'activedirectory') //enlever utf8 pour etre compatible Windows
	     {
	       $info["objectclass"][0] = "top";
	       $info["objectclass"][1] = "person";
	       $info["objectclass"][2] = "organizationalPerson";
	       //$info["objectclass"][3] = "inetOrgPerson";
	       $info["objectclass"][3] = "user";
	       
	       $info["cn"] = $this->firstname." ".$this->name;
	       $info["sn"] = $this->name;
	       $info["givenName"] = $this->firstname;
	       
	       if ($this->poste)
		 $info["title"] = $this->poste;
	       
	       if ($this->socid > 0)
		 {
		   $soc = new Societe($this->db);
		   $soc->fetch($this->socid);
		   $info["o"] = $soc->nom;
		   $info["company"] = $soc->nom;
		   
		   if ($soc->client == 1)
		     $info["businessCategory"] = "Clients";
		   elseif ($soc->client == 2)
		     $info["businessCategory"] = "Prospects";
		   
		   if ($soc->fournisseur == 1)
		     $info["businessCategory"] = "Fournisseurs";
		   
		   if ($soc->ville)
		     {
		       if ($soc->adresse)
			 $info["streetAddress"] = $soc->adresse;
		       
		       if ($soc->cp)
			 $info["postalCode"] = $soc->cp;
		       
		       $info["l"] = $soc->ville;
		     }
		 }
	       
	       if ($this->phone_pro)
		 $info["telephoneNumber"] = dolibarr_print_phone($this->phone_pro);
	       
	       if ($this->phone_perso)
		 $info["homePhone"] = dolibarr_print_phone($this->phone_perso);
	       
	       if ($this->phone_mobile)
		 $info["mobile"] = dolibarr_print_phone($this->phone_mobile);
	       
	       if ($this->fax)
		 $info["facsimileTelephoneNumber"] = dolibarr_print_phone($this->fax);
	       
	       if ($this->note)
		 $info["description"] = ($this->note);
	       if ($this->email)
		 $info["mail"] = $this->email;
	       
	       $dn = "cn=".$info["cn"].",".LDAP_CONTACT_DN;
	       
	       $r = @ldap_delete($ds, $dn);	   
	       
	       if (! @ldap_add($ds, $dn, $info))
		 {
		   $this->error[0] = ldap_err2str(ldap_errno($ds));
		 }  
	     }
	    else
	      {
		$info["objectclass"][0] = "top";
		$info["objectclass"][1] = "person";
		$info["objectclass"][2] = "organizationalPerson";
		$info["objectclass"][3] = "inetOrgPerson";
	       
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
			if ($soc->adresse)
			  $info["street"] = utf8_encode($soc->adresse);
			
			if ($soc->cp)
			  $info["postalCode"] = utf8_encode($soc->cp);
			
			$info["l"] = utf8_encode($soc->ville);
		      }
		  }
		
		if ($this->phone_pro)
		  $info["telephoneNumber"] = dolibarr_print_phone($this->phone_pro);
		
		if ($this->phone_perso)
		  $info["homePhone"] = dolibarr_print_phone($this->phone_perso);
		
		if ($this->phone_mobile)
		  $info["mobile"] = dolibarr_print_phone($this->phone_mobile);
		
		if ($this->fax)
		  $info["facsimileTelephoneNumber"] = dolibarr_print_phone($this->fax);
		
		if ($this->note)
		  $info["description"] = ($this->note);
		
		if(LDAP_SERVER_TYPE == 'egroupware')
		  {		
		    $info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware
		    
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
		    
		    if (strlen($user->egroupware_id) == 0)
		      {
			$user->egroupware_id = 1;
		      }
		    
		    $info["phpgwContactOwner"] = $user->egroupware_id;
		    
		    if ($this->phone_mobile)
		      $info["phpgwCellTelephoneNumber"] = dolibarr_print_phone($this->phone_mobile);
		  }
		else
		  {
		    if ($this->email)
		      $info["mail"] = $this->email;
		  }

		$dn = "cn=".$info["cn"].",".LDAP_CONTACT_DN;
		
		dolibarr_syslog("Contact::update_ldap dn : ".$dn,LOG_DEBUG);
		
		$r = @ldap_delete($ds, $dn);	   
		
		if (! @ldap_add($ds, $dn, $info))
		  {
		    $this->error[0] = ldap_err2str(ldap_errno($ds));
		    dolibarr_syslog("Contact::update_ldap error : ".$this->error[0],LOG_ERR);
		  }
	      }
	  }
	else
	  {
	    dolibarr_syslog("Contact::update_ldap bind failed",LOG_DEBUG);
	  }
	
	dolibarr_ldap_unbind($ds);
	
      }
    else
      {
	dolibarr_syslog("Contact::update_ldap Connexion failed",LOG_DEBUG);
	echo "Impossible de se connecter au serveur LDAP !";
      }
  }
  
  
  /*
   *    \brief      Mise à jour des alertes
   *    \param      id          id du contact
   *    \param      user        Utilisateur qui demande l'alerte
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
	  $this->error='Echec sql='.$sql;
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
	  $this->error='Echec sql='.$sql;
	}
 
      return $result;
    }


  /*
   *    \brief      Charge l'objet contact
   *    \param      id          id du contact
   *    \param      user        Utilisateur lié au contact pour une alerte
   *    \return     int         1 si ok, -1 si erreur
   */
  function fetch($id, $user=0)
    {
        $sql = "SELECT c.idp, c.fk_soc, c.civilite civilite_id, c.name, c.firstname,";
        $sql.= " c.address, c.cp, c.ville,";
        $sql.= " c.fk_pays, p.libelle as pays, p.code as pays_code,";
        $sql.= " c.birthday as birthday, c.poste,";
        $sql.= " c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid, c.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON c.fk_pays = p.rowid";
        $sql.= " WHERE c.idp = ". $id;
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id             = $obj->idp;
                $this->civilite_id    = $obj->civilite_id;
                $this->name           = $obj->name;
                $this->firstname      = $obj->firstname;
                $this->nom            = $obj->name;
                $this->prenom         = $obj->firstname;
    
                $this->address        = $obj->address;
                $this->cp             = $obj->cp;
                $this->ville          = $obj->ville;
        	    $this->fk_pays        = $obj->fk_pays;
        	    $this->pays_code      = $obj->fk_pays?$obj->pays_code:'';
        	    $this->pays           = $obj->fk_pays?$obj->pays:'';
    
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
            $this->db->free($resql);
    
    
            $sql = "SELECT u.rowid ";
            $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
            $sql .= " WHERE u.fk_socpeople = ". $id;
    
            $resql=$this->db->query($sql);
            if ($resql)
            {
                if ($this->db->num_rows($resql))
                {
                    $uobj = $this->db->fetch_object($resql);
    
                    $this->user_id = $uobj->rowid;
                }
                $this->db->free($resql);
            }
            else
            {
                dolibarr_syslog("Error in Contact::fetch() selectuser sql=$sql");
           	    $this->error="Error in Contact::fetch() selectuser - ".$this->db->error()." - ".$sql;
                return -1;
            }
    
    
            $sql = "SELECT count(*) ";
            $sql .= " FROM ".MAIN_DB_PREFIX."contact_facture";
            $sql .= " WHERE fk_contact = ". $id;
    
            $resql=$this->db->query($sql);
            if ($resql)
            {
                if ($this->db->num_rows($resql))
                {
                    $this->facturation = 1;
                }
                else
                {
                    $this->facturation = 0;
                }
                $this->db->free($resql);
            }
            else
            {
                dolibarr_syslog("Error in Contact::fetch() selectcontactfacture sql=$sql");
           	    $this->error="Error in Contact::fetch() selectcontactfacture - ".$this->db->error()." - ".$sql;
                return -1;
            }
    
            if ($user)
            {
                $sql = "SELECT fk_user";
                $sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
                $sql .= " WHERE fk_user = $user->id AND fk_contact = ".$id;
    
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    if ($this->db->num_rows($resql))
                    {
                        $obj = $this->db->fetch_object($resql);
    
                        $this->birthday_alert = 1;
                    }
                    $this->db->free($resql);
                 }
                else
                {
                    dolibarr_syslog("Error in Contact::fetch() selectuseralert sql=$sql");
            	    $this->error="Error in Contact::fetch() selectuseralert - ".$this->db->error()." - ".$sql;
                    return -1;
                }
            }
            
            return 1;
        }
        else
        {
            dolibarr_syslog("Error in Contact::fetch() selectsocpeople sql=$sql");
      	    $this->error="Error in Contact::fetch() selectsocpeople - ".$this->db->error()." - ".$sql;
            return -1;
        }
    }
    

  /*
   *    \brief      Efface le contact de la base et éventuellement de l'annuaire LDAP
   *    \param      id      id du contact a effacer
   */
  function delete($id)
    {
    	$sql = "SELECT c.name, c.firstname FROM ".MAIN_DB_PREFIX."socpeople as c";
      $sql .= " WHERE c.idp = ". $id;
      $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->old_name           = $obj->name;
                $this->old_firstname      = $obj->firstname;
            }
         }    
                
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
      $sql .= " WHERE idp=$id";

      $result = $this->db->query($sql);

      if (!$result) 
	{
	  print $this->db->error() . '<br>' . $sql;
	}
      
      if (defined('MAIN_MODULE_LDAP')  && MAIN_MODULE_LDAP)
	{
	  if (defined('LDAP_CONTACT_ACTIVE')  && LDAP_CONTACT_ACTIVE == 1)
	    {
	      
	      $ds = dolibarr_ldap_connect();
	      
	      if ($ds)
		{
		  $ldapbind = dolibarr_ldap_bind($ds);
		  
		  if ($ldapbind)
		    {	      
		      // delete from ldap directory
		      if (LDAP_SERVER_TYPE == 'activedirectory')
			{
			  $userdn = $this->old_firstname." ".$this->old_name; //enlever utf8 pour etre compatible Windows
			}
		      else
			{
			  $userdn = utf8_encode($this->old_firstname." ".$this->old_name);
			}		      
		      $dn = "cn=".$userdn.",".LDAP_CONTACT_DN;
		      
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
	      
	      
	      return $result;
	    }
	}
    }
  
  /*
   *    \brief      Charge les informations sur le contact, depuis la base
   *    \param      id      id du contact à charger
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
	      $obj = $this->db->fetch_object();

	      $this->id                = $obj->idp;

          if ($obj->fk_user) {
            $cuser = new User($this->db, $obj->fk_user);
            $cuser->fetch();
            $this->user_creation     = $cuser;
          }
          
	      if ($obj->fk_user_modif) {
            $muser = new User($this->db, $obj->fk_user_modif);
            $muser->fetch();
            $this->user_modification = $muser;
          }

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
