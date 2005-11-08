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
 * Script de vérification avant facturation
 */

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

$error = 0;

$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
  
if ( $db->query($sql) )
{
  $row = $db->fetch_row();
  print $row[0]." lignes de communications\n";
}

/*******************************************************************************
 *
 * Verifie la présence des tarifs adequat
 *
 */

$tarif_achat = new TelephonieTarif($db, 1, "achat");
$tarif_vente = new TelephonieTarif($db, 1, "vente");

$sql = "SELECT distinct(num) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";

$resql = $db->query($sql);
  
if ( $resql )
{
  $nums = $db->num_rows($resql);

  $i = 0;

  while($i < $nums)
    {
      $row = $db->fetch_row($resql);

      $numero = $row[0];

      /* Reformatage du numéro */

      if (substr($numero,0,2) == '00') /* International */
	{
	}     
      elseif (substr($numero,0,2) == '06') /* Telephones Mobiles */
	{	
	  $numero = "0033".substr($numero,1);
	}
      elseif (substr($numero,0,4) == substr($objp->client,0,4) ) /* Tarif Local */
	{
	  $numero = "0033999".substr($numero, 1);
	}
      else
	{
	  $numero = "0033".substr($numero, 1);
	}	  

      /* Recherche du tarif */

      /* Numéros spéciaux */
      if (substr($numero,4,1) == 8)
	{

	}
      else
	{
	  if (! $tarif_achat->cout($numero, $x, $y, $z))
	    {
	      print "\nTarif achat manquant pour $numero\n";
	      exit(1);
	    }
	  
	  if (! $tarif_vente->cout($numero, $x, $y, $z))
	    {
	      print "\nTarif vente manquant pour $numero\n";
	      exit(1);
	    }
	}
      
      print ".";
      $i++;
    }
  $db->free();
}
print "\n";

/*
 * Verification des contrats
 */
$contrats = array();

$sql = "SELECT rowid, fk_client_comm, fk_soc, fk_soc_facture";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat ";

$resql = $db->query($sql) ;

if ( $resql )
{
  $num = $db->num_rows($resql);
  
  $i = 0;
  
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);
      
      $contrats[$i] = $objp;
      
      $i++;
    }            
  $db->free();
}
dolibarr_syslog(sizeof($contrats) ." contrats a vérifier"); 

$error = 0;

foreach ($contrats as $contrat)
{

  $sql = "SELECT rowid, fk_client_comm, fk_soc, fk_soc_facture";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
  $sql .= " WHERE fk_contrat = ".$contrat->rowid;

  $resql = $db->query($sql) ;
  
  if ( $resql )
    {
      $num = $db->num_rows($resql);      
      $i = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object($resql);
	  
	  if ($objp->fk_client_comm <> $contrat->fk_client_comm)
	    {
	      dolibarr_syslog("Erreur fk_client_comm contrat ".$contrat->rowid." ligne ".$objp->rowid);
	      $error++;
	    }
	  
	  if ($objp->fk_soc <> $contrat->fk_soc)
	    {
	      dolibarr_syslog("Erreur fk_soc contrat ".$contrat->rowid." ligne ".$objp->rowid);
	      $error++;
	    }

	  if ($objp->fk_soc_facture <> $contrat->fk_soc_facture)
	    {
	      dolibarr_syslog("Erreur fk_soc_facture contrat ".$contrat->rowid." ligne ".$objp->rowid);
	      $error++;
	    }
	  $i++;
	}            
      $db->free();
    } 
  else
    {
      dolibarr_syslog("Erreur SQL");
    }
}
dolibarr_syslog($error ." erreurs trouvées"); 
?>
