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

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

$error = 0;

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
 * Affiche le nombre de ligne a facturer
 *
 *********************************************************/

$sql = "SELECT count(*)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";;
  
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $row = $db->fetch_row();

  dolibarr_syslog("Communications à traiter ".$row[0]);
  $db->free();
}
else
{
  $error++;
}

/**
 *
 * Lectures des différentes lignes dans la table d'import
 *
 */

if (!$error)
{
  $user = new user($db,1);
  
  $sql = "SELECT distinct(t.ligne)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr as t";
  $sql .= " ORDER BY ligne ASC";
  
  $clients = array();
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();
	  
	  $clients[$i] = $row[0];
	  
	  $i++;
	}            
      $db->free();
      dolibarr_syslog("$i lignes trouvées");
    }
  else
    {
      $error = 1;
    }
}

/***************************************
 *
 * Traitements
 *
 *
 */

if (!$error)
{

  foreach ($clients as $client)
    {
      $error = 0;
      $ligne = new LigneTel($db);

      $db->query("BEGIN");

      dolibarr_syslog("Ligne $client");
      dolibarr_syslog("Begin de la transaction");

      
      if ($ligne->fetch($client) > 0 )
	{
	  if ($ligne->socid == 0)
	    {
	      $error = 1;
	      print "Error ($error)\n";
	    }	  	  
	}
      else
	{
	  $error = 2;	  
	  print "Error ($error): Aucune société rattachée à la ligne : $client\n";
	}
      
      /*
       * Récupération des infos sur la sociétés
       *
       */

      $soc = new Societe($db);
      
      if (!$error &&  $soc->fetch($ligne->socid) )
	{
	  	  
	}
      else
	{
	  $error = 3;
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

	  if (calcul($client, $db, $total_achat, $total_vente, $total_fourn, $ligne, $ligne->client_comm_id) <> 0)
	    {
	      $error++;
	      dolibarr_syslog("Erreur de calcul de la facture pour la ligne $client");
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

	  $sql = "INSERT INTO llx_telephonie_facture";
	  $sql .= " (fk_ligne, ligne, date, fourn_montant, cout_achat, cout_vente, remise, cout_vente_remise, gain)";
	  
	  $sql .= " VALUES (".$ligne->id.",";
	  $sql .= "'$client','".$year."-".$month."-01',$total_fourn, $total_achat, $total_vente, $ligne->remise, $total_vente_remise, $gain)";
	  	  
	  if (! $db->query($sql))
	    {
	      $error++;
	      print "Erreur d'insertion dans llx_telephonie_facture\n";
	    }
	}

      /*
       * Suppression des données de la table d'import
       *
       */

      if (!$error)
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
	  $sql .= " WHERE ligne = $client ";

	  if (! $db->query($sql))
	    {
	      $error++;
	      dolibarr_syslog("Erreur de suppression dans llx_telephonie_import_cdr");
	    }
	}

      /*
       * Commit / Rollback SQL
       *
       */      
      
      if (!$error)
	{
	  $db->query("COMMIT");
	  dolibarr_syslog("Commit de la transaction");

	}
      else
	{
	  $db->query("ROLLBACK");
	  dolibarr_syslog("Annulation de la transaction");
	}

    } /* fin de la boucle */

  /*
   *
   *
   */
}

$db->close();

dolibarr_syslog("Conso mémoire ".memory_get_usage() );

// FIN

/******************************************************************************
 *
 * Fonction de calcul
 *
 ******************************************************************************/

function calcul($client, $db, &$total_cout_achat, &$total_cout_vente, &$total_cout_fourn, $ligne, $client_id=0)
{
  $error = 0;

  $total   = 0;
  $nbinter = 0;
  $nbmob   = 0;
  $nbnat   = 0;
  $duree   = 0;

  $fournisseur_id = 1;

  $tarif_achat = new TelephonieTarif($db, $fournisseur_id, "achat");
  $tarif_vente = new TelephonieTarif($db, $fournisseur_id, "vente", $client_id);

  $comms = array();

  $sql = "SELECT t.idx, t.ligne, t.montant, t.duree, t.num, t.date, t.heure, t.dest";
  $sql .= " , t.fichier, t.fk_fournisseur";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr as t";
  $sql .= " WHERE t.ligne = ".$client;
    
  if ( $db->query($sql) )
    {
      $num_sql = $db->num_rows();
      $i = 0;
      
      while ($i < $num_sql && $error == 0)
	{
	  $objp = $db->fetch_object($i);

	  $comm = new CommunicationTelephonique();

	  $comm->index       = $objp->idx;
	  $comm->ligne       = $objp->ligne;
	  $comm->date        = $objp->date;
	  $comm->heure       = $objp->heure;
	  $comm->duree       = $objp->duree;
	  $comm->dest        = $objp->dest;
	  $comm->numero      = $objp->num;
	  $comm->montant     = $objp->montant;
	  $comm->fichier_cdr = $objp->fichier;
	  $comm->fournisseur = $objp->fk_fournisseur;
	 
	  $comms[$i] = $comm;

	  $i++;
	}

      $db->free();
    }
  else
    {
      $error++;
      dolibarr_syslog("Erreur dans Calcul() Problème SQL");
    }

  for ($ii = 0 ; $ii < $num_sql ; $ii++)
    {
      $comm = $comms[$ii];

      $error = $error + $comm->cout($tarif_achat, $tarif_vente, $ligne);

      $total_cout_fourn = $total_cout_fourn + $comm->montant;
      $total_cout_achat = $total_cout_achat + $comm->cout_achat;
      $total_cout_vente = $total_cout_vente + $comm->cout_vente;

      $error = $error + $comm->logsql($db);
    }

  return $error;
}



?>
