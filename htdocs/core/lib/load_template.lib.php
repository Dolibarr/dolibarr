<?php

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


require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emaillayout.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

if (isset($_GET['template'])) {
	$template = basename($_GET['template']); // Sécuriser le nom de fichier

	// Utilisez la fonction getHtmlOfLayout pour obtenir le contenu du template avec les substitutions effectuées
	$content = getHtmlOfLayout($template);

	if ($content) {
		echo $content;
	} else {
		http_response_code(404);
		echo 'Template not found.';
	}
} else {
	http_response_code(400);
	echo 'Invalid request.';
}
