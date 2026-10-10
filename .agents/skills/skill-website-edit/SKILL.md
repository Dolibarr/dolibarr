---
name: skill-website-edit
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

1. Identify the website and the page (see queries above).
2. To change the content of a page, edit the HTML stored in the `content` column of `llx_website_page`:

```sql
UPDATE llx_website_page SET content = '...new html...' WHERE rowid = <pageid>;
```

3. The content is the HTML of the page body. Keep the existing HTML structure (containers, CSS classes) — do not redesign or reformat the whole page, apply the minimal change asked for.

## Regenerating the .tpl.php file

The `content` column alone is not rendered directly. Each page has a generated file:

```
documents/website/<site-ref>/page<pageid>.tpl.php
```

- `<site-ref>` is the `ref` of the website (directory `documents/website/<ref>/`).
- `documents` is the Dolibarr documents root (`$dolibarr_main_data_root` from `htdocs/conf/conf.php`). With multi-entity enabled, the path is `documents/<entity>/website/<site-ref>/`.
- After any change to the `content` column, this file MUST be regenerated, otherwise the change is not visible on the rendered site.

Important: the .tpl.php file is NOT a raw copy of the `content` column. It is built by `dolSavePageContent()` (in `htdocs/core/lib/website2.lib.php`), which wraps the page content with a PHP header, the page `htmlheader`, the website CSS and other PHP code. So never write the `content` value raw into the .tpl.php file.

Two ways to regenerate the file:

1. Preferred, through Dolibarr code: call `dolSavePageContent($filetpl, $website, $objectpage, 1)` with the website object and the page object loaded from database, where `$filetpl = $dolibarr_main_data_root.'/website/'.$website->ref.'/page'.$pageid.'.tpl.php'`. This is exactly what the website editor does when saving a page. It can also be done by saving the page from the website editor UI (Website menu -> site -> edit page -> Save).
2. Only if Dolibarr code cannot be executed: archive the old file first (see below), then regenerate the file with the same structure as the previous version, replacing only the part that carries the page content with the new value from the `content` column.

## Archiving the previous version

Before rewriting `page<pageid>.tpl.php`, the existing file MUST be archived as:

```
documents/website/<site-ref>/page<pageid>.tpl.php.v<TIMESTAMP>
```

- `<TIMESTAMP>` is the unix timestamp of the moment of archiving (this is the convention of `archiveOrBackupFile()` in `htdocs/core/lib/files.lib.php`, which moves — not copies — the old file and keeps the last 5 versions only).
- Never overwrite or delete the old .tpl.php without creating this `.v<TIMESTAMP>` version file first.

## Verification

After an edit, check that:

1. The `content` column of `llx_website_page` contains the new HTML.
2. `documents/website/<site-ref>/page<pageid>.tpl.php` has been rewritten (check its modification time).
3. The previous version exists as `page<pageid>.tpl.php.v<TIMESTAMP>`.

If the site is served through the alias/container mode, also check that the alias file `documents/website/<site-ref>/<pageurl>.php` still points to the right tpl file (it should not need to be regenerated when only the content changes).
