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
 * @var HookManager	$hookmanager
 * @var Translate $langs
 * @@var string[] $vars['body-class'] more CSS classes  for body
 * @@var string[] $vars['body-theme'] theme css to apply default is custom
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


	$jQueryUICSSUrl = $context->cdnUrl . '/jquery/css/base/jquery-ui.min.css?layout=classic';
	print '<link rel="stylesheet" href="'.$jQueryUICSSUrl.' ">'."\n";

	//$jNotifyCSSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.css';
	//$jNotifyCSSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.css', 2);
	$jNotifyCSSUrl =  $context->cdnUrl . '/jquery/plugins/jnotify/jquery.jnotify.min.css?layout=classic';
	print '<link rel="stylesheet" href="'.$jNotifyCSSUrl.' ">'."\n";

	?>
	<link rel="stylesheet" href="<?php print $context->rootUrl.'css/style.css.php?revision='.getDolGlobalInt('WEBPORTAL_PARAMS_REV'); ?>">
	<link rel="stylesheet" type="text/css" href="<?php print $context->rootUrl.'css/themes/custom.css.php?revision='.getDolGlobalInt('WEBPORTAL_PARAMS_REV'); ?>">

	<link rel="stylesheet" href="<?php print dirname($context->rootUrl).'/theme/common/fontawesome-5/css/all.min.css?layout=classic'; ?>">
	<?php

	// JQuery
	$jQueryJSUrl =  $context->cdnUrl . '/jquery/js/jquery.min.js';
	print '<script nonce="'.getNonce().'" src="'.$jQueryJSUrl.'"></script>'."\n";

	$jQueryUIJSUrl =  $context->cdnUrl . '/jquery/js/jquery-ui.min.js';
	print '<script nonce="'.getNonce().'" src="'.$jQueryUIJSUrl.'"></script>'."\n";

	// JNotify
	//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
	//$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.js', 2);
	$jNotifyJSUrl =  $context->cdnUrl . '/jquery/plugins/jnotify/jquery.jnotify.min.js';
	print '<script nonce="'.getNonce().'" src="'.$jNotifyJSUrl.'"></script>'."\n";

	// SELECT 2
	print '<link rel="stylesheet" href="'.$context->cdnUrl . '/jquery/plugins/select2/dist/css/select2.css">'."\n";
	print '<script nonce="'.getNonce().'" src="'.$context->cdnUrl . '/jquery/plugins/select2/dist/js/select2.full.min.js"></script>'."\n";

	// Modal script
	$ModalJSUrl = $context->rootUrl.'js/modal.js';
	print '<script nonce="'.getNonce().'" src="'.$ModalJSUrl.'"></script>'."\n";

	// Common dolibarr js functions
	$jQueryUIJSUrl = $context->rootUrl.'js/lib_head.js.php';
	print '<script nonce="'.getNonce().'" src="'.$jQueryUIJSUrl.'"></script>'."\n";

	$bodyAttributes = [
		'data-theme' => $vars['body-theme']??'custom',
		'data-controller' => $context->controller,
	];

	if (!empty($vars['body-class'])) {
		$bodyAttributes['class'] = $vars['body-class'];
	}

	$parameters = array(
		'bodyAttributes' => &$bodyAttributes
	);
	$hookmanager->executeHooks('webPortalHeader', $parameters, $context);
	print $hookmanager->resPrint;

	$bodyCompiledAttributes = commonHtmlAttributeBuilder($bodyAttributes);
	?>
</head>
<body <?php print empty($bodyCompiledAttributes) ? '' : implode(' ', $bodyCompiledAttributes); ?> >
