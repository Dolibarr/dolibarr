<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
	  var $client;		// Objet societe client (à charger par fetch_client)

    var $author;
    var $ref;
    var $date;
    var $duree;
    var $description;
    var $note_private;
    var $note_public;
    var $projet_id;


    /**
     *    \brief      Constructeur de la classe
     *    \param      DB            Handler accès base de données
     *    \param      socid			Id societe
     */
    function Fichinter($DB, $socid="")
    {
        global $langs;

        $this->db = $DB ;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=validé
        $this->statuts[0]=$langs->trans("Draft");
        $this->statuts[1]=$langs->trans("Validated");
        $this->statuts_short[0]=$langs->trans("Draft");
        $this->statuts_short[1]=$langs->trans("Validated");
    }


    /*
     *    \brief      Crée une fiche intervention en base
     *
     */
    function create()
    {
    	if (! is_numeric($this->duree)) { $this->duree = 0; }

		  // on vérifie si la ref n'est pas utilisée
		  $soc = new Societe($this->db);
	    $soc->fetch($this->socid);
	    $this->verifyNumRef($soc);
	    
	    dolibarr_syslog("Fichinter.class::create ref=".$this->ref);
		  
		  $this->db->begin();
		
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter (fk_soc, datei, datec, ref, fk_user_author, description, duree";
        if ($this->projet_id) {
            $sql .=  ",fk_projet";
        }
        $sql .= ") ";
        $sql .= " VALUES ($this->socid, $this->date, now(), '$this->ref', $this->author, '".addslashes($this->description)."', $this->duree";
        if ($this->projet_id) {
            $sql .= ", ".$this->projet_id;
        }
        $sql .= ")";
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
			dolibarr_syslog("Fichinter::create ".$this->error);
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
        $sql .= " WHERE rowid = $id";

        if (! $this->db->query($sql) )
        {
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
        }

        return 1;
    }

    /**
     *		\brief		Charge en mémoire la fiche intervention
     *		\param		rowid		Id de la fiche à charger
     *		\return		int			<0 si ko, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT ref, description, fk_soc, fk_statut, duree";
        $sql.= ", ".$this->db->pdate(datei)." as di, fk_projet, note_public, note_private";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
        $sql.= " WHERE rowid=".$rowid;

		    dolibarr_syslog("Fichinter.class::fetch rowid=$rowid sql=$sql");

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $rowid;
                $this->date         = $obj->di;
                $this->duree        = $obj->duree;
                $this->ref          = $obj->ref;
                $this->description  = $obj->description;
                $this->socid        = $obj->fk_soc;
                $this->projet_id    = $obj->fk_projet;
                $this->statut       = $obj->fk_statut;
                $this->note_public  = $obj->note_public;
                $this->note_private = $obj->note_private;
                
                $this->db->free($resql);
                return 1;
            }
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
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
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
    }

    /**
     *    \brief      Retourne le libellé du statut de l'intervantion
     *    \return     string      Libellé
     */
    function getLibStatut($mode=0)
    {
		return $this->LibStatut($this->statut,$mode);
    }

    /**
     *    \brief      Renvoi le libellé d'un statut donné
     *    \param      statut      id statut
     *    \return     string      Libellé
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
   *      \brief      Vérifie si la ref n'est pas déjà utilisée
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
   *      \brief      Renvoie la référence de fiche intervention suivante non utilisée en fonction du module
   *                  de numérotation actif défini dans FICHEINTER_ADDON
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

	    // Chargement de la classe de numérotation
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
 	 *    \brief      Mets à jour les commentaires publiques et privés
	 *    \param      note	Commentaire
	 *    \param      type  Type de note
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note($note,$type)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter';
		$sql.= " SET ".$type." = '".addslashes($note)."'";
		$sql.= " WHERE rowid =". $this->id;

		dolibarr_syslog("Fichinter.class::update_note type=".$type." sql=".$sql);

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
}  
?>