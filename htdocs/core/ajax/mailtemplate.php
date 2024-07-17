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
	$content = GETPOST('content');

	if (!empty($selectedPosts)) {
		$newsList = '';

		foreach ($selectedPosts as $post) {
			$newsList .= '<div style="display: flex; align-items: flex-start; justify-content: flex-start; width: 100%; max-width: 800px; margin-top: 20px;margin-bottom: 50px; padding: 20px;">
                            <div style="flex-grow: 1; margin-right: 30px; max-width: 600px; margin-left: 100px;">
                                <h2 style="margin: 0; font-size: 1.5em;">' . htmlentities($post['title']) . '</h2>
                                <p style="margin: 10px 0; color: #555;">' . htmlentities($post['description']) . '</p>
                                <span style="display: block; margin-bottom: 5px; color: #888;">Created By: <strong>' . htmlentities($post['user_fullname']) . '</strong></span>
                                <br>
                                <span style="display: block; color: #888;">' . dol_print_date($post['date_creation'], 'daytext', 'tzserver', '') . '</span>
                            </div>
                            <div style="flex-shrink: 0; margin-left: 100px; float: right;">
                                ' . ($post['image'] ? '<img alt="Image" width="130px" height="130px" style="border-radius: 10px;" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=medias&file=' . htmlentities($post['image']) . '">' : '<img alt="Gray rectangle" width="130px" height="130px" style="border-radius: 10px;" src="__GRAY_RECTANGLE__">') . '
                            </div>
                        </div>';
		}

		$content = str_replace('__NEWS_LIST__', $newsList, $content);
	} else {
		$content = str_replace('__NEWS_LIST__', 'No articles selected', $content);
	}


	print $content;
} else {
	print 'No content provided or invalid token';
}
