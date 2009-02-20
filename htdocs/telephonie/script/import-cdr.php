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
 * Script d'import des CDR des fournisseurs
 */

require ("../../master.inc.php");

$opt = getopt("f:i:");

$file = $opt['f'];
$id_fourn = $opt['i'];

if (strlen($file) == 0 || strlen($id_fourn) == 0)
{
  print "Usage :\n php import-cdr.php -f <filename> -i <id_fournisseur>\n";
  exit;
}

/*
 * Vérification du fournisseur
 *
 */

$sql = "SELECT f.rowid, f.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE f.rowid = ".$id_fourn;

if ($db->query($sql))
{  
  $num = $db->num_rows();

  if ($num == 1)
    {
      $row = $db->fetch_row();
      dol_syslog ("Import fichier ".$file);
      dol_syslog("Fournisseur [".$row[0]."] ".$row[1]);
    }
  else
    {
      dol_syslog("Erreur Fournisseur inexistant : ".$id_fourn);
      exit ;
    }
}
else
{
  dol_syslog("Erreur recherche fournisseur");
  exit ;
}

/*
 * Vérifie que le fichier n'a pas déjà été chargé
 *
 */

$sql = "SELECT count(fichier)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$sql .= " WHERE fichier = '".basename($file)."'";

if ($db->query($sql))
{  
  $num = $db->num_rows();

  if ($num == 1)
    {
      $row = $db->fetch_row();
      if ($row[0] > 0)
	{
	  dol_syslog ("Fichier ".$file." déjà chargé dans import-log");

	  exit ;
	}
    }
  else
    {
      dol_syslog("Erreur vérif du fichier");
      exit ;
    }
}
else
{
  dol_syslog("Erreur SQL vérification du fichier");
  exit ;
}

/*
 * Vérifie que le fichier n'a pas déjà été traité
 *
 */

$sql = "SELECT count(fichier_cdr)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE fichier_cdr = '".basename($file)."'";

if ($db->query($sql))
{  
  $num = $db->num_rows();

  if ($num == 1)
    {
      $row = $db->fetch_row();
      if ($row[0] > 0)
	{
	  dol_syslog ("Fichier ".$file." déjà traité");
	  exit ;
	}
    }
  else
    {
      dol_syslog("Erreur vérif du fichier dans les comm");
      exit ;
    }
}
else
{
  dol_syslog("Erreur SQL vérification du fichier dans les comm");
  dol_syslog($sql);
  exit ;
}

/*
 * Charge les ID de lignes
 *
 */

$sql = "SELECT ligne, rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

$resql = $db->query($sql);

if ($resql)
{  
  $num = $db->num_rows($resql);
  dol_syslog ($num . " lignes chargées");
  $i = 0;
  $ligneids = array();

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $ligneids[$row[0]] = $row[1];
      $i++;
    }
}
else
{
  dol_syslog("Erreur chargement des lignes");
  dol_syslog($sql);
  exit ;
}


/*
 * Traitement
 *
 */

if (is_readable($file))
{
  
  dol_syslog("Lecture du fichier $file");
  
  $error = 0;
  $line = 0;
  $hf = fopen ($file, "r");
  $line = 0;
  
  if ($db->query("BEGIN"))
    {  
      while (!feof($hf) )
	{
	  $cont = fgets($hf, 1024);
  
	  if (strlen(trim($cont)) > 0)
	    {
	      $tabline = explode(";", $cont);
	      if (sizeof($tabline) == 11)
		{
		  $index             = $tabline[0];
		  $ligne             = ereg_replace('"','',$tabline[1]);
		  $date              = $tabline[2];
		  $heure             = $tabline[3];
		  $numero            = $tabline[4];
		  $tarif             = $tabline[5];
		  $duree_text        = $tabline[6];
		  $tarif_fourn       = $tabline[7];
		  $montant           = $tabline[8];
		  $duree_secondes    = ereg_replace('"','',$tabline[9]);
		  		  
		  if ($ligneids[$ligne] > 0)
		    {
		      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_import_cdr";
		      
		      $sql .= "(idx,fk_ligne,ligne,date,heure,num,dest,dureetext,tarif,montant,duree";
		      $sql .= ", fichier, fk_fournisseur)";
		      
		      $sql .= " VALUES (";
		      $sql .= "$index";
		      $sql .= ",'".$ligneids[$ligne]."'";
		      $sql .= ",'".$ligne."'";
		      $sql .= ",'".ereg_replace('"','',$date)."'";
		      $sql .= ",'".ereg_replace('"','',$heure)."'";
		      $sql .= ",'".ereg_replace('"','',$numero)."'";
		      $sql .= ",'".addslashes(ereg_replace('"','',$tarif))."'";
		      $sql .= ",'".ereg_replace('"','',$duree_text)."'";
		      $sql .= ",'".ereg_replace('"','',$tarif_fourn)."'";
		      $sql .= ",".ereg_replace(',','.',$montant);
		      $sql .= ",".$duree_secondes;
		      $sql .= ",'".basename($file)."'";
		      $sql .= " ,".$id_fourn;
		      $sql .= ")";
		      
		      if(ereg("^[0-9]+$", $duree_secondes))
			{
			  if ($db->query($sql))
			    {
			      $line_inserted++;
			    }
			  else
			    {
			      dol_syslog("Erreur de traitement de ligne $index");
			      dol_syslog($db->error());
			      dol_syslog($sql);
			      $error++;
			    }
			}
		      else
			{
			  print "Ligne : $cont ignorée\n";
			}
		      
		    }
		  else
		    {
		      dol_syslog("Ligne : $ligne ignorée!");
		      $error++;
		    }
		  
		}
	      else
		{
		  dol_syslog("Mauvais format de fichier ligne $line");
		  $error++;
		}
	    }
	  $line++;
	}
      
      dol_syslog(($line -1 )." lignes traitées dans le fichier");
      dol_syslog($line_inserted." insert effectués");
      
      if ($error == 0)
	{	  
	  $db->query("COMMIT");
	  dol_syslog("COMMIT");
	}
      else
	{
	  $db->query("ROLLBACK");
	  dol_syslog("ROLLBACK");
	}
      
    }
  
  fclose($hf);
}
else
{
  print "Erreur lecture : $file";
  dol_syslog($file . " not readable");
}


return $error;
