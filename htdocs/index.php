<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

llxHeader();

$userstring=$user->prenom . ' ' . $user->nom .' ['.$user->code.']';
print_fiche_titre($langs->trans("WelcomeString",dolibarr_print_date(mktime(),"%A %d %B %Y"),$userstring), '<a href="about.php">'.$langs->trans("About").'</a>');

if (defined("MAIN_MOTD") && strlen(trim(MAIN_MOTD)))
{
  print "<br>".nl2br(MAIN_MOTD);
}

print "<br>\n";

/*
 * Boites
 *
 */
$user->getrights('');

$sql = "SELECT b.rowid, b.box_id, d.file FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d WHERE b.box_id = d.rowid";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $j = 0;
  
  while ($j < $num)
    {
      $obj = $db->fetch_object($j);
      $boxes[$j] = "includes/boxes/".$obj->file;
      $j++;
    }
}

print '<table width="100%">';
 
for ($ii=0, $ni=sizeof($boxes); $ii<$ni; $ii++)
{
  if ($ii % 2 == 0)
    {
      print "<tr>\n";
    }

  print '<td valign="top" width="50%">';
  include($boxes[$ii]);
  print "</td>";

  if ( ($ii -1) / 3 == 0)
    {
      print "</tr>\n";
    }
}

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>










