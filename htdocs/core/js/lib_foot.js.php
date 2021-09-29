<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/js/lib_foot.js.php
 * \brief      File that include javascript functions (included if option use_javascript activated)
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

require_once '../../main.inc.php';


/*
 * View
 */

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

//var_dump($conf);


// Wrapper to show tooltips (html or onclick popup)
print "\n/* JS CODE TO ENABLE Tooltips on all object with class classfortooltip */\n";
print "jQuery(document).ready(function () {\n";

if (empty($conf->dol_no_mouse_hover)) {
	print 'jQuery(".classfortooltip").tooltip({
		show: { collision: "flipfit", effect:"toggle", delay:50, duration: 20 },
		hide: { delay: 250, duration: 20 },
		tooltipClass: "mytooltip",
		content: function () {
    		console.log("Return title for popup");
            return $(this).prop("title");		/* To force to get title as is */
   		}
	});'."\n";
}

print '
jQuery(".classfortooltiponclicktext").dialog({
    closeOnEscape: true, classes: { "ui-dialog": "highlight" },
    maxHeight: window.innerHeight-60, width: '.($conf->browser->layout == 'phone' ? max($_SESSION['dol_screenwidth'] - 20, 320) : 700).',
    modal: true,
    autoOpen: false
    }).css("z-index: 5000");
jQuery(".classfortooltiponclick").click(function () {
    console.log("We click on tooltip for element with dolid="+$(this).attr(\'dolid\'));
    if ($(this).attr(\'dolid\')) {
        obj=$("#idfortooltiponclick_"+$(this).attr(\'dolid\'));		/* obj is a div component */
        obj.dialog("open");
        return false;
    }
});'."\n";

print "});\n";


// Wrapper to manage dropdown
if (!defined('JS_JQUERY_DISABLE_DROPDOWN')) {
	print "\n/* JS CODE TO ENABLE dropdown (hamburger, linkto, ...) */\n";
	print '
              jQuery(document).ready(function () {
				  var lastopendropdown = null;

                  // Click onto the link "link to" or "hamburger", toggle dropdown
				  $(document).on(\'click\', \'.dropdown dt a\', function () {
                  	  console.log("toggle dropdown dt a");

                      //$(this).parent().parent().find(\'dd ul\').slideToggle(\'fast\');
					  $(this).parent().parent().find(\'dd ul\').toggleClass("open");

					  if ($(this).parent().parent().find(\'dd ul\').hasClass("open")) {
					  	  lastopendropdown = $(this).parent().parent().find(\'dd ul\');
					  	  //console.log(lastopendropdown);
					  } else {
						  // We closed the dropdown for hamburger selectfields
						  if ($("input:hidden[name=formfilteraction]").val() == "listafterchangingselectedfields") {
							  console.log("resubmit the form saved into lastopendropdown after clicking on hamburger");
							  //$(".dropdown dt a").parents(\'form:first\').submit();
							  //$(".dropdown dt a").closest("form").submit();
							  lastopendropdown.closest("form").submit();
					      }
					  }

                      // Note: Did not find a way to get exact height (value is update at exit) so i calculate a generic from nb of lines
                      heigthofcontent = 21 * $(this).parent().parent().find(\'dd div ul li\').length;
                      if (heigthofcontent > 300) heigthofcontent = 300; // limited by max-height on css .dropdown dd ul
                      posbottom = $(this).parent().parent().find(\'dd\').offset().top + heigthofcontent + 8;
                      var scrollBottom = $(window).scrollTop() + $(window).height();
                      diffoutsidebottom = (posbottom - scrollBottom);
                      console.log("heigthofcontent="+heigthofcontent+", diffoutsidebottom (posbottom="+posbottom+" - scrollBottom="+scrollBottom+") = "+diffoutsidebottom);
                      if (diffoutsidebottom > 0)
                      {
                            pix = "-"+(diffoutsidebottom+8)+"px";
                            console.log("We reposition top by "+pix);
                            $(this).parent().parent().find(\'dd\').css("top", pix);
                      }
                  });

                  // Click on a link into the popup "link to" or other dropdown that ask to close drop down on element click, so close dropdown
                  $(".dropdowncloseonclick").on(\'click\', function () {
                      console.log("Link has class dropdowncloseonclick, so we close/hide the popup ul");
                      //$(this).parent().parent().hide();		// $(this).parent().parent() is ul
					  $(this).parent().parent().removeClass("open");	// $(this).parent().parent() is ul
                  });

				  // Click outside of any dropdown
                  $(document).bind(\'click\', function (e) {
                      var $clicked = $(e.target);	// This is element we click on
                      if (!$clicked.parents().hasClass("dropdown")) {
                          //console.log("close dropdown dd ul - we click outside");
						  //$(".dropdown dd ul").hide();
						  $(".dropdown dd ul").removeClass("open");

						  if ($("input:hidden[name=formfilteraction]").val() == "listafterchangingselectedfields") {
							  console.log("resubmit form saved into lastopendropdown after clicking outside of dropdown and having change selectlist from selectlist field of hamburger dropdown");
							  //$(".dropdown dt a").parents(\'form:first\').submit();
							  //$(".dropdown dt a").closest("form").submit();
							  lastopendropdown.closest("form").submit();
					      }
					  }
                  });
              });
           ';
}

// Wrapper to manage document_preview
if ($conf->browser->layout != 'phone') {
	print "\n/* JS CODE TO ENABLE document_preview */\n"; // Function document_preview is into header
	print '
                jQuery(document).ready(function () {
			        jQuery(".documentpreview").click(function () {
            		    console.log("We click on preview for element with href="+$(this).attr(\'href\')+" mime="+$(this).attr(\'mime\'));
            		    document_preview($(this).attr(\'href\'), $(this).attr(\'mime\'), \''.dol_escape_js($langs->transnoentities("Preview")).'\');
                		return false;
        			});
        		});
           ' . "\n";
}

// Code to manage reposition
print "\n/* JS CODE TO ENABLE reposition management (does not work if a redirect is done after action of submission) */\n";
print '
			jQuery(document).ready(function() {
				/* If page_y set, we set scollbar with it */
				page_y=getParameterByName(\'page_y\', 0);				/* search in GET parameter */
				if (page_y == 0) page_y = jQuery("#page_y").text();		/* search in POST parameter that is filed at bottom of page */
				if (page_y > 0)
				{
					console.log("page_y found is "+page_y);
					$(\'html, body\').scrollTop(page_y);
				}

				/* Set handler to add page_y param on output (click on href links or submit button) */
				jQuery(".reposition").click(function() {
					var page_y = $(document).scrollTop();

					if (page_y > 0)
					{
						if (this.href)
						{
							console.log("We click on tag with .reposition class. this.ref was "+this.href);
							var hrefarray = this.href.split("#", 2);
							hrefarray[0]=hrefarray[0].replace(/&page_y=(\d+)/, \'\');		/* remove page_y param if already present */
							this.href=hrefarray[0]+\'&page_y=\'+page_y;
							console.log("We click on tag with .reposition class. this.ref is now "+this.href);
						}
						else
						{
							console.log("We click on tag with .reposition class but element is not an <a> html tag, so we try to update input form field with name=page_y with value "+page_y);
							jQuery("input[type=hidden][name=page_y]").val(page_y);
						}
					}
				});
			});'."\n";

print "\n/* JS CODE TO ENABLE ClipBoard copy paste*/\n";
print 'jQuery(\'.clipboardCPShowOnHover\').hover(
			function() {
				console.log("We hover a value with a copy paste feature");
				$(this).children(".clipboardCPButton, .clipboardCPText").show();
			},
			function() {
				console.log("We hover out the value with a copy paste feature");
				$(this).children(".clipboardCPButton, .clipboardCPText").hide();
			}
		);';
print 'jQuery(\'.clipboardCPButton, .clipboardCPValueToPrint\').click(function() {
		/* console.log(this.parentNode); */
		console.log("We click on a clipboardCPButton or clipboardCPValueToPrint class");
		if (window.getSelection) {
			selection = window.getSelection();

			range = document.createRange();
			range.selectNodeContents(this.parentNode.firstChild);

			selection.removeAllRanges();
			selection.addRange( range );
		}
		document.execCommand( \'copy\' );
		window.getSelection().removeAllRanges();

		/* Show message */
		var lastchild = this.parentNode.lastChild;
		var tmp = lastchild.innerHTML
		lastchild.innerHTML = \''.dol_escape_js($langs->trans('CopiedToClipboard')).'\';
		setTimeout(() => { lastchild.innerHTML = tmp; }, 1000);
	})'."\n";
