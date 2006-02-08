<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004				Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004      	Benoit Mortier			  <benoit.mortier@opensides.be>
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
    	\file       htdocs/admin/commande.php
		\ingroup    commande
		\brief      Page d'administration-configuration du module Commande
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("orders");

llxHeader();

if (!$user->admin)
  accessforbidden();


// positionne la variable pour le test d'affichage de l'icone

$commande_addon_var = COMMANDE_ADDON;
$commande_addon_var_pdf = COMMANDE_ADDON_PDF;
$commande_rib_number_var = COMMANDE_RIB_NUMBER;

$commande_addon_var = COMMANDE_ADDON;


/*
 * Actions
 */

if ($_GET["action"] == 'setmod')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'COMMANDE_ADDON' ;";
	$db->query($sql);
	$sql = '';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('COMMANDE_ADDON','".$_GET["value"]."',0) ; ";

  if ($db->query($sql))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $commande_addon_var = $_GET["value"];
    }
}
if ($_GET["action"] == 'setpdf')
{
  if (dolibarr_set_const($db, "COMMANDE_ADDON_PDF",$_GET["value"])) $commande_addon_var_pdf = $_GET["value"];
}

$dir = "../includes/modules/commande/";

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

$dir = "../includes/modules/commande/";
$handle = opendir($dir);
if ($handle)
{
    $var=true;
    
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 13) == 'mod_commande_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/".$file.".php");

            $modCommande = new $file;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$modCommande->nom."</td><td>\n";
            print $modCommande->info();
            print '</td>';

            print '<td align="center" nowrap>';
            print $modCommande->getExample();
            print '</td>';

            if ($commande_addon_var == "$file")
            {
                print '<td align="center">';
                print img_tick();
                print '</td>';
            }
            else
            {
                print '<td align="center"><a href="commande.php?action=setmod&amp;value='.$file.'">'.$langs->trans("Activate").'</a></td>';
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
print_titre("Modèles de commande pdf");

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
    if (eregi('\.modules\.php$',$file) && substr($file,0,4) == 'pdf_')
    {
        $var = !$var;
        $name = substr($file, 4, strlen($file) -16);
        $classname = substr($file, 0, strlen($file) -12);
    
        print '<tr '.$bc[$var].'><td width="100">';
        echo "$name";
        print "</td><td>\n";
        require_once($dir.$file);
        $obj = new $classname($db);
    
        print $obj->description;
    
        print '</td><td align="center">';
    
        if ($commande_addon_var_pdf == "$name")
        {
            print '&nbsp;';
            print '</td><td align="center">';
            print img_tick();
        }
        else
        {
            print '&nbsp;';
			// print $commande_addon_var_pdf."iii";
            print '</td><td align="center">';
            print '<a href="commande.php?action=setpdf&amp;value='.$name.'">'.$langs->trans("Default").'</a>';
        }
        print "</td></tr>\n";
    
    }
}
closedir($handle);

print '</table>';

llxFooter('$Date$ - $Revision$');
?>
