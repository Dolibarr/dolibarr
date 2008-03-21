<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdocs/don.class.php
		\ingroup    don
		\brief      Fichier de la classe des dons
		\version    $Revision$
*/


/**
        \class      Don
		\brief      Classe permettant la gestion des dons
*/

class Don
{
	var $db;
	var $error;
	var $element='don';
	var $table_element='don';

    var $id;
    var $db;
    var $date;
    var $amount;
    var $prenom;
    var $nom;
    var $societe;
    var $adresse;
    var $cp;
    var $ville;
    var $pays;
    var $email;
    var $public;
    var $projetid;
    var $modepaiement;
    var $modepaiementid;
    var $note;
    var $statut;
    
    var $projet;
    var $error;
    
    /**
     *    \brief  Constructeur
     *    \param  DB          	Handler d'accès base
     */
    function Don($DB)
    {
        global $langs;
        
        $this->db = $DB ;
        $this->modepaiementid = 0;
    
        $langs->load("donations");
        $this->labelstatut[0]=$langs->trans("DonationStatusPromiseNotValidated");
        $this->labelstatut[1]=$langs->trans("DonationStatusPromiseValidated");
        $this->labelstatut[2]=$langs->trans("DonationStatusPayed");
        $this->labelstatutshort[0]=$langs->trans("DonationStatusPromiseNotValidatedShort");
        $this->labelstatutshort[1]=$langs->trans("DonationStatusPromiseValidatedShort");
        $this->labelstatutshort[2]=$langs->trans("DonationStatusPayedShort");
    }

    
	/**
	 *    \brief      Retourne le libellé du statut d'un don (brouillon, validée, abandonnée, payée)
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

		if ($mode == 0)
		{
			return $this->labelstatut[$statut];
		}
		if ($mode == 1)
		{
			return $this->labelstatutshort[$statut];
		}
		if ($mode == 2)
		{
			if ($statut == 0) return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatutshort[$statut];
			if ($statut == 1) return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatutshort[$statut];
			if ($statut == 2) return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatutshort[$statut];
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if ($statut == 0) return img_picto($this->labelstatut[$statut],'statut0');
			if ($statut == 1) return img_picto($this->labelstatut[$statut],'statut1');
			if ($statut == 2) return img_picto($this->labelstatut[$statut],'statut6');
		}
		if ($mode == 4)
		{
			if ($statut == 0) return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatut[$statut];
			if ($statut == 1) return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatut[$statut];
			if ($statut == 2) return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatut[$statut];
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if ($statut == 0) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
			if ($statut == 1) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
			if ($statut == 2) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
		}
	}
	
	
	/**
	 *		\brief		Initialise le don avec valeurs fictives aléatoire
	 *					Sert à générer une recu de don pour l'aperu des modèles ou demo
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
		$this->nom = 'Doe';
		$this->prenom = 'John';
		$this->socid = $socids[$socid];
		$this->date = time();
		$this->amount = 100;
		$this->public = 1;
		$this->societe = 'The Company';
		$this->adresse = 'Twist road';
		$this->cp = '99999';
		$this->ville = 'Town';
		$this->note_public='SPECIMEN';
		$this->email='email@email.com';
		$this->note='';
		$this->statut=1;
	}
	
	
	/*
     *
     */
    function print_error_list()
    {
    $num = sizeof($this->error);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->error[$i];
      }
    }

  /*
   *
   *
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
     *    \brief      Création du don en base
     *    \param      user          Objet utilisateur qui crée le don
     *    \return     int           Id don crée si ok, <0 si ko
     */
    function create($user)
    {
        $this->date = $this->db->idate($this->date);
    
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."don (datec, amount, fk_paiement,prenom, nom, societe,adresse, cp, ville, pays, public,";
        if ($this->projetid)
        {
            $sql .= " fk_don_projet,";
        }
        $sql .= " note, fk_user_author, datedon, email)";
        $sql .= " VALUES (now(),".price2num($this->amount).", $this->modepaiementid,'$this->prenom','$this->nom','$this->societe','$this->adresse', '$this->cp','$this->ville','$this->pays',$this->public, ";
        if ($this->projetid)
        {
            $sql .= " $this->projetid,";
        }
        $sql .= " '$this->note', ".$user->id.", '$this->date','$this->email')";
    
        $result = $this->db->query($sql);
        if ($result)
        {
            return $this->db->last_insert_id(MAIN_DB_PREFIX."don");
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
    }

  /**
   *    \brief      Mise à jour du don
   *    \param      user        Objet utilisateur qui met à jour le don
   *    \return     int         >0 si ok, <0 si ko
   */
    function update($user)
    {
    
        $this->date = $this->db->idate($this->date);
    
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET ";
        $sql .= "amount = " . $this->amount;
        $sql .= ",fk_paiement = ".$this->modepaiementid;
        $sql .= ",prenom = '".$this->prenom ."'";
        $sql .= ",nom='".$this->nom."'";
        $sql .= ",societe='".$this->societe."'";
        $sql .= ",adresse='".$this->adresse."'";
        $sql .= ",cp='".$this->cp."'";
        $sql .= ",ville='".$this->ville."'";
        $sql .= ",pays='".$this->pays."'";
        $sql .= ",public=".$this->public;
        if ($this->projetid) {    $sql .= ",fk_don_projet=".$this->projetid; }
        $sql .= ",note='".$this->note."'";
        $sql .= ",datedon='".$this->date."'";
        $sql .= ",email='".$this->email."'";
        $sql .= ",fk_statut=".$this->statut;
    
        $sql .= " WHERE rowid = $this->id";
    
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
    }

  /*
   *    \brief  Suppression du don de la base
   *    \param  rowid   id du don à supprimer 
   */
  function delete($rowid)
  {
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."don WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return -1;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return -1;
      }    
  }

    /*
     *      \brief      Charge l'objet don en mémoire depuis la base de donnée
     *      \param      rowid       Id du don à charger
     *      \return     int         <0 si ko, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT d.rowid, ".$this->db->pdate("d.datec")." as datec,";
        $sql.= " ".$this->db->pdate("d.datedon")." as datedon,";
        $sql.= " d.prenom, d.nom, d.societe, d.amount, p.libelle as projet, d.fk_statut, d.adresse, d.cp, d.ville, d.pays, d.public, d.amount, d.fk_paiement, d.note, cp.libelle, d.email, d.fk_don_projet";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as cp, ".MAIN_DB_PREFIX."don as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."don_projet as p";
        $sql.= " ON p.rowid = d.fk_don_projet";
        $sql.= " WHERE cp.id = d.fk_paiement AND d.rowid = ".$rowid;
    
        if ( $this->db->query( $sql) )
        {
            if ($this->db->num_rows())
            {
    
                $obj = $this->db->fetch_object();
    
                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->datec          = $obj->datec;
                $this->date           = $obj->datedon;
                $this->prenom         = stripslashes($obj->prenom);
                $this->nom            = stripslashes($obj->nom);
                $this->societe        = stripslashes($obj->societe);
                $this->statut         = $obj->fk_statut;
                $this->adresse        = stripslashes($obj->adresse);
                $this->cp             = stripslashes($obj->cp);
                $this->ville          = stripslashes($obj->ville);
                $this->email          = stripslashes($obj->email);
                $this->pays           = stripslashes($obj->pays);
                $this->projet         = $obj->projet;
                $this->projetid       = $obj->fk_don_projet;
                $this->public         = $obj->public;
                $this->modepaiementid = $obj->fk_paiement;
                $this->modepaiement   = $obj->libelle;
                $this->amount         = $obj->amount;
                $this->commentaire    = stripslashes($obj->note);
            }
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
    
    }

  /*
   *    \brief  Valide une promesse de don
   *    \param  rowid   id du don à modifier
   *    \param  userid  utilisateur qui valide la promesse
   *
   */
  function valid_promesse($rowid, $userid)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

    /*
     *    \brief  Classe le don comme payé, le don a été recu
     *    \param  rowid           id du don à modifier
     *    \param  modepaiementd   mode de paiement
     */
    function set_paye($rowid, $modepaiement='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2";
    
        if ($modepaiement)
        {
            $sql .= ", fk_paiement=$modepaiement";
        }
        $sql .=  " WHERE rowid = $rowid AND fk_statut = 1;";
    
        if ( $this->db->query( $sql) )
        {
            if ( $this->db->affected_rows() )
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            dolibarr_print_error($this->db);
            return 0;
        }
    }


	/*
	 *    \brief  Classe le don comme encaissé
	 *    \param  rowid   id du don à modifier
	 *
	 */
	function set_encaisse($rowid)
	{
	
		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 3 WHERE rowid = $rowid AND fk_statut = 2;";
	
		if ( $this->db->query( $sql) )
		{
			if ( $this->db->affected_rows() )
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return 0;
		}
	}

	/**
	 *    	\brief		Somme des dons
	 *		\param		param	1=promesses de dons validées , 2=xxx, 3=encaissés
	 */
	function sum_donations($param)
	{
		$result=0;
		
		$sql = "SELECT sum(amount) as total";
		$sql.= " FROM ".MAIN_DB_PREFIX."don";
		$sql.= " WHERE fk_statut = ".$param;
	
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$result=$obj->total;
		}

		return $result;
	}

}
?>
