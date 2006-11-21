<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/contact.class.php
        \ingroup    societe
        \brief      Fichier de la classe des contacts
        \version    $Revision$
*/

require_once (DOL_DOCUMENT_ROOT."/lib/ldap.class.php");



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
    var $socid;					// fk_soc
    
    var $code;
    var $email;
    var $birthday;

    var $ref_facturation;       // Nb de reference facture pour lequel il est contact
    var $ref_contrat;           // Nb de reference contrat pour lequel il est contact
    var $ref_commande;          // Nb de reference commande pour lequel il est contact
    var $ref_propal;            // Nb de reference propal pour lequel il est contact

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
		// Nettoyage parametres
        $this->name=trim($this->name);
        if (! $this->socid) $this->socid = 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (datec, fk_soc, name, fk_user)";
        $sql.= " VALUES (now(),";
        if ($this->socid > 0) $sql.= " $this->socid,";
        else $sql.= "null,";
        $sql.= "'$this->name',$user->id)";
	   	dolibarr_syslog("Contact.class::create sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");
    
            $result=$this->update($this->id, $user, 0);
            if ($result < 0)
            {
                $this->error=$this->db->error();
                return -2;
            }

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTACT_CREATE',$this,$user,$langs,$conf);
            // Fin appel triggers

			// \todo	Mettre en trigger
        	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
        	{
	    	    $this->create_ldap($user);
    		}
    		        
            return $this->id;
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Contact.class::create ".$this->error);
            return -1;
        }
    }

    /*
     *      \brief      Mise à jour des infos
     *      \param      id          	Id du contact à mettre à jour
     *      \param      user        	Objet utilisateur qui effectue la mise à jour
     *      \param      call_trigger    0=non, 1=oui
     *      \return     int         	<0 si erreur, >0 si ok
     */
    function update($id, $user=0, $call_trigger=1)
    {
        $this->id = $id;
    
    	// Nettoyage parametres
        $this->name=trim($this->name);
        $this->firstname=trim($this->firstname);
        $this->email=trim($this->email);
        $this->phone_pro=trim($this->phone_pro);
    
        if (! $this->phone_pro && $this->socid > 0)
        {
            $soc = new Societe($this->db);
            $soc->fetch($this->socid);
            $this->phone_pro = $soc->tel;
        }
    
        $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
        if ($this->socid > 0) $sql .= "  fk_soc='".addslashes($this->socid)."'";
        if ($this->socid == -1) $sql .= "  fk_soc=null";
        $sql .= ", civilite='".addslashes($this->civilite_id)."'";
        $sql .= ", name='".addslashes($this->name)."'";
        $sql .= ", firstname='".addslashes($this->firstname)."'";
        $sql .= ", address='".addslashes($this->address)."'";
        $sql .= ", cp='".addslashes($this->cp)."'";
        $sql .= ", ville='".addslashes($this->ville)."'";
        $sql .= ", fk_pays='".addslashes($this->fk_pays)."'";
        $sql .= ", poste='".addslashes($this->poste)."'";
        $sql .= ", fax='".addslashes($this->fax)."'";
        $sql .= ", email='".addslashes($this->email)."'";
        $sql .= ", note='".addslashes($this->note)."'";
        $sql .= ", phone = '".addslashes($this->phone_pro)."'";
        $sql .= ", phone_perso = '".addslashes($this->phone_perso)."'";
        $sql .= ", phone_mobile = '".addslashes($this->phone_mobile)."'";
        $sql .= ", jabberid = '".addslashes($this->jabberid)."'";
        if ($user) $sql .= ", fk_user_modif=".$user->id;
        $sql .= " WHERE idp=".$id;
        dolibarr_syslog("Contact.class::update sql=".$sql,LOG_DEBUG);
    
        $result = $this->db->query($sql);
        if (! $result)
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }

		if ($call_trigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTACT_UPDATE',$this,$user,$langs,$conf);
            // Fin appel triggers

			// \todo	Mettre en trigger
        	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
        	{
    	    	$this->update_ldap($user);
    	    }
    	}
    	

        return 1;
    }
  
  
	/**
	*	\brief      Mise à jour de l'arbre LDAP
	*   \param      user        Utilisateur qui efface
	*	\return		int			<0 si ko, >0 si ok
	*/
	function delete_ldap($user)
	{
		global $conf, $langs;

        //if (! $conf->ldap->enabled || ! $conf->global->LDAP_CONTACT_ACTIVE) return 0;

		dolibarr_syslog("Contact.class::delete_ldap this->id=".$this->id,LOG_DEBUG);
	
		$ldap=new Ldap();
		$result=$ldap->connect();
		if ($result)
		{
			$bind='';
			if ($conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS)
			{
				dolibarr_syslog("Contact.class::delete_ldap authBind user=".$conf->global->LDAP_ADMIN_DN,LOG_DEBUG);
				$bind=$ldap->authBind($conf->global->LDAP_ADMIN_DN,$conf->global->LDAP_ADMIN_PASS);
			}
			else
			{
				dolibarr_syslog("Contact.class::delete_ldap bind",LOG_DEBUG);
				$bind=$ldap->bind();
			}
			
			if ($bind)
			{
				$info=$this->_load_ldap_info($info);

				$dn = $conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS].",".$conf->global->LDAP_CONTACT_DN;
				$result=$ldap->delete($dn);
				
				return $result;
			}
		}
		else
		{
			$this->error="Failed to connect to LDAP server !";
			dolibarr_syslog("Contact.class::update_ldap Connexion failed",LOG_DEBUG);
			return -1;
		}
	}
	
	
	function _load_ldap_info($info)
	{
		global $conf,$langs;

		if ($conf->global->LDAP_SERVER_TYPE == 'activedirectory') 
		{
			$info["objectclass"]=array("top",
									   "person",
									   "organizationalPerson",
									   "user");
		}
		else
		{
			$info["objectclass"]=array("top",
									   "person",
									   "organizationalPerson",
									   "inetOrgPerson");
		}	

		// Champs 
		if ($this->fullname  && $conf->global->LDAP_FIELD_FULLNAME) $info[$conf->global->LDAP_FIELD_FULLNAME] = $this->fullname;
		if ($this->name && $conf->global->LDAP_FIELD_NAME) $info[$conf->global->LDAP_FIELD_NAME] = $this->name;
		if ($this->firstname && $conf->global->LDAP_FIELD_FIRSTNAME) $info[$conf->global->LDAP_FIELD_FIRSTNAME] = $this->firstname;
		if ($this->poste) $info["title"] = $this->poste;
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info["o"] = $soc->nom;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && $conf->global->LDAP_FIELD_ADDRESS) $info[$conf->global->LDAP_FIELD_ADDRESS] = $this->address;
		if ($this->cp && $conf->global->LDAP_FIELD_ZIP)          $info[$conf->global->LDAP_FIELD_ZIP] = $this->cp;
		if ($this->ville && $conf->global->LDAP_FIELD_TOWN)      $info[$conf->global->LDAP_FIELD_TOWN] = $this->ville;
		if ($this->phone_pro && $conf->global->LDAP_FIELD_PHONE) $info[$conf->global->LDAP_FIELD_PHONE] = $this->phone_pro;
		if ($this->phone_perso) $info["homePhone"] = $this->phone_perso;
		if ($this->phone_mobile && $conf->global->LDAP_FIELD_MOBILE) $info[$conf->global->LDAP_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && $conf->global->LDAP_FIELD_FAX)	    $info[$conf->global->LDAP_FIELD_FAX] = $this->fax;
		if ($this->note) $info["description"] = $this->note;
		if ($this->email && $conf->global->LDAP_FIELD_MAIL)     $info[$conf->global->LDAP_FIELD_MAIL] = $this->email;

		if ($conf->global->LDAP_SERVER_TYPE == 'egroupware')
		{
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz']      = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			if (strlen($this->egroupware_id) == 0)
			{
				$this->egroupware_id = 1;
			}

			$info["phpgwContactOwner"] = $this->egroupware_id;

			if ($this->email) $info["rfc822Mailbox"] = $this->email;
			if ($this->phone_mobile) $info["phpgwCellTelephoneNumber"] = $this->phone_mobile;
		}
						
		return $info;
	}


	/**
	*   \brief      Creation dans l'arbre LDAP
	*   \param      user        Utilisateur qui effectue la creation
	*	\return		int			<0 si ko, >0 si ok
	*/
	function create_ldap($user)
	{
		dolibarr_syslog("Contact.class::create_ldap this->id=".$this->id,LOG_DEBUG);
		return $this->update_ldap($user);
	}

	
	/**
	*   \brief      Mise à jour dans l'arbre LDAP
	*   \param      user        Utilisateur qui effectue la mise à jour
	*	\return		int			<0 si ko, >0 si ok
	*/
	function update_ldap($user)
	{
		global $conf, $langs;

        //if (! $conf->ldap->enabled || ! $conf->global->LDAP_CONTACT_ACTIVE) return 0;

		$info = array();

		dolibarr_syslog("Contact.class::update_ldap this->id=".$this->id,LOG_DEBUG);
	
		$ldap=new Ldap();
		$result=$ldap->connect();
		if ($result)
		{
			$bind='';
			if ($conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS)
			{
				dolibarr_syslog("Contact.class::update_ldap authBind user=".$conf->global->LDAP_ADMIN_DN,LOG_DEBUG);
				$bind=$ldap->authBind($conf->global->LDAP_ADMIN_DN,$conf->global->LDAP_ADMIN_PASS);
			}
			else
			{
				dolibarr_syslog("Contact.class::update_ldap bind",LOG_DEBUG);
				$bind=$ldap->bind();
			}
			if ($bind)
			{
				$info=$this->_load_ldap_info($info);

				// Definitition du DN
				$dn = $conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS].",".$conf->global->LDAP_CONTACT_DN;
				$olddn = $dn;
				if (($this->old_firstname || $this->old_name) && $conf->global->LDAP_KEY_CONTACTS=="cn")
					$olddn=$conf->global->LDAP_KEY_CONTACTS."=".trim($this->old_firstname." ".$this->old_name).",".$conf->global->LDAP_CONTACT_DN;

				// On supprime et on insère
				dolibarr_syslog("User.class::update_ldap dn=".$dn." olddn=".$olddn);

				$result = $ldap->delete($olddn);
				$result = $ldap->add($dn, $info);
				if ($result <= 0)
				{
					$this->error = ldap_errno($ldap->connection)." ".ldap_error($ldap->connection)." ".$ldap->error;
					dolibarr_syslog("Contact.class::update_ldap ".$this->error);	
					//print_r($info);
					return -1;
				}
				else
				{
					dolibarr_syslog("Contact.class::update_ldap rowid=".$this->id." added in LDAP");	
				}

				$ldap->unbind();

				return 1;
			}
			else
			{
				$this->error = "Error ".ldap_errno($ldap->connection)." ".ldap_error($ldap->connection);
				dolibarr_syslog("Contact.class::update_ldap bind failed",LOG_DEBUG);
				return -1;
			}
		}
		else
		{
			$this->error="Failed to connect to LDAP server !";
			dolibarr_syslog("Contact.class::update_ldap Connexion failed",LOG_DEBUG);
			return -1;
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
        $sql.= " c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid, c.note,";
        $sql.= " u.rowid as user_id, u.login as user_login";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON c.fk_pays = p.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.idp = u.fk_socpeople";
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
    
                $this->fullname       = trim($this->firstname . ' ' . $this->name);
    
                $this->phone_pro      = trim($obj->phone);
                $this->fax            = trim($obj->fax);
                $this->phone_perso    = trim($obj->phone_perso);
                $this->phone_mobile   = trim($obj->phone_mobile);
    
                $this->code           = $obj->code;
                $this->email          = $obj->email;
                $this->jabberid       = $obj->jabberid;
                $this->mail           = $obj->email;
    
                $this->birthday       = $obj->birthday;
                $this->birthday_alert = $obj->birthday_alert;
                $this->note           = $obj->note;

                $this->user_id        = $obj->user_id;
                $this->user_login     = $obj->user_login;
            }
            $this->db->free($resql);
    
    
            // Recherche le user Dolibarr lié à ce contact
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
    
            // Charge alertes du user
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
     *    \brief        Charge le nombre d'elements auquel est lié ce contact
     *                  ref_facturation
     *                  ref_contrat
     *                  ref_commande
     *                  ref_propale
     *    \return       int         0 si ok, -1 si erreur
     */
    function load_ref_elements()
    {
        // Compte les elements pour lesquels il est contact
        $sql ="SELECT tc.element, count(ec.rowid) as nb";
        $sql.=" FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
        $sql.=" WHERE ec.fk_c_type_contact = tc.rowid";
        $sql.=" AND fk_socpeople = ". $this->id;
        $sql.=" GROUP BY tc.element";

        dolibarr_syslog("Contact.class::load_ref_elements sql=".$sql);
        
        $resql=$this->db->query($sql);
        if ($resql)
        {
	        while($obj=$this->db->fetch_object($resql))
	        {
		        if ($obj->nb)
		        {
		            if ($obj->element=='facture')  $this->ref_facturation = $obj->nb;
		            if ($obj->element=='contrat')  $this->ref_contrat = $obj->nb;
		            if ($obj->element=='commande') $this->ref_commande = $obj->nb;
		            if ($obj->element=='propal')   $this->ref_propal = $obj->nb;
		        }
			}
	        $this->db->free($resql);
	        return 0;
        }
        else
        {
	        $this->error=$this->db->error()." - ".$sql;
	        dolibarr_syslog("Contact.class::load_ref_elements Error ".$this->error);
	        return -1;
        }
    }

	/*
	*   \brief      Efface le contact de la base et éventuellement de l'annuaire LDAP
	*   \param      id      id du contact a effacer
	*	\return		int		<0 si ko, >0 si ok
	*/
	function delete($id)
	{
		global $conf, $langs;
	
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
		if (! $result)
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	

        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('CONTACT_DELETE',$this,$user,$langs,$conf);
        // Fin appel triggers

		// \todo	Mettre en trigger
       	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
      	{
			// On modifie contact avec anciens noms
		 	$savname=$this->name;
		 	$savfirstname=$this->firstname;
	        $this->name=$this->old_name;
	        $this->firstname=$this->old_firstname;
	
	        $this->delete_ldap($user);
	
	        $this->name=$savname;
	        $this->firstname=$savfirstname;
		}
		
		return 1;
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
        
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
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
    
            $this->db->free($resql);
        }
        else
        {
            print $this->db->error();
        }
    }
    
    /*
     *    \brief        Renvoi nombre d'emailings reçu par le contact avec son email
     *    \return       int     Nombre d'emailings
     */
    function getNbOfEMailings()
    {
        $sql = "SELECT count(mc.email) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
        $sql.= " WHERE mc.email = '".addslashes($this->email)."'";
        $sql.= " AND mc.statut=1";      // -1 erreur, 0 non envoyé, 1 envoyé avec succès
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $nb=$obj->nb;
             
            $this->db->free($resql);
            return $nb;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }    

	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 *		\remarks	Utilise $this->id, $this->name et $this->firstname
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;
		
		$result='';
		
		$lien = '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($option == 'xxx')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$this->id.'">';
			$lienfin='</a>';
		}

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowContact"),'contact').$lienfin.' ');
		$result.=$lien.$this->name.' '.$this->firstname.$lienfin;
		return $result;
	}


	/**
	 *		\brief		Initialise le contact avec valeurs fictives aléatoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe LIMIT 10";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Initialise paramètres
		$this->id=0;
		$this->specimen=1;
		$this->fullname = 'DOLIBARR SPECIMEN';
		$this->nom = 'DOLIBARR';
		$this->name = $this->nom;
		$this->prenom = 'SPECIMEN';
		$this->firstname = $this->prenom;
		$this->address = '61 jump street';
		$this->cp = '75000';
		$this->ville = 'Paris';
		$this->fk_pays = 1;
		$this->email = 'specimen@specimen.com';
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
	}

}
?>
