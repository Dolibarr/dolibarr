<?php
/* Copyright (C) 2020 Florian Dufourg <florian.dufourg@gnl-solutions.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dropdownmenu/css/dropdownmenu.css.php
 * \ingroup dropdownmenu
 * \brief   CSS file for module Menu.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/../main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

$colorbackhmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);

if(empty($colorbackhmenu1))$colorbackhmenu1 = '55,61,90';


$colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbackhmenu1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) { $colortextbackhmenu = 'FFFFFF'; }
else { $colortextbackhmenu = '000000'; }


if($conf->standard_menu == 'dropdown_responsive_menu.php'){
?>
	
	.side-nav-vert{
		position:static;
	}
	
	ul {
		margin: 0px;
		padding: 0px;
	}
	.dropdown-responsive-menu {
		list-style: none;
		margin: 0;
		/*padding: 0;*/
		width:100%;
	}
	.dropdown-responsive-menu li{
		list-style: none;
	}
	.dropdown-responsive-menu li ul {
		display:none;
	}
	.dropdown-responsive-menu > li {
		display: block;
		/*margin: 0;*/
		/*padding: 0;*/
		/*border: 0px;*/
		padding-bottom: 5px;
		float: left;
	}
	.dropdown-responsive-menu li a {
		color: #<?php print $colortextbackhmenu; ?>;
		font-weight: 600;
		/*padding-bottom: 5px;*/
	}
	.dropdown-responsive-menu > li > a {
		display: block;
		position: relative;
		/*margin: 0;*/
		border: 0px;
		padding: 18px 5px 18px 12px;
		text-decoration: none;
		font-weight: 300;
		color: #<?php print $colortextbackhmenu; ?>;
	}
	.dropdown-responsive-menu li a i {
		padding-right: 5px;
		color: #<?php print $colortextbackhmenu; ?>;
	}
	.dropdown-responsive-menu > li > a i {
		text-shadow: none;
		color: #<?php print $colortextbackhmenu; ?>;
	}
	.dropdown-responsive-menu li ul li a i {
		padding-right: 5px;
	}
	.dropdown-responsive-menu li.menu-active > a {
		color: #<?php print $colortextbackhmenu; ?>;
	}
	.dropdown-responsive-menu li .menu-active {
		position: relative;
		background: orange;
	}

	.dropdown-responsive-menu li ul.sub-menu li > a > .arrow_ddown:before {
		margin-left: 15px;
		display: inline;
		height: auto;
		content: " \276F";
		font-weight: 300;
		text-shadow: none;
		width: 10px;
		display: inline-block;
		
	}
	.dropdown-responsive-menu > li > ul.sub-menu {
		display: none;
		list-style: none;
		clear: both;
		margin: 0;
		position: absolute;
		z-index: 999;
		margin-top:5px;
	}
	.dropdown-responsive-menu li ul.sub-menu {
		background: rgb(<?php print $colorbackhmenu1; ?>);
	}
	.dropdown-responsive-menu li ul.sub-menu > li {
		width: 250px;
	}
	.dropdown-responsive-menu li ul.sub-menu li a {
		display: block;
		text-align: left;
		margin: 0px 0px;
		padding: 12px 10px 12px 15px;
		text-decoration: none;
		background: none;
		width: 230px;
		color: #<?php print $colortextbackhmenu; ?>;
	}
	.dropdown-responsive-menu > li > ul.sub-menu > li {
		position: relative;
	}
	.dropdown-responsive-menu > li > ul.sub-menu > li ul.sub-menu {
		position: absolute;
		left: 250px;
		top: 0px;
		display: none;
		list-style: none;
	}
	.dropdown-responsive-menu > li > ul.sub-menu > li ul.sub-menu > li ul.sub-menu {
		position: absolute;
		left: 250px;
		top: 0px;
		display: none;
		list-style: none;
	}
	.dropdown-responsive-menu > li > ul.sub-menu li > a > .arrow_ddown:before {
		float: right;
		margin-top: 1px;
		margin-right: 0px;
		display: inline;
		height: auto;
		font-weight: 300;
		text-shadow: none;
	}

	/* Menu Toggle Btn
	----------------------------------------*/
	.menu-toggle {
		display: none;
		float: left;
		width: 100%;
		background: #333;
	}
	.menu-toggle h3 {
		float: left;
		color: #FFF;
		padding: 0px 10px;
		font-weight: 600;
		font-size: 16px;
	}
	.menu-toggle .icon-bar {
		display: block !important;
		width: 18px;
		height: 2px;
		background-color: #F5F5F5 !important;
		-webkit-border-radius: 1px;
		-moz-border-radius: 1px;
		border-radius: 1px;
		-webkit-box-shadow: 0 1px 0 rgba(0, 0, 0, 0.25);
		-moz-box-shadow: 0 1px 0 rgba(0, 0, 0, 0.25);
		box-shadow: 0 1px 0 rgba(0, 0, 0, 0.25);
		margin: 3px;
	}
	.menu-toggle .icon-bar:hover {
		background-color: #F5F5F5 !important;
	}
	.menu-toggle #menu-btn {
		float: right;
		background: #202020;
		border: 1px solid #0C0C0C;
		padding: 8px;
		border-radius: 5px;
		cursor: pointer;
		margin: 10px;
	}
	
	#logout-btn {
		float: left;
		background: #202020;
		border: 1px solid #0C0C0C;
		padding: 8px;
		border-radius: 5px;
		cursor: pointer;
		margin: 10px;
	}

	#a_link_spinner {
		color: white;
		float: left;
		padding: 8px;
		border-radius: 5px;
		cursor: pointer;
		margin-top: 10px;
		display: none;
	}
	
	.hide-menu {
		display: none;
	}


	/* Accordion Menu Styles
	----------------------------------------*/

	ul[data-menu-style="accordion"] {
		width: 250px;
	}
	ul[data-menu-style="accordion"] > li {    
		display: block;
		margin: 0;
		padding: 0;
		border: 0px;
		float: none !important;
	}
	ul[data-menu-style="accordion"] > li:first-child {
		border-top: 2px solid #FD5025;
	}
	ul[data-menu-style="accordion"] li ul.sub-menu > li {
		width: 100%;
	}
	ul[data-menu-style="accordion"] > li > a > .arrow:before {
		float: right;
		content: "\f105";
	}
	ul[data-menu-style="accordion"] li.menu-active > a > .arrow:before {
		content: "\f107" !important;
	}
	ul[data-menu-style="accordion"] > li > ul.sub-menu {
		position: static;
	}
	ul[data-menu-style="accordion"] > li > a i {
		padding-right: 10px;
		color: #FF5737;
	}
	ul[data-menu-style="accordion"] > li > ul.sub-menu > li ul.sub-menu {
		position: static;
	}
	ul[data-menu-style="accordion"] > li > ul.sub-menu > li ul.sub-menu > li ul.sub-menu {
		position: static;
	}
	ul[data-menu-style="accordion"] > li {
		border-bottom: 1px solid #242424;
	}
	ul[data-menu-style="accordion"] li a:hover {
		background: #272727 !important;
	}
	ul[data-menu-style="accordion"] ul.sub-menu li.menu-active > a > .arrow:before {
		content: "\f107" !important;
	}

	/* Vertical Menu Styles
	----------------------------------------*/

	ul[data-menu-style="vertical"] {
		width: 250px;
	}
	ul[data-menu-style="vertical"] > li {
		float: none;
	}
	ul[data-menu-style="vertical"] > li:first-child {
		border-top: 2px solid #FD5025;
	}
	ul[data-menu-style="vertical"] li ul.sub-menu > li {
		width: 100%;
	}
	ul[data-menu-style="vertical"] > li > a > .arrow:before {
		float: right;
		content: "\f105";
	}
	ul[data-menu-style="vertical"] > li.menu-active {
	position:relative;
	}
	ul[data-menu-style="vertical"] > li > ul.sub-menu {
		position: absolute;
		left:250px;
		top:0px;
		width:250px;
	}
	ul[data-menu-style="vertical"] > li > a i {
		padding-right: 10px;
		color: #FF5737;
	}
	ul[data-menu-style="vertical"]> li > ul.sub-menu > li ul.sub-menu {
		position: absolute;
		width:250px;
		left: 250px;
	}
	ul[data-menu-style="vertical"] > li > ul.sub-menu > li ul.sub-menu > li ul.sub-menu {
		position: absolute;
		width:250px;
		left: 250px;
	}
	ul[data-menu-style="vertical"] > li {
		border-bottom: 1px solid #242424;
	}
	ul[data-menu-style="vertical"] li a:hover {
		background: #272727 !important;
	}

	/* Responsive Menu Styles
	----------------------------------------*/

	@media screen and (max-width: 768px) {
		
		#dropdownMenu{
			overflow-y: scroll;
			height: 400px;
		}
		
		
		.menu-toggle {
			display: block;
			float: left;
			width: 100%;
			background: #333;
		}
		
		.tmenucompanylogo{
			display: none;
		}
		
		#mainmenutd_companylogo{
			display: none;
		}
		
		.demo {
			width:96%;
			padding:2%;
		}
		ul[data-menu-style="vertical"] , ul[data-menu-style="accordion"],
		ul[data-menu-style="vertical"] li ul.sub-menu {
			width: 100% !important;
		} 
		.dropdown-responsive-menu {
			float: left;
			width:100%;
		}
		.dropdown-responsive-menu > li {
			border-bottom: 1px solid #242424;
		   float: none;
		}   
		.dropdown-responsive-menu li a:hover {
			/*background: #272727 !important;*/
		}
		.dropdown-responsive-menu > li:first-child {
			border-top: 2px solid #FD5025;
		}    
		.dropdown-responsive-menu > li > a i {
			padding-right: 10px;
			color: #FF5737;
		}
		.dropdown-responsive-menu > li > a > .arrow:before {
			float: right;
			 content: " \276F";
			font-weight: 300;
			text-shadow: none;
			width: 10px;
			display: inline-block;
			transform: rotate(90deg);
		}
		li.menu-active > a > .arrow:before {
		content: " \276F";
		font-weight: 300;
		text-shadow: none;
		width: 10px;
		display: inline-block;
		transform: rotate(90deg);
		}
		.dropdown-responsive-menu li ul.sub-menu > li {
			width: 100%;
		}
		.dropdown-responsive-menu li ul.sub-menu li ul.sub-menu li a
			{
			padding-left: 30px;
		}  
		.dropdown-responsive-menu li ul.sub-menu li ul.sub-menu li ul.sub-menu li a 
		   {
			padding-left: 50px;
		}  
		.dropdown-responsive-menu > li > ul.sub-menu {
			position: static;
		}
		.dropdown-responsive-menu > li > ul.sub-menu > li ul.sub-menu {
			position: static;
		}
		.dropdown-responsive-menu > li > ul.sub-menu > li ul.sub-menu > li ul.sub-menu {
			position: static;
		}
		.dropdown-responsive-menu li ul.sub-menu li.menu-active > a > .arrow:before {
			  content: " \276F";
		font-weight: 300;
		text-shadow: none;
		width: 10px;
		display: inline-block;
		transform: rotate(90deg);
		}
	} 
<?php
}
