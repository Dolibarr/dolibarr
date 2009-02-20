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
 * Script d'import des CDR BT
 */

require ("../../master.inc.php");

$opt = getopt("f:i:");

$file = $opt['f'];
$id_fourn = $opt['i'];

if (strlen($file) == 0 )
{
  print "Usage :\n php import-cdr-bt.php -f <filename>\n";
  exit;
}

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
	      dol_syslog($file.$xfile." ajouté");
	      $i++;
	    }
	  else
	    {
	      dol_syslog($file.$xfile." ignoré");
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

$datef = strftime("%y%m", (time() - (15*3600*24)) );

foreach ($files as $xfile)
{
  if (is_readable($xfile))
    {
      $newfile = ereg_replace(".csv","-".$datef.".csv", $xfile);

      rename ($xfile, $newfile);
    }
}

return $error;
