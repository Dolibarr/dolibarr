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
 * Script de traitement des retour de commande
 */

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");

if ($verbose) dolibarr_syslog("retour-traitement");

$user = new User($db, 1);

$error = 0;

$sql = "SELECT cli,mode,situation";
$sql .= " , ".$db->pdate(date_mise_service);
$sql .= " , ".$db->pdate(date_resiliation).",motif_resiliation,commentaire,rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour ";
$sql .= " WHERE traite = 0";

if ($db->query($sql))
{
  $i = 0;
  $num = $db->num_rows();
  
  while ($i < $num)
    {
      $row[$i] = $db->fetch_row();
      
      $i++;
    }	 
} 

$n = sizeof($row);

if ($verbose) dolibarr_syslog($n . " lignes à traiter");

for ($i = 0 ; $i < $n ; $i++)
{ 
  $numero            = $row[$i][0];
  $mode              = $row[$i][1];
  $situation         = $row[$i][2];
  $date_mise_service = $row[$i][3];
  $date_resiliation  = $row[$i][4];
  $motif_resiliation = $row[$i][5];
  $commentaire       = $row[$i][6];
  $rowid             = $row[$i][7];
  
  $ligne = new LigneTel($db);
  
  if ($ligne->fetch($numero) == 1)
    {
      /*
       * Activation de la ligne
       */
      
      if ($mode == 'PRESELECTION' && 
	  $situation == 'CONFIRME' && 
	  $commentaire == 'CONFIRME PAR FT')
	{
	  
	  if ($ligne->statut == 2)
	    {
	      $statut = 3;
	      $datea = $db->idate($date_mise_service);
	      
	      if ($db->query("BEGIN"))
		{
		  $error = 0;
		  
		  if ($ligne->set_statut($user, $statut, $datea,'',1) <> 0)
		    {
		      $error++;
		    }
		  
		  if (!$error)
		    {
		      /* Tag la ligne comme traitée */
		      
		      $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_commande_retour ";
		      $sql .= " SET traite = 1, date_traitement=now() ";
		      $sql .= " WHERE rowid =".$rowid;
		      
		      if (! $db->query($sql))
			{
			  dolibarr_syslog("Erreur de traitement de ligne $numero");
			  $error++;
			}
		    }
		  
		  if ($error == 0)
		    {
		      $db->query("COMMIT");
		      dolibarr_syslog("COMMIT");
		    }
		  else
		    {
		      $db->query("ROLLBACK");
		      dolibarr_syslog("ROLLBACK");
		    }
		  
		}
	    }
	  else
	    {
	      dolibarr_syslog("Ligne $numero déjà active");
	    }
	}
      /*
       * Ligne Résiliée
       */
      if ($mode == 'PRESELECTION' && 
	  $situation == 'CONFIRME' && 
	  $commentaire == 'CPS DESACTIVE PAR FT' &&
	  $date_resiliation > 0)
	{
	  
	  if ($ligne->statut == 3)
	    {
	      $statut = 6;
	      $datea = $db->idate($date_resiliation);
	      
	      if ($db->query("BEGIN"))
		{
		  $error = 0;
		  
		  if ($ligne->set_statut($user, $statut, $datea,'',1) <> 0)
		    {
		      $error++;
		    }
		  
		  if (!$error)
		    {
		      /* Tag la ligne comme traitée */
		      
		      $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_commande_retour ";
		      $sql .= " SET traite = 1, date_traitement=now() ";
		      $sql .= " WHERE rowid =".$rowid;
		      
		      if (! $db->query($sql))
			{
			  dolibarr_syslog("Erreur de traitement de ligne $numero");
			  $error++;
			}
		    }
		  
		  if ($error == 0)
		    {
		      $db->query("COMMIT");
		      dolibarr_syslog("COMMIT");
		    }
		  else
		    {
		      $db->query("ROLLBACK");
		      dolibarr_syslog("ROLLBACK");
		    }		  
		}
	    }
	  else
	    {
	      dolibarr_syslog("Ligne $numero déjà active");
	    }
	}
      
      /*
       *
       */
      /*
       * Prefixe non géré
       */
      
      if ($mode == 'PREFIXE' && 
	  $situation == 'CONFIRME')
	{	  
	  if ($db->query("BEGIN"))
	    {
	      $error = 0;
		  		  
	      if (!$error)
		{
		  /* Tag la ligne comme traitée */
		  
		  $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_commande_retour ";
		  $sql .= " SET traite = 1, date_traitement=now() ";
		  $sql .= " WHERE rowid =".$rowid;
		  
		  if (! $db->query($sql))
		    {
		      dolibarr_syslog("Erreur de traitement de ligne $numero");
		      $error++;
		    }
		}
		  
	      if ($error == 0)
		{
		  $db->query("COMMIT");
		  dolibarr_syslog("COMMIT");
		}
	      else
		{
		  $db->query("ROLLBACK");
		  dolibarr_syslog("ROLLBACK");
		}
	      
	    }
	}
      /*
       * Fin mode PREFIXE
       *
       */
    }
  else
    {
      print "Ligne inconnue : $numero\n";
    }
}
