<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require ($GLOBALS["DOCUMENT_ROOT"]."/conf/conf.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/lib/mysql.lib.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/lib/functions.inc.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/product.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/user.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/menu.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/societe.class.php3");
require ($GLOBALS["DOCUMENT_ROOT"]."/html.form.class.php");
require ($GLOBALS["DOCUMENT_ROOT"]."/rtplang.class.php");

$conf = new Conf();

$db = new Db();

$user = new User($db);

$user->fetch($GLOBALS["REMOTE_USER"]);

if ($user->limite_liste <> $conf->liste_limit) {
  $conf->liste_limit = $user->limite_liste;
}

$db->close();
/*
 */
if(!isset($application_lang))
  $application_lang = "fr";

$rtplang = new rtplang($GLOBALS["DOCUMENT_ROOT"]."/langs", "en", "en", $application_lang);
$rtplang->debug=1;
/*
 */
$bc[0]="class=\"impair\"";
$bc[1]="class=\"pair\"";

$a = setlocale("LC_TIME", "FRENCH");

function top_menu($head) {
  global $user, $conf, $rtplang;

  print $rtplang->lang_header();

  print "<HTML><HEAD>";
  print $head;
  print '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">';
  print '<LINK REL="stylesheet" TYPE="text/css" HREF="/'.$conf->css.'">';
  print "\n";
  print '<title>Dolibarr</title>';
  print "\n";


  print "</HEAD>\n";
  
  print '<BODY TOPMARGIN="0" BOTTOMMARGIN="0" LEFTMARGIN="0" RIGHTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">';
  /*
   * Barre superieure
   *
   */

  print '<TABLE class="topbarre" width="100%">';

  print "<TR>";
  print '<TD width="15%" class="menu" align="center"><A class="menu" href="/">Accueil</A></TD>';

  print '<TD width="15%" class="menu" align="center">';
  if ($user->comm > 0 && $conf->commercial ) 
    {
      print '<A class="menu" href="/comm/">Commercial</A></TD>';
    }
  else
    {
      print '-';
    }

  print '<TD width="15%" class="menu" align="center">';
  if ($user->compta > 0)
    {
      print '<A class="menu" href="/compta/">Compta</A></TD>';
    } 
  else
    {
      print '-';
    }


  print '<TD width="15%" class="menu" align="center">';
  if ($conf->produit->enabled ) 
    {
      print '<A class="menu" href="/product/">Produits</a>';
    }
  else
    {
      print '-';
    }
  print '</td><td width="15%" class="menu" align="center">';
  if ($conf->webcal->enabled) {
    print '<a class="menu" href="'.$conf->webcal->url.'">Calendrier</a>';
  };
  print '&nbsp;</TD>';
  print '<TD width="15%" class="menu" align="center">'.strftime(" %d %B - %H:%M",time()).'</TD>';

  print '<TD width="10%" class="menu" align="center">'.$user->login.'</td>';
  print '</TR>';
  print '</table>';
  /*
   * Table principale
   *
   */
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';

  print "<TR><TD valign=\"top\" align=\"right\">";
}

function left_menu($menu) 
{
  global $conf;
  /*
   * Colonne de gauche
   *
   */
  print '<TABLE class="leftmenu" border="0" width="100%" cellspacing="1" cellpadding="4">';


  for ($i = 0 ; $i < sizeof($menu) ; $i++) 
    {

      print "<TR><TD class=\"barre\" valign=\"top\">";
      print '<A class="leftmenu" href="'.$menu[$i][0].'">'.$menu[$i][1].'</a>';

      for ($j = 2 ; $j < sizeof($menu[$i]) - 1 ; $j = $j +2) 
	{
	  print '<br>&nbsp;-&nbsp;<a class="submenu" href="'.$menu[$i][$j].'">'.$menu[$i][$j+1].'</A>';
	}
      print '</td></tr>';
      
    }

  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print '<A class="menu" href="/comm/clients.php3">Societes</A>';
  print '<form action="/comm/clients.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="soc">';
  print '<input type="text" name="socname" size="8">&nbsp;';
  print "<input type=\"submit\" value=\"go\">";
  print "</form>";

  print '<A class="menu" href="/comm/contact.php3">Contacts</A>';
  print '<form action="/comm/contact.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="contact">';
  print "<input type=\"text\" name=\"contactname\" size=\"8\">&nbsp;";
  print "<input type=\"submit\" value=\"go\">";
  print '</form>';
  print '</td></tr>';

  print '</table>';
  /*
   *
   *
   */
  print "</TD>\n<TD valign=\"top\" width=\"85%\">\n";


}

function llxFooter($foot='') 
{
  print "</TD></TR>";
  /*
   *
   */
  print "</TABLE>\n";
  print "<div>";
  print '[<a href="http://savannah.gnu.org/bugs/?group_id=1915">Bug report</a>]&nbsp;';
  print '[<a href="http://savannah.gnu.org/projects/dolibarr/">Source Code</a>]&nbsp;'.$foot.'</div>';
  print "</BODY></HTML>";
}


?>
