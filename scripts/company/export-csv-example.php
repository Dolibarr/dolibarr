<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Export simple des contacts
 */

require_once("../../htdocs/master.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbook.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");

$error = 0;


$fname = '/tmp/export-client.xls';

$workbook = &new writeexcel_workbook($fname);

$page = &$workbook->addworksheet('Export Dolibarr');

$page->set_column(0,4,18); // A

$sql = "SELECT distinct(c.email),c.name, c.firstname, s.nom ";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE s.rowid = c.fk_soc";
$sql .= " AND s.client = 1";
$sql .= " AND c.email IS NOT NULL";
$sql .= " ORDER BY c.email ASC";

if ($db->query($sql))
{
  $num = $db->num_rows();

  print "Lignes trait�es $num\n";

  $i = 0;
  $j = 1;

  $page->write_string(0, 0,  "Soci�t�");
  $page->write_string(0, 1,  "Pr�nom");
  $page->write_string(0, 2,  "Nom");
  $page->write_string(0, 3,  "Email");

  $oldemail = "";

  while ($i < $num)
    {
      $obj = $db->fetch_object();

      if ($obj->email <> $oldemail)
	{

	  $page->write_string($j, 0,  $obj->nom);
	  $page->write_string($j, 1,  $obj->firstname);
	  $page->write_string($j, 2,  $obj->name);
	  $page->write_string($j, 3,  $obj->email);
	  $j++;

	  $oldemail = $obj->email;
	}

      $i++;

    }
}

$workbook->close();
?>
