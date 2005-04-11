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
 */
require ("../htdocs/master.inc.php");

$fname = '/tmp/dump.sh';

$fp = fopen($fname,"w");

$excl = "llx_telephonie_communications_details";

$sql = "SHOW TABLES";

$resql = $db->query($sql);

if ($resql)
{
  $i = 0;
  $num = $db->num_rows($resql);

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);


      if ($row[0] <> $excl)
	{

	  $line = "mysqldump ".$dolibarr_main_db_name." ".$row[0] . " >> /tmp/dump.sql";

	  fputs($fp, $line."\n");	  
	}
      $i++;
    }
}
fclose($fp);
$db->close();
?>
