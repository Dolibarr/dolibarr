<?php
/* Copyright (C) 2005-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/admin/system/modules.php
 *  \brief      File to list all Dolibarr modules
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

if (!$user->admin)
	accessforbidden();


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("AvailableModules"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("ToActivateModule").'</span><br>';
print "<br>\n";

$modules = array();
$modules_names = array();
$modules_files = array();
$modules_fullpath = array();
$modulesdir = dolGetModulesDirs();

// Load list of modules
$i = 0;
foreach ($modulesdir as $dir)
{
	$handle = @opendir(dol_osencode($dir));
	if (is_resource($handle))
	{
		while (($file = readdir($handle)) !== false)
		{
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php')
			{
				$modName = substr($file, 0, dol_strlen($file) - 10);

				if ($modName)
				{
					//print 'xx'.$dir.$file.'<br>';
					if (in_array($file, $modules_files))
					{
						// File duplicate
						print "Warning duplicate file found : ".$file." (Found ".$dir.$file.", already found ".$modules_fullpath[$file].")<br>";
					}
					else
					{
						// File to load
						$res = include_once $dir.$file;
						if (class_exists($modName))
						{
							try {
								$objMod = new $modName($db);

								$modules[$objMod->numero] = $objMod;
								$modules_names[$objMod->numero] = $objMod->name;
								$modules_files[$objMod->numero] = $file;
								$modules_fullpath[$file] = $dir.$file;
								$picto[$objMod->numero] = (isset($objMod->picto) && $objMod->picto) ? $objMod->picto : 'generic';
							}
							catch (Exception $e)
							{
								dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
							}
						}
						else
						{
							print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
						}
					}
				}
			}
		}
		closedir($handle);
	}
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Modules").'</td>';
print '<td>'.$langs->trans("Version").'</td>';
print '<td class="center">'.$langs->trans("IdModule").'</td>';
print '<td>'.$langs->trans("IdPermissions").'</td>';
print '</tr>';
$var = false;
$sortorder = $modules_names;
ksort($sortorder);
$rights_ids = array();
foreach ($sortorder as $numero=>$name)
{
	$idperms = "";
	// Module
	print '<tr class="oddeven"><td width="300" class="nowrap">';
	$alt = $name.' - '.$modules_files[$numero];
	if (!empty($picto[$numero]))
	{
	   	if (preg_match('/^\//', $picto[$numero])) print img_picto($alt, $picto[$numero], 'width="14px"', 1);
	   	else print img_object($alt, $picto[$numero], 'width="14px"');
	}
	else
	{
	  	print img_object($alt, $picto[$numero], 'width="14px"');
	}
	print ' '.$modules[$numero]->getName();
	print "</td>";
	// Version
	print '<td>'.$modules[$numero]->getVersion().'</td>';
	// Id
	print '<td class="center">'.$numero.'</td>';
	// Permissions
	if ($modules[$numero]->rights)
	{
		foreach ($modules[$numero]->rights as $rights)
		{
			$idperms .= ($idperms ? ", " : "").$rights[0];
			array_push($rights_ids, $rights[0]);
		}
	}
	print '<td>'.($idperms ? $idperms : "&nbsp;").'</td>';
	print "</tr>\n";
}
print '</table>';
print '</div>';
print '<br>';
sort($rights_ids);
$old = '';
foreach ($rights_ids as $right_id)
{
	if ($old == $right_id) print "Warning duplicate id on permission : ".$right_id."<br>";
	$old = $right_id;
}

// End of page
llxFooter();
$db->close();
