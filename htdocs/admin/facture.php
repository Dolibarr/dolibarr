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

$facture_addon_var = FACTURE_ADDON;
$facture_addon_var_pdf = FACTURE_ADDON_PDF;
$facture_rib_number_var = FACTURE_RIB_NUMBER;
$facture_chq_number_var = FACTURE_CHQ_NUMBER;

if ($action == 'set')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'FACTURE_ADDON', value='".$value."', visible=0, type='chaine'";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $facture_addon_var = $value;
    }
}

if ($action == 'setribchq')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'FACTURE_RIB_NUMBER', value='".$rib."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $facture_rib_number_var = $rib;
    }

  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'FACTURE_CHQ_NUMBER', value='".$chq."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $facture_chq_number_var = $chq;
    }
}

if ($action == 'setpdf')
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'FACTURE_ADDON_PDF', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $facture_addon_var_pdf = $value;
    }
}

$dir = "../includes/modules/facture/";

print_titre("Module de numérotation");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
    {
      print '<tr class="pair"><td>';
      echo "$file";
      print "</td><td>\n";

      $func = $file."_get_num_explain";

      print $func();

      print '</td><td align="center">';

      if ($facture_addon_var == "$file")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="facture.php?action=set&value='.$file.'">activer</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';
/*
 * PDF
 */

print_titre("Modèles de facture pdf");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      print '<tr class="pair"><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $obj = new $classname();
      
      print $obj->description;

      print '</td><td align="center">';

      if ($facture_addon_var_pdf == "$name")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
	}
      else
	{
	  print "&nbsp;";
	}

      print "</td><td>\n";

      print '<a href="facture.php?action=setpdf&value='.$name.'">activer</a>';

      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';


/*
 *
 *
 */

print_titre( "Mode de règlement à afficher sur les factures");

print '<table border="1" cellpadding="3" cellspacing="0">';

print '<form action="facture.php" method="post">';
print '<input type="hidden" name="action" value="setribchq">';
print '<tr class="liste_titre">';
print '<td>Mode règlement</td>';
print '<td><input type="submit" value="Modifier">';
print "</tr>\n";
print '<tr class="pair">';
print "<td>Virement par RIB sur le compte</td>";
print "<td><select name=\"rib\">";
print '<option value="0">Ne pas afficher</option>';
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);

      if ($facture_rib_number_var == $row[0])
	{
	  print '<option value="'.$row[0].'" selected>'.$row[1].'</option>';
	}
      else
	{
	  print '<option value="'.$row[0].'">'.$row[1].'</option>';
	}
      $i++;
    }
}
print "</select></td></tr>";

print '<tr class="pair">';
print "<td>Ordre et adresse pour chèque à déposer sur le compte</td>";
print "<td><select name=\"chq\">";
print '<option value="0">Ne pas afficher</option>';
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);

      if ($facture_chq_number_var == $row[0])
	{
	  print '<option value="'.$row[0].'" selected>'.$row[1].'</option>';
	}
      else
	{
	  print '<option value="'.$row[0].'">'.$row[1].'</option>';
	}
      $i++;
    }
}
print "</select></td></tr>";

print "</form>";
print "</table>";
$db->close();

/*
 * Repertoire
 */

print_titre("Chemins d'accés aux documents");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td><td>Valeur</td>';
print "</TR>\n";
print '<tr class="pair"><td>Répertoire</td><td>'.FAC_OUTPUTDIR.'</td></tr>';
print '<tr class="pair"><td>URL</td><td><a href="'.FAC_OUTPUT_URL.'">'.FAC_OUTPUT_URL.'</a></td></tr>';
print "</table>";

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
