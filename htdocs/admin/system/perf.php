<?php
<<<<<<< HEAD
/* Copyright (C) 2013	Laurent Destailleur		<eldy@users.sourceforge.net>
=======
/* Copyright (C) 2013-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/admin/system/perf.php
 *  \brief      Page to show Performance information
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

<<<<<<< HEAD
$langs->load("admin");
$langs->load("install");
$langs->load("other");
=======
// Load translation files required by the page
$langs->loadLangs(array("install","other","admin"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if (! $user->admin)
	accessforbidden();

<<<<<<< HEAD
if (GETPOST('action','aZ09') == 'donothing')
=======
if (GETPOST('action', 'aZ09') == 'donothing')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	exit;
}


/*
 * View
 */

$form=new Form($db);
<<<<<<< HEAD
$nowstring=dol_print_date(dol_now(),'dayhourlog');

llxHeader();

print load_fiche_titre($langs->trans("PerfDolibarr"),'','title_setup');

print $langs->trans("YouMayFindPerfAdviceHere",'https://wiki.dolibarr.org/index.php/FAQ_Increase_Performance').' (<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Reload").'</a>)<br>';
=======
$nowstring=dol_print_date(dol_now(), 'dayhourlog');

llxHeader();

print load_fiche_titre($langs->trans("PerfDolibarr"), '', 'title_setup');

print $langs->trans("YouMayFindPerfAdviceHere", 'https://wiki.dolibarr.org/index.php/FAQ_Increase_Performance').' (<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Reload").'</a>)<br>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Recupere la version de PHP
$phpversion=version_php();
print "<br>PHP - ".$langs->trans("Version").": ".$phpversion."<br>\n";

// Recupere la version du serveur web
print "<br>Web server - ".$langs->trans("Version").": ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";

// XDebug
print '<br>';
print '<strong>'.$langs->trans("XDebug").'</strong>: ';
$test=!function_exists('xdebug_is_enabled');
<<<<<<< HEAD
if ($test) print img_picto('','tick.png').' '.$langs->trans("NotInstalled");
else
{
	print img_picto('','warning').' '.$langs->trans("XDebugInstalled");
=======
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled");
else
{
	print img_picto('', 'warning').' '.$langs->trans("XDebugInstalled");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

<<<<<<< HEAD
=======
// Module debugbar
print '<br>';
print '<strong>'.$langs->trans("DebugBar").'</strong>: ';
$test=empty($conf->debugbar->enabled);
if ($test) print img_picto('', 'tick.png').' '.$langs->trans("NotInstalled");
else
{
    print img_picto('', 'warning').' '.$langs->trans("DebugBarModuleActivated");
    //print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
// Applicative cache
print '<br>';
print '<strong>'.$langs->trans("ApplicativeCache").'</strong>: ';
$test=!empty($conf->memcached->enabled);
if ($test)
{
	if (!empty($conf->global->MEMCACHED_SERVER))
	{
<<<<<<< HEAD
		print img_picto('','tick.png').' '.$langs->trans("MemcachedAvailableAndSetup");
		print ' '.$langs->trans("MoreInformation").' <a href="'.dol_buildpath('/memcached/admin/memcached.php',1).'">Memcached module admin page</a>';
	}
	else
	{
		print img_picto('','warning').' '.$langs->trans("MemcachedModuleAvailableButNotSetup");
		print ' <a href="'.dol_buildpath('/memcached/admin/memcached.php',1).'">Memcached module admin page</a>';
	}
}
else print img_picto('','warning').' '.$langs->trans("MemcachedNotAvailable");
=======
		print img_picto('', 'tick.png').' '.$langs->trans("MemcachedAvailableAndSetup");
		print ' '.$langs->trans("MoreInformation").' <a href="'.dol_buildpath('/memcached/admin/memcached.php', 1).'">Memcached module admin page</a>';
	}
	else
	{
		print img_picto('', 'warning').' '.$langs->trans("MemcachedModuleAvailableButNotSetup");
		print ' <a href="'.dol_buildpath('/memcached/admin/memcached.php', 1).'">Memcached module admin page</a>';
	}
}
else print img_picto('', 'warning').' '.$langs->trans("MemcachedNotAvailable");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</br>';

// OPCode cache
print '<br>';
print '<strong>'.$langs->trans("OPCodeCache").'</strong>: ';
$foundcache=0;
$test=function_exists('xcache_info');
if (! $foundcache && $test)
{
	$foundcache++;
<<<<<<< HEAD
	print img_picto('','tick.png').' '.$langs->trans("XCacheInstalled");
=======
	print img_picto('', 'tick.png').' '.$langs->trans("XCacheInstalled");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xcache.php'.'">Xcache admin page</a>';
}
$test=function_exists('eaccelerator_info');
if (! $foundcache && $test)
{
	$foundcache++;
<<<<<<< HEAD
	print img_picto('','tick.png').' '.$langs->trans("EAcceleratorInstalled");
=======
	print img_picto('', 'tick.png').' '.$langs->trans("EAcceleratorInstalled");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
$test=function_exists('opcache_get_status');
if (! $foundcache && $test)
{
	$foundcache++;
<<<<<<< HEAD
	print img_picto('','tick.png').' '.$langs->trans("ZendOPCacheInstalled");  // Should be by default starting with PHP 5.5
=======
	print img_picto('', 'tick.png').' '.$langs->trans("ZendOPCacheInstalled");  // Should be by default starting with PHP 5.5
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	//$tmp=opcache_get_status();
	//var_dump($tmp);
}
$test=function_exists('apc_cache_info');
if (! $foundcache && $test)
{
	//var_dump(apc_cache_info());
	if (ini_get('apc.enabled'))
	{
		$foundcache++;
<<<<<<< HEAD
		print img_picto('','tick.png').' '.$langs->trans("APCInstalled");
	}
	else
	{
		print img_picto('','warning').' '.$langs->trans("APCCacheInstalledButDisabled");
=======
		print img_picto('', 'tick.png').' '.$langs->trans("APCInstalled");
	}
	else
	{
		print img_picto('', 'warning').' '.$langs->trans("APCCacheInstalledButDisabled");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}
if (! $foundcache) print $langs->trans("NoOPCodeCacheFound");
print '<br>';

// HTTPCacheStaticResources
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
  var getphpurl;
  var cachephpstring;
  var compphpstring;
  getphpurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/index.php\',
    cache: false,
    /* async: false, */
    /* crossDomain: true,*/
    success: function () {
    	cachephpstring=getphpurl.getResponseHeader(\'Cache-Control\');
    	/* alert(\'php:\'+getphpurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+cachephpstring);*/
      	if (cachephpstring == null || cachephpstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcachephpok").hide();
      		jQuery("#httpcachephpko").show();
		}
      	else
      	{
      		jQuery("#httpcachephpok").show();
      		jQuery("#httpcachephpko").hide();
      	}
      	compphpstring=getphpurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'php:\'+getphpurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+compphpstring);*/
      	if (compphpstring == null || (compphpstring.indexOf("gzip") == -1 && compphpstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompphpok").hide();
      		jQuery("#httpcompphpko").show();
		}
      	else
      	{
      		jQuery("#httpcompphpok").show();
      		jQuery("#httpcompphpko").hide();
      	}
	}
  })

  var getcssurl;
  var cachecssstring;
  var compcssstring;
  getcssurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/includes/jquery/css/base/jquery-ui.css\',
    cache: false,
    /* async: false, */
    /*crossDomain: true, */
    success: function () {
      	cachecssstring=getcssurl.getResponseHeader(\'Cache-Control\');
      	/* alert(\'css:\'+getcssurl.getAllResponseHeaders()); */
      	/*alert(\'css:\'+cachecssstring);*/
      	if (cachecssstring != null && cachecssstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcachecssok").hide();
      		jQuery("#httpcachecssko").show();
		}
      	else
      	{
      		jQuery("#httpcachecssok").show();
      		jQuery("#httpcachecssko").hide();
      	}
      	compcssstring=getcssurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'php:\'+getcssurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+compcssstring);*/
      	if (compcssstring == null || (compcssstring.indexOf("gzip") == -1 && compcssstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompcssok").hide();
      		jQuery("#httpcompcssko").show();
		}
      	else
      	{
      		jQuery("#httpcompcssok").show();
      		jQuery("#httpcompcssko").hide();
      	}
	}
  })

  var getcssphpurl;
  var cachecssphpstring;
  var compcssphpstring;
  getcssphpurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/theme/eldy/style.css.php\',
    cache: false,
    /* async: false, */
    /*crossDomain: true,*/
    success: function () {
      	cachecssphpstring=getcssphpurl.getResponseHeader(\'Cache-Control\');
      	/* alert(\'cssphp:\'+getcssphpurl.getAllResponseHeaders()); */
      	/*alert(\'cssphp:\'+cachecssphpstring);*/
      	if (cachecssphpstring != null && cachecssphpstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcachecssphpok").hide();
      		jQuery("#httpcachecssphpko").show();
		}
      	else
      	{
      		jQuery("#httpcachecssphpok").show();
      		jQuery("#httpcachecssphpko").hide();
      	}
      	compcssphpstring=getcssphpurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'php:\'+getcssphpurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+compcssphpstring);*/
      	if (compcssphpstring == null || (compcssphpstring.indexOf("gzip") == -1 && compcssphpstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompcssphpok").hide();
      		jQuery("#httpcompcssphpko").show();
		}
      	else
      	{
      		jQuery("#httpcompcssphpok").show();
      		jQuery("#httpcompcssphpko").hide();
      	}
    }
  })

  var getimgurl;
  var cacheimgstring;
  var compimgstring;
  getimgurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/theme/eldy/img/help.png\',
    cache: false,
    /* async: false, */
    /*crossDomain: true,*/
    success: function () {
      	cacheimgstring=getimgurl.getResponseHeader(\'Cache-Control\');
      	/* alert(\'img:\'+getimgurl.getAllResponseHeaders()); */
      	/*alert(\'img:\'+cacheimgstring);*/
      	if (cacheimgstring != null && cacheimgstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcacheimgok").hide();
      		jQuery("#httpcacheimgko").show();
		}
      	else
      	{
      		jQuery("#httpcacheimgok").show();
      		jQuery("#httpcacheimgko").hide();
      	}
      	compimgstring=getimgurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'php:\'+getimgurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+compimgstring);*/
      	if (compimgstring == null || (compimgstring.indexOf("gzip") == -1 && compimgstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompimgok").hide();
      		jQuery("#httpcompimgko").show();
		}
      	else
      	{
      		jQuery("#httpcompimgok").show();
      		jQuery("#httpcompimgko").hide();
      	}
	 }
  })

  var getjsurl;
  var cachejsstring;
  var compjsstring;
  getjsurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/core/js/lib_rare.js\',
    cache: false,
    /* async: false, */
    /* crossDomain: true,*/
    success: function () {
      	cachejsstring=getjsurl.getResponseHeader(\'Cache-Control\');
      	/*alert(\'js:\'+getjsurl.getAllResponseHeaders());*/
      	/*alert(\'js:\'+cachejsstring);*/
      	if (cachejsstring != null && cachejsstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcachejsok").hide();
      		jQuery("#httpcachejsko").show();
		}
      	else
      	{
      		jQuery("#httpcachejsok").show();
      		jQuery("#httpcachejsko").hide();
      	}
      	compjsstring=getjsurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'js:\'+getjsurl.getAllResponseHeaders()); */
      	/*alert(\'js:\'+compjsstring);*/
      	if (compjsstring == null || (compjsstring.indexOf("gzip") == -1 && compjsstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompjsok").hide();
      		jQuery("#httpcompjsko").show();
		}
      	else
      	{
      		jQuery("#httpcompjsok").show();
      		jQuery("#httpcompjsko").hide();
      	}
    }
  })

  var getjsphpurl;
  var cachejsphpstring;
  var compjsphpstring;
  getjsphpurl = $.ajax({
    type: "GET",
    url: \''.DOL_URL_ROOT.'/core/js/lib_head.js.php\',
    cache: false,
    /* async: false, */
    /* crossDomain: true,*/
    success: function () {
      	cachejsphpstring=getjsphpurl.getResponseHeader(\'Cache-Control\');
      	/* alert(\'jsphp:\'+getjsphpurl.getAllResponseHeaders()); */
      	/*alert(\'jsphp:\'+cachejsphpstring);*/
      	if (cachejsphpstring != null && cachejsphpstring.indexOf("no-cache") !== -1)
      	{
	      	jQuery("#httpcachejsphpok").hide();
      		jQuery("#httpcachejsphpko").show();
		}
      	else
      	{
      		jQuery("#httpcachejsphpok").show();
      		jQuery("#httpcachejsphpko").hide();
      	}
      	compjsphpstring=getjsphpurl.getResponseHeader(\'Content-Encoding\');
      	/* alert(\'php:\'+getjsphpurl.getAllResponseHeaders()); */
      	/*alert(\'php:\'+compjsphpstring);*/
      	if (compjsphpstring == null || (compjsphpstring.indexOf("gzip") == -1 && compjsphpstring.indexOf("deflate") == -1))
      	{
	      	jQuery("#httpcompjsphpok").hide();
      		jQuery("#httpcompjsphpko").show();
		}
      	else
      	{
      		jQuery("#httpcompjsphpok").show();
      		jQuery("#httpcompjsphpko").hide();
      	}
    }
  })

});
</script>';


print '<br>';
print '<strong>'.$langs->trans("HTTPCacheStaticResources").' - ';
print $form->textwithpicto($langs->trans("CacheByServer"), $langs->trans("CacheByServerDesc"));
print ':</strong><br>';
// No cahce on PHP
//print '<div id="httpcachephpok">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'php (.php)').'</div>';
//print '<div id="httpcachephpko">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeNotCached",'php (.php)').'</div>';
// Cache on rest
<<<<<<< HEAD
print '<div id="httpcachecssok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCached",'css (.css)').'</div>';
print '<div id="httpcachecssko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCached",'css (.css)').'</div>';
print '<div id="httpcachecssphpok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCached",'css (.css.php)').'</div>';
print '<div id="httpcachecssphpko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCached",'css (.css.php)').'</div>';
print '<div id="httpcacheimgok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCached",'img (.png)').'</div>';
print '<div id="httpcacheimgko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCached",'img (.png)').'</div>';
print '<div id="httpcachejsok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCached",'javascript (.js)').'</div>';
print '<div id="httpcachejsko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCached",'javascript (.js)').'</div>';
print '<div id="httpcachejsphpok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCached",'javascript (.js.php)').'</div>';
print '<div id="httpcachejsphpko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCached",'javascript (.js.php)').'</div>';
=======
print '<div id="httpcachecssok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCached", 'css (.css)').'</div>';
print '<div id="httpcachecssko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCached", 'css (.css)').'</div>';
print '<div id="httpcachecssphpok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCached", 'css (.css.php)').'</div>';
print '<div id="httpcachecssphpko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCached", 'css (.css.php)').'</div>';
print '<div id="httpcacheimgok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCached", 'img (.png)').'</div>';
print '<div id="httpcacheimgko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCached", 'img (.png)').'</div>';
print '<div id="httpcachejsok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCached", 'javascript (.js)').'</div>';
print '<div id="httpcachejsko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCached", 'javascript (.js)').'</div>';
print '<div id="httpcachejsphpok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCached", 'javascript (.js.php)').'</div>';
print '<div id="httpcachejsphpko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCached", 'javascript (.js.php)').'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<br>';
print '<strong>'.$langs->trans("HTTPCacheStaticResources").' - ';
print $langs->trans("CacheByClient").':</strong><br>';
print $langs->trans("TestNotPossibleWithCurrentBrowsers").'<br>';


// Compressions
print '<br>';
print '<strong>';
print $form->textwithpicto($langs->trans("CompressionOfResources"), $langs->trans("CompressionOfResourcesDesc"));
print '</strong>: ';
//$tmp=getURLContent(DOL_URL_ROOT.'/index.php','GET');var_dump($tmp);
print '<br>';
// on PHP
<<<<<<< HEAD
print '<div id="httpcompphpok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'php (.php)').'</div>';
print '<div id="httpcompphpko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'php (.php)').'</div>';
// on rest
print '<div id="httpcompcssok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'css (.css)').'</div>';
print '<div id="httpcompcssko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'css (.css)').'</div>';
print '<div id="httpcompcssphpok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'css (.css.php)').'</div>';
print '<div id="httpcompcssphpko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'css (.css.php)').'</div>';
//print '<div id="httpcompimgok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'img (.png)').'</div>';
//print '<div id="httpcompimgko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'img (.png)').'</div>';
print '<div id="httpcompjsok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'javascript (.js)').'</div>';
print '<div id="httpcompjsko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'javascript (.js)').'</div>';
print '<div id="httpcompjsphpok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'javascript (.js.php)').'</div>';
print '<div id="httpcompjsphpko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'javascript (.js.php)').'</div>';
=======
print '<div id="httpcompphpok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCompressed", 'php (.php)').'</div>';
print '<div id="httpcompphpko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCompressed", 'php (.php)').'</div>';
// on rest
print '<div id="httpcompcssok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCompressed", 'css (.css)').'</div>';
print '<div id="httpcompcssko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCompressed", 'css (.css)').'</div>';
print '<div id="httpcompcssphpok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCompressed", 'css (.css.php)').'</div>';
print '<div id="httpcompcssphpko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCompressed", 'css (.css.php)').'</div>';
//print '<div id="httpcompimgok">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeCompressed",'img (.png)').'</div>';
//print '<div id="httpcompimgko">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'img (.png)').'</div>';
print '<div id="httpcompjsok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCompressed", 'javascript (.js)').'</div>';
print '<div id="httpcompjsko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCompressed", 'javascript (.js)').'</div>';
print '<div id="httpcompjsphpok">'.img_picto('', 'tick.png').' '.$langs->trans("FilesOfTypeCompressed", 'javascript (.js.php)').'</div>';
print '<div id="httpcompjsphpko">'.img_picto('', 'warning.png').' '.$langs->trans("FilesOfTypeNotCompressed", 'javascript (.js.php)').'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Database driver
print '<br>';
print '<strong>'.$langs->trans("DriverType").'</strong>: ';
print '<br>';
if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli')
{
	$test=($conf->db->type == 'mysqli');
	if ($test)
	{
<<<<<<< HEAD
		print img_picto('','tick.png').' '.$langs->trans("YouUseBestDriver",$conf->db->type);
	}
	else
	{
		print img_picto('','warning.png').' '.$langs->trans("YouDoNotUseBestDriver",$conf->db->type,'mysqli');
=======
		print img_picto('', 'tick.png').' '.$langs->trans("YouUseBestDriver", $conf->db->type);
	}
	else
	{
		print img_picto('', 'warning.png').' '.$langs->trans("YouDoNotUseBestDriver", $conf->db->type, 'mysqli');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	print '<br>';
}

// Product search
print '<br>';
print '<strong>'.$langs->trans("SearchOptim").'</strong>: ';
print '<br>';
$tab = array();
$sql = "SELECT COUNT(*) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$resql=$db->query($sql);
if ($resql)
{
	$limitforoptim=10000;
	$num=$db->num_rows($resql);
	$obj=$db->fetch_object($resql);
	$nb=$obj->nb;
	if ($nb > $limitforoptim)
	{
		if (empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))
		{
<<<<<<< HEAD
			print img_picto('','warning.png').' '.$langs->trans("YouHaveXProductUseSearchOptim",$nb);
		}
		else
		{
			print img_picto('','tick.png').' '.$langs->trans("YouHaveXProductAndSearchOptimOn",$nb);
=======
			print img_picto('', 'warning.png').' '.$langs->trans("YouHaveXProductUseSearchOptim", $nb);
		}
		else
		{
			print img_picto('', 'tick.png').' '.$langs->trans("YouHaveXProductAndSearchOptimOn", $nb);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
	}
	else
	{
<<<<<<< HEAD
		print img_picto('','tick.png').' '.$langs->trans("NbOfProductIsLowerThanNoPb",$nb);
=======
		print img_picto('', 'tick.png').' '.$langs->trans("NbOfProductIsLowerThanNoPb", $nb);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	print '<br>';
	$db->free($resql);
}

// Browser
print '<br>';
print '<strong>'.$langs->trans("Browser").'</strong>:<br>';
if (! in_array($conf->browser->name, array('chrome','opera','safari','firefox')))
{
<<<<<<< HEAD
	print img_picto('','warning.png').' '.$langs->trans("BrowserIsKO",$conf->browser->name);
}
else
{
	print img_picto('','tick.png').' '.$langs->trans("BrowserIsOK",$conf->browser->name);
=======
	print img_picto('', 'warning.png').' '.$langs->trans("BrowserIsKO", $conf->browser->name);
}
else
{
	print img_picto('', 'tick.png').' '.$langs->trans("BrowserIsOK", $conf->browser->name);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
print '<br>';

// Database statistics update
/*
print '<br>';
print '<strong>'.$langs->trans("DatabaseStatistics").'</strong>: ';
print '<br>';
*/

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
