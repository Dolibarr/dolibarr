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
 * Script d'import des CDR
 */

require ("../../master.inc.php");

$opt = getopt("file");

$file = $opt[file];

if (strlen($file) == 0)
{
  print "Usage :\n php import-cdr.php --file FILENAME\n";
}
else
{
  if (is_readable($file))
    {

      dolibarr_syslog("Lecture du fichier $file");

      $error = 0;
      $line = 0;
      $hf = fopen ($file, "r");
      $line = 0;
  
      if ($db->query("BEGIN"))
	{  
	  while (!feof($hf) && $error == 0)
	    {
	      $cont = fgets($hf, 1024);
	  
	      $tabline = explode(";", $cont);
	  
	      if (sizeof($tabline) == 11)
		{
		  $index             = $tabline[0];
		  $ligne             = $tabline[1];
		  $date              = $tabline[2];
		  $heure             = $tabline[3];
		  $numero            = $tabline[4];
		  $tarif             = $tabline[5];
		  $duree_text        = $tabline[6];
		  $tarif_fourn       = $tabline[7];
		  $montant           = $tabline[8];
		  $duree_secondes    = $tabline[9];
	      
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_import_cdr";
	      
		  $sql .= "(idx,ligne,date,heure,num,dest,dureetext,tarif,montant,duree)";
	      
		  $sql .= " VALUES (";
		  $sql .= "$index";
		  $sql .= ",'".ereg_replace('"','',$ligne)."'";
		  $sql .= ",'".ereg_replace('"','',$date)."'";
		  $sql .= ",'".ereg_replace('"','',$heure)."'";
		  $sql .= ",'".ereg_replace('"','',$numero)."'";
		  $sql .= ",'".addslashes(ereg_replace('"','',$tarif))."'";
		  $sql .= ",'".ereg_replace('"','',$duree_text)."'";
		  $sql .= ",'".ereg_replace('"','',$tarif_fourn)."'";
		  $sql .= ",".ereg_replace(',','.',$montant);
		  $sql .= ",".ereg_replace('"','',$duree_secondes);
		  $sql .= ")";
	      
		  if (! $db->query($sql))
		    {
		      dolibarr_syslog("Erreur de traitement de ligne $index");
		      dolibarr_syslog($db->error());
		      dolibarr_syslog($sql);
		      $error++;
		    }
		}
	      else
		{
		  dolibarr_syslog("Mauvais format de fichier ligne $line");
		}
	  
	      $line++;
	    }
      
	  dolibarr_syslog(($line -1 )." lignes traitées");
      
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
  
      fclose($hf);
    }
  else
    {
      print "Erreur lecture : $file";
      dolibarr_syslog($file . " not readable");
    }
}

return $error;
