# "Google Drive" tab in the ECM area (googleapi module)

Date: 2026-07-29
Status: Approved by user, implementation plan pending.

## Context

The custom module `htdocs/custom/googleapi` already handles Google API
integration in Dolibarr: per-user OAuth (`getGoogleApiClient($user)` in
`googleapi/lib/googleapi.lib.php`), token storage/refresh via the `prune`
module (`llx_prune_oauth_token`), and the `drive` OAuth scope is already
requested during consent (`core/modules/oauth/googleapi_oauthcallback.php`)
even though it is currently unused.

The `prune` module already vendors `google/apiclient` (^2.9) with the
`Gmail`, `Calendar`, `PeopleService`, `Drive`, `Sheets` services — the
`Google\Service\Drive` and `Google\Service\Drive\DriveFile` classes are
therefore available without adding any new Composer dependency.

There is also another custom module, `gcloud`, which mirrors Dolibarr's ECM
files to Google Drive (folder-name mirroring for sync purposes). This module
must **not** be used or depended on: the feature requested here (browsing
and managing the real content of the connected Drive from the ECM area) is
independent and must work standalone.

## Goal

Add a new "Google Drive" tab to the ECM tab bar (`htdocs/ecm/index.php`),
alongside the native tabs (Manual, Auto, Medias). This tab shows the folder
tree and file listing of the currently logged-in Dolibarr user's own Google
Drive (per-user OAuth, no shared Drive account), with the ability to
navigate, download, upload, rename and delete (trash) files, starting from
the real Drive root (`root`), with no restriction to a sub-folder.

## Functional scope

- Navigate the full folder tree of the connected user's Drive, starting at
  `'root'`.
- List the content of the selected folder: name, icon based on MIME type,
  size, last modified date.
- Download a binary file (streamed directly to the browser, no copy stored
  on the Dolibarr server).
- Upload a file into the currently displayed folder.
- Delete = move to Drive trash (`trashed = true`). Never a permanent,
  irreversible delete from this UI.
- Rename a file or a folder.
- Native Google files (Docs, Sheets, Slides, Forms — mimeType
  `application/vnd.google-apps.*` with no directly downloadable binary
  content): no download button, an "Open in Drive" link (`webViewLink`) is
  shown instead.
- Folder creation: out of scope (not explicitly requested).

## Out of scope

- No local caching of the Drive tree (always queried live via the API —
  consistent with adding no new SQL table).
- No link with the `gcloud` module (no dependency, no code reuse from it).
- No shared/company Drive account: each Dolibarr user sees their own Drive,
  with their own OAuth token.
- No in-page file preview (download / external link only).

## Architecture

Everything is done by extending the existing `googleapi` module, without
modifying any Dolibarr core file (`ecm/index.php`, `core/lib/ecm.lib.php`
stay unchanged).

### 1. Tab declaration

In `htdocs/custom/googleapi/core/modules/modGoogleApi.class.php`, the
`$this->tabs` property (currently empty) gets one entry using the standard
`complete_head_from_modules` mechanism (already called by
`ecm_prepare_dasboard_head()` in `core/lib/ecm.lib.php`, no change needed
there):

```php
$this->tabs[] = array(
    'data' => 'ecm:+googledrive:GoogleApiDriveTab:googleapi@googleapi:$user->rights->googleapi->read:/googleapi/ecmgoogledrive.php'
);
```

The permission gating the tab is the module's existing `read` right
(`$this->rights_class = 'googleapi'`), no new right is created.

### 2. Tab page

New file `htdocs/custom/googleapi/ecmgoogledrive.php`:

- Loads the Dolibarr environment, checks `$user->hasRight('googleapi', 'read')`.
- Calls `ecm_prepare_dasboard_head()` (existing core function) to get the
  same tabs array as `ecm/index.php`, and renders it with
  `dol_get_fiche_head()` — guarantees the same tab bar as seen from
  `ecm/index.php`.
- Retrieves the Google client via `getGoogleApiClient($user)` (existing
  function from `googleapi/lib/googleapi.lib.php`).
  - If `$client` is `false` (no token / refresh failed): shows an
    informational message and a "Connect to Google Drive" button pointing
    to `core/modules/oauth/googleapi_oauthcallback.php` with `backtourl`
    set to `ecmgoogledrive.php`. End of page.
  - Otherwise: two-column layout, visually consistent with the native ECM
    area — folder tree on the left (`jqueryFileTree` JS plugin, already used
    by `ecm/index.php`, pointed at our own AJAX endpoints instead of
    `core/ajax/ajaxdirtree.php`), content of the selected folder on the
    right.
- Actions section (top of file, before the HTML) handles the
  upload/rename/delete actions submitted as POST from this same page
  (classic form, consistent with `ecm/index.php`'s style), with CSRF token
  verification (`newToken()`).

### 3. AJAX endpoints

Folder `htdocs/custom/googleapi/ajax/`:

- `ecmgoogledrivetree.php`: receives a `dir` (Drive folder ID, empty means
  `root`), returns the sub-folders as JSON
  (`files.list` with `q = "'<parentId>' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false"`),
  in the format expected by `jqueryFileTree`.
- `ecmgoogledrivefiles.php`: receives a `dir`, returns the HTML of the
  content table of the selected folder — **sub-folders and files together**
  (like a classic file manager; the left-hand tree stays folders-only, for
  quick navigation). Each row: icon based on mimeType (or folder icon),
  name, size (`size`, empty for a folder), modified date (`modifiedTime`),
  Rename / Delete buttons, plus Download for files (or "Open in Drive" link
  for native Google types). Clicking a folder row navigates into it
  (updates the right-hand panel and selects the matching node in the
  left-hand tree).
- A download endpoint (e.g. `googleapi/document.php?fileid=...`) that checks
  permissions, calls `files->get($fileId, ['alt' => 'media'])` (or
  `files->export()` for the rare case a native type is still offered as an
  export), and streams the content with the correct `Content-Type` /
  `Content-Disposition` headers.

### 4. Drive API calls used

- List folder content: `files->listFiles(['q' => ..., 'fields' => 'files(id,name,mimeType,size,modifiedTime,iconLink,webViewLink)', 'orderBy' => 'folder,name'])`.
- Upload: `files->create(new DriveFile(['name' => ..., 'parents' => [$parentId]]), ['data' => ..., 'mimeType' => ..., 'uploadType' => 'multipart'])`.
- Rename: `files->update($fileId, new DriveFile(['name' => $newName]))`.
- Delete (trash): `files->update($fileId, new DriveFile(['trashed' => true]))`.
- Download: `files->get($fileId, ['alt' => 'media'])`.

## Security

- Existing Dolibarr rights reused: `read` for browsing/downloading, `write`
  for upload/rename, `delete` for deletion (move to trash). No new right
  added to the module.
- Every mutating action (upload, rename, delete) checks the Dolibarr CSRF
  token (`newToken()` / standard POST action check).
- Drive folder/file IDs interpolated into an API `q=` query are escaped
  (single quotes escaped) before the query string is built.
- No Drive data is written to the Dolibarr server's disk: download is
  streamed directly to the HTTP response, upload reads the standard PHP
  temporary upload file (`$_FILES`) and sends it straight to the API with
  no persistent intermediate copy.
- Per-user OAuth (existing mechanism, unchanged): no Drive account is shared
  between Dolibarr users.

## Internationalisation

New keys in `googleapi/langs/{en_US,fr_FR}/googleapi.lang`:
`GoogleApiDriveTab`, `GoogleApiDriveNotConnected`, `GoogleApiConnectDrive`,
plus action labels (Download/Rename/Delete) if not already covered by
existing generic Dolibarr keys (`Download`, `Rename`, `Delete`).

## Error handling

- Missing token or failed refresh: informational message + connect button,
  no fatal error.
- Failed Drive API call (`Google\Service\Exception` or other): caught,
  `setEventMessages($e->getMessage(), null, 'errors')`, empty list shown,
  page stays usable.

## Testing

No automated PHPUnit suite: the feature entirely depends on the real Google
Drive API (no test double available in this project for the Google API).
Manual browser validation once implemented:

1. OAuth connection from the tab (if no token yet).
2. Navigate the tree (several folder levels).
3. Correct listing of the root folder and of a sub-folder.
4. Download a binary file.
5. Upload a file into the current folder, verify it appears on Google
   Drive.
6. Rename a file.
7. Delete a file, verify it appears in the Google Drive trash (not
   permanently deleted).
8. Native Google Docs file case: "Open in Drive" link shown instead of the
   download button.
