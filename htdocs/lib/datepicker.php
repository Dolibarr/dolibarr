<?php
/* Copyright (C) phpBSM
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * This file is a modified version of datepicker.php from phpBSM
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
        \file       htdocs/lib/datepicker.php
        \brief      Fichier de gestion de la popup de selection de date eldy
        \version    $Revision$
*/

require_once("../conf/conf.php");
require_once("../master.inc.php");
$langs->trans("main");

if(!isset($_GET["cm"])) $_GET["cm"]="shw";
if(!isset($_GET["sd"])) $_GET["sd"]="00000000";

switch($_GET["cm"])
{
	case "shw":
		displayBox($_GET["sd"],$_GET["m"],$_GET["y"]);
	break;
}



function xyzToUnixTimestamp($mysqldate){
	$year=substr($mysqldate,0,4);
	$month=substr($mysqldate,4,2);
	$day=substr($mysqldate,6,2);
	$unixtimestamp=mktime(0,0,0,$month,$day,$year);
	return $unixtimestamp;
}

function displayBox($selectedDate,$month,$year){
	global $dolibarr_main_url_root,$langs;
	$langs->load("main");
	
	//print "$selectedDate,$month,$year";
	$thedate=mktime(0,0,0,$month,1,$year);
	$today=mktime(0,0,0);
	$todayArray=getdate($today);
	if($selectedDate != "00000000")
	{ 
		$selDate=xyzToUnixTimestamp($selectedDate);
		$xyz=date("Ymd",$selDate);
	}
	else
	{
		$selDate=0;
		$xyz=0;
	}
?>
<table class="dp" cellspacing="0" cellpadding="0" border=0>
	<tr>
		<td colspan=6 class="dpHead"><?php echo date("F, Y", $thedate) ?></td>
		<td class="dpHead"><button type="buttton" class="dpInvisibleButtons" id="DPCancel" onClick="closeDPBox();">X</button></td>
	</tr>
	<tr>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo $month?>','<?php echo $year-1?>','<?php echo $xyz ?>')">&lt;&lt;</td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php if($month==1) echo "12"; else echo $month-1?>','<?php if($month==1) echo $year-1; else echo $year?>','<?php echo $xyz ?>')">&lt;</td>
		<td colspan=3 class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo date('m',$today)?>','<?php echo $todayArray["year"]?>','<?php echo $xyz ?>')"><?php echo $langs->trans("Today") ?></td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php if($month==12) echo "1"; else echo $month+1?>','<?php if($month==12) echo $year+1; else echo $year;?>','<?php echo $xyz ?>')">&gt;</td>
		<td class="dpButtons" onClick="loadMonth('<?php echo $dolibarr_main_url_root.'/lib/' ?>','<?php echo $month?>','<?php echo $year+1?>','<?php echo $xyz ?>')">&gt;&gt;</td>
	</tr>
	<tr  class="dpDayNames">
		<td width="14.286%">S</td>
		<td width="14.286%">M</td>
		<td width="14.286%">T</td>
		<td width="14.286%">W</td>
		<td width="14.286%">R</td>
		<td width="14.286%">F</td>
		<td width="14.286%">S</td>
	</tr>
	<?php 
		$firstdate=getdate($thedate);
		$mydate=$firstdate;
		while($firstdate["month"]==$mydate["month"])
		{
			if($mydate["wday"]==0) echo "<TR class=\"dpWeek\">";
			if($firstdate==$mydate){
				// firstdate, so we may have to put in blanks
				echo "<TR class=\"dpWeek\">";
				for($i=0;$i<$mydate["wday"];$i++)
					echo "<TD>&nbsp;</TD>";
			}
			
			$dayclass="dpReg";
			if($thedate==$selDate) $dayclass="dpSelected";
			elseif($thedate==$today) $dayclass="dpToday";
			
			// Sur click dans calendrier, appelle fonction dpClickDay
			echo "<TD class=\"".$dayclass."\" onMouseOver=\"dpHighlightDay(".$mydate["year"].",".date("n",$thedate).",".$mydate["mday"].")\" onClick=\"dpClickDay(".$mydate["year"].",".date("n",$thedate).",".$mydate["mday"].")\">".sprintf("%02s",$mydate["mday"])."</TD>";
			
			if($mydate["wday"]==6) echo "</tr>";
			$thedate=strtotime("tomorrow",$thedate);
			$mydate=getdate($thedate);
		}
		
		if($mydate["wday"]!=0){
			for($i=6;$i>=$mydate["wday"];$i--)
				echo "<TD>&nbsp;</TD>";
			echo "</TR";
		}
	?>
	<tr><td id="dpExp" class="dpExplanation" colspan="7"><?php 
		if($selDate)
		{
			$tempDate=getdate($selDate);
			print $tempDate["month"]." ";
			print sprintf("%02s",$tempDate["mday"]);
			print ", ".$tempDate["year"];
		}
		else
		    print "Click a Date";
	?></td></tr>	
</table>
<?php
}//end function
?>