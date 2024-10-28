<!-- file header_login.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

global $langs;
?>
<!DOCTYPE html>
<?php print '<html lang="'.substr($langs->defaultlang, 0, 2) . '">'."\n"; ?>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>
		<?php
		if (!empty($context->title)) {
			print $context->title;
		} else {
			print 'WebPortal';
		}
		?>
	</title>
	<link rel="stylesheet" href="<?php print $context->rootUrl.'css/global.css.php'; ?>">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css">
	<?php
	//$jNotifyCSSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.css';
	$jNotifyCSSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.css', 2);
	print '<link rel="stylesheet" href="'.$jNotifyCSSUrl.' ">';

	// JQuery
	//$jQueryJSUrl = $context->rootUrl.'includes/jquery/js/jquery.js';
	$jQueryJSUrl = dol_buildpath('/includes/jquery/js/jquery.js', 2);
	print '<script src="'.$jQueryJSUrl.'"></script>';

	// JNotify
	//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
	$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.js', 2);
	print '<script src="'.$jNotifyJSUrl.'"></script>';
	?>
</head>
<body class="login-page">
