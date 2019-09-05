<?php
/* Copyright (c) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/adherents/stats/geo.php
 *      \ingroup    member
 *		\brief      Page with geographical statistics on members
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$graphwidth=DolGraph::getDefaultGraphSizeForStats('width', 700);
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode=GETPOST('mode')?GETPOST('mode'):'';


// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}
$result=restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;

// Load translation files required by the page
$langs->loadLangs(array("companies","members"));


/*
 * View
 */

$arrayjs=array('https://www.google.com/jsapi');
if (! empty($conf->dol_use_jmobile)) $arrayjs=array();

$title=$langs->trans("Statistics");
if ($mode == 'memberbycountry') $title=$langs->trans("MembersStatisticsByCountries");
if ($mode == 'memberbystate') $title=$langs->trans("MembersStatisticsByState");
if ($mode == 'memberbytown') $title=$langs->trans("MembersStatisticsByTown");
if ($mode == 'memberbyregion') $title=$langs->trans("MembersStatisticsByRegion");

llxHeader('', $title, '', '', 0, 0, $arrayjs);

print load_fiche_titre($title, $mesg);

dol_mkdir($dir);

if ($mode)
{
    // Define sql
    if ($mode == 'memberbycountry')
    {
        $label=$langs->trans("Country");
        $tab='statscountry';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label";
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as c on d.country = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
        $sql.=" WHERE d.entity IN (".getEntity('adherent').")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY c.label, c.code";
        //print $sql;
    }

	if ($mode == 'memberbystate')
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("State");
        $tab='statsstate';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, c.nom as label2"; //
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.state_id = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as co on d.country = co.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
        $sql.=" WHERE d.entity IN (".getEntity('adherent').")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY co.label, co.code, c.nom";
        //print $sql;
    }
    if ($mode == 'memberbyregion') //
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("Region"); //département
        $tab='statsregion'; //onglet

        $data = array(); //tableau de donnée
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, r.nom as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_departements as c on d.state_id = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r on c.fk_region = r.code_region";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as co on d.country = co.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
        $sql.=" WHERE d.entity IN (".getEntity('adherent').")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY co.label, co.code, r.nom"; //+
        //print $sql;
    }
    if ($mode == 'memberbytown')
    {
        $label=$langs->trans("Country");
        $label2=$langs->trans("Town");
        $tab='statstown';

        $data = array();
        $sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label, d.town as label2";
        $sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as c on d.country = c.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
        $sql.=" WHERE d.entity IN (".getEntity('adherent').")";
        $sql.=" AND d.statut = 1";
        $sql.=" GROUP BY c.label, c.code, d.town";
        //print $sql;
    }

    $langsen=new Translate('', $conf);
    $langsen->setDefaultLang('en_US');
    $langsen->load("dict");
    //print $langsen->trans("Country"."FI");exit;

    // Define $data array
    dol_syslog("Count member", LOG_DEBUG);
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
							'lastdate'=>$db->jdate($obj->lastdate),
                            'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate)
                );
            }
            if ($mode == 'memberbyregion') //+
            {
                $data[]=array(
                    'label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                    'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
				    'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
					'nb'=>$obj->nb,
					'lastdate'=>$db->jdate($obj->lastdate),
                    'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate)
                );
	        }
            if ($mode == 'memberbystate')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
				            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
							'nb'=>$obj->nb,
							'lastdate'=>$db->jdate($obj->lastdate),
                            'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate)
                );
            }
            if ($mode == 'memberbytown')
            {
                $data[]=array('label'=>(($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code)?$langs->trans("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label_en'=>(($obj->code && $langsen->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code)?$langsen->transnoentitiesnoconv("Country".$obj->code):($obj->label?$obj->label:$langs->trans("Unknown"))),
                            'label2'=>($obj->label2?$obj->label2:$langs->trans("Unknown")),
                            'nb'=>$obj->nb,
                            'lastdate'=>$db->jdate($obj->lastdate),
                            'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate)
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

dol_fiche_head($head, $tab, $langs->trans("Statistics"), -1, 'user');


// Print title
if ($mode && ! count($data))
{
    print $langs->trans("NoValidatedMemberYet").'<br>';
    print '<br>';
}
else
{
    if ($mode == 'memberbycountry') print $langs->trans("MembersByCountryDesc").'<br>';
    elseif ($mode == 'memberbystate') print $langs->trans("MembersByStateDesc").'<br>';
    elseif ($mode == 'memberbytown') print $langs->trans("MembersByTownDesc").'<br>';
    elseif ($mode == 'memberbyregion') print $langs->trans("MembersByRegion").'<br>';//+
    else
    {
        print $langs->trans("MembersStatisticsDesc").'<br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbycountry">'.$langs->trans("MembersStatisticsByCountries").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbystate">'.$langs->trans("MembersStatisticsByState").'</a><br>';
        print '<br>';
        print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbytown">'.$langs->trans("MembersStatisticsByTown").'</a><br>';
        print '<br>';//+
		print '<a href="'.$_SERVER["PHP_SELF"].'?mode=memberbyregion">'.$langs->trans("MembersStatisticsByRegion").'</a><br>';//+
    }
    print '<br>';
}


// Show graphics
if (count($arrayjs) && $mode == 'memberbycountry')
{
    $color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
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
    print "\toptions['colors'] = [0x".colorArrayToHex($theme_datacolor[1], 'BBBBBB').", 0x".colorArrayToHex($theme_datacolor[0], '444444')."];\n";
    print "\tvar container = document.getElementById('".$mode."');\n";
    print "\tvar geomap = new google.visualization.GeoMap(container);\n";
    print "\tgeomap.draw(data, options);\n";
    print "};\n";
    print "</script>\n";

    // print the div tag that will contain the map
    print '<div class="center" id="'.$mode.'"></div>'."\n";
    print '<br>';
}

if ($mode)
{
    // Print array / Affiche le tableau
    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$label.'</td>';
    if ($label2) print '<td class="center">'.$label2.'</td>';
    print '<td class="right">'.$langs->trans("NbOfMembers").'</td>';
    print '<td class="center">'.$langs->trans("LastMemberDate").'</td>';
    print '<td class="center">'.$langs->trans("LatestSubscriptionDate").'</td>';
    print '</tr>';

    foreach ($data as $val)
    {
        $year = $val['year'];
        print '<tr class="oddeven">';
        print '<td>'.$val['label'].'</td>';
        if ($label2) print '<td class="center">'.$val['label2'].'</td>';
        print '<td class="right">'.$val['nb'].'</td>';
        print '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour').'</td>';
        print '<td class="center">'.dol_print_date($val['lastsubscriptiondate'], 'dayhour').'</td>';
        print '</tr>';
    }

    print '</table>';
}


dol_fiche_end();

// End of page
llxFooter();
$db->close();
