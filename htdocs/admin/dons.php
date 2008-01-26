<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/admin/dons.php
		\ingroup    dons
		\brief      Page d'administration/configuration du module Dons
		\version    $Revision$
*/
require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/don.class.php");

$langs->load("admin");
$langs->load("donations");

if (!$user->admin)
  accessforbidden();


$typeconst=array('yesno','texte','chaine');

/*
 * Action
 */
if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$don = new Don($db);
	$don->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/dons/";
	$file = $modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = $modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($don) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=don&file=SPECIMEN.html");
			return;
		}
	}
} 

if ($_GET["action"] == 'setdoc')
{
	$db->begin();
	
    if (dolibarr_set_const($db, "DON_ADDON_MODEL",$_GET["value"]))
    {
        $conf->global->DON_ADDON_MODEL = $_GET["value"];
    }

    // On active le modele
    $type='donation';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
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

if ($_GET["action"] == 'set')
{
	$type='donation';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $type='donation';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}


/*
 * Affiche page
 */

$dir = "../includes/modules/dons/";
$html=new Form($db);

llxHeader('',$langs->trans("DonationsSetup"),'DonConfiguration');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("DonationsSetup"),$linkback,'setup');


/*
 *  PDF
 */
print '<br>';
print_titre($langs->trans("DonationsModels"));

// Defini tableau def de modele
$type='donation';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
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

print '<table class="noborder" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Info").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
    if (eregi('\.modules\.php$',$file))
    {
        $var = !$var;
        $name = substr($file, 0, strlen($file) -12);
        $classname = substr($file, 0, strlen($file) -12);
	
		require_once($dir.'/'.$file);
		$module=new $classname($db);

        print '<tr '.$bc[$var].'><td width=\"100\">';
        echo $module->name;
        print '</td>';
        print '<td>';
        print $module->description;
        print '</td>';

		// Activé
		if (in_array($name, $def))
		{
	        print "<td align=\"center\">\n";
			if ($conf->global->DON_ADDON_MODEL == $name)
	        {
	            print img_tick($langs->trans("Enabled"));
	        }
	        else
	        {
	            print '&nbsp;';
	            print '</td><td align="center">';
	            print '<a href="dons.php?action=setdoc&value='.$name.'">'.$langs->trans("Activate").'</a>';
	        }
	        print '</td>';
		}
		else
		{
			print "<td align=\"center\">\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
			print "</td>";
		}

		// Defaut
		print "<td align=\"center\">";
		if ($conf->global->DON_ADDON_MODEL == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';
		
		// Info
    	$htmltooltip =    '<b>'.$langs->trans("Name").'</b>: '.$module->name;
    	$htmltooltip.='<br><b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
    	$htmltooltip.='<br><b>'.$langs->trans("Height").'/'.$langs->trans("Width").'</b>: '.$module->page_hauteur.'/'.$module->page_largeur;
    	$htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
    	$htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
    	$htmltooltip.='<br><b>'.$langs->trans("MultiLanguage").'</b>: '.yn($module->option_multilang);
    	print '<td align="center">';
    	print $html->textwithhelp('',$htmltooltip,1,0);
    	print '</td>';
    	print '<td align="center">';
    	print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'" target="specimen">'.img_object($langs->trans("Preview"),'generic').'</a>';
    	print '</td>';
    	
        print "</tr>\n";

    }
}
closedir($handle);

print '</table>';


print "<br>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>
