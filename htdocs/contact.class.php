<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2009 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin               <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke       <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 */

/**
        \file       htdocs/contact.class.php
        \ingroup    societe
        \brief      Fichier de la classe des contacts
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**
        \class      Contact
        \brief      Classe permettant la gestion des contacts
*/
class Contact extends CommonObject
{
    var $db;
    var $error;
    var $element='contact';
    var $table_element='socpeople';

    var $id;
	var $civilite_id;
    var $name;
    var $firstname;
    var $address;
    var $cp;
    var $ville;
    var $fk_pays;
    var $socid;					// fk_soc
    var $status;				// 0=brouillon, 1=4=actif, 5=inactif

    var $code;
    var $email;
    var $birthday;

    var $ref_facturation;       // Nb de reference facture pour lequel il est contact
    var $ref_contrat;           // Nb de reference contrat pour lequel il est contact
    var $ref_commande;          // Nb de reference commande pour lequel il est contact
    var $ref_propal;            // Nb de reference propal pour lequel il est contact

	var $user_id;
	var $user_login;


    /**
     *      \brief      Constructeur de l'objet contact
     *      \param      DB      Habler d'acc�s base
     *      \param      id      Id contact
     */
    function Contact($DB, $id=0)
    {
        $this->db = $DB;
        $this->id = $id;

        return 1;
    }

    /**
     *      \brief      Add a contact in database
     *      \param      user        Object user that create
     *      \return     int         <0 if KO, >0 if OK
     */
    function create($user)
    {
    	global $conf, $langs;

		// Clean parameters
        $this->name=trim($this->name);
        if (! $this->socid) $this->socid = 0;
		if (! $this->priv) $this->priv = 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (datec, fk_soc, name, fk_user_creat, priv)";
        $sql.= " VALUES (".$this->db->idate(mktime()).",";
        if ($this->socid > 0) $sql.= " ".$this->socid.",";
        else $sql.= "null,";
        $sql.= "'".addslashes($this->name)."',";
		$sql.= " ".($user->id > 0 ? "'".$user->id."'":"null").",";
        $sql.= $this->priv;
        $sql.= ")";

        dol_syslog("Contact::create sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");

            $result=$this->update($this->id, $user, 1);
            if ($result < 0)
            {
                $this->error=$this->db->error();
                return -2;
            }

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTACT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("Contact::create ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /*
     *      \brief      Mise � jour des infos en base
     *      \param      id          	Id du contact � mettre � jour
     *      \param      user        	Objet utilisateur qui effectue la mise � jour
     *      \param      notrigger	    0=non, 1=oui
     *      \return     int         	<0 si erreur, >0 si ok
     */
    function update($id, $user=0, $notrigger=0)
    {
    	global $conf, $langs;

        $this->id = $id;

    	// Nettoyage parametres
        $this->name=trim($this->name);
        $this->firstname=trim($this->firstname);

        $this->email=trim($this->email);
        $this->phone_pro=trim($this->phone_pro);
        $this->phone_perso=trim($this->phone_perso);
        $this->phone_mobile=trim($this->phone_mobile);
        $this->fax=trim($this->fax);

        $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
        if ($this->socid > 0) $sql .= " fk_soc='".addslashes($this->socid)."',";
        if ($this->socid == -1) $sql .= " fk_soc=null,";
        $sql .= "  civilite='".addslashes($this->civilite_id)."'";
        $sql .= ", name='".addslashes($this->name)."'";
        $sql .= ", firstname='".addslashes($this->firstname)."'";
        $sql .= ", address='".addslashes($this->address)."'";
        $sql .= ", cp='".addslashes($this->cp)."'";
        $sql .= ", ville='".addslashes($this->ville)."'";
        $sql .= ", fk_pays=".($this->fk_pays>0?$this->fk_pays:'NULL');
        $sql .= ", poste='".addslashes($this->poste)."'";
        $sql .= ", fax='".addslashes($this->fax)."'";
        $sql .= ", email='".addslashes($this->email)."'";
        $sql .= ", note='".addslashes($this->note)."'";
        $sql .= ", phone = '".addslashes($this->phone_pro)."'";
        $sql .= ", phone_perso = '".addslashes($this->phone_perso)."'";
        $sql .= ", phone_mobile = '".addslashes($this->phone_mobile)."'";
        $sql .= ", jabberid = '".addslashes($this->jabberid)."'";
        $sql .= ", priv = '".$this->priv."'";
        $sql .= ", fk_user_modif=".($user->id > 0 ? "'".$user->id."'":"null");
        $sql .= " WHERE rowid=".$id;

        dol_syslog("Contact::update sql=".$sql,LOG_DEBUG);
        $result = $this->db->query($sql);
        if (! $result)
        {
            $this->error=$this->db->lasterror().' sql='.$sql;
			dol_syslog("Contact::update Error ".$this->error,LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTACT_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
    	}

        return 1;
    }


	/*
	*	\brief		Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	*	\param		info		Info string loaded by _load_ldap_info
	*	\param		mode		0=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
								1=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
								2=Return key only (uid=qqq)
	*	\return		string		DN
	*/
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS].",".$conf->global->LDAP_CONTACT_DN;
		if ($mode==1) $dn=$conf->global->LDAP_CONTACT_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS];
		return $dn;
	}


	/*
	*	\brief		Initialise tableau info (tableau des attributs LDAP)
	*	\return		array		Tableau info des attributs
	*/
	function _load_ldap_info()
	{
		global $conf,$langs;

		// Object classes
		$info["objectclass"]=split(',',$conf->global->LDAP_CONTACT_OBJECT_CLASS);

		// Champs
		if ($this->getFullName($langs) && $conf->global->LDAP_FIELD_FULLNAME) $info[$conf->global->LDAP_FIELD_FULLNAME] = utf8_encode($this->getFullName($langs));
		if ($this->name && $conf->global->LDAP_FIELD_NAME) $info[$conf->global->LDAP_FIELD_NAME] = utf8_encode($this->name);
		if ($this->firstname && $conf->global->LDAP_FIELD_FIRSTNAME) $info[$conf->global->LDAP_FIELD_FIRSTNAME] = utf8_encode($this->firstname);

		if ($this->poste) $info["title"] = $this->poste;
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[$conf->global->LDAP_FIELD_COMPANY] = $soc->nom;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && $conf->global->LDAP_FIELD_ADDRESS) $info[$conf->global->LDAP_FIELD_ADDRESS] = utf8_encode($this->address);
		if ($this->cp && $conf->global->LDAP_FIELD_ZIP)          $info[$conf->global->LDAP_FIELD_ZIP] = $this->cp;
		if ($this->ville && $conf->global->LDAP_FIELD_TOWN)      $info[$conf->global->LDAP_FIELD_TOWN] = utf8_encode($this->ville);
		if ($this->pays && $conf->global->LDAP_FIELD_COUNTRY)      $info[$conf->global->LDAP_FIELD_COUNTRY] = $this->pays_code;
		if ($this->phone_pro && $conf->global->LDAP_FIELD_PHONE) $info[$conf->global->LDAP_FIELD_PHONE] = $this->phone_pro;
		if ($this->phone_perso && $conf->global->LDAP_FIELD_HOMEPHONE) $info[$conf->global->LDAP_FIELD_HOMEPHONE] = $this->phone_perso;
		if ($this->phone_mobile && $conf->global->LDAP_FIELD_MOBILE) $info[$conf->global->LDAP_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && $conf->global->LDAP_FIELD_FAX)	    $info[$conf->global->LDAP_FIELD_FAX] = $this->fax;
		if ($this->note && $conf->global->LDAP_FIELD_DESCRIPTION) $info[$conf->global->LDAP_FIELD_DESCRIPTION] = utf8_encode($this->note);
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


	/*
	*    \brief      Mise � jour des alertes
	*    \param      id          id du contact
	*    \param      user        Utilisateur qui demande l'alerte
	*/
	function update_perso($id, $user=0)
	{
		// Mis a jour contact
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET rowid=".$id;

		if ($this->birthday)	// <0 si avant 1970, >0 si apres 1970
		{
            if (eregi('^[0-9]+\-',$this->birthday))
            {
                // Si date = chaine (ne devrait pas arriver)
                $sql .= ", birthday='".$this->birthday."'";
            }
            else
            {
                // Si date = timestamp
            	$sql .= ", birthday=".$this->db->idate($this->birthday);
            }
		}
        if ($user) $sql .= ", fk_user_modif=".$user->id;
		$sql .= " WHERE rowid=".$id;
		//print "update_perso: ".$this->birthday.'-'.$this->db->idate($this->birthday);
		dol_syslog("Contact::update_perso this->birthday=".$this->birthday." - sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->error();
		}

		// Mis a jour alerte birthday
		if ($this->birthday_alert)
		{
			//check existing
			$sql_check = "SELECT * FROM ".MAIN_DB_PREFIX."user_alert WHERE type=1 AND fk_contact=$id AND fk_user=".$user->id;
			$result_check = $this->db->query($sql_check);
	        if (!$result_check or ($this->db->num_rows($result_check)<1))
    	    {
				//insert
				$sql = "INSERT into ".MAIN_DB_PREFIX."user_alert(type,fk_contact,fk_user) ";
				$sql.= "values (1,".$id.",".$user->id.")";
				$result = $this->db->query($sql);
				if (!$result)
				{
					$this->error='Echec sql='.$sql;
				}
    	    }
    	    else
    	    {
    	    	$result = true;
    	    }
		}
		else
		{
			$sql = "DELETE from ".MAIN_DB_PREFIX."user_alert ";
			$sql.= "where type=1 AND fk_contact=".$id." AND fk_user=".$user->id;
			$result = $this->db->query($sql);
			if (!$result)
			{
				$this->error='Echec sql='.$sql;
			}
		}
		return $result;
	}


    /*
     *    \brief      Charge l'objet contact
     *    \param      id          id du contact
     *    \param      user        Utilisateur li� au contact pour une alerte
     *    \return     int         -1 if KO, 0 if OK but not found, 1 if OK
     */
    function fetch($id, $user=0)
    {
    	global $langs;
    	$langs->load("companies");
        $sql = "SELECT c.rowid, c.fk_soc, c.civilite as civilite_id, c.name, c.firstname,";
        $sql.= " c.address, c.cp, c.ville,";
        $sql.= " c.fk_pays, p.libelle as pays, p.code as pays_code,";
        $sql.= " c.birthday,";
        $sql.= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid,";
        $sql.= " c.priv, c.note,";
        $sql.= " u.rowid as user_id, u.login as user_login,";
        $sql.= " s.nom as socname";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON c.fk_pays = p.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
        $sql.= " WHERE c.rowid = ". $id;

    	dol_syslog("Contact::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->civilite_id    = $obj->civilite_id;
                $this->name           = $obj->name;
                $this->firstname      = $obj->firstname;
                $this->nom            = $obj->name;
                $this->prenom         = $obj->firstname;

                $this->address        = $obj->address;
                $this->adresse        = $obj->address; // Todo: uniformiser le nom des variables
                $this->cp             = $obj->cp;
                $this->ville          = $obj->ville;
                $this->fk_pays        = $obj->fk_pays;
                $this->pays_code      = $obj->fk_pays?$obj->pays_code:'';
                $this->pays           = ($obj->fk_pays > 0)?$langs->transnoentities("Country".$obj->pays_code):$langs->transnoentities("SelectCountry");

                $this->societeid      = $obj->fk_soc;
                $this->socid          = $obj->fk_soc;
                $this->socname        = $obj->socname;
                $this->poste          = $obj->poste;

                $this->phone_pro      = trim($obj->phone);
                $this->fax            = trim($obj->fax);
                $this->phone_perso    = trim($obj->phone_perso);
                $this->phone_mobile   = trim($obj->phone_mobile);

                $this->email          = $obj->email;
                $this->jabberid       = $obj->jabberid;
                $this->priv           = $obj->priv;
                $this->mail           = $obj->email;

                $this->birthday       = dol_stringtotime($obj->birthday);
				//print "fetch: ".$obj->birthday.'-'.$this->birthday;
                $this->birthday_alert = $obj->birthday_alert;
                $this->note           = $obj->note;
                $this->user_id        = $obj->user_id;
                $this->user_login     = $obj->user_login;

	            // Recherche le user Dolibarr li� � ce contact
	            $sql = "SELECT u.rowid ";
	            $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	            $sql .= " WHERE u.fk_socpeople = ". $this->id;

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
	           	    $this->error=$this->db->error();
	                dol_syslog("Contact::fetch ".$this->error, LOG_ERR);
	                return -1;
	            }

	            // Charge alertes du user
	            if ($user)
	            {
	                $sql = "SELECT fk_user";
	                $sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
	                $sql .= " WHERE fk_user = ".$user->id." AND fk_contact = ".$id;

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
		           	    $this->error=$this->db->error();
		                dol_syslog("Contact::fetch ".$this->error, LOG_ERR);
	                    return -1;
	                }
	            }

				return 1;
			}
			else
			{
				$this->error=$langs->trans("RecordNotFound");
				return 0;
			}
        }
        else
        {
			$this->error=$this->db->error();
			dol_syslog("Contact::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /*
     *    \brief        Charge le nombre d'elements auquel est li� ce contact
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

        dol_syslog("Contact::load_ref_elements sql=".$sql);

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
	        dol_syslog("Contact::load_ref_elements Error ".$this->error, LOG_ERR);
	        return -1;
        }
    }

	/*
	*   \brief      Efface le contact de la base
	*	\return		int		<0 si ko, >0 si ok
	*/
	function delete($notrigger=0)
	{
		global $conf, $langs, $user;

		$error=0;

		$this->old_name           = $obj->name;
		$this->old_firstname      = $obj->firstname;

		$this->db->begin();

		if (! $error)
		{
			// Get all rowid of element_contact linked to a type that is link to llx_socpeople
			$sql = "SELECT ec.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."element_contact ec,";
			$sql.= " ".MAIN_DB_PREFIX."c_type_contact tc";
			$sql.= " WHERE ec.fk_socpeople=".$this->id;
			$sql.= " AND ec.fk_c_type_contact=tc.rowid";
			$sql.= " AND tc.source='external'";
			dol_syslog("Contact::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
                $num=$this->db->num_rows($resql);

				$i=0;
				while ($i < $num && ! $error)
				{
					$obj = $this->db->fetch_object($resql);

					$sqldel = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
					$sqldel.=" WHERE rowid = ".$obj->rowid;
					dol_syslog("Contact::delete sql=".$sqldel);
					$result = $this->db->query($sqldel);
					if (! $result)
					{
						$error++;
						$this->error=$this->db->error().' sql='.$sqldel;
					}

					$i++;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->error().' sql='.$sql;
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
			$sql .= " WHERE rowid=".$this->id;
			dol_syslog("Contact::delete sql=".$sql);
			$result = $this->db->query($sql);
			if (! $result)
			{
				$error++;
				$this->error=$this->db->error().' sql='.$sql;
			}
		}

		if (! $error && ! $notrigger)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTACT_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		if (! $error)
		{

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


    /*
     *    \brief      Charge les informations sur le contact, depuis la base
     *    \param      id      id du contact � charger
     */
    function info($id)
    {
        $sql = "SELECT c.rowid, ".$this->db->pdate("c.datec")." as datec, c.fk_user_creat";
        $sql .= ", ".$this->db->pdate("c.tms")." as tms, c.fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql .= " WHERE c.rowid = ".$id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id                = $obj->rowid;

                if ($obj->fk_user_creat) {
                    $cuser = new User($this->db, $obj->fk_user_creat);
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
     *    \brief        Renvoi nombre d'emailings re�u par le contact avec son email
     *    \return       int     Nombre d'emailings
     */
    function getNbOfEMailings()
    {
        $sql = "SELECT count(mc.email) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
        $sql.= " WHERE mc.email = '".addslashes($this->email)."'";
        $sql.= " AND mc.statut=1";      // -1 erreur, 0 non envoy�, 1 envoy� avec succ�s
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
	 *		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 *		\remarks	Utilise $this->id, $this->name et $this->firstname
	 */
	function getNomUrl($withpicto=0,$option='',$maxlen=0)
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

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowContact").': '.$this->getFullName($langs),'contact').$lienfin.' ');
		$result.=$lien.($maxlen?dol_trunc($this->getFullName($langs),$maxlen):$this->getFullName($langs)).$lienfin;
		return $result;
	}


    /**
     *    \brief      Retourne le libelle de civilite du contact
     *    \return     string      Nom traduit de la civilit�
     */
    function getCivilityLabel()
    {
        global $langs;
        $langs->load("dict");

		$code=$this->civilite_id;
        return $langs->trans("Civility".$code)!="Civility".$code ? $langs->trans("Civility".$code) : $code;
    }


	/**
	 *    	\brief      Return full name (name+' '+lastname)
	 *		\param		langs			Lang object for output
	 *		\param		option			0=No option, 1=Add civility
	 * 		\param		nameorder		0=Lastname+Firstname, 1=Firstname+Lastname
	 * 		\return		string			String with full name
	 */
	function getFullName($langs,$option=0,$nameorder=0)
	{
		$ret='';
		if ($option && $this->civilite_id)
		{
			if ($langs->transnoentities("Civility".$this->civilite_id)!="Civility".$this->civilite_id) $ret.=$langs->transnoentities("Civility".$this->civilite_id).' ';
			else $ret.=$this->civilite_id.' ';
		}

		if ($nameorder)
		{
			if ($this->firstname) $ret.=$langs->convToOutputCharset($this->firstname);
			if ($this->firstname && $this->name) $ret.=' ';
			if ($this->name)      $ret.=$langs->convToOutputCharset($this->name);
		}
		else
		{
			if ($this->name)      $ret.=$langs->convToOutputCharset($this->name);
			if ($this->firstname && $this->name) $ret.=' ';
			if ($this->firstname) $ret.=$langs->convToOutputCharset($this->firstname);
		}
		return trim($ret);
	}


	/**
	 *    	\brief      Retourne le libell� du statut du contact
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string      Libell�
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *		\brief      Renvoi le libell� d'un statut donn�
	 *    	\param      statut      Id statut
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libell�
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

        if ($mode == 0)
        {
        	if ($statut==0) return $langs->trans('StatusContactDraft');
        	if ($statut==1) return $langs->trans('StatusContactValidated');
        	if ($statut==4) return $langs->trans('StatusContactValidated');
        	if ($statut==5) return $langs->trans('StatusContactValidated');
		}
        if ($mode == 1)
        {
        	if ($statut==0) return $langs->trans('StatusContactDraftShort');
        	if ($statut==1) return $langs->trans('StatusContactValidatedShort');
        	if ($statut==4) return $langs->trans('StatusContactValidatedShort');
        	if ($statut==5) return $langs->trans('StatusContactValidatedShort');
        }
        if ($mode == 2)
        {
        	if ($statut==0) return img_picto($langs->trans('StatusContactDraftShort'),'statut0').' '.$langs->trans('StatusContactDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusContactValidatedShort'),'statut1').' '.$langs->trans('StatusContactValidated');
        	if ($statut==4) return img_picto($langs->trans('StatusContactValidatedShort'),'statut4').' '.$langs->trans('StatusContactValidated');
        	if ($statut==5) return img_picto($langs->trans('StatusContactValidatedShort'),'statut5').' '.$langs->trans('StatusContactValidated');
        }
        if ($mode == 3)
        {
        	if ($statut==0) return img_picto($langs->trans('StatusContactDraft'),'statut0');
        	if ($statut==1) return img_picto($langs->trans('StatusContactValidated'),'statut1');
        	if ($statut==4) return img_picto($langs->trans('StatusContactValidated'),'statut4');
        	if ($statut==5) return img_picto($langs->trans('StatusContactValidated'),'statut5');
        }
        if ($mode == 4)
        {
        	if ($statut==0) return img_picto($langs->trans('StatusContactDraft'),'statut0').' '.$langs->trans('StatusContactDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusContactValidated'),'statut1').' '.$langs->trans('StatusContactValidated');
        	if ($statut==4) return img_picto($langs->trans('StatusContactValidated'),'statut4').' '.$langs->trans('StatusContactValidated');
        	if ($statut==5) return img_picto($langs->trans('StatusContactValidated'),'statut5').' '.$langs->trans('StatusContactValidated');
        }
        if ($mode == 5)
        {
        	if ($statut==0) return $langs->trans('StatusContactDraftShort').' '.img_picto($langs->trans('StatusContactDraftShort'),'statut0');
        	if ($statut==1) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut1');
        	if ($statut==4) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut4');
        	if ($statut==5) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut5');
        }
	}


	/**
	 *		\brief      Return translated label of Public or Private
	 *    	\param      type		Type (0 = public, 1 = private)
	 *    	\return     string		Label translated
	 */
	function LibPubPriv($statut)
	{
		global $langs;
       	if ($statut=='1') return $langs->trans('ContactPrivate');
       	else return $langs->trans('ContactPublic');
	}


	/**
	 *		\brief		Initialise le contact avec valeurs fictives al�atoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de soci�t� socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe LIMIT 10";
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

		// Initialise param�tres
		$this->id=0;
		$this->specimen=1;
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
