<?php
/* Copyright (C) phpBSM
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014	   Juanjo Menent        <jmenent@2byte.es>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/datepicker.php
 *       \brief      File to manage popup date selector
 */

if (!defined('NOREQUIREUSER'))   define('NOREQUIREUSER', '1'); // disabled
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled cause need to load personalized language
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1); // disabled
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php

// Load translation files required by the page
$langs->loadLangs(array("main", "agenda"));

$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');

//var_dump($langs->defaultlang);
//var_dump($conf->format_date_short_java);
//var_dump($langs->trans("FormatDateShortJava"));


// URL http://mydolibarr/core/datepicker.php?mode=test&m=10&y=2038 can be used for tests
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n";
print '<html>'."\n";
print '<head>'."\n";
if (GETPOST('mode') && GETPOST('mode') == 'test')
{
	print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/lib_head.js.php"></script>'."\n";
} else {
	print '<title>'.$langs->trans("Calendar").'</title>';
}

// Define tradMonths javascript array (we define this in datapicker AND in parent page to avoid errors with IE8)
$tradTemp = array(
	$langs->trans("January"),
	$langs->trans("February"),
	$langs->trans("March"),
	$langs->trans("April"),
	$langs->trans("May"),
	$langs->trans("June"),
	$langs->trans("July"),
	$langs->trans("August"),
	$langs->trans("September"),
	$langs->trans("October"),
	$langs->trans("November"),
	$langs->trans("December")
);
print '<script type="text/javascript">';
print 'var tradMonths = [';
foreach ($tradTemp as $val)
{
	print '"'.addslashes($val).'",';
}
print '""];';
print '</script>'."\n";
print '</head>'."\n";

print '<body>'."\n";


$qualified = true;

if (!isset($_GET["sd"])) $_GET["sd"] = "00000000";

if (!isset($_GET["m"]) || !isset($_GET["y"])) $qualified = false;
if (isset($_GET["m"]) && isset($_GET["y"]))
{
	if ($_GET["m"] < 1 || $_GET["m"] > 12) $qualified = false;
	if ($_GET["y"] < 0 || $_GET["y"] > 9999) $qualified = false;
}

// If parameters provided, we show calendar
if ($qualified)
{
	//print $_GET["cm"].",".$_GET["sd"].",".$_GET["m"].",".$_GET["y"];exit;
	displayBox(GETPOST("sd", 'alpha'), GETPOST("m", 'int'), GETPOST("y", 'int'));
} else {
	dol_print_error('', 'ErrorBadParameters');
}


print '</body></html>'."\n";

/**
 * 	Convert date to timestamp
 *
 * 	@param	string		$mysqldate		Date YYYMMDD
 *  @return	integer					Timestamp
 */
function xyzToUnixTimestamp($mysqldate)
{
	$year = substr($mysqldate, 0, 4);
	$month = substr($mysqldate, 4, 2);
	$day = substr($mysqldate, 6, 2);
	$unixtimestamp = dol_mktime(12, 0, 0, $month, $day, $year);
	return $unixtimestamp;
}

/**
 * Show box
 *
 * @param	string	$selectedDate	Date YYYMMDD
 * @param	int		$month			Month
 * @param 	int		$year			Year
 * @return	void
 */
function displayBox($selectedDate, $month, $year)
{
	global $langs, $conf;

	//print "$selectedDate,$month,$year";
	$thedate = dol_mktime(12, 0, 0, $month, 1, $year);
	//print "thedate=$thedate";
	$today = dol_now();
	$todayArray = dol_getdate($today);
	if ($selectedDate != "00000000")
	{
		$selDate = xyzToUnixTimestamp($selectedDate);
		$xyz = dol_print_date($selDate, "%Y%m%d");
	} else {
		$selDate = 0;
		$xyz = 0;
	}
	?>
<table class="dp">
	<tr>
		<td colspan="6" class="dpHead"><?php
		$selectMonth = dol_print_date($thedate, '%m');
		$selectYear = dol_print_date($thedate, '%Y');
		echo $langs->trans("Month".$selectMonth).", ".$selectYear;
		?></td>
		<td class="dpHead">
		<button type="button" class="dpInvisibleButtons" id="DPCancel"
			onClick="closeDPBox();">X</button>
		</td>
	</tr>
	<tr>
		<td class="dpButtons"
			onClick="loadMonth('<?php echo DOL_URL_ROOT.'/core/' ?>','<?php echo $month?>','<?php echo $year - 1?>','<?php echo $xyz ?>','<?php echo $langs->defaultlang ?>')">&lt;&lt;</td>
		<td class="dpButtons"
			onClick="loadMonth('<?php echo DOL_URL_ROOT.'/core/' ?>','<?php if ($month == 1) echo "12"; else echo $month - 1?>','<?php if ($month == 1) echo $year - 1; else echo $year?>','<?php echo $xyz ?>','<?php echo $langs->defaultlang ?>')">&lt;</td>
		<td colspan="3" class="dpButtons"
			onClick="loadMonth('<?php echo DOL_URL_ROOT.'/core/' ?>','<?php echo (int) dol_print_date($today, '%m')?>','<?php echo $todayArray["year"]?>','<?php echo $xyz ?>','<?php echo $langs->defaultlang ?>')"><?php echo '-' ?></td>
		<td class="dpButtons"
			onClick="loadMonth('<?php echo DOL_URL_ROOT.'/core/' ?>','<?php if ($month == 12) echo "1"; else echo $month + 1?>','<?php if ($month == 12) echo $year + 1; else echo $year; ?>','<?php echo $xyz ?>','<?php echo $langs->defaultlang ?>')">&gt;</td>
		<td class="dpButtons"
			onClick="loadMonth('<?php echo DOL_URL_ROOT.'/core/' ?>','<?php echo $month?>','<?php echo $year + 1?>','<?php echo $xyz ?>','<?php echo $langs->defaultlang ?>')">&gt;&gt;</td>
	</tr>
	<tr class="dpDayNames">
	<?php
	$startday = isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1;
	$day_names = array('ShortSunday', 'ShortMonday', 'ShortTuesday', 'ShortWednesday', 'ShortThursday', 'ShortFriday', 'ShortSaturday');
	for ($i = 0; $i < 7; $i++)
	{
		echo '<td width="', (int) (($i + 1) * 100 / 7) - (int) ($i * 100 / 7), '%">', $langs->trans($day_names[($i + $startday) % 7]), '</td>', "\n";
	}
	print '</tr>';
	//print "x ".$thedate." y";			// $thedate = first day of month
	$firstdate = dol_getdate($thedate);
	//var_dump($firstdateofweek);
	$mydate = dol_get_first_day_week(1, $month, $year, true); // mydate = cursor date

	// Loop on each day of month
	$stoploop = 0; $day = 1; $cols = 0;
	while (!$stoploop)
	{
		//print_r($mydate);
		if ($mydate < $firstdate)	// At first run
		{
			echo "<tr class=\"dpWeek\">";
			//echo $conf->global->MAIN_START_WEEK.' '.$firstdate["wday"].' '.$startday;
			$cols = 0;
			for ($i = 0; $i < 7; $i++)
			{
				$w = ($i + $startday) % 7;
				if ($w == $firstdate["wday"])
				{
					$mydate = $firstdate;
					break;
				}
				echo "<td>&nbsp;</td>";
				$cols++;
			}
		} else {
			if ($mydate["wday"] == $startday)
			{
				echo "<tr class=\"dpWeek\">";
				$cols = 0;
			}
		}

		$dayclass = "dpReg";
		if ($thedate == $selDate) $dayclass = "dpSelected";
		elseif ($thedate == $today) $dayclass = "dpToday";

		if ($langs->trans("FormatDateShortJavaInput") == "FormatDateShortJavaInput")
		{
			print "ERROR FormatDateShortJavaInput not defined for language ".$langs->defaultlang;
			exit;
		}

		// Sur click dans calendrier, appelle fonction dpClickDay
		echo "<td class=\"".$dayclass."\"";
		echo " onMouseOver=\"dpHighlightDay(".$mydate["year"].",parseInt('".dol_print_date($thedate, "%m")."',10),".$mydate["mday"].",tradMonths)\"";
		echo " onClick=\"dpClickDay(".$mydate["year"].",parseInt('".dol_print_date($thedate, "%m")."',10),".$mydate["mday"].",'".$langs->trans("FormatDateShortJavaInput")."')\"";
		echo ">".sprintf("%02s", $mydate["mday"])."</td>";
		$cols++;

		if (($mydate["wday"] + 1) % 7 == $startday) echo "</TR>\n";

		//$thedate=strtotime("tomorrow",$thedate);
		$day++;
		$thedate = dol_mktime(12, 0, 0, $month, $day, $year);
		if ($thedate == '')
		{
			$stoploop = 1;
		} else {
			$mydate = dol_getdate($thedate);
			if ($firstdate["month"] != $mydate["month"]) $stoploop = 1;
		}
	}

	if ($cols < 7)
	{
		for ($i = 6; $i >= $cols; $i--) echo "<td>&nbsp;</td>";
		echo "</tr>\n";
	}
	?>
	<tr>
		<td id="dpExp" class="dpExplanation" colspan="7"><?php
		if ($selDate)
		{
			$tempDate = dol_getdate($selDate);
			print $langs->trans("Month".$selectMonth)." ";
			print sprintf("%02s", $tempDate["mday"]);
			print ", ".$selectYear;
		} else {
			print "Click a Date";
		}
		?></td>
	</tr>
</table>
		<?php
}//end function
