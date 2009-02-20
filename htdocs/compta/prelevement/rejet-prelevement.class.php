<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
		\file       htdocs/compta/prelevement/rejet-prelevement.class.php
		\ingroup    prelevement
		\brief      Fichier de la classe des prelevements
		\version    $Revision$
*/


/**
		\class 		Prelevement
		\brief      Classe permettant la gestion des prelevements
*/

class RejetPrelevement
{
  var $id;
  var $db;


  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          Handler accès base de données
   *    \param  user        Utilisateur
   */
  function RejetPrelevement($DB, $user)
  {
    $this->db = $DB ;
    $this->user = $user;

    $this->motifs = array();
    $this->motifs[0] = "Non renseigné";
    $this->motifs[1] = "Provision insuffisante";
    $this->motifs[2] = "Tirage contesté";
    $this->motifs[3] = "Pas de bon à payer";
    $this->motifs[4] = "Opposition sur compte";
    $this->motifs[5] = "RIB inexploitable";
    $this->motifs[6] = "Compte soldé";
    $this->motifs[7] = "Décision judiciaire";
    $this->motifs[8] = "Autre motif";
  }

  function create($user, $id, $motif, $date_rejet, $bonid, $facturation=0)
  {
    $error = 0;
    $this->id = $id;
    $this->bon_id = $bonid;
    
    dol_syslog("RejetPrelevement::Create id $id");
    
    $facs = $this->_get_list_factures();
    
    $this->db->begin();


    /* Insert la ligne de rejet dans la base */
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_rejet ";
    $sql .= " (fk_prelevement_lignes, date_rejet";
    $sql .= " , motif , fk_user_creation, date_creation, afacturer)";
    $sql .= " VALUES (".$id;
    $sql .= " ,'".$this->db->idate($date_rejet)."'";
    $sql .= " ,".$motif.",". $user->id.", ".$this->db->idate(mktime()).",".$facturation.");";
    
    $result=$this->db->query($sql);
    
    if (!$result)
      {
	dol_syslog("RejetPrelevement::create Erreur 4");
	dol_syslog("RejetPrelevement::create Erreur 4 $sql");
	$error++;
      }
    
    /* Tag la ligne de prev comme rejetée */
    
    $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes ";
    $sql .= " SET statut = 3";
    $sql .= " WHERE rowid=".$id;
    
    if (! $this->db->query($sql))
      {
	dol_syslog("RejetPrelevement::create Erreur 5");
	$error++;
      }
    
    
    for ($i = 0 ; $i < sizeof($facs) ; $i++)
      {	  
	$fac = new Facture($this->db);
	$fac->fetch($facs[$i]);
	
	/* Emet un paiement négatif */

	$pai = new Paiement($this->db);

	$pai->amounts = array();
	// On remplace la virgule éventuelle par un point sinon
	// certaines install de PHP renvoie uniquement la partie
	// entiere negative

	$pai->amounts[$facs[$i]] = price2num($fac->total_ttc * -1);
	$pai->datepaye = $this->db->idate($date_rejet);
	$pai->paiementid = 3; // prélèvement
	$pai->num_paiement = "Rejet";

	if ($pai->create($this->user, 1) == -1)  // on appelle en no_commit
	  {
	    $error++;
	    dol_syslog("RejetPrelevement::Create Erreur creation paiement facture ".$facs[$i]);
	  }       
  
	/* Valide le paiement */

	if ($pai->valide() < 0)
	  {
	    $error++;
	    dol_syslog("RejetPrelevement::Create Erreur validation du paiement");
	  }

	/* Tag la facture comme impayée */
	dol_syslog("RejetPrelevement::Create set_unpayed fac ".$fac->ref);
	$fac->set_unpayed($fac->id, $user);

	/* Envoi un email à l'emetteur de la demande de prev */
	$this->_send_email($fac);
      }

    if ($error == 0)
      {
	dol_syslog("RejetPrelevement::Create Commit");
	$this->db->commit();
      }
    else
      {
	dol_syslog("RejetPrelevement::Create Rollback");
	$this->db->rollback();
      }

  }

    /**
     *      \brief      Envoi mail
     *
     */
    function _send_email($fac)
    {
        $userid = 0;
    
        $sql = "SELECT fk_user_demande";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        $sql .= " WHERE pfd.fk_prelevement_bons = ".$this->bon_id;
        $sql .= " AND pfd.fk_facture = ".$fac->id;
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            if ($num > 0)
            {
                $row = $this->db->fetch_row($resql);
                $userid = $row[0];
            }
        }
        else
        {
            dol_syslog("RejetPrelevement::_send_email Erreur lecture user");
        }
    
        if ($userid > 0)
        {
            $emuser = new User($this->db, $userid);
            $emuser->fetch();
    
            $soc = new Societe($this->db);
            $soc->fetch($fac->socid);
    
            require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
    
            $subject = "Prélèvement rejeté";
            $sendto = $emuser->fullname." <".$emuser->email.">";
            $from = $this->user->fullname." <".$this->user->email.">";
    		$msgishtml=0;
    		
            $arr_file = array();
            $arr_mime = array();
            $arr_name = array();
    
            $message = "Bonjour,\n";
            $message .= "\nLe prélèvement de la facture ".$fac->ref." pour le compte de la société ".$soc->nom." d'un montant de ".price($fac->total_ttc)." a été rejeté par la banque.";
            $message .= "\n\n--\n".$this->user->fullname;
    
            $mailfile = new CMailFile($subject,$sendto,$from,$message,
                                      $arr_file,$arr_mime,$arr_name,
                                      '', '', 0, $msgishtml,$this->user->email);
    
            $result=$mailfile->sendfile();
            if ($result)
            {
                dol_syslog("RejetPrelevement::_send_email email envoyé");
            }
            else
            {
                dol_syslog("RejetPrelevement::_send_email Erreur envoi email");
            }
        }
        else
        {
            dol_syslog("RejetPrelevement::_send_email Userid invalide");
        }
    }


  /**
   *    \brief      Recupére la liste des factures concernées
   */
  function _get_list_factures()
  {
    $arr = array();
    /*
     * Renvoie toutes les factures associée à un prélèvement
     *
     */
      
    $sql = "SELECT f.rowid as facid";

    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture as pf";
    $sql .= " , ".MAIN_DB_PREFIX."facture as f";

    $sql .= " WHERE pf.fk_prelevement_lignes = ".$this->id;

    $sql .= " AND pf.fk_facture = f.rowid";

    $result=$this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();

	if ($num)
	  {
	    $i = 0;
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row();
		$arr[$i] = $row[0];
		$i++;
	      }
	  }
	$this->db->free();
      }
    else
      {
	dol_syslog("RejetPrelevement Erreur");
      }

    return $arr;

  }

  

  /**
   *    \brief      Recupére l'objet prelevement
   *    \param      rowid       id de la facture a récupérer
   */
  function fetch($rowid)
  {

    $sql = "SELECT ".$this->db->pdate("pr.date_rejet")." as dr";
    $sql .= ", motif";
    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_rejet as pr";
    $sql .= " WHERE pr.fk_prelevement_lignes =".$rowid;
      

    if ($this->db->query($sql))
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object();
	      
	    $this->id             = $rowid;
	    $this->date_rejet     = $obj->dr;
	    $this->motif          = $this->motifs[$obj->motif];

	    $this->db->free();
	    
	    return 0;
	  }
	else
	  {
	    dol_syslog("RejetPrelevement::Fetch Erreur rowid=$rowid numrows=0");
	    return -1;
	  }
      }
    else
      {
	dol_syslog("RejetPrelevement::Fetch Erreur rowid=$rowid");
	return -2;
      }
  }

}

?>
