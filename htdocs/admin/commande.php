<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

// positionne la variable pour le test d'affichage de l'icone

$commande_addon_var = COMMANDE_ADDON;
$commande_addon_var_pdf = COMMANDE_ADDON_PDF;
$commande_rib_number_var = COMMANDE_RIB_NUMBER;

$commande_addon_var = COMMANDE_ADDON;

if ($action == 'setmod')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'COMMANDE_ADDON', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $commande_addon_var = $value;
    }
}


$dir = "../includes/modules/commande/";

print_titre("Configuration du module Commandes");

print "<br>";

print_titre("Module de numérotation des commandes");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<TR class="liste_titre">';
print '<td>Nom</td><td>Info</td>';
print '<td align="center">Activé</td><td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$dir = "../includes/modules/commande/";
$handle = opendir($dir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 13) == 'mod_commande_' && substr($file, strlen($file)-3, 3) == 'php')
	{
	  $file = substr($file, 0, strlen($file)-4);

	  require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/".$file.".php");

	  $modCommande = new $file;

	  print '<tr class="pair"><td>'.$modCommande->nom."</td><td>\n";
	  print $modCommande->info();
	  print '</td>';
	  
	  if ($commande_addon_var == "$file")
	    {
	      print '<td align="center">';
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
		  print '</td><td>&nbsp;</td>';
	    }
	  else
	    {
		  print '<td>&nbsp;</td>';
		  print '<td align="center"><a href="'.$PHP_SELF.'?action=setmod&amp;value='.$file.'">activer</a></td>';
	    }

	  print '</tr>';
	}
    }
  closedir($handle);
}
else
{
  print "Erreur";
}

print '</table>';

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
