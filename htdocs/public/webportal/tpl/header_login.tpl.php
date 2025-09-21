<!-- file header_login.tpl.php -->
<?php
/**
 * @var Context $context	Object Context for webportal
 */

// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

global $langs;


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
		<?php
		if (!empty($context->title)) {
			print $context->title;
		} else {
			print 'WebPortal';
		}
		?>
	</title>
	<link rel="stylesheet" href="<?php print $context->rootUrl.'css/style.css.php'; ?>">
	<link rel="stylesheet" href="<?php print $context->rootUrl.'css/themes/custom.css.php'; ?>">

	<link rel="stylesheet" href="<?php print dirname($context->rootUrl).'/theme/common/fontawesome-5/css/all.min.css?layout=classic'; ?>">
	<?php
	//$jNotifyCSSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.css';
	//$jNotifyCSSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.css', 2);
	$jNotifyCSSUrl = dirname($context->rootUrl).'/includes/jquery/plugins/jnotify/jquery.jnotify.min.css?layout=classic';
	print '<link rel="stylesheet" href="'.$jNotifyCSSUrl.' ">'."\n";

	// JQuery
	//$jQueryJSUrl = $context->rootUrl.'includes/jquery/js/jquery.js';
	//$jQueryJSUrl = dol_buildpath('/includes/jquery/js/jquery.js', 2);
	$jQueryJSUrl = dirname($context->rootUrl).'/includes/jquery/jquery.min.js';
	print '<script src="'.$jQueryJSUrl.'"></script>'."\n";

	// JNotify
	//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
	//$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.js', 2);
	$jNotifyJSUrl = dirname($context->rootUrl).'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';
	print '<script src="'.$jNotifyJSUrl.'"></script>'."\n";
	?>
</head>
<body class="login-page">
