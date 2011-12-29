<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2010 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/contact/class/contact.class.php
 *	\ingroup    societe
 *	\brief      File of contacts class
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 *	\class      Contact
 *	\brief      Classe permettant la gestion des contacts
 */
class Contact extends CommonObject
{
	public $element='contact';
	public $table_element='socpeople';

	var $id;
	var $civilite_id;  // In fact we stor civility_code
    var $lastname;
	var $name;         // TODO deprecated
	var $nom;          // TODO deprecated
	var $firstname;
	var $prenom;       // TODO deprecated
	var $address;
	var $cp;	       // TODO deprecated
	var $zip;
	var $ville;	       // TODO deprecated
	var $town;

	var $fk_departement;		// Id of department
	var $departement_code;		// Code of department
	var $departement;			// Label of department

	var $fk_pays;				// Id of country
	var $pays_code;				// Code of country
	var $pays;					// Label of country
	var $country_id;				// Id of country
	var $country_code;				// Code of country
	var $country;					// Label of country

	var $socid;					// fk_soc
	var $status;				// 0=brouillon, 1=4=actif, 5=inactif

	var $code;
	var $email;
	var $birthday;
	var $default_lang;

	var $ref_facturation;       // Nb de reference facture pour lequel il est contact
	var $ref_contrat;           // Nb de reference contrat pour lequel il est contact
	var $ref_commande;          // Nb de reference commande pour lequel il est contact
	var $ref_propal;            // Nb de reference propal pour lequel il est contact

	var $user_id;
	var $user_login;

	var $oldcopy;		// To contains a clone of this when we need to save old properties of object


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function Contact($DB)
	{
		$this->db = $DB;
	}

	/**
	 *  Add a contact into database
	 *
	 *  @param      User	$user       Object user that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf, $langs;

		$error=0;
		$now=dol_now();

		$this->db->begin();

		// Clean parameters
		$this->name=trim($this->name);
        $this->firstname=trim($this->firstname);
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->name=ucwords($this->name);
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->firstname=ucwords($this->firstname);
        if (! $this->socid) $this->socid = 0;
		if (! $this->priv) $this->priv = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (";
		$sql.= " datec";
		$sql.= ", fk_soc";
        $sql.= ", name";
        $sql.= ", firstname";
        $sql.= ", fk_user_creat";
		$sql.= ", priv";
		$sql.= ", canvas";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($now)."',";
		if ($this->socid > 0) $sql.= " ".$this->socid.",";
		else $sql.= "null,";
		$sql.= "'".$this->db->escape($this->name)."',";
        $sql.= "'".$this->db->escape($this->firstname)."',";
		$sql.= " ".($user->id > 0 ? "'".$user->id."'":"null").",";
		$sql.= " ".$this->priv.",";
        $sql.= " ".($this->canvas?"'".$this->canvas."'":"null").",";
        $sql.= " ".$conf->entity;
		$sql.= ")";

		dol_syslog("Contact::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");

			if (! $error)
			{
                $result=$this->update($this->id, $user, 1);
                if ($result < 0)
                {
                    $error++;
				    $this->error=$this->db->lasterror();
                }
			}

            if (! $error)
            {
                $result=$this->update_perso($this->id, $user);
                if ($result < 0)
                {
                    $error++;
                    $this->error=$this->db->lasterror();
                }
            }

            if (! $error)
            {
    			// Appel des triggers
    			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    			$interface=new Interfaces($this->db);
    			$result=$interface->run_triggers('CONTACT_CREATE',$this,$user,$langs,$conf);
    			if ($result < 0) { $error++; $this->errors=$interface->errors; }
    			// Fin appel triggers
            }

            if (! $error)
            {
                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->db->rollback();
                dol_syslog("Contact::create ".$this->error, LOG_ERR);
                return -2;
            }
		}
		else
		{
			$this->error=$this->db->lasterror();

			$this->db->rollback();
			dol_syslog("Contact::create ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *      Update informations into database
	 *
	 *      @param      int		$id          	Id of contact/address to update
	 *      @param      User	$user        	Objet user making change
	 *      @param      int		$notrigger	    0=no, 1=yesi
	 *      @return     int      			   	<0 if KO, >0 if OK
	 */
	function update($id, $user=0, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		$this->id = $id;

		// Nettoyage parametres
		$this->name=trim($this->name);
		$this->firstname=trim($this->firstname);

		$this->email=trim($this->email);
		$this->phone_pro=trim($this->phone_pro);
		$this->phone_perso=trim($this->phone_perso);
		$this->phone_mobile=trim($this->phone_mobile);
		$this->fax=trim($this->fax);
		$this->country_id=($this->country_id > 0?$this->country_id:$this->fk_pays);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
		if ($this->socid > 0) $sql .= " fk_soc='".$this->db->escape($this->socid)."',";
		if ($this->socid == -1) $sql .= " fk_soc=null,";
		$sql .= "  civilite='".$this->db->escape($this->civilite_id)."'";
		$sql .= ", name='".$this->db->escape($this->name)."'";
		$sql .= ", firstname='".$this->db->escape($this->firstname)."'";
		$sql .= ", address='".$this->db->escape($this->address)."'";
		$sql .= ", cp='".$this->db->escape($this->zip)."'";
		$sql .= ", ville='".$this->db->escape($this->town)."'";
		$sql .= ", fk_pays=".($this->country_id>0?$this->country_id:'NULL');
		$sql .= ", fk_departement=".($this->fk_departement>0?$this->fk_departement:'NULL');
		$sql .= ", poste='".$this->db->escape($this->poste)."'";
		$sql .= ", fax='".$this->db->escape($this->fax)."'";
		$sql .= ", email='".$this->db->escape($this->email)."'";
		$sql .= ", note='".$this->db->escape($this->note)."'";
		$sql .= ", phone = '".$this->db->escape($this->phone_pro)."'";
		$sql .= ", phone_perso = '".$this->db->escape($this->phone_perso)."'";
		$sql .= ", phone_mobile = '".$this->db->escape($this->phone_mobile)."'";
		$sql .= ", jabberid = '".$this->db->escape($this->jabberid)."'";
		$sql .= ", priv = '".$this->priv."'";
		$sql .= ", fk_user_modif=".($user->id > 0 ? "'".$user->id."'":"null");
		$sql .= ", default_lang=".($this->default_lang?"'".$this->default_lang."'":"null");
		$sql .= " WHERE rowid=".$id;

		dol_syslog("Contact::update sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if (! $error && ! $notrigger)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('CONTACT_MODIFY',$this,$user,$langs,$conf);
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
				$this->error=join(',',$this->errors);
				dol_syslog("Contact::update Error ".$this->error,LOG_ERR);
				$this->db->rollback();
				return -$error;
			}
		}
		else
		{
			$this->error=$this->db->lasterror().' sql='.$sql;
			dol_syslog("Contact::update Error ".$this->error,LOG_ERR);
            $this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info string loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
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


	/**
	 *	Initialise tableau info (tableau des attributs LDAP)
	 *
	 *	@return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
		global $conf,$langs;

		// Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_CONTACT_OBJECT_CLASS);

		$this->fullname=$this->getFullName($langs);

		// Fields
		if ($this->fullname && $conf->global->LDAP_CONTACT_FIELD_FULLNAME) $info[$conf->global->LDAP_CONTACT_FIELD_FULLNAME] = $this->fullname;
		if ($this->name && $conf->global->LDAP_CONTACT_FIELD_NAME) $info[$conf->global->LDAP_CONTACT_FIELD_NAME] = $this->name;
		if ($this->firstname && $conf->global->LDAP_CONTACT_FIELD_FIRSTNAME) $info[$conf->global->LDAP_CONTACT_FIELD_FIRSTNAME] = $this->firstname;

		if ($this->poste) $info["title"] = $this->poste;
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[$conf->global->LDAP_CONTACT_FIELD_COMPANY] = $soc->nom;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && $conf->global->LDAP_CONTACT_FIELD_ADDRESS) $info[$conf->global->LDAP_CONTACT_FIELD_ADDRESS] = $this->address;
		if ($this->cp && $conf->global->LDAP_CONTACT_FIELD_ZIP)          $info[$conf->global->LDAP_CONTACT_FIELD_ZIP] = $this->cp;
		if ($this->ville && $conf->global->LDAP_CONTACT_FIELD_TOWN)      $info[$conf->global->LDAP_CONTACT_FIELD_TOWN] = $this->ville;
		if ($this->country_code && $conf->global->LDAP_CONTACT_FIELD_COUNTRY)      $info[$conf->global->LDAP_CONTACT_FIELD_COUNTRY] = $this->country_code;
		if ($this->phone_pro && $conf->global->LDAP_CONTACT_FIELD_PHONE) $info[$conf->global->LDAP_CONTACT_FIELD_PHONE] = $this->phone_pro;
		if ($this->phone_perso && $conf->global->LDAP_CONTACT_FIELD_HOMEPHONE) $info[$conf->global->LDAP_CONTACT_FIELD_HOMEPHONE] = $this->phone_perso;
		if ($this->phone_mobile && $conf->global->LDAP_CONTACT_FIELD_MOBILE) $info[$conf->global->LDAP_CONTACT_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && $conf->global->LDAP_CONTACT_FIELD_FAX)	    $info[$conf->global->LDAP_CONTACT_FIELD_FAX] = $this->fax;
		if ($this->note && $conf->global->LDAP_CONTACT_FIELD_DESCRIPTION) $info[$conf->global->LDAP_CONTACT_FIELD_DESCRIPTION] = $this->note;
		if ($this->email && $conf->global->LDAP_CONTACT_FIELD_MAIL)     $info[$conf->global->LDAP_CONTACT_FIELD_MAIL] = $this->email;

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

			if (dol_strlen($this->egroupware_id) == 0)
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
	 *  Update field alert birthday
	 *
	 *  @param      int			$id         Id of contact
	 *  @param      User		$user		User asking to change alert or birthday
     *  @return     int         			<0 if KO, >=0 if OK
	 */
	function update_perso($id, $user=0)
	{
	    $error=0;
	    $result=false;

		// Mis a jour contact
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET rowid=".$id;
		$sql .= ", birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		if ($user) $sql .= ", fk_user_modif=".$user->id;
		$sql .= " WHERE rowid=".$id;
		//print "update_perso: ".$this->birthday.'-'.$this->db->idate($this->birthday);
		dol_syslog("Contact::update_perso this->birthday=".$this->birthday." - sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
            $error++;
		    $this->error=$this->db->lasterror();
		}

		// Mis a jour alerte birthday
		if ($this->birthday_alert)
		{
			//check existing
			$sql_check = "SELECT * FROM ".MAIN_DB_PREFIX."user_alert WHERE type=1 AND fk_contact=".$id." AND fk_user=".$user->id;
			$result_check = $this->db->query($sql_check);
			if (!$result_check or ($this->db->num_rows($result_check)<1))
			{
				//insert
				$sql = "INSERT into ".MAIN_DB_PREFIX."user_alert(type,fk_contact,fk_user) ";
				$sql.= "values (1,".$id.",".$user->id.")";
				$result = $this->db->query($sql);
				if (!$result)
				{
                    $error++;
                    $this->error=$this->db->lasterror();
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
			if (! $result)
			{
                $error++;
                $this->error=$this->db->lasterror();
			}
		}

		return $result;
	}


	/**
	 *  Charge l'objet contact
	 *
	 *  @param      int		$id          id du contact
	 *  @param      User	$user        Utilisateur (abonnes aux alertes) qui veut les alertes de ce contact
	 *  @return     int     		    -1 if KO, 0 if OK but not found, 1 if OK
	 */
	function fetch($id, $user=0)
	{
		global $langs;

		$langs->load("companies");

		$sql = "SELECT c.rowid, c.fk_soc, c.civilite as civilite_id, c.name, c.firstname,";
		$sql.= " c.address, c.cp, c.ville,";
		$sql.= " c.fk_pays,";
		$sql.= " c.fk_departement,";
		$sql.= " c.birthday,";
		$sql.= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid,";
		$sql.= " c.priv, c.note, c.default_lang, c.canvas,";
		$sql.= " p.libelle as pays, p.code as pays_code,";
		$sql.= " d.nom as departement, d.code_departement as departement_code,";
		$sql.= " u.rowid as user_id, u.login as user_login,";
		$sql.= " s.nom as socname, s.address as socaddress, s.cp as soccp, s.ville as soccity, s.default_lang as socdefault_lang";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON c.fk_pays = p.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
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

				$this->id				= $obj->rowid;
				$this->ref				= $obj->rowid;
				$this->civilite_id		= $obj->civilite_id;
				$this->name				= $obj->name;
				$this->firstname		= $obj->firstname;
				$this->nom				= $obj->name;			// TODO deprecated
				$this->prenom			= $obj->firstname;		// TODO deprecated

				$this->address			= $obj->address;
				$this->adresse			= $obj->address; 		// TODO deprecated
				$this->cp				= $obj->cp;				// TODO deprecated
				$this->zip				= $obj->cp;
				$this->ville			= $obj->ville;			// TODO deprecated
				$this->town				= $obj->ville;

				$this->fk_departement	= $obj->fk_departement;
				$this->state_id			= $obj->fk_departement;
				$this->departement_code = $obj->departement_code;	// TODO deprecated
				$this->state_code       = $obj->departement_code;
				$this->departement		= $obj->departement;	// TODO deprecated
				$this->state			= $obj->departement;

				$this->fk_pays			= $obj->fk_pays;
				$this->country_id 		= $obj->fk_pays;
				$this->pays_code		= $obj->fk_pays?$obj->pays_code:'';
				$this->country_code		= $obj->fk_pays?$obj->pays_code:'';
				$this->pays				= ($obj->fk_pays > 0)?$langs->transnoentitiesnoconv("Country".$obj->pays_code):'';
				$this->country			= ($obj->fk_pays > 0)?$langs->transnoentitiesnoconv("Country".$obj->pays_code):'';

				$this->socid			= $obj->fk_soc;
				$this->socname			= $obj->socname;
				$this->poste			= $obj->poste;

				$this->phone_pro		= trim($obj->phone);
				$this->fax				= trim($obj->fax);
				$this->phone_perso		= trim($obj->phone_perso);
				$this->phone_mobile		= trim($obj->phone_mobile);

				$this->email			= $obj->email;
				$this->jabberid			= $obj->jabberid;
				$this->priv				= $obj->priv;
				$this->mail				= $obj->email;

				$this->birthday			= dol_stringtotime($obj->birthday);
				//print "fetch: ".$obj->birthday.'-'.$this->birthday;
				$this->birthday_alert 	= $obj->birthday_alert;
				$this->note				= $obj->note;
				$this->default_lang		= $obj->default_lang;
				$this->user_id			= $obj->user_id;
				$this->user_login		= $obj->user_login;
				$this->canvas			= $obj->canvas;

				// Recherche le user Dolibarr lie a ce contact
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


	/**
	 *  Charge le nombre d'elements auquel est lie ce contact
	 *  ref_facturation
	 *  ref_contrat
	 *  ref_commande
	 *  ref_propale
	 *
     *  @return     int             					<0 if KO, >=0 if OK
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

	/**
	 *   	Efface le contact de la base
	 *
	 *   	@param		int		$notrigger		Disable all trigger
	 *		@return		int						<0 if KO, >0 if OK
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
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CONTACT_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			if ($error) $this->error=join(',',$this->errors);
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


	/**
	 *  Charge les informations sur le contact, depuis la base
	 *
	 *  @param		int		$id      Id du contact a charger
	 *  @return		void
	 */
	function info($id)
	{
		$sql = "SELECT c.rowid, c.datec as datec, c.fk_user_creat,";
		$sql.= " c.tms as tms, c.fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " WHERE c.rowid = ".$id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id                = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}

			$this->db->free($resql);
		}
		else
		{
			print $this->db->error();
		}
	}

	/**
	 *  Return number of mass Emailing received by this contacts with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql.= " WHERE mc.email = '".$this->db->escape($this->email)."'";
		$sql.= " AND mc.statut=1";      // -1 erreur, 0 non envoye, 1 envoye avec succes
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
	 *  Return name of contact with link (and eventually picto)
	 *	Use $this->id, $this->name, $this->firstname, this->civilite_id
	 *
	 *	@param		int			$withpicto		Include picto with link
	 *	@param		string		$option			Where the link point to
	 *	@param		int			$maxlen			Max length of
	 *	@return		string						String with URL
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
     * 	Return full address of contact
     *
     * 	@param		int			$withcountry		1=Add country into address string
     *  @param		string		$sep				Separator to use to build string
     *	@return		string							Full address string
     */
    function getFullAddress($withcountry=0,$sep="\n")
    {
        $ret='';
        if (in_array($this->country,array('us')))
        {
	        $ret.=($this->address?$this->address.$sep:'');
	        $ret.=trim($this->zip.' '.$this->town);
	        if ($withcountry) $ret.=($this->country?$sep.$this->country:'');
        }
        else
        {
	        $ret.=($this->address?$this->address.$sep:'');
	        $ret.=trim($this->zip.' '.$this->town);
	        if ($withcountry) $ret.=($this->country?$sep.$this->country:'');
        }
        return trim($ret);
    }


	/**
	 *    Return label of a civility contact
	 *
	 *    @return     string      Translated name of civility
	 */
	function getCivilityLabel()
	{
		global $langs;
		$langs->load("dict");

		$code=$this->civilite_id;
        return $langs->trans("Civility".$code)!="Civility".$code ? $langs->trans("Civility".$code) : '';
		/*if (empty($ret))
		{
		    $ret=$code;
		    $langs->getLabelFromKey($this->db,$reg[1],'c_civilite','code','civilite');
		     //$ret=dol_getIdFromCode($this->db,$code,'c_civilite',
		}
		return $ret;*/
	}


	/**
	 *    	Return full name (civility+' '+name+' '+lastname)
	 *
	 *		@param		Translate	$langs			Language object for translation of civility
	 *		@param		string		$option			0=No option, 1=Add civility
	 * 		@param		int			$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname
	 * 		@return		string						String with full name
	 */
	function getFullName($langs,$option=0,$nameorder=-1)
	{
		global $conf;

		$ret='';
		if ($option && $this->civilite_id)
		{
			if ($langs->transnoentitiesnoconv("Civility".$this->civilite_id)!="Civility".$this->civilite_id) $ret.=$langs->transnoentitiesnoconv("Civility".$this->civilite_id).' ';
			else $ret.=$this->civilite_id.' ';
		}

		// If order not defined, we use the setup
		if ($nameorder < 0) $nameorder=(! $conf->global->MAIN_FIRSTNAME_NAME_POSITION);

		if ($nameorder)
		{
			if ($this->firstname) $ret.=$this->firstname;
			if ($this->firstname && $this->name) $ret.=' ';
			if ($this->name)      $ret.=$this->name;
		}
		else
		{
			if ($this->name)      $ret.=$this->name;
			if ($this->firstname && $this->name) $ret.=' ';
			if ($this->firstname) $ret.=$this->firstname;
		}
		return trim($ret);
	}


	/**
	 *  Retourne le libelle du statut du contact
	 *
	 *  @param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return     string      			Libelle
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *  @param      int			$statut     Id statut
	 *  @param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return     string					Libelle
	 */
	function LibStatut($statut, $mode)
	{
		global $langs;

		if ($mode == 0)
		{
			if ($statut==0) return $langs->trans('StatusContactDraft');
			elseif ($statut==1) return $langs->trans('StatusContactValidated');
			elseif ($statut==4) return $langs->trans('StatusContactValidated');
			elseif ($statut==5) return $langs->trans('StatusContactValidated');
		}
		elseif ($mode == 1)
		{
			if ($statut==0) return $langs->trans('StatusContactDraftShort');
			elseif ($statut==1) return $langs->trans('StatusContactValidatedShort');
			elseif ($statut==4) return $langs->trans('StatusContactValidatedShort');
			elseif ($statut==5) return $langs->trans('StatusContactValidatedShort');
		}
		elseif ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans('StatusContactDraftShort'),'statut0').' '.$langs->trans('StatusContactDraft');
			elseif ($statut==1) return img_picto($langs->trans('StatusContactValidatedShort'),'statut1').' '.$langs->trans('StatusContactValidated');
			elseif ($statut==4) return img_picto($langs->trans('StatusContactValidatedShort'),'statut4').' '.$langs->trans('StatusContactValidated');
			elseif ($statut==5) return img_picto($langs->trans('StatusContactValidatedShort'),'statut5').' '.$langs->trans('StatusContactValidated');
		}
		elseif ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans('StatusContactDraft'),'statut0');
			elseif ($statut==1) return img_picto($langs->trans('StatusContactValidated'),'statut1');
			elseif ($statut==4) return img_picto($langs->trans('StatusContactValidated'),'statut4');
			elseif ($statut==5) return img_picto($langs->trans('StatusContactValidated'),'statut5');
		}
		elseif ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans('StatusContactDraft'),'statut0').' '.$langs->trans('StatusContactDraft');
			elseif ($statut==1) return img_picto($langs->trans('StatusContactValidated'),'statut1').' '.$langs->trans('StatusContactValidated');
			elseif ($statut==4) return img_picto($langs->trans('StatusContactValidated'),'statut4').' '.$langs->trans('StatusContactValidated');
			elseif ($statut==5) return img_picto($langs->trans('StatusContactValidated'),'statut5').' '.$langs->trans('StatusContactValidated');
		}
		elseif ($mode == 5)
		{
			if ($statut==0) return $langs->trans('StatusContactDraftShort').' '.img_picto($langs->trans('StatusContactDraftShort'),'statut0');
			elseif ($statut==1) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut1');
			elseif ($statut==4) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut4');
			elseif ($statut==5) return $langs->trans('StatusContactValidatedShort').' '.img_picto($langs->trans('StatusContactValidatedShort'),'statut5');
		}
	}


	/**
	 *	Return translated label of Public or Private
	 *
	 * 	@param      int			$statut		Type (0 = public, 1 = private)
	 *  @return     string					Label translated
	 */
	function LibPubPriv($statut)
	{
		global $langs;
		if ($statut=='1') return $langs->trans('ContactPrivate');
		else return $langs->trans('ContactPublic');
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de societe socids
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

		// Initialise parameters
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
		$this->pays_code = 'FR';
		$this->pays = 'France';
		$this->email = 'specimen@specimen.com';
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
	}

}
?>
