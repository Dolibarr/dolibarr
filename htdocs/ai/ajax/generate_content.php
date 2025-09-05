<?php
/* Copyright (C) 2008-2025  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/ai/ajax/generate_content.php
 *  \brief          Ajax page to generate content
 */

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

require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

if (!isModEnabled('ai')) {
	accessforbidden('Module AI not enabled');
}


/*
 * View
 */

top_httphead();

//get data from AJAX
$rawData = file_get_contents('php://input');
$jsonData = json_decode($rawData, true);

if (is_null($jsonData)) {
	dol_print_error($db, 'data in POST has not a valid JSON format.');
}
$ai = new Ai($db);

// Get parameters
$function = empty($jsonData['function']) ? 'textgeneration' : $jsonData['function'];	// Default value. Can also be 'textgeneration', 'textgenerationemail', 'textgenerationwebpage', 'imagegeneration', 'videogeneration', ...
$instructions = dol_string_nohtmltag($jsonData['instructions'], 1, 'UTF-8');
$format = empty($jsonData['format']) ? '' : $jsonData['format'];						// Can be '' for text, 'html', ...

if ($format == "html") {
	$instructions = $jsonData['instructions'];
} else {
	$instructions = dol_string_nohtmltag($jsonData['instructions'], 1, 'UTF-8');
}


// Note: The option AI_DEBUG will generate a log file dolibarr_ai.log when calling generateContent()
dol_syslog("generate_content: function=".$function." format=".$format." instruction=".dol_substr($instructions, 0, 200));


$generatedContent = $ai->generateContent($instructions, 'auto', $function, $format);

if (empty($instructions)) {
	http_response_code(400);
	print "Error : empty message.";
} elseif (is_null($generatedContent) || (is_array($generatedContent) && $generatedContent['error'])) {
	// Output error
	if (!empty($generatedContent['code']) && $generatedContent['code'] == 429) {
		http_response_code($generatedContent['code']);
		print "Quota or allowed period exceeded. Retry Later !";
	} elseif (!empty($generatedContent['code']) && $generatedContent['code'] >= 400) {
		http_response_code($generatedContent['code']);
		print "Error : " . $generatedContent['message'];
		print '<br><a href="'.DOL_MAIN_URL_ROOT.'/ai/admin/setup.php">'.$langs->trans('ErrorGoToModuleSetup').'</a>';
	} elseif (!empty($generatedContent['message'])) {
		http_response_code($generatedContent['code']);
		print "Error returned by API call: " . $generatedContent['message'];
	} else {
		http_response_code($generatedContent['code']);
		print "Error API returned no answer";
	}
} else {
	if ($function == 'textgenerationemail' || $function == 'textgenerationwebpage') {
		print dolPrintHTML($generatedContent);	// Note that common HTML tags are NOT escaped (but a sanitization is done)
	} elseif ($function == 'imagegeneration') {
		// TODO
	} elseif ($function == 'videogeneration') {
		// TODO
	} elseif ($function == 'audiogeneration') {
		// TODO
	} else {
		// Default case 'textgeneration', 'texttranslation', 'textsummarize'
		if ($format == "html") {
			print dolPrintHTML($generatedContent);
		} else {
			// We must not use dolPrintText because dolPrintText format data including accent in htmlentities for a HTML output. We need a non formatted output.
			//print dol_string_onlythesehtmltags('"Ça va" est une expression française et ceci une balise <a> html', 1, 1, 1);
			print dol_string_onlythesehtmltags($generatedContent, 1, 1, 1);
		}
	}
}
