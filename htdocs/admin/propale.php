<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($action == 'nbprod' && $user->admin)
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PROPALE_NEW_FORM_NB_PRODUCT', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      Header("Location: propale.php");
    }
}

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

if ($action == 'set')
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) VALUES ('".$value."')";

  if ($db->query($sql))
    {

    }
}
if ($action == 'del')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."propal_model_pdf WHERE nom='".$value."'";

  if ($db->query($sql))
    {

    }
}

// positionne la variable pour le test d'affichage de l'icone

$propale_addon_var_pdf = PROPALE_ADDON_PDF;

if ($action == 'setpdf')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PROPALE_ADDON_PDF', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $propale_addon_var_pdf = $value;
    }
  /*
   * On la set active
   */
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) VALUES ('".$value."')";

  if ($db->query($sql))
    {

    }
}

$propale_addon_var = PROPALE_ADDON;

if ($action == 'setmod')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'PROPALE_ADDON', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $propale_addon_var = $value;
    }
}

/*
 *
 *
 *
 */

print_titre("Module de numérotation");

print '<table class="border" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$dir = "../includes/modules/propale/";
$handle = opendir($dir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 12) == 'mod_propale_' && substr($file, strlen($file)-3, 3) == 'php')
	{
	  $file = substr($file, 0, strlen($file)-4);

	  require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".$file.".php");

	  $modPropale = new $file;

	  print '<tr class="pair"><td>'.$file."</td><td>\n";
	  print $modPropale->info();
	  print '</td><td align="center">';
	  
	  if ($propale_addon_var == "$file")
	    {
	      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	    }
	  else
	    {
	      print "&nbsp;";
	    }
	  
	  print "</td><td>\n";
	  
	  print '<a href="propale.php?action=setmod&amp;value='.$file.'">activer</a>';
	  
	  print '</td></tr>';
	}
    }
  closedir($handle);
}
print '</table>';
/*
 * PDF
 */




$def = array();

$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."propal_model_pdf";
if ($db->query($sql))
{
  $i = 0;
  while ($i < $db->num_rows())
    {
      $array = $db->fetch_array($i);
      array_push($def, $array[0]);
      $i++;
    }
}
else
{
  print $db->error();
}

$dir = "../includes/modules/propale/";

/*
 * PDF
 */

print_titre("Modèles de propale pdf");

print '<table class="border" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center" colspan="2">Actif</td>';
print '<td align="center" colspan="2">Défaut</td>';
print "</TR>\n";

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,12) == 'pdf_propale_')
    {
      $name = substr($file, 12, strlen($file) - 24);
      $classname = substr($file, 0, strlen($file) -12);

      print '<tr class="pair"><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $obj = new $classname();
      
      print $obj->description;

      print '</td><td align="center">';

      if (in_array($name, $def))
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	  print "</td><td>\n";
	  print '<a href="propale.php?action=del&amp;value='.$name.'">désactiver</a>';
	}
      else
	{
	  print "&nbsp;";
	  print "</td><td>\n";
	  print '<a href="propale.php?action=set&amp;value='.$name.'">activer</a>';
	}

      print '</td><td align="center">';

      if ($propale_addon_var_pdf == "$name")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="propale.php?action=setpdf&amp;value='.$name.'">activer</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';

/*
 *
 *
 */
print_titre("Formulaire de création");
print '<form method="post" action="propale.php?action=nbprod">';
print '<table class="border" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td>';
print "</TR>\n";
print '<tr><td>';
print 'Nombre de ligne produits</td><td align="center">';
print '<input size="3" type="text" name="value" value="'.PROPALE_NEW_FORM_NB_PRODUCT.'">';
print '</td><td><input type="submit" value="changer"></td></tr></table></form>';



$db->close();

llxFooter();
?>
