<?php
/* Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/collab/index.php
 *		\ingroup    collab
 *		\brief      Page to work on a shared document (PAD)
 */

define('NOSCANPOSTFORINJECTION', 1);
define('NOSTYLECHECK', 1);

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "website"));

if (!$user->admin) accessforbidden();

$conf->dol_hide_leftmenu = 1; // Force hide of left menu.

$error = 0;
$website = GETPOST('website', 'alpha');
$page = GETPOST('page', 'alpha');
$pageid = GETPOST('pageid', 'int');
$action = GETPOST('action', 'alpha');

if (GETPOST('delete')) { $action = 'delete'; }
if (GETPOST('preview')) $action = 'preview';
if (GETPOST('create')) { $action = 'create'; }
if (GETPOST('editmedia')) { $action = 'editmedia'; }
if (GETPOST('editcss')) { $action = 'editcss'; }
if (GETPOST('editmenu')) { $action = 'editmenu'; }
if (GETPOST('setashome')) { $action = 'setashome'; }
if (GETPOST('editmeta')) { $action = 'editmeta'; }
if (GETPOST('editcontent')) { $action = 'editcontent'; }

if (empty($action)) $action = 'preview';




/*
 * Actions
 */

if (GETPOST('refreshsite')) $pageid = 0; // If we change the site, we reset the pageid.
if (GETPOST('refreshpage')) $action = 'preview';


// Add a collab page
if ($action == 'add')
{
	$db->begin();

	$objectpage->title = GETPOST('WEBSITE_TITLE');
	$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
	$objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
	$objectpage->keywords = GETPOST('WEBSITE_KEYWORD');

	if (empty($objectpage->title))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		$res = $objectpage->create($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}
	}
	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
		$action = '';
	}
	else
	{
		$db->rollback();
	}

	$action = 'preview';
	$id = $objectpage->id;
}

// Update page
if ($action == 'delete')
{
	$db->begin();

	$res = $object->fetch(0, $website);

	$res = $objectpage->fetch($pageid, $object->fk_website);

	if ($res > 0)
	{
		$res = $objectpage->delete($user);
		if (!$res > 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}

		if (!$error)
		{
			$db->commit();
			setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $website), null, 'mesgs');

			header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website);
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url = '';

llxHeader('', $langs->trans("WebsiteSetup"), $help_url, '', 0, '', '', '', '', '', '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">');

print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST"><div>';
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($action == 'create')
{
	print '<input type="hidden" name="action" value="add">';
}


// Add a margin under toolbar ?
$style = '';
if ($action != 'preview' && $action != 'editcontent') $style = ' margin-bottom: 5px;';

//var_dump($objectpage);exit;
print '<div class="centpercent websitebar">';




print "</div>\n</form>\n";

// End of page
llxFooter();
$db->close();
