<?php
/* Copyright (c) 2004-2011  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014  Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/employees/stats/geo.php
 *      \ingroup    employee
 *		  \brief      Page with geographical statistics on employees
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$graphwidth=DolGraph::getDefaultGraphSizeForStats('width',700);
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode=GETPOST('mode')?GETPOST('mode'):'';


// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}
$result=restrictedArea($user,'employee','','','');

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;

$langs->load("employees");
$langs->load("companies");


/*
 * View
 */

$arrayjs=array('http://www.google.com/jsapi');
if (! empty($conf->dol_use_jmobile)) $arrayjs=array();
llxHeader('','','','',0,0,$arrayjs);

$title=$langs->trans("Statistics");
if ($mode == 'employeebycountry') $title=$langs->trans("EmployeesStatisticsByCountries");
if ($mode == 'employeebystate') $title=$langs->trans("EmployeesStatisticsByState");
if ($mode == 'employeebytown') $title=$langs->trans("EmployeesStatisticsByTown");

print_fiche_titre($title, $mesg);

dol_mkdir($dir);

if ($mode)
{
    // Define sql
    if ($mode == 'employeebycountry')
    {
        $label=$langs->trans("Country");
        $tab='statscountry';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, c.code, c.libelle as label";
        $sql.=" FROM ".MAIN_DB_PREFIX."employee as d LEFT JOIN ".MAIN_DB_PREFIX."c_pays as c on d.country = c.rowid";
        $sql.=" WHERE d.entity IN (".getEntity().")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY c.libelle, c.code";
        //print $sql;
    }
    if ($mode == 'employeebystate')
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("State");
        $tab='statsstate';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.libelle as label, c.nom as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."employee as d LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.state_id = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on d.country = p.rowid";
        $sql.=" WHERE d.entity IN (".getEntity().")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY p.libelle, p.code, c.nom";
        //print $sql;
    }
    if ($mode == 'employeebytown')
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("Town");
        $tab='statstown';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.libelle as label, d.town as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."employee as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on d.country = p.rowid";
        $sql.=" WHERE d.entity IN (".getEntity().")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY p.libelle, p.code, d.town";
        //print $sql;
    }

    $langsen=new Translate('',$conf);
    $langsen->setDefaultLang('en_US');
    $langsen->load("dict");
    //print $langsen->trans("Country"."FI");exit;

    // Define $data array
    dol_syslog("Count employee sql=".$sql);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num=$db->num_rows($resql);
        $i=0;
        while ($i < $num)
        {
            $obj=$db->fetch_object($resql);
            if ($mode == 'employeebycountry')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'code'=>$obj->code,
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
                );
            }
            if ($mode == 'employeebystate')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
				            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
                );
            }
            if ($mode == 'employeebytown')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
                            'nb'=>$obj->nb,
                            'lastdate'=>$db->jdate($obj->lastdate)
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


$head = employee_stats_prepare_head($emp);

dol_fiche_head($head, $tab, $langs->trans("Statistics"), 0, 'user');


// Print title
if ($mode && ! count($data))
{
    print $langs->trans("NoValidatedEmployeeYet").'<br>';
    print '<br>';
}
else
{
    if ($mode == 'employeebycountry') print $langs->trans("EmployeesByCountryDesc").'<br>';
    else if ($mode == 'employeebystate') print $langs->trans("EmployeesByStateDesc").'<br>';
    else if ($mode == 'employeebytown') print $langs->trans("EmployeesByTownDesc").'<br>';
    else
    {
        print $langs->trans("EmployeesStatisticsDesc").'<br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=employeebycountry">'.$langs->trans("EmployeesStatisticsByCountries").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=employeebystate">'.$langs->trans("EmployeesStatisticsByState").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=employeebytown">'.$langs->trans("EmployeesStatisticsByTown").'</a><br>';
    }
    print '<br>';
}


// Show graphics
if (count($arrayjs) && $mode == 'employeebycountry')
{
    $color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
    if (is_readable($color_file)) include_once $color_file;

    // Assume we've already included the proper headers so just call our script inline
    // More doc: https://developers.google.com/chart/interactive/docs/gallery/geomap?hl=fr-FR
    print "\n<script type='text/javascript'>\n";
    print "google.load('visualization', '1', {'packages': ['geomap']});\n";
    print "google.setOnLoadCallback(drawMap);\n";
    print "function drawMap() {\n\tvar data = new google.visualization.DataTable();\n";

    // Get the total number of rows
    print "\tdata.addRows(".count($data).");\n";
    print "\tdata.addColumn('string', 'Country');\n";
    print "\tdata.addColumn('number', 'Number');\n";

    // loop and dump
    $i=0;
    foreach($data as $val)
    {
        $valcountry=strtoupper($val['code']);    // Should be ISO-3166 code (faster)
        //$valcountry=ucfirst($val['label_en']);
        if ($valcountry == 'Great Britain') { $valcountry = 'United Kingdom'; }    // fix case of uk (when we use labels)
        print "\tdata.setValue(".$i.", 0, \"".$valcountry."\");\n";
        print "\tdata.setValue(".$i.", 1, ".$val['nb'].");\n";
        // Google's Geomap only supports up to 400 entries
        if ($i >= 400){ break; }
        $i++;
    }

    print "\tvar options = {};\n";
    print "\toptions['dataMode'] = 'regions';\n";
    print "\toptions['showZoomOut'] = false;\n";
    //print "\toptions['zoomOutLabel'] = '".dol_escape_js($langs->transnoentitiesnoconv("Numbers"))."';\n";
    print "\toptions['width'] = ".$graphwidth.";\n";
    print "\toptions['height'] = ".$graphheight.";\n";
    print "\toptions['colors'] = [0x".colorArrayToHex($theme_datacolor[1],'BBBBBB').", 0x".colorArrayToHex($theme_datacolor[0],'444444')."];\n";
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
    print '<td align="center">'.$langs->trans("NbOfEmployees").'</td>';
    print '<td align="center">'.$langs->trans("LastEmployeeDate").'</td>';
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


dol_fiche_end();



llxFooter();

$db->close();
?>
