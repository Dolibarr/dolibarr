<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

class BonPrelevement
{
  var $db;

  var $date_echeance;
  var $raison_sociale;
  var $reference_remise;
  var $emetteur_code_guichet;
  var $emetteur_numero_compte;
  var $emetteur_code_etablissement;
  var $total;

  function BonPrelevement($DB, $filename) 
    {
      $error = 0;
      $this->db = $DB;

      $this->file = fopen ($filename,"w");
      
      $this->date_echeance = time();
      $this->raison_sociale = "";
      $this->reference_remise = "";

      $this->emetteur_code_guichet = "";
      $this->emetteur_numero_compte = "";
      $this->emetteur_code_etablissement = "";

      $this->factures = array();

      $this->numero_national_emetteur = "";

      $this->methodes_trans = array();

      $this->methodes_trans[0] = "Internet";

      return 1;
    }
  /*
   *
   *
   *
   */
  function AddFacture($facture_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
  {
    $result = 0;
    $ligne_id = 0;

    $result = $this->AddLigne($ligne_id, $client_id, $client_nom, 
			      $amount, $code_banque, $code_guichet, $number);
    
    if ($result == 0)
      {	
	if ($ligne_id > 0)
	  {	    
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_facture ";
	    $sql .= " (fk_facture,fk_prelevement_lignes)";
	    $sql .= " VALUES (".$facture_id.",".$ligne_id.")";
	    
	    if ($this->db->query($sql))
	      {      
		$result = 0;	
	      }
	    else
	      {
		$result = -1;
		dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
	      }
	  }
	else
	  {
	    $result = -2;
	    dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
	  }
      }
    else
      {
	$result = -3;
	dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
      }

    return $result;

  }
  /*
   *
   *
   */
  function AddLigne(&$ligne_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
  {
    $result = -1;
    $concat = 0;

    if ($concat == 1)
      {
	/*
	 * On aggrège les lignes 
	 */
	$sql = "SELECT rowid FROM  ".MAIN_DB_PREFIX."prelevement_lignes";
	$sql .= " WHERE fk_prelevement_bons".$this->id;
	$sql .= " AND fk_soc       =".$client_id;
	$sql .= " AND code_banque  ='".$code_banque."'";
	$sql .= " AND code_guichet ='".$code_guichet."'";
	$sql .= " AND number       ='".$number."'";

	if ($this->db->query($sql))
	  {
	    $num = $this->db->num_rows();
	  }
	else
	  {
	    $result = -1;
	  }
      }
    else
      {
	/*
	 * Pas de d'agrégation
	 */
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_lignes (fk_prelevement_bons";
	$sql .= " , fk_soc , client_nom ";
	$sql .= " , amount";
	$sql .= " , code_banque , code_guichet , number)";

	$sql .= " VALUES (".$this->id;
	$sql .= ",".$client_id.",'".addslashes($client_nom)."'";
	$sql .= ",'".ereg_replace(",",".",$amount)."'";
	$sql .= ", '$code_banque', '$code_guichet', '$number')";

	if ($this->db->query($sql))
	  {
	    $ligne_id = $this->db->last_insert_id();
	    $result = 0;
	  }
	else
	  {
	    dolibarr_syslog("BonPrelevement::AddLigne Erreur -2");
	    $result = -2;
	  }

      }

    return $result; 
  }
  /*
   *
   *
   */
  function Fetch($rowid)
  {
    $sql = "SELECT p.rowid, p.ref, p.amount, p.note, p.credite";
    $sql .= ",".$this->db->pdate("p.datec")." as dc";

    $sql .= ",".$this->db->pdate("p.date_trans")." as date_trans";
    $sql .= " , method_trans, fk_user_trans";
    $sql .= ",".$this->db->pdate("p.date_credit")." as date_credit";
    $sql .= " , fk_user_credit";

    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";

    $sql .= " WHERE p.rowid=".$rowid;
      
    $result=$this->db->query($sql);
    if ($result)
      {
	if ($this->db->num_rows($result))
	  {
	    $obj = $this->db->fetch_object();
	    
	    $this->id                 = $obj->rowid;
	    $this->ref                = $obj->ref;
	    $this->amount             = $obj->amount;
	    $this->note               = stripslashes($obj->note);
	    $this->datec              = $obj->dc;
	    $this->credite            = $obj->credite;
	    
	    $this->date_trans         = $obj->date_trans;
	    $this->method_trans       = $obj->method_trans;
	    $this->user_trans         = $obj->fk_user_trans;

	    $this->date_credit         = $obj->date_credit;
	    $this->user_credit         = $obj->fk_user_credit;

	    return 0;
	  }
	else
	  {
	    dolibarr_syslog("BonPrelevement::Fetch Erreur aucune ligne retournée");
	    return -1;
	  }
      }
    else
      {
	dolibarr_syslog("BonPrelevement::Fetch Erreur ");
	dolibarr_syslog($sql);
	return -2;
      }
  }
  /**
   *
   *
   */
  function set_credite()
  {
    $error == 0;

    if ($this->db->begin())
      {
	$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
	$sql .= " SET credite = 1";
	$sql .= " WHERE rowid=".$this->id;
      
	$result=$this->db->query($sql);
	if (! $result)
	  {
	    dolibarr_syslog("bon-prelevement::set_credite Erreur 1");
	    $error++;
	  }

	if ($error == 0)
	  {
	    /**
	     *
	     *
	     *
	     */
	    $facs = array();
	    $facs = $this->_get_list_factures();
	    
	    for ($i = 0 ; $i < sizeof($facs) ; $i++)
	      {	    
		$fac = new Facture($this->db);
		
		/* Tag la facture comme impayée */
		dolibarr_syslog("BonPrelevement::set_credite set_payed fac ".$facs[$i]);
		$fac->set_payed($facs[$i]);
	      }
	  }

	if ($error == 0)
	  {
	    
	    $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes ";
	    $sql .= " SET statut  = 2";
	    $sql .= " WHERE fk_prelevement_bons=".$this->id;
	    
	    if (! $this->db->query($sql))
	      {
		dolibarr_syslog("BonPrelevement::set_infocredit Erreur 1");
		$error++;
	      }
	  }

	/*
	 * Fin de la procédure
	 *
	 */
	if ($error == 0)
	  {
	    $this->db->commit();
	    return 0;
	  }
	else
	  {

	    $this->db->rollback();
	    dolibarr_syslog("BonPrelevement::set_credite ROLLBACK ");

	    return -1;
	  }
	
	
      }
    else
      {
	
	dolibarr_syslog("BonPrelevement::set_credite Ouverture transaction SQL impossible ");
	return -2;
      }
  }
  /**
   *
   *
   */
  function set_infocredit($user, $date)
  {
    $error == 0;

    if ($this->db->begin())
      {
	$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
	$sql .= " SET fk_user_credit = ".$user->id;
	$sql .= " , statut = 2";
	$sql .= " , date_credit='".$this->db->idate($date)."'";
	$sql .= " WHERE rowid=".$this->id;
      	$sql .= " AND statut = 1";

	if (! $this->db->query($sql))
	  {
	    dolibarr_syslog("BonPrelevement::set_infocredit Erreur 1");
	    $error++;
	  }

	/*
	 * Fin de la procédure
	 *
	 */
	if ($error == 0)
	  {
	    $this->db->commit();
	    return 0;
	  }
	else
	  {

	    $this->db->rollback();
	    dolibarr_syslog("bon-prelevment::set_infotrans ROLLBACK ");

	    return -1;
	  }		
      }
    else
      {
	
	dolibarr_syslog("bon-prelevement::set_infocredit Ouverture transaction SQL impossible ");
	return -2;
      }
  }
  /**
   *
   *
   */
  function set_infotrans($user, $date, $method)
  {
    $error == 0;

    if ($this->db->begin())
      {

	$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
	$sql .= " SET fk_user_trans = ".$user->id;
	$sql .= " , date_trans='".$this->db->idate($date)."'";
	$sql .= " , method_trans=".$method;
	$sql .= " , statut = 1";
	$sql .= " WHERE rowid=".$this->id;
      	$sql .= " AND statut = 0";
      
	if (! $this->db->query($sql))
	  {
	    dolibarr_syslog("bon-prelevement::set_infotrans Erreur 1");
	    dolibarr_syslog($this->db->error());
	    $error++;
	  }

	/*
	 * Fin de la procédure
	 *
	 */
	if ($error == 0)
	  {
	    $this->db->commit();
	    return 0;
	  }
	else
	  {
	    $this->db->rollback();
	    dolibarr_syslog("BonPrelevement::set_infotrans ROLLBACK ");

	    return -1;
	  }		
      }
    else
      {
	
	dolibarr_syslog("BonPrelevement::set_infotrans Ouverture transaction SQL impossible ");
	return -2;
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
       * Renvoie toutes les factures présente
       * dans un bon de prélèvement
       */
      
      $sql = "SELECT fk_facture";
      $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture";
      $sql .= " WHERE fk_prelevement = ".$this->id;

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
	  dolibarr_syslog("Bon-Prelevement::_get_list_factures Erreur");
	}

      return $arr;
    }

  /** 
   * Génération d'un bon de prélèvement
   *
   */
  function Generate()
  {
    $result = -1;
    /*
     * En-tete Emetteur
     */

    $this->EnregEmetteur();

    /*
     * Lignes
     */
    $this->total = 0;

    $sql = "SELECT rowid, client_nom, code_banque, code_guichet, number, amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes";
    $sql .= " WHERE fk_prelevement_bons = ".$this->id;

    $i = 0;

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();

	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $this->EnregDestinataire($row[0], 
				     $row[1], 
				     $row[2], 
				     $row[3], 
				     $row[4], 
				     $row[5]);

	    $this->total = $this->total + $row[5];

	    $i++;
	  }
      }
    else
      {
	$result = -2;
      }
    /*
    $nbfactures = sizeof($this->factures);
    for ($i = 0 ; $i < $nbfactures ; $i++)
      {
	$fac = new Facture($this->db);
	$fac->fetch($this->factures[$i]);
	$fac->fetch_client();
	$fac->client->rib();
	if ($fac->client->bank_account->verif()) {
	    $this->total = $this->total + $fac->total_ttc;
	    $this->EnregDestinataire($fac);
	  }else{
	    print $fac->client->bank_account->error_message;
	    print $fac->client->nom; }
      }
    */

    /*
     * Pied de page total
     */

    $this->EnregTotal($this->total);

    fclose($this->file);

    return $result;
  }


  /*
   * Enregistrements destinataires
   *
   *
   */

  function EnregDestinataire($rowid, $client_nom, $rib_banque, $rib_guichet, $rib_number, $amount)
  {
    fputs ($this->file, "06");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Date d'échéance C1

    fputs ($this->file, "       "); 
    fputs ($this->file, strftime("%d%m", $this->date_echeance));
    fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));
    
    // Raison Sociale Destinataire C2

    fputs ($this->file, substr($client->nom. "                           ",0,24));

    // Domiciliation facultative D1

    fputs ($this->file, substr("                                    ",0,24));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,8));
    
    // Code Guichet  D3

    fputs ($this->file, $rib_guichet);

    // Numero de compte D4

    fputs ($this->file, substr("000000000000000".$rib_number, -11));

    // Zone E Montant
 
    $montant = (round($amount,2) * 100);

    fputs ($this->file, substr("000000000000000".$montant, -16));

    // Libellé F
 
    fputs ($this->file, substr("*".$this->ref.$rowid."                                   ",0,13));
    fputs ($this->file, substr("                                        ",0,18));

    // Code établissement G1

    fputs ($this->file, $rib_banque);

    // Zone Réservée G2
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");
  }


  /*
   * Enregistrements destinataires
   *
   *
   */

  function EnregDestinataireVersion1($fac)
  {
    fputs ($this->file, "06");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Date d'échéance C1

    fputs ($this->file, "       "); 
    fputs ($this->file, strftime("%d%m", $this->date_echeance));
    fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));
    
    // Raison Sociale Destinataire C2

    fputs ($this->file, substr($fac->client->nom. "                           ",0,24));

    // Reference de la remise créancier D1

    fputs ($this->file, substr("                                    ",0,24));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,8));
    
    // Code Guichet  D3

    fputs ($this->file, $fac->client->bank_account->code_guichet);

    // Numero de compte D4

    fputs ($this->file, substr("000000000000000".$fac->client->bank_account->number, -11));

    // Zone E Montant
 
    $montant = (round($fac->total_ttc,2) * 100);

    fputs ($this->file, substr("000000000000000".$montant, -16));

    // Libellé F
 
    fputs ($this->file, substr("*".$fac->ref."                                   ",0,13));
    fputs ($this->file, substr("                                        ",0,18));

    // Code établissement G1

    fputs ($this->file, $fac->client->bank_account->code_banque);

    // Zone Réservée G2
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");
  }

  /*
   *
   *
   */
  function EnregEmetteur()
  {
    fputs ($this->file, "03");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Date d'échéance C1

    fputs ($this->file, "       "); 
    fputs ($this->file, strftime("%d%m", $this->date_echeance));
    fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));
    
    // Raison Sociale C2

    fputs ($this->file, substr($this->raison_sociale. "                           ",0,24));

    // Reference de la remise créancier D1 sur 7 caractéres

    fputs ($this->file, substr($this->reference_remise. "                           ",0,7));

    // Zone Réservée D1-2
 
    fputs ($this->file, substr("                                    ",0,17));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,2));
    fputs ($this->file, "E");
    fputs ($this->file, substr("                             ",0,5));
    
    // Code Guichet  D3

    fputs ($this->file, $this->emetteur_code_guichet);

    // Numero de compte D4

    fputs ($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

    // Zone Réservée E
 
    fputs ($this->file, substr("                                        ",0,16));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,31));

    // Code établissement

    fputs ($this->file, $this->emetteur_code_etablissement);

    // Zone Réservée G
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");

  }

  /*
   * Pied de page
   *
   */


  function EnregTotal($total)
  {
    fputs ($this->file, "08");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Réservé C1

    fputs ($this->file, substr("                           ",0,12));

    
    // Raison Sociale C2

    fputs ($this->file, substr("                           ",0,24));

    // D1

    fputs ($this->file, substr("                                    ",0,24));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,8));
    
    // Code Guichet  D3

    fputs ($this->file, substr("                             ",0,5));

    // Numero de compte D4

    fputs ($this->file, substr("                             ",0,11));
    
    // Zone E Montant
 
    $montant = ($total * 100);

    fputs ($this->file, substr("000000000000000".$montant, -16));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,31));

    // Code établissement

    fputs ($this->file, substr("                                        ",0,5));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");
  }
}
?>
