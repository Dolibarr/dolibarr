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
?>
<!-- file header_login.tpl.php -->
<?php
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
	$jQueryJSUrl = dirname($context->rootUrl).'/includes/jquery/js/jquery.min.js';
	print '<script src="'.$jQueryJSUrl.'"></script>'."\n";

	// JNotify
	//$jNotifyJSUrl = $context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js';
	//$jNotifyJSUrl = dol_buildpath('/includes/jquery/plugins/jnotify/jquery.jnotify.min.js', 2);
	$jNotifyJSUrl = dirname($context->rootUrl).'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js';
	print '<script src="'.$jNotifyJSUrl.'"></script>'."\n";

	$bodyClass = [
		'login-page'
	];

	$loginFormTheme = getDolGlobalString('WEBPORTAL_LOGIN_FORM_THEME', 'default');
	if (!empty($loginFormTheme)) {
		$loginFormTheme = mb_strtolower($loginFormTheme, 'UTF-8');
		// Replace spaces and consecutive whitespace with a single dash
		$loginFormTheme = preg_replace('/\s+/', '-', $loginFormTheme);
		// Remove all characters except letters, numbers, dash and underscore
		$loginFormTheme = preg_replace('/[^a-z0-9\-_]/', '', $loginFormTheme);
		// Remove leading or trailing dash/underscore
		$loginFormTheme = trim($loginFormTheme, '-_');

		$bodyClass[] = 'login-form-'.$loginFormTheme;
	}

	$langs->load("main", 0, 1);
	$bodyClass[] = ($langs->trans("DIRECTION") == 'rtl' ? 'direction-rtl' : 'direction-ltr');

	// TODO add HOOK here to allow customise headers add body class

	?>
</head>
<body class="<?php print dolPrintHTMLForAttribute(implode(' ', $bodyClass)) ?>">
