<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004				Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004      	Benoit Mortier			  <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2006  Regis Houssin         <regis.houssin@cap-networks.com>
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
    	\file       htdocs/admin/fournisseur.php
		\ingroup    fournisseur
		\brief      Page d'administration-configuration du module Fournisseur
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("orders");

llxHeader();

if (!$user->admin)
  accessforbidden();


// positionne la variable pour le test d'affichage de l'icone

$commande_fournisseur_addon_var = COMMANDE_SUPPLIER_ADDON;
$commande_fournisseur_addon_var_pdf = COMMANDE_SUPPLIER_ADDON_PDF;


/*
 * Actions
 */

if ($_GET["action"] == 'setmod')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'COMMANDE_SUPPLIER_ADDON' ;";
	$db->query($sql);
	$sql = '';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('COMMANDE_SUPPLIER_ADDON','".$_GET["value"]."',0) ; ";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $commande_fournisseur_addon_var = $_GET["value"];
    }
}
if ($_GET["action"] == 'set')
{
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_model_pdf (nom) VALUES ('".$_GET["value"]."')";

    if ($db->query($sql))
    {

    }
}
$propale_fournisseur_addon_var_pdf = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;
if ($_GET["action"] == 'setpdf')
{
  
  if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$_GET["value"])) 
  	$commande_fournisseur_addon_var_pdf = $_GET["value"];

	// On active le modele
    $sql_del = "delete from ".MAIN_DB_PREFIX."commande_fournisseur_model_pdf where nom = '".$_GET["value"]."';";
    $db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_model_pdf (nom) VALUES ('".$_GET["value"]."')";
    if ($db->query($sql))
    {

    }
}
if ($_GET["action"] == 'del')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur_model_pdf WHERE nom='".$_GET["value"]."'";

    if ($db->query($sql))
    {

    }
}
$propale_fournisseur_addon_var_pdf = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;

$dir = "../fourn/commande/modules/";

print_titre($langs->trans("OrdersSetup"));

print "<br>";

print_titre($langs->trans("OrdersNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td><td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Activated").'</td>';
print "</tr>\n";

clearstatcache();

$dir = "../fourn/commande/modules/";
$handle = opendir($dir);
if ($handle)
{
    $var=true;
    
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 13) == 'mod_commande_fournisseur_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/fourn/commande/modules/".$file.".php");

            $modCommande = new $file;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$modCommande->nom."</td><td>\n";
            print $modCommande->info();
            print '</td>';

            print '<td align="center" nowrap>';
            print $modCommande->getExample();
            print '</td>';

            if ($commande_fournisseur_addon_var == "$file")
            {
                print '<td align="center">';
                print img_tick();
                print '</td>';
            }
            else
            {
                print '<td align="center"><a href="fournisseur.php?action=setmod&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';
            }

            print '</tr>';
        }
    }
    closedir($handle);
}

print '</table>';

/*
 *  PDF
 */
print '<br>';
print_titre("Modèles de commande fournisseur pdf");

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '  <td align="center" colspan="2">'.$langs->trans("Activated")."</td>\n";
print '  <td align="center">'.$langs->trans("Default")."</td>\n";
print "</tr>\n";

clearstatcache();

$def = array();
$sql = "SELECT nom FROM ".MAIN_DB_PREFIX."commande_fournisseur_model_pdf";
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
$dir = "../fourn/commande/modules/pdf/";
$handle=opendir($dir);
$var=True;
while (($file = readdir($handle))!==false)
{
	if (eregi('\.modules\.php$',$file) && substr($file,0,4) == 'pdf_')
    {
        $var = !$var;
        $name = substr($file, 4, strlen($file) -16);
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
	  print '<a href="fournisseur.php?action=del&amp;value='.$name.'">'.$langs->trans("Disable").'</a>';
	}
      else
	{
	  print "&nbsp;";
	  print "</td>\n  <td>";
	  print '<a href="fournisseur.php?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
	}

      print "</td>\n  <td align=\"center\">";

      if ($commande_fournisseur_addon_var_pdf == "$name")
	{
	  print img_tick();
	}
      else
	{
      print '<a href="fournisseur.php?action=setpdf&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
	}
      print '</td></tr>';
    }
}
closedir($handle);

print '</table>';

llxFooter('$Date$ - $Revision$');
?>
