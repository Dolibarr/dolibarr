<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin         <regis.houssin@cap-networks.com>
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
	    \file       htdocs/admin/avoir.php
		\ingroup    avoir
		\brief      Page d'administration/configuration du module Avoir
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_GET["action"] == 'set')
{
	$type='creditnote';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom='".$_GET["value"]."'";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

    if (dolibarr_set_const($db, "AVOIR_ADDON_PDF",$_GET["value"]))
    {
        // La constante qui a été lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage cohérent
        $conf->global->AVOIR_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
	$type='creditnote';
    $sql_del = "delete from ".MAIN_DB_PREFIX."document_model where nom = '".$_GET["value"]."'";
    $result1=$db->query($sql_del);
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type) VALUES ('".$_GET["value"]."','".$type."')";
    $result2=$db->query($sql);
    if ($result1 && $result2) 
    {
		$db->commit();
    }
    else
    {
    	$db->rollback();
    }
}

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre activé
    // par appel methode canBeActivated



	if (dolibarr_set_const($db, "AVOIR_ADDON",$_GET["value"]))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $conf->global->AVOIR_ADDON = $_GET["value"];
    }
}


/*
 * Affiche page
 */

llxHeader("","");

$dir = DOL_DOCUMENT_ROOT .'/avoir/modules/';
$html=new Form($db);

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/facture.php";
$head[$h][1] = $langs->trans("Invoices");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/avoir.php";
$head[$h][1] = $langs->trans("CreditNotes");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 *  Module numérotation
 */

print_titre($langs->trans("DiscountsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);

$var=true;
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 10) == 'mod_avoir_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/avoir/modules/".$file.".php");

            $module = new $file;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print "\n  <td>".$module->info()."</td>\n";

	        // Affiche example
	        print '<td nowrap="nowrap">'.$module->getExample().'</td>';
	
	        print '<td align="center">';
	        if ($conf->global->FACTURE_ADDON == "$file")
	        {
	            print img_tick($langs->trans("Activated"));
	        }
	        else
	        {
	            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
	        }
	        print '</td>';
	
			// Info
			$htmltooltip='';
	        $nextval=$module->getNextValue();
	        if ($nextval != $langs->trans("NotAvailable"))
	        {
	            $htmltooltip='<b>'.$langs->trans("NextValue").'</b>: '.$nextval;
	        }
	    	print '<td align="center" '.$html->tooltip_properties($htmltooltip).'>';
	    	print ($htmltooltip?img_help(0):'');
	    	print '</td>';
    	
            print "</tr>\n";
        }
    }
    closedir($handle);
}
print "</table><br>\n";


/*
 * Modeles de documents
 */

print_titre($langs->trans("DiscountsPDFModules"));

// Defini tableau def de modele invoice
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'creditnote'";
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

$dir = DOL_DOCUMENT_ROOT .'/avoir/modules/pdf/';

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,10) == 'pdf_avoir_')
    {
      $name = substr($file, 10, strlen($file) - 24);
      $classname = substr($file, 0, strlen($file) -12);

      $var=!$var;
      print "<tr ".$bc[$var].">\n  <td>";
      print "$name";
      print "</td>\n  <td>\n";
      require_once($dir.$file);
      $obj = new $classname($db);
      print $obj->description;
      print '</td>';

		// Activé
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->FACTURE_AVOIR_ADDON_PDF != "$name") 
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
				print img_tick($langs->trans("Disable"));
				print '</a>';
			}
			else
			{
				print img_tick($langs->trans("Enabled"));
			}
			print "</td>";
		}
		else
		{
			print "<td align=\"center\">\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
			print "</td>";
		}

		// Defaut
		print "<td align=\"center\">";
		if ($conf->global->FACTURE_AVOIR_ADDON_PDF == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="propale.php?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';
		
		// Info
    	$htmltooltip =    '<b>'.$langs->trans("Type").'</b>: '.($obj->type?$obj->type:$langs->trans("Unknown"));
    	$htmltooltip.='<br><b>'.$langs->trans("Width").'</b>: '.$obj->page_largeur;
    	$htmltooltip.='<br><b>'.$langs->trans("Height").'</b>: '.$obj->page_hauteur;
    	$htmltooltip.='<br>'.$langs->trans("FeaturesSupported").':';
    	$htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($obj->option_logo);
    	print '<td align="center" '.$html->tooltip_properties($htmltooltip).'>'.img_help(0).'</td>';

		print '</tr>';
    }
}
closedir($handle);

print '</table>';

/*
 *  Repertoire
 */
print '<br>';
print_titre($langs->trans("PathToDocuments"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr ".$bc[false].">\n  <td width=\"140\">".$langs->trans("Directory")."</td>\n  <td>".$conf->avoir->dir_output."</td>\n</tr>\n";
print "</table>\n";


$db->close();

llxFooter();
?>
