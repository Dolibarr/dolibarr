<?php
/* Copyright (c) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2005      Lionel COUSTEIX      <etm_ltd@tiscali.co.uk>
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
	    \file       htdocs/user.class.php
  	    \brief      Fichier de la classe utilisateur
  	    \author     Rodolphe Qiedeville
  	    \author     Jean-Louis Bergamo
  	    \author     Laurent Destailleur
  	    \author     Sebastien Di Cintio
  	    \author     Benoit Mortier
  	    \author     Regis Houssin
  	    \version    $Revision$
*/



/**
        \class      User
		\brief      Classe permettant la gestion d'un utilisateur
*/

class User
{
  var $db;
	
  var $id;
  var $fullname;
  var $nom;
  var $prenom;
  var $note;
  var $code;
  var $email;
  var $office_tel;
  var $office_fax;
  var $user_mobile;
  var $admin;
  var $login;
  var $pass;
  var $lang;
  var $datec;
  var $datem;
  var $societe_id;
  var $webcal_login;
  var $datelastaccess;
  
  var $error;
  var $userpref_limite_liste;
  var $all_permissions_are_loaded;         /**< \private all_permissions_are_loaded */


  /**
   *    \brief Constructeur de la classe
   *    \param  $DB         handler accès base de données
   *    \param  $id         id de l'utilisateur (0 par défaut)
   */
	 
  function User($DB, $id=0)
    {

      $this->db = $DB;
      $this->id = $id;

      // Preference utilisateur
      $this->liste_limit = 0;
      $this->clicktodial_enabled = 0;
		  
      $this->all_permissions_are_loaded = 0;

      return 1;
  }


  /**
   *    \brief      Charge un objet user avec toutes ces caractéristiques depuis un id ou login
   *    \param      login       Si défini, login a utiliser pour recherche
   */
	 
  function fetch($login='')
    {
        // Recupere utilisateur
        $sql = "SELECT u.rowid, u.name, u.firstname, u.email, u.office_phone, u.office_fax, u.user_mobile, u.code, u.admin, u.login, u.pass, u.webcal_login, u.note,";
        $sql.= " u.fk_societe, u.fk_socpeople, ";
        $sql.= " ".$this->db->pdate("u.datec")." as datec, ".$this->db->pdate("u.tms")." as datem,";
        $sql.= " ".$this->db->pdate("u.datelastaccess")." as datel";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        if ($login)
        {
            $sql .= " WHERE u.login = '$login'";
        }
        else
        {
            $sql .= " WHERE u.rowid = $this->id";
        }
        
        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                $this->nom = stripslashes($obj->name);
                $this->prenom = stripslashes($obj->firstname);
        
                $this->fullname = $this->prenom . ' ' . $this->nom;
                $this->code = $obj->code;
                $this->login = $obj->login;
                $this->pass  = $obj->pass;
 				$this->office_phone  = $obj->office_phone;
 				$this->office_fax  = $obj->office_fax;
 				$this->user_mobile  = $obj->user_mobile;
                $this->email = $obj->email;
                $this->admin = $obj->admin;
                $this->contact_id = $obj->fk_socpeople;
                $this->note = stripslashes($obj->note);
                $this->lang = 'fr_FR';    // \todo Gérer la langue par défaut d'un utilisateur Dolibarr

                $this->datec  = $obj->datec;
                $this->datem  = $obj->datem;
                $this->datelastaccess = $obj->datel;
        
                $this->webcal_login = $obj->webcal_login;
                $this->societe_id = $obj->fk_societe;
            }
            $this->db->free($result);
        
        }
        else
        {
            dolibarr_print_error($this->db);
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
                $this->conf->$p = $obj->value;
                $i++;
            }
            $this->db->free($result);
        }
        else
        {
            dolibarr_print_error($this->db);
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
                dolibarr_print_error($this->db);
            }
        }
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
            // Where pour la liste des droits à ajouter
            if ($allmodule) $whereforadd="module='$allmodule'";
            if ($allperms)  $whereforadd=" AND perms='$allperms'";
        }

        // Ajout des droits de la liste whereforadd
        if ($whereforadd)
        {
            //print "$module-$perms-$subperms";
            $sql = "SELECT id";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE $whereforadd";
            
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
            // Where pour la liste des droits à supprimer
            if ($allmodule) $wherefordel="module='$allmodule'";
            if ($allperms)  $wherefordel=" AND perms='$allperms'";
        }

        // Suppression des droits de la liste wherefordel
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
   *    \brief      Charge dans l'objet user, la liste des permissions auxquelles l'utilisateur a droit
   *    \param      module    nom du module dont il faut récupérer les droits ('' par defaut signifie tous les droits)
   */
	 
  function getrights($module='')
    {
        if ($this->all_permissions_are_loaded)
        {
            // Si les permissions ont déja été chargé pour ce user, on quitte
            return;
        }
        
        // Récupération des droits utilisateurs + récupération des droits groupes

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
        
        if ($module == '')
        {
            // Si module etait non defini, alors on a tout chargé, on peut donc considérer
            // que les droits sont en cache (car tous chargés) pour cet instance de user
            $this->all_permissions_are_loaded=1;
        }
        
    }


    /**
     *      \brief      Désactive un utilisateur
     *      \return     int     <0 si ko, >0 si ok
     */
    function disable()
    {
        $error=0;
        
        // Désactive utilisateur
        $sql = "UPDATE ".MAIN_DB_PREFIX."user";
        $sql.= " SET login = NULL";
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
            return -$error;
        }
        else
        {
            return 1;
        }
    }
  
  
    /**
     *    \brief      Supprime complètement un utilisateur
     */
    function delete()
    {
        // Supprime droits
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id";
        if ($this->db->query($sql))
        {
    
        }
    
        // Si contact, supprime lien
        if ($this->contact_id)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_user = null WHERE idp = $this->contact_id";
            if ($this->db->query($sql))
            {
    
            }
        }
    
        // Supprime utilisateur
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."user WHERE rowid = $this->id";
        $result = $this->db->query($sql);
    }
  

    /**
     *      \brief      Crée un utilisateur en base
     *      \return     int         si erreur <0, si ok renvoie id compte créé
     */
    function create()
    {
        global $langs;
    
        $this->db->begin();

        $sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='".$this->login."';";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $this->db->free($resql);
    
            if ($num)
            {
                $this->error = $langs->trans("ErrorLoginAlreadyExists");
                return -5;
            }
            else
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."user (datec,login) VALUES(now(),'$this->login');";
                $result=$this->db->query($sql);
    
                if ($result)
                {
                    $table =  "".MAIN_DB_PREFIX."user";
                    $this->id = $this->db->last_insert_id($table);
    
                    // Set default rights
                    if ($this->set_default_rights() < 0) 
                    {
                        $this->db->rollback();
        
                        $this->error=$this->db->error();
                        return -4;
                    }
                    
                    // Update minor fields
                    if ($this->update() < 0)
                    {
                        $this->db->rollback();
        
                        $this->error=$this->db->error();
                        return -5;
                    }
    
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('USER_CREATE',$this,$user,$lang,$conf);
                    if ($result < 0) $error++;
                    // Fin appel triggers
    
                    if (! $error)
                    {
                        $this->db->commit();
        
                        return $this->id;
                    }
                    else
                    {
                        $this->db->rollback();
        
                        $this->error=$interface->error;
                        return -3;
                    }
                }
                else
                {
                    $this->db->rollback();
                    $this->error=$this->db->error();
                    return -2;
                }
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->error();
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

        $this->login = strtolower(substr($contact->prenom, 0, 3)) . strtolower(substr($contact->nom, 0, 3));
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
                $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople";
                $sql.= " SET fk_user = ".$this->id;
                $sql.= " WHERE idp = ".$contact->id;
                $resql=$this->db->query($sql);
        
                if ($resql)
                {
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                    $this->error=$this->db->error()." - $sql";
                    dolibarr_syslog("User::create_from_contact - 20 - ".$this->error);

                    $this->db->rollback();
                    return -2;
                }                        
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
   *    \brief      Affectation des permissions par défaut
   *    \return     si erreur <0, si ok renvoi le nbre de droits par defaut positionnés
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
   *    \brief      Mise à jour en base d'un utilisateur
   *    \param      create      1 si update durant le create, 0 sinon
   *    \return     int         <0 si echec, >=0 si ok
   */
  function update($create=0)
    {
        global $langs;
        
        $error=0;
        
        if (!strlen($this->code)) $this->code = $this->login;

        $sql = "UPDATE ".MAIN_DB_PREFIX."user SET ";
        $sql .= " name = '$this->nom'";
        $sql .= ", firstname = '$this->prenom'";
        $sql .= ", login = '$this->login'";
        if ($this->pass) $sql .= ", pass = '$this->pass'";
        $sql .= ", admin = $this->admin";
        $sql .= ", office_phone = '$this->office_phone'";
        $sql .= ", office_fax = '$this->office_fax'";
        $sql .= ", user_mobile = '$this->user_mobile'";
        $sql .= ", email = '$this->email'";
        $sql .= ", webcal_login = '$this->webcal_login'";
        $sql .= ", code = '$this->code'";
        $sql .= ", note = '$this->note'";
        $sql .= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->affected_rows())
            {
                if (! $create)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('USER_MODIFY',$this,$user,$lang,$conf);
                    if ($result < 0) $error++;
                    // Fin appel triggers
                }
                
                return 1;
            }
            return 0;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }

   }


  /**
   *    \brief      Mise à jour en base de la date de deniere connexion d'un utilisateur
   *    \return     <0 si echec, >=0 si ok
   */
  function update_last_login_date()
    {
        dolibarr_syslog ("Mise a jour date derniere connexion pour user->id=".$this->id);
        $sql = "UPDATE ".MAIN_DB_PREFIX."user";
        $sql.= " SET datelastaccess = sysdate()";
        $sql.= " WHERE rowid = ".$this->id;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
   }
   
   
  /**
   *    \brief     Change le mot de passe d'un utilisateur
   *    \param     user             Object user de l'utilisateur qui fait la modification
   *    \param     password         Nouveau mot de passe (généré par defaut si non communiqué)
   *    \param     isencrypted      0 ou 1 si il faut crypter le mot de passe en base (0 par défaut)
   *    \return    string           mot de passe, < 0 si erreur
   */
    function password($user, $password='', $isencrypted = 0)
    {
        global $langs;
        $longueurmotdepasse=8;
        
        if (! $password)
        {
            $password =  strtolower(substr(md5(uniqid(rand())),0,$longueurmotdepasse));
        }
    
        if ($isencrypted)
        {
            $sqlpass = crypt($password, "CRYPT_STD_DES");
        }
        else
        {
            $sqlpass = $password;
        }
        $this->pass=$password;
        $sql = "UPDATE ".MAIN_DB_PREFIX."user SET pass = '".$sqlpass."'";
        $sql.= " WHERE rowid = $this->id";
    
        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->affected_rows())
            {
                return $this->pass;
            }
            else {
                return -2;
            }
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
    }


  /**
   *    \brief     Envoie mot de passe par mail
   *    \param     user             Object user de l'utilisateur qui fait l'envoi
   *    \param     password         Nouveau mot de passe
   *    \return    int              < 0 si erreur, > 0 si ok
   */
    function send_password($user, $password='')
    {
        global $langs;
        
        require_once DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php";

        $subject = $langs->trans("SubjectNewPassword");
        
        $mesg .= "Bonjour,\n\n";
        $mesg .= "Votre mot de passe pour accéder à Dolibarr a été changé :\n\n";
        $mesg .= $langs->trans("Login")." : $this->login\n";
        $mesg .= $langs->trans("Password")." : $password\n\n";

        $mesg .= "Adresse : http://".$_SERVER["HTTP_HOST"].DOL_URL_ROOT;
        $mesg .= "\n\n";
        $mesg .= "--\n";
        $mesg.= $user->fullname;

        $mailfile = new CMailFile($subject,$this->email,$conf->email_from,$mesg,array(),array(),array());

        if ($mailfile->sendfile())
        {
            return 1;
        }
        else
        {
            $this->error=$langs->trans("ErrorFailedToSendPassword");
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
}

?>
