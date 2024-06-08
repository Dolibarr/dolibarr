<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \brief      Save edit inline changes
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
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';


$action = GETPOST('action', 'alpha');
$website_ref = GETPOST('website_ref');
$page_id = GETPOST('page_id');
$content = GETPOST('content', 'none');
$element_id = GETPOST('element_id');
$element_type = GETPOST('element_type');

$usercanmodify = $user->hasRight('website', 'write');
if (!$usercanmodify) {
	print "You don't have permission for this action.";
	exit;
}


/*
 * View
 */

top_httphead();

if (!empty($action) && $action === 'updatedElementContent' && $usercanmodify && !empty($content) && !empty($element_id) && !empty($website_ref) && !empty($page_id)) {
	// Page object
	$objectpage = new WebsitePage($db);
	$res = $objectpage->fetch($page_id);
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

	// Replace element content into database and tpl file
	$objectpage->content = preg_replace('/<' . $element_type . '[^>]*id="' . $element_id . '"[^>]*>\K(.*?)(?=<\/' . $element_type . '>)/s', $content, $objectpage->content, 1);
	$res = $objectpage->update($user);
	if ($res) {
		global $dolibarr_main_data_root;
		$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$website_ref;
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

	if (!$error) {
		$db->commit();
		print "Changes are saved for " . $element_type . " with id " . $element_id;
	} else {
		$db->rollback();
	}

	$db->close();
}
