<?php
/* Copyright (C) 2017 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2024 Frédéric France   	<frederic.france@free.fr>
 * Copyright (C) 2025 MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026 Joachim Kueter		<git-jk@bloxera.com>
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
 *      \file       htdocs/core/ajax/editinline.php
 *      \ingroup    website
 *      \brief      Save changes done into a website page when we are in "edit inline" mode.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';

$action = GETPOST('action', 'alpha');
$page_id = GETPOSTINT('page_id');
$element_id = GETPOST('element_id', 'aZ09');
// For action 'updatedElementContent' (element with contenteditable="true" and html inside, edited with CKEditor)
$content = GETPOST('content', 'restricthtml');
$element_type = GETPOST('element_type', 'aZ09');
// For action 'updateElementText' (element with contenteditable="true" and only text inside)
$text = GETPOST('text', 'nohtml');

global $dolibarr_main_data_root;

$usercanmodify = $user->hasRight('website', 'write');
if (!$usercanmodify) {
	http_response_code(403);
	print "You don't have permission for this action.";
	exit;
}


/*
 * View
 */

if ($action === 'updateElementText') {
	top_httphead('application/json');

	$langs->loadLangs(array("website", "errors"));

	$error = '';
	$oldcontent = '';
	$objectpage = new WebsitePage($db);
	$objectwebsite = new Website($db);

	if (empty($element_id) || empty($page_id)) {
		http_response_code(400);
		$error = $langs->trans("ErrorBadParameters");
	} elseif ($objectpage->fetch($page_id) <= 0 || $objectwebsite->fetch($objectpage->fk_website) <= 0) {
		// Website::fetch() also checks the entity
		http_response_code(404);
		$error = $langs->trans("ErrorRecordNotFound");
	} else {
		// Find the element with contenteditable="true" and this id in the source of the page. Its content must be plain text
		// (only <br> tags allowed), so the closing tag found by the regex is the one of the element and no html or php code
		// can be lost.
		$regex = '/(<([a-z][a-z0-9]*)(?=[^<>]*\sid="'.preg_quote($element_id, '/').'")(?=[^<>]*\scontenteditable="true")[^<>]*>)(.*?)(<\/\2\s*>)/is';
		$nbfound = preg_match_all($regex, $objectpage->content, $reg);
		if ($nbfound == 0) {
			http_response_code(404);
			$error = $langs->trans("ErrorEditInlineElementNotFound", $element_id);
		} elseif ($nbfound > 1) {
			http_response_code(409);
			$error = $langs->trans("ErrorEditInlineElementNotUnique", $element_id);
		} elseif (preg_match('/<(?!br\s*\/?>)/i', $reg[3][0])) {
			http_response_code(409);
			$error = $langs->trans("ErrorEditInlineElementNotPlainText", $element_id);
		}
	}

	if (!$error) {
		$text = trim(str_replace("\r", '', $text));
		$newtext = nl2br(htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

		$oldcontent = $objectpage->content;
		$objectpage->content = preg_replace_callback($regex, static function ($m) use ($newtext) {
			return $m[1].$newtext.$m[4];
		}, $oldcontent, 1);

		// The text is escaped, so this should never happen, but if the area is inside a php string, the php code would change
		$oldphp = dolKeepOnlyPhpCode($oldcontent);
		$newphp = dolKeepOnlyPhpCode($objectpage->content);
		if ($oldphp != $newphp) {
			http_response_code(409);
			$error = $langs->trans("ErrorEditInlineElementNotPlainText", $element_id);
		}
	}

	if (!$error && $objectpage->content != $oldcontent) {
		$db->begin();

		if ($objectpage->update($user) <= 0) {
			$error = $objectpage->error;
		} else {
			$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$objectwebsite->ref;
			$filetpl = $pathofwebsite.'/page'.$objectpage->id.'.tpl.php';
			if (!dolSavePageContent($filetpl, $objectwebsite, $objectpage, 1)) {
				$error = $langs->trans("ErrorFailedToWriteInDir", $pathofwebsite);
			}
		}

		if ($error) {
			$db->rollback();
			http_response_code(500);
		} else {
			$db->commit();
		}
	}

	if ($error) {
		print json_encode(array('error' => $error));
	} else {
		// Return the saved text, it may differ from the posted one (tags are removed)
		print json_encode(array('status' => 'ok', 'message' => $langs->trans("Saved"), 'text' => $text));
	}

	$db->close();
	exit;
}

top_httphead();

if (!empty($action) && $action === 'updatedElementContent' && $usercanmodify && !empty($content) && !empty($element_id) && !empty($page_id)) {
	// Page object
	$objectpage = new WebsitePage($db);
	$res = $objectpage->fetch((int) $page_id);
	if (!$res) {
		print "Cannot find page with ID = " . $page_id . ".";
		exit;
	}

	// Website object
	$objectwebsite = new Website($db);
	$res = $objectwebsite->fetch($objectpage->fk_website);
	if (!$res) {
		print "Cannot find website with REF " . $objectpage->fk_website . ".";
		exit;
	}

	$db->begin();
	$error = 0;

	$oldContent = $objectpage->content;

	// Replace element content into database and tpl file
	// TODO Enhance this by a DOM scan/replacement
	$objectpage->content = preg_replace('/<' . preg_quote($element_type, '/') . '[^>]*\bid="' . preg_quote($element_id, '/') . '"[^>]*>\K(.*?)(?=<\/' . preg_quote($element_type, '/') . '>)/s', $content, $objectpage->content, 1);

	$oldPhp = dolKeepOnlyPhpCode($oldContent);
	$newPhp = dolKeepOnlyPhpCode($objectpage->content);
	if (checkPHPCode($oldPhp, $newPhp)) {
		// Error php was modified when not allowed
		$error++;
		// A message was set by setEventMessages into checkPHPCode().
	} else {
		$res = $objectpage->update($user);
		if ($res) {
			$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$objectwebsite->ref;
			$filetpl = $pathofwebsite.'/page'.$objectpage->id.'.tpl.php';

			$result = dolSavePageContent($filetpl, $objectwebsite, $objectpage, 1);
			if (!$result) {
				print "Failed to write file " . $filetpl . ".";
				$error++;
			}
		} else {
			print "Failed to save changes error " . $objectpage->error . ".";
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		print "Changes are saved for " . $element_type . " with id " . $element_id;
	} else {
		$db->rollback();
	}

	$db->close();
}
