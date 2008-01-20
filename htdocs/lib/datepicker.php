<?php
/* Copyright (C) phpBSM
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
        \file       htdocs/lib/datepicker.php
        \brief      Fichier de gestion de la popup de selection de date eldy
        \version    $Id$
*/

if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled cause need to load personalized language
if (! defined('NOREQUIRESOC'))  define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");


// URL http://mydolibarr/lib/datepicker.php?mode=test&m=10&y=2038 can be used for tests
if (isset($_GET["mode"]) && $_GET["mode"] == 'test')
{
	print '<html><head>';
	print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_head.js"></script>'."\n";
	print '</head><body>'."\n";
}
else
{
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n";
	print '<html><head><title>Calendar</title></head><body>'."\n";
}


$langs->load("main");

$qualified=true;

if (! isset($_GET["sd"])) $_GET["sd"]="00000000";

if (! isset($_GET["m"])) $qualified=false;
if (! isset($_GET["y"])) $qualified=false;
if (isset($_GET["m"]) && isset($_GET["y"]))
{
	if ($_GET["m"] < 1)    $qualified=false;
	if ($_GET["m"] > 12)   $qualified=false;
	if ($_GET["y"] < 0)    $qualified=false;
	if ($_GET["y"] > 9999) $qualified=false;
}

// If parameters provided, we show calendar
if ($qualified)
{
	//print $_GET["cm"].",".$_GET["sd"].",".$_GET["m"].",".$_GET["y"];exit;
	displayBox($_GET["sd"],$_GET["m"],$_GET["y"]);
}
else
{
	dolibarr_print_error('','ErrorBadParameters');
}


print '</body></html>'."\n";


function xyzToUnixTimestamp($mysqldate){
	$year=substr($mysqldate,0,4);
	$month=substr($mysqldate,4,2);
	$day=substr($mysqldate,6,2);
	$unixtimestamp=dolibarr_mktime(12,0,0,$month,$day,$year);
	return $unixtimestamp;
}

function displayBox($selectedDate,$month,$year){
	global $dolibarr_main_url_root,$langs,$conf;
	
	//print "$selectedDate,$month,$year";
	$thedate=dolibarr_mktime(12,0,0,$month,1,$year);
	//print "thedate=$thedate";
	$today=mktime();
	$todayArray=dolibarr_getdate($today);
	if($selectedDate != "00000000")
	{ 
		$selDate=xyzToUnixTimestamp($selectedDate);
		$xyz=dolibarr_date("Ymd",$selDate);
	}
	else
	{
		$selDate=0;
		$xyz=0;
	}
?>
<table class="dp" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td colspan="6" class="dpHead">
		<?php
		$selectMonth = dolibarr_date("F", $thedate);
		$selectYear = dolibarr_date("Y", $thedate);
		echo $langs->trans($selectMonth).", ".$selectYear;
		?>
		</td>
		<td class="dpHead"><button type="button" class="dpInvisibleButtons" id="DPCancel" onClick="closeDPBox();">X</button></td>
	</tr>
	<tr>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo $month?>','<?php echo $year-1?>','<?php echo $xyz ?>')">&lt;&lt;</td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php if($month==1) echo "12"; else echo $month-1?>','<?php if($month==1) echo $year-1; else echo $year?>','<?php echo $xyz ?>')">&lt;</td>
		<td colspan="3" class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo dolibarr_date('m',$today)?>','<?php echo $todayArray["year"]?>','<?php echo $xyz ?>')"><?php echo $langs->trans("MonthOfDay") ?></td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php if($month==12) echo "1"; else echo $month+1?>','<?php if($month==12) echo $year+1; else echo $year;?>','<?php echo $xyz ?>')">&gt;</td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo $month?>','<?php echo $year+1?>','<?php echo $xyz ?>')">&gt;&gt;</td>
	</tr>
	<tr  class="dpDayNames">
		<td width="14%"><?php echo $langs->trans("ShortSunday") ?></td>
		<td width="14%"><?php echo $langs->trans("ShortMonday") ?></td>
		<td width="15%"><?php echo $langs->trans("ShortTuesday") ?></td>
		<td width="14%"><?php echo $langs->trans("ShortWednesday") ?></td>
		<td width="15%"><?php echo $langs->trans("ShortThursday") ?></td>
		<td width="14%"><?php echo $langs->trans("ShortFriday") ?></td>
		<td width="14%"><?php echo $langs->trans("ShortSaturday") ?></td>
	</tr>
<?php 
		//print "x ".$thedate." y";
		$firstdate=dolibarr_getdate($thedate);
		$mydate=$firstdate;

		// Loop on each day of month
		$stoploop=0; $day=1; $cols=0;
		while (! $stoploop)
		{
			//print_r($mydate);
			if($firstdate==$mydate)	// At first run
			{
				echo "<TR class=\"dpWeek\">";
				$cols=0;
				for($i=0;$i< $mydate["wday"];$i++) 
				{
					echo "<TD>&nbsp;</TD>";
					$cols++;
				}
			}
			else
			{
				if ($mydate["wday"]==0)
				{
					echo "<TR class=\"dpWeek\">";
					$cols=0;
				}
			}
			
			$dayclass="dpReg";
			if($thedate==$selDate) $dayclass="dpSelected";
			elseif($thedate==$today) $dayclass="dpToday";
			
			// Sur click dans calendrier, appelle fonction dpClickDay
			echo "<TD class=\"".$dayclass."\"";
			echo " onMouseOver=\"dpHighlightDay(".$mydate["year"].",".dolibarr_date("n",$thedate).",".$mydate["mday"].",tradMonths)\"";
			echo " onClick=\"dpClickDay(".$mydate["year"].",".dolibarr_date("n",$thedate).",".$mydate["mday"].",'".$conf->format_date_short_java."')\"";
			echo ">".sprintf("%02s",$mydate["mday"])."</TD>";
			$cols++;
			
			if ($mydate["wday"]==6) echo "</TR>\n";
			
			//$thedate=strtotime("tomorrow",$thedate);
			$day++;
			$thedate=dolibarr_mktime(12,0,0,$month,$day,$year);
			if ($thedate == '')
			{
				$stoploop=1;
			}
			else
			{
				$mydate=dolibarr_getdate($thedate);
				if ($firstdate["month"] != $mydate["month"]) $stoploop=1;
			}
		}
		
		if ($cols < 7)
		{
			for($i=6; $i>=$cols; $i--) echo "<TD>&nbsp;</TD>";
			echo "</TR>\n";
		}
	?>
	<tr><td id="dpExp" class="dpExplanation" colspan="7"><?php 
		if($selDate)
		{
			$tempDate=dolibarr_getdate($selDate);
			print $langs->trans($selectMonth)." ";
			print sprintf("%02s",$tempDate["mday"]);
			print ", ".$selectYear;
		}
		else
		{
		    print "Click a Date";
		}
	?></td></tr>	
</table>
<?php
}//end function
?>