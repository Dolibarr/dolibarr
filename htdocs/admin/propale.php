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
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_POST["action"] == 'nbprod')
{
    dolibarr_set_const($db, "PROPALE_NEW_FORM_NB_PRODUCT",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_GET["action"] == 'set')
{
	$type='propal';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
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
	
    if (dolibarr_set_const($db, "PROPALE_ADDON_PDF",$_GET["value"]))
    {
        // La constante qui a été lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage cohérent
        $conf->global->PROPALE_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='propal';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model where nom = '".$_GET["value"]."'";
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


	if (dolibarr_set_const($db, "PROPALE_ADDON",$_GET["value"]))
    {
      // la constante qui a été lue en avant du nouveau set
      // on passe donc par une variable pour avoir un affichage cohérent
      $conf->global->PROPALE_ADDON = $_GET["value"];
    }
}


/*
 * Affiche page
 */

llxHeader('',$langs->trans("PropalSetup"));

$dir = "../includes/modules/propale/";
$html=new Form($db);


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
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
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

            $module = new $file;

            $var=!$var;
            print "<tr ".$bc[$var].">\n  <td width=\"140\">".$file."</td>";
            print "\n  <td>".$module->info()."</td>\n";
            
            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->PROPALE_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            }
            else
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
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

print_titre($langs->trans("ProposalsPDFModules"));

// Defini tableau def de modele propal
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'propal'";
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
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
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
		print '</td>';

		// Activé
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->PROPALE_ADDON_PDF != "$name") 
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
		if ($conf->global->PROPALE_ADDON_PDF == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';
		
		// Info
    	$htmltooltip =    '<b>'.$langs->trans("Type").'</b>: '.($obj->type?$obj->type:$langs->trans("Unknown"));
    	$htmltooltip.='<br><b>'.$langs->trans("Width").'</b>: '.$obj->page_largeur;
    	$htmltooltip.='<br><b>'.$langs->trans("Height").'</b>: '.$obj->page_hauteur;
    	$htmltooltip.='<br>'.$langs->trans("FeaturesSupported").':';
    	$htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($obj->option_logo);
    	$htmltooltip.='<br><b>'.$langs->trans("PaymentMode").'</b>: '.yn($obj->option_modereg);
    	$htmltooltip.='<br><b>'.$langs->trans("PaymentConditions").'</b>: '.yn($obj->option_condreg);
    	print '<td align="center" '.$html->tooltip_properties($htmltooltip).'>'.img_help(0).'</td>';

        print "</tr>\n";
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
print "<td align=\"left\"><input size=\"3\" class=\"flat\" type=\"text\" name=\"value\" value=\"".PROPALE_NEW_FORM_NB_PRODUCT."\"></td>";
print '<td><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
