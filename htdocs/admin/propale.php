<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur       <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio       <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier            <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne               <eric.seigne@ryxeo.com>
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

/**
	    \file       htdocs/admin/propale.php
		\ingroup    propale
		\brief      Page d'administration/configuration du module Propale
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("propal");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'nbprod')
{
    dolibarr_set_const($db, "PROPALE_NEW_FORM_NB_PRODUCT",$value);
    Header("Location: propale.php");
}
if ($_GET["action"] == 'set')
{
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) VALUES ('".$_GET["value"]."')";

    if ($db->query($sql))
    {

    }
}
if ($_GET["action"] == 'del')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."propal_model_pdf WHERE nom='".$_GET["value"]."'";

    if ($db->query($sql))
    {

    }
}


$propale_addon_var_pdf = $conf->global->PROPALE_ADDON_PDF;

if ($_GET["action"] == 'setpdf')
{
    if (dolibarr_set_const($db, "PROPALE_ADDON_PDF",$_GET["value"]))
    {
        // La constante qui a été lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage cohérent
        $propale_addon_var_pdf = $_GET["value"];
    }

    // On active le modele
    $sql_del = "delete from ".MAIN_DB_PREFIX."propal_model_pdf where nom = '".$_GET["value"]."';";
    $db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) VALUES ('".$_GET["value"]."')";
    if ($db->query($sql))
    {

    }
}

$propale_addon_var = $conf->global->PROPALE_ADDON;

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre activé
    // par appel methode canBeActivated



	if (dolibarr_set_const($db, "PROPALE_ADDON",$_GET["value"]))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $propale_addon_var = $_GET["value"];
    }
}


/*
 * Affiche page
 */

$dir = "../includes/modules/propale/";


llxHeader('',$langs->trans("PropalSetup"));

print_titre($langs->trans("PropalSetup"));


/*
 *  Module numérotation
 */
print "<br>";
print_titre($langs->trans("ProposalsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 12) == 'mod_propale_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".$file.".php");

            $modPropale = new $file;

            $var=!$var;
            print "<tr ".$bc[$var].">\n  <td width=\"140\">".$file."</td>";
            print "\n  <td>".$modPropale->info()."</td>\n";
            print "\n  <td nowrap>".$modPropale->getExample()."</td>\n";

            print '<td align="center">';
            if ($propale_addon_var == "$file")
            {
                $title='';
                if ($modPropale->getNextValue() != $langs->trans("NotAvailable"))
                {
                    $title=$langs->trans("NextValue").': '.$modPropale->getNextValue();
                }
                print img_tick($title);
            }
            else
            {
                print "<a href=\"propale.php?action=setmod&amp;value=".$file."\">".$langs->trans("Activate")."</a>";
            }
            print '</td>';


            print "</tr>\n";
        }
    }
    closedir($handle);
}
print "</table><br>\n";


/*
 * PDF
 */

print_titre($langs->trans("ProposalsPDFModules"));

$def = array();

$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."propal_model_pdf";
$resql=$db->query($sql);
if ($resql)
{
  $i = 0;
  $num_rows=$db->num_rows($resql);
  while ($i < $num_rows)
    {
      $array = $db->fetch_array($resql);
      array_push($def, $array[0]);
      $i++;
    }
}
else
{
  dolibarr_print_error($db);
}

$dir = "../includes/modules/propale/";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '  <td align="center" colspan="2">'.$langs->trans("Activated")."</td>\n";
print '  <td align="center">'.$langs->trans("Default")."</td>\n";
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,12) == 'pdf_propale_')
    {
      $name = substr($file, 12, strlen($file) - 24);
      $classname = substr($file, 0, strlen($file) -12);

      $var=!$var;
      print "<tr ".$bc[$var].">\n  <td>";
      print "$name";
      print "</td>\n  <td>\n";
      require_once($dir.$file);
      $obj = new $classname($db);
      
      print $obj->description;

      print "</td>\n  <td align=\"center\">\n";

      if (in_array($name, $def))
	{
	  print img_tick();
	  print "</td>\n  <td>";
	  print '<a href="propale.php?action=del&amp;value='.$name.'">'.$langs->trans("Disable").'</a>';
	}
      else
	{
	  print "&nbsp;";
	  print "</td>\n  <td>";
	  print '<a href="propale.php?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
	}

      print "</td>\n  <td align=\"center\">";

      if ($propale_addon_var_pdf == "$name")
	{
	  print img_tick();
	}
      else
	{
      print '<a href="propale.php?action=setpdf&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
	}
      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';

/*
 *  Repertoire
 */
print '<br>';
print_titre("Chemins d'accés aux documents");

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr ".$bc[false].">\n  <td width=\"140\">".$langs->trans("Directory")."</td>\n  <td>".$conf->propal->dir_output."</td>\n</tr>\n";
print "</table>\n<br>";



/*
 * Formulaire création
 *
 */
print_titre($langs->trans("CreateForm"));

print "<form method=\"post\" action=\"propale.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"nbprod\">";
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td align=\"left\">".$langs->trans("Value")."</td>\n";
print "  <td>&nbsp;</td>\n";
print "</tr><tr ".$bc[false].">";
print '<td>'.$langs->trans("NumberOfProductLines").'</td>';
print "<td align=\"left\"><input size=\"3\" type=\"text\" name=\"value\" value=\"".PROPALE_NEW_FORM_NB_PRODUCT."\"></td>";
print '<td><input type="submit" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

$db->close();

llxFooter();
?>
