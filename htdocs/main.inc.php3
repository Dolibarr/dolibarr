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

require ($GLOBALS["DOCUMENT_ROOT"]."/lib/functions.inc.php3");

require ($GLOBALS["DOCUMENT_ROOT"]."/user.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/lib/product.class.php3");

$conf = new Conf();


$db = new Db();
$user = new User($db);
$user->fetch($GLOBALS["REMOTE_USER"]);

$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";


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

function top_menu($head) {
  global $user, $conf;

  print "<HTML><HEAD>";
  print $head;
  print "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">\n";
  print '<LINK REL="stylesheet" TYPE="text/css" HREF="/'.$conf->css.'">';
  print "</HEAD>\n";
  
  print '<BODY BGCOLOR="#c0c0c0" TOPMARGIN="0" BOTTOMMARGIN="0" LEFTMARGIN="0" RIGHTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">';
  /*
   * Barre superieure
   *
   */

  print '<TABLE border="0" width="100%" bgcolor="#000000" cellspacing="0" cellpadding="0">';
  print '<tr><td>';
  print '<TABLE border="0" width="100%" cellspacing="1" cellpadding="2">';

  print "<TR>";
  print '<TD width="15%" class="menu" align="center"><A class="menu" href="/">Accueil</A></TD>';

  print '<TD width="15%" class="menu" align="center">';
  if ($user->comm > 0) {
    print '<A class="menu" href="../comm/">Commercial</A></TD>';
  } else {
    print '-';
  }

  print '<TD width="15%" class="menu" align="center">';
  if ($user->compta > 0) {
    print '<A class="menu" href="../compta/">Compta</A></TD>';
  } else {
    print '-';
  }


  print '<TD width="15%" class="menu" align="center">-</TD>';
  print '<TD width="15%" class="menu" align="center">-</TD>';
  print '<TD width="15%" class="menu" align="center">-</TD>';

  print '<TD width="10%" class="menu" align="center">'.$user->code.'</td>';
  print '</TR>';
  print '</table>';
  print '</td></tr>';
  print '</table>';
  /*
   * Table principale
   *
   */
  print '<TABLE border="0" width="100%">';


}

?>
