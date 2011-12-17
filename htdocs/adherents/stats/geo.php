<?php
/* Copyright (c) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/adherents/stats/geo.php
 *      \ingroup    member
 *		\brief      Page with geographical statistics on members
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");

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
if ($mode == 'memberbytown') $title=$langs->trans("MembersStatisticsByTown");

print_fiche_titre($title, $mesg);

create_exdir($dir);

if ($mode)
{
    // Define sql
    if ($mode == 'memberbycountry')
    {
        $label=$langs->trans("Country");
        $tab='statscountry';

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
        $tab='statsstate';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.libelle as label, c.nom as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.fk_departement = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on d.pays = p.rowid";
        $sql.=" WHERE d.statut = 1";
        $sql.=" GROUP BY p.libelle, p.code, c.nom";
        //print $sql;
    }
    if ($mode == 'memberbytown')
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("Town");
        $tab='statstown';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, p.code, p.libelle as label, d.ville as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p on d.pays = p.rowid";
        $sql.=" WHERE d.statut = 1";
        $sql.=" GROUP BY p.libelle, p.code, d.ville";
        //print $sql;
    }

    $langsen=new Translate('',$conf);
    $langsen->setDefaultLang('en_US');
    $langsen->load("dict");
    //print $langsen->trans("Country"."FI");exit;

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
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
							'code'=>$obj->code,
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
                );
            }
            if ($mode == 'memberbystate')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
				            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate)
                );
            }
            if ($mode == 'memberbytown')
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


$head = member_stats_prepare_head($adh);

dol_fiche_head($head, $tab, $langs->trans("Statistics"), 0, 'user');


// Print title
if ($mode && ! count($data))
{
    print $langs->trans("NoValidatedMemberYet").'<br>';
    print '<br>';
}
else
{
    if ($mode == 'memberbycountry') print $langs->trans("MembersByCountryDesc").'<br>';
    else if ($mode == 'memberbystate') print $langs->trans("MembersByStateDesc").'<br>';
    else if ($mode == 'memberbytown') print $langs->trans("MembersByTownDesc").'<br>';
    else
    {
        print $langs->trans("MembersStatisticsDesc").'<br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbycountry">'.$langs->trans("MembersStatisticsByCountries").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbystate">'.$langs->trans("MembersStatisticsByState").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbytown">'.$langs->trans("MembersStatisticsByTown").'</a><br>';
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
    print "\tdata.addRows(".count($data).");\n";
    print "\tdata.addColumn('string', 'Country');\n";
    print "\tdata.addColumn('number', 'Number');\n";

    // loop and dump
    $i=0;
    foreach($data as $val)
    {
        //$valcountry=ucfirst($val['code']);
        $valcountry=ucfirst($val['label_en']);
        // fix case of uk
        if ($valcountry == 'Great Britain') { $valcountry = 'United Kingdom'; }
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


dol_fiche_end();


$db->close();

llxFooter();
?>
