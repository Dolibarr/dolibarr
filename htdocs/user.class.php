<?PHP
/* Copyright (c) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/*!	\file htdocs/user.class.php
  \brief  Fichier de la classe utilisateur
  \author Rodolphe Qiedeville
  \author Jean-Louis Bergamo
  \author Laurent Destailleur
  \author Sebastien Di Cintio
  \author Benoit Mortier
  \version $Revision$
*/



/*! \class User
		\brief Classe permettant la gestion d'un utilisateur
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
  var $admin;
  var $login;
  var $pass;
  var $comm;
  var $compta;
	var $societe_id;
  var $webcal_login;
  var $errorstr;
  var $userpref_limite_liste;
  var $all_permissions_are_loaded;         /*!< \private all_permissions_are_loaded */


  /**
   *    \brief Constructeur de la classe
   *    \param  $DB         handler accès base de données
   *    \param  $id         id de l'utilisateur (0 par défaut)
   */
  function User($DB, $id=0)
    {

      $this->db = $DB;
      $this->id = $id;

      $this->comm = 1;
      $this->compta = 1;

      // Preference utilisateur
      $this->userpreflimite_liste = 0;

      $this->all_permissions_are_loaded = 0;

      return 1;
  }

  /**
   *    \brief      Ajoute un droit a l'utilisateur
   *    \param      rid        id du droit à ajouter
   */
  function addrights($rid)
    {
      if (strlen($rid) == 2)
	{
	  $topid = substr($rid,0,1);
	  $lowid = substr($rid,1,1);
	}

      if (strlen($rid) == 3)
	{
	  $topid = substr($rid,0,2);
	  $lowid = substr($rid,2,1);
	}

      if ($lowid == 1)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$rid";
		
		$this->db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $rid)";
	  
		//$sql = "UPDATE ".MAIN_DB_PREFIX."user_rights SET fk_user = $this->id fk_id = $rid";
		
		if ($this->db->query($sql))
	    {
	    }
	}
      
      if ($lowid > 1)
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$rid";
		
		$this->db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $rid)";

		//$sql = "UPDATE ".MAIN_DB_PREFIX."user_rights SET fk_user = $this->id fk_id = $rid";
		
	  if ($this->db->query($sql))
	    {
	    }
	  $nid = $topid . "1";
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$nid";
		
		$this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $nid)";
	  
	  if ($this->db->query($sql))
	    {
	      
	    }
	  else
	    {
    	  dolibarr_print_error($this->db);
	    }
	}
      
      if ($lowid == 0)
	{
	  for ($i = 1 ; $i < 10 ; $i++)
	    {
	      $nid = $topid . "$i";

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$nid";
				
				$this->db->query($sql);
	
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $nid)";
	      
				if ($this->db->query($sql))
		{
		  
		}
	      else
		{
    	  dolibarr_print_error($this->db);
		}
	    }
	}
      
      
      return 1;
    }


  /**
   *    \brief      Retire un droit a l'utilisateur
   *    \param      rid        id du droit à retirer
   */
  function delrights($rid)
    {

      if (strlen($rid) == 2)
	{
	  $topid = substr($rid,0,1);
	  $lowid = substr($rid,1,1);
	}

      if (strlen($rid) == 3)
	{
	  $topid = substr($rid,0,2);
	  $lowid = substr($rid,2,1);
	}

      if ($lowid > 1)
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$rid";
	  if ($this->db->query($sql))
	    {
	    }
	}
      
      if ($lowid == 1)
	{
	  $fid = $topid . "0";
	  $lid = $topid . "9";
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id >= $fid AND fk_id <= $lid";
	  if ($this->db->query($sql))
	    {
	      
	    }
	  else
	    {
	      print $sql;
	    }
	}
      
      if ($lowid == 0)
	{
	  for ($i = 1 ; $i < 10 ; $i++)
	    {
	      $nid = $topid . "$i";
	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$nid";
	      if ($this->db->query($sql))
		{
		  
		}
	      else
		{
		  print $sql;
		}
	    }
	}
            
      return 1;
    }

  /**
   *    \brief      Charge dans l'objet user, la liste des permissions auquels l'utilisateur a droit
   *    \param      module    nom du module dont il faut récupérer les droits ('' par defaut signifie tous les droits)
   */
  function getrights($module='')
    {
      if ($this->all_permissions_are_loaded) {
        // Si les permissions ont déja été chargé pour ce user, on quitte
        // Cela évite de faire n fois le select quand la fonction est appelée plusieurs fois
        // pour charger les droits de différents modules. On les charges tous la
        // première fois, puis on ne fait plus rien.
        return;
      }

      $sql = "SELECT fk_user, fk_id FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user= $this->id";
      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object();
	      
	      if ($module == 'facture' or $module == '')
		{
		  if ($obj->fk_id == 11)
		    $this->rights->facture->lire = 1;
		  
		  if ($obj->fk_id == 12)
		    $this->rights->facture->creer = 1;
		  
		  if ($obj->fk_id == 13)
		    $this->rights->facture->modifier = 1;
		  
		  if ($obj->fk_id == 14)
		    $this->rights->facture->valider = 1;

		  if ($obj->fk_id == 15)
		    $this->rights->facture->envoyer = 1;

		  if ($obj->fk_id == 16)
		    $this->rights->facture->paiement = 1;

		  if ($obj->fk_id == 19)
		    $this->rights->facture->supprimer = 1;
		  
		}
	      if ($module == 'propale' or $module == '')
		{
		  if ($obj->fk_id == 21)
		    $this->rights->propale->lire = 1;
		  
		  if ($obj->fk_id == 22)
		    $this->rights->propale->creer = 1;
		  
		  if ($obj->fk_id == 23)
		    $this->rights->propale->modifier = 1;
		  
		  if ($obj->fk_id == 24)
		    $this->rights->propale->valider = 1;
		  
		  if ($obj->fk_id == 25)
		    $this->rights->propale->envoyer = 1;
		  
		  if ($obj->fk_id == 26)
		    $this->rights->propale->cloturer = 1;
		  
		  if ($obj->fk_id == 27)
		    $this->rights->propale->supprimer = 1;
		}
	    
	      if ($module == 'produit' or $module == '')
		{

		  if ($obj->fk_id == 31)
		    $this->rights->produit->lire = 1;
			
		  if ($obj->fk_id == 32)
		    $this->rights->produit->creer = 1;
			
		  if ($obj->fk_id == 33)
		    $this->rights->produit->modifier = 1;
			
		  if ($obj->fk_id == 34)
		    $this->rights->produit->supprimer = 1;

		}
	      if ($module == 'projet' or $module == '')
		{

		  if ($obj->fk_id == 41)
		    $this->rights->projet->lire = 1;
			
		  if ($obj->fk_id == 42)
		    $this->rights->projet->creer = 1;
			
		  if ($obj->fk_id == 43)
		    $this->rights->projet->modifier = 1;
			
		  if ($obj->fk_id == 44)
		    $this->rights->projet->supprimer = 1;
		}

	      if ($module == 'commande' or $module == '')
		{

		  if ($obj->fk_id == 81)
		    $this->rights->commande->lire = 1;
			
		  if ($obj->fk_id == 82)
		    $this->rights->commande->creer = 1;
						
		  if ($obj->fk_id == 84)
		    $this->rights->commande->valider = 1;

		  if ($obj->fk_id == 89)
		    $this->rights->commande->supprimer = 1;
		}

	      if ($module == 'expedition' or $module == '')
		{

		  if ($obj->fk_id == 101)
		    $this->rights->expedition->lire = 1;
			
		  if ($obj->fk_id == 102)
		    $this->rights->expedition->creer = 1;
						
		  if ($obj->fk_id == 104)
		    $this->rights->expedition->valider = 1;

		  if ($obj->fk_id == 109)
		    $this->rights->expedition->supprimer = 1;
		}

	      if ($module == 'adherent' or $module == '')
		{

		  if ($obj->fk_id == 71)
		    $this->rights->adherent->lire = 1;
			
		  if ($obj->fk_id == 72)
		    $this->rights->adherent->creer = 1;
			
		  if ($obj->fk_id == 73)
		    $this->rights->adherent->modifier = 1;
			
		  if ($obj->fk_id == 74)
		    $this->rights->adherent->supprimer = 1;

		}

	      if ($module == 'compta' or $module == '')
		{
		  if ($obj->fk_id == 91)
		    $this->rights->compta->charges->lire = 1;

		  if ($obj->fk_id == 92)
		    $this->rights->compta->charges->creer = 1;
		    
		  if ($obj->fk_id == 93)
		    $this->rights->compta->charges->supprimer = 1;

		  if ($obj->fk_id == 95)
		    $this->rights->compta->resultat->lire = 1;

		}
		
	      if ($module == 'banque' or $module == '')
		{
		  if ($obj->fk_id == 111)
		    $this->rights->banque->lire = 1;

		  if ($obj->fk_id == 112)
		    $this->rights->banque->modifier = 1;

		  if ($obj->fk_id == 113)
		    $this->rights->banque->configurer = 1;
		}

	      if ($module == 'societe' or $module == '')
		{
		  if ($obj->fk_id == 121)
		    $this->rights->societe->lire = 1;

		  if ($obj->fk_id == 122)
		    $this->rights->societe->creer = 1;

		  if ($obj->fk_id == 129)
		    $this->rights->societe->supprimer = 1;
		}

	      if ($module == 'caisse' or $module == '')
		{
		  if ($obj->fk_id == 131)
		    $this->rights->caisse->lire = 1;

		  if ($obj->fk_id == 132)
		    $this->rights->caisse->modifier = 1;

		  if ($obj->fk_id == 133)
		    $this->rights->caisse->configurer = 1;
		}

	      if ($module == 'telephonie' or $module == '')
		{
		  if ($obj->fk_id == 141)
		    $this->rights->telephonie->lire = 1;

		  if ($obj->fk_id == 142)
		    $this->rights->telephonie->ligne_commander = 1;

		  if ($obj->fk_id == 143)
		    $this->rights->telephonie->ligne_activer = 1;

		}


	      $i++;
	  }
	    $this->db->free();	    

        if ($module == '') {
            // Si module etait non defini, alors on a tout chargé, on peut donc considérer
            // que les droits sont cachés (car tous chargés) pour cet instance de user
            $this->all_permissions_are_loaded=1;
        }
	  }
	else
	  {
    	  dolibarr_print_error($this->db);
	  }
    }

  /**
   *    \brief      Charge un objet user avec toutes ces caractéristiques depuis un login
   *    \param      login   login a charger
   */
	 
  function fetch($login='')
    {
      
      //$sql = "SELECT u.rowid, u.name, u.firstname, u.email, u.code, u.admin, u.module_comm, u.module_compta, u.login, u.pass, u.webcal_login, u.note";
      //$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
      $sql = "SELECT * FROM ".MAIN_DB_PREFIX."user as u";
      if ($this->id)
	{
	  $sql .= " WHERE u.rowid = $this->id";
	}
      else
	{
	  $sql .= " WHERE u.login = '$login'";
	}
      
      $result = $this->db->query($sql);

      if ($result) 
	{
	  if ($this->db->num_rows()) 
	    {
	      //$obj = $this->db->fetch_object($result , 0);
	      $obj = $this->db->fetch_object($result);
	      $this->id = $obj->rowid;
	      $this->nom = stripslashes($obj->name);
	      $this->prenom = stripslashes($obj->firstname);
	      
	      $this->note = stripslashes($obj->note);
	      
	      $this->fullname = $this->prenom . ' ' . $this->nom;
	      $this->admin = $obj->admin;
	      $this->webcal_login = $obj->webcal_login;
	      $this->code = $obj->code;
	      $this->email = $obj->email;
	      
	      $this->contact_id = $obj->fk_socpeople;
	      
	      $this->login = $obj->login;
	      $this->pass  = $obj->pass;
	      $this->webcal_login = $obj->webcal_login;
	      
	      $this->societe_id = $obj->fk_societe;
	      $this->egroupware_id = $obj->egroupware_id;
	    }
	  $this->db->free();
	  
	}
      else
	{
    	  dolibarr_print_error($this->db);
	}

      $sql = "SELECT param, value FROM ".MAIN_DB_PREFIX."user_param";
      $sql .= " WHERE fk_user = ".$this->id;
      $sql .= " AND page = '".$_SERVER["SCRIPT_URL"]."'";

      if ( $this->db->query($sql) );
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  $page_param_url = '';
	  $this->page_param = array();
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object($i);
	      $this->page_param[$obj->param] = $obj->value;
	      $page_param_url .= $obj->param . "=".$obj->value."&amp;";
	      $i++;
	    }
	  $this->page_param_url = $page_param_url;
	}


  }

  /**
   *    \brief  Efface de la base, un utilisateur
   */
	 
  function delete()
    {
      if ($this->contact_id) 
	{

	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."user WHERE rowid = $this->id";
  
	  $result = $this->db->query($sql);

	  $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_user = 0 WHERE idp = $this->contact_id";
	    
	  if ($this->db->query($sql)) 
	    {
	      
	    }
	}
      else
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."user SET login = '' WHERE rowid = $this->id";
  
	  $result = $this->db->query($sql);
	}

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id";

      if ($this->db->query($sql)) 
	{
	    
	}

  }

  /**
   *    \brief  Crée en base un utilisateur
   */
  function create()
    {
        $sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='$this->login';";
				//$sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='$this->email';";
      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();
	  $this->db->free();

	  if ($num) 
	    {
	      $this->errorstr = "Ce login existe déjà";
	      return 0;
	    }
	  else
	    {            
				$sql = "insert into ".MAIN_DB_PREFIX."user (datec,login,email)
				values(now(),'$this->login','$this->email');";
	      if ($this->db->query($sql))
		{
		  /*if ($this->db->affected_rows()) 
		    {
		      $this->id = $this->db->last_insert_id();
		      $this->update();
		      $this->set_default_rights();
		      return $this->id;      
		    }*/ // ce code pose probleme en postgres il est remplace par le bloc ci dessous
				// fonctionne autant en postgres que mysql
				$table =  "".MAIN_DB_PREFIX."user";
				$this->id = $this->db->last_insert_id($table);
				$this->set_default_rights();
				$this->update();
				return $this->id;
		}
	      else
		{
		  dolibarr_print_error($this->db);
		}
	    }
	}
      else
	{
    	  dolibarr_print_error($this->db);
	}
    } //fin function

  /**
   * \brief     Créé en base un utilisateur depuis l'objetc contact
   * \param     contact      Objet du contact source
   *
   */
  function create_from_contact($contact)
    {
      $this->nom = $contact->nom;
      $this->prenom = $contact->prenom;
      $this->email = $contact->email;

      $this->login = strtolower(substr($contact->prenom, 0, 3)) . strtolower(substr($contact->nom, 0, 3));

      $sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='$this->login'";

      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();
	  $this->db->free();

	  if ($num) 
	    {
	      $this->errorstr = "Ce login existe déjà";
	      return 0;
	    }
	  else
	    {            
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."user (datec, login, fk_socpeople, fk_societe)";
	      $sql .= " VALUES (now(),'$this->login',$contact->id, $contact->societeid);";
	      if ($this->db->query($sql))
		{
		  if ($this->db->affected_rows()) 
		    {
		      $this->id = $this->db->last_insert_id();
		      $this->admin = 0;
		      $this->update();
		      
		      $sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_user = $this->id WHERE idp = $contact->id";
		      $this->db->query($sql);

		      $this->set_default_rights();

		      return $this->id;
		    }
		}
	      else
		{
		  dolibarr_print_error($this->db);
		}
	    }
	}
      else
	{
    	  dolibarr_print_error($this->db);
	}
      
    }

  /**
   * \brief     Affectation des permissions par défaut
   *
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
		
		$this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $rd[$i])";
	
	  if ($this->db->query($sql)) 
	    {
	    }
	  $i++;
	}
    }

  /**
   * \brief     Mise à jour en base d'un utilisateur
   *
   */
  function update()
    {
		
      $sql = "SELECT login FROM ".MAIN_DB_PREFIX."user WHERE login ='$this->login' AND rowid <> $this->id;";
		 

      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();
	  $this->db->free();
		

	  if ($num) 
	    {
	      $this->errorstr = "Ce login existe déjà";
	      return 0;
	    }
	  else
	    {            
	      if (!strlen($this->code))
		$this->code = $this->login;

	      $sql = "UPDATE ".MAIN_DB_PREFIX."user SET ";
	      $sql .= " name = '$this->nom'";
	      $sql .= ", firstname = '$this->prenom'";
	      $sql .= ", login = '$this->login'";
	      $sql .= ", email = '$this->email'";
	      $sql .= ", admin = $this->admin";
	      $sql .= ", webcal_login = '$this->webcal_login'";
	      $sql .= ", module_comm = 1";
	      $sql .= ", module_compta = 1";
	      $sql .= ", code = '$this->code'";
	      $sql .= ", note = '$this->note'";
	      $sql .= " WHERE rowid = $this->id;";
				
	      
	      $result = $this->db->query($sql);
	      
	      if ($result) 
		{
		  if ($this->db->affected_rows()) 
		    {
		      return 1;		      
		    }		  
		}
	      else
		{
		  dolibarr_print_error($this->db);
		}
	    }
	}
    }

  /**
   * \brief     Change le mot de passe d'un utilisateur et l'envoie par mail
   * \param     password        nouveau mot de passe (généré par defaut si non communiqué)
   * \param     isencrypted     0 ou 1 si il faut crypter le mot de passe en base (0 par défaut)
   */
  function password($password='', $isencrypted = 0)
    {
      if (! $password)
	{
	  $password =  strtolower(substr(md5(uniqid(rand())),0,6));
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
      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);

      if ($result) 
	{
	  if ($this->db->affected_rows()) 
	    {
	      $mesg = "Votre mot de passe pour accéder à Dolibarr a été changé :\n\n";
	      $mesg .= "Login        : $this->login\n";
	      $mesg .= "Mot de passe : $password\n\n";
	      $mesg .= "Adresse      : ".substr($_SERVER["SCRIPT_URI"],0,strlen($_SERVER["SCRIPT_URI"]) - 14);
	      if (mail($this->email, "Mot de passe Dolibarr", $mesg))
		{
		  return 1;
		}
	    }
	  
	}
      else
	{
	  dolibarr_print_error($this->db);
	}
    }

  /**
   * \brief     Renvoie la dernière erreur fonctionnelle de manipulation de l'objet
   * \return    string      chaine erreur
   */
  function error()
    {
      return $this->errorstr;
    }
  

}

?>
