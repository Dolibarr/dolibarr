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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');


/**
 * \file    dropdownmenu/js/dropdownmenu.js.php
 * \ingroup dropdownmenu
 * \brief   JavaScript file for module Menu.
 */

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

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


?>

	(function ($) {

		//Show spinner
		$(document).on("click", 'a', function(event) {

			if(!$(this).hasClass('documentdownload') 
				&& $(this).attr('target') != '_blank'
				&& $(this).attr('href') != '#'
				&& $(this).attr('rel') != 'modal:open'
				&& $(this).attr('rel') != 'modal:close') { 
				$('#a_link_spinner').show();
			}
		});

		$(document).on("submit", 'form', function(event) {
			$('#a_link_spinner').show();
		});

		$(document).on("click", 'button.ui-button', function(event) {
			if($(this).text() == 'Oui'){
				$('#a_link_spinner').show();
			}
		});

		$.fn.dropDownResponsiveMenu = function (options) {
						
			//plugin's default options
			var defaults = {
				resizeWidth: '768',
				animationSpeed: 'fast',
				accoridonExpAll: false
			};

			//Variables
			var options = $.extend(defaults, options),
				opt = options,
				$resizeWidth = opt.resizeWidth,
				$animationSpeed = opt.animationSpeed,
				$expandAll = opt.accoridonExpAll,
				$ddownMenu = $(this),
				$menuStyle = $(this).attr('data-menu-style');

			// Initilizing        
			$ddownMenu.find('ul').addClass("sub-menu");
			$ddownMenu.find('.ul_submenu').siblings('a').append('<span class="arrow_ddown"></span>');
			if ($menuStyle == 'accordion') { $(this).addClass('collapse'); }

			// Window resize on menu breakpoint 
			if ($(window).innerWidth() <= $resizeWidth) {
				menuCollapse();
			}
			$(window).resize(function () {
				menuCollapse();
			});

			// Menu Toggle
			function menuCollapse() {
				var w = $(window).innerWidth();
				if (w <= $resizeWidth) {
					$ddownMenu.find('li.menu-active').removeClass('menu-active');
					$ddownMenu.find('ul.slide').removeClass('slide').removeAttr('style');
					$ddownMenu.addClass('collapse hide-menu');
					$ddownMenu.attr('data-menu-style', '');
					$('.menu-toggle').show();
				} else {
					$ddownMenu.attr('data-menu-style', $menuStyle);
					$ddownMenu.removeClass('collapse hide-menu').removeAttr('style');
					$('.menu-toggle').hide();
					if ($ddownMenu.attr('data-menu-style') == 'accordion') {
						$ddownMenu.addClass('collapse');
						return;
					}
					$ddownMenu.find('li.menu-active').removeClass('menu-active');
					$ddownMenu.find('ul.slide').removeClass('slide').removeAttr('style');
				}
			}

			//ToggleBtn Click
			$('#menu-btn').click(function () {
				$ddownMenu.slideToggle().toggleClass('hide-menu');
			});

			// Main function 
			return this.each(function () {
				
				var timeoutID;
				
				// Function for Horizontal menu on mouseenter
				$ddownMenu.on('mouseover', '> li a', function () {
					if ($ddownMenu.hasClass('collapse') === true) {
						clearTimeout(timeoutID);
						return false;
					}
				   
				   clearTimeout(timeoutID);
				   
					$(this).parent('li').siblings().children('.sub-menu').stop(true, true).slideUp($animationSpeed).removeClass('slide').removeAttr('style').stop();
					$(this).parent().addClass('menu-active').children('.sub-menu').slideDown($animationSpeed).addClass('slide');
					return;
				});
				
				$ddownMenu.on('mouseleave', 'li', function () {
					if ($ddownMenu.hasClass('collapse') === true) {
						clearTimeout(timeoutID);
						return false;
					}
					$(this).off('click', '> li a');
					$(this).removeClass('menu-active');
					
					var mainObject = $(this);
					clearTimeout(timeoutID);
					
					timeoutID = setTimeout(function() {
						mainObject.children('ul.sub-menu').stop(true, true).slideUp($animationSpeed).removeClass('slide').removeAttr('style');
						
						return;
					}, 400);
					
				});
				//End of Horizontal menu function
				
				
				$ddownMenu.on('click', 'li a', function () {
					if ($ddownMenu.hasClass('collapse') === true && !$(this).parent().hasClass('menu-active')) {
						return false;
					}
				});				

				//Allow use of touchscreen on computer
				//Detect touchtap
				$(document).on('touchstart', function() {
					detectTap = true; // Detects all touch events
				});
				$(document).on('touchmove', function() {
					detectTap = false; // Excludes the scroll events from touch events
				});
				$ddownMenu.on('touchend', '> li a', function () {

					if (detectTap && !$(this).parent().hasClass('menu-active')) {
						
						$('li').removeClass('menu-active');
						
						$(this).parent('li').siblings().children('.sub-menu').stop(true, true).slideUp($animationSpeed).removeClass('slide').removeAttr('style').stop();
						$(this).parent().addClass('menu-active').children('.sub-menu').slideDown($animationSpeed).addClass('slide');
						return false;
					}

				});
				

				// Function for Vertical/Responsive Menu on mouse click
				$ddownMenu.on('click', '> li a', function () {
					
					if ($ddownMenu.hasClass('collapse') === false) {
						//return false;
					}
					
					$(this).off('mouseover', '> li a');
					if ($(this).parent().hasClass('menu-active')) {
						$(this).parent().children('.sub-menu').slideUp().removeClass('slide');
						$(this).parent().removeClass('menu-active');
					} else {
						if ($expandAll == true) {
							$(this).parent().addClass('menu-active').children('.sub-menu').slideDown($animationSpeed).addClass('slide');
							return;
						}
						$(this).parent().siblings().removeClass('menu-active');
						$(this).parent('li').siblings().children('.sub-menu').slideUp().removeClass('slide');
						$(this).parent().addClass('menu-active').children('.sub-menu').slideDown($animationSpeed).addClass('slide');
					}
				});
				//End of responsive menu function

			});
			//End of Main function
		}

	})(jQuery);

