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
  if (! file_exists($dir))
    {
      umask(0);
      if (! @mkdir($dir, 0755))
	{
	  print  "Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
	}
      else
	{
	  print $dir ." créé\n";
	}
    }	
}
?>
