<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/admin/livraison.php
        \ingroup    livraison
        \brief      Page d'administration/configuration du module Livraison
        \version    $Id$
*/
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("sendings");
$langs->load("deliveries");

if (!$user->admin) accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'updateMask')
{
	$maskconstdelivery=$_POST['maskconstdelivery'];
	$maskdelivery=$_POST['maskdelivery'];
	if ($maskconstdelivery)  dolibarr_set_const($db,$maskconstdelivery,$maskdelivery,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$sending = new Livraison($db);
	$sending->initAsSpecimen();
	//$sending->fetch_commande();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/livraison/pdf/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($sending,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=livraison&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			$mesg='<div class="error">'.$obj->error.'</div>';
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

if ($_GET["action"] == 'set')
{
	$type='delivery';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $type='delivery';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE nom = '".$_GET["value"];
    $sql.= " AND type = '".$type."'";
    $sql.= " AND entity = ".$conf->entity;

    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

    if (dolibarr_set_const($db, "LIVRAISON_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
    {
        $conf->global->LIVRAISON_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='delivery';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$_GET["value"];
    $sql_del.= " AND type = '".$type."'";
    $sql_del.= " AND entity = ".$conf->entity;
    $result1=$db->query($sql_del);
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
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

if ($_POST["action"] == 'set_DELIVERY_FREE_TEXT')
{
    dolibarr_set_const($db, "DELIVERY_FREE_TEXT",$_POST["DELIVERY_FREE_TEXT"],'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

	dolibarr_set_const($db, "LIVRAISON_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}


/*
 * View
 */

$dir = DOL_DOCUMENT_ROOT."/includes/modules/livraison/";
$html=new Form($db);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Setup"),$linkback,'setup');
print '<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

if ($conf->global->MAIN_SUBMODULE_EXPEDITION)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
	$head[$h][1] = $langs->trans("Sending");
	$h++;
}

$head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
$head[$h][1] = $langs->trans("Receivings");
$hselected=$h;
$h++;


dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 *  Module numerotation
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);

$var=true;
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 14) == 'mod_livraison_' && substr($file, strlen($file)-3, 3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/livraison/".$file.".php");

			$module = new $file;

			// Show modules according to features level
		    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
		    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

		    if ($module->isEnabled())
		    {
				$var=!$var;
				print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
				print $module->info();
				print '</td>';

				// Affiche example
				print '<td nowrap="nowrap">'.$module->getExample().'</td>';

				print '<td align="center">';
				if ($conf->global->LIVRAISON_ADDON == "$file")
				{
					print img_tick($langs->trans("Activated"));
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
				}
				print '</td>';

				$livraison=new Livraison($db);
				$livraison->initAsSpecimen();

				// Info
				$htmltooltip='';
				$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
				$facture->type=0;
		        $nextval=$module->getNextValue($mysoc,$livraison);
				if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
				{
					$htmltooltip.=''.$langs->trans("NextValue").': ';
			        if ($nextval)
					{
						$htmltooltip.=$nextval.'<br>';
					}
					else
					{
						$htmltooltip.=$langs->trans($module->error).'<br>';
					}
				}

				print '<td align="center">';
				print $html->textwithpicto('',$htmltooltip,1,0);
				print '</td>';

				print '</tr>';
		    }
		}
    }
    closedir($handle);
}
print '</table>';


/*
 *  Modeles de documents
 */
print '<br>';
print_titre($langs->trans("DeliveryOrderModel"));

// Defini tableau def de modele invoice
$type="delivery";
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;

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
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Action").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dir = DOL_DOCUMENT_ROOT."/includes/modules/livraison/pdf/";

if(is_dir($dir))
{
	$handle=opendir($dir);
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
		{
			$name = substr($file, 4, strlen($file) - 16);
			$classname = substr($file, 0, strlen($file) - 12);

			$var=!$var;
			print "<tr $bc[$var]><td>";
			print $name;
			print "</td><td>\n";
			require_once($dir.$file);
			$module = new $classname($db);

			print $module->description;
			print '</td>';

			// Activ
			if (in_array($name, $def))
			{
				print "<td align=\"center\">\n";
				if ($conf->global->LIVRAISON_ADDON_PDF != "$name")
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
			if ($conf->global->LIVRAISON_ADDON_PDF == "$name")
			{
				print img_tick($langs->trans("Default"));
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
			}
			print '</td>';

			// Info
			$htmltooltip =    ''.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
			$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
			$htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
			$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
	    	print '<td align="center">';
	    	print $html->textwithpicto('',$htmltooltip,1,0);
	    	print '</td>';
	    	print '<td align="center">';
	    	print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'sending').'</a>';
	    	print '</td>';

			print '</tr>';
		}
	}
	closedir($handle);
}
else
{
	print "<tr><td><b>ERROR</b>: $dir is not a directory !</td></tr>\n";
}
print '</table>';

/*
*
*
*/
print "<br>";
print_titre($langs->trans("OtherOptions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=true;

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_DELIVERY_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnDeliveryReceipts").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="DELIVERY_FREE_TEXT" class="flat" cols="120">'.$conf->global->DELIVERY_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

print '</table>';

$db->close();

llxFooter();
?>
