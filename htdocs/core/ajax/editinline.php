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
// For action 'updateElementHtml' (element edited with CKEditor). Class attributes are kept, they are used by the templates.
// The content is checked again with dolWebsiteCheckEditInlineHtml().
$content = GETPOST('content', 'restricthtmlallowclass');
// For action 'updateElementText' (element with only text inside, edited as plain text)
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

if ($action === 'updateElementText' || $action === 'updateElementHtml') {
	top_httphead('application/json');

	$langs->loadLangs(array("website", "errors"));

	$error = '';
	$oldcontent = '';
	$newinner = '';
	$element = null;
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
		// Find the element with contenteditable="true" and this id in the source of the page, with its position
		$elements = dolWebsiteGetEditInlineElements($objectpage->content);
		if (empty($elements[$element_id])) {
			http_response_code(404);
			$error = $langs->trans("ErrorEditInlineElementNotFound", $element_id);
		} else {
			$element = $elements[$element_id];
			if ($element['error']) {
				http_response_code(409);
				$error = $langs->trans($element['error'], $element_id);
			} elseif ($action === 'updateElementText' && $element['mode'] != 'text') {
				http_response_code(409);
				$error = $langs->trans("ErrorEditInlineElementNotPlainText", $element_id);
			}
		}
	}

	if (!$error) {
		$oldinner = substr($objectpage->content, $element['pos'], $element['len']);

		if ($action === 'updateElementText') {
			$text = trim(str_replace("\r", '', $text));
			$newinner = nl2br(htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
		} else {
			// The links of the preview are not the ones of the source, so we restore them
			$newinner = dolWebsiteRestoreLinksOfEditInline($objectwebsite, trim(str_replace("\r", '', $content)), $oldinner);
			if (!dolWebsiteCheckEditInlineHtml($newinner, $element['blocks'])) {
				http_response_code(409);
				$error = $langs->trans("ErrorEditInlineContentNotAllowed", $element_id);
			} else {
				// Keep the line feeds and indentation of the source around the content
				preg_match('/^\s*/', $oldinner, $regspace);
				preg_match('/\s*$/', $oldinner, $regspaceend);
				$newinner = $regspace[0].$newinner.$regspaceend[0];
			}
		}
	}

	if (!$error) {
		$oldcontent = $objectpage->content;
		$objectpage->content = substr_replace($oldcontent, $newinner, $element['pos'], $element['len']);

		// The element has no php code and the new content is checked, so this should never happen
		$oldphp = dolKeepOnlyPhpCode($oldcontent);
		$newphp = dolKeepOnlyPhpCode($objectpage->content);
		if ($oldphp != $newphp) {
			http_response_code(409);
			$error = $langs->trans("ErrorEditInlineElementWithPhp", $element_id);
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
	} elseif ($action === 'updateElementText') {
		// Return the saved text, it may differ from the posted one (tags are removed)
		print json_encode(array('status' => 'ok', 'message' => $langs->trans("Saved"), 'text' => $text));
	} else {
		print json_encode(array('status' => 'ok', 'message' => $langs->trans("Saved")));
	}

	$db->close();
	exit;
}

http_response_code(400);
print 'Bad value for parameter action';
