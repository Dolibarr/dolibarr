<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2010       Regis Houssin           <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/public/demo/index.php
 *		\ingroup    core
 *		\brief      Entry page to access demo
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once '../../core/lib/functions2.lib.php';

$langs->loadLangs(array("main", "install", "other"));

$conf->dol_hide_topmenu = GETPOST('dol_hide_topmenu', 'int');
$conf->dol_hide_leftmenu = GETPOST('dol_hide_leftmenu', 'int');
$conf->dol_optimize_smallscreen = GETPOST('dol_optimize_smallscreen', 'int');
$conf->dol_no_mouse_hover = GETPOST('dol_no_mouse_hover', 'int');
$conf->dol_use_jmobile = GETPOST('dol_use_jmobile', 'int');

// Security check
global $dolibarr_main_demo;
if (empty($dolibarr_main_demo)) {
	httponly_accessforbidden('Parameter dolibarr_main_demo must be defined in conf file with value "default login,default pass" to enable the demo entry page');
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$res = $hookmanager->initHooks(array('demo'));

$demoprofiles = array();
$alwayscheckedmodules = array();
$alwaysuncheckedmodules = array();
$alwayshiddencheckedmodules = array();
$alwayshiddenuncheckedmodules = array();

$url = '';
$url .= ($url ? '&' : '').($conf->dol_hide_topmenu ? 'dol_hide_topmenu='.$conf->dol_hide_topmenu : '');
$url .= ($url ? '&' : '').($conf->dol_hide_leftmenu ? 'dol_hide_leftmenu='.$conf->dol_hide_leftmenu : '');
$url .= ($url ? '&' : '').($conf->dol_optimize_smallscreen ? 'dol_optimize_smallscreen='.$conf->dol_optimize_smallscreen : '');
$url .= ($url ? '&' : '').($conf->dol_no_mouse_hover ? 'dol_no_mouse_hover='.$conf->dol_no_mouse_hover : '');
$url .= ($url ? '&' : '').($conf->dol_use_jmobile ? 'dol_use_jmobile='.$conf->dol_use_jmobile : '');
$url = DOL_URL_ROOT.'/index.php'.($url ? '?'.$url : '');

$tmpaction = 'view';
$parameters = array();
$object = new stdClass();
$reshook = $hookmanager->executeHooks('addDemoProfile', $parameters, $object, $tmpaction); // Note that $action and $object may have been modified by some hooks
$error = $hookmanager->error; $errors = $hookmanager->errors;
if (empty($reshook)) {
	$demoprofiles = array(
		array('default'=>'1', 'key'=>'profdemoservonly', 'label'=>'DemoCompanyServiceOnly',
		'disablemodules'=>'adherent,barcode,bom,cashdesk,don,expedition,externalsite,ftp,incoterm,mailmanspip,margin,mrp,prelevement,product,productbatch,stock,takepos',
		//'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot8.png',
		'icon'=>DOL_URL_ROOT.'/public/demo/demo-profile-service.jpg',
		'url'=>$url
		),
		array('default'=>'0', 'key'=>'profmanufacture', 'label'=>'DemoCompanyManufacturing',
			'disablemodules'=>'adherent,contrat,don,externalsite,ficheinter,ftp,mailmanspip,prelevement,service',
			'icon'=>DOL_URL_ROOT.'/public/demo/demo-profile-manufacturing.jpg',
			'url'=>$url
		),
		array('default'=>'0', 'key'=>'profdemoprodstock', 'label'=>'DemoCompanyProductAndStocks',
		'disablemodules'=>'adherent,bom,contrat,don,externalsite,ficheinter,ftp,mailmanspip,mrp,prelevement,service',
		//'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png',
		'icon'=>DOL_URL_ROOT.'/public/demo/demo-profile-product.jpg',
		'url'=>$url
		),
		array('default'=>'0', 'key'=>'profdemofun2', 'label'=>'DemoFundation2',
		'disablemodules'=>'barcode,cashdesk,bom,commande,commercial,compta,comptabilite,contrat,expedition,externalsite,ficheinter,ftp,incoterm,mailmanspip,margin,mrp,prelevement,product,productbatch,projet,propal,propale,service,societe,stock,tax,takepos',
		//'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png',
		'icon'=>DOL_URL_ROOT.'/public/demo/demo-profile-foundation.jpg',
		'url'=>$url
		),
		// All demo profile
		array('default'=>'0', 'key'=>'profdemoall', 'label'=>'ChooseYourDemoProfilMore',
		'disablemodules'=>'adherent,cashdesk,don,externalsite,mailmanspip',
		//'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'
		'icon'=>DOL_URL_ROOT.'/public/demo/demo-profile-all.jpg'
		)
	);


	// Visible
	$alwayscheckedmodules = array('barcode', 'bookmark', 'categorie', 'externalrss', 'fckeditor', 'geoipmaxmind', 'gravatar', 'memcached', 'syslog', 'user', 'webservices'); // Technical module we always want
	$alwaysuncheckedmodules = array('dav', 'dynamicprices', 'incoterm', 'loan', 'multicurrency', 'paybox', 'paypal', 'stripe', 'google', 'printing', 'scanner', 'socialnetworks', 'website'); // Module we dont want by default
	// Not visible
	$alwayshiddencheckedmodules = array('accounting', 'api', 'barcode', 'blockedlog', 'bookmark', 'clicktodial', 'comptabilite', 'cron', 'document', 'domain', 'externalrss', 'externalsite', 'fckeditor', 'geoipmaxmind', 'gravatar', 'label', 'ldap',
									'mailmanspip', 'notification', 'oauth', 'syslog', 'user', 'webservices', 'workflow',
									// Extended modules
									'memcached', 'numberwords', 'zipautofillfr');
	$alwayshiddenuncheckedmodules = array('cashdesk', 'collab', 'dav', 'debugbar', 'emailcollector', 'ftp', 'hrm', 'modulebuilder', 'printing', 'webservicesclient', 'zappier',
									// Extended modules
									'awstats', 'bittorrent', 'bootstrap', 'cabinetmed', 'cmcic', 'concatpdf', 'customfield', 'datapolicy', 'deplacement', 'dolicloud', 'filemanager', 'lightbox', 'mantis', 'monitoring', 'moretemplates', 'multicompany', 'nltechno', 'numberingpack', 'openstreetmap',
									'ovh', 'phenix', 'phpsysinfo', 'pibarcode', 'postnuke', 'dynamicprices', 'receiptprinter', 'selectbank', 'skincoloreditor', 'submiteverywhere', 'survey', 'thomsonphonebook', 'topten', 'tvacerfa', 'voyage', 'webcalendar', 'webmail');
}

// Search modules
$dirlist = $conf->file->dol_document_root;


// Search modules dirs
$modulesdir = dolGetModulesDirs();


$filename = array();
$modules = array();
$orders = array();
$categ = array();
$i = 0; // is a sequencer of modules found
$j = 0; // j is module number. Automatically affected if module number not defined.

foreach ($modulesdir as $dir) {
	// Charge tableaux modules, nom, numero, orders depuis repertoire dir
	$handle = @opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			//print "$i ".$file."\n<br>";
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
				$modName = substr($file, 0, dol_strlen($file) - 10);

				if ($modName) {
					try {
						include_once $dir.$file;
						$objMod = new $modName($db);

						if ($objMod->numero > 0) {
							$j = $objMod->numero;
						} else {
							$j = 1000 + $i;
						}

						$modulequalified = 1;

						// We discard modules according to features level (PS: if module is activated we always show it)
						$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));
						if ($objMod->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2 && !getDolGlobalString($const_name)) {
							$modulequalified = 0;
						}
						if ($objMod->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1 && !getDolGlobalString($const_name)) {
							$modulequalified = 0;
						}

						if ($modulequalified) {
							$modules[$i] = $objMod;
							$filename[$i] = $modName;
							$orders[$i]  = $objMod->family."_".$j; // Tri par famille puis numero module
							//print "x".$modName." ".$orders[$i]."\n<br>";
							$j++;
							$i++;
						}
					} catch (Exception $e) {
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

if (GETPOST('action', 'aZ09') == 'gotodemo') {     // Action run when we click on "Start" after selection modules
	//print 'ee'.GETPOST("demochoice");
	$disablestring = '';
	// If we disable modules using a profile choice
	if (GETPOST("demochoice")) {
		foreach ($demoprofiles as $profilearray) {
			if ($profilearray['key'] == GETPOST("demochoice")) {
				$disablestring = $profilearray['disablemodules'];
				break;
			}
		}
	}
	// If we disable modules using personalized list
	foreach ($modules as $val) {
		$modulekeyname = strtolower($val->name);
		if (!GETPOST($modulekeyname) && empty($val->always_enabled) && !in_array($modulekeyname, $alwayscheckedmodules)) {
			$disablestring .= $modulekeyname.',';
			if ($modulekeyname == 'propale') {
				$disablestring .= 'propal,';
			}
		}
	}

	// Do redirect to login page
	if ($disablestring) {
		if (GETPOST('urlfrom')) {
			$url .= (preg_match('/\?/', $url) ? '&amp;' : '?').'urlfrom='.urlencode(GETPOST('urlfrom', 'alpha'));
		}
		$url .= (preg_match('/\?/', $url) ? '&amp;' : '?').'disablemodules='.$disablestring;
		//var_dump($url);exit;
		header("Location: ".$url);
		exit;
	}
}


/*
 * View
 */

$head = '';
$head .= '<meta name="keywords" content="demo,online,demonstration,example,test,erp,crm,demos,web">'."\n";
$head .= '<meta name="description" content="Dolibarr ERP and CRM demo. You can test here several profiles for Dolibarr ERP and CRM demonstration.">'."\n";

$head .= '
<script type="text/javascript">
var openedId="";
jQuery(document).ready(function () {
    jQuery("tr.moduleline").hide();
    // Enable this to allow personalized setup
    jQuery(".modulelineshow").attr("href","#a1profdemoall");
    jQuery(".cursorpointer").css("cursor","pointer");
    jQuery(".modulelineshow").click(function() {
		console.log("We select the custom demo");
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

print '<div class="demoban demobackground">';
print '<div class="right" style="padding-right: 30px; padding-top: 30px;">';
print '<a alt="Official portal of your ERP CRM application" targe="_blank" href="https://www.dolibarr.org?utm_medium=website&utm_source=demo"><img class="demologo" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" alt="Dolibarr logo"></a>';
print '</div>';
print '</div>';

print '<div class="demobantext" style="max-width: 1024px;">';
print '<div style="font-size: 20px; padding: 40px;">';
print '<div class="hideonsmartphone" style="text-align: justify;">'.$langs->trans("DemoDesc").'</div><br>';
print '<div class="titre"><span style="font-size: 20px">'.$langs->trans("ChooseYourDemoProfil").'</span></div>';
print '</div>';
print '</div>';


print '<div class="clearboth"></div>';
print '<div class="demobanbox">';

$i = 0;
foreach ($demoprofiles as $profilearray) {
	if ($profilearray['default'] >= 0) {
		//print $profilearray['lang'];
		if (!empty($profilearray['lang'])) {
			$langs->load($profilearray['lang']);
		}

		$url = $_SERVER["PHP_SELF"].'?action=gotodemo';
		$urlwithmod = $url.'&amp;demochoice='.$profilearray['key'];
		// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
		//print "xx".$_SERVER["PHP_SELF"].' '.DOL_URL_ROOT.'<br>';

		$urlfrom = preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/i', '', $_SERVER["PHP_SELF"]);
		//print $urlfrom;

		if (!empty($profilearray['url'])) {
			$urlwithmod = $profilearray['url'];
			$urlwithmod = $urlwithmod.(preg_match('/\?/', $urlwithmod) ? '&amp;' : '?').'urlfrom='.urlencode($urlfrom);
			if (!empty($profilearray['disablemodules'])) {
				$urlwithmod = $urlwithmod.(preg_match('/\?/', $urlwithmod) ? '&amp;' : '?').'disablemodules='.$profilearray['disablemodules'];
			}
		}

		if (empty($profilearray['url'])) {
			print '<div class="clearboth"></div>';
		}

		print '<form method="POST" class="valigntop inline-block" name="form'.$profilearray['key'].'" id="form'.$profilearray['key'].'" action="'.$_SERVER["PHP_SELF"].'#a1'.$profilearray['key'].'">'."\n";
		print '<input type="hidden" name="action" value="gotodemo">'."\n";
		print '<input type="hidden" name="urlfrom" value="'.dol_escape_htmltag($urlfrom).'">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		print '<input type="hidden" name="username" value="demo">'."\n";
		print '<input type="hidden" name="dol_hide_topmenu" value="'.$conf->dol_hide_topmenu.'">'."\n";
		print '<input type="hidden" name="dol_hide_leftmenu" value="'.$conf->dol_hide_leftmenu.'">'."\n";
		print '<input type="hidden" name="dol_optimize_smallscreen" value="'.$conf->dol_optimize_smallscreen.'">'."\n";
		print '<input type="hidden" name="dol_no_mouse_hover" value="'.$conf->dol_no_mouse_hover.'">'."\n";
		print '<input type="hidden" name="dol_use_jmobile" value="'.$conf->dol_use_jmobile.'">'."\n";

		print '<div id="div'.$profilearray['key'].'" summary="Dolibarr online demonstration for profile '.$profilearray['label'].'" class="center inline-block CTable CTableRow'.($i % 2 == 0 ? '1' : '0').'">'."\n";


		print '<div id="a1'.$profilearray['key'].'" class="demobox '.(empty($profilearray['url']) ? 'modulelineshow cursorpointer' : 'nomodulelines').'">';

		print '<a href="'.$urlwithmod.'" class="'.(empty($profilearray['url']) ? 'modulelineshow' : 'nomodulelines').'">';
		print '<div style="padding: 10px;">';

		print '<img class="demothumb" src="'.$profilearray['icon'].'" alt="Demo '.$profilearray['label'].'">';

		print '<div class="clearboth"></div>';

		print '<div class="demothumbtext">';
		print $langs->trans($profilearray['label']);
		print '</div>';

		print '</div>';
		print '</a>';


		// Modules (a profile you must choose modules)
		if (empty($profilearray['url'])) {
			print '<div id="tr1'.$profilearray['key'].'" class="moduleline hidden" style="margin-left: 8px; margin-right: 8px; text-align: justify; font-size:0.75em; line-height: 130%; padding-bottom: 8px">';

			print '<span class="opacitymedium">'.$langs->trans("ThisIsListOfModules").'</span><br><br>';

			print '<div class="csscolumns">';

			$listofdisabledmodules = explode(',', $profilearray['disablemodules']);
			$j = 0;
			//$nbcolsmod = empty($conf->dol_optimize_smallscreen) ? 4 : 3;
			//var_dump($modules);
			foreach ($orders as $index => $key) { // Loop on qualified (enabled) modules
				//print $index.' '.$key;
				$val = $modules[$index];
				$modulekeyname = strtolower($val->name);

				$modulequalified = 1;
				if (!empty($val->always_enabled) || in_array($modulekeyname, $alwayshiddenuncheckedmodules)) {
					$modulequalified = 0;
				}
				if ($val->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2 && !getDolGlobalString($const_name)) {
					$modulequalified = 0;
				}
				if ($val->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1 && !getDolGlobalString($const_name)) {
					$modulequalified = 0;
				}
				if (!$modulequalified) {
					continue;
				}

				if (in_array($modulekeyname, $alwayshiddencheckedmodules)) {
					print "\n".'<!-- Module '.$modulekeyname.' hidden and always checked -->';
					print '<input type="hidden" name="'.$modulekeyname.'" value="1">';
				} else {
					//$modulo = ($j % $nbcolsmod);
					//if ($modulo == 0) print '<tr>';
					print '<!-- id='.$val->numero.' -->';
					print '<div class="nowrap">';
					print '<input type="checkbox" class="checkbox valignmiddle paddingright" id="id'.$modulekeyname.'" name="'.$modulekeyname.'" value="1" title="'.dol_escape_htmltag($val->getName()).'"';
					$disabled = '';
					if (in_array($modulekeyname, $alwaysuncheckedmodules)) {
						$disabled = 'disabled';
						print ' '.$disabled;
					}
					if (!in_array($modulekeyname, $alwaysuncheckedmodules) && (!in_array($modulekeyname, $listofdisabledmodules) || in_array($modulekeyname, $alwayscheckedmodules))) {
						print ' checked';
					}
					print '>';
					/*
					$s = img_picto('', $modulekeyname, 'class="pictofixedwidth paddingleft"');
					if ($s) {
						print $s;
					} else {
						print img_picto('', 'generic', 'class="pictofixedwidth paddingleft"');
					}*/
					print '<label for="id'.$modulekeyname.'" class="inline-block demomaxoveflow valignmiddle paddingleft'.($disabled ? ' opacitymedium' : '').'" title="'.dol_escape_htmltag($val->getName()).'">'.$val->getName().'</label><br>';
					print '</div>';
					//if ($modulo == ($nbcolsmod - 1)) print '</tr>';
					$j++;
				}
			}

			print '</div>';

			print '<br><div class="center">';
			print '<input type="submit" value=" &nbsp; &nbsp; '.$langs->trans("Start").' &nbsp; &nbsp; " class="button">';
			print '<br><br>';
			print '</div>';

			print '</div>';
		}

		print '</div></div>';
		print '</form>'."\n";

		$i++;
	}
}

print '</div>';

print '<br>';


// TODO Replace this with a hook
// Google Adsense (need Google module)
if (isModEnabled('google') && getDolGlobalString('MAIN_GOOGLE_AD_CLIENT') && getDolGlobalString('MAIN_GOOGLE_AD_SLOT')) {
	if (empty($conf->dol_use_jmobile)) {
		print '<div align="center">'."\n";
		print '<script><!--'."\n";
		print 'google_ad_client = "' . getDolGlobalString('MAIN_GOOGLE_AD_CLIENT').'";'."\n";
		print 'google_ad_slot = "' . getDolGlobalString('MAIN_GOOGLE_AD_SLOT').'";'."\n";
		print 'google_ad_width = ' . getDolGlobalString('MAIN_GOOGLE_AD_WIDTH').';'."\n";
		print 'google_ad_height = ' . getDolGlobalString('MAIN_GOOGLE_AD_HEIGHT').';'."\n";
		print '//-->'."\n";
		print '</script>'."\n";
		print '<script src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>'."\n";
		print '</div>'."\n";
	} else {
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
	top_httphead();

	top_htmlhead($head, $title, 0, 0, array(), array('public/demo/demo.css'), 0, 1);

	print '<body class="demobody"><div class="demobackgrounddiv">'."\n";
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
