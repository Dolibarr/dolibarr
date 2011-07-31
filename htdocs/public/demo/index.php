<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *     	\file       htdocs/public/demo/index.php
 *		\ingroup    core
 *		\brief      Entry page to access demo
 *		\author	    Laurent Destailleur
 *		\version    $Id: index.php,v 1.61 2011/07/31 23:23:21 eldy Exp $
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require("../../main.inc.php");

$langs->load("main");
$langs->load("install");
$langs->load("other");

// Security check
global $dolibarr_main_demo;
if (empty($dolibarr_main_demo)) accessforbidden('Parameter dolibarr_main_demo must be defined in conf file with value "default login,default pass" to enable the demo entry page',1,1,1);


$demoprofiles=array(
	array('default'=>'-1', 'key'=>'profdemofun','label'=>'DemoFundation',
	'disablemodules'=>'banque,barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,facture,ficheinter,fournisseur,prelevement,product,projet,propal,propale,service,societe,stock,tax',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
	array('default'=>'0', 'key'=>'profdemofun2','label'=>'DemoFundation2',
	'disablemodules'=>'barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,facture,ficheinter,fournisseur,prelevement,product,projet,propal,propale,service,societe,stock,tax',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
	array('default'=>'1', 'key'=>'profdemoservonly','label'=>'DemoCompanyServiceOnly',
	'disablemodules'=>'adherent,barcode,boutique,cashdesk,categorie,don,expedition,externalsite,prelevement,product,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot8.png'),
	array('default'=>'-1','key'=>'profdemoshopwithdesk','label'=>'DemoCompanyShopWithCashDesk',
	'disablemodules'=>'adherent,boutique,categorie,don,externalsite,ficheinter,prelevement,product,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoprodstock','label'=>'DemoCompanyProductAndStocks',
	'disablemodules'=>'adherent,boutique,contrat,categorie,don,externalsite,ficheinter,prelevement,service',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoall','label'=>'DemoCompanyAll',
	'disablemodules'=>'adherent,boutique,don,externalsite',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'),
	);

$alwayscheckedmodules=array('barcode','bookmark','externalrss','fckeditor','geoipmaxmind','gravatar','memcached','syslog','user','webservices');  // Technical module we always want
$alwaysuncheckedmodules=array('paybox','paypal','filemanager','google','scanner','workflow');  // Module we never want
$alwayshiddenmodules=array('accounting','barcode','bookmark','boutique','clicktodial','document','domain','externalrss','externalsite','fckeditor','ftp','geoipmaxmind','gravatar','label','ldap','mantis','memcached','notification',
                            'syslog','user','webservices',
                            // Extended modules
                            'awstats','bittorrent','cabinetmed','concatpdf','filemanager','monitoring','nltechno','numberwords','ovh','phenix','phpsysinfo','postnuke','submiteverywhere',
                            'survey','thomsonphonebook','voyage','webcalendar','webmail','zipautofillfr');

// Search modules
$dirlist=$conf->file->dol_document_root;

$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0; // is a sequencer of modules found
$j = 0; // j is module number. Automatically affected if module number not defined.
foreach ($dirlist as $dirroot)
{
    $dir = $dirroot . "/includes/modules/";

    // Charge tableaux modules, nom, numero, orders depuis repertoire dir
    $handle=opendir($dir);
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            //print "$i ".$file."\n<br>";
            if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
            {
                $modName = substr($file, 0, dol_strlen($file) - 10);

                if ($modName)
                {
                    //$modnameshort=strtolower(preg_replace('/^mod/','',$modName));
                    //if (! in_array($modnameshort,$conf->modules)) continue;

                    include_once($dir.$file);
                    $objMod = new $modName($db);

                    if ($objMod->numero > 0)
                    {
                        $j = $objMod->numero;
                    }
                    else
                    {
                        $j = 1000 + $i;
                    }

                    $modulequalified=1;

                    // We discard modules according to features level (PS: if module is activated we always show it)
                    $const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));
                    if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
                    if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;

                    if ($modulequalified)
                    {
                        $modules[$i] = $objMod;
                        $filename[$i]= $modName;
                        $orders[$i]  = $objMod->family."_".$j;   // Tri par famille puis numero module
                        //print "x".$modName." ".$orders[$i]."\n<br>";
                        $categ[$objMod->special]++;                 // Array of all different modules categories
                        $dirmod[$i] = $dirroot;
                        $j++;
                        $i++;
                    }
                }
            }
        }
    }
}

asort($orders);
//var_dump($orders);


/*
 * Actions
 */

if (GETPOST("action") == 'gotodemo')
{
	//print 'ee'.GETPOST("demochoice");
	$disablestring='';
	// If we disable modules using a profile choice
	if (GETPOST("demochoice"))
	{
    	foreach ($demoprofiles as $profilearray)
    	{
    		if ($profilearray['key'] == GETPOST("demochoice"))
    		{
    			$disablestring=$profilearray['disablemodules'];
    			break;
    		}
    	}
	}
	// If we disable modules using personalized list
	foreach($modules as $val)
	{
	    $modulekeyname=strtolower($val->name);
	    if (empty($_POST[$modulekeyname]) && empty($val->always_enabled) && ! in_array($modulekeyname,$alwayscheckedmodules))
	    {
	        $disablestring.=$modulekeyname.',';
	        if ($modulekeyname=='propale') $disablestring.='propal,';
	    }

	}

    // Do redirect to login page
	if ($disablestring)
	{
		$url=DOL_URL_ROOT.'/index.php?disablemodules='.$disablestring;
		if (GETPOST("urlfrom")) $url.='&urlfrom='.GETPOST("urlfrom");
		header("Location: ".$url);
		exit;
	}
}


/*
 * View
 */

llxHeaderVierge($langs->trans("DolibarrDemo"));

?>
<script type="text/javascript" language="javascript">
var openedId='';
jQuery(document).ready(function () {
    jQuery('tr.moduleline').hide();
    // Enable this to allow personalized setup
    jQuery('.modulelineshow').attr('href','#');
    jQuery(".modulelineshow").click(function() {
        var currentId = $(this).attr('id').substring(2);
        jQuery('tr.moduleline').hide();
        if (currentId != openedId)
        {
            openedId=currentId;
            jQuery("#tr1"+currentId).show();
            jQuery("#tr2"+currentId).show();
        }
        else openedId = '';
    });
});
</script>
<?php


print "\n";

print '<table style="font-size:14px;" summary="List of Dolibarr demos">';

print '<tr><td>';
print '<center><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.png" alt="Dolibarr logo"></center><br>';
print '<br>';

print $langs->trans("DemoDesc").'<br>';
print '<br>';
print '<font color="#555577"><b>'.$langs->trans("ChooseYourDemoProfil").'</b></font>';

print '</td></tr>';
print '<tr><td width="50%">';

print '<table style="font-size:14px;" width="100%" summary="List of Dolibarr demos">'."\n";
$i=0;
foreach ($demoprofiles as $profilarray)
{
	if ($profilarray['default'] >= 0)
	{
		$url=$_SERVER["PHP_SELF"].'?action=gotodemo&amp;urlfrom='.urlencode($_SERVER["PHP_SELF"]);
		$urlwithmod=$url.'&amp;demochoice='.$profilarray['key'];
		// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
		//print "xx".$_SERVER["PHP_SELF"].' '.DOL_URL_ROOT.'<br>';
		$urlfrom=preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','',$_SERVER["PHP_SELF"]);
		//print $urlfrom;

		//if ($i % $NBOFCOLS == 0) print '<tr>';
		print '<tr>';
		print '<td>'."\n";

		print '<form method="POST" name="form'.$profilarray['key'].'" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="action" value="gotodemo">'."\n";
        print '<input type="hidden" name="urlfrom" value="'.urlencode($urlfrom).'">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="username" value="demo">'."\n";
        print '<table summary="Dolibarr online demonstration for profile '.$profilarray['label'].'" style="font-size:14px;" width="100%" class="CTableRow'.($i%2==0?'1':'1').'">'."\n";
		print '<tr>';
		print '<td width="50"><a href="'.$urlwithmod.'" id="a1'.$profilarray['key'].'" class="modulelineshow"><img src="'.$profilarray['icon'].'" width="48" border="0" alt="Demo '.$profilarray['label'].'"></a></td>';
		//print '<td><input type="radio" name="demochoice"';
		//if ($profilarray['default']) print ' checked="true"';
		//print ' value="'.$profilarray['key'].'"></td>';
		print '<td><a href="'.$urlwithmod.'" id="a2'.$profilarray['key'].'" class="modulelineshow">'.$langs->trans($profilarray['label']).'</a></td></tr>'."\n";

		print '<tr id="tr1'.$profilarray['key'].'" class="moduleline">';
		print '<td colspan="2">';
		print $langs->trans("ThisIsListOfModules").'<br>';
		print '<table width="100%">';
		$listofdisabledmodules=explode(',',$profilarray['disablemodules']);
		$j=0;$nbcolsmod=4;
		foreach($modules as $val) // Loop on qualified (enabled) modules
		{
		    $modulekeyname=strtolower($val->name);

		    $modulequalified=1;
            if (! empty($val->always_enabled) || in_array($modulekeyname,$alwayshiddenmodules)) $modulequalified=0;
            if ($val->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
            if ($val->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;
            if (! $modulequalified) continue;

            $modulo=($j % $nbcolsmod);
		    if ($modulo == 0) print '<tr>';
            print '<td><input type="checkbox" class="checkbox" name="'.$modulekeyname.'" value="1"';
            if (in_array($modulekeyname,$alwaysuncheckedmodules)) print ' disabled="true"';
            if (! in_array($modulekeyname,$alwaysuncheckedmodules)  && (! in_array($modulekeyname,$listofdisabledmodules) || in_array($modulekeyname,$alwayscheckedmodules))) print ' checked="true"';
            print '>'.$val->getName().' &nbsp;';
            print '<!-- id='.$val->numero.' -->';
            print '</td>';
            if ($modulo == ($nbcolsmod - 1)) print '</tr>';
            $j++;
		}
		print '</table>';
		print '</td>';
		print '</tr>'."\n";

		print '<tr id="tr2'.$profilarray['key'].'" class="moduleline"><td colspan="'.$nbcolsmod.'" align="center"><input type="submit" value=" &nbsp; &nbsp; '.$langs->trans("Start").' &nbsp; &nbsp; " class="button"></td></tr>';
		print '</table></form>'."\n";

		print '</td>';
		//if ($i % $NBOFCOLS == ($NBOFCOLS-1)) print '</tr>'."\n";
		print '</tr>'."\n";
		$i++;
	}
}
print '</table>';

print '</td>';
print '</tr>';

// Description
print '<tr><td>';


print '</td></tr>';

// Button
/*
print '<tr><td align="center">';
print '<input type="hidden" name="action" value="gotodemo">';
print '<input class="button" type="submit" value=" < '.$langs->trans("GoToDemo").' > ">';
print '</td></tr>';
*/

print '</table>';

$db->close();

// Google Adsense (need Google module)
if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
{
	print '<div align="center">'."\n";
	print '<script type="text/javascript"><!--'."\n";
	print 'google_ad_client = "'.$conf->global->MAIN_GOOGLE_AD_CLIENT.'";'."\n";
	print 'google_ad_slot = "'.$conf->global->MAIN_GOOGLE_AD_SLOT.'";'."\n";
	print 'google_ad_width = '.$conf->global->MAIN_GOOGLE_AD_WIDTH.';'."\n";
	print 'google_ad_height = '.$conf->global->MAIN_GOOGLE_AD_HEIGHT.';'."\n";
	print '//-->'."\n";
	print '</script>'."\n";
	print '<script type="text/javascript"'."\n";
	print 'src="http://pagead2.googlesyndication.com/pagead/show_ads.js">'."\n";
	print '</script>'."\n";
	print '</div>'."\n";
}

// Google Analytics (need Google module)
if (! empty($conf->global->MAIN_GOOGLE_AN_ID))
{
    print "\n";
    print '<script type="text/javascript">'."\n";
    print '  var _gaq = _gaq || [];'."\n";
    print '  _gaq.push([\'_setAccount\', \''.$conf->global->MAIN_GOOGLE_AN_ID.'\']);'."\n";
    print '  _gaq.push([\'_trackPageview\']);'."\n";
    print ''."\n";
    print '  (function() {'."\n";
    print '    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;'."\n";
    print '    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';'."\n";
    print '    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);'."\n";
    print '  })();'."\n";
    print '</script>'."\n";
}


llxFooterVierge('$Date: 2011/07/31 23:23:21 $ - $Revision: 1.61 $');




function llxHeaderVierge($title, $head = "")
{
    global $user, $conf, $langs;

    header("Content-type: text/html; charset=".$conf->file->character_set_client);

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    //print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
    print "\n";
    print "<html>\n";
    print "<head>\n";
    print '<meta name="robots" content="index,nofollow">'."\n";
    print '<meta name="keywords" content="dolibarr,demo,online,demonstration,example,test,web,erp,crm,demos,online">'."\n";
    print '<meta name="description" content="Dolibarr simple ERP/CRM demo. You can test here several profiles of Dolibarr ERP/CRM demos.">'."\n";
    print "<title>".$title."</title>\n";
    print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/eldy/style.css.php?lang='.$langs->defaultlang.'">'."\n";
    print '<!-- Includes for JQuery -->'."\n";
    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-latest.min.js"></script>'."\n";
    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui-latest.custom.min.js"></script>'."\n";
    print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd_0_5.js"></script>'."\n";
    if ($head) print $head."\n";
    print '<style type="text/css">';
    print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
    print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
    print '</style>';
    print "</head>\n";
    print '<body style="margin: 20px;">'."\n";
}

function llxFooterVierge()
{
    print "\n";
    print "</body>\n";
    print "</html>\n";
}

?>
