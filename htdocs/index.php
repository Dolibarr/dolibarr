<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */

define('NOCSRFCHECK',1);	// This is login page. We must be able to go on it from another web site.

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu']=GETPOST('mainmenu', 'alpha')?GETPOST('mainmenu', 'alpha'):'home';
$action=GETPOST('action');

$hookmanager->initHooks(array('index'));



/*
 * Actions
 */

// Check if company name is defined (first install)
if (!isset($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_NOM))
{
    header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
    exit;
}

if (GETPOST('addbox'))	// Add box (when submit is done from a form when ajax disabled)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone=GETPOST('areacode');
	$userid=GETPOST('userid');
	$boxorder=GETPOST('boxorder');
	$boxorder.=GETPOST('boxcombo');

	$result=InfoBox::saveboxorder($db,$zone,$boxorder,$userid);
}




/*
 * View
 */

if (! is_object($form)) $form=new Form($db);

// Title
$title=$langs->trans("HomeArea").' - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$langs->trans("HomeArea").' - '.$conf->global->MAIN_APPLICATION_TITLE;

llxHeader('',$title);


$resultboxes=FormOther::getBoxesArea($user,"0");


print load_fiche_titre($langs->trans("HomeArea"),$resultboxes['selectboxlist'],'title_home');

if (! empty($conf->global->MAIN_MOTD))
{
    $conf->global->MAIN_MOTD=preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i','<br>',$conf->global->MAIN_MOTD);
    if (! empty($conf->global->MAIN_MOTD))
    {
    	$i=0;
    	while (preg_match('/__\(([a-zA-Z|@]+)\)__/i',$conf->global->MAIN_MOTD,$reg) && $i < 100)
    	{
    		$tmp=explode('|',$reg[1]);
    		if (! empty($tmp[1])) $langs->load($tmp[1]);
    		$conf->global->MAIN_MOTD=preg_replace('/__\('.preg_quote($reg[1]).'\)__/i',$langs->trans($tmp[0]),$conf->global->MAIN_MOTD);
    		$i++;
    	}

        print "\n<!-- Start of welcome text -->\n";
        print '<table width="100%" class="notopnoleftnoright"><tr><td>';
        print dol_htmlentitiesbr($conf->global->MAIN_MOTD);
        print '</td></tr></table><br>';
        print "\n<!-- End of welcome text -->\n";
    }
}


//print '<div class="fichecenter"><div class="fichethirdleft">';



//print '</div></div></div><div class="clearboth"></div>';

print '<div class="fichecenter fichecenterbis">';


/*
 * Show boxes
 */

$boxlist.='<table width="100%" class="notopnoleftnoright">';
$boxlist.='<tr><td class="notopnoleftnoright">'."\n";

$boxlist.='<div class="fichehalfleft">';

$boxlist.=$resultboxes['boxlista'];

$boxlist.= '</div><div class="fichehalfright"><div class="ficheaddleft">';

$boxlist.=$resultboxes['boxlistb'];

$boxlist.= '</div></div>';
$boxlist.= "\n";

$boxlist.= "</td></tr>";
$boxlist.= "</table>";

print $boxlist;

print '</div>';


/*
 * Show security warnings
 */

// Security warning repertoire install existe (si utilisateur admin)
if ($user->admin && empty($conf->global->MAIN_REMOVE_INSTALL_WARNING))
{
    $message='';

    // Check if install lock file is present
    $lockfile=DOL_DATA_ROOT.'/install.lock';
    if (! empty($lockfile) && ! file_exists($lockfile) && is_dir(DOL_DOCUMENT_ROOT."/install"))
    {
        $langs->load("errors");
        //if (! empty($message)) $message.='<br>';
        $message.=info_admin($langs->trans("WarningLockFileDoesNotExists",DOL_DATA_ROOT).' '.$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }

    // Conf files must be in read only mode
    if (is_writable($conffile))
    {
        $langs->load("errors");
        //$langs->load("other");
        //if (! empty($message)) $message.='<br>';
        $message.=info_admin($langs->transnoentities("WarningConfFileMustBeReadOnly").' '.$langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }

    if ($message)
    {
        print $message;
        //$message.='<br>';
        //print info_admin($langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
    }
}

//print 'mem='.memory_get_usage().' - '.memory_get_peak_usage();

llxFooter();

$db->close();


/**
 *  Show weather logo. Logo to show depends on $totallate and values for
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @param      string  $text           Text to show on logo
 *  @param      string  $options        More parameters on img tag
 *  @return     string                  Return img tag of weather
 */
function showWeather($totallate,$text,$options)
{
    global $conf;

    $out='';
    $offset=0;
    $factor=10; // By default
    
    $level0=$offset;           if (! empty($conf->global->MAIN_METEO_LEVEL0)) $level0=$conf->global->MAIN_METEO_LEVEL0;
    $level1=$offset+1*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL1)) $level1=$conf->global->MAIN_METEO_LEVEL1;
    $level2=$offset+2*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL2)) $level2=$conf->global->MAIN_METEO_LEVEL2;
    $level3=$offset+3*$factor; if (! empty($conf->global->MAIN_METEO_LEVEL3)) $level3=$conf->global->MAIN_METEO_LEVEL3;

    if ($totallate <= $level0) $out.=img_weather($text,'weather-clear.png',$options);
    if ($totallate > $level0 && $totallate <= $level1) $out.=img_weather($text,'weather-few-clouds.png',$options);
    if ($totallate > $level1 && $totallate <= $level2) $out.=img_weather($text,'weather-clouds.png',$options);
    if ($totallate > $level2 && $totallate <= $level3) $out.=img_weather($text,'weather-many-clouds.png',$options);
    if ($totallate > $level3) $out.=img_weather($text,'weather-storm.png',$options);
    return $out;
}
