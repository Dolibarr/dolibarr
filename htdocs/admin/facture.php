<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/*!	\file htdocs/admin/facture.php
		\ingroup    facture
		\brief      Page d'administration/configuration du module Facture
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


$facture_addon_var      = FACTURE_ADDON;
$facture_addon_var_pdf  = FACTURE_ADDON_PDF;
$facture_rib_number_var = FACTURE_RIB_NUMBER;
$facture_chq_number_var = FACTURE_CHQ_NUMBER;
$facture_tva_option     = FACTURE_TVAOPTION;

$typeconst=array('yesno','texte','chaine');


if ($_GET["action"] == 'set')
{
  if (dolibarr_set_const($db, "FACTURE_ADDON",$_GET["value"]))
    $facture_addon_var = $_GET["value"];
}

if ($_GET["action"] == 'dateforce')
{
  dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$_GET["value"]);
  Header("Location: facture.php");    
}

if ($_POST["action"] == 'setribchq')
{
  if (dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$_POST["rib"])) $facture_rib_number_var = $_POST["rib"];
  if (dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$_POST["chq"])) $facture_chq_number_var = $_POST["chq"];
}

if ($_GET["action"] == 'setpdf')
{
  if (dolibarr_set_const($db, "FACTURE_ADDON_PDF",$_GET["value"])) $facture_addon_var_pdf = $_GET["value"];
}

if ($_POST["action"] == 'settvaoption')
{
  if (dolibarr_set_const($db, "FACTURE_TVAOPTION",$_POST["optiontva"])) $facture_tva_option = $_POST["optiontva"];
}

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:''));
	{
	  	print $db->error();
	}
}

if ($_GET["action"] == 'delete')
{
  if (! dolibarr_del_const($db, $_GET["rowid"]));
  {
    print $db->error();
  }
}

llxHeader('','Fiche commande','FactureConfiguration');

$dir = "../includes/modules/facture/";

print_titre("Configuration du module Factures");


/*
 *  Module numérotation
 */
print "<br>";
print_titre("Module de numérotation des factures");

print '<table class="noborder" cellpadding="2" cellspacing="0" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
  if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
    {
      $var = !$var;
      print '<tr '.$bc[$var].'><td width=\"100\">';
      echo "$file";
      print "</td><td>\n";

      $filebis = $file."/".$file.".modules.php";

      // Chargement de la classe de numérotation
      $classname = "NumRefFactures".ucfirst($file);
      require_once($dir.$filebis);

      $obj = new $classname($db);
      print $obj->getDesc();

      print '</td>';

      // Affiche example
      print '<td>'.$obj->getExample().'</td>';
      
      print '<td align="center">';
      if ($facture_addon_var == "$file")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
      print '</td><td align="center">';
      print '&nbsp;';
	}
      else
	{
	  print '&nbsp;';
      print '</td><td align="center">';
      print '<a href="facture.php?action=set&value='.$file.'">'.$langs->trans("Activate").'</a>';
	}
	print "</td></tr>\n";
    }
}
closedir($handle);

print '</table>';

print "<br>";
print_titre("Date des factures");

print '<table class="noborder" cellpadding="2" cellspacing="0" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr '.$bc[$var].'><td>';
echo "Forcer la définition de la date des factures lors de la validation";

print '</td><td width="60" align="center">';

if (defined("FAC_FORCE_DATE_VALIDATION") && FAC_FORCE_DATE_VALIDATION == "1")
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
  print '</td><td align="center">';
  print '<a href="facture.php?action=dateforce&amp;value=0">'.$langs->trans("Désactiver").'</a>';
}
else
{
  print '&nbsp;';
  print '</td><td align="center">';
  print '<a href="facture.php?action=dateforce&amp;value=1">'.$langs->trans("Activate").'</a>';
}
print "</td></tr>\n";




print '</table>';



/*
 *  PDF
 */
print '<br>';
print_titre("Modèles de facture pdf");

print '<table class="noborder" cellpadding="2" cellspacing="0" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
	  $var = !$var;
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      print '<tr '.$bc[$var].'><td width=\"100\">';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $obj = new $classname($db);
      
      print $obj->description;

      print '</td><td align="center">';

      if ($facture_addon_var_pdf == "$name")
	{
	  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
      print '</td><td align="center">';
      print '&nbsp;';
	}
      else
	{
	  print '&nbsp;';
      print '</td><td align="center">';
      print '<a href="facture.php?action=setpdf&value='.$name.'">'.$langs->trans("Activate").'</a>';
	}
	print "</td></tr>\n";

    }
}
closedir($handle);

print '</table>';


/*
 *  Modes de règlement
 *
 */
print '<br>';
print_titre( "Mode de règlement à afficher sur les factures");

print '<table class="noborder" cellpadding="2" cellspacing="0" width=\"100%\">';
$var=True;

print '<form action="facture.php" method="post">';
print '<input type="hidden" name="action" value="setribchq">';
print '<tr class="liste_titre">';
print '<td>Mode règlement à proposer</td>';
print '<td align="right"><input type="submit" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>Proposer paiement par RIB sur le compte</td>";
print "<td>";
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account where clos = 0";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0) {
    print "<select name=\"rib\">";
    print '<option value="0">Ne pas afficher</option>';
    while ($i < $num)
      {
	$var=!$var;
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
    print "</select>";
  } else {
    print "<i>Aucun compte bancaire actif créé</i>";
  }
}
print "</td></tr>";
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>Proposer paiement par chèque à l'ordre et adresse du titulaire du compte</td>";
print "<td>";
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account where clos = 0";
$var=True;
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0)
    {
      print "<select name=\"chq\">";
      print '<option value="0">Ne pas afficher</option>';
      while ($i < $num)
	{
	  $var=!$var;
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
      print "</select>";
    } else {
      print "<i>Aucun compte bancaire actif créé</i>";
    }
}
print "</td></tr>";
print "</form>";
print "</table>";


/*
 *  Options fiscale
 */
print '<br>';
print_titre("Options fiscales de facturation de la TVA");

print '<table class="noborder" cellpadding="2" cellspacing="0" width=\"100%\">';
print '<form action="facture.php" method="post">';
print '<input type="hidden" name="action" value="settvaoption">';
print '<tr class="liste_titre">';
print '<td>Option</td><td>Description</td>';
print '<td align="right"><input type="submit" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=True;
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"140\"><input type=\"radio\" name=\"optiontva\" value=\"reel\"".($facture_tva_option != "franchise"?" checked":"")."> Option réel</td>";
print "<td colspan=\"2\">L'option 'réel' est la plus courante. Elle est à destination des entreprises et professions libérales.\nChaque produits/service vendu est soumis à la TVA (Dolibarr propose le taux standard par défaut à la création d'une facture). Cette dernière est récupérée l'année suivante suite à la déclaration TVA pour les produits/services achetés et est reversée à l'état pour les produits/services vendus.</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"140\"><input type=\"radio\" name=\"optiontva\" value=\"franchise\"".($facture_tva_option == "franchise"?" checked":"")."> Option franchise</td>";
print "<td colspan=\"2\">L'option 'franchise' est utilisée par les particuliers ou professions libérales à titre occasionnel avec de petits chiffres d'affaires.\nChaque produits/service vendu est soumis à une TVA de 0 (Dolibarr propose le taux 0 par défaut à la création d'une facture cliente). Il n'y a pas de déclaration ou récupération de TVA, et les factures qui gèrent l'option affichent la mention obligatoire \"TVA non applicable - art-293B du CGI\".</td></tr>\n";
print "</form>";
print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
