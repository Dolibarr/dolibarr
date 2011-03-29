<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/adherents/stats/bycountry.php
 *      \ingroup    member
 *		\brief      Page des stats
 *		\version    $Id$
*/

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commandestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");

$graphwidth = 700;
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode=GETPOST('mode')?GETPOST('mode'):'';


// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
if (! $user->rights->adherent->cotisation->lire)
accessforbidden();

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;



/*
 * View
 */


llxHeader('','','','',0,0,array('http://www.google.com/jsapi'));

$title=$langs->trans("Statistics");
if ($mode == 'memberbycountry') $title=$langs->trans("MembersStatisticsByCountries");
if ($mode == 'memberbystate') $title=$langs->trans("MembersStatisticsByState");

print_fiche_titre($title, $mesg);

create_exdir($dir);

if ($mode)
{
	// Define sql
	if ($mode == 'memberbycountry')
	{
		$label=$langs->trans("Country");

		$data = array();
		$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, c.code, c.libelle as label";
		$sql.=" FROM ".MAIN_DB_PREFIX."adherent as d LEFT JOIN ".MAIN_DB_PREFIX."c_pays as c on d.pays = c.rowid";
		$sql.=" WHERE d.statut = 1";
		$sql.=" GROUP BY c.libelle, c.code";
		//print $sql;
	}
	if ($mode == 'memberbystate')
	{
        $label=$langs->trans("Country");
	    $label2=$langs->trans("State");

		$data = array();
		$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.libelle as label, c.nom as label2";
		$sql.=" FROM ".MAIN_DB_PREFIX."adherent as d LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.fk_departement = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on d.pays = p.rowid";
        $sql.=" WHERE d.statut = 1";
		$sql.=" GROUP BY p.libelle, p.code, c.nom";
		//print $sql;
	}

	// Define $data array
	dol_syslog("Count member sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num=$db->num_rows($resql);
		$i=0;
		while ($i < $num)
		{
			$obj=$db->fetch_object($resql);
			if ($mode == 'memberbycountry')
			{
				$data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'code'=>$obj->code,
							'nb'=>$obj->nb,
							'lastdate'=>$obj->lastdate
				);
			}
			if ($mode == 'memberbystate')
			{
				$data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
				            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
							'nb'=>$obj->nb,
							'lastdate'=>$obj->lastdate
				);
			}

			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

// Print title
if ($mode && ! sizeof($data))
{
	print $langs->trans("NoValidatedMemberYet").'<br>';
	print '<br>';
}
else
{
	if ($mode == 'memberbycountry') print $langs->trans("MembersByCountryDesc").'<br>';
	else if ($mode == 'memberbystate') print $langs->trans("MembersByStateDesc").'<br>';
	else
	{
		print $langs->trans("MembersStatisticsDesc").'<br>';
		print '<br>';
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbycountry">'.$langs->trans("MembersStatisticsByCountries").'</a><br>';
		print '<br>';
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbystate">'.$langs->trans("MembersStatisticsByState").'</a><br>';
	}
	print '<br>';
}


// Show graphics
if ($mode == 'memberbycountry')
{
	// Assume we've already included the proper headers so just call our script inline
	print "\n<script type='text/javascript'>\n";
	print "google.load('visualization', '1', {'packages': ['geomap']});\n";
	print "google.setOnLoadCallback(drawMap);\n";
	print "function drawMap() {\n\tvar data = new google.visualization.DataTable();\n";

	// Get the total number of rows
	print "\tdata.addRows(".sizeof($data).");\n";
	print "\tdata.addColumn('string', 'Country');\n";
	print "\tdata.addColumn('number', 'Number');\n";

	// loop and dump
	$i=0;
	foreach($data as $val)
	{
		// fix case of uk
	    if ($val['label'] == 'Great Britain') { $val['label'] = 'United Kingdom'; }
	    print "\tdata.setValue(".$i.", 0, \"".ucfirst($val['label'])."\");\n";
	    print "\tdata.setValue(".$i.", 1, ".$val['nb'].");\n";
	    // Google's Geomap only supports up to 400 entries
	    if ($i >= 400){ break; }
		$i++;
	}

	print "\tvar options = {};\n";
	print "\toptions['dataMode'] = 'regions';\n";
	print "\toptions['width'] = ".$graphwidth.";\n";
	print "\toptions['height'] = ".$graphheight.";\n";
	print "\tvar container = document.getElementById('".$mode."');\n";
	print "\tvar geomap = new google.visualization.GeoMap(container);\n";
	print "\tgeomap.draw(data, options);\n";
	print "};\n";
	print "</script>\n";

	// print the div tag that will contain the map
	print '<div align="center" id="'.$mode.'"></div>'."\n";
	print '<br>';
}

if ($mode)
{
	// Print array
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="center">'.$label.'</td>';
    if ($label2) print '<td align="center">'.$label2.'</td>';
	print '<td align="center">'.$langs->trans("NbOfMembers").'</td>';
	print '<td align="center">'.$langs->trans("LastMemberDate").'</td>';
	print '</tr>';

	$oldyear=0;
	$var=true;
	foreach ($data as $val)
	{
		$year = $val['year'];
		$var=!$var;
		print '<tr '.$bc[$var].'>';
	    print '<td align="center">'.$val['label'].'</td>';
        if ($label2) print '<td align="center">'.$val['label2'].'</td>';
	    print '<td align="right">'.$val['nb'].'</td>';
		print '<td align="right">'.dol_print_date($val['lastdate'],'dayhour').'</td>';
		print '</tr>';
		$oldyear=$year;
	}

	print '</table>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
