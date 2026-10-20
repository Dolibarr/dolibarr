---
name: skill-doli-translation
description: "Use when asking question on translation or synchronizing translations with Transifex (running the scripts dev/translation/txpull.sh and dev/translation/txpush.sh). Explains the Transifex workflow: en_US is the only editable source, all other locales are pulled from Transifex."
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - bash
 - grep
---

# Translations management with Transifex (DoliMed)

## Golden rule

- **Only the `en_US` language files are edited by hand**: `htdocs/`projectname`/langs/en_US/*.lang`.
- All other locales (`fr_FR`, `de_DE`, ...) are **never edited manually** in this repository. They are produced and synchronized by Transifex (external tool). This is the rule stated in the project AGENTS.md ("Internationalisation" section) and it overrides any request to "also add the French translation".
- When a new key is needed:
  1. Add it only to `htdocs/`projectname`/langs/en_US/<file>.lang` (PascalCase key names, `Key=English value`).
  2. Push the source to Transifex with `txpush.sh` (translators translate it there).
  3. Pull the translated key back into the locale files with `txpull.sh`.
- If a French/other-locale string is needed immediately before a pull, it is acceptable to add it manually, but be aware that the next Transifex pull may overwrite it — the authoritative flow remains push/pull.

## Transifex project layout

- Organization / project / resources are defined in `.tx/config` at the repository root.
  - Organization: `dolicloud`, project: `dolimed`.
  - One resource per `.lang` file: `r:admin`, `r:cabinetmed`, etc.
  - For each resource:
    - `source_file = htdocs/`projectname`/langs/en_US/<file>.lang` (the reference language)
    - `file_filter = htdocs/`projectname`/langs/<lang>/<file>.lang` (where translated files are written)
    - `type = MOZILLAPROPERTIES` (the .lang format `key=value`)
- The scripts refuse to run if `.tx/` does not exist at the repository root (they assume the root as working directory).
- Transifex client installation / update (see `dev/translation/README`): `sudo pip install --upgrade transifex-client`
- The direct transifex URL to make translations is: https://app.transifex.com/`organizationname`/`projectname`/translate/

## dev/translation/txpush.sh — push local files to Transifex

Usage (from the repository root):

```
./dev/translation/txpush.sh (source|xx_XX) [-r dolimed.<file>] [-f] [--no-interactive]
```

- `txpush.sh source` → runs `tx push -s`: pushes the en_US source files to Transifex. **This is the command to run after editing en_US files.**
- `txpush.sh fr_FR` (or any `xx_XX`) → loops over `htdocs/`projectname`/langs/xx_XX/*.lang` and runs `tx push --skip -r dolimed.<file> -t -l xx_XX` for each: pushes a local translation file into Transifex as a translation. Only used to feed Transifex with a locally-corrected translation.
- A local translation file is skipped if the Transifex side is newer. `-f` forces the overwrite of the translation on Transifex (but not the translation memory).
- `-r dolimed.<file>` limits the push to one resource, e.g. `-r dolimed.cabinetmed`.

## dev/translation/txpull.sh — pull translated files from Transifex

Usage (from the repository root):

```
./dev/translation/txpull.sh (all|xx_XX) [-r dolimed.<file>] [-f] [-s]
```

- `txpull.sh all` → loops over every directory in `htdocs/`projectname`/langs/*` except `en_US` and runs `tx pull -l <lang>` for each: downloads all translated files from Transifex into the local tree.
- `txpull.sh fr_FR` (or any `xx_XX`) → `tx pull -l xx_XX`: downloads only that language.
- A local file is skipped if the local copy is newer than the Transifex one. `-f` forces the local file to be overwritten (does not work with `all`).
- `-r dolimed.<file>` limits the pull to one resource.
- The script ends by reminding to also run: `dev/fixaltlanguages.sh fix all` (see below).

## Complete workflow for a new translation key

Example: add the key `DossierMedical=Dossier médical` feature string.

1. Add the English key to `htdocs/`projectname`/langs/en_US/`projectname`.lang`.
2. Commit/push the en_US change following the project git workflow.
3. Run `./dev/translation/txpush.sh source -r projectname.modulename` (or without `-r` for all files).
4. Wait for translators (or the translation memory) to translate it on Transifex.
5. Run `./dev/translation/txpull.sh fr_FR` (or `all`) to get the translated files.
6. Run `dev/fixaltlanguages.sh fix all` to deduplicate entries that already exist in the parent language file.

## Related tools in dev/translation/

- `README` — installation of the Transifex client (`transifex-client` pip package).
- `strip_language_file.php <primary_lang_dir> <secondary_lang_dir> [file.lang|all]` — strips from a secondary language file the entries redundant with its primary file (run from `htdocs/langs`).
- `sanity_check_en_langfiles.php` — HTML report checking the en_US language files.
- `autotranslator.php` — automatic translation helper.
- `../fixaltlanguages.sh (list|fix) (all|file.lang) [xx_XX]` — deduplicates alternate language files; recommended by txpull.sh after each pull.
- `../fixduplicatelangkey.sh` — removes duplicate keys inside language files.

## Dolibarr i18n reminders (apply when writing keys)

- Keys use PascalCase (`MyModuleLabel`), values are English text.
- `$langs->trans('Key')` for direct HTML output, `$langs->transnoentities('Key')` when the result feeds an escaping/encoding function (PDF, emails).
- Language file loaded with `$langs->loadLangs(array("main", "cabinetmed@cabinetmed"))`.
- No hardcoded user-facing strings in PHP — always a trans key.
- Locale files other than en_US must keep `CHARSET=UTF-8` on their first line.
