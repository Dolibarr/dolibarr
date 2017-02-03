<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2010       Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *     	\file       htdocs/public/demo/index.php
 *		\ingroup    core
 *		\brief      Entry page to access demo
 *		\author	    Laurent Destailleur
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';
require_once '../../core/lib/functions2.lib.php';

$langs->load("main");
$langs->load("install");
$langs->load("other");

$conf->dol_hide_topmenu=GETPOST('dol_hide_topmenu','int');
$conf->dol_hide_leftmenu=GETPOST('dol_hide_leftmenu','int');
$conf->dol_optimize_smallscreen=GETPOST('dol_optimize_smallscreen','int');
$conf->dol_no_mouse_hover=GETPOST('dol_no_mouse_hover','int');
$conf->dol_use_jmobile=GETPOST('dol_use_jmobile','int');

// Security check
global $dolibarr_main_demo;
if (empty($dolibarr_main_demo)) accessforbidden('Parameter dolibarr_main_demo must be defined in conf file with value "default login,default pass" to enable the demo entry page',0,0,1);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$res=$hookmanager->initHooks(array('demo'));

$demoprofiles=array();
$alwayscheckedmodules=array();
$alwaysuncheckedmodules=array();
$alwayshiddencheckedmodules=array();
$alwayshiddenuncheckedmodules=array();

$tmpaction = 'view';
$parameters=array();
$object=new stdClass();
$reshook=$hookmanager->executeHooks('addDemoProfile', $parameters, $object, $tmpaction);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=$hookmanager->errors;
if (empty($reshook))
{
	$demoprofiles=array(
		array('default'=>'1', 'key'=>'profdemoservonly','label'=>'DemoCompanyServiceOnly',
		'disablemodules'=>'adherent,barcode,cashdesk,don,expedition,externalsite,incoterm,mailmanspip,margin,prelevement,product,productbatch,stock',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot8.png'),
		array('default'=>'-1','key'=>'profdemoshopwithdesk','label'=>'DemoCompanyShopWithCashDesk',
		'disablemodules'=>'adherent,don,externalsite,ficheinter,incoterm,mailmanspip,prelevement,product,productbatch,stock',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
		array('default'=>'0', 'key'=>'profdemoprodstock','label'=>'DemoCompanyProductAndStocks',
		'disablemodules'=>'adherent,contrat,don,externalsite,ficheinter,mailmanspip,prelevement,service',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
		array('default'=>'0', 'key'=>'profdemoall','label'=>'DemoCompanyAll',
		'disablemodules'=>'adherent,don,externalsite,mailmanspip',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'),
		array('default'=>'-1', 'key'=>'profdemofun','label'=>'DemoFundation',
		'disablemodules'=>'banque,barcode,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,ficheinter,incoterm,mailmanspip,margin,prelevement,product,productbatch,projet,propal,propale,service,societe,stock,tax',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
		array('default'=>'0', 'key'=>'profdemofun2','label'=>'DemoFundation2',
		'disablemodules'=>'barcode,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,ficheinter,incoterm,mailmanspip,margin,prelevement,product,productbatch,projet,propal,propale,service,societe,stock,tax',
		'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png')
	);

	// Visible
	$alwayscheckedmodules=array('barcode','bookmark','categorie','externalrss','fckeditor','geoipmaxmind','gravatar','memcached','syslog','user','webservices');  // Technical module we always want
	$alwaysuncheckedmodules=array('dynamicprices','loan','multicurrency','paybox','paypal','google','printing','scanner','workflow');  // Module we never want
	// Not visible
	$alwayshiddencheckedmodules=array('accounting','api','barcode','bookmark','clicktodial','comptabilite','cron','document','domain','externalrss','externalsite','fckeditor','geoipmaxmind','gravatar','label','ldap',
									'mailmanspip','notification','oauth','syslog','user','webservices',
	                                // Extended modules
	                                'memcached','numberwords','zipautofillfr');
	$alwayshiddenuncheckedmodules=array('ftp','hrm','webservicesclient','websites',
	                                // Extended modules
	                                'awstats','bittorrent','bootstrap','cabinetmed','cmcic','concatpdf','customfield','deplacement','dolicloud','filemanager','lightbox','mantis','monitoring','moretemplates','multicompany','nltechno','numberingpack','openstreetmap',
	                                'ovh','phenix','phpsysinfo','pibarcode','postnuke','selectbank','skincoloreditor','submiteverywhere','survey','thomsonphonebook','topten','tvacerfa','voyage','webcalendar','webmail');
}

// Search modules
$dirlist=$conf->file->dol_document_root;


// Search modules dirs
$modulesdir = dolGetModulesDirs();


$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0; // is a sequencer of modules found
$j = 0; // j is module number. Automatically affected if module number not defined.

foreach ($modulesdir as $dir)
{
    // Charge tableaux modules, nom, numero, orders depuis repertoire dir
    $handle=@opendir($dir);
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
		            try
		            {
                        include_once $dir.$file;
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
                        if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && empty($conf->global->$const_name)) $modulequalified=0;
                        if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && empty($conf->global->$const_name)) $modulequalified=0;

                        if ($modulequalified)
                        {
                            $modules[$i] = $objMod;
                            $filename[$i]= $modName;
                            $orders[$i]  = $objMod->family."_".$j;   // Tri par famille puis numero module
                            //print "x".$modName." ".$orders[$i]."\n<br>";
       						if (isset($categ[$objMod->special])) $categ[$objMod->special]++;					// Array of all different modules categories
       			            else $categ[$objMod->special]=1;
                            $dirmod[$i] = $dirroot;
                            $j++;
                            $i++;
                        }
		            }
                    catch(Exception $e)
                    {
                        dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
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
		$url='';
		$url.=($url?'&':'').($conf->dol_hide_topmenu?'dol_hide_topmenu='.$conf->dol_hide_topmenu:'');
		$url.=($url?'&':'').($conf->dol_hide_leftmenu?'dol_hide_leftmenu='.$conf->dol_hide_leftmenu:'');
		$url.=($url?'&':'').($conf->dol_optimize_smallscreen?'dol_optimize_smallscreen='.$conf->dol_optimize_smallscreen:'');
		$url.=($url?'&':'').($conf->dol_no_mouse_hover?'dol_no_mouse_hover='.$conf->dol_no_mouse_hover:'');
		$url.=($url?'&':'').($conf->dol_use_jmobile?'dol_use_jmobile='.$conf->dol_use_jmobile:'');
		if (GETPOST('urlfrom')) $url.=($url?'&':'').'urlfrom='.urlencode(GETPOST('urlfrom','alpha'));
		$url=DOL_URL_ROOT.'/index.php?'.($url?$url.'&':'').'disablemodules='.$disablestring;
		header("Location: ".$url);
		exit;
	}
}


/*
 * View
 */

$head='';
$head.='<meta name="keywords" content="demo,online,demonstration,example,test,erp,crm,demos,web">'."\n";
$head.='<meta name="description" content="Dolibarr ERP and CRM demo. You can test here several profiles for Dolibarr ERP and CRM demonstration.">'."\n";
$head.='<style type="text/css">'."\n";
$head.='.body { font: 12px arial,verdana,helvetica !important; }'."\n";
$head.='.CTable {
padding: 6px;
font: 12px arial,verdana,helvetica;
font-weight: normal;
color: #444444 !important;

margin: 8px 0px 8px 2px;

border: 1px solid #bbb;
border-radius: 8px;
-moz-border-radius: 8px;

background: -webkit-linear-gradient(bottom, rgb(255,255,255) 85%, rgb(255,255,255) 100%);

}
.csscolumns {
    margin-top: 6px;
    -webkit-column-count: 4; /* Chrome, Safari, Opera */
    -moz-column-count: 4; /* Firefox */
    column-count: 4;
}
@media only screen and (max-width: 840px)
{
	.csscolumns {
		-webkit-column-count: 3; /* Chrome, Safari, Opera */
	    -moz-column-count: 3; /* Firefox */
	    column-count: 3;
	}
}
@media only screen and (max-width: 640px)
{
	.csscolumns {
		-webkit-column-count: 2; /* Chrome, Safari, Opera */
	    -moz-column-count: 2; /* Firefox */
	    column-count: 2;
	}
}
@media only screen and (max-width: 420px)
{
	.csscolumns {
		-webkit-column-count: 1; /* Chrome, Safari, Opera */
	    -moz-column-count: 1; /* Firefox */
	    column-count: 1;
	}
}
</style>

<script type="text/javascript">
var openedId="";
jQuery(document).ready(function () {
jQuery("tr.moduleline").hide();
// Enable this to allow personalized setup
jQuery(".modulelineshow").attr("href","#");
jQuery(".cursorpointer").css("cursor","pointer");
jQuery(".modulelineshow").click(function() {
var idstring=$(this).attr("id");
if (typeof idstring != "undefined")
{
var currentId = idstring.substring(2);
jQuery("tr.moduleline").hide();
if (currentId != openedId)
{
openedId=currentId;
jQuery("#tr1"+currentId).show();
jQuery("#tr2"+currentId).show();
}
else openedId = "";
}
});
});
</script>';

llxHeaderVierge($langs->trans("DolibarrDemo"), $head);


print "\n";

print '<table style="font-size:14px;" class="centpercent" summary="Main table for Dolibarr demos">';

print '<tr><td>';
print '<div class="center"><a alt="Official portal of your ERP CRM application" targe="_blank" href="https://www.dolibarr.org"><img class="demologo" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.png" alt="Dolibarr logo"></a></div><br>';
print '<br>';

print '<div style="text-align: justify;">'.$langs->trans("DemoDesc").'</div><br>';
print '<br>';
print '<font color="#555577"><b>'.$langs->trans("ChooseYourDemoProfil").'</b></font>';

print '</td></tr>';
print '<tr><td>';

//print '<table width="100%" summary="List of Dolibarr demos" class="notopnoleft">'."\n";
$i=0;
foreach ($demoprofiles as $profilearray)
{
	if ($profilearray['default'] >= 0)
	{
	    //print $profilearray['lang'];
	    if (! empty($profilearray['lang'])) $langs->load($profilearray['lang']);

		$url=$_SERVER["PHP_SELF"].'?action=gotodemo&amp;urlfrom='.urlencode($_SERVER["PHP_SELF"]);
		$urlwithmod=$url.'&amp;demochoice='.$profilearray['key'];
		// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
		//print "xx".$_SERVER["PHP_SELF"].' '.DOL_URL_ROOT.'<br>';
		$urlfrom=preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','',$_SERVER["PHP_SELF"]);
		//print $urlfrom;
		if (! empty($profilearray['url'])) $urlwithmod=$profilearray['url'];

		//if ($i % $NBOFCOLS == 0) print '<tr>';
		//print '<tr>';
		//print '<td>'."\n";

		print '<form method="POST" name="form'.$profilearray['key'].'" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="action" value="gotodemo">'."\n";
        print '<input type="hidden" name="urlfrom" value="'.dol_escape_htmltag($urlfrom).'">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="username" value="demo">'."\n";
        print '<input type="hidden" name="dol_hide_topmenu" value="'.$conf->dol_hide_topmenu.'">'."\n";
        print '<input type="hidden" name="dol_hide_leftmenu" value="'.$conf->dol_hide_leftmenu.'">'."\n";
        print '<input type="hidden" name="dol_optimize_smallscreen" value="'.$conf->dol_optimize_smallscreen.'">'."\n";
        print '<input type="hidden" name="dol_no_mouse_hover" value="'.$conf->dol_no_mouse_hover.'">'."\n";
        print '<input type="hidden" name="dol_use_jmobile" value="'.$conf->dol_use_jmobile.'">'."\n";

        print '<table summary="Dolibarr online demonstration for profile '.$profilearray['label'].'" style="font-size:14px;" class="centpercent CTable CTableRow'.($i%2==0?'1':'0').'">'."\n";
		// Title
        print '<tr>';
		print '<td width="130" id="a1'.$profilearray['key'].'" class="'.(empty($profilearray['url'])?'modulelineshow cursorpointer':'nomodulelines').'"><a href="'.$urlwithmod.'" class="'.(empty($profilearray['url'])?'modulelineshow':'nomodulelines').'"><img class="demothumb" src="'.$profilearray['icon'].'" alt="Demo '.$profilearray['label'].'"></a></td>';
		print '<td id="a2'.$profilearray['key'].'" class="'.(empty($profilearray['url'])?'modulelineshow cursorpointer':'nomodulelines').'"><a href="'.$urlwithmod.'" class="'.(empty($profilearray['url'])?'modulelineshow':'nomodulelines').'">'.$langs->trans($profilearray['label']).'</a></td>';
		print '</tr>'."\n";
        // Modules
        if (empty($profilearray['url']))
        {
    		print '<tr id="tr1'.$profilearray['key'].'" class="moduleline">';
    		print '<td colspan="2">';
    		print $langs->trans("ThisIsListOfModules").'<br>';
    		print '<div class="csscolumns">';
    		//print '<table width="100%">';
    		$listofdisabledmodules=explode(',',$profilearray['disablemodules']);
    		$j=0;
    		$nbcolsmod=empty($conf->dol_optimize_smallscreen)?4:3;
    		//var_dump($modules);
    		foreach($orders as $index => $key) // Loop on qualified (enabled) modules
    		{
    			//print $index.' '.$key;
    			$val = $modules[$index];
    		    $modulekeyname=strtolower($val->name);

    		    $modulequalified=1;
                if (! empty($val->always_enabled) || in_array($modulekeyname,$alwayshiddenuncheckedmodules)) $modulequalified=0;
                if ($val->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
                if ($val->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;
                if (! $modulequalified) continue;

                if (in_array($modulekeyname,$alwayshiddencheckedmodules))
                {
                    print "\n".'<!-- Module '.$modulekeyname.' hidden and always checked -->';
                    print '<input type="hidden" name="'.$modulekeyname.'" value="1">';
                }
                else
                {
                    $modulo=($j % $nbcolsmod);
        		    //if ($modulo == 0) print '<tr>';
                    //print '<td>';
                    print '<!-- id='.$val->numero.' -->';
                    print '<input type="checkbox" class="checkbox" name="'.$modulekeyname.'" value="1"';
                    if (in_array($modulekeyname,$alwaysuncheckedmodules)) print ' disabled';
                    if (! in_array($modulekeyname,$alwaysuncheckedmodules)  && (! in_array($modulekeyname,$listofdisabledmodules) || in_array($modulekeyname,$alwayscheckedmodules))) print ' checked';
                    print '> '.$val->getName().'<br>';
                    //print '</td>';
                    //if ($modulo == ($nbcolsmod - 1)) print '</tr>';
                    $j++;
                }
    		}
    		//print '</table>';
    		print '</div></td>';
    		print '</tr>'."\n";

		    print '<tr id="tr2'.$profilearray['key'].'" class="moduleline"><td colspan="2" align="center"><input type="submit" value=" &nbsp; &nbsp; '.$langs->trans("Start").' &nbsp; &nbsp; " class="button"></td></tr>';
        }
		print '</table>';
		print '</form>'."\n";

		//print '</td>';
		//if ($i % $NBOFCOLS == ($NBOFCOLS-1)) print '</tr>'."\n";
		//print '</tr>'."\n";
		$i++;
	}
}
//print '</table>';

print '</td>';
print '</tr>';

// Description
print '<tr><td>';


print '</td></tr>';

print '</table>';

// TODO Replace this with a hook
// Google Adsense (need Google module)
if (! empty($conf->google->enabled) && ! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
{
	if (empty($conf->dol_use_jmobile))
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
	else
	{
		print '<!-- google js advert tag disabled with jmobile -->'."\n";
	}
}

llxFooterVierge();

$db->close();


/**
 * Show header for demo
 *
 * @param 	string		$title		Title
 * @param 	string		$head		Head string
 * @return	void
 */
function llxHeaderVierge($title, $head = "")
{
    global $user, $conf, $langs;

    top_httphead();

    top_htmlhead($head,$title);

    print '<body class="demobody demobackground"><div style="padding: 20px;" class="demobackgrounddiv">'."\n";
}

/**
 * Show footer for demo
 *
 * @return	void
 */
function llxFooterVierge()
{
    printCommonFooter('public');

    print "\n";
    print "</div></body>\n";
    print "</html>\n";
}

