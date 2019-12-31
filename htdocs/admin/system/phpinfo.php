<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016       Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       htdocs/admin/system/phpinfo.php
 *		\brief      Page des infos systeme de php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();



/*
 * View
 */

llxHeader();

$title='InfoPHP';

if (isset($title))
{
	print load_fiche_titre($langs->trans($title), '', 'title_setup');
}


// Check PHP setup is OK
$maxphp=@ini_get('upload_max_filesize');	// In unknown
if (preg_match('/k$/i', $maxphp)) $maxphp=$maxphp*1;
if (preg_match('/m$/i', $maxphp)) $maxphp=$maxphp*1024;
if (preg_match('/g$/i', $maxphp)) $maxphp=$maxphp*1024*1024;
if (preg_match('/t$/i', $maxphp)) $maxphp=$maxphp*1024*1024*1024;
$maxphp2=@ini_get('post_max_size');			// In unknown
if (preg_match('/k$/i', $maxphp2)) $maxphp2=$maxphp2*1;
if (preg_match('/m$/i', $maxphp2)) $maxphp2=$maxphp2*1024;
if (preg_match('/g$/i', $maxphp2)) $maxphp2=$maxphp2*1024*1024;
if (preg_match('/t$/i', $maxphp2)) $maxphp2=$maxphp2*1024*1024*1024;
if ($maxphp > 0 && $maxphp2 > 0 && $maxphp > $maxphp2)
{
	$langs->load("errors");
	print info_admin($langs->trans("WarningParamUploadMaxFileSizeHigherThanPostMaxSize", @ini_get('upload_max_filesize'), @ini_get('post_max_size')), 0, 0, 0, 'warning');
	print '<br>';
}


print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
print "\n";


// Get PHP version
$phpversion=version_php();
print '<tr class="oddeven"><td  width="220px">'.$langs->trans("Version")."</td><td>".$phpversion."</td></tr>\n";

print '</table>';
print '<br>';



// Get php_info array
$phparray=phpinfo_array();
foreach($phparray as $key => $value)
{
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder">';
	print '<tr class="liste_titre">';
	//print '<td width="220px">'.$langs->trans("Parameter").'</td>';
	print '<td width="220px">'.$key.'</td>';
	print '<td colspan="2">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	//var_dump($value);
	foreach($value as $keyparam => $keyvalue)
	{
		if (! is_array($keyvalue))
		{
			print '<tr class="oddeven">';
			print '<td>'.$keyparam.'</td>';
			$valtoshow=$keyvalue;
			if ($keyparam == 'X-ChromePhp-Data') $valtoshow=dol_trunc($keyvalue, 80);
			print '<td colspan="2">';
			if ($keyparam == 'Path') $valtoshow=implode('; ', explode(';', trim($valtoshow)));
			if ($keyparam == 'PATH') $valtoshow=implode('; ', explode(';', trim($valtoshow)));
			if ($keyparam == '_SERVER["PATH"]') $valtoshow=implode('; ', explode(';', trim($valtoshow)));
			print $valtoshow;
			print '</td>';
			print '</tr>';
		}
		else
		{
			print '<tr class="oddeven">';
			print '<td>'.$keyparam.'</td>';
			$i=0;
			foreach($keyvalue as $keyparam2 => $keyvalue2)
			{
				print '<td>';
				$valtoshow=$keyvalue2;
				if ($keyparam == 'disable_functions') $valtoshow=implode(', ', explode(',', trim($valtoshow)));
				//print $keyparam;
				print $valtoshow;
				$i++;
				print '</td>';
			}
			print '</tr>';
		}
	}
	print '</table>';
	print '</div>';
	print '<br>';
}

// End of page
llxFooter();
$db->close();
