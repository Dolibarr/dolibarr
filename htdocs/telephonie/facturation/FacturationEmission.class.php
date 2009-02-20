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
 * Script de facturation
 * Emets les factures compta en partant des factures téléphonique
 *
 */

/**
   \file       htdocs/telephonie/script/facturation-emission.php
   \ingroup    telephonie
   \brief      Emission des factures
   \version    $Revision$
*/

require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");


class FacturationEmission {

  function FacturationEmission($db, $user)
  {
    $this->db = $db;
    $this->do_validate_facture = 0;
    $this->messages = array();
    $this->user = $user;
  }
  /*
   * Compte le nombre de facture a generer
   *
   */
  function NbFactureToGenerate()
  {    
    $sql = "SELECT count(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facture";
    $sql .= " WHERE fk_facture IS NULL;";
    
    $resql = $this->db->query($sql);
    
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);
	
	$this->nbfac = $row[0];
	$this->db->free($resql);
	$this->error = 0;
      }
    else
      {
	$this->error = 1;
	dol_syslog("Erreur ".$error);
      }
    return $this->error;
  }
  
  function Emission()
  {
    $error = 0;
    
    $datetime = time();
    $datetimeprev = $datetime; // Date du prélèvement
    
    $date = strftime("%d%h%Y%Hh%Mm%S",$datetime);
        
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
    
    /*
     * Lecture du batch
     *
     */
    
    $sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facturation_batch";
    
    $resql = $this->db->query($sql);
    
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);
	
	$batch_id = $row[0];
	
	dol_syslog("FacturationEmission::Emission Traitement du batch ".$batch_id);
	$this->db->free($resql);
      }
    else
      {
	$error = 1;
	dol_syslog("FacturationEmission::Emission Erreur ".$error);
      }

    /*
     * Traitements
     *
     */
    
    if (!$error)
      {
	/***************************************************************
	 *
	 * Lectures des contrats a traiter
	 *
	 *
	 ***************************************************************/
	
	dol_syslog("FacturationEmission::Emission Lecture des contrats",LOG_DEBUG);
	
	$sql = "SELECT distinct(c.rowid)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
	$sql .= " ,    ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	$sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";
	
	$sql .= " WHERE f.fk_facture IS NULL";
	$sql .= " AND f.fk_batch = ".$batch_id;
	$sql .= " AND f.isfacturable = 'oui'"; 
	$sql .= " AND f.fk_ligne = l.rowid ";
	$sql .= " AND l.fk_contrat = c.rowid";  
	
	if (strlen($optcontrat) >  0)
	  {
	    $sql .= " AND c.rowid=".$optcontrat;
	    dol_syslog("Limite sur le contrat : ".$optcontrat);
	  }
	
	$contrats = array();
  
	$resql = $this->db->query($sql) ;
	
	if ( $resql )
	  {
	    $i = 0;
	    while ($row = $this->db->fetch_row($resql))
	      {
		$contrats[$i] = $row[0];
		$i++;
	      }            
	    $this->db->free($resql);
	  }
	else
	  {
	    $error = 2;
	    dol_syslog("FacturationEmission::Emission Erreur $error",LOG_ERR);
	  }
      }
    /*
     *
     *
     */
    if (!$error)
      { 
	dol_syslog("FacturationEmission::Emission Nombre de contrats à facturer ".sizeof($contrats),LOG_DEBUG);
	array_push($this->messages, "Nombre de contrats à facturer : ".sizeof($contrats));
	$xcli = 0;
	$xclis = sizeof($contrats);
	
	foreach ($contrats as $contrat)
	  {
	    $xcli++;
	    
	    /* Lecture des factures téléphoniques du contrat */
	    dol_syslog("FacturationEmission::Emission ".$xcli."/".$xclis." Contrat à facturer id=".$contrat." (".memory_get_usage() .")",LOG_DEBUG);
	    array_push($this->messages, $xcli."/".$xclis." Contrat à facturer id=".$contrat.",batch=".$batch_id);
	    $sql = "SELECT f.rowid FROM ";     
	    $sql .=     MAIN_DB_PREFIX."telephonie_facture as f";
	    $sql .= ",".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	    $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";
	    $sql .= ",".MAIN_DB_PREFIX."societe as s";
	    
	    $sql .= " WHERE f.fk_batch = ".$batch_id;
	    $sql .= " AND c.rowid = ".$contrat;
	    $sql .= " AND l.fk_contrat = c.rowid";
	    $sql .= " AND l.rowid = f.fk_ligne";
	    $sql .= " AND s.rowid = c.fk_soc_facture ";
	    $sql .= " AND f.fk_facture IS NULL";
	    $sql .= " AND f.isfacturable = 'oui'";  
	    
	    $sql .= " ORDER BY l.code_analytique ASC, l.rowid DESC";
	    
	    $numlignes = array();
	    $resql = $this->db->query($sql);
	    if ( $resql )
	      {
		while ($objp = $this->db->fetch_object($resql))
		  {		    
		    array_push($numlignes, $objp->rowid);
		  }            
		$this->db->free($resql);
		
		dol_syslog("FacturationEmission::Emission Contrat $contrat, $i factures trouvées à générer", LOG_DEBUG);
		array_push($this->messages, "Contrat $contrat, $i factures trouvées à générer");
		
		$factures_prev = array();
		$factures_a_mailer = array();
		
		if (sizeof($numlignes) > 0)
		  {
		    $this->facture_contrat($this->db, $this->user, $contrat, $numlignes, $datetime, $factures_prev, $factures_a_mailer); 
		  }
		
		if (sizeof($numlignes) > 0)
		  {
		    $this->_prelevements($this->db, $this->user, $factures_prev); 
		  }
		
		if (sizeof($numlignes) > 0)
		  {
		    //$this->_emails($this->db, $this->user, $contrat, $factures_a_mailer);
		  }
	      }
	    else
	      {
		$error = 1;
		dol_syslog("FacturationEmission::Emission Error ".$error, LOG_ERR);
		array_push($this->messages, "Erreur base de donnees");
	      }     
	  }
      }
  }
  /**
   *
   *
   *
   *
   */
  function facture_contrat($db, $user, $contrat_id, $factel_ids, $datetime, &$factures_prev, &$factures_a_mailer)
  {
    $i = 0;	      
    $error = 0;
    
    /* Ouverture de la transaction */
    
    if (! $db->query("BEGIN") )
      {
	$error++;
      }
    
    /* Lecture du contrat */
    
    if (!$error)
      {
	$contrat = new TelephonieContrat($db);
	if ( $contrat->fetch($contrat_id) >= 0)
	  {
	    
	  }
	else
	  {
	    $error++;
	    dol_syslog("FacturationEmission::facture_contrat Impossible de lire le contrat");
	    array_push($this->messages, array('error',"Impossible de lire le contrat : $contrat_id"));
	  }
      }
    
    /* Lecture de la société */
    
    if (!$error)
      {
	$soc = new Societe($db);
	
	if ($soc->fetch($contrat->client_facture_id) )
	  {
	    if ($verbose) dol_syslog($soc->nom);
	  }
	else
	  {
	    array_push($this->messages, array('error',"Impossible de lire la societe"));
	    $error = 132;
	  }
      }
    
    /* Recuperation des infos de factures dans la base
     * Creation de la facture
     *
     */	      
    if (!$error)
      {
	if ($verbose) dol_syslog("FacturationEmission::facture_contrat Création facture pour $soc->nom", LOG_DEBUG);
	
	$fac = new Facture($db, $soc->id);
	$cancel_facture = 1;
	$fac->date = $datetime;
	$fac->cond_reglement_id = 1;
	$fac->remise_percent = 0;
	
	$facid = $fac->create($user);
	
	if ($facid > 0)
	  {
	    
	  }
	else
	  {
	    dol_syslog("FacturationEmission::facture_contrat Erreur création objet facture erreur : $facid",LOG_ERR);
	    array_push($this->messages, array('error',"Erreur création objet facture erreur : $facid"));
	    $error = 16;
	  }		  
      }
    
    /*
     * Lecture des différentes lignes rattachées
     *
     */
    if (!$error)
      {	  	      
	foreach ($factel_ids as $factel_id)
	  {
	    /* Lecture de la facture téléphonique */
	  
	    $factel = new FactureTel($db);
	    if ($factel->fetch($factel_id) == 0)
	      {
	      
	      }
	    else
	      {
		dol_syslog("FacturationEmission::facture_contrat Erreur lecture facture téléphonique $factel_id");
		array_push($this->messages, array('error',"Erreur lecture facture téléphonique (id=$factel_id)"));
		$error = 17;
	      }
	  
	    /* Lecture de la ligne correspondante */
	  
	    $ligne = new LigneTel($db);
	    if ($ligne->fetch($factel->ligne) == 1)
	      {
	      
	      }
	    else
	      {
		dol_syslog("ERREUR lecture ligne $factel->ligne");
		$error = 18;
	      }
	  
	    if (!$error && $ligne->facturable) /* Test si on doit facturer ou non la ligne */
	      {	      
		$cancel_facture = 0;
	      
		/* Création du lien entre les factures */
	      
		if ($factel->affect_num_facture_compta($facid) == 0)
		  {
		  
		  }
		else
		  {
		    $error = 19;
		  }
	      
		$soca = new Societe($db);
		$soca->fetch($ligne->client_id);
	      	      
		/* Insertion des lignes de factures */
		$libelle = "";

		if (strlen(trim($ligne->code_analytique)) > 0)
		  {
		    $libelle .= "".$ligne->code_analytique." ";
		  }

		$dm = mktime (1,1,1,strftime("%m",$datetime), -1, strftime("%Y",$datetime));

		$libelle .= "Communications téléphoniques de la ligne $ligne->numero";
		$libelle .= " mois de ".strftime("%B %Y",$dm);

		if (!$error)
		  {
		    $ventil = 0 ;
		    if (defined("TELEPHONIE_COMPTE_VENTILATION"))
		      {
			if (is_numeric(TELEPHONIE_COMPTE_VENTILATION))
			  {			  
			    $ventil = TELEPHONIE_COMPTE_VENTILATION;
			  }
		      }


		    $result = $fac->addline($facid,
					    $libelle,
					    $factel->cout_vente_remise,
					    1,
					    '19.6',
					    0,
					    0,'','',$ventil);
		  }		      		  
	      }
	   	  	  
	  } /* Fin de la boucle des lignes */
      }

    /*********************************/
    /*                               */	  
    /* Ajout des services            */
    /*                               */
    /*********************************/

    if (!$error)
      {
	$sql = "SELECT s.libelle_facture, sc.montant";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service as sc";
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_service as s";
            
	$sql .= " WHERE sc.fk_contrat = ".$contrat_id;
	$sql .= " AND s.rowid = sc.fk_service";  
	$sql .= " AND sc.montant > 0";

	$resql = $db->query($sql) ;
	$ventil = 18;
	if ( $resql )
	  {
	    $num = $db->num_rows($resql);      
	    $is = 0;
	  
	    while ($is < $num)
	      {
		$row = $db->fetch_row($resql);

		$result = $fac->addline($facid,
					$row[0],
					$row[1],
					1,
					'19.6',
					0,
					0,
					0,
					'',
					$ventil);

		$is++;
	      }            
	    $db->free($resql);
	  }
	else
	  {
	    $error = 20;
	    dol_syslog("FacturationEmission Erreur $error", LOG_ERR);
	  }
      }
     
    /*********************************/
    /*                               */	  
    /* Ajout rejets de prelevements  */
    /*                               */
    /*********************************/
    if (!$error)
      {
	$sql = "SELECT pr.rowid,".$db->pdate("pr.date_rejet");
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_rejet as pr";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql .= " WHERE pl.fk_soc = ".$soc->id;
	$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";
	$sql .= " AND afacturer = 1 LIMIT 1;";
	$resql = $db->query($sql) ;
	$ventil = 10;
	if ( $resql )
	  {
	    while ($row = $db->fetch_row($resql))
	      {
		$result = $fac->addline($facid,
					"Frais pour prélèvement rejeté du ".strftime("%d/%m/%Y",$row[1]),
					15,
					1,
					'0',
					0,
					0,
					0,
					'',
					$ventil);

		$sqlu = "UPDATE ".MAIN_DB_PREFIX."prelevement_rejet as pr";
		$sqlu .= " SET afacturer=0";
		$sqlu .= " ,fk_facture=".$facid;
		$sqlu .= " WHERE rowid=".$row[0].";";

		$resqlu = $db->query($sqlu);
	      }            
	    $db->free($resql);
	  }
	else
	  {
	    $error = 21;
	    dol_syslog($db->error());
	    dol_syslog("Erreur rejet prelevement");
	  }
      }

    /*********************************/
    /*                               */	  
    /* Remise exceptionnelle         */
    /*                               */
    /*********************************/
    if (!$error)
      {
	$remise_exceptionnelle = 0;
      
	$sql = "SELECT rowid,amount,fk_user";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_client_remise";
	$sql .= " WHERE fk_client = ".$soc->id;
	$sql .= " AND fk_facture = 0";
      
	$resql = $db->query($sql) ;
	if ( $resql )
	  {
	    while ($row = $db->fetch_row($resql))
	      {
		$remise_id = $row[0];
		$remise_exceptionnelle = $row[1];
		$remise_user = $row[2];
	      }
	  }
	else
	  {
	    $error = 32;
	    dol_syslog("Erreur remise exceptionnelle");
	    dol_syslog($sql);
	  }
      
	//print "remise $remise_exceptionnelle \n";
	//print "total ".$fac->total_ht."\n";

	if ($remise_exceptionnelle > 0)
	  {

	    // Calcul valeur de remise a appliquer (remise) et reliquat
	    if ($remise_exceptionnelle > ($fac->total_ht * 0.9))
	      {
		$remise = floor($fac->total_ht * 0.9);
		$reliquat = ($remise_exceptionnelle - $remise);
	      }
	    else
	      {
		$remise = $remise_exceptionnelle;
		$reliquat=0;
	      }
	  
	    $result_insert = $fac->addline($fac->id,
					   addslashes('Remise exceptionnelle'),
					   (0 - $remise),
					   1,
					   '19.6');
	    if ($result_insert < 0)
	      {
		$error = 33;
	      }
	  
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.'telephonie_client_remise';
	    $sql .= ' SET fk_facture = '.$fac->id;
	    $sql .= " ,amount = '".ereg_replace(',','.',$remise)."'";
	    $sql .= ' WHERE rowid ='.$remise_id;
	    $sql .= ' AND fk_client ='. $soc->id;
	  
	    if (! $db->query( $sql))
	      {
		$error = 34;
	      }
	  
	    if ($reliquat > 0 && $error == 0)
	      {
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'telephonie_client_remise';
		$sql .= ' (fk_client, datec, amount, fk_user) ';
		$sql .= ' VALUES ';
		$sql .= ' ('.$soc->id;
		$sql .= ' ,now()';
		$sql .= " ,'".ereg_replace(',','.',$reliquat)."'";
		$sql .= ' ,'.$remise_user;
		$sql .= ')';
	      
		if (! $db->query( $sql) )
		  {
		    $error = 35;
		  }
	      }
	  }
      }
    /*********************************/
    /*                               */	  
    /* Prestas annexes               */
    /*                               */
    /*********************************/
    if (!$error)
      {
	$prestas = 0;
      
	$sql = "SELECT rowid,amount,libelle";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_client_presta";
	$sql .= " WHERE fk_client = ".$soc->id;
	$sql .= " AND fk_facture = 0";
      
	$resql = $db->query($sql) ;
	if ( $resql )
	  {
	    while ($row = $db->fetch_row($resql))
	      {
		$presta_id[$prestas] = $row[0];
		$presta_amount[$prestas] = $row[1];
		$presta_libelle[$prestas] = $row[2];
		$prestas++;
	      }
	  }
	else
	  {
	    $error = 36;
	  }
      
	if ($prestas > 0)
	  {
	    $i = 0;
	    while ($i < $prestas)
	      {
		$result_insert = $fac->addline($fac->id,
					       addslashes($presta_libelle[$i]),
					       $presta_amount[$i],
					       1,
					       '19.6');
		if ($result_insert < 0)
		  {
		    $error = 37;
		  }
	      
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'telephonie_client_presta';
		$sql .= ' SET fk_facture = '.$fac->id;
		$sql .= ' WHERE rowid ='.$presta_id[$i];
		$sql .= ' AND fk_client ='. $soc->id;
	      
		if (! $db->query( $sql))
		  {
		    $error = 38;
		  }
		$i++;
	      }
	  }
      }


    /*********************************/
    /*                               */	  
    /* Validation de la facture      */
    /*                               */
    /*********************************/
    if ($this->do_validate_facture)
      {
	if (!$error && !$cancel_facture)
	  {
	    if ($verbose) dol_syslog("Validation de la facture : $facid");
	  
	    $y = substr($year, -1);
	    $m = substr("00".$month, -2);
	  
	    if ( $fac->set_valid($user, $soc) )
	      {
		$valid_ok = 1;
	      }
	    else
	      {
		$valid_ok = 0;
		$error = 5;
	      }      
	  }
      }
    else
      {
	$valid_ok = 1;
      }

    /**************************************/
    /*                                    */
    /* Factures détaillées                */
    /*                                    */
    /**************************************/

    if (!$error && !$cancel_facture)
      {
	foreach ($factel_ids as $factel_id)
	  {
	    /* Lecture de la facture téléphonique */
	  
	    $factel = new FactureTel($db);
	    if ($factel->fetch($factel_id) == 0)
	      {
	      
	      }
	    else
	      {
		dol_syslog("ERREUR lecture facture téléphonique $factel_id");
		$error = 17;
	      }
	  
	    /* Lecture de la ligne correspondante */
	  
	    $ligne = new LigneTel($db);
	    if ($ligne->fetch($factel->ligne) == 1)
	      {
	      
	      }
	    else
	      {
		dol_syslog("ERREUR lecture ligne $factel->ligne");
		$error = 18;
	      }

	    /* Facture détaillée standard */

	    if (!$error)
	      {
		$facok = 0;
	      
		// Différents modèles de factures détaillées
	      
		$modele = "standard";
		if (strlen($ligne->pdfdetail) > 0)
		  {
		    $modele = $ligne->pdfdetail;
		  }
	      
		require_once (DOL_DOCUMENT_ROOT."/telephonie/pdf/pdfdetail_".$modele.".modules.php");
		$class = "pdfdetail_".$modele;
	      
		$facdet = new $class($db, $ligne, $year, $month, $factel);
	      
		if ($facdet->write_pdf_file($factel, $factel->ligne) == 0)
		  {
		    $facok++;
		  }
		else
		  {
		    dol_syslog("FacturationEmission::facture_contrat ERREUR lors de Génération du pdf détaillé ($modele)");
		    $error = 19;
		  } 
	      }

	    /* Factures détaillées autres */

	    if (!$error)
	      {
		// Recherche des factures détaillées
		// et copie dans le répertoire de la facture
		// ID facture telephonique $factel_id
		$fdefacid = substr('0000'.$factel_id, -4);
		$fdedir = DOL_DATA_ROOT.'/telephonie/facture/';

		$fdedir .= substr($fdefacid,0,1)."/";
		$fdedir .= substr($fdefacid,1,1)."/";
		$fdedir .= substr($fdefacid,2,1)."/";
		$fdedir .= substr($fdefacid,3,1)."/";
	
		$fname = $fdedir . $fdefacid . "-detail.xls";

		if (file_exists($fname))
		  {
		    $fdefac = new Facture($db,"",$factel->fk_facture);
		    $fdefac->fetch($factel->fk_facture);  

		    $dest = FAC_OUTPUTDIR ."/".$fdefac->ref."/".$fdefac->ref."-".$fdefacid."-".$ligne->numero."-detail.xls";

		    copy($fname, $dest);
		  }
	      }
	  }	  	
      }

    /*********************************/
    /*                               */
    /* Creation du pdf de la facture */
    /*                               */
    /*********************************/
  
    if (!$error && !$cancel_facture && $valid_ok == 1)
      {
	if ($verbose) dol_syslog("Génération du pdf facture : $facid");
      
	$fac->fetch($facid);
	$fac->fetch_client();
	$fac->client->load_ban();
      
	$message = "";
      
	if ($fac->client->bank_account->verif() && $ligne->mode_paiement == 'pre')
	  {
	    $message .= "Cette facture sera prélevée sur votre compte bancaire numéro : ";
	    $message .= $fac->client->bank_account->number."\n";
	  }
      
	if ($verbose) dol_syslog("Création du pdf facture : $facid");
      
	if (! facture_pdf_create($db, $facid, $message))
	  {
	    $error = 1;
	    //print "- ERREUR de génération du pdf de la facture\n";
	  }
      }
  
    if (!$error && !$cancel_facture)
      {
	$db->query("COMMIT");      
	/* $soc
	 * $ligne
	 */
      
	if ($contrat->facturable)
	  {
	    array_push($factures_a_mailer, $facid);
	  
	    if ($soc->verif_rib())
	      {
		array_push($factures_prev, $facid);
	      }
	    else
	      {
		dol_syslog("facture $facid non preleve, RIB incorrect");
	      }	  
	  }
      
	if ($verbose) dol_syslog("Commit de la transaction");;
      }
    else
      {
	$db->query("ROLLBACK");
	dol_syslog("ROLLBACK de la transaction $error");
      }    
  }
  /**
     \brief Emissions des demandes de prelevement
     \param db database handler
     \param user Object User
     \param factures_prev Array factures id
   */
  function _prelevements($db, $user, $factures_prev)
  { 
    dol_syslog("FacturationEmission::_prelevements Debut demande de prelevement", LOG_DEBUG);
    dol_syslog("FacturationEmission::_prelevements Nombre de factures ".sizeof($factures_prev),LOG_DEBUG); 
    if (sizeof($factures_prev) > 0)
      {
	foreach ($factures_prev as $fac)
	  {
	    $fact = new Facture($db);
	    $fact->fetch($fac);
	    $fact->mode_reglement(3);
	    $fact->demande_prelevement($user);
	  }
      }
    dol_syslog("FacturationEmission::_prelevements Fin demande de prelevement", LOG_DEBUG);
  }


  function _emails($db, $user, $contrat_id, $factures_a_mailer)
  {
    /********************************************************************
     *                                                                  *
     * Envoi des factures par emails                                    *
     *                                                                  *
     ********************************************************************/
    if (sizeof($factures_a_mailer) > 0)
      {
	foreach ($factures_a_mailer as $fac)
	  {
	    $fact = new Facture($db);
	    $fact->fetch($fac);

	    $contrat = new TelephonieContrat($db);
	    $contrat->fetch($contrat_id);

	    $emails = $contrat->get_contact_facture();

	    $ligne = new LigneTel($db);

	    if ($ligne->fetch_by_facture_number($fact->id) == 0)
	      {

	      }

	    if (sizeof($emails > 0))
	      {
		$sendto = "";
		for ($k = 0 ; $k < sizeof($emails) ; $k++)
		  {
		    $sendto .= html_entity_decode($emails[$k]) . ",";
		  }
		$sendto = substr($sendto,0,strlen($sendto) - 1);

		dol_syslog("[EM] Envoi email à ".html_entity_decode($sendto) );

		$subject = ereg_replace("#FACREF#",$fact->ref,TELEPHONIE_MAIL_FACTURATION_SUJET);
		$subject = ereg_replace("#CONTRAT#",$contrat->ref, $subject);

		$from = TELEPHONIE_EMAIL_FACTURATION_EMAIL;
	      
		$message = "Bonjour,\n\n";
		$message .= "Veuillez trouver ci-joint notre facture numéro $fact->ref du ".strftime("%d/%m/%Y",$fact->date).".";

		$message .= "\nEgalement joint à ce mail le détails de vos communications.\n\n";

		$message .= TELEPHONIE_MAIL_FACTURATION_SIGNATURE;
	      
	      
		$mailfile = new DolibarrMail($subject,
					     $sendto,
					     $from,
					     $message);
	      
		$mailfile->addr_bcc = TELEPHONIE_EMAIL_FACTURATION_EMAIL;

		$arr_file = array();	      
		$arr_name = array();
		$arr_mime = array();

		$facfile = FAC_OUTPUTDIR . "/" . $fact->ref . "/" . $fact->ref . ".pdf";

		/*
		 * Join la facture
		 */
		array_push($arr_file, $facfile);
		array_push($arr_mime, "application/pdf");
		array_push($arr_name, $fact->ref.".pdf");

		$dir = FAC_OUTPUTDIR . "/" . $fact->ref . "/";

		$handle=opendir(FAC_OUTPUTDIR . "/" . $fact->ref . "/");
		/*
		 * Joint les détails
		 *
		 */
		while (($file = readdir($handle))!==false)
		  {
		    if (is_readable($dir.$file) && substr($file, -11) == '-detail.pdf')
		      {
			array_push($arr_file, $dir.$file);
			array_push($arr_mime, "application/pdf");
			array_push($arr_name, $file);
		      }
		    if (is_readable($dir.$file) && substr($file, -11) == '-detail.xls')
		      {
			array_push($arr_file, $dir.$file);
			array_push($arr_mime, "application/vns.ms-excel");
			array_push($arr_name, $file);
		      }
		  }
	      
		$mailfile->PrepareFile($arr_file, $arr_mime, $arr_name);
	      
		if ( $mailfile->sendfile() )
		  {
		
		    for ($kj = 0 ; $kj < sizeof($contrat->contact_facture_id) ; $kj++)
		      {
			$sendtoid = $contrat->contact_facture_id[$kj];
		      
			$sendtox = $emails[$kj];
			  
			$actioncode=9;
			$actionmsg="Envoyée à $sendtox";
			$actionmsg2="Envoi Facture par mail";
		    
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), '$actioncode' ,'$fact->socid' ,'$actionmsg','$fact->id','$sendtoid','$user->id', '$actionmsg2',100);";
		     
			if (! $db->query($sql) )
			  {
			    //print $db->error();
			  }
			else
			  {
			    //print "TOTO".$sendto. " ". $sendtoid ." \n";
			  }
		    
		      }
		  
		  }
	      }
	    else
	      {
		dol_syslog("Aucun email trouvé");
	      }
	  }
      }
  }


}
?>
