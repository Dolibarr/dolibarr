<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/adherents/adherent.class.php
        \ingroup    adherent
		\brief      Fichier de la classe permettant la gestion d'un adhérent
		\author     Rodolphe Qiedeville
		\author	    Jean-Louis Bergamo
		\author	    Laurent Destailleur
		\author     Sebastien Di Cintio
		\author     Benoit Mortier
		\version    $Revision$
*/


/**
        \class      Adherent
		\brief      Classe permettant la gestion d'un adhérent
*/

class Adherent
{
	var $id;
	var $db;
	var $prenom;
	var $nom;
	var $fullname;
	var $societe;
	var $adresse;
	var $cp;
	var $ville;
	var $pays_id;
	var $pays_code;
	var $pays;
	var $morphy;
	var $email;
	var $public;
	var $commentaire;
	var $statut;
	var $login;
	var $pass;
	var $naiss;
	var $photo;

	var $typeid;			// Id type adherent
	var $type;			// Libellé type adherent
	var $need_subscription;

	//  var $public;
	var $array_options;

	var $error;

/**
		\brief Adherent
		\param DB		base de données
		\param id		id de l'adhérent
*/

  function Adherent($DB, $id='')
  {
    $this->db = $DB ;
    $this->id = $id;
    $this->statut = -1;
    // l'adherent n'est pas public par defaut
    $this->public = 0;
    // les champs optionnels sont vides
    $this->array_options=array();
  }


	/**
		\brief	    function envoyant un email au destinataire (recipient) avec le text fourni en parametre.
		\param	    recipients		destinataires
		\param	    text			contenu du message
		\param	    subject			sujet du message
		\return		int				<0 si ko, >0 si ok
		\remarks		La particularite de cette fonction est de remplacer certains champs
		\remarks		par leur valeur pour l'adherent en l'occurrence :
		\remarks		%PRENOM% : est remplace par le prenom
		\remarks		%NOM% : est remplace par nom
		\remarks		%INFOS% : l'ensemble des attributs de cet adherent
		\remarks		%SERVEUR% : URL du serveur web
		\remarks		etc..
	*/
	function send_an_email($recipients,$text,$subject="Vos coordonnees sur %SERVEUR%")
	{
		global $conf,$langs;

	    $patterns = array (
		       '/%PRENOM%/',
		       '/%NOM%/',
		       '/%INFOS%/',
		       '/%INFO%/',
		       '/%SERVEUR%/',
		       '/%SOCIETE%/',
		       '/%ADRESSE%/',
		       '/%CP%/',
		       '/%VILLE%/',
		       '/%PAYS%/',
		       '/%EMAIL%/',
		       '/%NAISS%/',
		       '/%PHOTO%/',
		       '/%LOGIN%/',
		       '/%PASS%/'
		       );
	    $infos.= $langs->trans("Lastname").": $this->nom\n";
	    $infos = $langs->trans("Firstname").": $this->prenom\n";
	    $infos.= $langs->trans("Company").": $this->societe\n";
	    $infos.= $langs->trans("Address").": $this->adresse\n";
	    $infos.= $langs->trans("Zip").": $this->cp\n";
	    $infos.= $langs->trans("Town").": $this->ville\n";
	    $infos.= $langs->trans("Country").": $this->pays\n";
	    $infos.= $langs->trans("EMail").": $this->email\n";
	    $infos.= $langs->trans("Login").": $this->login\n";
	    $infos.= $langs->trans("Password").": $this->pass\n";
	    $infos.= $langs->trans("Birthday").": $this->naiss\n";
	    $infos.= $langs->trans("Photo").": $this->photo\n";
		$infos.= $langs->trans("Public").": ".yn($this->public)."\n";

	    $replace = array (
		      $this->prenom,
		      $this->nom,
		      $infos,
		      $infos,
		      "http://".$_SERVER["SERVER_NAME"]."/",
		      $this->societe,
		      $this->adresse,
		      $this->cp,
		      $this->ville,
		      $this->pays,
		      $this->email,
		      $this->naiss,
		      $this->photo,
		      $this->login,
		      $this->pass
		      );
		$texttosend = preg_replace ($patterns, $replace, $text);
		$subjectosend = preg_replace ($patterns, $replace, $subject);
		$msgishtml=0;

		// Envoi mail confirmation
        include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");

        $from=$conf->email_from;
        if ($conf->global->ADHERENT_MAIL_FROM) $from=$conf->global->ADHERENT_MAIL_FROM;

		$mailfile = new CMailFile($subjectosend,$this->email,$from,$texttosend,
									array(),array(),array(),
									'', '', 0, $msgishtml);
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
		\brief	imprime une liste d'erreur.
*/

  function print_error_list()
  {
    $num = sizeof($this->error);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->error[$i];
      }
  }


/**
		\brief      Renvoie le libelle traduit de la nature d'un adherent (physique ou morale)
		\param	    morphy		Nature physique ou morale de l'adhérent
*/

  function getmorphylib($morphy='')
  {
    global $langs;
    if (! $morphy) { $morphy=$this->morphy; }
    if ($morphy == 'phy') { return $langs->trans("Physical"); }
    if ($morphy == 'mor') { return $langs->trans("Moral"); }
    return $morphy;
  }

/**
		\brief      Vérifie les données entrées
		\param	    minimum
*/

  function check($minimum=0)
    {
      $err = 0;

      if (strlen(trim($this->societe)) == 0)
	{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	    {
	      $error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	      $err++;
	    }
	}

      if (strlen(trim($this->adresse)) == 0)
	{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->cp)) == 0)
	{
	  $error_string[$err] = "Le code postal saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->ville)) == 0)
	{
	  $error_string[$err] = "La ville saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->email)) == 0)
	{
	  $error_string[$err] = "L'email saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->login)) == 0)
	{
	  $error_string[$err] = "Le login saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->pass)) == 0)
	{
	  $error_string[$err] = "Le pass saisi est invalide";
	  $err++;
	}
      $this->amount = trim($this->amount);

      $map = range(0,9);
      for ($i = 0; $i < strlen($this->amount) ; $i++)
	{
	  if (!isset($map[substr($this->amount, $i, 1)] ))
	    {
	      $error_string[$err] = "Le montant du don contient un/des caractère(s) invalide(s)";
	      $err++;
	      $amount_invalid = 1;
	      break;
	    }
	}

      if (! $amount_invalid)
	{
	  if ($this->amount == 0)
	    {
	      $error_string[$err] = "Le montant du don est null";
	      $err++;
	    }
	  else
	    {
	      if ($this->amount < $minimum && $minimum > 0)
		{
		  $error_string[$err] = "Le montant minimum du don est de $minimum";
		  $err++;
		}
	    }
	}

      /*
       * Return errors
       */

      if ($err)
	{
	  $this->error = $error_string;
	  return 0;
	}
      else
	{
	  return 1;
	}

    }

	/**
		\brief  	Fonction qui crée l'adhérent
		\return		int			<0 si ko, >0 si ok
	*/
	function create()
	{
		global $conf,$langs,$user;

		// Verification parametres
		if ($conf->global->ADHERENT_MAIL_REQUIRED && ! ValidEMail($this->email)) {
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}

		$this->date = $this->db->idate($this->date);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent (datec)";
		$sql .= " VALUES (now())";

		$result = $this->db->query($sql);

		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent");
			$result=$this->update(1);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MEMBER_CREATE',$this,$user,$langs,$conf);
            // Fin appel triggers

			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
			\brief fonction qui met à jour l'adhérent
			\param		disable_triggers	1=désactive le trigger UPDATE (quand appelé par creation)
			\return		int					<0 si ko, >0 si ok
	*/
	function update($disable_trigger=0)
	{
		global $conf,$langs,$user;

		dolibarr_syslog("Adherent.class.php::update $disable_trigger");

		// Verification parametres
		if ($conf->global->ADHERENT_MAIL_REQUIRED && ! ValidEMail($this->email))
		{
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql .= " prenom = '".$this->prenom ."'";
		$sql .= ",nom='"    .$this->nom."'";
		$sql .= ",societe='".$this->societe."'";
		$sql .= ",adresse='".$this->adresse."'";
		$sql .= ",cp='"     .$this->cp."'";
		$sql .= ",ville='"  .$this->ville."'";
		$sql .= ",pays='"   .$this->pays_code."'";
		$sql .= ",note='"   .$this->commentaire."'";
		$sql .= ",email='"  .$this->email."'";
		$sql .= ",login='"  .$this->login."'";
		$sql .= ",pass='"   .$this->pass."'";
		$sql .= ",naiss="   .$this->naiss?"'".$this->naiss."'":"null";
		$sql .= ",photo="   .$this->photo?"'".$this->photo."'":"null";
		$sql .= ",public='" .$this->public."'";
		$sql .= ",statut="  .$this->statut;
		$sql .= ",fk_adherent_type=".$this->typeid;
		$sql .= ",morphy='".$this->morphy."'";
		$sql .= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}

		if (sizeof($this->array_options) > 0)
		{
			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options WHERE adhid = ".$this->id;
			$this->db->query($sql_del);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_options (adhid";
			foreach($this->array_options as $key => $value)
			{
				// recupere le nom de l'attribut
				$attr=substr($key,8);
				$sql.=",$attr";
			}
			$sql .= ") VALUES ($this->id";
			foreach($this->array_options as $key => $value)
			{
				$sql.=",'".$this->array_options[$key]."'";
			}
			$sql.=");";

			$result = $this->db->query($sql);
			if (! $result)
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
		}

		if (! $disable_trigger)
		{
	        // Appel des triggers
	        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	        $interface=new Interfaces($this->db);
	        $result=$interface->run_triggers('MEMBER_MODIFY',$this,$user,$langs,$conf);
	        // Fin appel triggers
		}

		$this->db->commit();

		return 1;
	}


/**
		\brief fonction qui supprime l'adhérent et les données associées
		\param	rowid
*/

  function delete($rowid)

  {
    $result = 0;
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE fk_adherent = $rowid";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->affected_rows() )
		  {
		    $result = 1;
		  }
	      }
	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options WHERE adhid = $rowid";
	    if ( $this->db->query( $sql) )
	      {
		if ( $this->db->affected_rows() )
		  {
		    $result = 1;
		  }
	      }
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
      }

    return $result;

  }

/**
		\brief      Fonction qui récupére l'adhérent en donnant son login
		\param	    login		login de l'adhérent
*/

	function fetch_login($login)
  {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent WHERE login='$login' LIMIT 1";

    $result=$this->db->query( $sql);

    if ($result)
    {
        if ($this->db->num_rows())
        {
            $obj = $this->db->fetch_object($result);
            $this->fetch($obj->rowid);
        }
    }
    else
    {
        dolibarr_print_error($this->db);
    }
  }


    /**
    		\brief fonction qui récupére l'adhérent en donnant son rowid
    		\param	rowid
    */
    function fetch($rowid)
    {
        global $langs;

        $sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, d.statut, d.public, d.adresse, d.cp, d.ville, d.note, d.email, d.login, d.pass, d.naiss, d.photo, d.fk_adherent_type, d.morphy,";
        $sql.= " ".$this->db->pdate("d.datefin")." as datefin,";
        $sql.= " d.pays, p.rowid as pays_id, p.code as pays_code, p.libelle as pays_lib,";
        $sql.= " t.libelle as type, t.cotisation as cotisation";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
        $sql.= " WHERE d.rowid = ".$rowid." AND d.fk_adherent_type = t.rowid";

        $result=$this->db->query( $sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id             = $obj->rowid;
                $this->statut         = $obj->statut;
                $this->public         = $obj->public;
                $this->date           = $obj->datedon;
                $this->prenom         = $obj->prenom;
                $this->nom            = $obj->nom;
                $this->fullname       = trim($obj->nom.' '.$obj->prenom);
                $this->societe        = $obj->societe;
                $this->adresse        = $obj->adresse;
                $this->cp             = $obj->cp;
                $this->ville          = $obj->ville;
                $this->pays_id        = $obj->pays_id;
                $this->pays_code      = $obj->pays_code;
                if ($langs->trans("Country".$obj->pays_code) != "Country".$obj->pays_code) $this->pays = $langs->trans("Country".$obj->pays_code);
                elseif ($obj->pays_lib) $this->pays=$obj->pays_lib;
                else $this->pays=$obj->pays;
                $this->email          = $obj->email;
                $this->login          = $obj->login;
                $this->pass           = $obj->pass;
                $this->naiss          = $obj->naiss;
                $this->photo          = $obj->photo;
                $this->datefin        = $obj->datefin;
                $this->commentaire    = $obj->note;
                $this->morphy         = $obj->morphy;

                $this->typeid         = $obj->fk_adherent_type;
                $this->type           = $obj->type;
                $this->need_subscription = ($obj->cotisation=='yes'?1:0);
            }
        }
        else
        {
            dolibarr_print_error($this->db);
        }

    }


/**
		\brief      Fonction qui récupére les données optionelles de l'adhérent
		\param	    rowid
*/

	function fetch_optionals($rowid)
  {
    $tab=array();
    $sql = "SELECT *";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent_options";
    $sql .= " WHERE adhid=$rowid";

    $result=$this->db->query( $sql);

    if ($result)
    {
    	if ($this->db->num_rows())
    	{
    	  $tab = $this->db->fetch_array($result);

    	  foreach ($tab as $key => $value)
    	  {
    	    if ($key != 'optid' && $key != 'tms' && $key != 'adhid')
    	    {
    	      // we can add this attribute to adherent object
    	      $this->array_options["options_$key"]=$value;
    	    }
    	  }
    	}
    }
    else
    {
      dolibarr_print_error($this->db);
    }

  }

  /*
   * fetch optional attribute name
   */
  function fetch_name_optionals()
  {
    $array_name_options=array();
    $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."adherent_options";

    $result=$this->db->query( $sql);

    if ($result)
    {
        if ($this->db->num_rows())
        {
            //$array_name_options[]=$tab->Field;
            while ($tab = $this->db->fetch_object($result))
            {
                if ($tab->Field != 'optid' && $tab->Field != 'tms' && $tab->Field != 'adhid')
                {
                    // we can add this attribute to adherent object
                    $array_name_options[]=$tab->Field;
                }
            }
            return $array_name_options;
        }
        else
        {
            return array();
        }
    }
    else
    {
        dolibarr_print_error($this->db);
        return array() ;
    }

  }

    /**
    		\brief      Fonction qui insère la cotisation dans la base de données
    					et eventuellement liens dans banques, mailman, etc...
    		\param	    date        Date cotisation
    		\param	    montant     Montant cotisation
            \return     int         rowid de l'entrée ajoutée, <0 si erreur
    */
    function cotisation($date, $montant, $accountid, $operation, $label, $num_chq)
    {
        global $conf,$langs,$user;

        dolibarr_syslog("Adherent.class.php::cotisation $date, $montant, $accountid, $operation, $label, $num_chq");
        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."cotisation (fk_adherent, datec, dateadh, cotisation)";
        $sql .= " VALUES ($this->id, now(), ".$this->db->idate($date).", $montant)";

        $result=$this->db->query($sql);
        if ($result)
        {
            $rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."cotisation");
			// datefin = date + 1 an
            $datefin = mktime(12, 0 , 0, strftime("%m",$date), strftime("%d",$date),
                                            strftime("%Y",$date)+1) - (24 * 3600);

            $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET datefin = ".$this->db->idate($datefin);
            $sql.= " WHERE rowid =". $this->id;
            $resql=$this->db->query( $sql);
            if ($resql)
            {

		        // Rajout du nouveau cotisant dans les listes qui vont bien
		        if ($conf->global->ADHERENT_MAILMAN_LISTS_COTISANT && ! $adh->datefin)
		        {
		            $result=$adh->add_to_mailman($conf->global->ADHERENT_MAILMAN_LISTS_COTISANT);
		        }

	            // Insertion dans la gestion bancaire si configuré pour
	            if ($conf->global->ADHERENT_BANK_USE && $accountid)
	            {
	                $acct=new Account($this->db,$accountid);

	                $dateop=strftime("%Y%m%d",time());
	                $amount=$cotisation;

	                $insertid=$acct->addline($dateop, $operation, $label, $amount, $num_chq, '', $user);
	                if ($insertid > 0)
	                {
	        			$inserturlid=$acct->add_url_line($insertid, $adh->id, DOL_URL_ROOT.'/adherents/fiche.php?rowid=', $adh->getFullname(), 'member');
	                    if ($inserturlid > 0)
	                    {
	                        // Met a jour la table cotisation
	                        $sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=".$insertid." WHERE rowid=".$crowid;
	                        $resql = $this->db->query($sql);
	                        if (! $resql)
	                        {
				                $this->error=$this->db->error();
				                $this->db->rollback();
				                return -5;
	                        }
	                    }
	                    else
	                    {
			                $this->error=$acct->error();
			                $this->db->rollback();
			                return -4;
	                    }
	                }
	                else
	                {
		                $this->error=$this->db->error();
		                $this->db->rollback();
		                return -3;
	                }
	            }

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('MEMBER_SUBSCRIPTION',$this,$user,$langs,$conf);
                // Fin appel triggers

               	$this->db->commit();
               	return $rowid;
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                return -2;
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
	 *		\brief 		Fonction qui vérifie que l'utilisateur est valide
	 *		\param		userid		userid adhérent à valider
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function validate($userid)
	{
		global $user,$langs,$conf;

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " statut=1, datevalid = now(),";
		$sql.= " fk_user_valid=".$userid;
		$sql.= " WHERE rowid = $this->id";

		$result = $this->db->query($sql);
		if ($result)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MEMBER_VALIDATE',$this,$user,$langs,$conf);
			// Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		\brief 		Fonction qui résilie un adhérent
	 *		\param		userid		userid adhérent à résilier
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function resiliate($userid)
	{
		global $user,$langs,$conf;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET ";
		$sql .= "statut=0";
		$sql .= ",fk_user_valid=".$userid;
		$sql .= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
	        // Appel des triggers
	        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	        $interface=new Interfaces($this->db);
	        $result=$interface->run_triggers('MEMBER_RESILIATE',$this,$user,$langs,$conf);
	        // Fin appel triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
			\brief fonction qui ajoute l'adhérent au abonnements automatiques
			\param	adht
			\remarks	mailing-list, spip, glasnost, etc...
	*/
	function add_to_abo($adht)
	{
		$err=0;
		// mailman
		if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
		{
			$result=$this->add_to_mailman();
			if ($result < 0)
			{
				$err+=1;
			}
		}
	
		if ($adht->vote == 'yes' &&
		defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
		defined("ADHERENT_USE_GLASNOST_AUTO") && ADHERENT_USE_GLASNOST_AUTO ==1
		)
		{
			if(!$this->add_to_glasnost())
			{
				$err+=1;
			}
		}
		if (
		defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
		)
		{
			if(!$this->add_to_spip())
			{
				$err+=1;
			}
		}
		if ($err>0)
		{
			// error
			return 0;
		}
		else
		{
			return 1;
		}
	}


	/**
			\brief      fonction qui supprime l'adhérent des abonnements automatiques
			\param	    adht
			\remarks	mailing-list, spip, glasnost, etc...
	*/
	function del_to_abo($adht)
	{
		$err=0;
		// mailman
		if (defined("ADHERENT_USE_MAILMAN") && ADHERENT_USE_MAILMAN == 1)
		{
			if(!$this->del_to_mailman()){
				$err+=1;
			}
		}
		if ($adht->vote == 'yes' &&
		defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
		defined("ADHERENT_USE_GLASNOST_AUTO") && ADHERENT_USE_GLASNOST_AUTO ==1
		)
		{
			if(!$this->del_to_glasnost()){
				$err+=1;
			}
		}
		if (
		defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
		defined("ADHERENT_USE_SPIP_AUTO") && ADHERENT_USE_SPIP_AUTO ==1
		)
		{
			if(!$this->del_to_spip()){
				$err+=1;
			}
		}
		if ($err>0){
			// error
			return 0;
			}else{
				return 1;
			}
		}


	/**
			\brief fonction qui donne les droits rédacteurs dans spip
	*/
	function add_to_spip()
    {
      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
	  defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
	  defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
	  defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
	  ){
	$mdpass=md5($this->pass);
	$htpass=crypt($this->pass,makesalt());
	$query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"".$this->prenom." ".$this->nom."\",\"".$this->email."\",\"".$this->login."\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"1comite\")";
	//      $mydb=new Db('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
      $mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
      $result = $mydb->query($query);

      if ($result)
	{
	  $mydb->close();
	  return 1;
	}
      else
	{
	  $this->error=$mydb->error();
	  return 0;
	}
      }
    }

/**
		\brief fonction qui enlève les droits rédacteurs dans spip
*/

	function del_to_spip()
    {
      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
	  defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
	  defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
	  defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
	  defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
	  ){
	$query = "DELETE FROM spip_auteurs WHERE login='".$this->login."'";
	$mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);
	$result = $mydb->query($query);

	if ($result)
	  {
	    $mydb->close();
	    return 1;
	  }
	else
	  {
	    $this->error=$mydb->error();
	    return 0;
	  }
      }
    }

/**
		\brief      Fonction qui dit si cet utilisateur est un rédacteur existant dans spip
		\return     int     1=existe, 0=n'existe pas, -1=erreur
*/

	function is_in_spip()
    {
        if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
            defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
            defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
            defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
            defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != '')
        {

            $query = "SELECT login FROM spip_auteurs WHERE login='".$this->login."'";
            $mydb=new DoliDb('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB);

            if ($mydb->ok) {

                $result = $mydb->query($query);

                if ($result)
                {
                    if ($mydb->num_rows())
                    {
                        # nous avons au moins une reponse
                        $mydb->close();
                        return 1;
                    }
                    else
                    {
                    # nous n'avons pas de reponse => n'existe pas
                    $mydb->close();
                    return 0;
                    }
                }
                else
                {
                    # error
                    $this->error=$mydb->error();
                    return -1;
                }
            } else {
                $this->error="Echec de connexion avec les identifiants ".ADHERENT_SPIP_SERVEUR." ".ADHERENT_SPIP_USER." ".ADHERENT_SPIP_PASS." ".ADHERENT_SPIP_DB;
                return -1;
            }
        }
    }


/**
		\brief      Fonction qui ajoute l'utilisateur dans glasnost
*/

	function add_to_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  $this->error=$response['faultString'];
	  return 0;
	}

	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('addObject'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    array(
							  '__thingCategory__'=>'object',
							  '__thingName__'=>  'Person',
							  'firstName'=>$this->prenom,
							  'lastName'=>$this->nom,
							  'login'=>$this->login,
							  'email'=>$this->email
							  )
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response[0];
	}else{
	  $this->error=$response['faultString'];
	  return 0;
	}
	return 1;
      }else{
	$this->error="Constantes de connection non definies";
	return 0;
      }
    }

/**
		\brief fonction qui enlève l'utilisateur de glasnost
*/

	function del_to_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  return 0;
	}
	// recuperation du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('getObjectByLogin'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $this->login
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response['id'];
	}else{
	  $this->error=$response['faultString'];
	  return 0;
	}
	if (defined('ADHERENT_GLASNOST_DEFAULT_GROUPID') && ADHERENT_GLASNOST_DEFAULT_GROUPID != ''){
	  // recuperation des personne de ce groupe
	  list($success,$response)=
	    XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			   'callGateway',
			   array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/groups"),
				 XMLRPC_prepare('getObject'),
				 XMLRPC_prepare(array(
						      "glasnost://".ADHERENT_GLASNOST_SERVEUR."/groups",
						      "$applicationtoken",
						      $usertoken,
						      ADHERENT_GLASNOST_DEFAULT_GROUPID
						      )
						)
				 )
			   );
	  if ($success){
	    $groupids=$response['membersSet'];
	  }else{
	    $this->error=$response['faultString'];
	    return 0;
	  }
	  // TODO faire la verification que le user n'est pas dans ce
	  // groupe par defaut. si il y ai il faut l'effacer et
	  // modifier le groupe
	}
	// suppression du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('deleteObject'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $personid
						    )
					      )
			       )
			 );
	if ($success){
	  return 1;
	}else{
	  $this->error=$response['faultString'];
	  return 0;
	}
      }else{
	$this->error="Constantes de connection non definies";
	return 0;
      }
    }

/**
		\brief fonction qui vérifie si l'utilisateur est dans glasnost
*/

  function is_in_glasnost()
    {
      if (defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1 &&
	  defined('ADHERENT_GLASNOST_SERVEUR') && ADHERENT_GLASNOST_SERVEUR != '' &&
	  defined('ADHERENT_GLASNOST_USER') && ADHERENT_GLASNOST_USER != '' &&
	  defined('ADHERENT_GLASNOST_PASS') && ADHERENT_GLASNOST_PASS != ''
	  ){
	// application token is not useful here
	$applicationtoken='';
	list($success, $response) =
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication"),
			       XMLRPC_prepare('getUserIdAndToken'),
			       XMLRPC_prepare(array("glasnost://".ADHERENT_GLASNOST_SERVEUR."/authentication","$applicationtoken",ADHERENT_GLASNOST_USER,ADHERENT_GLASNOST_PASS))
			       )
			 );
	if ($success){
	  $userid=$response[0];
	  $usertoken=$response[1];
	}else{
	  return 0;
	}
	// recuperation du personID
	list($success,$response)=
	  XMLRPC_request(ADHERENT_GLASNOST_SERVEUR.':8001',
			 '/RPC2',
			 'callGateway',
			 array(XMLRPC_prepare("glasnost://".ADHERENT_GLASNOST_SERVEUR."/people"),
			       XMLRPC_prepare('getObjectByLogin'),
			       XMLRPC_prepare(array(
						    "glasnost://".ADHERENT_GLASNOST_SERVEUR."/people",
						    "$applicationtoken",
						    $usertoken,
						    $this->login
						    )
					      )
			       )
			 );
	if ($success){
	  $personid=$response['id'];
	  return 1;
	}else{
	  $this->error=$response['faultString'];
	  return 0;
	}
      }else{
	$this->error="Constantes de connection non definies";
	return 0;
      }
    }


	/**
			\brief 		Fonction qui rajoute l'utilisateur dans mailman
			\return		int		<0 si KO, >0 si OK
	*/
	function add_to_mailman($listes='')
	{
		global $conf,$langs;
		
		if (! function_exists("curl_init"))
		{
			$this->error=$langs->trans("ErrorFunctionNotAvailableInPHP","curl_init");
			return -1;	
		}
		
		if (defined("ADHERENT_MAILMAN_URL") && ADHERENT_MAILMAN_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
		{
			if ($listes =='')
			{
				$lists=explode(',',ADHERENT_MAILMAN_LISTS);
			}
			else
			{
					$lists=explode(',',$listes);
			}
			foreach ($lists as $list)
			{
				// on remplace dans l'url le nom de la liste ainsi
				// que l'email et le mot de passe
				$patterns = array (
				'/%LISTE%/',
				'/%EMAIL%/',
				'/%PASS%/',
				'/%ADMINPW%/',
				'/%SERVER%/'
				);
				$replace = array (
				$list,
				$this->email,
				$this->pass,
				ADHERENT_MAILMAN_ADMINPW,
				ADHERENT_MAILMAN_SERVER
				);
				$curl_url = preg_replace ($patterns, $replace, ADHERENT_MAILMAN_URL);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"$curl_url");
				//curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				//curl_setopt($ch, CURLOPT_POST, 0);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
				//--- Start buffering
				//ob_start();
				$result=curl_exec ($ch);
				dolibarr_syslog($result);
				//--- End buffering and clean output
				//ob_end_clean();
				if (curl_error($ch) > 0)
				{
					// error
					return 0;
				}
				curl_close ($ch);

			}
			return 1;
		}
		else
		{
			$this->error="Constantes de connection non definies";
			return -1;
		}
	}

	/**
		\brief fonction qui désinscrit l'utilisateur de toutes les mailing list mailman
		\ remarks	utilie lors de la résiliation d'adhésion
	*/
	function del_to_mailman($listes='')
	{
    if (defined("ADHERENT_MAILMAN_UNSUB_URL") && ADHERENT_MAILMAN_UNSUB_URL != '' && defined("ADHERENT_MAILMAN_LISTS") && ADHERENT_MAILMAN_LISTS != '')
      {
	if ($listes==''){
	  $lists=explode(',',ADHERENT_MAILMAN_LISTS);
	  if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT !=''){
	    $lists=array_merge ($lists,explode(',',ADHERENT_MAILMAN_LISTS_COTISANT));
	  }
	}else{
	  $lists=explode(',',$listes);
	}
	foreach ($lists as $list)
	  {
	    // on remplace dans l'url le nom de la liste ainsi
	    // que l'email et le mot de passe
	    $patterns = array (
			       '/%LISTE%/',
			       '/%EMAIL%/',
			       '/%PASS%/',
			       '/%ADMINPW%/',
			       '/%SERVER%/'
			       );
	    $replace = array (
			      $list,
			      $this->email,
			      $this->pass,
			      ADHERENT_MAILMAN_ADMINPW,
			      ADHERENT_MAILMAN_SERVER
			      );
	    $curl_url = preg_replace ($patterns, $replace, ADHERENT_MAILMAN_UNSUB_URL);

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL,"$curl_url");
	    //curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    //curl_setopt($ch, CURLOPT_POST, 0);
	    //curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
	    //--- Start buffering
	    //ob_start();
	    $result=curl_exec ($ch);
	    dolibarr_syslog($result);
	    //--- End buffering and clean output
	    //ob_end_clean();
	    if (curl_error($ch) > 0)
	      {
		// error
		return 0;
	      }
	    curl_close ($ch);

	  }
	return 1;
      }
    else
      {
	$this->error="Constantes de connection non definies";
	return 0;
      }
  }

	/**
	 *    \brief      Retourne le nom complet de l'adhérent
	 *    \return     string      	Nom complet
	 */
    function getFullname()
    {
        if ($this->nom && $this->prenom) return $this->nom.' '.$this->prenom;
        if ($this->nom)    return $this->nom;
        if ($this->prenom) return $this->prenom;
        return '';
    }

	/**
	 *    	\brief      Retourne le libellé du statut d'un adhérent (brouillon, validé, résilié)
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
	 */
    function getLibStatut($mode=0)
    {
		return $this->LibStatut($this->statut,$this->need_subscription,$this->datefin,$mode);
    }

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
 	 *    	\param      statut      id statut
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
 	 *    	\return     string      Libellé
 	 */
    function LibStatut($statut,$need_subscription,$date_end_subscription,$mode=0)
    {
        global $langs;
        $langs->load("members");
		if ($mode == 0)
		{
	        if ($statut == -1) return $langs->trans("MemberStatusDraft");
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return $langs->trans("MemberStatusActive");
	        	elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate");
	        	else                                     return $langs->trans("MemberStatusPayed");
	        }
	        if ($statut == 0)  return $langs->trans("MemberStatusResiliated");
		}
		if ($mode == 1)
		{
	        if ($statut == -1) return $langs->trans("MemberStatusDraft");
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return $langs->trans("MemberStatusActiveShort");
	        	elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLateShort");
	        	else                                     return $langs->trans("MemberStatusPayedShort");
	        }
	        if ($statut == 0)  return $langs->trans("MemberStatusResiliated");
		}
		if ($mode == 2)
		{
	        if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraft");
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActiveShort");
	        	elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLateShort");
	        	else                                     return img_picto($langs->trans('MemberStatusPayed'),'statut4').' '.$langs->trans("MemberStatusPayedShort");
	        }
	        if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliated");
		}
		if ($mode == 3)
		{
	        if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0');
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1');
	        	elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
	        	else                                     return img_picto($langs->trans('MemberStatusPayed'),'statut4');
	        }
	        if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
		if ($mode == 4)
		{
	        if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraft");
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActive");
	        	elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLate");
	        	else                                     return img_picto($langs->trans('MemberStatusPayed'),'statut4').' '.$langs->trans("MemberStatusPayed");
	        }
	        if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliated");
		}
        if ($mode == 5)
        {
	        if ($statut == -1) return $langs->trans("MemberStatusDraft").' '.img_picto($langs->trans('MemberStatusDraft'),'statut0');
	        if ($statut == 1)
	        {
	        	if (! $date_end_subscription)            return $langs->trans("MemberStatusActive").' '.img_picto($langs->trans('MemberStatusActive'),'statut1');
	        	elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate").' '.img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
	        	else                                     return $langs->trans("MemberStatusPayed").' '.img_picto($langs->trans('MemberStatusPayed'),'statut4');
	        }
	        if ($statut == 0)  return $langs->trans("MemberStatusResiliated").' '.img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
    }


    /**
     *      \brief      Charge indicateurs this->nb de tableau de bord
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        global $conf;

        $this->nb=array();

        $sql = "SELECT count(a.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.statut > 0";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["members"]=$obj->nb;
            }
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }

    }

    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user)
    {
        global $conf;

        if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

        $this->nbtodo=$this->nbtodolate=0;
        $sql = "SELECT a.rowid,".$this->db->pdate("a.datefin")." as datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.statut=1";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->datefin < (time() - $conf->adherent->cotisation->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }

	/**
	 *		\brief		Initialise le membre avec valeurs fictives aléatoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Initialise paramètres
		$this->id=0;
		$this->specimen=1;
		$this->fullname = 'DOLIBARR SPECIMEN';
		$this->nom = 'DOLIBARR';
		$this->prenom = 'SPECIMEN';
		$this->fullname=trim($this->nom.' '.$this->prenom);
		$this->societe = 'Societe ABC';
		$this->adresse = '61 jump street';
		$this->cp = '75000';
		$this->ville = 'Paris';
		$this->pays_id = 1;
		$this->pays_code = 'FR';
		$this->pays = 'France';
		$this->moraphy = 1;
		$this->email = 'specimen@specimen.com';
		$this->public=1;
		$this->commentaire='No comment';
		$this->statut=1;
		$this->login='dolibspec';
		$this->pass='dolibspec';
		$this->naiss=time();
		$this->photo='';

		$this->typeid=1;				// Id type adherent
		$this->type='Type adherent';	// Libellé type adherent
		$this->need_subscription=0;
	}
}
?>
