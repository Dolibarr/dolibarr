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
 * Génération des factures détaillées
 *
 * Génère les factures détaillées du dernier mois.
 *
 *
 */

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$error = 0;

$datetime = time();

/*
 *
 */

$sql = "SELECT max(date) FROM ".MAIN_DB_PREFIX."telephonie_facture";

$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);

  $row = $db->fetch_row($resql);

  $date = $row[0];
}
else
{
  $error++;
}

dol_syslog("Génère les factures détaillées pour : ".$date);

/*
 * Lectures de différentes factures
 * Basé sur le dernier mois de facturation
 */

if (!$error)
{
  $user = new user($db,1);
  
  $sql = "SELECT rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";
  $sql .= " WHERE date='".$date."'";
  $sql .= " AND fk_facture IS NOT NULL";
  
  $factures = array();

  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $num = $db->num_rows($resql);      
      $i = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object($resql);	  
	  $factures[$i] = $objp->rowid;	  
	  $i++;
	}            
      $db->free($resql);
      dol_syslog("$i lignes trouvées");
    }
  else
    {
      $error = 1;
      dol_syslog($db->error());
    }
}

/*
 * Traitements
 *
 */

if (!$error && sizeof($factures))
{
  $facok = 0;

   foreach ($factures as $facid)
    {
      $error = 0;

      /* Creation des factures détaillée */
	  
      $factel = new FactureTel($db);
      if ($factel->fetch($facid) == 0)
	{
	  $objligne = new LigneTel($db);
	  
	  if ($objligne->fetch($factel->ligne) == 1)    
	    {	      
	      // Différents modèles de factures détaillées
	      
	      $modele = "standard";
	      
	      require_once (DOL_DOCUMENT_ROOT."/telephonie/pdf/pdfdetail_".$modele.".modules.php");
	      $class = "pdfdetail_".$modele;
	      	      
	      $facdet = new $class($db, $ligne, $year, $month, $factel);
	      
	      if ($facdet->write_pdf_file($factel, $factel->ligne) == 0)
		{
		  $facok++;
		}
	      else
		{
		  dol_syslog("ERREUR lors de Génération du pdf détaillé");
		  $error = 19;
		}
	    }
	}
    }

   dol_syslog(sizeof($factures)." factures traitées");
   dol_syslog($facok." factures générées");
}

$db->close();
?>
