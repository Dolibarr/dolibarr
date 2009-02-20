<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Script d'import des retour
 *
 * Ce script permet de rechercher des lignes inconnues chez nous
 * presentes dans les fichiers de retour
 *
 *
 */
$verbose = 1;
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

$dir = DOL_DATA_ROOT."/telephonie/ligne/commande/retour/";
$dirdone = DOL_DATA_ROOT."/telephonie/ligne/commande/retour/traite/";

$dirback = DOL_DATA_ROOT."/telephonie/ligne/commande/retour/backup/";

if (! file_exists($dirback))
{
  umask(0);
  if (! @mkdir($dirback, 0755))
    {
      dol_syslog("Erreur: creation '$dir'");
    }
}


$handle=opendir($dir);

if ($verbose) dol_syslog("Lecture repertoire $dir");

while (($file = readdir($handle))!==false)
{
  if (is_file($dir.$file))
    {
      if (is_readable($dir.$file))
	{	  
	  if ($verbose) dol_syslog("Lecture $file");	  
	  
	  if (! file_exists($dirdone))
	    {
	      umask(0);
	      if (! @mkdir($dirdone, 0755))
		{
		  dol_syslog("Erreur: creation '$dirdone'");
		}
	    }
	  
	  /* 
	   * On teste le fichier
	   */
	  check_file($db, $dir, $file);	  
	}
      else
	{
	  dol_syslog("Erreur Lecture $file permissions insuffisante");
	}
    }
}

closedir($handle);

/**
 *
 *
 *
 */

Function check_file($db,$dir,$file)
{
  $error = 0;
  $line = 0;
  $hf = fopen ($dir.$file, "r");

  $ok = 0;
  $nok = 0;

  while (!feof($hf))
    {
      $cont = fgets($hf, 1024);
      
      $tabline = explode(";", $cont);
      
      if (substr($tabline, 0, 3) <> 'CLI')
	{
	  if (sizeof($tabline) == 8)
	    {
	      $numero            = $tabline[0];
	      $mode              = $tabline[1];
	      $situation         = $tabline[2];
	      $date_mise_service = $tabline[3];
	      $date_resiliation  = $tabline[4];
	      $motif_resiliation = $tabline[5];
	      $commentaire       = $tabline[6];
	      $fichier = $file;
	      
	      $ligne = new LigneTel($db);
	      if ($ligne->fetch($numero) == 1)
		{
		  print "Ligne : $numero OK\n";
		  $ok++;
		}
	      else
		{
		  print "Ligne : $numero ERREUR\n";
		  $nok++;
		}      
	    }      
	}
      $line++;
    }
     
  fclose($hf);

  print "ok      : $ok\n";
  print "erreurs : $nok\n";
  print "lignes : $line\n";
  return $error;


}
