<?php
/* Copyright (C) 2008-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
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
 *	\file			htdocs/ai/lib/generate_content.lib.php
 *  \brief          Library of ai script
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

top_httphead();

//get data from AJAX
$rawData = file_get_contents('php://input');
$jsonData = json_decode($rawData, true);

if (is_null($jsonData)) {
	dol_print_error('data with format JSON valide.');
}
$chatGPT = new Ai($db);

$instructions = dol_string_nohtmltag($jsonData['instructions'], 1, 'UTF-8');

$generatedContent = $chatGPT->generateContent($instructions, 'gpt-3.5-turbo', 'MAILING');

if (is_array($generatedContent) && $generatedContent['error']) {
	print "Error : " . $generatedContent['message'];
} else {
	print $generatedContent;
}
