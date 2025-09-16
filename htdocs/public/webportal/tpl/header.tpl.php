<?php
/* Copyright (C) 2024  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * @var Context $context	Object context for webportal
 * @var Translate $langs
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

// Return HTTP headers
top_httphead();

// Return HTML header
?>
<!DOCTYPE html>
<?php print '<html lang="'.substr($langs->defaultlang, 0, 2) . '">'."\n"; ?>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>
		<?php print $title;	?>
	</title>
	<?php

	$jQueryUICSSUrl = dirname($context->rootUrl).'/includes/jquery/css/base/jquery-ui.min.css?layout=classic';
	print '<link rel="stylesheet" href="'.$jQueryUICSSUrl.' ">'."\n";

	//$jNotifyCSSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.css';
	//$jNotifyCSSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.css', 2);
	$jNotifyCSSUrl = dirname($context->rootUrl).'/includes/jquery/plugins/jnotify/jquery.jnotify.min.css?layout=classic';
	print '<link rel="stylesheet" href="'.$jNotifyCSSUrl.' ">'."\n";

	?>
	<link rel="stylesheet" href="<?php print $context->rootUrl.'css/style.css.php'; ?>">
	<?php
	if (getDolGlobalString('WEBPORTAL_CUSTOM_CSS')) {
		print '<link rel="stylesheet" type="text/css" href="'.$context->rootUrl.'css/themes/custom.css.php?revision='.getDolGlobalInt('WEBPORTAL_PARAMS_REV').'">'."\n";
	}
	?>

	<link rel="stylesheet" href="<?php print dirname($context->rootUrl).'/theme/common/fontawesome-5/css/all.min.css?layout=classic'; ?>">
	<?php
	// JQuery
	//$jQueryJSUrl = $context->rootUrl.'includes/jquery/js/jquery.js';
	//$jQueryJSUrl = dol_buildpath('/includes/jquery/js/jquery.js', 2);
	$jQueryJSUrl = dirname($context->rootUrl).'/includes/jquery/jquery.min.js';
	print '<script src="'.$jQueryJSUrl.'"></script>'."\n";

	$jQueryUIJSUrl = dirname($context->rootUrl).'/includes/jquery/jquery-ui.min.js';
	print '<script src="'.$jQueryUIJSUrl.'"></script>'."\n";

	// JNotify
	//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
	//$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.js', 2);
	$jNotifyJSUrl = dirname($context->rootUrl).'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';
	print '<script src="'.$jNotifyJSUrl.'"></script>'."\n";

	// Common dolibarr js functions
	$jQueryUIJSUrl = $context->rootUrl.'js/lib_head.js.php';
	print '<script src="'.$jQueryUIJSUrl.'"></script>'."\n";
	?>
</head>
<body data-theme="custom" data-controller="<?php print dol_escape_htmltag($context->controller); ?>">
