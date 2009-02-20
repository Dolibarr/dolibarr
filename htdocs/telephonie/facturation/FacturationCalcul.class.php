<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Script de calcul de la facturation
 * - Lit les entrees dans la table import_cdr
 * - Verifie que tous les tarifs sont dispos
 * - Importe les lignes dans llx_communications_details
 * - Calcul la facture telephonique par ligne
 */

/**
   \file       htdocs/telephonie/script/facturation-calcul.php
   \ingroup    telephonie
   \brief      Calcul des factures
   \version    $Revision$
*/
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.class.php");

class FacturationCalcul {

  function FacturationCalcul($dbh)
  {
    $this->db = $dbh;
    $this->messages = array();
    $this->message_bad_file_format = array();
  }

  function Calcul()
  {
    $error = 0;
    $nbcommit = 0;
    $datetime = time();
    
    $date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

    /*
     * On facture les communications du mois precedent
     */
    
    $month = strftime("%m", $datetime);
    $year = strftime("%Y", $datetime);
    
    if ($month == 1)
      {
	$month = "12";
	$year = $year - 1;
      }
    else
      {
	$month = substr("00".($month - 1), -2) ;
      }

    /********************************************************
     *
     * Affiche le nombre de comunications a traiter
     *
     *********************************************************/
    
    $sql = "SELECT count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";;
    
    $resql = $this->db->query($sql);
    
    if ( $resql )
      {
	$num = $this->db->num_rows($resql);
	$row = $this->db->fetch_row($resql);
	
	dol_syslog("FacturationCalcul::Calcul Communications a traiter ".$row[0],LOG_INFO);
	$this->db->free($resql);
      }
    else
      {
	$error = 1;
	dol_syslog("FacturationCalcul Erreur ".$error);
      }
    
    /**********************************************************
     *
     *
     *
     ***********************************************************/
    
    $sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facture";
    
    $resql = $this->db->query($sql);
    
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);
	
	dol_syslog("FacturationCalcul Max rowid avant facture ".$row[0]);
	$this->db->free($resql);
      }
    else
      {
	$error = 2;
	dol_syslog("FacturationCalcul Erreur ".$error);
      }
    
    /**
     *
     * Lectures des differentes lignes dans la table d'import
     *
     */
    
    if (!$error)
      {
	$user = new user($this->db,1);
	
	$sql = "SELECT distinct(t.fk_ligne)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr as t";
	$sql .= " ORDER BY fk_ligne ASC";
	
	$lines_keys = array();
	$resql = $this->db->query($sql);
	if ( $resql )
	  {    
	    $i=0;
	    while ($row = $this->db->fetch_row($resql))
	      {
		$lines_keys[$i] = $row[0];		
		$i++;
	      }            
	    $this->db->free($resql);
	    dol_syslog(sizeof($lines_keys)." lignes trouvees");
	  }
	else
	  {
	    $error = 3;
	    dol_syslog("FacturationCalcul Erreur ".$error);
	  }
      }

    /**********************************************************
     *
     * Creation d'un batch de facturation
     *
     ***********************************************************/
    
    if (sizeof($lines_keys) > 0)
      {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_facturation_batch";
	$sql .= " (date_batch) VALUES (now())";
	$resql = $this->db->query($sql);
	
	if ( $resql )
	  {
	    $batch_id = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_facturation_batch");
	    
	    dol_syslog("FacturationCalcul Batch ID ".$batch_id);
	  }
	else
	  {
	    $error = 20;
	    dol_syslog("FacturationCalcul Erreur ".$error);
	  }  
      }
    
    /* ***************************************************** */
    /*                                                       */
    /* Traitements                                           */
    /*                                                       */
    /*                                                       */
    /* ***************************************************** */
    
    if (!$error)
      {	
	foreach ($lines_keys as $line_key)
	  {
	    $error = 0;
	    $ligne = new LigneTel($this->db);
	    
	    if ( $this->db->query("BEGIN") )
	      {
		if ($ligne->fetch_by_id($line_key) > 0 )
		  {
		    if ($ligne->socid == 0)
		      {
			$error = 4;
			dol_syslog("FacturationCalcul Error ($error)");
		      }	  	  
		  }
		else
		  {
		    
		    $error = 5;	  
		    dol_syslog("FacturationCalcul Error ($error): Aucune societe rattachee a la ligne : $line_key");
		  }
		
		
		/*
		 * Recuperation des infos sur la societes
		 *
		 */      
		if (!$error )
		  {	      
		    $soc = new Societe($this->db);
		    if ( $soc->fetch($ligne->socid) )
		      {
			
		      }
		    else
		      {
			$error = 6;
			dol_syslog("FacturationCalcul FacturationCalcul Error ($error)");
		      }
		  }
		
		/*
		 *
		 * Creation d'une facture de telephonie si la ligne est facturable
		 *
		 */
		
		if (!$error)
		  {
		    if ($ligne->facturable == 1)
		      {
			$facturable = 'oui';
		      }
		    else
		      {
			$facturable = 'non';
		      }
		    
		    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_facture";
		    $sql .= " (fk_ligne, ligne, date, isfacturable, fk_batch, fk_contrat)";
		    $sql .= " VALUES (".$ligne->id.",";
		    $sql .= " '$ligne->numero','".$year."-".$month."-01'";
		    $sql .= ", '$facturable',".$batch_id;
		    $sql .= ", ".$ligne->contrat.")";
		    
		    if ($this->db->query($sql))
		      {
			$facid = $this->db->last_insert_id(MAIN_DB_PREFIX."telephonie_facture");
		      }
		    else
		      {
			$error++;
			dol_syslog("FacturationCalcul Erreur d'insertion dans llx_telephonie_facture");
			dol_syslog($this->db->error());
			dol_syslog($sql);
		      }
		  }	 
		/*
		 *
		 * Calcul de la facture
		 *
		 */
		if (!$error)
		  {
		    $total_achat = 0;
		    $total_vente = 0;
		    $total_fourn = 0;
		    
		    if ($this->CalculateBill($this->db, $ligne, $facid, $total_achat, $total_vente, $total_fourn) <> 0)
		      {
			$error++;
			dol_syslog("FacturationCalcul Erreur de calcul de la facture pour la ligne $line_key $ligne->numero");
			array_push($this->messages, array('error',"Erreur de calcul de la facture pour la ligne $ligne->numero (id=$line_key)"));
		      }	  
		  }	  
		
		/*
		 *
		 * Insertion des donnees dans la base
		 *
		 */
		
		if (!$error)
		  {
		    $total_vente_remise = $total_vente;
		    
		    $total_vente_remise = ereg_replace(",",".", $total_vente_remise);
		    
		    $gain = ($total_vente_remise - $total_fourn);
		    
		    $total_achat = ereg_replace(",",".", $total_achat);
		    $total_vente = ereg_replace(",",".", $total_vente);
		    $total_fourn = ereg_replace(",",".", $total_fourn);
		    
		    $gain = ereg_replace(",",".", $gain);
		    
		    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture";
	      
		    $sql .= " SET ";
		    $sql .= " fourn_montant = $total_fourn";
		    $sql .= " , cout_achat = $total_achat";
		    $sql .= " , cout_vente = $total_vente";
		    $sql .= " , remise = $ligne->remise";
		    $sql .= " , cout_vente_remise = $total_vente_remise";
		    $sql .= " , gain = $gain";
	      
		    $sql .= " WHERE rowid =".$facid;
	      
		    if ($this->db->query($sql))
		      {
		  
		      }
		    else
		      {
			$error++;
			dol_syslog("FacturationCalcul Erreur de mise a jour dans llx_telephonie_facture");
			dol_syslog($this->db->error());
			dol_syslog($sql);
		      }
		  }
	  
		/*
		 * Suppression des donnees de la table d'import
		 *
		 */
	  
		if (!$error)
		  {
		    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
		    $sql .= " WHERE fk_ligne = $line_key ";
	      
		    if (! $this->db->query($sql))
		      {
			$error++;
			dol_syslog("FacturationCalcul Erreur de suppression dans llx_telephonie_import_cdr");
		      }
		  }
	  
		/*
		 * Commit / Rollback SQL
		 *
		 */      
	  
		if (!$error)
		  {
		    $this->db->query("COMMIT");
		    $nbcommit++;
		    dol_syslog("FacturationCalcul Ligne $ligne->numero - COMMIT");
		    array_push($this->messages, "Facturation ligne ".$ligne->numero." reussie");
		  }
		else
		  {
		    $this->db->query("ROLLBACK");
		    dol_syslog("FacturationCalcul Ligne $ligne->numero - ROLLBACK de la transaction");	      
		  }
	      }
	    else
	      {
		dol_syslog("FacturationCalcul Erreur ouverture Transaction SQL");
	      }
	  } /* fin de la boucle */

	/*
	 *
	 *
	 */
      }

    /**********************************************************
     *
     *
     *
     ***********************************************************/
    $sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facture";

    $resql = $this->db->query($sql);
  
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);

	dol_syslog("FacturationCalcul Max rowid après facture ".$row[0]);
	$this->db->free($resql);
      }
    else
      {
	$error++;
      }

    /**********************************************************
     *
     *
     *
     ***********************************************************/

    dol_syslog($nbcommit." facture emises");

    /**********************************************************
     *
     *
     *
     ***********************************************************/
    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";

    $resql = $this->db->query($sql);
  
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);

	dol_syslog($row[0]. " communications restantes dans la table d'import");
	$this->db->free($resql);
      }
    else
      {
	$error++;
      }

    dol_syslog("FacturationCalcul Fin Batch ID ".$batch_id);
  }
  /******************************************************************************
   *
   * Fonction de calcul de la facture
   *
   ******************************************************************************/
  
  function CalculateBill($db, $ligne, $facture_id, &$total_cout_achat, &$total_cout_vente, &$total_cout_fourn)
  {
    $error = 0;
    
    $total   = 0;
    $nbinter = 0;
    $nbmob   = 0;
    $nbnat   = 0;
    $duree   = 0;

    $tarif_spec = TELEPHONIE_GRILLE_VENTE_DEFAUT_ID ;
    
    $sql = "SELECT d.grille_tarif";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    
    $sql .= " WHERE l.rowid = ".$ligne->id;
    $sql .= " AND d.rowid = dc.fk_distributeur";
    $sql .= " AND l.fk_commercial_sign = dc.fk_user";
    
    $resql = $db->query($sql);
    
    if ( $resql )
      {
	$num_sql = $db->num_rows($resql);
	
	if ($num_sql > 0)
	  {
	    $row = $db->fetch_row($resql);
	    $tarif_spec = $row[0];
	  }
	$db->free($resql);
      }

    $fournisseur_id = $ligne->fournisseur_id;
    
    dol_syslog("FacturationCalcul::CalculateBill Utilisation du tarif ".$tarif_spec." pour la ligne ".$ligne->id);
    
    $tarif_achat = new TelephonieTarif($db, $tarif_spec, "achat", $fournisseur_id);
    $tarif_vente = new TelephonieTarif($db, $tarif_spec, "vente", $tarif_spec, $ligne->client_comm_id);

    $comms = array();

    $sql = "SELECT t.idx, t.fk_ligne, t.ligne, t.montant, t.duree, t.num, t.date, t.heure, t.dest";
    $sql .= " , t.fichier, t.fk_fournisseur";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr as t";
    $sql .= " WHERE t.fk_ligne = ".$ligne->id;
    
    $resql = $db->query($sql);

    if ($resql)
      {
	$num_sql = $db->num_rows($resql);
	$i = 0;
      
	while ($i < $num_sql && $error == 0)
	  {
	    $objp = $db->fetch_object($resql);

	    $comm = new CommunicationTelephonique();

	    $comm->index       = $objp->idx;
	    $comm->fk_ligne    = $objp->fk_ligne;
	    $comm->ligne       = $objp->ligne;
	    $comm->date        = $objp->date;
	    $comm->heure       = $objp->heure;
	    $comm->duree       = $objp->duree;
	    $comm->dest        = $objp->dest;
	    $comm->numero      = $objp->num;
	    $comm->montant     = $objp->montant;
	    $comm->fichier_cdr = $objp->fichier;
	    $comm->fournisseur = $objp->fk_fournisseur;
	    $comm->facture_id  = $facture_id;
	 
	    $comms[$i] = $comm;

	    $i++;
	  }

	$db->free($resql);
      }
    else
      {
	$error++;
	dol_syslog("FacturationCalcul::CalculateBill Erreur dans Calcul() Probleme SQL");
      }

    for ($ii = 0 ; $ii < $num_sql ; $ii++)
      {
	$comm = $comms[$ii];

	$error = $error + $comm->cout($tarif_achat, $tarif_vente, $ligne, $db);

	$total_cout_fourn = $total_cout_fourn + $comm->montant;
	$total_cout_achat = $total_cout_achat + $comm->cout_achat;
	$total_cout_vente = $total_cout_vente + $comm->cout_vente;

	$error = $error + $comm->logsql($db);

	foreach ($comm->messages as $message)
	  {
	    array_push($this->messages, $message);
	  }
      }



    dol_syslog("FacturationCalcul::CalculateBill return $error", LOG_DEBUG);
    return $error;
  }

}
?>
