<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 *
 * $Id$
 * $Source$
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
 */

require ($GLOBALS["DOCUMENT_ROOT"]."/conf/conf.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/lib/mysql.lib.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/user.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/lib/functions.inc.php3");


$conf = new Conf();


$db = new Db();
$user = new User($db);
$user->fetch($GLOBALS["REMOTE_USER"]);

function llxFooter($foot='') {
  print "</TD></TR>";
  /*
   *
   */
  print "</TABLE>\n";
  print "$foot<br>";
  print '[<a href="http://savannah.gnu.org/bugs/?group_id=1915">Bug report</a>]&nbsp;';
  print '[<a href="http://savannah.gnu.org/projects/dolibarr/">Source Code</a>]&nbsp;';
  print "</BODY></HTML>";
}


?>
