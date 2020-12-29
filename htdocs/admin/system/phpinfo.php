<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016       Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2020       Tobias Sekan			<tobias.sekan@startmail.com>
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

$langs->loadLangs(array("admin", "install", "errors"));

if (!$user->admin)
	accessforbidden();



/*
 * View
 */

llxHeader();

$title = 'InfoPHP';

if (isset($title))
{
	print load_fiche_titre($langs->trans($title), '', 'title_setup');
}


// Check PHP setup is OK
$maxphp = @ini_get('upload_max_filesize'); // In unknown
if (preg_match('/k$/i', $maxphp)) $maxphp = $maxphp * 1;
if (preg_match('/m$/i', $maxphp)) $maxphp = $maxphp * 1024;
if (preg_match('/g$/i', $maxphp)) $maxphp = $maxphp * 1024 * 1024;
if (preg_match('/t$/i', $maxphp)) $maxphp = $maxphp * 1024 * 1024 * 1024;
$maxphp2 = @ini_get('post_max_size'); // In unknown
if (preg_match('/k$/i', $maxphp2)) $maxphp2 = $maxphp2 * 1;
if (preg_match('/m$/i', $maxphp2)) $maxphp2 = $maxphp2 * 1024;
if (preg_match('/g$/i', $maxphp2)) $maxphp2 = $maxphp2 * 1024 * 1024;
if (preg_match('/t$/i', $maxphp2)) $maxphp2 = $maxphp2 * 1024 * 1024 * 1024;
if ($maxphp > 0 && $maxphp2 > 0 && $maxphp > $maxphp2)
{
	$langs->load("errors");
	print info_admin($langs->trans("WarningParamUploadMaxFileSizeHigherThanPostMaxSize", @ini_get('upload_max_filesize'), @ini_get('post_max_size')), 0, 0, 0, 'warning');
	print '<br>';
}


print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

$ErrorPicturePath = "../../theme/eldy/img/error.png";
$WarningPicturePath = "../../theme/eldy/img/warning.png";
$OkayPicturePath = "../../theme/eldy/img/tick.png";

print '<tr><td width="220">'.$langs->trans("Version").'</td><td>';

$arrayphpminversionerror = array(5, 5, 0);
$arrayphpminversionwarning = array(5, 6, 0);

if (versioncompare(versionphparray(), $arrayphpminversionerror) < 0)
{
	print '<img src="'.$ErrorPicturePath.'" alt="Error"> '.$langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionerror));
} elseif (versioncompare(versionphparray(), $arrayphpminversionwarning) < 0)
{
	print '<img src="'.$WarningPicturePath.'" alt="Warning"> '.$langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionwarning));
} else {
	print '<img src="'.$OkayPicturePath.'" alt="Ok"> '.versiontostring(versionphparray());
}

print '</td></tr>';
print '<tr><td>GET and POST support</td><td>';

if (!isset($_GET["testget"]) && !isset($_POST["testpost"]) && !isset($_GET["mainmenu"]))	// We must keep $_GET and $_POST here
{
	print '<img src="'.$WarningPicturePath.'" alt="Warning"> '.$langs->trans("PHPSupportPOSTGETKo");
	print ' (<a href="'.$_SERVER["PHP_SELF"].'?testget=ok">'.$langs->trans("Recheck").'</a>)';
} else {
	print '<img src="'.$OkayPicturePath.'" alt="Ok"> '.$langs->trans("PHPSupportPOSTGETOk");
}

print '</td></tr>';
print '<tr><td>Sessions support</td><td>';
if (!function_exists("session_id"))
{
	print '<img src="'.$ErrorPicturePath.'" alt="Error"> '.$langs->trans("ErrorPHPDoesNotSupportSessions");
} else {
	print '<img src="'.$OkayPicturePath.'" alt="Ok"> '.$langs->trans("PHPSupportSessions");
}
print '</td></tr>';

print '<tr><td>UTF-8 support</td><td>';
if (!function_exists("utf8_encode"))
{
	print '<img src="'.$WarningPicturePath.'" alt="Warning"> '.$langs->trans("ErrorPHPDoesNotSupport", "UTF8");
} else {
	print '<img src="'.$OkayPicturePath.'" alt="Ok"> '.$langs->trans("PHPSupport", "UTF8");
}
print '</td></tr>';

print '</table>';

print '<br>';

$activatedExtensions = array();
$loadedExtensions    = array_map('strtolower', get_loaded_extensions(false));

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="200">'.$langs->trans("Extension").'</td>';
//print '<td align="center">'.$langs->trans("EnabledInSetup").'</td>';
print '<td align="center">'.$langs->trans("Loaded").'</td>';
print '<td align="center">'.$langs->trans("FunctionTest").'</td>';
print '<td>'.$langs->trans("Result").'</td>';
print '</tr>';

$functions = ["mb_check_encoding"];
$name      = "MBString";

print "<tr>";
print "<td>".$name."</td>";
//print getTableColumn($name, $activatedExtensions);
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

$functions = ["json_decode"];
$name      = "JSON";

print "<tr>";
print "<td>".$name."</td>";
//print getTableColumn($name, $activatedExtensions);
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

$functions = ["imagecreate"];
$name      = "GD";

print "<tr>";
print "<td>".$name."</td>";
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

$functions = ["curl_init"];
$name      = "Curl";

print "<tr>";
print "<td>".$name."</td>";
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost')
{
	$functions = ["locale_get_primary_language", "locale_get_region"];
	$name      = "Intl";

	print "<tr>";
	print "<td>".$name."</td>";
	print getTableColumn($name, $loadedExtensions);
	print getTableColumnFunction($functions);
	print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
	print "</tr>";
}

$functions = ["imap_open"];
$name      = "IMAP";

print "<tr>";
print "<td>".$name."</td>";
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

$functions = array();
$name      = "xDebug";

print "<tr>";
print "<td>".$name."</td>";
print getTableColumn($name, $loadedExtensions);
print getTableColumnFunction($functions);
print getResultColumn($name, $activatedExtensions, $loadedExtensions, $functions);
print "</tr>";

print '</table>';

print '<br>';

// Get php_info array
$phparray = phpinfo_array();
foreach ($phparray as $key => $value)
{
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<td width="220px">'.$key.'</td>';
	print '<td colspan="2">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	//var_dump($value);
	foreach ($value as $keyparam => $keyvalue)
	{
		if (!is_array($keyvalue))
		{
			print '<tr class="oddeven">';
			print '<td>'.$keyparam.'</td>';
			$valtoshow = $keyvalue;
			if ($keyparam == 'X-ChromePhp-Data') $valtoshow = dol_trunc($keyvalue, 80);
			print '<td colspan="2" class="wordbreak">';
			if ($keyparam == 'Path') $valtoshow = implode('; ', explode(';', trim($valtoshow)));
			if ($keyparam == 'PATH') $valtoshow = implode('; ', explode(';', trim($valtoshow)));
			if ($keyparam == '_SERVER["PATH"]') $valtoshow = implode('; ', explode(';', trim($valtoshow)));
			print $valtoshow;
			print '</td>';
			print '</tr>';
		} else {
			print '<tr class="oddeven">';
			print '<td class="wordbreak">'.$keyparam.'</td>';
			$i = 0;
			foreach ($keyvalue as $keyparam2 => $keyvalue2)
			{
				print '<td>';
				$valtoshow = $keyvalue2;
				if ($keyparam == 'disable_functions') $valtoshow = implode(', ', explode(',', trim($valtoshow)));
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


/**
 * Return a table column with a indicator (okay or warning), based on the given name and list
 *
 * @param string $name		The name to check inside the given list
 * @param array  $list      A list that should contains the given name
 *
 * @return string
 */
function getTableColumn($name, array $list)
{
	global $langs;

	$name = strtolower($name);
	$html = "<td align='center'>";

	if (in_array($name, $list))
	{
		if ($name == 'xdebug') $html .= '<img src="../../theme/eldy/img/warning.png" title="'.$langs->trans("ModuleActivated", "xdebug").'">';
		else $html .= '<img src="../../theme/eldy/img/tick.png" title="Ok">';
	} else {
		if ($name == 'xdebug') $html .= yn(0);
		else $html .= '<img src="../../theme/eldy/img/warning.png" title="Warning">';
	}

	$html .= "</td>";

	return $html;
}

/**
 * Return a table column with a indicator (okay or warning), based on the given functions to check
 *
 * @param array $functions	A list with functions to check
 *
 * @return string
 */
function getTableColumnFunction(array $functions)
{
	if (count($functions) < 1)
	{
		return "<td align='center'>-</td>";
	}

	$result = true;
	$html   = "<td align='center'>";

	foreach ($functions as $function)
	{
		$result = $result && function_exists($function);
	}

	if ($result)
	{
		$html .= '<img src="../../theme/eldy/img/tick.png" alt="Ok">';
	} else {
		$html .= '<img src="../../theme/eldy/img/warning.png" alt="Warning">';
	}

	$html .= "</td>";

	return $html;
}

/**
 * Return a result column with a translated result text
 *
 * @param string $name			The name of the PHP extension
 * @param array $activated		A list with all activated PHP extensions. Deprecated.
 * @param array $loaded			A list with all loaded PHP extensions
 * @param array $functions		A list with all PHP functions to check
 *
 * @return string
 */
function getResultColumn($name, array $activated, array $loaded, array $functions)
{
	global $langs;

	$result = true;
	//$result = $result && in_array(strtolower($name), $activated);
	$result = $result && in_array(strtolower($name), $loaded);

	foreach ($functions as $function)
	{
	 	$result = $result && function_exists($function);
	}

	$html = "<td>";
	$html .= $result ? $langs->trans("PHPSupport", $name) : $langs->trans("ErrorPHPDoesNotSupport", $name);
	$html .= "</td>";

	return $html;
}
