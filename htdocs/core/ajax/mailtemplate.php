<?php
/* Copyright (C) 2024-2025 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024      Frédéric France     <frederic.france@free.fr>
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
 *       \file      htdocs/core/ajax/mailtemplate.php
 *       \ingroup	core
 *       \brief     File to return Ajax response on location_incoterms request
 */


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
require_once '../../main.inc.php';
require_once '../lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 * @var string $dolibarr_main_url_root
 */

/*
 * Actions
 */

// None


/*
 * View
 */

top_httphead();

// TODO Replace with ID of template
if (GETPOSTISSET('template')) {
	$templatefile = DOL_DOCUMENT_ROOT.'/install/doctemplates/maillayout/'.dol_sanitizeFileName(GETPOST('template')).'.html';

	$content = file_get_contents($templatefile);

	if ($content === false) {
		print 'Failed to load template '.dol_escape_htmltag(GETPOST('template'));
		exit;
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


	$specificSubstitutionArray = array(
		'__TITLEOFMAILHOLDER__' => $langs->trans('TitleOfMailHolder'),
		'__CONTENTOFMAILHOLDER__' => 'Lorem ipsum ...',
		'__GRAY_RECTANGLE__' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC',
		'__LAST_NEWS__'   => $langs->trans('LastNews'),
		'__LIST_PRODUCTS___' => $langs->trans('ListProducts'),
		'__SUBJECT__' => GETPOST('subject'),
		// vars for company
		'__MYCOMPANY_ADDRESS__' => $mysoc->getFullAddress(0, '<br>', 1, ''),
		'__MYCOMPANY_EMAIL__' => $mysoc->email,
		'__MYCOMPANY_PHONE_PRO__' => $mysoc->phone_pro,
		'__MYCOMPANY_PHONE_MOBILE__' => $mysoc->phone_mobile,
		'__MYCOMPANY_FAX__' => $mysoc->fax,
	);

	$listsocialnetworks = '';
	// TODO Add a column imgsrcdata into llx_c_socialnetworks to store the src data for image of the social network in black on a white background.
	/*
	foreach($mysoc->socialnetworks as $snkey => $snval) {
		$listsocialnetworks .= $snkey;
	}
	*/
	$specificSubstitutionArray['__MYCOMPANY_SOCIAL_NETWORKS__'] = $listsocialnetworks;

	if (!empty($mysoc->logo) && dol_is_file($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		$specificSubstitutionArray['__LOGO_URL__'] = $urlwithroot.'/viewimage.php?modulepart=mycompany&file='.urlencode('logos/'.$mysoc->logo);
	} else {
		$specificSubstitutionArray['__LOGO_URL__'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC';
	}

	$specificSubstitutionArray['__USERSIGNATURE__'] = empty($user->signature) ? '' : $user->signature;

	if (GETPOST('fromtype') == 'user') {
		$specificSubstitutionArray['__SENDEREMAIL_SIGNATURE__'] = empty($user->signature) ? '' : $user->signature;
	} elseif (GETPOST('fromtype') == 'company') {
		$specificSubstitutionArray['__SENDEREMAIL_SIGNATURE__'] = $mysoc->name.' '.$mysoc->email;
	} elseif (GETPOST('fromtype') == 'main_from') {
		$specificSubstitutionArray['__SENDEREMAIL_SIGNATURE__'] = $mysoc->name.' '.getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
	} else {
		// GETPOST('fromtype') is senderprofile_x_y (x = ID profile)
		$specificSubstitutionArray['__SENDEREMAIL_SIGNATURE__'] = 'TODO Read database to get the signature of the profile';
	}


	// Must replace
	// __SUBJECT__, __CONTENTOFMAILHOLDER__, __USERSIGNATURE__, __NEWS_LIST__, __PRODUCTS_LIST__
	foreach ($specificSubstitutionArray as $key => $val) {
		$content = str_replace($key, $val, $content);
	}

	// Parse all strings __(...)__ to replace with the translated value $langs->trans("...")
	$langs->load("other");
	$content = preg_replace_callback(
		'/__\((.+)\)__/',
		/**
		 * @param 	array<int,string> $matches	Array of matches
		 * @return 	string 						Translated string for the key
		 */
		function ($matches) {
			global $langs;
			return $langs->trans($matches[1]);
		},
		$content);


	$selectedPostsStr = GETPOST('selectedPosts', 'alpha');
	$selectedPosts = explode(',', $selectedPostsStr);

	if (is_array($selectedPosts) && !empty($selectedPosts)) {
		$newsList = '';

		foreach ($selectedPosts as $postId) {
			$post = getNewsDetailsById($postId);

			$newsList .= '<div style="display: flex; align-items: flex-start; justify-content: flex-start; width: 100%; max-width: 800px; margin-top: 20px;margin-bottom: 50px; padding: 20px;">
                            <div style="flex-grow: 1; margin-right: 30px; max-width: 600px; margin-left: 100px;">
                                <h2 style="margin: 0; font-size: 1.5em;">' . htmlentities(empty($post['title']) ? '' : $post['title']) . '</h2>
                                <p style="margin: 10px 0; color: #555;">' . htmlentities(empty($post['description']) ? '' : $post['description']) . '</p>
                                <span style="display: block; margin-bottom: 5px; color: #888;">Created By: <strong>' . htmlentities(empty($post['user_fullname']) ? '' : $post['user_fullname']) . '</strong></span>
                                <br>
                                <span style="display: block; color: #888;">' . dol_print_date((empty($post['date_creation']) ? dol_now() : $post['date_creation']), 'daytext', 'tzserver', $langs) . '</span>
                            </div>
                            <div style="flex-shrink: 0; margin-left: 100px; float: right;">
                                ' . (!empty($post['image']) ? '<img alt="Image" width="130px" height="130px" style="border-radius: 10px;" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=medias&file=' . htmlentities($post['image']) . '">' : '<img alt="Gray rectangle" width="130px" height="130px" style="border-radius: 10px;" src="__GRAY_RECTANGLE__">') . '
                            </div>
                        </div>';
		}

		$content = str_replace('__NEWS_LIST__', $newsList, $content);
	} else {
		$content = str_replace('__NEWS_LIST__', 'No articles selected', $content);
	}

	print $content;
} else {
	print 'No template ID provided or expired token';
}
