<?PHP
/* Copyright (c) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

require("../main.inc.php3");


$db = new Db();

$sql = "SELECT count(*) FROM llx_user";
$result = $db->query($sql);

if ($result) 
{
  if ($db->num_rows()) 
    {
      $row = $db->fetch_row(0);

      $db->free();
      
      if ($row[0] > 0)
	{
	  print "La base contient déjà des mots de passes";
	}
      else
	{
	  $user = new User($db);

	  $user->login = "admin";
	  $user->admin = 1;
	  $user->create();

	  $user->id = 1;

	  $user->password("admin");

	  print "Compte admin/admin créé";
	}
    }
}
else
{
  print $db->error();
}



?>
