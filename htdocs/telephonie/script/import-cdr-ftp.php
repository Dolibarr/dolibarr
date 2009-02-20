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

$opt = getopt("f:i:");

/*
$file = $opt['f'];
$id_fourn = $opt['i'];
if (strlen($file) == 0 || strlen($id_fourn) == 0)
{
  print "Usage :\n php import-cdr-bt.php -f <filename> -i <id_fournisseur>\n";
  exit;
}
*/
$file = DOL_DATA_ROOT."/telephonie/CDR/atraiter/";
$id_fourn = 4;

/*
 * Traitement
 *
 */

$files = array();

if (is_dir($file))
{
  $handle=opendir($file);

  if ($handle)
    {
      $i = 0 ;
      $var=True;
      
      while (($xfile = readdir($handle))!==false)
	{
	  if (is_file($file.$xfile) && substr($xfile, -4) == ".csv")
	    {
	      $files[$i] = $file.$xfile;
	      dol_syslog($xfile." ajouté");
	      $i++;
	    }
	  else
	    {
	      dol_syslog($xfile." ignoré");
	    }
	}
      
      closedir($handle);
    }
  else
    {
      dol_syslog("Impossible de libre $file");
      exit ;
    }
}
elseif (is_file($file))
{
  $files[0] = $file;
}
else
{
  dol_syslog("Impossible de libre $file");
  exit ;
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
 * Vérification des fichiers traités
 *
 */
$fichiers = array();
$sql = "SELECT distinct(fichier_cdr)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
if ($db->query($sql))
{  
  while ($row = $db->fetch_row($resql))
    {
      array_push($fichiers, $row[0]);
    }
  $db->free($resql);
}
else
{
  dol_syslog("Erreur recherche fournisseur");
}
$sql = "SELECT distinct(fichier)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
if ($db->query($sql))
{  
  while ($row = $db->fetch_row($resql))
    {
      array_push($fichiers, $row[0]);
    }
  $db->free($resql);
}
else
{
  dol_syslog("Erreur recherche fournisseur");
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

foreach ($files as $xfile)
{
  if (is_readable($xfile))
    {
      if ( _verif($db, $xfile, $fichiers) == 0)
	{      
	  dol_syslog("Lecture du fichier $xfile");
      
	  $error = 0;
	  $line = 0;
	  $line_inserted = 0;
	  $hf = fopen ($xfile, "r");
	  $line = 0;
	  
	  if ($db->query("BEGIN"))
	    {  
	      while (!feof($hf))
		{
		  $cont = fgets($hf, 1024);
		  
		  if (strlen(trim($cont)) > 0)
		    {

		      if ($line == 0)
			{
			  $headers = array();
			  $headers = explode(";",$cont);
			  //print_r($headers);
			}
		      else
			{		      
			  $tabline = explode(";", $cont);
			  if (sizeof($tabline) == 24)
			    {
			      //print_r($tabline);
			      $index             = $line;
			      $ligne             = "0".$tabline[11];
			      $date              = substr($tabline[12],0,10);
			      $date              = substr($date, 8,2)."/".substr($date, 5,2)."/".substr($date, 0,4);

			      $heure             = substr($tabline[12],11,8);
			      if ($tabline[8] == "3")
				{
				  $numero            = "0".$tabline[9];
				}

			      if ($tabline[8] == "4")
				{
				  $numero            = "00".$tabline[9];
				}

			      $tarif             = $tabline[14];
			      
			      $h = floor(trim($tabline[13]) / 3600);
			      $m = floor((trim($tabline[13]) - ($h * 3600)) / 60);
			      $s = (trim($tabline[13]) - ( ($h * 3600 ) + ($m * 60) ) );
			      
			      if ($h > 0)
				{
				  $dt = $h . " h " . substr("00".$m, -2) ."mn" . substr("00".$s, -2);
				}
			      else
				{
				  if ($m > 0)
				    {
				      $dt = substr("00".$m,-2) ."mn" . substr("00".$s, -2);
				    }
				  else
				    {
				      $dt =  "00mn".substr("00".$s, -2);
				    }
				}
			      
			      $duree_text        = $dt;
			      $tarif_fourn       = "NONE";
			      $montant           = trim($tabline[15]);
			      $duree_secondes    = trim($tabline[13]);
			      
			      if ($ligneids[$ligne] > 0)
				{
				  if ($duree_secondes > 0)
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
				      $sql .= ",'".basename($xfile)."'";
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
					  $error++;
					}
				    }				  
				}
			      else
				{
				  dol_syslog("Ligne : $ligne ignorée ! log write in /tmp/$ligne.import");
				  $fp = fopen("/tmp/$ligne.import","w");
				  if ($fp)
				    {
				      fwrite($fp, $cont);
				      foreach($tabline as $logtab)
					{
					  fwrite($fp, $logtab);
					}
				      fclose($fp);
				    }
				  $error++;
				}
			      
			    }
			  else
			    {
			      dol_syslog("Mauvais format de fichier ligne $line ".sizeof($tabline));
			      dol_syslog($cont);
			      $error++;
			    }
			}
		    }
		  $line++;
		}
	      
	      dol_syslog($line." lignes traitées dans le fichier");
	      dol_syslog($line_inserted." insert effectués");
	  
	      if ($error == 0)
		{	  
		  $db->query("COMMIT");
		}
	      else
		{
		  $db->query("ROLLBACK");
		  dol_syslog("ROLLBACK");
		}
	  
	    }
      
	  fclose($hf);
	}
    }
  else
    {
      print "Erreur lecture : $xfile";
      dol_syslog($xfile . " not readable");
    }
}


function _verif($db, $file, $fichiers)
{
  $result = 0;
  /*
   * Vérifie que le fichier n'a pas déjà été chargé
   *
   */
  if (in_array (basename($file), $fichiers))
    {
      dol_syslog ("Fichier ".basename($file)." déjà chargé/traité");
      $result = -1;
    }


  /*
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
	      $result = -1;
	    }
	}
      else
	{
	  dol_syslog("Erreur vérif du fichier");
	  $result = -1;
	}
    }
  else
    {
      dol_syslog("Erreur SQL vérification du fichier");
      $result = -1;
    }
 
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
	      $result = -1;
	    }
	}
      else
	{
	  dol_syslog("Erreur vérif du fichier dans les comm");
	  $result = -1;
	}
    }
  else
    {
      dol_syslog("Erreur SQL vérification du fichier dans les comm");
      dol_syslog($sql);
      $result = -1;
    } 
  */
  return $result;
}

return $error;
