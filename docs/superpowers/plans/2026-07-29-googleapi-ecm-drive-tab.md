# Google Drive ECM Tab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Google Drive" tab to the ECM area (`htdocs/ecm/index.php`) that lets the logged-in Dolibarr user browse, download, upload, rename and trash the content of their own connected Google Drive, by extending the existing `googleapi` custom module.

**Architecture:** `modGoogleApi` gains one `$this->tabs` entry (standard `complete_head_from_modules` mechanism, zero core file touched) pointing to a new full page `googleapi/ecmgoogledrive.php` that renders the same ECM tab bar (`ecm_prepare_dasboard_head()`) and a two-column browser: a `jqueryFileTree`-powered folder tree on the left (already used by native ECM, now pointed at a new Drive-backed AJAX endpoint) and a combined folders+files table on the right (its own AJAX endpoint). A small `getGoogleDriveService($user)` helper wraps the module's existing per-user OAuth (`getGoogleApiClient()`) to hand back a `Google\Service\Drive` object. Upload/rename/trash are handled as POST actions on `ecmgoogledrive.php` itself, download is streamed by a dedicated endpoint. No new SQL table, no dependency on the unrelated `gcloud` module.

**Tech Stack:** PHP 8.2+ (repo dev container runs PHP 8.5), Dolibarr module framework, `google/apiclient` ^2.9 + `google/apiclient-services` (Drive service) already vendored via the sibling `prune` module, jQuery + `jqueryFileTree` (already loaded by Dolibarr core).

## Global Constraints

- Indentation: tabs, not spaces (PSR-12 otherwise) — repo-wide PHP style rule (`CLAUDE.md`).
- Read request params via `GETPOST('name', 'type')`, never `$_GET`/`$_POST` directly (except the raw `$_FILES` array for the uploaded file itself, which has no `GETPOST` equivalent).
- Escape HTML output with `dol_escape_htmltag()`, JS string literals with `dol_escape_js()`.
- Include the CSRF `token` hidden field in every POST form (`newToken()`), matching the convention already used by `ecm/index.php`.
- Never use PDO/MySQLi directly; this feature does not need direct SQL at all (Google Drive is the only data source, no local cache table).
- No SQL inside loops — not applicable here (no SQL queries in this feature).
- Reuse existing rights: `$user->hasRight('googleapi', 'read'|'write'|'delete')`. Do not add a new permission.
- Reuse existing per-user OAuth (`getGoogleApiClient($user)` in `htdocs/custom/googleapi/lib/googleapi.lib.php`). Do not touch or depend on the sibling `gcloud` custom module.
- Commit message format for this repo: short lowercase summary (see `git log` in the module repo, e.g. `fix missing try catch`) — this module has its own history style, distinct from the outer Dolibarr repo's `TYPE: #issue Description` convention.
- **Repository location:** `htdocs/custom/googleapi` is its own git repository (remote `git@github.com:Net-Logic/dolibarr_module_googleapi.git`, currently on branch `dev`). All commits for this feature are made **inside that nested repo**, not the outer `dolibarr` repo. The outer repo only holds the design spec and this plan under `docs/superpowers/`.
- The nested `googleapi` repo was dirty with unrelated in-progress work at plan-writing time; that work has already been committed separately (commit `23fd14d`, "add per-context push notification toggle and various fixes") so the working tree is clean before Task 1 starts. Verify with `git status` before your first commit — if it is not clean, stop and ask before proceeding.
- Never use `--no-verify`; if this module has pre-commit hooks and one fails, fix the real issue and commit again.
- No automated tests are possible for the Drive-facing logic (no test double for the Google API in this project, no PHPUnit coverage of `custom/` modules). Each task's "test" step is a PHP lint check (`php -l`); Task 9 is a manual, in-browser QA pass against a real connected Google account.

Reference design doc: `docs/superpowers/specs/2026-07-29-googleapi-ecm-drive-tab-design.md` (outer `dolibarr` repo).

All file paths below are given relative to `htdocs/custom/googleapi/` (the nested repo root) unless stated otherwise.

---

### Task 1: Drive service helper functions

**Files:**
- Modify: `lib/googleapi.lib.php:195` (immediately after the closing `}` of `getGoogleApiClient()`)

**Interfaces:**
- Consumes: `getGoogleApiClient($fuser)` (existing function in the same file, returns a `Google_Client` or `false`).
- Produces: `getGoogleDriveService($fuser): \Google\Service\Drive|false` — used by every later task that talks to Drive. `googleapiDriveEscapeId($id): string` — used by every task that builds a Drive API `q=` query string.

- [ ] **Step 1: Add the two helper functions**

Insert right after the existing `getGoogleApiClient()` function (the line that currently reads `	return $client;\n}` followed by a blank line and `/**\n * Create agenda event from task`):

```php
/**
 * Get an authenticated Google Drive service for a user's connected Google account
 *
 * @param User $fuser User owning the Google OAuth token
 * @return \Google\Service\Drive|false Drive service, or false if the user has no valid token
 */
function getGoogleDriveService($fuser)
{
	$client = getGoogleApiClient($fuser);
	if (!is_object($client)) {
		return false;
	}
	return new \Google\Service\Drive($client);
}

/**
 * Escape a Google Drive object id for safe use inside a Drive API 'q' query string
 *
 * @param string $id Drive file or folder id
 * @return string Escaped id
 */
function googleapiDriveEscapeId($id)
{
	return str_replace("'", "\\'", (string) $id);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l lib/googleapi.lib.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add lib/googleapi.lib.php
git commit -m "add getGoogleDriveService helper for the Drive ECM tab"
```

---

### Task 2: Register the ECM tab and add language keys

**Files:**
- Modify: `core/modules/modGoogleApi.class.php:74` (version) and `:176` (`$this->tabs`)
- Modify: `langs/en_US/googleapi.lang`
- Modify: `langs/fr_FR/googleapi.lang`

**Interfaces:**
- Produces: a new ECM tab named `googledrive` pointing to `/googleapi/ecmgoogledrive.php` (built in Task 3), gated on `$user->rights->googleapi->read`. Lang keys `GoogleApiDriveTab`, `GoogleApiDriveNotConnected`, `GoogleApiConnectDrive`, `GoogleApiFolder`, `GoogleApiRename`, `GoogleApiNewName`, `GoogleApiOpenInDrive`, `GoogleApiConfirmDeleteDriveFile`, `GoogleApiUploadToThisFolder`, `GoogleApiDriveFileUploaded`, `GoogleApiDriveFileRenamed`, `GoogleApiDriveFileDeleted`, `GoogleApiErrorDriveApi` — consumed by Tasks 3, 5, 7, 8.

- [ ] **Step 1: Register the tab**

In `core/modules/modGoogleApi.class.php`, replace:

```php
		// Array to add new pages in new tabs
		$this->tabs = [];
```

with:

```php
		// Array to add new pages in new tabs
		$this->tabs = [
			array('data' => 'ecm:+googledrive:GoogleApiDriveTab:googleapi@googleapi:$user->rights->googleapi->read:/googleapi/ecmgoogledrive.php'),
		];
```

- [ ] **Step 2: Bump the module version**

Replace:

```php
		$this->version = '1.0.2';
```

with:

```php
		$this->version = '1.1.0';
```

- [ ] **Step 3: Add English language keys**

Append to `langs/en_US/googleapi.lang`:

```
GoogleApiDriveTab=Google Drive
GoogleApiDriveNotConnected=Your Google account is not connected yet, or the access token has expired.
GoogleApiConnectDrive=Connect to Google Drive
GoogleApiFolder=Folder
GoogleApiRename=Rename
GoogleApiNewName=New name
GoogleApiOpenInDrive=Open in Drive
GoogleApiConfirmDeleteDriveFile=Are you sure you want to move "__FILENAME__" to the Google Drive trash?
GoogleApiUploadToThisFolder=Upload a file into this folder
GoogleApiDriveFileUploaded=File uploaded to Google Drive
GoogleApiDriveFileRenamed=Renamed on Google Drive
GoogleApiDriveFileDeleted=Moved to the Google Drive trash
GoogleApiErrorDriveApi=Google Drive API error: %s
```

- [ ] **Step 4: Add French language keys**

Append to `langs/fr_FR/googleapi.lang`:

```
GoogleApiDriveTab=Google Drive
GoogleApiDriveNotConnected=Votre compte Google n'est pas encore connecté, ou le jeton d'accès a expiré.
GoogleApiConnectDrive=Se connecter à Google Drive
GoogleApiFolder=Dossier
GoogleApiRename=Renommer
GoogleApiNewName=Nouveau nom
GoogleApiOpenInDrive=Ouvrir dans Drive
GoogleApiConfirmDeleteDriveFile=Voulez-vous vraiment mettre "__FILENAME__" à la corbeille Google Drive ?
GoogleApiUploadToThisFolder=Envoyer un fichier dans ce dossier
GoogleApiDriveFileUploaded=Fichier envoyé sur Google Drive
GoogleApiDriveFileRenamed=Renommé sur Google Drive
GoogleApiDriveFileDeleted=Déplacé vers la corbeille Google Drive
GoogleApiErrorDriveApi=Erreur API Google Drive : %s
```

- [ ] **Step 5: Lint the PHP file**

Run: `php -l core/modules/modGoogleApi.class.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add core/modules/modGoogleApi.class.php langs/en_US/googleapi.lang langs/fr_FR/googleapi.lang
git commit -m "register Google Drive ECM tab and add its language keys"
```

---

### Task 3: Tab page skeleton (connect screen + browser shell)

**Files:**
- Create: `ecmgoogledrive.php`

**Interfaces:**
- Consumes: `getGoogleDriveService($user)` (Task 1), `ecm_prepare_dasboard_head()` (Dolibarr core, `core/lib/ecm.lib.php`, already available — no include needed beyond the one below), lang keys from Task 2.
- Produces: the page `googleapi/ecmgoogledrive.php` referenced by the tab declared in Task 2. Defines `$permissiontowrite`, `$permissiontodelete`, `$driveservice` and an `/* Actions */` section (currently empty) that Tasks 7 and 8 append to. Defines the DOM containers `#filetree` (left tree), `#ecmgdrive-breadcrumb` and `#ecmgdrive-filelist` (right panel), and the hidden field `#ecmgdrive_folderid`, consumed by the JS file built in Task 5.

- [ ] **Step 1: Create the page**

```php
<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    googleapi/ecmgoogledrive.php
 * \ingroup googleapi
 * \brief   ECM tab: browse and manage the connected user's Google Drive
 */

// Load Dolibarr environment
include 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once 'lib/googleapi.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('ecm', 'googleapi@googleapi'));

if (!$user->hasRight('googleapi', 'read')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$permissiontowrite = $user->hasRight('googleapi', 'write');
$permissiontodelete = $user->hasRight('googleapi', 'delete');

$driveservice = getGoogleDriveService($user);

/*
 * Actions
 */

// Google Drive mutation actions (upload, rename, delete) are added here by later tasks.

/*
 * View
 */

$morejs = array('/googleapi/js/ecmgoogledrive.js.php');

llxHeader('', $langs->trans("ECMArea"), '', '', 0, 0, $morejs, '', '', 'mod-googleapi page-ecmgoogledrive');

$head = ecm_prepare_dasboard_head();

print dol_get_fiche_head($head, 'googledrive', '', -1, '');

if (!is_object($driveservice)) {
	print '<div class="opacitymedium">'.$langs->trans("GoogleApiDriveNotConnected").'</div><br>'."\n";
	$urltoconnect = dol_buildpath('/googleapi/core/modules/oauth/googleapi_oauthcallback.php', 1).'?backtourl='.urlencode(dol_buildpath('/googleapi/ecmgoogledrive.php', 1));
	print '<a class="butAction" href="'.$urltoconnect.'">'.$langs->trans("GoogleApiConnectDrive").'</a>'."\n";
} else {
	print '<div class="fichecenter">'."\n";
	print '<div class="ecmgdrive-left" style="float:left; width: 30%;">'."\n";
	print '<div id="filetree"></div>'."\n";
	print '</div>'."\n";
	print '<div class="ecmgdrive-right" style="float:left; width: 68%; margin-left: 2%;">'."\n";
	print '<div id="ecmgdrive-breadcrumb"></div>'."\n";
	if ($permissiontowrite) {
		print '<form name="formulaireecmgdriveupload" action="'.$_SERVER['PHP_SELF'].'" method="POST" enctype="multipart/form-data">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		print '<input type="hidden" name="action" value="upload">'."\n";
		print '<input type="hidden" name="folderid" id="ecmgdrive_folderid" value="root">'."\n";
		print '<input type="file" name="userfile">'."\n";
		print '<input type="submit" class="button" value="'.$langs->trans("GoogleApiUploadToThisFolder").'">'."\n";
		print '</form>'."\n";
	} else {
		print '<input type="hidden" id="ecmgdrive_folderid" value="root">'."\n";
	}
	print '<div id="ecmgdrive-filelist"></div>'."\n";
	print '</div>'."\n";
	print '<div style="clear:both;"></div>'."\n";
	print '</div>'."\n";
}

print dol_get_fiche_end();

llxFooter();

$db->close();
```

- [ ] **Step 2: Lint the file**

Run: `php -l ecmgoogledrive.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add ecmgoogledrive.php
git commit -m "add Google Drive ECM tab page skeleton"
```

---

### Task 4: AJAX tree endpoint (left-hand folder navigation)

**Files:**
- Create: `core/ajax/ecmgoogledrivetree.php`

**Interfaces:**
- Consumes: `getGoogleDriveService()`, `googleapiDriveEscapeId()` (Task 1).
- Produces: an endpoint compatible with the `jqueryFileTree` jQuery plugin contract (POST `dir`, returns an HTML `<ul class="ecmjqft">…</ul>` fragment), called from the JS file built in Task 5 (`script:` option of `.fileTree()`).

- [ ] **Step 1: Create the endpoint**

```php
<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    googleapi/core/ajax/ecmgoogledrivetree.php
 * \ingroup googleapi
 * \brief   Returns the sub-folders of a Google Drive folder, for the jqueryFileTree plugin
 */

$defines = [
	'NOTOKENRENEWAL',
	'NOREQUIREMENU',
	'NOREQUIREHTML',
	'NOREQUIREAJAX',
];

// Load Dolibarr environment
include '../../config.php';
require_once '../../lib/googleapi.lib.php';

top_httphead();

if (!$user->hasRight('googleapi', 'read')) {
	accessforbidden();
}

$dir = GETPOST('dir', 'alpha');
$parentid = trim((string) $dir, '/');
if ($parentid == '') {
	$parentid = 'root';
}

$driveservice = getGoogleDriveService($user);

print '<ul class="ecmjqft" style="display: none;">'."\n";

if (is_object($driveservice)) {
	try {
		$query = "'".googleapiDriveEscapeId($parentid)."' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false";
		$result = $driveservice->files->listFiles(array(
			'q' => $query,
			'fields' => 'files(id,name)',
			'orderBy' => 'name',
			'pageSize' => 1000,
		));
		foreach ($result->getFiles() as $folder) {
			print '<li class="directory collapsed">';
			print '<a class="jqft ecmjqft" href="#" rel="'.dol_escape_htmltag($folder->getId()).'/" onclick="ecmGoogleDriveNavigate(\''.dol_escape_js($folder->getId()).'\', \''.dol_escape_js($folder->getName()).'\');">';
			print dol_escape_htmltag($folder->getName());
			print '</a>';
			print '</li>'."\n";
		}
	} catch (Exception $e) {
		dol_syslog('ecmgoogledrivetree: '.$e->getMessage(), LOG_ERR);
	}
}

print '</ul>'."\n";
```

- [ ] **Step 2: Lint the file**

Run: `php -l core/ajax/ecmgoogledrivetree.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add core/ajax/ecmgoogledrivetree.php
git commit -m "add Google Drive folder tree AJAX endpoint"
```

---

### Task 5: AJAX list endpoint (right-hand content table) and JS glue

**Files:**
- Create: `core/ajax/ecmgoogledrivelist.php`
- Create: `js/ecmgoogledrive.js.php`

**Interfaces:**
- Consumes: `getGoogleDriveService()`, `googleapiDriveEscapeId()` (Task 1); DOM containers `#filetree`, `#ecmgdrive-breadcrumb`, `#ecmgdrive-filelist`, `#ecmgdrive_folderid` (Task 3); tree endpoint from Task 4 (`core/ajax/ecmgoogledrivetree.php`).
- Produces: `ecmgoogledrivelist.php?dir=<folderId>` HTML fragment. Global JS functions `ecmGoogleDriveNavigate(folderid, foldername)`, `ecmGoogleDriveLoadList(folderid)`, `ecmGoogleDriveRename(fileid, currentname)`, `ecmGoogleDriveDelete(fileid, filename)` — the last two POST `action=renamedrivefile`/`action=deletedrivefile` to `ecmgoogledrive.php`, handled by Task 8. Until Task 8 lands, clicking Rename/Delete has no visible effect (no matching `$action` branch server-side, page just reloads its list) — this is expected at this point in the plan.

- [ ] **Step 1: Create the list endpoint**

```php
<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    googleapi/core/ajax/ecmgoogledrivelist.php
 * \ingroup googleapi
 * \brief   Returns the HTML table listing the content (folders and files) of a Google Drive folder
 */

$defines = [
	'NOTOKENRENEWAL',
	'NOREQUIREMENU',
	'NOREQUIREHTML',
	'NOREQUIREAJAX',
];

// Load Dolibarr environment
include '../../config.php';
require_once '../../lib/googleapi.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

top_httphead();

if (!$user->hasRight('googleapi', 'read')) {
	accessforbidden();
}

$permissiontowrite = $user->hasRight('googleapi', 'write');
$permissiontodelete = $user->hasRight('googleapi', 'delete');

$dir = GETPOST('dir', 'alpha');
$parentid = ($dir == '' ? 'root' : $dir);

$langs->loadLangs(array('googleapi@googleapi'));

$driveservice = getGoogleDriveService($user);

print '<table class="border centpercent">'."\n";
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td class="right">'.$langs->trans("Size").'</td>';
print '<td class="center">'.$langs->trans("DateModification").'</td>';
print '<td class="right"></td>';
print '</tr>'."\n";

if (is_object($driveservice)) {
	try {
		$query = "'".googleapiDriveEscapeId($parentid)."' in parents and trashed=false";
		$result = $driveservice->files->listFiles(array(
			'q' => $query,
			'fields' => 'files(id,name,mimeType,size,modifiedTime,webViewLink)',
			'orderBy' => 'folder,name',
			'pageSize' => 1000,
		));

		foreach ($result->getFiles() as $file) {
			$isfolder = ($file->getMimeType() == 'application/vnd.google-apps.folder');
			$isnativegoogletype = (!$isfolder && strpos((string) $file->getMimeType(), 'application/vnd.google-apps.') === 0);
			$fileid = $file->getId();
			$filename = $file->getName();

			print '<tr class="oddeven">';

			print '<td>';
			if ($isfolder) {
				print img_picto('', 'folder', 'class="paddingright"');
				print '<a href="#" onclick="ecmGoogleDriveNavigate(\''.dol_escape_js($fileid).'\', \''.dol_escape_js($filename).'\'); return false;">';
				print dol_escape_htmltag($filename);
				print '</a>';
			} else {
				print img_mime($filename);
				print dol_escape_htmltag($filename);
			}
			print '</td>';

			print '<td class="right">';
			if (!$isfolder && $file->getSize()) {
				print dol_print_size((int) $file->getSize(), 1);
			}
			print '</td>';

			print '<td class="center">';
			if ($file->getModifiedTime()) {
				print dol_print_date(strtotime((string) $file->getModifiedTime()), 'dayhour');
			}
			print '</td>';

			print '<td class="right nowraponall">';
			if (!$isfolder && !$isnativegoogletype) {
				$downloadurl = dol_buildpath('/googleapi/core/ajax/ecmgoogledrivedownload.php', 1).'?token='.currentToken().'&fileid='.urlencode($fileid);
				print '<a class="editfielda marginleftonly" href="'.$downloadurl.'" title="'.dol_escape_htmltag($langs->trans("Download")).'">'.img_picto($langs->trans("Download"), 'download').'</a>';
			} elseif ($isnativegoogletype && $file->getWebViewLink()) {
				print '<a class="editfielda marginleftonly" href="'.dol_escape_htmltag($file->getWebViewLink()).'" target="_blank" rel="noopener noreferrer" title="'.dol_escape_htmltag($langs->trans("GoogleApiOpenInDrive")).'">'.img_picto($langs->trans("GoogleApiOpenInDrive"), 'globe').'</a>';
			}
			if ($permissiontowrite) {
				print ' <a class="editfielda marginleftonly" href="#" onclick="ecmGoogleDriveRename(\''.dol_escape_js($fileid).'\', \''.dol_escape_js($filename).'\'); return false;" title="'.dol_escape_htmltag($langs->trans("GoogleApiRename")).'">'.img_picto($langs->trans("GoogleApiRename"), 'edit').'</a>';
			}
			if ($permissiontodelete) {
				print ' <a class="deletefilelink marginleftonly" href="#" onclick="ecmGoogleDriveDelete(\''.dol_escape_js($fileid).'\', \''.dol_escape_js($filename).'\'); return false;" title="'.dol_escape_htmltag($langs->trans("Delete")).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
			}
			print '</td>';

			print '</tr>'."\n";
		}
	} catch (Exception $e) {
		print '<tr><td colspan="4">'.dol_escape_htmltag($e->getMessage()).'</td></tr>'."\n";
		dol_syslog('ecmgoogledrivelist: '.$e->getMessage(), LOG_ERR);
	}
}

print '</table>'."\n";
```

- [ ] **Step 2: Create the JS glue file**

```php
<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    googleapi/js/ecmgoogledrive.js.php
 * \ingroup googleapi
 * \brief   JS glue for the Google Drive ECM tab
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
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

include '../config.php';

$langs->loadLangs(array('googleapi@googleapi'));

top_httphead('application/javascript');
?>
var ecmGoogleDriveBreadcrumb = [{id: 'root', name: '<?php echo dol_escape_js($langs->trans("Home")); ?>'}];

function ecmGoogleDriveRenderBreadcrumb()
{
	var html = '';
	for (var i = 0; i < ecmGoogleDriveBreadcrumb.length; i++) {
		if (i > 0) {
			html += ' / ';
		}
		html += '<a href="#" onclick="ecmGoogleDriveGoToBreadcrumb('+i+'); return false;">'+ecmGoogleDriveBreadcrumb[i].name+'</a>';
	}
	jQuery('#ecmgdrive-breadcrumb').html(html);
}

function ecmGoogleDriveGoToBreadcrumb(index)
{
	var entry = ecmGoogleDriveBreadcrumb[index];
	ecmGoogleDriveBreadcrumb = ecmGoogleDriveBreadcrumb.slice(0, index + 1);
	jQuery('#ecmgdrive_folderid').val(entry.id);
	ecmGoogleDriveLoadList(entry.id);
}

function ecmGoogleDriveNavigate(folderid, foldername)
{
	ecmGoogleDriveBreadcrumb.push({id: folderid, name: foldername});
	jQuery('#ecmgdrive_folderid').val(folderid);
	ecmGoogleDriveLoadList(folderid);
}

function ecmGoogleDriveLoadList(folderid)
{
	ecmGoogleDriveRenderBreadcrumb();
	jQuery('#ecmgdrive-filelist').html('<?php echo dol_escape_js($langs->trans("PleaseBePatient")); ?>');
	jQuery.get('<?php echo dol_buildpath('/googleapi/core/ajax/ecmgoogledrivelist.php', 1); ?>', {dir: folderid, token: '<?php echo currentToken(); ?>'}, function(data) {
		jQuery('#ecmgdrive-filelist').html(data);
	});
}

function ecmGoogleDriveRename(fileid, currentname)
{
	var newname = prompt('<?php echo dol_escape_js($langs->trans("GoogleApiNewName")); ?>', currentname);
	if (newname === null || newname === '' || newname === currentname) {
		return;
	}
	jQuery.post('<?php echo dol_buildpath('/googleapi/ecmgoogledrive.php', 1); ?>', {
		action: 'renamedrivefile',
		token: '<?php echo newToken(); ?>',
		fileid: fileid,
		newname: newname
	}, function() {
		ecmGoogleDriveLoadList(jQuery('#ecmgdrive_folderid').val());
	});
}

function ecmGoogleDriveDelete(fileid, filename)
{
	var msgtemplate = '<?php echo dol_escape_js($langs->trans("GoogleApiConfirmDeleteDriveFile")); ?>';
	if (!confirm(msgtemplate.replace('__FILENAME__', filename))) {
		return;
	}
	jQuery.post('<?php echo dol_buildpath('/googleapi/ecmgoogledrive.php', 1); ?>', {
		action: 'deletedrivefile',
		token: '<?php echo newToken(); ?>',
		fileid: fileid
	}, function() {
		ecmGoogleDriveLoadList(jQuery('#ecmgdrive_folderid').val());
	});
}

jQuery(document).ready(function() {
	jQuery('#filetree').fileTree(
		{
			root: 'root/',
			script: '<?php echo dol_buildpath('/googleapi/core/ajax/ecmgoogledrivetree.php', 1); ?>?token=<?php echo currentToken(); ?>',
			folderEvent: 'click',
			multiFolder: false
		},
		function(file) {
			// Files are not shown in the left tree (folders only): nothing to do here.
		}
	);
	ecmGoogleDriveLoadList('root');
});
```

- [ ] **Step 3: Lint both files**

Run: `php -l core/ajax/ecmgoogledrivelist.php && php -l js/ecmgoogledrive.js.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Commit**

```bash
git add core/ajax/ecmgoogledrivelist.php js/ecmgoogledrive.js.php
git commit -m "add Google Drive content list AJAX endpoint and JS glue for the ECM tab"
```

---

### Task 6: Download endpoint

**Files:**
- Create: `core/ajax/ecmgoogledrivedownload.php`

**Interfaces:**
- Consumes: `getGoogleDriveService()` (Task 1). Linked from the download button rendered in `core/ajax/ecmgoogledrivelist.php` (Task 5): `core/ajax/ecmgoogledrivedownload.php?token=<token>&fileid=<id>`.
- Produces: a binary stream response (no further consumers).

- [ ] **Step 1: Create the endpoint**

```php
<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    googleapi/core/ajax/ecmgoogledrivedownload.php
 * \ingroup googleapi
 * \brief   Streams a Google Drive file to the browser
 */

$defines = [
	'NOTOKENRENEWAL',
	'NOREQUIREMENU',
	'NOREQUIREHTML',
	'NOREQUIREAJAX',
];

// Load Dolibarr environment
include '../../config.php';
require_once '../../lib/googleapi.lib.php';

if (!$user->hasRight('googleapi', 'read')) {
	accessforbidden();
}

$fileid = GETPOST('fileid', 'alpha');
if (empty($fileid)) {
	http_response_code(400);
	print 'Missing fileid';
	exit;
}

$driveservice = getGoogleDriveService($user);
if (!is_object($driveservice)) {
	http_response_code(403);
	print 'Not connected to Google Drive';
	exit;
}

try {
	$metadata = $driveservice->files->get($fileid, array('fields' => 'name,mimeType,size'));
	if (strpos((string) $metadata->getMimeType(), 'application/vnd.google-apps.') === 0) {
		// Native Google file (Docs/Sheets/Slides/...) has no direct binary content to stream
		http_response_code(400);
		print 'This file type cannot be downloaded directly, open it in Google Drive instead';
		exit;
	}

	$response = $driveservice->files->get($fileid, array('alt' => 'media'));
	$content = $response->getBody()->getContents();

	top_httphead($metadata->getMimeType() ? $metadata->getMimeType() : 'application/octet-stream');
	header('Content-Disposition: attachment; filename="'.dol_sanitizeFileName($metadata->getName()).'"');
	header('Content-Length: '.strlen($content));
	print $content;
} catch (Exception $e) {
	dol_syslog('ecmgoogledrivedownload: '.$e->getMessage(), LOG_ERR);
	http_response_code(500);
	print dol_escape_htmltag($e->getMessage());
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l core/ajax/ecmgoogledrivedownload.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add core/ajax/ecmgoogledrivedownload.php
git commit -m "add Google Drive file download endpoint for the ECM tab"
```

---

### Task 7: Upload action

**Files:**
- Modify: `ecmgoogledrive.php` (the `/* Actions */` section added in Task 3)

**Interfaces:**
- Consumes: `$driveservice`, `$permissiontowrite` (Task 3); the upload form already rendered in Task 3 (`name="userfile"`, `action=upload`, hidden `folderid`).
- Produces: nothing consumed by later tasks (leaf feature).

- [ ] **Step 1: Add the upload handler**

In `ecmgoogledrive.php`, replace:

```php
/*
 * Actions
 */

// Google Drive mutation actions (upload, rename, delete) are added here by later tasks.
```

with:

```php
/*
 * Actions
 */

if ($action == 'upload' && $permissiontowrite) {
	if (!empty($_FILES['userfile']['tmp_name']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
		$parentid = GETPOST('folderid', 'alpha') ? GETPOST('folderid', 'alpha') : 'root';
		if (is_object($driveservice)) {
			try {
				$drivefile = new \Google\Service\Drive\DriveFile();
				$drivefile->setName($_FILES['userfile']['name']);
				$drivefile->setParents(array($parentid));
				$driveservice->files->create($drivefile, array(
					'data' => file_get_contents($_FILES['userfile']['tmp_name']),
					'mimeType' => $_FILES['userfile']['type'] ? $_FILES['userfile']['type'] : 'application/octet-stream',
					'uploadType' => 'multipart',
				));
				setEventMessages($langs->trans("GoogleApiDriveFileUploaded"), null, 'mesgs');
			} catch (Exception $e) {
				setEventMessages($langs->trans("GoogleApiErrorDriveApi", $e->getMessage()), null, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
	}
}

// Google Drive rename/delete actions are added here by the next task.
```

- [ ] **Step 2: Lint the file**

Run: `php -l ecmgoogledrive.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add ecmgoogledrive.php
git commit -m "handle file upload to the current Google Drive folder"
```

---

### Task 8: Rename and delete (trash) actions

**Files:**
- Modify: `ecmgoogledrive.php` (the marker comment added in Task 7)

**Interfaces:**
- Consumes: `$driveservice`, `$permissiontowrite`, `$permissiontodelete` (Task 3); the `ecmGoogleDriveRename()`/`ecmGoogleDriveDelete()` JS functions (Task 5), which POST `action=renamedrivefile`/`action=deletedrivefile` with `fileid` (and `newname` for rename).
- Produces: nothing consumed by later tasks (leaf feature). This closes the loop with Task 5's JS, which was written expecting these two action handlers.

- [ ] **Step 1: Add the rename and delete handlers**

In `ecmgoogledrive.php`, replace:

```php
// Google Drive rename/delete actions are added here by the next task.
```

with:

```php
if ($action == 'renamedrivefile' && $permissiontowrite) {
	$fileid = GETPOST('fileid', 'alpha');
	$newname = GETPOST('newname', 'alphanohtml');
	if ($fileid && $newname && is_object($driveservice)) {
		try {
			$drivefile = new \Google\Service\Drive\DriveFile();
			$drivefile->setName($newname);
			$driveservice->files->update($fileid, $drivefile);
			setEventMessages($langs->trans("GoogleApiDriveFileRenamed"), null, 'mesgs');
		} catch (Exception $e) {
			setEventMessages($langs->trans("GoogleApiErrorDriveApi", $e->getMessage()), null, 'errors');
		}
	}
}

if ($action == 'deletedrivefile' && $permissiontodelete) {
	$fileid = GETPOST('fileid', 'alpha');
	if ($fileid && is_object($driveservice)) {
		try {
			$drivefile = new \Google\Service\Drive\DriveFile();
			$drivefile->setTrashed(true);
			$driveservice->files->update($fileid, $drivefile);
			setEventMessages($langs->trans("GoogleApiDriveFileDeleted"), null, 'mesgs');
		} catch (Exception $e) {
			setEventMessages($langs->trans("GoogleApiErrorDriveApi", $e->getMessage()), null, 'errors');
		}
	}
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l ecmgoogledrive.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add ecmgoogledrive.php
git commit -m "handle rename and trash actions for Google Drive files"
```

---

### Task 9: Manual QA pass

**Files:** none (verification only; fix forward in the relevant file from Tasks 1-8 if a check fails, then re-run this task's checklist).

**Interfaces:**
- Consumes: the whole feature (Tasks 1-8), against a real Dolibarr instance with the `googleapi` module enabled, `OAUTH_GOOGLEAPI_ID`/`OAUTH_GOOGLEAPI_SECRET` configured, and a real Google account.

- [ ] **Step 1: Enable the module and check the tab appears**

Enable the `googleapi` module (if not already), go to the ECM area (`ecm/index.php`). Confirm a "Google Drive" tab is visible in the tab bar, next to Manual/Auto/Medias.

- [ ] **Step 2: Connect flow**

With no Google OAuth token stored for the current user, open the Google Drive tab. Confirm the "not connected" message and the "Connect to Google Drive" button appear. Click it, complete the Google OAuth consent screen (scope includes Drive), confirm you land back on the Google Drive tab with the browser UI now visible (left tree + right panel populated with root Drive content).

- [ ] **Step 3: Navigation**

Click into a sub-folder from the right-hand list; confirm the right panel refreshes to show that folder's content and the breadcrumb gains a segment. Click a breadcrumb segment to jump back up; confirm the panel updates accordingly. Expand the same folder from the left-hand tree; confirm it also refreshes the right panel to the same folder.

- [ ] **Step 4: Download**

Click the download icon on a binary file (e.g. a PDF or image). Confirm the browser downloads the file with the correct name and that its content matches the file on Drive.

- [ ] **Step 5: Upload**

Use the upload form to send a file while browsing a specific folder. Confirm a success message appears, the file shows up in the list after refresh, and it is visible in that same folder on Google Drive itself (drive.google.com).

- [ ] **Step 6: Rename**

Click the rename icon on a file, enter a new name. Confirm the success message, that the list shows the new name, and that Google Drive itself reflects the rename.

- [ ] **Step 7: Delete (trash)**

Click the delete icon on a file, confirm the confirmation dialog shows the file name, confirm it. Verify the file disappears from the list, and that it appears in the Trash on Google Drive (drive.google.com) — not permanently deleted.

- [ ] **Step 8: Native Google file type**

Navigate to a folder containing a native Google Docs/Sheets/Slides file. Confirm no download icon is shown for it, and that an "Open in Drive" link is shown instead, opening the file on drive.google.com in a new tab.

- [ ] **Step 9: Record the outcome**

If every check above passes, no further commit is needed (the feature is complete as committed in Tasks 1-8). If any check fails, fix the relevant file from the task that introduced it, re-run that task's `php -l` check, re-test the specific QA step here, then commit the fix with a message describing what was wrong (e.g. `git commit -m "fix folder navigation not refreshing breadcrumb"`).
