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
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

top_httphead();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && GETPOSTISSET('selectedIds')) {
	$selectedIds = json_decode(GETPOST('selectedIds'), true);

	$websitepage = new WebsitePage($db);
	$selectedPosts = array();

	foreach ($selectedIds as $id) {
		$blog = new WebsitePage($db);
		$blog->fetch($id);

		$selectedPosts[] = array(
			'id' => $blog->id,
			'title' => $blog->title,
			'description' => $blog->description,
			'date_creation' => $blog->date_creation,
			'image' => $blog->image,
		);
	}

	print json_encode($selectedPosts);
} else {
	print json_encode(array('error' => 'Invalid request'));
}
