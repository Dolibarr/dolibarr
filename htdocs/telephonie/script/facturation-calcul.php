<?PHP
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
 *
 * Script de calcul de la facturation
 * - Lit les entrées dans la table import_cdr
 * - Verifie que tous les tarifs sont dispos
 * - Importe les lignes dans llx_communications_details
 * - Calcul la facture téléphonique par ligne
 */

/**
   \file       htdocs/telephonie/script/facturation-calcul.php
   \ingroup    telephonie
   \brief      Calcul des factures
   \version    $Revision$
*/


require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.class.php");


$error = 0;
$nbcommit = 0;
$datetime = time();

$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

/*
 * On facture les communications du mois précédent
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
  
$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $row = $db->fetch_row($resql);

  dol_syslog("Communications à traiter ".$row[0]);
  $db->free($resql);
}
else
{
  $error = 1;
  dol_syslog("Erreur ".$error);
}

/**********************************************************
*
*
*
***********************************************************/

$sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facture";

$resql = $db->query($sql);
  
if ( $resql )
{
  $row = $db->fetch_row($resql);

  dol_syslog("Max rowid avant facture ".$row[0]);
  $db->free($resql);
}
else
{
  $error = 2;
  dol_syslog("Erreur ".$error);
}

/**
 *
 * Lectures des différentes lignes dans la table d'import
 *
 */

if (!$error)
{
  $user = new user($db,1);
  
  $sql = "SELECT distinct(t.fk_ligne)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr as t";
  $sql .= " ORDER BY fk_ligne ASC";
  
  $lines_keys = array();
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();
	  
	  $lines_keys[$i] = $row[0];
	  
	  $i++;
	}            
      $db->free();
      dol_syslog(sizeof($lines_keys)." lignes trouvées");
    }
  else
    {
      $error = 3;
      dol_syslog("Erreur ".$error);
    }
}




/**********************************************************
*
* Création d'un batch de facturation
*
***********************************************************/

if (sizeof($lines_keys) > 0)
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_facturation_batch";
  $sql .= " (date_batch) VALUES (now())";
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $batch_id = $db->last_insert_id(MAIN_DB_PREFIX."telephonie_facturation_batch");
      
      dol_syslog("Batch ID ".$batch_id);
    }
  else
    {
      $error = 20;
      dol_syslog("Erreur ".$error);
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
      $ligne = new LigneTel($db);

      if ( $db->query("BEGIN") )
	{
	  if ($ligne->fetch_by_id($line_key) > 0 )
	    {
	      if ($ligne->socid == 0)
		{
		  $error = 4;
		  dol_syslog("Error ($error)");
		}	  	  
	    }
	  else
	    {

	      $error = 5;	  
	      dol_syslog("Error ($error): Aucune société rattachée à la ligne : $line_key");
	    }

	  
	  /*
	   * Récupération des infos sur la sociétés
	   *
	   */      
	  if (!$error )
	    {	      
	      $soc = new Societe($db);
	      if ( $soc->fetch($ligne->socid) )
		{
		  
		}
	      else
		{
		  $error = 6;
		  dol_syslog("Error ($error)");
		}
	    }
	  
	  /*
	   *
	   * Création d'une facture de telephonie si la ligne est facturable
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
	      
	      if ($db->query($sql))
		{
		  $facid = $db->last_insert_id(MAIN_DB_PREFIX."telephonie_facture");
		}
	      else
		{
		  $error++;
		  dol_syslog("Erreur d'insertion dans llx_telephonie_facture");
		  dol_syslog($db->error());
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

	      if (calcul($db, $ligne, $facid, $total_achat, $total_vente, $total_fourn) <> 0)
		{
		  $error++;
		  dol_syslog("Erreur de calcul de la facture pour la ligne $line_key $ligne->numero");
		}	  
	    }	  
	  
	  /*
	   *
	   * Insertion des données dans la base
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
	      
	      if ($db->query($sql))
		{
		  
		}
	      else
		{
		  $error++;
		  dol_syslog("Erreur de mise à jour dans llx_telephonie_facture");
		  dol_syslog($db->error());
		  dol_syslog($sql);
		}
	    }
	  
	  /*
	   * Suppression des données de la table d'import
	   *
	   */
	  
	  if (!$error)
	    {
	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
	      $sql .= " WHERE fk_ligne = $line_key ";
	      
	      if (! $db->query($sql))
		{
		  $error++;
		  dol_syslog("Erreur de suppression dans llx_telephonie_import_cdr");
		}
	    }
	  
	  /*
	   * Commit / Rollback SQL
	   *
	   */      
	  
	  if (!$error)
	    {
	      $db->query("COMMIT");
	      $nbcommit++;
	      dol_syslog("Ligne $ligne->numero - COMMIT");
	    }
	  else
	    {
	      $db->query("ROLLBACK");
	      dol_syslog("Ligne $ligne->numero - ROLLBACK de la transaction");	      
	    }
	}
      else
	{
	  dol_syslog("Erreur ouverture Transaction SQL");
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

$resql = $db->query($sql);
  
if ( $resql )
{
  $row = $db->fetch_row($resql);

  dol_syslog("Max rowid après facture ".$row[0]);
  $db->free($resql);
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

dol_syslog($nbcommit." facture émises");

/**********************************************************
*
*
*
***********************************************************/
$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";

$resql = $db->query($sql);
  
if ( $resql )
{
  $row = $db->fetch_row($resql);

  dol_syslog($row[0]. " communications restantes dans la table d'import");
  $db->free($resql);
}
else
{
  $error++;
}


$db->close();

dol_syslog("Fin Batch ID ".$batch_id);

// FIN

/******************************************************************************
 *
 * Fonction de calcul de la facture
 *
 ******************************************************************************/

function calcul($db, $ligne, $facture_id, &$total_cout_achat, &$total_cout_vente, &$total_cout_fourn)
{
  $error = 0;

  $total   = 0;
  $nbinter = 0;
  $nbmob   = 0;
  $nbnat   = 0;
  $duree   = 0;

  $fournisseur_id = TELEPHONIE_GRILLE_VENTE_DEFAUT_ID ;

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
  
  dol_syslog("Utilisation du tarif ".$tarif_spec." pour la ligne ".$ligne->id);

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
      dol_syslog("Erreur dans Calcul() Problème SQL");
    }

  for ($ii = 0 ; $ii < $num_sql ; $ii++)
    {
      $comm = $comms[$ii];

      $error = $error + $comm->cout($tarif_achat, $tarif_vente, $ligne, $db);

      $total_cout_fourn = $total_cout_fourn + $comm->montant;
      $total_cout_achat = $total_cout_achat + $comm->cout_achat;
      $total_cout_vente = $total_cout_vente + $comm->cout_vente;

      $error = $error + $comm->logsql($db);
    }

  return $error;
}
?>
