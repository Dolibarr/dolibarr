<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/admin/societe.php
 *	\ingroup    company
 *	\brief      Third party module setup page
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
accessforbidden();


/*
 * Actions
 */
if ($_GET["action"] == 'setcodeclient')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECLIENT_ADDON",$_GET["value"],'chaine',0,'',$conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_GET["action"] == 'setcodecompta')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECOMPTA_ADDON",$_GET["value"],'chaine',0,'',$conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_POST["action"] == 'usesearchtoselectcompany')
{
	if (dolibarr_set_const($db, "COMPANY_USE_SEARCH_TO_SELECT", $_POST["activate_usesearchtoselectcompany"],'chaine',0,'',$conf->entity))
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// define constants for tigre model
if ($_POST["action"] == 'setModuleOptions')
{
	$param=$_POST["param1"];
	$value=$_POST["value1"];
	dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'set')
{
	$type='company';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

	if (dolibarr_set_const($db, "COMPANY_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->COMPANY_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='company';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$_GET["value"]."'";
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



/*
 * 	View
 */

$form=new Form($db);


llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CompanySetup"),$linkback,'setup');

print "<br>";


// Choix du module de gestion des codes clients / fournisseurs

print_titre($langs->trans("CompanyCodeChecker"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td align="center">'.$langs->trans("Status").'</td>';
print '  <td align="center" width="30">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
	$var = true;

	// Loop on each module find in opened directory
	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 15) == 'mod_codeclient_' && substr($file, -3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

			$modCodeTiers = new $file;

			// Show modules according to features level
			if ($modCodeTiers->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
			if ($modCodeTiers->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

			$var = !$var;
			print "<tr ".$bc[$var].">\n  <td width=\"140\">".$modCodeTiers->nom."</td>\n  <td>";
			print $modCodeTiers->info($langs);
			print "</td>\n";
			print '<td nowrap="nowrap">'.$modCodeTiers->getExample($langs)."</td>\n";

			if ($conf->global->SOCIETE_CODECLIENT_ADDON == "$file")
			{
				print "<td align=\"center\">\n";
				print img_picto($langs->trans("Activated"),'on');
				print "</td>\n";
			}
			else
			{
				print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setcodeclient&amp;value='.$file.'">';
				print img_picto($langs->trans("Disabled"),'off');
				print '</a></td>';
			}

			print '<td align="center">';
			$s=$modCodeTiers->getToolTip($langs,$soc,-1);
			print $form->textwithpicto('',$s,1);
			print '</td>';

			print '</tr>';
		}
	}
	closedir($handle);
}
print '</table>';


print "<br>";


// Choix du module de gestion des codes compta

print_titre($langs->trans("AccountCodeManager"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Status").'</td>';
print '<td align="center" width="30">&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/societe/";
$handle = opendir($dir);
if ($handle)
{
	$var = true;
	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 15) == 'mod_codecompta_' && substr($file, -3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$file.".php");

			$modCodeCompta = new $file;
			$var = !$var;

			print '<tr '.$bc[$var].'>';
			print '<td width="140">'.$modCodeCompta->nom."</td><td>\n";
			print $modCodeCompta->info($langs);
			print '</td>';
			print '<td nowrap="nowrap">'.$modCodeCompta->getExample($langs)."</td>\n";

			if ($conf->global->SOCIETE_CODECOMPTA_ADDON == "$file")
			{
				print '<td align="center">';
				print img_picto($langs->trans("Activated"),'on');
				print '</td>';
			}
			else
			{
				print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setcodecompta&amp;value='.$file.'">';
				print img_picto($langs->trans("Disabled"),'off');
				print '</a></td>';
			}
			print '<td>&nbsp;</td>';
			print "</tr>\n";
		}
	}
	closedir($handle);
}
print "</table>\n";


//
/*
 *  Modeles de documents
 */
print '<br>';
print_titre($langs->trans("ModelModules"));

// Defini tableau def de modele invoice
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'company'";
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
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();


$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/includes/modules/societe/doc";

	if (is_dir($dir))
	{
		$handle=opendir($dir);
		if ($handle)
		{
			while (($file = readdir($handle))!==false)
			{
				if (preg_match('/\.modules\.php$/i',$file))
				{
					$var = !$var;
					$name = substr($file, 4, strlen($file) -16);
					$classname = substr($file, 0, strlen($file) -12);

					require_once($dir.'/'.$file);
					$module = new $classname($db);

					print '<tr '.$bc[$var].'><td width="100">';
					echo $module->name;
					print "</td><td>\n";
					if (method_exists($module,'info')) print $module->info($langs);
					else print $module->description;
					print '</td>';

					// Active
					if (in_array($name, $def))
					{
						print "<td align=\"center\">\n";
						if ($conf->global->COMPANY_ADDON_PDF != "$name")
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
							print img_picto($langs->trans("Enabled"),'on');
							print '</a>';
						}
						else
						{
							print img_picto($langs->trans("Enabled"),'on');
						}
						print "</td>";
					}
					else
					{
						print "<td align=\"center\">\n";
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
						print "</td>";
					}

					// Defaut
					print "<td align=\"center\">";
					if ($conf->global->COMPANY_ADDON_PDF == "$name")
					{
						print img_picto($langs->trans("Default"),'on');
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
					}
					print '</td>';

					// Info
					$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
					$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
					if ($modele->type == 'pdf')
					{
						$htmltooltip.='<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
					}
					$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
					$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraft").': '.yn($module->option_draft_watermark,1,1);


					print '<td align="center">';
					print $form->textwithpicto('',$htmltooltip,1,0);
					print '</td>';
					print '<td align="center">';
					if ($modele->type == 'pdf')
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
					}
					else
					{
						print '<a href="#">'.img_object($langs->trans("PreviewNotAvailable"),'generic').'</a>';
					}
					print '</td>';

					print "</tr>\n";
				}
			}
			closedir($handle);
		}
	}
}
print '</table>';

print '<br>';

print_titre($langs->trans("Other"));

// Autres options
$html=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

// Utilisation formulaire Ajax sur choix societe
$var=!$var;
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="usesearchtoselectcompany">';
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("UseSearchToSelectCompany").'</td>';
if (! $conf->use_javascript_ajax)
{
	print '<td nowrap="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
}
else
{
	print '<td width="60" align="right">';
	print $html->selectyesno("activate_usesearchtoselectcompany",$conf->global->COMPANY_USE_SEARCH_TO_SELECT,1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';
print '</form>';

print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
