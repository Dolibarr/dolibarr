<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
    \file       htdocs/install/check.php
    \brief      Test si le fichier conf est modifiable et si il n'existe pas, test la possibilité de le créer
    \version    $Revision$
*/

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:$langcode;
$langs->defaultlang=$setuplang;
$langs->load("install");

pHeader($langs->trans("DolibarrWelcome"), "licence");   // Etape suivante = license


print $langs->trans("InstallEasy")."<br>";


if (is_readable($conffile))
{
  $confexists=1;
  include_once($conffile);
}
else
{
  $confexists=0;
  $fp = @fopen("$conffile", "w");
  if($fp)
    {
      @fwrite($fp, '<?php');
      @fputs($fp,"\n");
      @fputs($fp,"?>");
      fclose($fp);
    }
}

if (!file_exists($conffile))
{
  print "<br /><br />Le fichier de configuration <b>conf.php</b> n'existe pas !<br />";
  print "Vous devez créer un fichier <b>htdocs/conf/conf.php</b> et donner les droits d'écriture dans celui-ci au serveur Apache.<br /><br />";

  print 'Corrigez le problème et <a href="index.php">rechargez la page</a>';

  $err++;
}
else
{
  if (!is_writable($conffile))
    {
      if ($confexists) print "<br /><br />Le fichier de configuration <b>conf.php</b> existe.<br />";
      else print "<br /><br />Le fichier de configuration <b>conf.php</b> a pu etre créé.<br />";
      print "Le fichier <b>conf.php</b> n'est pas accessible en écriture, vérifiez les droits sur celui-ci, le serveur Apache doit avoir le droit d'écrire dans ce fichier le temps de la configuration (chmod 666 par exemple)<br>";
      

      $err++;
    }
  else
    {
      if ($confexists) print "<br /><br />Le fichier de configuration <b>conf.php</b> existe.<br />";
      else print "<br /><br />Le fichier de configuration <b>conf.php</b> a pu etre créé.<br />";
      print "Le fichier <b>conf.php</b> est accessible en écriture<br /><br />Vous pouvez continuer...";

    }
}

// Si pas d'erreur, on affiche le bouton pour passer à l'étape suivante
if ($err == 0) pFooter();

?>
