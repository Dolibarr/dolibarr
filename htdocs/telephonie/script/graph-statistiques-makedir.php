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
 * Génération des répertoires
 *
 *
 */
require ("../../master.inc.php");

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

/*
 * Création des répertoires
 *
 */
$dirs[0] = DOL_DATA_ROOT."/graph/";
$dirs[1] = DOL_DATA_ROOT."/graph/telephonie/";
$dirs[2] = DOL_DATA_ROOT."/graph/telephonie/communications/";
$dirs[3] = DOL_DATA_ROOT."/graph/telephonie/factures/";
$dirs[4] = DOL_DATA_ROOT."/graph/telephonie/ca/";
$dirs[5] = DOL_DATA_ROOT."/graph/telephonie/client/";
$dirs[6] = DOL_DATA_ROOT."/graph/telephonie/lignes/";
$dirs[7] = DOL_DATA_ROOT."/graph/telephonie/commercials/";
$dirs[8] = DOL_DATA_ROOT."/graph/telephonie/contrats/";

$numdir = sizeof($dirs);

$sql = "SELECT distinct fk_commercial";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();	
      
      $dirs[($numdir + $i)] = DOL_DATA_ROOT."/graph/telephonie/commercials/".$row[0];
      
      $i++;
    }
}

for ($j = 0 ; $j < 10 ; $j++)
{
  $dirs[$i] = DOL_DATA_ROOT."/graph/telephonie/client/".$j;
  $i++;
}

/*
 *
 */

for ($j = 0 ; $j < 10 ; $j++)
{
  $dirs[$i] = DOL_DATA_ROOT."/graph/".$j."/telephonie/client/";
  $i++;
  $dirs[$i] = DOL_DATA_ROOT."/graph/".$j."/telephonie/ligne/";
  $i++;
  $dirs[$i] = DOL_DATA_ROOT."/graph/".$j."/telephonie/commercial/";
  $i++;
}

/*
 *
 */

$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $j = 0;
  
  while ($j < $num)
    {
      $row = $db->fetch_row();	
      
      $dirs[$i] = DOL_DATA_ROOT."/graph/telephonie/client/".substr($row[0],0,1)."/".$row[0]."/";     
      $i++;

      $dirs[$i] = DOL_DATA_ROOT."/graph/".substr($row[0],-1)."/telephonie/client/".$row[0]."/";     
      $i++;

      $j++;
    }
}

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."telephonie_contrat";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $j = 0;
  
  while ($j < $num)
    {
      $row = $db->fetch_row();	
      
      $dirs[$i] = DOL_DATA_ROOT."/graph/".substr($row[0],-1)."/telephonie/contrat/".$row[0]."/";
      $i++;

      $j++;
    }
}

/*
 *
 */ 
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $j = 0;
  
  while ($j < $num)
    {
      $row = $db->fetch_row();	
      
      $dirs[$i] = DOL_DATA_ROOT."/graph/".substr($row[0],-1)."/telephonie/ligne/".$row[0]."/";
      $i++;

      $j++;
    }
}
/*
 *
 */

if (is_array($dirs))
{
  foreach ($dirs as $key => $value)
    {
      $dir = $value;      
      create_dir($dir);
    }
}

$base_dir = DOL_DATA_ROOT.'/graph/telephonie/lignes/';

for ($i = 0 ; $i < 10 ; $i++)
{
  $dir = $base_dir . $i . "/";
      
  create_dir($dir);

  for ($j = 0 ; $j < 10 ; $j++)
    {
      $dir = $base_dir . $i . "/". $j . "/";
      
      create_dir($dir);

      for ($k = 0 ; $k < 10 ; $k++)
	{
	  $dir = $base_dir . $i . "/". $j . "/". $k . "/";
	  
	  create_dir($dir);
	}
    }  
}

function create_dir($dir)
{
  if (file_exists(dirname($dir)))
    {
      if (! file_exists($dir))
	{
	  umask(0);
	  if (! @mkdir($dir, 0755))
	    {
	      print  "Erreur: Le répertoire ".basename($dir)." n'existe pas et Dolibarr n'a pu le créer.";
	    }
	}
    }
  else
    {
      create_dir(dirname($dir));
    }
}
?>
