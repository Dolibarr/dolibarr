<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
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
 */

/**
   \file       htdocs/user.class.php
   \brief      Fichier de la classe utilisateur
   \author     Rodolphe Quiedeville
   \author     Jean-Louis Bergamo
   \author     Laurent Destailleur
   \author     Sebastien Di Cintio
   \author     Benoit Mortier
   \author     Regis Houssin
   \author     Lionel Cousteix
   \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**
   \class      User
   \brief      Classe permettant la gestion d'un utilisateur
*/
class User extends CommonObject
{
    var $db;
    var $error;
    var $element='user';
    var $table_element='user';
  
  var $id;
  var $ldap_sid;
  var $search_sid;
  var $fullname;
  var $nom;
  var $prenom;
  var $note;
  var $email;
  var $office_phone;
  var $office_fax;
  var $user_mobile;
  var $admin;
  var $login;

  //! Mot de passe en clair en mémoire
  var $pass;
  //! Mot de passe en clair en base (renseigné si DATABASE_PWD_ENCRYPTED=0)
  var $pass_indatabase;
  //! Mot de passe crypté en base (toujours renseigné)
  var $pass_indatabase_crypted;

  var $datec;
  var $datem;
  var $societe_id;
  var $fk_member;
  var $webcal_login;
  var $phenix_login;
  var $phenix_pass;
  var $phenix_pass_crypted;
  
  var $datelastlogin;
  var $datepreviouslogin;
  var $statut;
  var $lang;
  
  var $userpref_limite_liste;
  var $all_permissions_are_loaded;         /**< \private all_permissions_are_loaded */
  //! Liste des entrepots auquel a acces l'utilisateur
  var $entrepots;

  var $tab_loaded=array();		// Tableau pour signaler les permissions deja chargées

  
  /**
   *    \brief Constructeur de la classe
   *    \param  DB         Handler accès base de données
   *    \param  id         Id de l'utilisateur (0 par défaut)
   */
  function User($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;
    
    // Preference utilisateur
    $this->liste_limit = 0;
    $this->clicktodial_enabled = 0;
    
    $this->all_permissions_are_loaded = 0;
    $this->admin=0;
    
    return 1;
  }
  
  
	/**
	*	\brief      Charge un objet user avec toutes ces caractéristiques depuis un id ou login
	*	\param      login       Si défini, login a utiliser pour recherche
	*	\return		int			<0 if KO, 0 not found, >0 if OK
	*/
	function fetch($login='')
	{
		global $conf;

		// Nettoyage parametres
		$login=trim($login);
		
		// Recupere utilisateur
		$sql = "SELECT u.rowid, u.name, u.firstname, u.email, u.office_phone, u.office_fax, u.user_mobile,";
		$sql.= " u.admin, u.login, u.webcal_login, u.phenix_login, u.phenix_pass, u.note,";
		$sql.= " u.pass, u.pass_crypted, u.pass_temp,";
		$sql.= " u.fk_societe, u.fk_socpeople, u.fk_member, u.ldap_sid,";
		$sql.= " u.statut, u.lang,";
		$sql.= " ".$this->db->pdate("u.datec")." as datec,";
		$sql.= " ".$this->db->pdate("u.tms")." as datem,";
		$sql.= " ".$this->db->pdate("u.datelastlogin")." as datel,";
		$sql.= " ".$this->db->pdate("u.datepreviouslogin")." as datep";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr' && $this->search_sid != '')
		{
			// permet une recherche du user par son SID ActiveDirectory ou Samba
			$sql .= " WHERE (u.ldap_sid = '".$this->search_sid."' || u.login = '".addslashes($login)."') LIMIT 1";
		}
		else if ($login)
		{
			$sql .= " WHERE u.login = '".addslashes($login)."'";
		}
		else
		{
			$sql .= " WHERE u.rowid = ".$this->id;
		}
	
		dolibarr_syslog("User::Fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;

				$this->ldap_sid = $obj->ldap_sid;
				$this->nom = $obj->name;
				$this->prenom = $obj->firstname;
				
				$this->fullname = trim($this->prenom . ' ' . $this->nom);
				$this->login = $obj->login;
				$this->pass_indatabase = $obj->pass;
				$this->pass_indatabase_crypted = $obj->pass_crypted;
				$this->pass = $obj->pass;
				$this->pass_temp = $obj->pass_temp;
				$this->office_phone = $obj->office_phone;
				$this->office_fax   = $obj->office_fax;
				$this->user_mobile  = $obj->user_mobile;
				$this->email = $obj->email;
				$this->admin = $obj->admin;
				$this->note = $obj->note;
				$this->statut = $obj->statut;
				$this->lang = $obj->lang;
				
				$this->datec  = $obj->datec;
				$this->datem  = $obj->datem;
				$this->datelastlogin     = $obj->datel;
				$this->datepreviouslogin = $obj->datep;
				
				$this->webcal_login         = $obj->webcal_login;
				$this->phenix_login         = $obj->phenix_login;
				$this->phenix_pass_crypted  = $obj->phenix_pass;
				$this->societe_id           = $obj->fk_societe;
				$this->contact_id           = $obj->fk_socpeople;
				$this->fk_member            = $obj->fk_member;
				
				if (! $this->lang) $this->lang='fr_FR';

				$this->db->free($result);
			}
			else
			{
				$this->error="USERNOTFOUND";
				dolibarr_syslog("User::fetch user not found", LOG_DEBUG);

				$this->db->free($result);
				return 0;
			} 			
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("User::fetch Error -1, fails to get user - ".$this->error." - sql=".$sql, LOG_ERR);
			return -1;
		}
		
		// Recupere parametrage global propre à l'utilisateur
		// \todo a stocker/recupérer en session pour eviter ce select a chaque page
		$sql = "SELECT param, value FROM ".MAIN_DB_PREFIX."user_param";
		$sql.= " WHERE fk_user = ".$this->id;
		$sql.= " AND page = ''";
		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$p=$obj->param;
				if ($p) $this->conf->$p = $obj->value;
				$i++;
			}
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("User::fetch Error -2, fails to get setup user - ".$this->error." - sql=".$sql, LOG_ERR);
			return -2;
		}
		
		// Recupere parametrage propre à la page et à l'utilisateur
		// \todo SCRIPT_URL non defini sur tous serveurs
		// Paramétrage par page desactivé pour l'instant
		if (1==2 && isset($_SERVER['SCRIPT_URL']))
		{
			$sql = "SELECT param, value FROM ".MAIN_DB_PREFIX."user_param";
			$sql.= " WHERE fk_user = ".$this->id;
			$sql.= " AND page='".$_SERVER['SCRIPT_URL']."'";
			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				$page_param_url = '';
				$this->page_param = array();
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$this->page_param[$obj->param] = $obj->value;
					$page_param_url .= $obj->param."=".$obj->value."&amp;";
					$i++;
				}
				$this->page_param_url = $page_param_url;
				$this->db->free($result);
			}
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
		
		return 1;
	}
  
  /**
   *    \brief      Ajoute un droit a l'utilisateur
   *    \param      rid         id du droit à ajouter
   *    \param      allmodule   Ajouter tous les droits du module allmodule
   *    \param      allperms    Ajouter tous les droits du module allmodule, perms allperms
   *    \return     int         > 0 si ok, < 0 si erreur
   */
    function addrights($rid,$allmodule='',$allperms='')
    {
        dolibarr_syslog("User::addrights $rid, $allmodule, $allperms");
        $err=0;
        $whereforadd='';

        $this->db->begin();

        if ($rid)
        {
            // Si on a demandé ajout d'un droit en particulier, on récupère
            // les caractéristiques (module, perms et subperms) de ce droit.
            $sql = "SELECT module, perms, subperms";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE ";
            $sql.=" id = '".$rid."'";

            $result=$this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
                $module=$obj->module;
                $perms=$obj->perms;
                $subperms=$obj->subperms;
            }
            else {
                $err++;
                dolibarr_print_error($this->db);
            }

            // Where pour la liste des droits à ajouter
            $whereforadd="id=".$rid;
            // Ajout des droits induits
            if ($subperms) $whereforadd.=" OR (module='$module' AND perms='$perms' AND subperms='lire')";
            if ($perms)    $whereforadd.=" OR (module='$module' AND perms='lire' AND subperms IS NULL)";
        }
        else {
            // On a pas demandé un droit en particulier mais une liste de droits
            // sur la base d'un nom de module de de perms
            // Where pour la liste des droits à ajouter
            if ($allmodule) $whereforadd="module='$allmodule'";
            if ($allperms)  $whereforadd=" AND perms='$allperms'";
        }

        // Ajout des droits trouvés grace au critere whereforadd
        if ($whereforadd)
        {
            //print "$module-$perms-$subperms";
            $sql = "SELECT id";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE ".$whereforadd;

            $result=$this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    $nid = $obj->id;

                    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$nid";
                    if (! $this->db->query($sql)) $err++;
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $nid)";
                    if (! $this->db->query($sql)) $err++;

                    $i++;
                }
            }
            else
            {
                $err++;
                dolibarr_print_error($this->db);
            }
        }

        if ($err) {
            $this->db->rollback();
            return -$err;
        }
        else {
            $this->db->commit();
            return 1;
        }

    }


  /**
   *    \brief      Retire un droit a l'utilisateur
   *    \param      rid         id du droit à retirer
   *    \param      allmodule   Retirer tous les droits du module allmodule
   *    \param      allperms    Retirer tous les droits du module allmodule, perms allperms
   *    \return     int         > 0 si ok, < 0 si erreur
   */
    function delrights($rid,$allmodule='',$allperms='')
    {
        $err=0;
        $wherefordel='';

        $this->db->begin();

        if ($rid)
        {
            // Si on a demandé supression d'un droit en particulier, on récupère
            // les caractéristiques module, perms et subperms de ce droit.
            $sql = "SELECT module, perms, subperms";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE ";
            $sql.=" id = '".$rid."'";

            $result=$this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
                $module=$obj->module;
                $perms=$obj->perms;
                $subperms=$obj->subperms;
            }
            else {
                $err++;
                dolibarr_print_error($this->db);
            }

            // Where pour la liste des droits à supprimer
            $wherefordel="id=".$rid;
            // Suppression des droits induits
            if ($subperms=='lire') $wherefordel.=" OR (module='$module' AND perms='$perms' AND subperms IS NOT NULL)";
            if ($perms=='lire')    $wherefordel.=" OR (module='$module')";
        }
        else {
            // On a demandé suppression d'un droit sur la base d'un nom de module ou perms
            // Where pour la liste des droits à supprimer
            if ($allmodule) $wherefordel="module='$allmodule'";
            if ($allperms)  $wherefordel=" AND perms='$allperms'";
        }

        // Suppression des droits selon critere defini dans wherefordel
        if ($wherefordel)
        {
            //print "$module-$perms-$subperms";
            $sql = "SELECT id";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE $wherefordel";

            $result=$this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    $nid = $obj->id;

                    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$nid";
                    if (! $this->db->query($sql)) $err++;

                    $i++;
                }
            }
            else
            {
                $err++;
                dolibarr_print_error($this->db);
            }
        }

        if ($err) {
            $this->db->rollback();
            return -$err;
        }
        else {
            $this->db->commit();
            return 1;
        }

    }


	/**
	*    \brief      Vide la tableau des droits de l'utilisateur
	*/
	function clearrights()
    {
		$this->rights='';
		$this->all_permissions_are_loaded=false;
	}


	/**
		\brief      Charge dans l'objet user, la liste des permissions auxquelles l'utilisateur a droit
		\param      module    nom du module dont il faut récupérer les droits ('' par defaut signifie tous les droits)
	*/
	function getrights($module='')
	{
		if ($module && isset($this->tab_loaded[$module]) && $this->tab_loaded[$module]) { return; }    // Le fichier de ce module est deja chargé

		if ($this->all_permissions_are_loaded)
		{
			// Si les permissions ont déja été chargé pour ce user, on quitte
			return;
		}
		
		// Récupération des droits utilisateurs + récupération des droits groupes
		dolibarr_syslog('User::Getrights id='.$this->id.' module='.$module, LOG_DEBUG);
		
		// D'abord les droits utilisateurs
		$sql = "SELECT r.module, r.perms, r.subperms";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as r";
		$sql .= " WHERE r.id = ur.fk_id AND ur.fk_user= ".$this->id." AND r.perms IS NOT NULL";
		
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);

				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;

				if ($perms)
				{
					if ($subperms)
					{
						if (! isset($this->rights->$module) ||
								(is_object($this->rights->$module) && ! isset($this->rights->$module->$perms)) ||
								(is_object($this->rights->$module->$perms)) )
						{
							$this->rights->$module->$perms->$subperms = 1;
						}
					}
					else
					{
						$this->rights->$module->$perms = 1;
					}

				}
				$i++;
			}
			$this->db->free($result);
		}

		// Maintenant les droits groupes
		$sql  = " SELECT r.module, r.perms, r.subperms";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as gr, ".MAIN_DB_PREFIX."usergroup_user as gu, ".MAIN_DB_PREFIX."rights_def as r";
		$sql .= " WHERE r.id = gr.fk_id AND gr.fk_usergroup = gu.fk_usergroup AND gu.fk_user = ".$this->id." AND r.perms IS NOT NULL";

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($result);

				if (strlen($row[1]) > 0)
				{

					if (strlen($row[2]) > 0)
					{
						$this->rights->$row[0]->$row[1]->$row[2] = 1;
					}
					else
					{
						$this->rights->$row[0]->$row[1] = 1;
					}

				}
				$i++;
			}
			$this->db->free($result);
		}

		if (! $module)
		{
			// Si module etait non defini, alors on a tout chargé, on peut donc considérer
			// que les droits sont en cache (car tous chargés) pour cet instance de user
			$this->all_permissions_are_loaded=1;
		}
		else
		{
			// Si module défini, on considère chargé en cache que le module
			$this->tab_loaded[$module]=1;
		}
	}

    /**
     *      \brief      Change statut d'un utilisateur
     *      \return     int     <0 si ko, >0 si ok
     */
    function setstatus($statut)
    {
      $error=0;
      
      $this->db->begin();
      
      // Désactive utilisateur
      $sql = "UPDATE ".MAIN_DB_PREFIX."user";
      $sql.= " SET statut = ".$statut;
      $sql.= " WHERE rowid = ".$this->id;
      $result = $this->db->query($sql);
      
      if ($result)
        {
	  // Appel des triggers
	  include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	  $interface=new Interfaces($this->db);
	  $result=$interface->run_triggers('USER_DISABLE',$this,$user,$lang,$conf);
	  if ($result < 0) $error++;
	  // Fin appel triggers
        }
      
      if ($error)
        {
	  $this->db->rollback();
	  return -$error;
        }
      else
        {
	  $this->db->commit();
	  return 1;
        }
    }
  
  
  /**
   *    \brief      Supprime complètement un utilisateur
   */
  function delete()
  {
    global $conf,$langs;
    
    $this->db->begin();
    
    $this->fetch();
    
    // Supprime droits
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = ".$this->id;
    if ($this->db->query($sql))
    {
	
    }
    
    // Si contact, supprime lien
    if ($this->contact_id)
    {
    	$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_user_creat = null WHERE rowid = ".$this->contact_id;
	    if ($this->db->query($sql))
	    {
	    
	    }
    }
    
    // Supprime utilisateur
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."user WHERE rowid = $this->id";
    $result = $this->db->query($sql);
    
    if ($result)
    {
    	// Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('USER_DELETE',$this,$user,$lang,$conf);
	    if ($result < 0) $error++;
	    // Fin appel triggers
	
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
   *  \brief      Crée l'utilisateur en base
   *  \param      user        	Objet user qui demande la creation
   *  \param      notrigger		1 ne declenche pas les triggers, 0 sinon
   *  \return     int         	<0 si KO, id compte créé si OK
   */
	function create($user='',$notrigger=0)
	{
		global $conf,$langs;
		
		// Nettoyage parametres
		$this->login = trim($this->login);
		
		dolibarr_syslog("User::Create login=".$this->login.", user=".$user->id);
		
		$this->db->begin();
		
		$sql = "SELECT login FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE login ='".addslashes($this->login)."'";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$this->db->free($resql);
			
			if ($num)
			{
				$this->error = $langs->trans("ErrorLoginAlreadyExists");
				return -6;
			}
			else
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user (datec,login,ldap_sid) VALUES(now(),'".addslashes($this->login)."','".$this->ldap_sid."')";
				$result=$this->db->query($sql);
				
				dolibarr_syslog("User::Create sql=".$sql, LOG_DEBUG);
				if ($result)
				{
					$table =  "".MAIN_DB_PREFIX."user";
					$this->id = $this->db->last_insert_id($table);
					
					// Set default rights
					if ($this->set_default_rights() < 0)
					{
						$this->error=$this->db->error();
						$this->db->rollback();
						return -5;
					}
					
					// Update minor fields
					$result = $this->update($user,1,1);
					if ($result < 0)
					{
						$this->db->rollback();
						return -4;
					}

					if ($conf->global->STOCK_USERSTOCK == 1 && $conf->global->STOCK_USERSTOCK_AUTOCREATE == 1)
					{
						require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
						$entrepot = new Entrepot($this->db);
						$entrepot->libelle = 'Stock Personnel '.$this->nom;
						$entrepot->description = 'Cet entrepot représente le stock personnel de '.$this->prenom.' '.$this->nom;
						$entrepot->statut = 1;
						$entrepot->create($user);
					}

					if (! $notrigger)
					{
						// Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
						$interface = new Interfaces($this->db);
						$result = $interface->run_triggers('USER_CREATE',$this,$user,$lang,$conf);
						if ($result < 0) $error++;
						// Fin appel triggers
					}
					
					if (! $error)
					{
						$this->db->commit();
						return $this->id;
					}
					else
					{
						$this->error=$interface->error;
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$this->db->error();
					$this->db->rollback();
					return -2;
				}
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	*      \brief      Créé en base un utilisateur depuis l'objet contact
	*      \param      contact     Objet du contact source
	*      \return     int         si erreur <0, si ok renvoie id compte créé
	*/
	function create_from_contact($contact)
	{
        global $langs;

        // Positionne paramètres
        $this->nom = $contact->nom;
        $this->prenom = $contact->prenom;

        $this->login = strtolower(substr($contact->prenom, 0, 4)) . strtolower(substr($contact->nom, 0, 4));
        $this->admin = 0;

        $this->email = $contact->email;

        $this->db->begin();

        // Crée et positionne $this->id
        $result=$this->create();

        if ($result > 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."user";
            $sql.= " SET fk_socpeople=".$contact->id.", fk_societe=".$contact->societeid;
            $sql.= " WHERE rowid=".$this->id;
            $resql=$this->db->query($sql);

            if ($resql)
            {
                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->error=$this->db->error()." - $sql";
                dolibarr_syslog("User::create_from_contact - 10 - ".$this->error);

                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            // $this->error deja positionné
            dolibarr_syslog("User::create_from_contact - 0");

            $this->db->rollback();
            return $result;
        }

    }

  /**
   *      \brief      Créé en base un utilisateur depuis l'objet adherent
   *      \param      member	Objet adherent source
   *      \return     int		Si erreur <0, si ok renvoie id compte créé
   */
  function create_from_member($member)
  {
        global $conf, $user,$langs;

        // Positionne paramètres
        $this->nom = $member->nom;
        $this->prenom = $member->prenom;

        $this->login = $member->login;
		$this->pass = $member->pass;
        $this->admin = 0;

        $this->email = $member->email;

        $this->db->begin();

        // Crée et positionne $this->id
        $result=$this->create();
        if ($result > 0)
        {
			$result=$this->password($user,$this->pass,$conf->password_encrypted);

			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
            $sql.= " SET fk_member=".$member->id;
            $sql.= " WHERE rowid=".$this->id;
            $resql=$this->db->query($sql);

            if ($resql)
            {
				$this->db->commit();
				return $this->id;
            }
            else
            {
                $this->error=$this->db->error()." - ".$sql;
                dolibarr_syslog("User::create_from_member - 1 - ".$this->error);

                $this->db->rollback();
                return -1;
            }
		}
        else
        {
            // $this->error deja positionné
            dolibarr_syslog("User::create_from_member - 2 - ".$this->error);

            $this->db->rollback();
            return $result;
        }
    }

	/**
	*    \brief      Affectation des permissions par défaut
	*    \return     Si erreur <0, si ok renvoi le nbre de droits par defaut positionnés
	*/
	function set_default_rights()
    {
        $sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def WHERE bydefault = 1";

        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
            $rd = array();
            while ($i < $num)
            {
                $row = $this->db->fetch_row($i);
                $rd[$i] = $row[0];
                $i++;
            }
            $this->db->free();
        }
        $i = 0;
        while ($i < $num)
        {

            $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$rd[$i]";
            $result=$this->db->query($sql);

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $rd[$i])";
            $result=$this->db->query($sql);
            if (! $result) return -1;
            $i++;
        }

        return $i;
    }

  /**
   *    \brief      Mise à jour en base d'un utilisateur (sauf info mot de passe)
   *	\param		user			User qui fait la mise a jour
   *    \param      notrigger		1 ne declenche pas les triggers, 0 sinon
   *	\param		nosyncmember	Do not synchronize linked member
   *    \return     int         	<0 si KO, >=0 si OK
   */
  function update($user,$notrigger=0,$nosyncmember=0)
    {
        global $conf, $langs;
        
		$nbrowsaffected=0;
		$error=0;

        dolibarr_syslog("User::update notrigger=".$notrigger.", nosyncmember=".$nosyncmember);

        // Nettoyage parametres
        $this->nom          = trim($this->nom);
        $this->prenom       = trim($this->prenom);
        $this->fullname     = $this->prenom." ".$this->nom;
        $this->login        = trim($this->login);
        $this->pass         = trim($this->pass);
        $this->office_phone = trim($this->office_phone);
        $this->office_fax   = trim($this->office_fax);
        $this->user_mobile  = trim($this->user_mobile);
        $this->email        = trim($this->email);
        $this->note         = trim($this->note);
        $this->webcal_login = trim($this->webcal_login);
        $this->phenix_login = trim($this->phenix_login);
        if ($this->phenix_pass != $this->phenix_pass_crypted)
        {
        	$this->phenix_pass  = md5(trim($this->phenix_pass));
        }
        $this->admin        = $this->admin?$this->admin:0;
        
        $this->db->begin();
        
        // Mise a jour autres infos
        $sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
        $sql.= " name = '".addslashes($this->nom)."'";
        $sql.= ", firstname = '".addslashes($this->prenom)."'";
        $sql.= ", login = '".addslashes($this->login)."'";
        $sql.= ", admin = ".$this->admin;
        $sql.= ", office_phone = '".addslashes($this->office_phone)."'";
        $sql.= ", office_fax = '".addslashes($this->office_fax)."'";
        $sql.= ", user_mobile = '".addslashes($this->user_mobile)."'";
        $sql.= ", email = '".addslashes($this->email)."'";
        $sql.= ", webcal_login = '".addslashes($this->webcal_login)."'";
        $sql.= ", phenix_login = '".addslashes($this->phenix_login)."'";
        $sql.= ", phenix_pass = '".addslashes($this->phenix_pass)."'";
        $sql.= ", note = '".addslashes($this->note)."'";
        $sql.= " WHERE rowid = ".$this->id;

        dolibarr_syslog("User::update sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	$nbrowsaffected+=$this->db->affected_rows($resql);
        	
        	// Mise a jour mot de passe
	        if ($this->pass)
	        {
	        	if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted)
	       		{
	       			// Si mot de passe saisi et différent de celui en base
	       			$result=$this->password($user,$this->pass,$conf->password_encrypted,0,$notrigger);
	       			
	       			if (! $nbrowsaffected) $nbrowsaffected++;
	       		}
	       	}
	       	
	       	if ($nbrowsaffected)
			{
				if ($this->fk_member && ! $nosyncmember)
				{
					require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
					
					// This user is linked with a member, so we also update members informations
					// if this is an update.
					$adh=new Adherent($this->db);
					$result=$adh->fetch($this->fk_member);
					
					if ($result >= 0)
					{
						$adh->prenom=$this->prenom;
						$adh->nom=$this->nom;
						$adh->login=$this->login;
						$adh->pass=$this->pass;
						$adh->societe=$this->societe_id;

						$adh->email=$this->email;
						$adh->phone=$this->office_phone;
						$adh->phone_mobile=$this->user_mobile;
						
						$adh->commentaire=$this->note;

						$adh->user_id=$this->id;
						$adh->user_login=$this->login;

						$result=$adh->update($user,0,1);
						if ($result < 0)
						{
							$this->error=$luser->error;
							dolibarr_syslog("User::update ".$this->error,LOG_ERROR);
							$error++;
						}
					}
					else
					{
						$this->error=$adh->error;
						$error++;
					}
				}

                if (! $error && ! $notrigger)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('USER_MODIFY',$this,$user,$lang,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }
            }

			if (! $error)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}

			return $nbrowsaffected;
        }
        else
        {
			$this->db->rollback();

            $this->error=$this->db->lasterror();
			dolibarr_syslog("User::update ".$this->error,LOG_ERROR);
            return -1;
        }

   }

	/**
			\brief 		Fonction qui met à jour le commentaire d'un utilisateur
			\param		note			Note
			\param		user			Utilisateur qui réalise la mise a jour
			\return		int				<0 si KO, >0 si OK
	*/
	function update_note($note,$user)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
		$sql.= " note='".addslashes($note)."'";
		$sql.= " WHERE rowid = ".$this->id;

		dolibarr_syslog("User::update_note sql=$sql");
		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
		
		$this->note = $note;
		
		$this->db->commit();
		return 1;
	}

  /**
   *    \brief      Mise à jour en base de la date de deniere connexion d'un utilisateur
   *				Fonction appelée lors d'une nouvelle connexion
   *    \return     <0 si echec, >=0 si ok
   */
  function update_last_login_date()
    {
        dolibarr_syslog ("Mise a jour date derniere connexion pour user->id=".$this->id);

        $now=time();

        $sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
        $sql.= " datepreviouslogin = datelastlogin,";
        $sql.= " datelastlogin = ".$this->db->idate($now).",";
        $sql.= " tms = tms";    // La date de derniere modif doit changer sauf pour la mise a jour de date de derniere connexion
        $sql.= " WHERE rowid = ".$this->id;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->datepreviouslogin=$this->datelastlogin;
            $this->datelastlogin=$now;
            return 1;
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
   }


	/**
	*   \brief     	Change le mot de passe d'un utilisateur
	*   \param     	user             		Object user de l'utilisateur qui fait la modification
	*   \param     	password         		Nouveau mot de passe (à générer si non communiqué)
	*   \param     	noclearpassword			0 ou 1 s'il ne faut pas stocker le mot de passe en clair
	*	\param		changelater				1=Change password only after clicking on confirm email
	*	\param		notrigger				1=Ne declenche pas les triggers
    *	\param		nosyncmember	        Do not synchronize linked member
	*   \return    	string           		If OK return clear password, 0 if no change, < 0 if error
	*/
    function password($user, $password='', $noclearpassword=0, $changelater=0, $notrigger=0, $nosyncmember=0)
    {
        global $conf, $langs;
	
		$error=0;
		
        dolibarr_syslog("User::Password user=".$user->id." password=".eregi_replace('.','*',$password)." noclearpassword=".$noclearpassword." changelater=".$changelater." notrigger=".$notrigger);

        // Si nouveau mot de passe non communiqué, on génère par module
        if (! $password)
        {
        	// TODO Mettre appel au module de génération de mot de passe
        	$password=creer_pass_aleatoire_1('');
        	//$password=creer_pass_aleatoire_2('');
        }

       	// Crypte avec md5
        $password_crypted = md5($password);

		// Mise a jour
		if (! $changelater)
		{
	        $sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql.= " SET pass_crypted = '".addslashes($password_crypted)."',";
			$sql.= " pass_temp = null";
			if ($noclearpassword)
			{
				$sql.= ", pass = null";
			}
			else
			{
				$sql.= ", pass = '".addslashes($password)."'";
			}
	        $sql.= " WHERE rowid = ".$this->id;

			//dolibarr_syslog("User::Password sql=hidden");
			dolibarr_syslog("User::Password sql=".$sql);
	        $result = $this->db->query($sql);
	        if ($result)
	        {
				if ($this->db->affected_rows($result))
	            {
			        $this->pass=$password;
			        $this->pass_indatabase=$password;
			        $this->pass_indatabase_crypted=$password_crypted;

					if ($this->fk_member && ! $nosyncmember)
					{
						require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
	
						// This user is linked with a member, so we also update members informations
						// if this is an update.
						$adh=new Adherent($this->db);
						$result=$adh->fetch($this->fk_member);
						
						if ($result >= 0)
						{
							$result=$adh->password($user,$this->pass,0,0,1);	// Cryptage non géré dans module adhérent
							if ($result < 0)
							{
								$this->error=$adh->error;
								dolibarr_syslog("User::password ".$this->error,LOG_ERROR);
								$error++;
							}
						}
						else
						{
							$this->error=$adh->error;
							$error++;
						}
					}

					if (! $error && ! $notrigger)
					{
		                // Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		                $interface=new Interfaces($this->db);
		                $result=$interface->run_triggers('USER_NEW_PASSWORD',$this,$user,$langs,$conf);
		                if ($result < 0) $this->errors=$interface->errors;
		                // Fin appel triggers
					}
					
	                return $this->pass;
	            }
	            else
				{
	                return 0;
	            }
	        }
	        else
	        {
	            dolibarr_print_error($this->db);
	            return -1;
	        }
		}
		else
		{
			// We store clear password in password temporary field.
			// After receiving confirmation link, we will crypt it and store it in pass_crypted
			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql.= " SET pass_temp = '".addslashes($password)."'";
	        $sql.= " WHERE rowid = ".$this->id;

			// dolibarr_syslog("User::update sql=".$sql);  Pas de trace
	        $result = $this->db->query($sql);
	        if ($result)
			{
                return $password;
			}
			else
			{
	            dolibarr_print_error($this->db);
	            return -3;
	        }
		}
    }


	/**
	*   \brief     	Envoie mot de passe par mail
	*   \param     	user            Object user de l'utilisateur qui fait l'envoi
	*   \param		password        Nouveau mot de passe
	*	\param		changelater		1=Change password only after clicking on confirm email
	*   \return    	int             < 0 si erreur, > 0 si ok
	*/
    function send_password($user, $password='', $changelater=0)
    {
        global $conf,$langs;

        require_once DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php";

        $subject = $langs->trans("SubjectNewPassword");
        $msgishtml=0;

		// Define $msg
        $mesg = '';
		if (! $changelater)
		{
			$mesg.= "A request to change your Dolibarr password has been received.\n";
	    $mesg.= "This is your new keys to login:\n\n";
	    $mesg.= $langs->trans("Login")." : $this->login\n";
	    $mesg.= $langs->trans("Password")." : $password\n\n";
			$mesg.= "\n";
	    $url = "http://".$_SERVER["HTTP_HOST"].DOL_URL_ROOT;
			$mesg.= '<a href="'.$url.'">Go to Dolibarr</a>'."\n\n";
	    $mesg.= "--\n";
	    $mesg.= $user->fullname;	// Username that make then sending
		}
		else
		{
			$mesg.= "A request to change your Dolibarr password has been received.\n";
	    $mesg.= "Your new key to login will be:\n\n";
	    $mesg.= $langs->trans("Login")." : $this->login\n";
			$mesg.= $langs->trans("Password")." : $password\n\n";
			$mesg.= "\n";
			$mesg.= "You must click on the folowing link to validate its change.\n";
	    $url = "http://".$_SERVER["HTTP_HOST"].DOL_URL_ROOT.'/user/passwordforgotten.php?action=validatenewpassword&username='.$this->login."&passwordmd5=".md5($password);
	    $mesg.= $url."\n\n";
			$mesg.= "If you didn't ask anything, just forget this email\n\n";
			dolibarr_syslog("User::send_password url=".$url);
		}
        $mailfile = new CMailFile($subject,$this->email,$conf->notification->email_from,$mesg,
        							array(),array(),array(),
        							'', '', 0, $msgishtml);

        if ($mailfile->sendfile())
        {
            return 1;
        }
        else
        {
            $this->error=$langs->trans("ErrorFailedToSendPassword").' '.$mailfile->error;
			//print nl2br($mesg);
            return -1;
        }
    }

  /**
   * \brief     Renvoie la dernière erreur fonctionnelle de manipulation de l'objet
   * \return    string      chaine erreur
   */
	function error()
    {
      return $this->error;
    }


  /**
   *    \brief      Lecture des infos de click to dial
   */
  function fetch_clicktodial()
    {

      $sql = "SELECT login, pass, poste FROM ".MAIN_DB_PREFIX."user_clicktodial as u";
      $sql .= " WHERE u.fk_user = ".$this->id;

      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object();

	      $this->clicktodial_login = $obj->login;
	      $this->clicktodial_password = $obj->pass;
	      $this->clicktodial_poste = $obj->poste;

	      if (strlen(trim($this->clicktodial_login)) &&
		  strlen(trim($this->clicktodial_password)) &&
		  strlen(trim($this->clicktodial_poste)))
		{
		  $this->clicktodial_enabled = 1;
		}

	    }

	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	}
    }

  /**
   *    \brief      Mise à jour des infos de click to dial
   */
  	function update_clicktodial()
    {

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_clicktodial";
      $sql .= " WHERE fk_user = ".$this->id;

      $result = $this->db->query($sql);

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_clicktodial";
      $sql .= " (fk_user,login,pass,poste)";
      $sql .= " VALUES (".$this->id;
      $sql .= ", '". $this->clicktodial_login ."'";
      $sql .= ", '". $this->clicktodial_password ."'";
      $sql .= ", '". $this->clicktodial_poste."')";

      $result = $this->db->query($sql);

      if ($result)
	{
	  return 0;
	}
      else
	{
	  print $this->db->error();
	}
    }


  /**
   *    \brief      Ajoute l'utilisateur dans un groupe
   *    \param      group       id du groupe
   */
  	function SetInGroup($group)
    {

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
      $sql .= " WHERE fk_user  = ".$this->id;
      $sql .= " AND fk_usergroup = ".$group;

      $result = $this->db->query($sql);

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_user (fk_user, fk_usergroup)";
      $sql .= " VALUES (".$this->id.",".$group.")";

      $result = $this->db->query($sql);
    }

	/**
	 *    \brief      Retire l'utilisateur d'un groupe
	 *    \param      group       id du groupe
	 */
	function RemoveFromGroup($group)
    {

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
      $sql .= " WHERE fk_user  = ".$this->id;
      $sql .= " AND fk_usergroup = ".$group;

      $result = $this->db->query($sql);
    }

	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($option == 'xxx')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$this->id.'">';
			$lienfin='</a>';
		}

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowUser"),'user').$lienfin.' ');
		$result.=$lien.$this->nom.' '.$this->prenom.$lienfin;
		return $result;
	}

	/**
	 *    	\brief      Renvoie login clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 */
	function getLoginUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($option == 'xxx')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$this->id.'">';
			$lienfin='</a>';
		}

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowUser"),'user').$lienfin.' ');
		$result.=$lien.$this->login.$lienfin;
		return $result;
	}

	/**
	 *    \brief      Retourne le libellé du statut d'un user (actif, inactif)
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string        	Libellé du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('users');

		if ($mode == 0)
		{
			$prefix='';
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($statut == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($statut == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
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
		if ($mode==0) $dn=$conf->global->LDAP_KEY_USERS."=".$info[$conf->global->LDAP_KEY_USERS].",".$conf->global->LDAP_USER_DN;
		if ($mode==1) $dn=$conf->global->LDAP_USER_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_USERS."=".$info[$conf->global->LDAP_KEY_USERS];
		return $dn;
	}


	/*
	*	\brief		Initialise tableau info (tableau des attributs LDAP)
	*	\return		array		Tableau info des attributs
	*/
	function _load_ldap_info()
	{
		global $conf,$langs;

		$info=array();

		// Object classes
		$info["objectclass"]=split(',',$conf->global->LDAP_USER_OBJECT_CLASS);
		
		// Champs
		if ($this->fullname  && $conf->global->LDAP_FIELD_FULLNAME) $info[$conf->global->LDAP_FIELD_FULLNAME] = $this->fullname;
		if ($this->nom && $conf->global->LDAP_FIELD_NAME) $info[$conf->global->LDAP_FIELD_NAME] = $this->nom;
		if ($this->prenom && $conf->global->LDAP_FIELD_FIRSTNAME) $info[$conf->global->LDAP_FIELD_FIRSTNAME] = $this->prenom;
		if ($this->login && $conf->global->LDAP_FIELD_LOGIN) $info[$conf->global->LDAP_FIELD_LOGIN] = $this->login;
		if ($this->login && $conf->global->LDAP_FIELD_LOGIN_SAMBA) $info[$conf->global->LDAP_FIELD_LOGIN_SAMBA] = $this->login;
		if ($this->pass && $conf->global->LDAP_FIELD_PASSWORD) $info[$conf->global->LDAP_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypté
		if ($this->ldap_sid && $conf->global->LDAP_FIELD_SID) $info[$conf->global->LDAP_FIELD_SID] = $this->ldap_sid;
		if ($this->societe_id > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->societe_id);

			$info["o"] = $soc->nom;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && $conf->global->LDAP_FIELD_ADDRESS) $info[$conf->global->LDAP_FIELD_ADDRESS] = $this->address;
		if ($this->cp && $conf->global->LDAP_FIELD_ZIP)          $info[$conf->global->LDAP_FIELD_ZIP] = $this->cp;
		if ($this->ville && $conf->global->LDAP_FIELD_TOWN)      $info[$conf->global->LDAP_FIELD_TOWN] = $this->ville;
		if ($this->office_phone && $conf->global->LDAP_FIELD_PHONE) $info[$conf->global->LDAP_FIELD_PHONE] = $this->office_phone;
		if ($this->user_mobile && $conf->global->LDAP_FIELD_MOBILE) $info[$conf->global->LDAP_FIELD_MOBILE] = $this->user_mobile;
		if ($this->office_fax && $conf->global->LDAP_FIELD_FAX)	    $info[$conf->global->LDAP_FIELD_FAX] = $this->office_fax;
		if ($this->note && $conf->global->LDAP_FIELD_DESCRIPTION) $info[$conf->global->LDAP_FIELD_DESCRIPTION] = $this->note;
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
	 *		\brief		Initialise le user avec valeurs fictives aléatoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
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
		$this->ref = 'SPECIMEN';
		$this->specimen=1;

		$this->nom='DOLIBARR';
		$this->prenom='SPECIMEN';
		$this->fullname=trim($this->prenom.' '.$this->nom);
		$this->note='This is a note';
		$this->email='email@specimen.com';
		$this->office_phone='0999999999';
		$this->office_fax='0999999998';
		$this->user_mobile='0999999997';
		$this->admin=0;
		$this->login='dolibspec';
		$this->pass='dolibspec';
		$this->datec=time();
		$this->datem=time();
		$this->webcal_login='dolibspec';

		$this->datelastlogi=time();
		$this->datepreviouslogin=time();
		$this->statut=1;

		$socid = rand(1, $num_socs);
		$this->societe_id = $socids[$socid];
	}

  /**
   *    \brief      Charge la liste des entrepots pour l'utilisateur
   *    \return     int   0 si ok, <> 0 si erreur
   */
  function load_entrepots()
  {
    $err=0;
    $this->entrepots = array();
    $sql = "SELECT e.rowid,ue.consult,ue.send,e.label";
    $sql.= " FROM ".MAIN_DB_PREFIX."user_entrepot as ue,".MAIN_DB_PREFIX."entrepot as e";
    $sql.= " WHERE fk_user = '".$this->id."'";
    $sql .= " AND e.statut = 1";
    $sql .= " AND e.rowid = ue.fk_entrepot";
    
    if ( $this->db->query($sql) ) 
      {
	$i=0;
	while ($obj = $this->db->fetch_object($result) )
	  {
	    $this->entrepots[$i]['id'] = $obj->consult;
	    $this->entrepots[$i]['consult'] = $obj->consult;
	    $this->entrepots[$i]['send'] = $obj->send;
	    $this->entrepots[$i]['label'] = $obj->label;
	    $i++;
	  }
      }
    else 
      {
	$err++;
	dolibarr_print_error($this->db);
      }    
    return $err;
  }

  
     /*
    *       \brief     Charge les informations d'ordre info dans l'objet user
    *       \param     id     id du user a charger
    */
    function info($id)
    {
        $sql = "SELECT u.rowid, u.login as ref, ".$this->db->pdate("datec")." as datec,";
        $sql.= $this->db->pdate("u.tms")." as date_modification";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.rowid = ".$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

			    $this->ref			     = (! $obj->ref) ? $obj->rowid : $obj->ref;
                $this->date_creation     = $obj->datec;
                $this->date_modification = $obj->date_modification;
            }

            $this->db->free($result);

        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }

}


/**
   \brief      Fonction pour créer un mot de passe aléatoire en minuscule
   \param	    sel			Donnée aléatoire
   \return		string		Mot de passe
*/
function creer_pass_aleatoire_1($sel = "")
{
  $longueur = 8;
  
  return strtolower(substr(md5(uniqid(rand())),0,$longueur));
}


/**
   \brief      Fonction pour créer un mot de passe aléatoire mélangeant majuscule,
   minuscule, chiffre et alpha et caractères spéciaux
   \remarks    La fonction a été prise sur http://www.uzine.net/spip
   \param	    sel			Donnée aléatoire
   \return		string		Mot de passe
*/
function creer_pass_aleatoire_2($sel = "")
{
  $longueur=8;
  
  $seed = (double) (microtime() + 1) * time();
  srand($seed);
  
  for ($i = 0; $i < $longueur; $i++)
    {
      if (!$s)
	{
	  if (!$s) $s = rand();
	  $s = substr(md5(uniqid($s).$sel), 0, 16);
	}
      $r = unpack("Cr", pack("H2", $s.$s));
      $x = $r['r'] & 63;
      if ($x < 10) $x = chr($x + 48);
      else if ($x < 36) $x = chr($x + 55);
      else if ($x < 62) $x = chr($x + 61);
      else if ($x == 63) $x = '/';
      else $x = '.';
      $pass .= $x;
      $s = substr($s, 2);
    }  
  return $pass;
}



?>
