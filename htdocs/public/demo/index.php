<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require '../../main.inc.php';

$langs->load("main");
$langs->load("install");
$langs->load("other");

// Security check
global $dolibarr_main_demo;
if (empty($dolibarr_main_demo)) accessforbidden('Parameter dolibarr_main_demo must be defined in conf file with value "default login,default pass" to enable the demo entry page',1,1,1);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('demo'));

$demoprofiles=array(
	array('default'=>'1', 'key'=>'profdemoservonly','label'=>'DemoCompanyServiceOnly',
	'disablemodules'=>'adherent,barcode,boutique,cashdesk,categorie,don,expedition,externalsite,mailmanspip,prelevement,product,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot8.png'),
	array('default'=>'-1','key'=>'profdemoshopwithdesk','label'=>'DemoCompanyShopWithCashDesk',
	'disablemodules'=>'adherent,boutique,categorie,don,externalsite,ficheinter,mailmanspip,prelevement,product,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoprodstock','label'=>'DemoCompanyProductAndStocks',
	'disablemodules'=>'adherent,boutique,contrat,categorie,don,externalsite,ficheinter,mailmanspip,prelevement,service',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoall','label'=>'DemoCompanyAll',
	'disablemodules'=>'adherent,boutique,don,externalsite,mailmanspip',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'),
	array('default'=>'-1', 'key'=>'profdemofun','label'=>'DemoFundation',
	'disablemodules'=>'banque,barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,facture,ficheinter,fournisseur,mailmanspip,prelevement,product,projet,propal,propale,service,societe,stock,tax',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
	array('default'=>'0', 'key'=>'profdemofun2','label'=>'DemoFundation2',
	'disablemodules'=>'barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,facture,ficheinter,fournisseur,mailmanspip,prelevement,product,projet,propal,propale,service,societe,stock,tax',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png')
);


$tmpaction = 'view';
$parameters=array();
$object=(object) 'nothing';
$reshook=$hookmanager->executeHooks('addDemoProfile', $parameters, $object, $tmpaction);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=$hookmanager->errors;
/*
$demoprofiles[]=array('default'=>'0', 'key'=>'profdemomed', 'lang'=>'cabinetmed@cabinetmed', 'label'=>'DemoCabinetMed', 'url'=>'http://demodolimed.dolibarr.org',
	'disablemodules'=>'adherent,boutique,don,externalsite',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png');
*/

$alwayscheckedmodules=array('barcode','bookmark','externalrss','fckeditor','geoipmaxmind','gravatar','memcached','syslog','user','webservices');  // Technical module we always want
$alwaysuncheckedmodules=array('paybox','paypal','google','scanner','workflow');  // Module we never want
$alwayshiddencheckedmodules=array('accounting','barcode','bookmark','clicktodial','comptabilite','document','domain','externalrss','externalsite','fckeditor','geoipmaxmind','gravatar','label','ldap',
								'mailmanspip','notification','syslog','user','webservices',
                                // Extended modules
                                'memcached','numberwords','zipautofillfr');
$alwayshiddenuncheckedmodules=array('boutique','ftp',
                                // Extended modules
                                'awstats','bittorrent','cabinetmed','cmcic','concatpdf','dolicloud','filemanager','lightbox','mantis','monitoring','moretemplates','nltechno','numberingpack','openstreetmap',
                                'ovh','phenix','phpsysinfo','pibarcode','postnuke','skincoloreditor','submiteverywhere','survey','thomsonphonebook','topten','tvacerfa','voyage','webcalendar','webmail');

// Search modules
$dirlist=$conf->file->dol_document_root;


// Search modules dirs
$modulesdir = array();
foreach ($conf->file->dol_document_root as $type => $dirroot)
{
    $modulesdir[$dirroot . '/core/modules/'] = $dirroot . '/core/modules/';

    $handle=@opendir($dirroot);
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
            {
                if (is_dir($dirroot . '/' . $file . '/core/modules/'))
                {
                    $modulesdir[$dirroot . '/' . $file . '/core/modules/'] = $dirroot . '/' . $file . '/core/modules/';
                }
            }
        }
        closedir($handle);
    }
}
//var_dump($modulesdir);


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
                        if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
                        if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;

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
		$url=DOL_URL_ROOT.'/index.php?disablemodules='.$disablestring;
		if (GETPOST('urlfrom','alpha')) $url.='&urlfrom='.GETPOST('urlfrom','alpha');
		header("Location: ".$url);
		exit;
	}
}


/*
 * View
 */

llxHeaderVierge($langs->trans("DolibarrDemo"));

?>
<script type="text/javascript">
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
		print '<tr>';
		print '<td>'."\n";

		print '<form method="POST" name="form'.$profilearray['key'].'" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="action" value="gotodemo">'."\n";
        print '<input type="hidden" name="urlfrom" value="'.urlencode($urlfrom).'">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="username" value="demo">'."\n";
        print '<table summary="Dolibarr online demonstration for profile '.$profilearray['label'].'" style="font-size:14px;" width="100%" class="CTable CTableRow'.($i%2==0?'1':'0').'">'."\n";
		// Title
        print '<tr>';
		print '<td width="50"><a href="'.$urlwithmod.'" id="a1'.$profilearray['key'].'" class="'.(empty($profilearray['url'])?'modulelineshow':'nomodulelines').'"><img src="'.$profilearray['icon'].'" width="48" border="0" alt="Demo '.$profilearray['label'].'"></a></td>';
		print '<td><a href="'.$urlwithmod.'" id="a2'.$profilearray['key'].'" class="'.(empty($profilearray['url'])?'modulelineshow':'nomodulelines').'">'.$langs->trans($profilearray['label']).'</a></td>';
		print '</tr>'."\n";
        // Modules
        if (empty($profilearray['url']))
        {
    		print '<tr id="tr1'.$profilearray['key'].'" class="moduleline">';
    		print '<td colspan="2">';
    		print $langs->trans("ThisIsListOfModules").'<br>';
    		print '<table width="100%">';
    		$listofdisabledmodules=explode(',',$profilearray['disablemodules']);
    		$j=0;$nbcolsmod=4;
    		foreach($modules as $val) // Loop on qualified (enabled) modules
    		{
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
        		    if ($modulo == 0) print '<tr>';
                    print '<td><input type="checkbox" class="checkbox" name="'.$modulekeyname.'" value="1"';
                    if (in_array($modulekeyname,$alwaysuncheckedmodules)) print ' disabled="disabled"';
                    if (! in_array($modulekeyname,$alwaysuncheckedmodules)  && (! in_array($modulekeyname,$listofdisabledmodules) || in_array($modulekeyname,$alwayscheckedmodules))) print ' checked="checked"';
                    print '> '.$val->getName().' &nbsp;';
                    print '<!-- id='.$val->numero.' -->';
                    print '</td>';
                    if ($modulo == ($nbcolsmod - 1)) print '</tr>';
                    $j++;
                }
    		}
    		print '</table>';
    		print '</td>';
    		print '</tr>'."\n";

		    print '<tr id="tr2'.$profilearray['key'].'" class="moduleline"><td colspan="'.$nbcolsmod.'" align="center"><input type="submit" value=" &nbsp; &nbsp; '.$langs->trans("Start").' &nbsp; &nbsp; " class="button"></td></tr>';
        }
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

llxFooterVierge();


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
    print '<style type="text/css">'."\n";
    print '.body { font: 12px arial,verdana,helvetica !important; }'."\n";
    print '.CTable {
    	padding: 6px;
    	font: 12px arial,verdana,helvetica;
        font-weight: normal;
        color: #444444 !important;
        /* text-shadow: 1px 2px 3px #AFAFAF; */

margin: 8px 0px 8px 2px;

border-left: 1px solid #DDD;
border-right: 1px solid #DDD;
border-bottom: 1px solid #EEE;
border-radius: 8px;
-moz-border-radius: 8px;

-moz-box-shadow: 4px 4px 4px #EEE;
-webkit-box-shadow: 4px 4px 4px #EEE;
box-shadow: 4px 4px 4px #EEE;

background-image: linear-gradient(bottom, rgb(246,248,250) 85%, rgb(235,235,238) 100%);
background-image: -o-linear-gradient(bottom, rgb(246,248,250) 85%, rgb(235,235,238) 100%);
background-image: -moz-linear-gradient(bottom, rgb(246,248,250) 85%, rgb(235,235,238) 100%);
background-image: -webkit-linear-gradient(bottom, rgb(246,248,250) 85%, rgb(235,235,238) 100%);
background-image: -ms-linear-gradient(bottom, rgb(246,248,250) 85%, rgb(235,235,238) 100%);

    }';
//    print '.CTableRow1      { background: #f0f0f0; color: #000000; }';
//    print '.CTableRow0      { background: #fafafa; color: #000000; }';
    print '</style>';
    print "</head>\n";
    print '<body style="margin: 20px;">'."\n";
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
    print "</body>\n";
    print "</html>\n";
}

?>
