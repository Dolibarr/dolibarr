<?php

// Just for display errors in editor
ini_set('display_errors', 1);

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
require_once '../../main.inc.php';
require_once '../lib/files.lib.php';

top_httphead();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && GETPOSTISSET('content')) {
	$content = GETPOST('content', 'none');
	$selectedPostsJson = GETPOST('selectedPosts', 'none');
	$selectedPosts = $selectedPostsJson ? json_decode($selectedPostsJson, true) : [];

	if (!empty($selectedPosts)) {
		$content = str_replace('<!-- PHP_START -->', '<?php $selectedPosts = ' . var_export($selectedPosts, true) . '; ?>', $content);
		$content = str_replace('<!-- PHP_END -->', '', $content);
	}

	$directory = $conf->admin->dir_temp . '/mailing/email_template';
	if (!is_dir($directory)) {
		dol_mkdir($directory);
	}

	$i = 0;
	do {
		$filePath = $directory . '/template_' . $i++ . '.php';
	} while (file_exists($filePath));

	file_put_contents($filePath, $content);

	$output = '';

	ob_start();
	try {
		include $filePath;
		$output = ob_get_clean();
	} finally {
		dol_delete_file($filePath);
	}

	print $output;
} else {
	print 'No content provided or invalid token';
}
