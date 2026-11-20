---
name: skill-doli-website-edit
description:
  Handles instructions to edit a Dolibarr website loaded into the database.
  Use when the user tries to modify a website, a website template or the content of a website page.
license: MIT
user-invocable: true
---


# Skill: Handle Dolibarr website edit

A Dolibarr website (module Website) is loaded into the database. It is NOT a set of static files: the source of truth is the database, and the files under `documents/website/` are only generated artifacts.


## Where the data lives

- `llx_website`: the websites. Relevant columns: `rowid`, `ref` (site name, used as directory name), `lang`, `fk_default_home`, `otherjs`, `othercss`.
- `llx_website_page`: the pages of each website. Relevant columns: `rowid`, `fk_website`, `pageurl`, `title`, `lang`, `type_container`, `content` (the HTML of the page), `htmlheader` (the `<head>` part).

To find the site and the page to edit:

```sql
SELECT rowid, ref FROM llx_website WHERE ref = 'mywebsite';
SELECT rowid, pageurl, title FROM llx_website_page WHERE fk_website = <siteid>;
```


## Editing a page

### Storage of page content

1. Identify the website and the page (see queries above).
2. To change the content of a page, edit the HTML stored in the `content` column of `llx_website_page`:

```sql
UPDATE llx_website_page SET content = '...new html...' WHERE rowid = <pageid>;
```

3. The content is the HTML of the page body. Keep the existing HTML structure (containers, CSS classes) — do not redesign or reformat the whole page, apply the minimal change asked for.

### Page format

* The `type_container` column can be `page` (common page), `banner` if this is just a part of a page included into a main page, `blogpost` if the page is a news article (fulle page or included into another page),
  or `library` if page contains only PHP functions, or `setup` for a page never shown to visitor but used to offer a form to setup the website, or `other` (usually a part of page included into a main `page` type page)
* The content of each page must be organized using `<section conteneditable="true" id="a unique id">` tags to separate each independant block of content.
* When generating the text of an article to publish (for example a `blogpost`), add a few emojis to make it livelier (a few only, related to the topic, not one per sentence).


## Images and documents (medias directory)

Images and documents shown by the website are stored in the public medias directory (`documents/medias/`), not in the page content.
Manage them with the MCP tools `api_documents_medias_*` (paths are always relative to the medias directory):

- `api_documents_medias_list`: list a directory. Params: `subdir` (e.g. `image/mywebsite`, empty for the root), `recursive` (0|1), `search`, `sortfield` (`name`|`date`|`size`), `sortorder` (`asc`|`desc`). Use it to check whether a file already exists before uploading it or linking it.
- `api_documents_medias_download`: get the content (base64) of one small file. Param: `original_file` (e.g. `image/mywebsite/picture.png`).
- `api_documents_medias_upload`: upload a file. Params: `filename`, `subdir`, `filecontent` (base64) with `fileencoding='base64'`, `overwriteifexists`, `createdirifnotexists` (set it to 1 if the directory may not exist yet). The user is asked to confirm before the file is written. The file becomes publicly reachable.

Conventions for the website `mywebsite` (use the `ref` of the website):

- Images go into `image/mywebsite/`. To show one in a page, insert into the `content`:
  `<img src="/medias/image/mywebsite/filename.ext">`
- Documents (pdf, ...) go into `doc/mywebsite/`. To offer a download link, insert into the `content`:
  `<a href="/document.php?modulepart=medias&file=doc/mywebsite/filename.ext">Download</a>`
  (the `file` parameter is the path relative to the medias directory, so it must match the `subdir` used for the upload).


## Regenerating the page on disk (.tpl.php file)

Each page is rendered from a generated file `documents/website/<site-ref>/page<pageid>.tpl.php` (`documents/<entity>/...` with multi-entity), built by `dolSavePageContent()` (in `htdocs/core/lib/website2.lib.php`), not a raw copy of the `content` column.

- When the page is created or updated with the MCP tools `api_websites_pages_create` / `api_websites_pages_update` (or from the website editor UI), this file is regenerated automatically: nothing more to do.
- Only if the `content` column was changed directly in database by SQL, regenerate it by calling `dolSavePageContent($filetpl, $website, $objectpage, 1)` (it archives the old file, see below). Never write the `content` value raw into the .tpl.php file.


## Archiving the previous version

The previous `page<pageid>.tpl.php` is archived as `page<pageid>.tpl.php.v<TIMESTAMP>` (unix timestamp, last 5 versions kept) by `dolSavePageContent()` when called with `$backupold = 1`, through `archiveOrBackupFile()` (in `htdocs/core/lib/files.lib.php`).

- The tools `api_websites_pages_create` / `api_websites_pages_update` and the website editor UI already do it: nothing more to do.
- Only if the `content` column was changed directly by SQL, call `dolSavePageContent()` with `$backupold = 1`. Never overwrite or delete the old .tpl.php without this version file.


## Verification

After an edit, check with `api_websites_pages_get` that the page contains the new HTML.


## Saving website as a template in GIT sources

When saving a website as a template into the sources of the git repository, the content (the columns) of the table `llx_website_page` are saved
as SQL INSERT instructions int file doctemplates/websites/website_template-<websitekey>/website_pages.sql. The content of the directories medias/(image|js)/websitekey is saved into doctemplates/websites/website_template-<websitekey>/medias
and all the .html/.txt/.php files that are not pages are saved into doctemplates/websites/website_template-<websitekey>/containers/
