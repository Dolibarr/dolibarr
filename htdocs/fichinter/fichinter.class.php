<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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

/**	    \file       htdocs/fichinter/fichinter.class.php
		\ingroup    fucheinter
		\brief      Fichier de la classe des gestion des fiches interventions
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**	    \class      Ficheinter
		\brief      Classe des gestion des fiches interventions
*/
class Fichinter extends CommonObject
{
    var $db;
	  var $element='fichinter';

    var $id;
    
	  var $socid;		// Id client
	  var $client;		// Objet societe client (a charger par fetch_client)

    var $author;
    var $ref;
    var $date;
    var $date_delivery;
    var $duree;
    var $description;
    var $note_private;
    var $note_public;
    var $projet_id;
    var $modelpdf;


    /**
     *    \brief      Constructeur de la classe
     *    \param      DB            Handler acces base de donnees
     *    \param      socid			Id societe
     */
    function Fichinter($DB, $socid="")
    {
        global $langs;

        $this->db = $DB ;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=valide
        $this->statuts[0]=$langs->trans("Draft");
        $this->statuts[1]=$langs->trans("Validated");
        $this->statuts_short[0]=$langs->trans("Draft");
        $this->statuts_short[1]=$langs->trans("Validated");
    }


    /*
     *    	\brief      Cree une fiche intervention en base
     *		\return		int		<0 if KO, >0 if OK
     */
    function create()
	{
		dolibarr_syslog("Fichinter.class::create ref=".$this->ref);

		if (! is_numeric($this->duree)) { $this->duree = 0; }
		if ($this->socid <= 0) 
		{
			$this->error='ErrorBadParameterForFunc';
			dolibarr_syslog("Fichinter::create ".$this->error,LOG_ERR);
			return -1;
		}
		
		$this->db->begin();

		// on verifie si la ref n'est pas utilisee
		$soc = new Societe($this->db);
		$result=$soc->fetch($this->socid);
		$this->verifyNumRef($soc);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter (fk_soc, datei, datec, ref, fk_user_author, description, model_pdf";
		if ($this->projet_id) $sql.=  ", fk_projet";
		$sql.= ") ";
		$sql.= " VALUES (".$this->socid.",";
		$sql.= " ".$this->db->idate($this->date).",";
		$sql.= " now(), '".$this->ref."', ".$this->author;
		$sql.= ", '".addslashes($this->description)."', '".$this->modelpdf."'";
		if ($this->projet_id) $sql .= ", ".$this->projet_id;
		$sql.= ")";
		$sqlok = 0;

		dolibarr_syslog("Fichinter::create sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."fichinter");
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Fichinter::create ".$this->error,LOG_ERR);
			$this->db->rollback();
			return -1;
		}

	}

    /*
     *	\brief		Met a jour une intervention
     *	\return		int		<0 si ko, >0 si ok
     */
    function update($id)
    {
        if (! is_numeric($this->duree)) { $this->duree = 0; }
        if (! strlen($this->projet_id))
        {
            $this->projet_id = 0;
        }

        /*
         *  Insertion dans la base
         */
        $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET ";
        $sql .= " datei = ".$this->date;
        $sql .= ", description  = '".addslashes($this->description)."'";
        $sql .= ", duree = ".$this->duree;
        $sql .= ", fk_projet = ".$this->projet_id;
        $sql .= " WHERE rowid = ".$id;

		dolibarr_syslog("Fichinter::update sql=".$sql);
        if (! $this->db->query($sql))
        {
			$this->error=$this->db->error();
			dolibarr_syslog("Fichinter::update error ".$this->error,LOG_ERR);
			return -1;
        }

        return 1;
    }

    /**
     *		\brief		Charge en memoire la fiche intervention
     *		\param		rowid		Id de la fiche a charger
     *		\return		int			<0 si ko, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT ref, description, fk_soc, fk_statut,";
        $sql.= " ".$this->db->pdate(datei)." as di, duree, fk_projet, note_public, note_private, model_pdf";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
        $sql.= " WHERE rowid=".$rowid;

		dolibarr_syslog("Fichinter::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $rowid;
                $this->ref          = $obj->ref;
                $this->description  = $obj->description;
                $this->socid        = $obj->fk_soc;
                $this->statut       = $obj->fk_statut;
                $this->date         = $obj->di;
                $this->duree        = $obj->duree;
                $this->projetidp    = $obj->fk_projet;
                $this->note_public  = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->modelpdf     = $obj->model_pdf;
                
                if ($this->statut == 0) $this->brouillon = 1;
                
                $this->db->free($resql);
                return 1;
            }
        }
        else
        {
			$this->error=$this->db->error();
			dolibarr_syslog("Fichinter::update error ".$this->error,LOG_ERR);
            return -1;
        }
    }

    /**
     *		\brief		Valide une fiche intervention
     *		\param		user		User qui valide
     *		\return		int			<0 si ko, >0 si ok
     */
    function valid($user, $outputdir)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
        $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=".$user->id;
        $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

		dolibarr_syslog("Fichinter::valid sql=".$sql);
		$resql=$this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('FICHEINTER_VALIDATE',$this,$user,$langs,$conf);
            // Fin appel triggers

            return 1;
        }
        else
        {
			$this->error=$this->db->error();
			dolibarr_syslog("Fichinter::update error ".$this->error,LOG_ERR);
            return -1;
        }
    }

    /**
     *    \brief      Retourne le libelle du statut de l'intervantion
     *    \return     string      Libelle
     */
    function getLibStatut($mode=0)
    {
		return $this->LibStatut($this->statut,$mode);
    }

    /**
     *    \brief      Renvoi le libelle d'un statut donne
     *    \param      statut      id statut
     *    \return     string      Libelle
     */
    function LibStatut($statut,$mode=0)
    {
        if ($mode == 0)
        {
	        return $this->statuts[$statut];
		}
        if ($mode == 1)
        {
        	return $this->statuts_short[$statut];
        }
        if ($mode == 2)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0').' '.$this->statuts_short[$statut];
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut6').' '.$this->statuts_short[$statut];
        }
        if ($mode == 3)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0');
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut6');
        }
        if ($mode == 4)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0').' '.$this->statuts[$statut];
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut6').' '.$this->statuts[$statut];
        }
        if ($mode == 5)
        {
        	if ($statut==0) return $this->statuts_short[$statut].' '.img_picto($this->statuts_short[$statut],'statut0');
        	if ($statut==1) return $this->statuts_short[$statut].' '.img_picto($this->statuts_short[$statut],'statut6');
        }
    }

	/**
	*		\brief		Positionne modele derniere generation
	*		\param		user		Objet use qui modifie
	*		\param		modelpdf	Nom du modele
	*/
	function set_pdf_model($user, $modelpdf)
	{
		if ($user->rights->facture->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET model_pdf = '$modelpdf'";
			$sql .= " WHERE rowid = ".$this->id;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->modelpdf=$modelpdf;
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				return 0;
			}
		}
	}
	
 /**
   *      \brief      Verifie si la ref n'est pas deja utilisee
   *      \param	    soc  		            objet societe
   */
  function verifyNumRef($soc)
  {
  	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."fichinter";
  	$sql.= " WHERE ref = '".$this->ref."'";

  	$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num > 0)
			{
				$this->ref = $this->getNextNumRef($soc);
			}
		}
	}
  	
	
 /**
   *      \brief      Renvoie la reference de fiche intervention suivante non utilisee en fonction du module
   *                  de numerotation actif defini dans FICHEINTER_ADDON
   *      \param	    soc  		            objet societe
   *      \return     string              reference libre pour la fiche intervention
   */
  function getNextNumRef($soc)
  {
    global $db, $langs;
    $langs->load("interventions");

    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/fichinter/";

    if (defined("FICHEINTER_ADDON") && FICHEINTER_ADDON)
    {
    	$file = FICHEINTER_ADDON.".php";

	    // Chargement de la classe de numerotation
	    $classname = FICHEINTER_ADDON;
	    require_once($dir.$file);

	    $obj = new $classname();

	    $numref = "";
	    $numref = $obj->getNumRef($soc,$this);

	    if ( $numref != "")
	    {
	      return $numref;
	    }
	    else
	    {
	      dolibarr_print_error($db,"Fichinter::getNextNumRef ".$obj->error);
	      return "";
	    }
     }
     else
     {
	     print $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined");
	     return "";
     }
  }
  
  /**
 	 *    \brief      Mets a jour les commentaires publiques et prives
	 *    \param      note	Commentaire
	 *    \param      type  Type de note
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note($note,$type)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter';
		$sql.= " SET ".$type." = '".addslashes($note)."'";
		$sql.= " WHERE rowid =". $this->id;

		dolibarr_syslog("Fichinter::update_note type=".$type." sql=".$sql);

		if ($this->db->query($sql))
		{
			$this->$type = $type;
			return 1;
		}
		else
		{
      $this->error=$this->db->error();
			return -1;
		}
	}
	
	/**
   *      \brief      Information sur l'objet fiche intervention
   *      \param      id      id de la fiche d'intervention
   */
  function info($id)
  {
    $sql = "SELECT f.rowid, ";
    $sql.= $this->db->pdate("f.datec")." as datec, ".$this->db->pdate("f.date_valid")." as datev";
    $sql.= ", f.fk_user_author, f.fk_user_valid";
    $sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
    $sql.= " WHERE f.rowid = ".$id;

    $result = $this->db->query($sql);
    
    if ($result)
    {
    	if ($this->db->num_rows($result))
    	{
    		$obj = $this->db->fetch_object($result);

	      $this->id                = $obj->rowid;

	      $this->date_creation     = $obj->datec;
	      $this->date_validation   = $obj->datev;

	      $cuser = new User($this->db, $obj->fk_user_author);
	      $cuser->fetch();
	      $this->user_creation     = $cuser;

	      if ($obj->fk_user_valid)
	      {
		      $vuser = new User($this->db, $obj->fk_user_valid);
		      $vuser->fetch();
		      $this->user_validation     = $vuser;
	      }
	    }
	    $this->db->free($result);
	  }
    else
    {
    	dolibarr_print_error($this->db);
    }
  }
  
  /**
   *      \brief     Classe la fiche d'intervention dans un projet
   *      \param     project_id       Id du projet dans lequel classer la facture
   */
	function set_project($user, $project_id)
	{
		if ($user->rights->ficheinter->creer)
		{
			//verif que le projet et la societe concordent
			$sql = 'SELECT p.rowid, p.title FROM '.MAIN_DB_PREFIX.'projet as p WHERE p.fk_soc ='.$this->socid.' AND p.rowid='.$project_id;
			$sqlres = $this->db->query($sql);
			if ($sqlres)
			{
				$numprojet = $this->db->num_rows($sqlres);
				if ($numprojet > 0)
				{
					$this->projetidp=$project_id;
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter SET fk_projet = '.$project_id;
					$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';
					$this->db->query($sql);
				}
			}
			else
			{
	
				dolibarr_syslog("Fichinter::set_project Erreur SQL");
			}
		}
	}
	
/**
	*    \brief      Efface fiche intervention
	*    \param      user        Objet du user qui efface
	*/
	function delete($user)
	{
		global $conf;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE fk_fichinter = ".$this->id;
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinter WHERE rowid = ".$this->id;
			if ( $this->db->query($sql) )
			{
	
				// On efface le repertoire du pdf
				$fichinterref = sanitize_string($this->ref);
				if ($conf->fichinter->dir_output)
				{
					$dir = $conf->fichinter->dir_output . "/" . $fichinterref ;
					$file = $conf->fichinter->dir_output . "/" . $fichinterref . "/" . $fichinterref . ".pdf";
					if (file_exists($file))
					{
						fichinter_delete_preview($this->db, $this->id, $this->ref);
	
						if (!dol_delete_file($file))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (!dol_delete_dir($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}
	
				dolibarr_syslog("Suppression de la fiche intervention $this->id par $user->id");
				return 1;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}
	
/**
 *      \brief      Definit une date de livraison du bon d'intervention
 *      \param      user        		Objet utilisateur qui modifie
 *      \param      date_creation   date de livraison
 *      \return     int         		<0 si ko, >0 si ok
 */
 function set_date_delivery($user, $date_delivery)
 {
   if ($user->rights->ficheinter->creer)
   {
     $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
     $sql.= " SET datei = ".$this->db->idate($date_delivery);
     $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

     if ($this->db->query($sql))
     {
       $this->date_delivery = $date_delivery;
       return 1;
     }
     else
     {
       $this->error=$this->db->error();
       dolibarr_syslog("Fichinter::set_date_delivery Erreur SQL");
       return -1;
     }
   }
 }
 
/**
 *      \brief      Definit le label de l'intervention
 *      \param      user        		Objet utilisateur qui modifie
 *      \param      description     description
 *      \return     int         		<0 si ko, >0 si ok
 */
 function set_description($user, $description)
 {
   if ($user->rights->ficheinter->creer)
   {
     $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
     $sql.= " SET description = '".addslashes($description)."'";
     $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

     if ($this->db->query($sql))
     {
       $this->description = $description;
       return 1;
     }
     else
     {
       $this->error=$this->db->error();
       dolibarr_syslog("Fichinter::set_description Erreur SQL");
       return -1;
     }
   }
 }
 
 /**
   *  	\brief     	Ajout d'une ligne d'intervention, en base
	 * 		\param    	fichinterid      	  Id de la fiche d'intervention
	 * 		\param    	desc            	  Description de la ligne
	 *    \param      date_intervention   Date de l'intervention
	 *    \param      duration            Duree de l'intervention
   *    	\return    	int             	>0 si ok, <0 si ko
   */
    function addline($fichinterid, $desc, $date_intervention, $duration)
    {
    	dolibarr_syslog("Fichinter::Addline $fichinterid, $desc, $date_intervention, $duration");
    	
    	if ($this->statut == 0)
      {
      	$this->db->begin();
      	
      	// Insertion ligne
      	$ligne=new FichinterLigne($this->db);
      	
      	$ligne->fk_fichinter = $fichinterid;
      	$ligne->desc         = $desc;
      	$ligne->datei        = $date_intervention;
      	$ligne->duration     = $duration;
      	
      	$result=$ligne->insert();			
      	if ($result > 0)
      	{
			$this->db->commit();
			return 1;
        }
        else
        {
        	$this->error=$this->db->error();
	        dolibarr_syslog("Error sql=$sql, error=".$this->error);
					$this->db->rollback();
					return -1;
        }
      }
    }

	/**
	 *      \brief      Stocke un numero de rang pour toutes les lignes de
	 *                  detail d'une fiche d'intervention qui n'en ont pas.
	 */
	function line_order()
	{
		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' WHERE fk_fichinter='.$this->id;
		$sql .= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'fichinterdet';
			$sql .= ' WHERE fk_fichinter='.$this->id;
			$sql .= ' ORDER BY rang ASC, rowid ASC';
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$li[$i] = $row[0];
					$i++;
				}
			}
			for ($i = 0 ; $i < sizeof($li) ; $i++)
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinterdet SET rang = '.($i+1);
				$sql .= ' WHERE rowid = '.$li[$i];
				if (!$this->db->query($sql) )
				{
					dolibarr_syslog($this->db->error());
				}
			}
		}
	}
 
	function line_up($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinterdet SET rang = '.$rang ;
			$sql .= ' WHERE fk_fichinter  = '.$this->id;
			$sql .= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinterdet SET rang  = '.($rang - 1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

	function line_down($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la propale */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' WHERE fk_fichinter ='.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinterdet SET rang = '.$rang;
			$sql .= ' WHERE fk_fichinter  = '.$this->id;
			$sql .= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinterdet SET rang = '.($rang+1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}
	
	/**
	*		\brief		Initialise la fiche intervention avec valeurs fictives aleatoire
	*					Sert a generer une fiche intervention pour l'aperu des modeles ou demo
	*/
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de societe socids
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

		// Charge tableau des produits prodids
		$prodids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date = time();
		$this->date_lim_reglement=$this->date+3600*24*30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->note_public='SPECIMEN';
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new FichinterLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=1;
			$ligne->subprice=100;
			$ligne->price=100;
			$ligne->tva_tx=19.6;
			$prodid = rand(1, $num_prods);
			$ligne->produit_id=$prodids[$prodid];
			$this->lignes[$xnbp]=$ligne;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}
}


/**
		\class      FichinterLigne
		\brief      Classe permettant la gestion des lignes d'intervention
*/

class FichinterLigne
{
	var $db;
	var $error;

  // From llx_fichinterdet
	var $rowid;
	var $fk_fichinter;
  var $desc;          	// Description ligne
  var $datei;           // Date intervention
  var $duration;        // Duree de l'intervention
  var $rang = 0;


	/**
	 *      \brief     Constructeur d'objets ligne d'intervention
	 *      \param     DB      handler d'acces base de donnee
	 */
	function FichinterLigne($DB)
	{
		$this->db= $DB;
	}

	/**
	 *      \brief     Recupere l'objet ligne d'intervention
	 *      \param     rowid           id de la ligne
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang,';
		$sql.= ' '.$this->db->pdate('ft.date').' as datei';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql.= ' WHERE ft.rowid = '.$rowid;

		dolibarr_syslog("FichinterLigne::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          	= $objp->rowid;
			$this->fk_fichinter   	= $objp->fk_fichinter;
			$this->datei			= $objp->datei;
			$this->desc           	= $objp->description;
			$this->duration       	= $objp->duree;
			$this->rang           	= $objp->rang;

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dolibarr_print_error($this->db,$this->error);
			return -1;
		}
	}
	
	/**
	 *      \brief     	Insere l'objet ligne d'intervention en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function insert()
	{
		dolibarr_syslog("FichinterLigne::insert rang=".$this->rang);
		$this->db->begin();
		
		$rangToUse=$this->rang;
		if ($rangToUse == -1)
		{
			// Recupere rang max de la ligne d'intervention dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'fichinterdet';
			$sql.= ' WHERE fk_fichinter ='.$this->fk_fichinter;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$rangToUse = $obj->max + 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}	

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fichinterdet';
		$sql.= ' (fk_fichinter, description, date, duree, rang)';
		$sql.= " VALUES (".$this->fk_fichinter.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " ".$this->datei.",";
		$sql.= " ".$this->duration.",";
		$sql.= ' '.$rangToUse;
		$sql.= ')';

		dolibarr_syslog("FichinterLigne::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$result=$this->update_total();
			if ($result > 0)
			{
				$this->rang=$rangToUse;
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dolibarr_syslog("FichinterLigne::insert Error ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}
	
	
	/**
	 *      \brief     	Mise a jour de l'objet ligne d'intervention en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinterdet SET";
		$sql.= " description='".addslashes($this->desc)."'";
		$sql.= ",date=".$this->db->idate($this->datei);
		$sql.= ",duree=".$this->duration;
		$sql.= ",rang='".$this->rang."'";
		$sql.= " WHERE rowid = ".$this->rowid;

		dolibarr_syslog("FichinterLigne::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$result=$this->update_total();
			if ($result > 0)
			{
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("FichinterLigne::update Error ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}
	
	/**
	 *      \brief     	Mise a jour duree total dans table llx_fichinter
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		$sql = "SELECT SUM(duree) as total_duration";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql.= " WHERE fk_fichinter=".$this->fk_fichinter;

		dolibarr_syslog("FichinterLigne::update_total sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$total_duration=0;
			if ($obj) $total_duration = $obj->total_duration;
			
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql.= " SET duree = ".$total_duration;
			$sql.= " WHERE rowid = ".$this->fk_fichinter;

			dolibarr_syslog("FichinterLigne::update_total sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("FichinterLigne::update_total Error ".$this->error);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("FichinterLigne::update Error ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}
	
	  /**
     *      \brief      Supprime une ligne d'intervention
     *      \return     int         >0 si ok, <0 si ko
     */
    function delete_line()
    {
    	if ($this->statut == 0)
      {
      	dolibarr_syslog("FichinterLigne::delete_line lineid=".$this->rowid);
        $this->db->begin();
           
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE rowid = ".$this->rowid;
        $resql = $this->db->query($sql);
        dolibarr_syslog("FichinterLigne::delete_line sql=".$sql);

        if ($resql)
        {
        	$result = $this->update_total();
          if ($result > 0)
          {
          	$this->db->commit();
            return $result;
          }
          else
          {
          	$this->db->rollback();
            return -1;
          }
        }
        else
        {
        	$this->error=$this->db->error()." sql=".$sql;
        	dolibarr_syslog("FichinterLigne::delete_line Error ".$this->error);
        	$this->db->rollback();
        	return -1;
        }
      }
      else
      {
      	return -2;
      }
    }
	
}

  
?>