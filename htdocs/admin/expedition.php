<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/admin/expedition.php
 \ingroup    expedition
 \brief      Page d'administration/configuration du module Expedition
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/expedition/expedition.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("sendings");
$langs->load("deliveries");

if (!$user->admin) accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$exp = new Expedition($db);
	$exp->initAsSpecimen();
	//$exp->fetch_commande();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/expedition/pdf/";
	$file = "pdf_expedition_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_expedition_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($exp,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expedition&file=SPECIMEN.pdf");
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
	$type='shipping';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$type='shipping';
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

	if (dolibarr_set_const($db, "EXPEDITION_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->EXPEDITION_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='shipping';
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

// \todo A quoi servent les methode d'expedition ?
if ($_GET["action"] == 'setmethod' || $_GET["action"] == 'setmod')
{
	$module=$_GET["module"];
	$moduleid=$_GET["moduleid"];
	$statut=$_GET["statut"];

	require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/methode_expedition_$module.modules.php");

	$class = "methode_expedition_$module";
	$expem = new $class($db);

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."expedition_methode";
	$sql.= " WHERE rowid = ".$moduleid;
	
	$resql = $db->query($sql);
	if ($resql && ($statut == 1 || $_GET["action"] == 'setmod'))
	{
		$db->begin();

		$sqlu = "UPDATE ".MAIN_DB_PREFIX."expedition_methode";
		$sqlu.= " SET statut=1";
		$sqlu.= " WHERE rowid=".$moduleid;
		
		$result=$db->query($sqlu);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}

	if ($statut == 1 || $_GET["action"] == 'setmod')
	{
		$db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition_methode (rowid,code,libelle,description,statut)";
		$sql.= " VALUES (".$moduleid.",'".$expem->code."','".$expem->name."','".$expem->description."',1)";
		$result=$db->query($sql);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			//dol_print_error($db);
			$db->rollback();
		}
	}
	else if ($statut == 0)
	{
		$db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition_methode";
		$sql.= " SET statut=0";
		$sql.= " WHERE rowid=".$moduleid;
		$result=$db->query($sql);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}
}

if ($_GET["action"] == 'setmod')
{
	// \todo Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "EXPEDITION_ADDON",$_GET["module"],'chaine',0,'',$conf->entity);
}



/*
 * Viewe
 */

$dir = DOL_DOCUMENT_ROOT."/includes/modules/expedition/";
$html=new Form($db);


llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Setup"),$linkback,'setup');
print '<br>';


if ($mesg) print $mesg.'<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
$head[$h][1] = $langs->trans("Sending");
$hselected=$h;
$h++;

if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
	$head[$h][1] = $langs->trans("Receivings");
	$h++;
}

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));



/*
 *  Modeles de documents
 */
print_titre($langs->trans("SendingsReceiptModel"));

// Defini tableau def de modele invoice
$type="shipping";
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
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dir = DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/";

if(is_dir($dir))
{
	$handle=opendir($dir);
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,15) == 'pdf_expedition_')
		{
			$name = substr($file, 15, strlen($file) - 27);
			$classname = substr($file, 0, strlen($file) - 12);

			$var=!$var;
			print "<tr $bc[$var]><td>";
			print $name;
			print "</td><td>\n";
			require_once($dir.$file);
			$module = new $classname();

			print $module->description;
			print '</td>';

			// Activ
			if (in_array($name, $def))
			{
				print "<td align=\"center\">\n";
				if ($conf->global->EXPEDITION_ADDON_PDF != $name)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
					print img_tick($langs->trans("Disable"));
					print '</a>';
				}
				else
				{
					print img_tick($langs->trans("Activated"));
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
			if ($conf->global->EXPEDITION_ADDON_PDF == $name)
			{
				print img_tick($langs->trans("Default"));
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
			}
			print '</td>';

			// Info
			$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
			$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
			$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
			$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
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

$db->close();

llxFooter();
?>
