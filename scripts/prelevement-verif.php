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
 * Script de verification des demandes de prélèvement
 *
 * Vérifie que les sociétés qui doivent être prélevées ont bien un RIB correct
 *
 */
require ("../htdocs/master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php");

$error = 0;

/*
 *
 * Lectures des factures a prélever
 *
 */

$factures = array();
$factures_prev = array();

if (!$error)
{
  
  $sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
  $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";

  $sql .= " WHERE f.fk_statut = 1";
  $sql .= " AND f.rowid = pfd.fk_facture";
  $sql .= " AND f.paye = 0";
  $sql .= " AND pfd.traite = 0";
  $sql .= " AND f.total_ttc > 0";
  $sql .= " AND f.fk_mode_reglement = 3";
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row();
	  
	  $factures[$i] = $row;
	  
	  $i++;
	}            
      $db->free();
      dolibarr_syslog("$i factures à prélever");
    }
  else
    {
      $error = 1;
      dolibarr_syslog("Erreur -1");
      dolibarr_syslog($db->error());
    }
}

/*
 *
 * Vérification des clients
 *
 */

if (!$error)
{
  /*
   * Vérification des RIB
   *
   */
  $i = 0;
  dolibarr_syslog("Début vérification des RIB");

  if (sizeof($factures) > 0)
    {      
      foreach ($factures as $fac)
	{
	  $fact = new Facture($db);
	  
	  if ($fact->fetch($fac[0]) == 1)
	    {
	      $soc = new Societe($db);
	      if ($soc->fetch($fact->socidp) == 1)
		{
		  
		  if ($soc->verif_rib() == 1)
		    {

		      $factures_prev[$i] = $fac;

		      $i++;
		    }
		  else
		    {
		      dolibarr_syslog("Erreur de RIB societe $fact->socidp $soc->nom");
		    }
		}
	      else
		{
		  dolibarr_syslog("Impossible de lire la société");
		}
	    }
	  else
	    {
	      dolibarr_syslog("Impossible de lire la facture");
	    }
	}
    }
  else
    {
      dolibarr_syslog("Aucune factures a traiter");
    }
}

dolibarr_syslog(sizeof($factures_prev)." factures sur ".sizeof($factures)." seront prélevées");

$db->close();


?>
