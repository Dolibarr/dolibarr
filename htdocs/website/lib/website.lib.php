<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/website/lib/website.lib.php
 * \ingroup website
 * \brief   Library files with common functions for WebsiteAccount
 */


 /**
 * Prepare array of tabs for Website
 *
 * @param	Website		$object		Website
 * @return 	array<array{0:string,1:string,2:string}>	Array of tabs
 */
function websiteconfigPrepareHead($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/website/index.php?websiteid='.$object->id.'&action=editcss';
	$head[$h][1] = $langs->trans("General");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/website/index.php?websiteid='.$object->id.'&action=editsecurity';
	$head[$h][1] = $langs->trans("Security");
	$head[$h][2] = 'security';
	$h++;

	/*if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if(!empty($object->fields['note_private'])) $nbNote++;
		if(!empty($object->fields['note_public'])) $nbNote++;
		$head[$h][0] = dol_buildpath('/monmodule/websiteaccount_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		$head[$h][2] = 'note';
		$h++;
	}*/

	/*
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->monmodule->dir_output . "/websiteaccount/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/monmodule/websiteaccount_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.($nbFiles+$nbLinks).'</span>' : '');
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/monmodule/websiteaccount_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@monmodule:/monmodule/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@monmodule:/monmodule/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'websiteaccount@website');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'websiteaccount@website', 'remove');

	return $head;
}

/**
 * Prepare array of directives for Website
 *
 * @return 	array<string,array<string,string>>					Array of directives
 */
function websiteGetContentPolicyDirectives()
{
	return array(
		// Fetch directives
		"child-src" => array("label" => "child-src", "data-directivetype" => "fetch"),
		"connect-src" => array("label" => "connect-src", "data-directivetype" => "fetch"),
		"default-src" => array("label" => "default-src", "data-directivetype" => "fetch"),
		"fenced-frame-src" => array("label" => "fenced-frame-src", "data-directivetype" => "fetch"),
		"font-src" => array("label" => "font-src", "data-directivetype" => "fetch"),
		"frame-src" => array("label" => "frame-src", "data-directivetype" => "fetch"),
		"img-src" => array("label" => "img-src", "data-directivetype" => "fetch"),
		"manifest-src" => array("label" => "manifest-src", "data-directivetype" => "fetch"),
		"media-src" => array("label" => "media-src", "data-directivetype" => "fetch"),
		"object-src" => array("label" => "object-src", "data-directivetype" => "fetch"),
		"prefetch-src" => array("label" => "prefetch-src", "data-directivetype" => "fetch"),
		"script-src" => array("label" => "script-src", "data-directivetype" => "fetch"),
		"script-src-elem" => array("label" => "script-src-elem", "data-directivetype" => "fetch"),
		"script-src-attr" => array("label" => "script-src-attr", "data-directivetype" => "fetch"),
		"style-src" => array("label" => "style-src","data-directivetype" => "fetch"),
		"style-src-elem" => array("label" => "style-src-elem", "data-directivetype" => "fetch"),
		"style-src-attr" => array("label" => "style-src-attr", "data-directivetype" => "fetch"),
		"worker-src" => array("label" => "worker-src", "data-directivetype" => "fetch"),
		// Document directives
		"base-uri" => array("label" => "base-uri", "data-directivetype" => "document"),
		"sandbox" => array("label" => "sandbox", "data-directivetype" => "document"),
		// Navigation directives
		"form-action" => array("label" => "form-action", "data-directivetype" => "navigation"),
		"frame-ancestors" => array("label" => "frame-ancestors", "data-directivetype" => "navigation"),
		// Reporting directives
		"report-to" => array("label" => "report-to", "data-directivetype" => "reporting"),
		// Other directives
		"require-trusted-types-for" => array("label" => "require-trusted-types-for", "data-directivetype" => "require-trusted-types-for"),
		"trusted-types" => array("label" => "trusted-types", "data-directivetype" => "trusted-types"),
		"upgrade-insecure-requests" => array("label" => "upgrade-insecure-requests", "data-directivetype" => "none"),
	);
}

/**
 * Prepare array of sources for Website
 *
 * @return 	array<string,array<string,array<string,string>>>					Array of sources
 */
function websiteGetContentPolicySources()
{
	return array(
		// Fetch directives
		"fetch" => array(
			"*" => array("label" => "*", "data-sourcetype" => "select"),
			"data" => array("label" => "data:", "data-sourcetype" => "data"),
			"self" => array("label" => "self", "data-sourcetype" => "quoted"),
			"unsafe-eval" => array("label" => "unsafe-eval", "data-sourcetype" => "quoted"),
			"wasm-unsafe-eval" => array("label" => "wasm-unsafe-eval", "data-sourcetype" => "quoted"),
			"unsafe-inline" => array("label" => "unsafe-inline", "data-sourcetype" => "quoted"),
			"unsafe-hashes" => array("label" => "unsafe-hashes", "data-sourcetype" => "quoted"),
			"inline-speculation-rules" => array("label" => "inline-speculation-rules", "data-sourcetype" => "quoted"),
			"strict-dynamic" => array("label" => "strict-dynamic", "data-sourcetype" => "quoted"),
			"report-sample" => array("label" => "report-sample", "data-sourcetype" => "quoted"),
			"host-source" => array("label" => "host-source (*.mydomain.com)", "data-sourcetype" => "input"),
			"scheme-source" => array("label" => "scheme-source", "data-sourcetype" => "input"),
		),
		// Document directives
		"document" => array(
			"none" => array("label" => "self", "data-sourcetype" => "quoted"),
			"self" => array("label" => "self", "data-sourcetype" => "quoted"),
			"host-source" => array("label" => "host-source (*.mydomain.com)", "data-sourcetype" => "input"),
			"scheme-source" => array("label" => "scheme-source (*.mydomain.com)", "data-sourcetype" => "input"),
		),
		// Navigation directives
		"navigation" => array(
			"none" => array("label" => "self", "data-sourcetype" => "quoted"),
			"self" => array("label" => "self", "data-sourcetype" => "quoted"),
			"host-source" => array("label" => "host-source (*.mydomain.com)", "data-sourcetype" => "input"),
			"scheme-source" => array("label" => "scheme-source", "data-sourcetype" => "input"),
		),
		// Reporting directives
		"reporting" => array(
			"report-to" => array("label" => "report-to", "data-sourcetype" => "input"),
		),
		// Other directives
		"require-trusted-types-for" => array(
			"script" => array("label" => "script", "data-sourcetype" => "select"),
		),
		"trusted-types" => array(
			"policyName" => array("label" => "policyName", "data-sourcetype" => "input"),
			"none" => array("label" => "none", "data-sourcetype" => "quoted"),
			"allow-duplicates" => array("label" => "allow-duplicates", "data-sourcetype" => "quoted"),
		),
	);
}

/**
 * Transform a Content Security Policy to an array
 *
 * @param	string		$forceCSP		Content security policy string
 * @return 	array<string,array<string|int,array<string|int,string>|string>>				Array of sources
 */
function websiteGetContentPolicyToArray($forceCSP)
{
	$forceCSPArr = array();
	$sourceCSPArr = websiteGetContentPolicySources();
	$sourceCSPArrflatten = array();

	// We remove a level for sources array
	foreach ($sourceCSPArr as $key => $arr) {
		$sourceCSPArrflatten = array_merge($sourceCSPArrflatten, array_keys($arr));
	}
	// Gerer le problème avec data:text/plain;base64,SGVsbG8sIFdvcmxkIQ%3D%3D qui est split + problème avec button ajouter
	$forceCSP = preg_replace('/;base64,/', "__semicolumnbase64__", $forceCSP);
	$securitypolicies = explode(";", $forceCSP);

	// Loop on each security policy to create an array
	foreach ($securitypolicies as $key => $securitypolicy) {
		if ($securitypolicy == "") {
			continue;
		}
		$securitypolicy = preg_replace('/__semicolumnbase64__/', ";base64,", $securitypolicy);
		$securitypolicyarr = explode(" ", $securitypolicy);
		$directive = array_shift($securitypolicyarr);
		// Remove unwanted spaces
		while ($directive == "") {
			$directive = array_shift($securitypolicyarr);
		}
		if (empty($directive)) {
			continue;
		}
		$sources = $securitypolicyarr;
		if (empty($sources)) {
			$forceCSPArr[$directive] = array();
		} else {
			//Loop on each sources to add to the right directive array key
			foreach ($sources as $key2 => $source) {
				$source = str_replace("'", "", $source);
				if (empty($source)) {
					continue;
				}
				if (empty($forceCSPArr[$directive])) {
					$forceCSPArr[$directive] = array($source);
				} else {
					$forceCSPArr[$directive][] = $source;
				}
			}
		}
	}
	return $forceCSPArr;
}
