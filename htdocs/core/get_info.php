<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/get_info.php
 *       \brief      File to return a single page with just logged user info, to be used by other frontend
 */

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU',1);

require_once '../main.inc.php';

if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09'));	// If language was forced on URL by the main.inc.php

$langs->load("main");

$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');


/*
 * View
 */

$title=$langs->trans("Info");

// URL http://mydolibarr/core/search_page?dol_use_jmobile=1 can be used for tests
$head='<!-- Quick access -->'."\n";
$arrayofjs=array();
$arrayofcss=array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);



print '<body>'."\n";
print '<div style="padding: 20px;">';
//print '<br>';

$nbofsearch=0;

// Define link to login card
$appli=constant('DOL_APPLICATION_TITLE');
if (! empty($conf->global->MAIN_APPLICATION_TITLE))
{
	$appli=$conf->global->MAIN_APPLICATION_TITLE;
	if (preg_match('/\d\.\d/', $appli))
	{
		if (! preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli.=" (".DOL_VERSION.")";	// If new title contains a version that is different than core
	}
	else $appli.=" ".DOL_VERSION;
}
else $appli.=" ".DOL_VERSION;

if (! empty($conf->global->MAIN_FEATURES_LEVEL)) $appli.="<br>".$langs->trans("LevelOfFeature").': '.$conf->global->MAIN_FEATURES_LEVEL;

$logouttext='';
if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
{
	//$logouthtmltext=$appli.'<br>';
	if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http')
	{
		$logouthtmltext.=$langs->trans("Logout").'<br>';

		$logouttext .='<a href="'.DOL_URL_ROOT.'/user/logout.php">';
		//$logouttext .= img_picto($langs->trans('Logout').":".$langs->trans('Logout'), 'logout_top.png', 'class="login"', 0, 0, 1);
		$logouttext .='<span class="fa fa-sign-out atoplogin"></span>';
		$logouttext .='</a>';
	}
	else
	{
		$logouthtmltext.=$langs->trans("NoLogoutProcessWithAuthMode",$_SESSION["dol_authmode"]);
		$logouttext .= img_picto($langs->trans('Logout').":".$langs->trans('Logout'), 'logout_top.png', 'class="login"', 0, 0, 1);
	}
}

print '<div class="login_block_getinfo">'."\n";

// Add login user link
$toprightmenu.='<div class="login_block_user">';

// Login name with photo and tooltip
$mode=-1;
$toprightmenu.='<div class="inline-block nowrap"><div class="inline-block login_block_elem login_block_elem_name" style="padding: 0px;">';
$toprightmenu.=$user->getNomUrl($mode, '', -1, 0, 11, 0, ($user->firstname ? 'firstname' : -1),'atoplogin');
$toprightmenu.='</div></div>';

$toprightmenu.='</div>'."\n";

$toprightmenu.='<div class="login_block_other">';

// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
$parameters=array();
$result=$hookmanager->executeHooks('printTopRightMenu',$parameters);    // Note that $action and $object may have been modified by some hooks
if (is_numeric($result))
{
	if (empty($result)) $toprightmenu.=$hookmanager->resPrint;		// add
	else  $toprightmenu=$hookmanager->resPrint;						// replace
}
else $toprightmenu.=$result;	// For backward compatibility

// Link to module builder
if (! empty($conf->modulebuilder->enabled))
{
	$text ='<a href="'.DOL_URL_ROOT.'/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="_modulebuilder">';
	//$text.= img_picto(":".$langs->trans("ModuleBuilder"), 'printer_top.png', 'class="printer"');
	$text.='<span class="fa fa-bug atoplogin"></span>';
	$text.='</a>';
	$toprightmenu.=@Form::textwithtooltip('',$langs->trans("ModuleBuilder"),2,1,$text,'login_block_elem',2);
}

// Link to print main content area
/*
if (empty($conf->global->MAIN_PRINT_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && empty($conf->browser->phone))
{
	$qs=dol_escape_htmltag($_SERVER["QUERY_STRING"]);

	if (is_array($_POST))
	{
		foreach($_POST as $key=>$value) {
			if ($key!=='action' && $key!=='password' && !is_array($value)) $qs.='&'.$key.'='.urlencode($value);
		}
	}
	$qs.=(($qs && $morequerystring)?'&':'').$morequerystring;
	$text ='<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.$qs.($qs?'&':'').'optioncss=print" target="_blank">';
	//$text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
	$text.='<span class="fa fa-print atoplogin"></span>';
	$text.='</a>';
	$toprightmenu.=@Form::textwithtooltip('',$langs->trans("PrintContentArea"),2,1,$text,'login_block_elem',2);
}
*/

// Link to Dolibarr wiki pages
/*
if (empty($conf->global->MAIN_HELP_DISABLELINK) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
{
	$langs->load("help");

	$helpbaseurl='';
	$helppage='';
	$mode='';

	if (empty($helppagename)) $helppagename='EN:User_documentation|FR:Documentation_utilisateur|ES:Documentación_usuarios';

	// Get helpbaseurl, helppage and mode from helppagename and langs
	$arrayres=getHelpParamFor($helppagename,$langs);
	$helpbaseurl=$arrayres['helpbaseurl'];
	$helppage=$arrayres['helppage'];
	$mode=$arrayres['mode'];

	// Link to help pages
	if ($helpbaseurl && $helppage)
	{
		$text='';
		$title=$appli.'<br>';
		$title.=$langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage');
		if ($mode == 'wiki') $title.=' - '.$langs->trans("PageWiki").' &quot;'.dol_escape_htmltag(strtr($helppage,'_',' ')).'&quot;';
		$text.='<a class="help" target="_blank" rel="noopener" href="';
		if ($mode == 'wiki') $text.=sprintf($helpbaseurl,urlencode(html_entity_decode($helppage)));
		else $text.=sprintf($helpbaseurl,$helppage);
		$text.='">';
		//$text.=img_picto('', 'helpdoc_top').' ';
		$text.='<span class="fa fa-question-circle atoplogin"></span>';
		//$toprightmenu.=$langs->trans($mode == 'wiki' ? 'OnlineHelp': 'Help');
		//if ($mode == 'wiki') $text.=' ('.dol_trunc(strtr($helppage,'_',' '),8).')';
		$text.='</a>';
		//$toprightmenu.='</div>'."\n";
		$toprightmenu.=@Form::textwithtooltip('',$title,2,1,$text,'login_block_elem',2);
	}
}
*/

// Logout link
if (GETPOST('withlogout','int')) $toprightmenu.=@Form::textwithtooltip('',$logouthtmltext,2,1,$logouttext,'login_block_elem',2);

$toprightmenu.='</div>';

print $toprightmenu;

print "</div>\n";		// end div class="login_block"

print '</div>';
print '</body></html>'."\n";

$db->close();
