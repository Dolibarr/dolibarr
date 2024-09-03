<?php
/* Copyright (C) 2013-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *  \file       htdocs/admin/system/perf.php
 *  \brief      Page to show Performance information
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin", "products"));

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('action', 'aZ09') == 'donothing') {
	exit;
}


/*
 * View
 */

$form = new Form($db);
$nowstring = dol_print_date(dol_now(), 'dayhourlog');

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_perf');

print load_fiche_titre($langs->trans("PerfDolibarr"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("YouMayFindPerfAdviceHere", 'https://wiki.dolibarr.org/index.php/FAQ_Increase_Performance').'</span>';
print ' &nbsp; &nbsp; ';
print '<a href="'.$_SERVER["PHP_SELF"].'">';
print img_picto($langs->trans("Reload"), 'refresh').' ';
print $langs->trans("Reload");
print '</a>';
print '<br>';
print '<br>';

// Recupere la version de PHP
$phpversion = version_php();
print "<br><strong>PHP</strong> - ".$langs->trans("Version").": ".$phpversion."\n";

// Recupere la version du serveur web
print "<br><strong>Web server</strong> - ".$langs->trans("Version").": ".$_SERVER["SERVER_SOFTWARE"]."<br>\n";

print '<hr>';

print "<br>\n";

// XDebug
print '<br>';
print '<strong>'.$langs->trans("XDebug").'</strong><br>';
print '<div class="divsection">';
$test = !function_exists('xdebug_is_enabled');
if ($test) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NotInstalled").'  <span class="opacitymedium">'.$langs->trans("NotSlowedDownByThis").'</span>';
} else {
	print img_picto('', 'warning', 'class="pictofixedwidth"').' '.$langs->trans("ModuleActivated", $langs->transnoentities("XDebug"));
	print ' - '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php">XDebug admin page</a>';
}
print '<br>';
print '</div>';

// Module log
print '<br>';
print '<strong>'.$langs->trans("Syslog").'</strong><br>';
print '<div class="divsection">';
$test = !isModEnabled('syslog');
if ($test) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NotInstalled").'  <span class="opacitymedium">'.$langs->trans("NotSlowedDownByThis").'</span>';
} else {
	if (getDolGlobalInt('SYSLOG_LEVEL') > LOG_NOTICE) {
		print img_picto('', 'warning', 'class="pictofixedwidth"').' '.$langs->trans("ModuleActivatedWithTooHighLogLevel", $langs->transnoentities("Syslog"));
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("ModuleSyslogActivatedButLevelNotTooVerbose", $langs->transnoentities("Syslog"), getDolGlobalInt('SYSLOG_LEVEL'));
	}
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';
print '</div>';

// Module debugbar
print '<br>';
print '<strong>'.$langs->trans("DebugBar").'</strong><br>';
print '<div class="divsection">';
$test = !isModEnabled('debugbar');
if ($test) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NotInstalled").' <span class="opacitymedium">'.$langs->trans("NotSlowedDownByThis").'</span>';
} else {
	print img_picto('', 'warning', 'class="pictofixedwidth"').' '.$langs->trans("ModuleActivated", $langs->transnoentities("DebugBar"));
	//print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xdebug.php'.'">XDebug admin page</a>';
}
print '<br>';
print '</div>';

// Applicative cache
print '<br>';
print '<strong>'.$langs->trans("ApplicativeCache").'</strong><br>';
print '<div class="divsection">';
$test = isModEnabled('memcached');
if ($test) {
	if (getDolGlobalString('MEMCACHED_SERVER')) {
		print $langs->trans("MemcachedAvailableAndSetup");
		print ' '.$langs->trans("MoreInformation").' <a href="'.dol_buildpath('/memcached/admin/memcached.php', 1).'">Memcached module admin page</a>';
	} else {
		print $langs->trans("MemcachedModuleAvailableButNotSetup");
		print ' <a href="'.dol_buildpath('/memcached/admin/memcached.php', 1).'">Memcached module admin page</a>';
	}
} else {
	print $langs->trans("MemcachedNotAvailable");
}
print '</br>';
print '</div>';

// OPCode cache
print '<br>';
print '<strong>'.$langs->trans("OPCodeCache").'</strong><br>';
print '<div class="divsection">';
$foundcache = 0;
$test = function_exists('xcache_info');
if (!$foundcache && $test) {
	$foundcache++;
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("PHPModuleLoaded", "XCache");
	print ' '.$langs->trans("MoreInformation").' <a href="'.DOL_URL_ROOT.'/admin/system/xcache.php">Xcache admin page</a>';
}
$test = function_exists('eaccelerator_info');
if (!$foundcache && $test) {
	$foundcache++;
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("PHPModuleLoaded", "Eaccelerator");
}
$test = function_exists('opcache_get_status');
if (!$foundcache && $test) {
	$foundcache++;
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("PHPModuleLoaded", "ZendOPCache"); // Should be by default starting with PHP 5.5
	//$tmp=opcache_get_status();
	//var_dump($tmp);
}
$test = function_exists('apc_cache_info');
if (!$foundcache && $test) {
	//var_dump(apc_cache_info());
	if (ini_get('apc.enabled')) {
		$foundcache++;
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("APCInstalled");
	} else {
		print img_picto('', 'warning', 'class="pictofixedwidth"').' '.$langs->trans("APCCacheInstalledButDisabled");
	}
}
if (!$foundcache) {
	print $langs->trans("NoOPCodeCacheFound");
}
print '<br>';
print '</div>';

// Use of preload bootstrap
print '<br>';
print '<strong>'.$langs->trans("PreloadOPCode").'</strong><br>';
print '<div class="divsection">';
if (ini_get('opcache.preload')) {
	print ini_get('opcache.preload');
} else {
	print img_picto('', 'minus', 'class="pictofixedwidth"').' '.$langs->trans("No");
}
print '<br>';
print '</div>';

// HTTPCacheStaticResources
print '<script type="text/javascript">
jQuery(document).ready(function() {
  var getphpurl;
  var cachephpstring;
  var compphpstring;
  getphpurl = $.ajax({
    type: "GET",
	data: { token: \''.currentToken().'\' },
    url: \''.DOL_URL_ROOT.'/public/notice.php\',
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
      	if (compphpstring == null || (compphpstring.indexOf("gzip") == -1 && compphpstring.indexOf("deflate") == -1  && compphpstring.indexOf("br") == -1))
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
	data: { token: \'notrequired\' },
    url: \''.DOL_URL_ROOT.'/includes/jquery/css/base/jquery-ui.css\',
    cache: false,
    /* async: false, */
    /* crossDomain: true, */
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
      	if (compcssstring == null || (compcssstring.indexOf("gzip") == -1 && compcssstring.indexOf("deflate") == -1 && compcssstring.indexOf("br") == -1))
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
	data: { token: \''.currentToken().'\' },
    url: \''.DOL_URL_ROOT.'/theme/eldy/style.css.php\',
    cache: false,
    /* async: false, */
    /* crossDomain: true,*/
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
      	if (compcssphpstring == null || (compcssphpstring.indexOf("gzip") == -1 && compcssphpstring.indexOf("deflate") == -1 && compcssphpstring.indexOf("br") == -1))
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
	data: { token: \'notrequired\' },
    url: \''.DOL_URL_ROOT.'/theme/eldy/img/help.png\',
    cache: false,
    /* async: false, */
    /* crossDomain: true,*/
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
      	if (compimgstring == null || (compimgstring.indexOf("gzip") == -1 && compimgstring.indexOf("deflate") == -1 && compimgstring.indexOf("br") == -1))
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
	data: { token: \'notrequired\' },
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
      	if (compjsstring == null || (compjsstring.indexOf("gzip") == -1 && compjsstring.indexOf("deflate") == -1 && compjsstring.indexOf("br") == -1))
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
	data: { token: \''.currentToken().'\' },
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
      	if (compjsphpstring == null || (compjsphpstring.indexOf("gzip") == -1 && compjsphpstring.indexOf("deflate") == -1 && compjsphpstring.indexOf("br") == -1))
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
print '</strong><br>';
print '<div class="divsection">';
// No cache on PHP
//print '<div id="httpcachephpok">'.img_picto('','warning.png').' '.$langs->trans("FilesOfTypeNotCompressed",'php (.php)').'</div>';
//print '<div id="httpcachephpko">'.img_picto('','tick.png').' '.$langs->trans("FilesOfTypeNotCached",'php (.php)').'</div>';
// Cache on rest
print '<div id="httpcachecssok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCached", 'css (.css)').'</div>';
print '<div id="httpcachecssko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCached", 'css (.css)').'</div>';
print '<div id="httpcachecssphpok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCached", 'css (.css.php)').'</div>';
print '<div id="httpcachecssphpko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCached", 'css (.css.php)').'</div>';
print '<div id="httpcacheimgok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCached", 'img (.png)').'</div>';
print '<div id="httpcacheimgko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCached", 'img (.png)').'</div>';
print '<div id="httpcachejsok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCached", 'javascript (.js)').'</div>';
print '<div id="httpcachejsko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCached", 'javascript (.js)').'</div>';
print '<div id="httpcachejsphpok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCached", 'javascript (.js.php)').'</div>';
print '<div id="httpcachejsphpko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCached", 'javascript (.js.php)').'</div>';
print '</div>';
print '<br>';
print '<strong>'.$langs->trans("HTTPCacheStaticResources").' - '.$langs->trans("CacheByClient").'</strong><br>';
print '<div class="divsection">';
print '<div id="httpcachebybrowser">'.img_picto('', 'question.png', 'class="pictofixedwidth"').' '.$langs->trans("TestNotPossibleWithCurrentBrowsers").'</div>';
print '</div>';

// Compressions
print '<br>';
print '<strong>';
print $form->textwithpicto($langs->trans("CompressionOfResources"), $langs->trans("CompressionOfResourcesDesc"));
print '</strong>';
print '<br>';
print '<div class="divsection">';
// on PHP
print '<div id="httpcompphpok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed", 'php (.php)').'</div>';
print '<div id="httpcompphpko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed", 'php (.php)').'</div>';
// on rest
print '<div id="httpcompcssok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed", 'css (.css)').'</div>';
print '<div id="httpcompcssko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed", 'css (.css)').'</div>';
print '<div id="httpcompcssphpok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed", 'css (.css.php)').'</div>';
print '<div id="httpcompcssphpko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed", 'css (.css.php)').'</div>';
//print '<div id="httpcompimgok">'.img_picto('','tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed",'img (.png)').'</div>';
//print '<div id="httpcompimgko">'.img_picto('','warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed",'img (.png)').'</div>';
print '<div id="httpcompjsok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed", 'javascript (.js)').'</div>';
print '<div id="httpcompjsko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed", 'javascript (.js)').'</div>';
print '<div id="httpcompjsphpok">'.img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeCompressed", 'javascript (.js.php)').'</div>';
print '<div id="httpcompjsphpko">'.img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("FilesOfTypeNotCompressed", 'javascript (.js.php)').'</div>';
print '</div>';

// Database driver
print '<br>';
print '<strong>'.$langs->trans("DriverType").'</strong>';
print '<br>';
print '<div class="divsection">';
if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli') {
	$test = ($conf->db->type == 'mysqli');
	if ($test) {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouUseBestDriver", $conf->db->type);
	} else {
		print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouDoNotUseBestDriver", $conf->db->type, 'mysqli');
	}
	print '<br>';
}
print '</div>';

print '<br>';
print '<strong>'.$langs->trans("ComboListOptim").'</strong>';
print '<br>';
print '<div class="divsection">';
// Product combo list
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 5000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('PRODUIT_USE_SEARCH_TO_SELECT')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseComboOptim", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"), 'PRODUIT_USE_SEARCH_TO_SELECT');
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"), 'PRODUIT_USE_SEARCH_TO_SELECT', getDolGlobalString('PRODUIT_USE_SEARCH_TO_SELECT'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"));
	}
	print '<br>';
	$db->free($resql);
}
// Thirdparty combo list
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 5000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseComboOptim", $nb, $langs->transnoentitiesnoconv("ThirdParties"), 'COMPANY_USE_SEARCH_TO_SELECT');
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("ThirdParties"), 'COMPANY_USE_SEARCH_TO_SELECT', getDolGlobalString('COMPANY_USE_SEARCH_TO_SELECT'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("ThirdParties"));
	}
	print '<br>';
	$db->free($resql);
}
// Contact combo list
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as s";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 5000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseComboOptim", $nb, $langs->transnoentitiesnoconv("Contacts"), 'CONTACT_USE_SEARCH_TO_SELECT');
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("Contacts"), 'CONTACT_USE_SEARCH_TO_SELECT', getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("Contacts"));
	}
	print '<br>';
	$db->free($resql);
}
// Contact combo list
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as s";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 5000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('PROJECT_USE_SEARCH_TO_SELECT')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseComboOptim", $nb, $langs->transnoentitiesnoconv("Projects"), 'PROJECT_USE_SEARCH_TO_SELECT');
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("Projects"), 'PROJECT_USE_SEARCH_TO_SELECT', getDolGlobalString('PROJECT_USE_SEARCH_TO_SELECT'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("Projects"));
	}
	print '<br>';
	$db->free($resql);
}
print '</div>';

print '<br>';
print '<strong>'.$langs->trans("SearchOptim").'</strong>';
print '<br>';
print '<div class="divsection">';
// Product search
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 100000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('PRODUCT_DONOTSEARCH_ANYWHERE')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseSearchOptim", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"), 'PRODUCT_DONOTSEARCH_ANYWHERE');
			print $langs->trans("YouHaveXObjectUseSearchOptimDesc");
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"), 'PRODUCT_DONOTSEARCH_ANYWHERE', getDolGlobalString('PRODUCT_DONOTSEARCH_ANYWHERE'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("ProductsOrServices"));
	}
	print '<br>';
	$db->free($resql);
}

// Thirdparty search
$sql = "SELECT COUNT(*) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$resql = $db->query($sql);
if ($resql) {
	$limitforoptim = 100000;
	$num = $db->num_rows($resql);
	$obj = $db->fetch_object($resql);
	$nb = $obj->nb;
	if ($nb > $limitforoptim) {
		if (!getDolGlobalString('COMPANY_DONOTSEARCH_ANYWHERE')) {
			print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectUseSearchOptim", $nb, $langs->transnoentitiesnoconv("ThirdParties"), 'COMPANY_DONOTSEARCH_ANYWHERE');
			print $langs->trans("YouHaveXObjectUseSearchOptimDesc");
		} else {
			print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("YouHaveXObjectAndSearchOptimOn", $nb, $langs->transnoentitiesnoconv("ThirdParties"), 'COMPANY_DONOTSEARCH_ANYWHERE', getDolGlobalString('COMPANY_DONOTSEARCH_ANYWHERE'));
		}
	} else {
		print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("NbOfObjectIsLowerThanNoPb", $nb, $langs->transnoentitiesnoconv("ThirdParties"));
	}
	print '<br>';
	$db->free($resql);
}
print '</div>';

// Browser
print '<br>';
print '<strong>'.$langs->trans("Browser").'</strong><br>';
print '<div class="divsection">';
if (!in_array($conf->browser->name, array('chrome', 'opera', 'safari', 'firefox'))) {
	print img_picto('', 'warning.png', 'class="pictofixedwidth"').' '.$langs->trans("BrowserIsKO", $conf->browser->name);
} else {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"').' '.$langs->trans("BrowserIsOK", $conf->browser->name);
}
print '<br>';
print '</div>';

// Options
print '<br>';
print '<strong>'.$langs->trans("Options").'</strong><br>';
print '<div class="divsection">';
if (getDolGlobalInt('MAIN_ACTIVATE_FILECACHE')) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"');
} else {
	print img_picto('', 'minus', 'class="pictofixedwidth"');
}
print ' '.$form->textwithpicto($langs->trans("EnableFileCache").' ('.$langs->trans("Widgets").')', $langs->trans("Option").' MAIN_ACTIVATE_FILECACHE');
print ': '.yn(getDolGlobalInt('MAIN_ACTIVATE_FILECACHE'));
print '<br>';

if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"');
} else {
	print img_picto('', 'minus', 'class="pictofixedwidth"');
}
print ' MAIN_ENABLE_AJAX_TOOLTIP : ';
print yn(getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP'));
print '<br>';


if (getDolGlobalInt('MAIN_CACHE_COUNT')) {
	print img_picto('', 'tick.png', 'class="pictofixedwidth"');
} else {
	print img_picto('', 'minus', 'class="pictofixedwidth"');
}
print 'MAIN_CACHE_COUNT : ';
print yn(getDolGlobalInt('MAIN_CACHE_COUNT'));
//.' '.img_picto('', 'warning.png');
print '<br>';

print '</div>';

// End of page
llxFooter();
$db->close();
