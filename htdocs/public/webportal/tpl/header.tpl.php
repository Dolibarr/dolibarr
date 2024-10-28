<?php
/* Copyright (C) 2024  Laurent Destailleur <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (!empty($context->title)) {
	$title = $context->title;
} else {
	$title = 'WebPortal';
}

$head = '<link rel="stylesheet" href="'.$context->rootUrl.'css/global.css.php">'."\n";

//$jNotifyCSSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.css', 2);
//$head .= '<link rel="stylesheet" href="'.$jNotifyCSSUrl.' ">'."\n";

if (getDolGlobalString('WEBPORTAL_CUSTOM_CSS')) {
	$head .= '<link rel="stylesheet" type="text/css" href="'.$context->rootUrl.'css/themes/custom.css.php?revision='.getDolGlobalInt('WEBPORTAL_PARAMS_REV').'">'."\n";
}
// JQuery
//$jQueryJSUrl = $context->rootUrl.'includes/jquery/js/jquery.js';
//$jQueryJSUrl = dol_buildpath('/includes/jquery/js/jquery.js', 2);
//$head .= '<script src="'.$jQueryJSUrl.'"></script>'."\n";

// JNotify
//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
//$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.js', 2);
//$head .= '<script src="'.$jNotifyJSUrl.'"></script>'."\n";

top_htmlhead($head, $title);
?>
<body
		data-theme="custom"
		data-controller="<?php print dol_escape_htmltag($context->controller); ?>"
>
