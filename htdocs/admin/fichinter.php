<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
    	\file       htdocs/admin/fichinter.php
		\ingroup    fichinter
		\brief      Page d'administration/configuration du module FicheInter
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fichinter/fichinter.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("interventions");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/fichinter/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($inter,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ficheinter&file=SPECIMEN.pdf");
			return;
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
	}
}

if ($_GET["action"] == 'set')
{
	$type='ficheinter';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $type='ficheinter';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();
	
    if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF",$_GET["value"]))
    {
        // La constante qui a �t� lue en avant du nouveau set
        // on passe donc par une variable pour avoir un affichage coh�rent
        $conf->global->FICHEINTER_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='ficheinter';
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

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre activ�
    // par appel methode canBeActivated

	dolibarr_set_const($db, "FICHEINTER_ADDON",$_GET["value"]);
}

// d�fini les constantes du mod�le arctic
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "FICHEINTER_NUM_MATRICE",$_POST["matrice"]);
if ($_POST["action"] == 'updatePrefix') dolibarr_set_const($db, "FICHEINTER_NUM_PREFIX",$_POST["prefix"]);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "FICHEINTER_NUM_DELTA",$_POST["offset"]);
if ($_POST["action"] == 'setFiscalMonth') dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START",$_POST["fiscalmonth"]);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "FICHEINTER_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"]);


/*
 * Affichage page
 */

llxHeader();

$dir=DOL_DOCUMENT_ROOT."/includes/modules/fichinter/";
$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("InterventionsSetup"),$linkback,'setup');

print "<br>";


print_titre($langs->trans("FicheinterNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    
    while (($file = readdir($handle))!==false)
    {
        if (eregi('^(mod_.*)\.php$',$file,$reg))
        {
            $file = $reg[1];
            $className = substr($file,4);

            require_once($dir.$file.".php");

            $module = new $file;

            $var=!$var;
            print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
            print $module->info();
            print '</td>';

            // Examples
            print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

            print '<td align="center">';
            if ($conf->global->FICHEINTER_ADDON == $className)
            {
                print img_tick($langs->trans("Activated"));
            }
            else
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$className.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
            }
            print '</td>';

			    $ficheinter=new Fichinter($db);
			    
			    // Info
			    $htmltooltip='';
	        $nextval=$module->getNextValue($mysoc,$ficheinter);
	        if ($nextval != $langs->trans("NotAvailable"))
	        {
	            $htmltooltip='<b>'.$langs->trans("NextValue").'</b>: '.$nextval;
	        }
	    	print '<td align="center">';
	    	print $html->textwithhelp('',$htmltooltip,1,0);
	    	print '</td>';

            print '</tr>';
        }
    }
    closedir($handle);
}

print '</table><br>';



print_titre($langs->trans("TemplatePDFInterventions"));

// Defini tableau def des modeles
$type='ficheinter';
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


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;

$handle=opendir($dir);
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
    {
      $name = substr($file, 4, strlen($file) -16);
      $classname = substr($file, 0, strlen($file) -12);

      $var=!$var;

      print '<tr '.$bc[$var].'><td>';
      echo "$name";
      print "</td><td>\n";
      require_once($dir.$file);
      $module = new $classname();
      print $module->description;
      print '</td>';

		// Activ�
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->FICHEINTER_ADDON_PDF != "$name") 
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
		if ($conf->global->FICHEINTER_ADDON_PDF == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';
		
		// Info
    	$htmltooltip =    '<b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
    	$htmltooltip.='<br><b>'.$langs->trans("Width").'</b>: '.$module->page_largeur;
    	$htmltooltip.='<br><b>'.$langs->trans("Height").'</b>: '.$module->page_hauteur;
    	$htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
    	$htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
    	print '<td align="center">';
    	print $html->textwithhelp('',$htmltooltip,1,0);
    	print '</td>';
    	print '<td align="center">';
    	print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
    	print '</td>';

		print '</tr>';
    }
}
closedir($handle);

print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
