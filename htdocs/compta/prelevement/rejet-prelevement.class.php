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
 *
 */

/*
  \file       htdocs/compta/prelevement/prelevement.class.php
  \ingroup    prelevement
  \brief      Fichier de la classe des prelevements
  \version    $Revision$
*/


/*!
  \class Prelevement
  \brief      Classe permettant la gestion des prelevements
*/

class RejetPrelevement
{
  var $id;
  var $db;


  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  soc_idp     id societe ("" par defaut)
   *    \param  facid       id facture ("" par defaut)
   */
  function RejetPrelevement($DB, $user)
  {
    $this->db = $DB ;
    $this->user = $user;
  }

  function create($id, $socid)
    {
      $this->id = $id;
      $this->socid = $socid;

      dolibarr_syslog("RejetPrelevement::Create id $id");
      dolibarr_syslog("RejetPrelevement::Create socid $socid");
      
      $facs = $this->_get_list_factures();

      for ($i = 0 ; $i < sizeof($facs) ; $i++)
	{	  
	  $fac = new Facture($this->db);
	  $fac->fetch($facs[$i]);

	  /* Emet un paiement négatif */

	  $pai = new Paiement($this->db);

	  $pai->amounts = array();
	  $pai->amounts[$facs[$i]] = (0 - $fac->total_ttc);
	  $pai->datepaye = $this->db->idate(time());
	  $pai->paiementid = 3; // prélèvement
	  $pai->num_paiement = "Rejet";

	  if ($pai->create($this->user, 1) == -1)  // on appelle en no_commit
	    {
	      $error++;
	      dolibarr_syslog("RejetPrelevement::Create Erreur creation paiement facture ".$facs[$i]);
	    }
	  
	  /* Tag la facture comme impayée */
	  dolibarr_syslog("RejetPrelevement::Create set_unpayed fac ".$fac->ref);
	  $fac->set_unpayed($facs[$i]);

	  /* Envoi un email à l'emetteur de la demande de prev */
	  $this->_send_email($fac);
	}

    }

  /**
   *
   *
   *
   */
  function _send_email($fac)
  {
    $userid = 0;

    $sql = "SELECT fk_user_demande";
    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
    $sql .= " WHERE pfd.fk_prelevement = ".$this->id;
    $sql .= " AND pfd.fk_facture = ".$fac->id;

    $result=$this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();	
	if ($num > 0)
	  {
	    $row = $this->db->fetch_row();
	    $userid = $row[0];
	  }		
      }
    else
      {
	dolibarr_syslog("RejetPrelevement::_send_email Erreur lecture user");
      }

    if ($userid > 0)
      {
	$emuser = new User($this->db, $userid);
	$emuser->fetch();

	$subject = "Prélèvement rejeté";

	$soc = new Societe($this->db);
	$soc->fetch($fac->socidp);

	$sendto = $emuser->fullname." <".$emuser->email.">";
	$from = $this->user->fullname." <".$this->user->email.">";

	$message = "Bonjour,\n";
	$message .= "\nLe prélèvement de la facture ".$fac->ref." pour le compte de la société ".$soc->nom." d'un montant de ".price($fac->total_ttc)." euros a été rejeté par la banque.";

	$message .= "\n\n--\n".$this->user->fullname;	
	      
	$mailfile = new DolibarrMail($subject,
				     $sendto,
				     $from,
				     $message);
	      
	$mailfile->errors_to = $this->user->email;

	if ( $mailfile->sendfile() )
	  {
	    dolibarr_syslog("RejetPrelevement::_send_email email envoyé");
	  }
	else
	  {
	    dolibarr_syslog("RejetPrelevement::_send_email Erreur envoi email");
	  }
      }
    else
      {
	dolibarr_syslog("RejetPrelevement::_send_email Userid invalide");
      }
  }  


  /**
   *    \brief      Recupére la liste des factures concernées
   *    \param      rowid       id de la facture a récupérer
   *    \param      societe_id  id de societe
   */
  function _get_list_factures()
    {
      $arr = array();
      /*
       * Renvoie toutes les factures de la société à partir d'une facture
       * dans un bon de prélèvement
       * Lors du prélèvement les diff factures sont agrégées ensemble
       */
      
      $sql = "SELECT f.rowid as facid";

      $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture as pf";
      $sql .= " , ".MAIN_DB_PREFIX."facture as f";

      $sql .= " WHERE f.fk_soc = ".$this->socid;

      $sql .= " AND pf.fk_prelevement = ".$this->id;

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
	  dolibarr_syslog("RejetPrelevement Erreur");
	}

      return $arr;

    }

  

  /**
   *    \brief      Recupére l'objet prelevement
   *    \param      rowid       id de la facture a récupérer
   *    \param      societe_id  id de societe
   */
  function fetch($rowid)
    {

      $sql = "SELECT f.fk_soc,f.facnumber,f.amount,f.tva,f.total,f.total_ttc,f.remise,f.remise_percent";
      $sql .= ",".$this->db->pdate("f.datef")." as df,f.fk_projet";
      $sql .= ",".$this->db->pdate("f.date_lim_reglement")." as dlr";
      $sql .= ", c.rowid as cond_regl_id, c.libelle, c.libelle_facture";
      $sql .= ", f.note, f.paye, f.fk_statut, f.fk_user_author";
      $sql .= ", fk_mode_reglement";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."cond_reglement as c";
      $sql .= " WHERE f.rowid=$rowid AND c.rowid = f.fk_cond_reglement";
      
      if ($societe_id > 0) 
	{
	  $sql .= " AND f.fk_soc = ".$societe_id;
	}

    $result=$this->db->query($sql);
    if ($result)
	{
	  if ($this->db->num_rows($result))
	    {
	      $obj = $this->db->fetch_object($result);
	      
	      $this->id                 = $rowid;
	      $this->datep              = $obj->dp;
	      $this->date               = $obj->df;
	      $this->ref                = $obj->facnumber;
	      $this->amount             = $obj->amount;
	      $this->remise             = $obj->remise;
	      $this->total_ht           = $obj->total;
	      $this->total_tva          = $obj->tva;
	      $this->total_ttc          = $obj->total_ttc;
	      $this->paye               = $obj->paye;
	      $this->remise_percent     = $obj->remise_percent;
	      $this->socidp             = $obj->fk_soc;
	      $this->statut             = $obj->fk_statut;
	      $this->date_lim_reglement = $obj->dlr;
	      $this->cond_reglement_id  = $obj->cond_regl_id;
	      $this->cond_reglement     = $obj->libelle;
	      $this->cond_reglement_facture = $obj->libelle_facture;
	      $this->projetid           = $obj->fk_projet;
	      $this->note               = stripslashes($obj->note);
	      $this->user_author        = $obj->fk_user_author;
	      $this->lignes             = array();

	      $this->mode_reglement     = $obj->fk_mode_reglement;

	      if ($this->statut == 0)
		{
		  $this->brouillon = 1;
		}

	      $this->db->free();


	    }
	  else
	    {
	      //dolibarr_print_error($this->db);
	      dolibarr_syslog("Erreur Facture::Fetch rowid=$rowid numrows=0");
	    }
	}
      else
	{
	  //dolibarr_print_error($this->db);
	  dolibarr_syslog("Erreur Facture::Fetch rowid=$rowid Erreur dans fetch de la facture");
	}
    }


}

?>
