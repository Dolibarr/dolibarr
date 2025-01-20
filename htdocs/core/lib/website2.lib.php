<?php
/* Copyright (C) 2017 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *      \file       htdocs/core/lib/website2.lib.php
 *      \ingroup    website
 *      \brief      Library for website module (rare functions not required for execution of website)
 */


/**
 * Save content of a page on disk
 *
 * @param	string		$filemaster			Full path of filename master.inc.php for website to generate
 * @return	boolean							True if OK
 */
function dolSaveMasterFile($filemaster)
{
	// Now generate the master.inc.php page
	dol_syslog("We regenerate the master.inc.php file");

	dol_delete_file($filemaster);

	$mastercontent = '<?php'."\n";
	$mastercontent .= '// File generated to link to the master file - DO NOT MODIFY - It is just an include'."\n";
	$mastercontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) {\n";
	$mastercontent .= "    if (! defined('USEEXTERNALSERVER')) define('USEEXTERNALSERVER', 1);\n";
	$mastercontent .= "    require_once '".DOL_DOCUMENT_ROOT."/master.inc.php';\n";
	$mastercontent .= "}\n";
	$mastercontent .= '?>'."\n";
	$result = file_put_contents($filemaster, $mastercontent);
	dolChmod($filemaster);

	return $result;
}

/**
 * Save an alias page on disk (A page that include the reference page).
 * It saves file into the root directory but also into language subdirectory.
 *
 * @param	string		$filealias			Full path of filename to generate
 * @param	Website		$object				Object website
 * @param	WebsitePage	$objectpage			Object websitepage
 * @return	boolean							True if OK
 * @see dolSavePageContent()
 */
function dolSavePageAlias($filealias, $object, $objectpage)
{
	// Now create the .tpl file
	dol_syslog("dolSavePageAlias We regenerate the alias page filealias=".$filealias." and a wrapper into all language subdirectories");

	$aliascontent = '<?php'."\n";
	$aliascontent .= "// File generated to wrap the alias page - DO NOT MODIFY - It is just a wrapper to real page\n";
	$aliascontent .= 'global $dolibarr_main_data_root;'."\n";
	$aliascontent .= 'if (empty($dolibarr_main_data_root)) require \'./page'.$objectpage->id.'.tpl.php\'; ';
	$aliascontent .= 'else require $dolibarr_main_data_root.\'/website/\'.$website->ref.\'/page'.$objectpage->id.'.tpl.php\';'."\n";
	$aliascontent .= '?>'."\n";
	$result = file_put_contents($filealias, $aliascontent);
	if ($result === false) {
		dol_syslog("Failed to write file ".$filealias, LOG_WARNING);
	}
	dolChmod($filealias);

	// Save also alias into language subdirectory if it is not a main language
	if ($objectpage->lang && in_array($objectpage->lang, explode(',', $object->otherlang))) {
		$dirname = dirname($filealias);
		$filename = basename($filealias);
		$filealiassub = $dirname.'/'.$objectpage->lang.'/'.$filename;

		dol_mkdir($dirname.'/'.$objectpage->lang, DOL_DATA_ROOT);

		$aliascontent = '<?php'."\n";
		$aliascontent .= "// File generated to wrap the alias page - DO NOT MODIFY - It is just a wrapper to real page\n";
		$aliascontent .= 'global $dolibarr_main_data_root;'."\n";
		$aliascontent .= 'if (empty($dolibarr_main_data_root)) require \'../page'.$objectpage->id.'.tpl.php\'; ';
		$aliascontent .= 'else require $dolibarr_main_data_root.\'/website/\'.$website->ref.\'/page'.$objectpage->id.'.tpl.php\';'."\n";
		$aliascontent .= '?>'."\n";
		$result = file_put_contents($filealiassub, $aliascontent);
		if ($result === false) {
			dol_syslog("Failed to write file ".$filealiassub, LOG_WARNING);
		}
		dolChmod($filealiassub);
	} elseif (empty($objectpage->lang) || !in_array($objectpage->lang, explode(',', $object->otherlang))) {
		// Save also alias into all language subdirectories if it is a main language
		if (!getDolGlobalString('WEBSITE_DISABLE_MAIN_LANGUAGE_INTO_LANGSUBDIR') && !empty($object->otherlang)) {
			$dirname = dirname($filealias);
			$filename = basename($filealias);
			foreach (explode(',', $object->otherlang) as $sublang) {
				// Avoid to erase main alias file if $sublang is empty string
				if (empty(trim($sublang))) {
					continue;
				}
				$filealiassub = $dirname.'/'.$sublang.'/'.$filename;

				$aliascontent = '<?php'."\n";
				$aliascontent .= "// File generated to wrap the alias page - DO NOT MODIFY - It is just a wrapper to real page\n";
				$aliascontent .= 'global $dolibarr_main_data_root;'."\n";
				$aliascontent .= 'if (empty($dolibarr_main_data_root)) require \'../page'.$objectpage->id.'.tpl.php\'; ';
				$aliascontent .= 'else require $dolibarr_main_data_root.\'/website/\'.$website->ref.\'/page'.$objectpage->id.'.tpl.php\';'."\n";
				$aliascontent .= '?>'."\n";

				dol_mkdir($dirname.'/'.$sublang);
				$result = file_put_contents($filealiassub, $aliascontent);
				if ($result === false) {
					dol_syslog("Failed to write file ".$filealiassub, LOG_WARNING);
				}
				dolChmod($filealiassub);
			}
		}
	}

	return ($result ? true : false);
}


/**
 * Save content of a page on disk (page name is generally ID_of_page.php).
 * Page contents are always saved into "root" directory. Only aliases pages saved with dolSavePageAlias() can be in root or language subdir.
 *
 * @param	string		$filetpl			Full path of filename to generate
 * @param	Website		$object				Object website
 * @param	WebsitePage	$objectpage			Object websitepage
 * @param	int			$backupold			1=Make a backup of old page
 * @return	boolean							True if OK
 * @see dolSavePageAlias()
 */
function dolSavePageContent($filetpl, Website $object, WebsitePage $objectpage, $backupold = 0)
{
	global $db;

	// Now create the .tpl file (duplicate code with actions updatesource or updatecontent but we need this to save new header)
	dol_syslog("dolSavePageContent We regenerate the tpl page filetpl=".$filetpl);

	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	if (dol_is_file($filetpl)) {
		if ($backupold) {
			$result = archiveOrBackupFile($filetpl);
			if (! $result) {
				return false;
			}
		} else {
			dol_delete_file($filetpl);
		}
	}

	$shortlangcode = '';
	if ($objectpage->lang) {
		$shortlangcode = substr($objectpage->lang, 0, 2); // en_US or en-US -> en
	}
	if (empty($shortlangcode)) {
		// Take the language of website
		$shortlangcode = substr($object->lang, 0, 2); // en_US or en-US -> en
	}

	if (!empty($objectpage->type_container) && in_array($objectpage->type_container, array('library', 'service'))) {
		$originalcontentonly = 1;
	}

	$tplcontent = '';
	if (!isset($originalcontentonly)) {
		$tplcontent .= "<?php // BEGIN PHP\n";
		$tplcontent .= '$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;'."\n";
		$tplcontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) {\n";
		$tplcontent .= '	$pathdepth = count(explode(\'/\', $_SERVER[\'SCRIPT_NAME\'])) - 2;'."\n";
		$tplcontent .= '	require_once ($pathdepth ? str_repeat(\'../\', $pathdepth) : \'./\').\'master.inc.php\';'."\n";
		$tplcontent .= "} // Not already loaded\n";
		$tplcontent .= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
		$tplcontent .= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
		$tplcontent .= "ob_start();\n";
		$tplcontent .= "// END PHP ?>\n";
		if (getDolGlobalString('WEBSITE_FORCE_DOCTYPE_HTML5')) {
			$tplcontent .= "<!DOCTYPE html>\n";
		}
		// If a language was forced on page, we use it, else we use the lang of visitor else the lang of web site
		$tplcontent .= '<html'.($objectpage->lang ? ' lang="'.substr($objectpage->lang, 0, 2).'"' : '<?php echo $weblangs->shortlang ? \' lang="\'.$weblangs->shortlang.\'"\' : \'\' ?>').'>'."\n";
		$tplcontent .= '<head>'."\n";
		$tplcontent .= '<title>'.dol_string_nohtmltag($objectpage->title, 0, 'UTF-8').'</title>'."\n";
		$tplcontent .= '<meta charset="utf-8">'."\n";
		$tplcontent .= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'."\n";
		$tplcontent .= '<meta name="robots" content="index, follow" />'."\n";
		$tplcontent .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
		$tplcontent .= '<meta name="keywords" content="'.dol_string_nohtmltag($objectpage->keywords).'" />'."\n";
		$tplcontent .= '<meta name="title" content="'.dol_string_nohtmltag($objectpage->title, 0, 'UTF-8').'" />'."\n";
		$tplcontent .= '<meta name="description" content="'.dol_string_nohtmltag($objectpage->description, 0, 'UTF-8').'" />'."\n";
		$tplcontent .= '<meta name="generator" content="'.DOL_APPLICATION_TITLE.' '.DOL_VERSION.' (https://www.dolibarr.org)" />'."\n";
		$tplcontent .= '<meta name="dolibarr:pageid" content="'.dol_string_nohtmltag((string) $objectpage->id).'" />'."\n";

		// Add favicon
		if (in_array($objectpage->type_container, array('page', 'blogpost'))) {
			$tplcontent .= '<link rel="icon" type="image/png" href="/favicon.png" />'."\n";
		}

		// Add canonical reference
		if ($object->virtualhost) {
			$tplcontent .= '<link rel="canonical" href="'.(($objectpage->id == $object->fk_default_home) ? '/' : (($shortlangcode != substr($object->lang, 0, 2) ? '/'.$shortlangcode : '').'/'.$objectpage->pageurl.'.php')).'" />'."\n";
		}
		// Add translation reference (main language)
		if ($object->isMultiLang()) {
			// Add page "translation of"
			$translationof = $objectpage->fk_page;
			if ($translationof) {
				$tmppage = new WebsitePage($db);
				$tmppage->fetch($translationof);
				if ($tmppage->id > 0) {
					$tmpshortlangcode = '';
					if ($tmppage->lang) {
						$tmpshortlangcode = preg_replace('/[_-].*$/', '', $tmppage->lang); // en_US or en-US -> en
					}
					if (empty($tmpshortlangcode)) {
						$tmpshortlangcode = preg_replace('/[_-].*$/', '', $object->lang); // en_US or en-US -> en
					}
					if ($tmpshortlangcode != $shortlangcode) {
						$tplcontent .= '<link rel="alternate" hreflang="'.$tmpshortlangcode.'" href="<?php echo $website->virtualhost; ?>'.($object->fk_default_home == $tmppage->id ? '/' : (($tmpshortlangcode != substr($object->lang, 0, 2)) ? '/'.$tmpshortlangcode : '').'/'.$tmppage->pageurl.'.php').'" />'."\n";
					}
				}
			}

			// Add "has translation pages"
			$sql = "SELECT rowid as id, lang, pageurl from ".MAIN_DB_PREFIX.'website_page where fk_page IN ('.$db->sanitize($objectpage->id.($translationof ? ", ".$translationof : '')).")";
			$resql = $db->query($sql);
			if ($resql) {
				$num_rows = $db->num_rows($resql);
				if ($num_rows > 0) {
					while ($obj = $db->fetch_object($resql)) {
						$tmpshortlangcode = '';
						if ($obj->lang) {
							$tmpshortlangcode = preg_replace('/[_-].*$/', '', $obj->lang); // en_US or en-US -> en
						}
						if ($tmpshortlangcode != $shortlangcode) {
							$tplcontent .= '<link rel="alternate" hreflang="'.$tmpshortlangcode.'" href="<?php echo $website->virtualhost; ?>'.($object->fk_default_home == $obj->id ? '/' : (($tmpshortlangcode != substr($object->lang, 0, 2) ? '/'.$tmpshortlangcode : '')).'/'.$obj->pageurl.'.php').'" />'."\n";
						}
					}
				}
			} else {
				dol_print_error($db);
			}

			// Add myself
			$tplcontent .= '<?php if ($_SERVER["PHP_SELF"] == "'.(($object->fk_default_home == $objectpage->id) ? '/' : (($shortlangcode != substr($object->lang, 0, 2)) ? '/'.$shortlangcode : '')).'/'.$objectpage->pageurl.'.php") { ?>'."\n";
			$tplcontent .= '<link rel="alternate" hreflang="'.$shortlangcode.'" href="<?php echo $website->virtualhost; ?>'.(($object->fk_default_home == $objectpage->id) ? '/' : (($shortlangcode != substr($object->lang, 0, 2)) ? '/'.$shortlangcode : '').'/'.$objectpage->pageurl.'.php').'" />'."\n";

			$tplcontent .= '<?php } ?>'."\n";
		}
		// Add manifest.json. Do we have to add it only on home page ?
		$tplcontent .= '<?php if ($website->use_manifest) { print \'<link rel="manifest" href="/manifest.json.php" />\'."\n"; } ?>'."\n";
		$tplcontent .= '<!-- Include link to CSS file -->'."\n";
		// Add js
		$tplcontent .= '<link rel="stylesheet" href="/styles.css.php?website=<?php echo $websitekey; ?>" type="text/css" />'."\n";
		$tplcontent .= '<!-- Include link to JS file -->'."\n";
		$tplcontent .= '<script nonce="'.getNonce().'" async src="/javascript.js.php?website=<?php echo $websitekey; ?>"></script>'."\n";
		// Add headers
		$tplcontent .= '<!-- Include HTML header from common file -->'."\n";
		$tplcontent .= '<?php if (file_exists(DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html")) include DOL_DATA_ROOT."/website/".$websitekey."/htmlheader.html"; ?>'."\n";
		$tplcontent .= '<!-- Include HTML header from page header block -->'."\n";
		$tplcontent .= preg_replace('/<\/?html>/ims', '', $objectpage->htmlheader)."\n";
		$tplcontent .= '</head>'."\n";

		$tplcontent .= '<!-- File generated by Dolibarr website module editor -->'."\n";
		$tplcontent .= '<body id="bodywebsite" class="bodywebsite bodywebpage-'.$objectpage->ref.'">'."\n";
		$tplcontent .= $objectpage->content."\n";
		$tplcontent .= '</body>'."\n";
		$tplcontent .= '</html>'."\n";

		$tplcontent .= '<?php // BEGIN PHP'."\n";
		$tplcontent .= '$tmp = ob_get_contents(); ob_end_clean();'."\n";
		if (strpos($objectpage->content, '$__PAGE__TITLE__') !== false) {
			$tplcontent .= '$tmp = preg_replace("/<title>.*?<\/title>/s", "<title>" . dol_escape_htmltag($__PAGE__TITLE__) . "</title>", $tmp);'."\n";
			$tplcontent .= '$tmp = preg_replace("/<meta name=\"title\" content=\".*?\" \/>/s", "<meta name=\"title\" content=\"" . dol_string_nohtmltag($__PAGE__TITLE__) . "\"  />", $tmp);';
		}
		if (strpos($objectpage->content, '$__PAGE__KEYWORDS__') !== false) {
			$tplcontent .= '$tmp = preg_replace("/<meta name=\"keywords\" content=\".*?\" \/>/s", "<meta name=\"keywords\" content=\"" . dol_string_nohtmltag($__PAGE__KEYWORDS__) . "\"  />", $tmp);';
		}
		if (strpos($objectpage->content, '$__PAGE__DESC__') !== false) {
			$tplcontent .= '$tmp = preg_replace("/<meta name=\"description\" content=\".*?\" \/>/s", "<meta name=\"description\" content=\"" . dol_string_nohtmltag($__PAGE__DESC__) . "\"  />", $tmp);';
		}
		$tplcontent .= 'dolWebsiteOutput($tmp, "html", '.$objectpage->id.'); dolWebsiteIncrementCounter('.$object->id.', "'.$objectpage->type_container.'", '.$objectpage->id.');'."\n";
		$tplcontent .= "// END PHP ?>\n";
	} else {
		$tplcontent .= "<?php // BEGIN PHP\n";
		$tplcontent .= '$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;'."\n";
		$tplcontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) {\n";
		$tplcontent .= '	$pathdepth = count(explode(\'/\', $_SERVER[\'SCRIPT_NAME\'])) - 2;'."\n";
		$tplcontent .= '	require_once ($pathdepth ? str_repeat(\'../\', $pathdepth) : \'./\').\'master.inc.php\';'."\n";
		$tplcontent .= "} // Not already loaded\n";
		$tplcontent .= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
		$tplcontent .= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
		$tplcontent .= "// END PHP ?>\n";

		$tplcontent .= $objectpage->content;
	}

	//var_dump($filetpl);exit;
	$result = file_put_contents($filetpl, $tplcontent);

	dolChmod($filetpl);

	return $result;
}


/**
 * Save content of the index.php and/or the wrapper.php page
 *
 * @param	string		$pathofwebsite			Path of website root
 * @param	string		$fileindex				Full path of file index.php
 * @param	string		$filetpl				File tpl the index.php page redirect to (used only if $fileindex is provided)
 * @param	string		$filewrapper			Full path of file wrapper.php
 * @param	Website		$object					Object website
 * @return	boolean								True if OK
 */
function dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper, $object = null)
{
	global $db;

	$result1 = false;
	$result2 = false;

	dol_mkdir($pathofwebsite);

	if ($fileindex) {
		dol_delete_file($fileindex);
		$indexcontent = '<?php'."\n";
		$indexcontent .= "// BEGIN PHP File generated to provide an index.php as Home Page or alias redirector - DO NOT MODIFY - It is just a generated wrapper.\n";
		$indexcontent .= '$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;'."\n";
		$indexcontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Load master if not already loaded\n";
		$indexcontent .= 'if (!empty($_GET[\'pageref\']) || !empty($_GET[\'pagealiasalt\']) || !empty($_GET[\'pageid\'])) {'."\n";
		$indexcontent .= "	require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
		$indexcontent .= "	require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
		$indexcontent .= '	redirectToContainer($_GET[\'pageref\'], $_GET[\'pagealiasalt\'], $_GET[\'pageid\']);'."\n";
		$indexcontent .= "}\n";
		$indexcontent .= "include_once './".basename($filetpl)."'\n";
		$indexcontent .= '// END PHP ?>'."\n";

		$result1 = file_put_contents($fileindex, $indexcontent);

		dolChmod($fileindex);

		if (is_object($object) && $object->fk_default_home > 0) {
			$objectpage = new WebsitePage($db);
			$objectpage->fetch($object->fk_default_home);

			// Create a version for sublanguages
			if (empty($objectpage->lang) || !in_array($objectpage->lang, explode(',', $object->otherlang))) {
				if (!getDolGlobalString('WEBSITE_DISABLE_MAIN_LANGUAGE_INTO_LANGSUBDIR') && is_object($object) && !empty($object->otherlang)) {
					$dirname = dirname($fileindex);
					foreach (explode(',', $object->otherlang) as $sublang) {
						// Avoid to erase main alias file if $sublang is empty string
						if (empty(trim($sublang))) {
							continue;
						}
						$fileindexsub = $dirname.'/'.$sublang.'/index.php';

						// Same indexcontent than previously but with ../ instead of ./ for master and tpl file include/require_once.
						$relpath = '..';
						$indexcontent = '<?php'."\n";
						$indexcontent .= "// BEGIN PHP File generated to provide an index.php as Home Page or alias redirector - DO NOT MODIFY - It is just a generated wrapper.\n";
						$indexcontent .= '$websitekey=basename(__DIR__); if (empty($websitepagefile)) $websitepagefile=__FILE__;'."\n";
						$indexcontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once '".$relpath."/master.inc.php'; } // Load master if not already loaded\n";
						$indexcontent .= 'if (!empty($_GET[\'pageref\']) || !empty($_GET[\'pagealiasalt\']) || !empty($_GET[\'pageid\'])) {'."\n";
						$indexcontent .= "	require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
						$indexcontent .= "	require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
						$indexcontent .= '	redirectToContainer($_GET[\'pageref\'], $_GET[\'pagealiasalt\'], $_GET[\'pageid\']);'."\n";
						$indexcontent .= "}\n";
						$indexcontent .= "include_once '".$relpath."/".basename($filetpl)."'\n";	// use .. instead of .
						$indexcontent .= '// END PHP ?>'."\n";
						$result = file_put_contents($fileindexsub, $indexcontent);
						if ($result === false) {
							dol_syslog("Failed to write file ".$fileindexsub, LOG_WARNING);
						}
						dolChmod($fileindexsub);
					}
				}
			}
		}
	} else {
		$result1 = true;
	}

	if ($filewrapper) {
		dol_delete_file($filewrapper);
		$wrappercontent = file_get_contents(DOL_DOCUMENT_ROOT.'/website/samples/wrapper.php');

		$result2 = file_put_contents($filewrapper, $wrappercontent);
		dolChmod($filewrapper);
	} else {
		$result2 = true;
	}

	return ($result1 && $result2);
}


/**
 * Save content of a page on disk
 *
 * @param	string		$filehtmlheader		Full path of filename to generate
 * @param	string		$htmlheadercontent	Content of file
 * @return	boolean							True if OK
 */
function dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent)
{
	global $pathofwebsite;

	dol_syslog("Save html header into ".$filehtmlheader);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filehtmlheader, $htmlheadercontent);
	dolChmod($filehtmlheader);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filecss			Full path of filename to generate
 * @param	string		$csscontent			Content of file
 * @return	boolean							True if OK
 */
function dolSaveCssFile($filecss, $csscontent)
{
	global $pathofwebsite;

	dol_syslog("Save css file into ".$filecss);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filecss, $csscontent);
	dolChmod($filecss);

	return $result;
}

/**
 * Save content of a page on disk. For example into documents/website/mywebsite/javascript.js.php file.
 *
 * @param	string		$filejs				Full path of filename to generate
 * @param	string		$jscontent			Content of file
 * @return	boolean							True if OK
 */
function dolSaveJsFile($filejs, $jscontent)
{
	global $pathofwebsite;

	dol_syslog("Save js file into ".$filejs);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filejs, $jscontent);
	dolChmod($filejs);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filerobot			Full path of filename to generate
 * @param	string		$robotcontent		Content of file
 * @return	boolean							True if OK
 */
function dolSaveRobotFile($filerobot, $robotcontent)
{
	global $pathofwebsite;

	dol_syslog("Save robot file into ".$filerobot);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filerobot, $robotcontent);
	dolChmod($filerobot);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filehtaccess		Full path of filename to generate
 * @param	string		$htaccess			Content of file
 * @return	boolean							True if OK
 */
function dolSaveHtaccessFile($filehtaccess, $htaccess)
{
	global $pathofwebsite;

	dol_syslog("Save htaccess file into ".$filehtaccess);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filehtaccess, $htaccess);
	dolChmod($filehtaccess);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$file				Full path of filename to generate
 * @param	string		$content			Content of file
 * @return	boolean							True if OK
 */
function dolSaveManifestJson($file, $content)
{
	global $pathofwebsite;

	dol_syslog("Save manifest.js.php file into ".$file);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($file, $content);
	dolChmod($file);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$file				Full path of filename to generate
 * @param	string		$content			Content of file
 * @return	boolean							True if OK
 */
function dolSaveReadme($file, $content)
{
	global $pathofwebsite;

	dol_syslog("Save README.md file into ".$file);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($file, $content);
	dolChmod($file);

	return $result;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$file				Full path of filename to generate
 * @param	string		$content			Content of file
 * @return	boolean							True if OK
 */
function dolSaveLicense($file, $content)
{
	global $pathofwebsite;

	dol_syslog("Save LICENSE file into ".$file);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($file, $content);
	dolChmod($file);

	return $result;
}

/**
 * 	Show list of themes. Show all thumbs of themes/skins
 *
 *	@param	Website		$website		Object website to load the template into
 * 	@return	void
 */
function showWebsiteTemplates(Website $website)
{
	global $conf, $langs, $form, $user;

	// We want only one directory for dir of website templates. If an external module need to provide a template, the template must be copied into this directory
	// when module is enabled.
	$dirthemes = array('/doctemplates/websites');

	$colspan = 2;

	print '<!-- For website template import -->'."\n";
	print '<table class="noborder centpercent">';

	// Title
	print '<tr class="liste_titre"><th class="titlefield">';
	print $form->textwithpicto($langs->trans("Templates"), $langs->trans("ThemeDir").' : '.implode(", ", $dirthemes));
	print ' ';
	print '<a href="'.$_SERVER["PHP_SELF"].'?website='.urlencode($website->ref).'&importsite=1" rel="noopener noreferrer external">';
	print img_picto('', 'refresh');
	print '</a>';
	print '</th>';
	print '<th class="right">';
	$url = 'https://www.dolistore.com/43-web-site-templates';
	print '<a href="'.$url.'" target="_blank" rel="noopener noreferrer external">';
	print img_picto('', 'globe', 'class="pictofixedwidth"').$langs->trans('DownloadMoreSkins');
	print '</a>';
	print '</th></tr>';

	print '<tr><td colspan="'.$colspan.'">';

	print '<table class="nobordernopadding centpercent"><tr><td><div class="display-flex">';

	if (count($dirthemes)) {
		$i = 0;
		foreach ($dirthemes as $dir) {
			$dirtheme = DOL_DATA_ROOT.$dir;

			if (is_dir($dirtheme)) {
				$handle = opendir($dirtheme);
				if (is_resource($handle)) {
					while (($subdir = readdir($handle)) !== false) {
						//var_dump($dirtheme.'/'.$subdir);
						if (dol_is_file($dirtheme."/".$subdir) && substr($subdir, 0, 1) != '.' && substr($subdir, 0, 3) != 'CVS' && preg_match('/\.zip$/i', $subdir)) {
							$subdirwithoutzip = preg_replace('/\.zip$/i', '', $subdir);
							$subdirwithoutzipwithoutver = preg_replace('/(_exp|_dev)$/i', '', $subdirwithoutzip);

							// Disable not stable themes (dir ends with _exp or _dev)
							if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2 && preg_match('/_dev$/i', $subdirwithoutzip)) {
								continue;
							}
							if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1 && preg_match('/_exp$/i', $subdirwithoutzip)) {
								continue;
							}

							print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px; margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';

							$templatedir = $dirtheme."/".$subdir;
							$file = $dirtheme."/".$subdirwithoutzipwithoutver.".jpg";
							$url = DOL_URL_ROOT.'/viewimage.php?modulepart=doctemplateswebsite&file='.$subdirwithoutzipwithoutver.".jpg";

							if (!file_exists($file)) {
								$url = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
							}

							$originalimgfile = basename($file);
							$entity = $conf->entity;
							$modulepart = 'doctemplateswebsite';
							$cache = '';
							$title = $file;

							$ret = '';
							$urladvanced = getAdvancedPreviewUrl($modulepart, $originalimgfile, 1, '&entity='.$entity);
							if (!empty($urladvanced)) {
								$ret .= '<a class="'.$urladvanced['css'].'" target="'.$urladvanced['target'].'" mime="'.$urladvanced['mime'].'" href="'.$urladvanced['url'].'">';
							} else {
								$ret .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).'&entity='.((int) $entity).'&file='.urlencode($originalimgfile).'&cache='.((int) $cache).'">';
							}
							print $ret;
							print '<img class="img-skinthumb shadow" src="'.$url.'" border="0" alt="'.$title.'" title="'.$title.'" style="margin-bottom: 5px;">';
							print '</a>';

							print '<br>';
							print $subdir;
							print '<br>';
							print '<span class="opacitymedium">'.dol_print_size(dol_filesize($dirtheme."/".$subdir), 1, 1).' - '.dol_print_date(dol_filemtime($templatedir), 'dayhour', 'tzuserrel').'</span>';
							if ($user->hasRight('website', 'delete')) {
								print ' <a href="'.$_SERVER["PHP_SELF"].'?action=deletetemplate&token='.newToken().'&website='.urlencode($website->ref).'&templateuserfile='.urlencode($subdir).'">'.img_picto('', 'delete').'</a>';
							}
							print '<br><a href="'.$_SERVER["PHP_SELF"].'?action=importsiteconfirm&token='.newToken().'&website='.urlencode($website->ref).'&templateuserfile='.urlencode($subdir).'" class="button">'.$langs->trans("Load").'</a>';
							print '</div>';

							$i++;
						}
					}
					print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px;margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;"></div>';
					print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px;margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;"></div>';
					print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px;margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;"></div>';
					print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px;margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;"></div>';
					print '<div class="inline-block center flex-item" style="min-width: 250px; max-width: 400px;margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;"></div>';
				}
			}
		}
	} else {
		print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
	}

	print '</div></td></tr></table>';

	print '</td></tr>';
	print '</table>';
}


/**
 * Check a new string containing only php code (including <php tag)
 * - Block if user has no permission to change PHP code.
 * - Block also if bad code found in the new string.
 *
 * @param	string		$phpfullcodestringold		PHP old string (before the change). For example "<?php echo 'a' ?><php echo 'b' ?>"
 * @param	string		$phpfullcodestring			PHP new string. For example "<?php echo 'a' ?><php echo 'c' ?>"
 * @return	int										Error or not
 * @see dolKeepOnlyPhpCode(), dol_eval() to see sanitizing rules that should be very close.
 */
function checkPHPCode(&$phpfullcodestringold, &$phpfullcodestring)
{
	global $langs, $user;

	$error = 0;

	if (empty($phpfullcodestringold) && empty($phpfullcodestring)) {
		return 0;
	}

	// First check permission
	if ($phpfullcodestringold != $phpfullcodestring) {
		if (!$error && !$user->hasRight('website', 'writephp')) {
			$error++;
			setEventMessages($langs->trans("NotAllowedToAddDynamicContent"), null, 'errors');
		}
	}

	$phpfullcodestringnew = $phpfullcodestring;

	// Then check forbidden commands
	if (!$error) {
		if (getDolGlobalString("WEBSITE_DISALLOW_DOLLAR_UNDERSCORE")) {
			$phpfullcodestring = preg_replace('/\$_COOKIE\[/', '__DOLLARCOOKIE__', $phpfullcodestring);
			$phpfullcodestring = preg_replace('/\$_FILES\[/', '__DOLLARFILES__', $phpfullcodestring);
			$phpfullcodestring = preg_replace('/\$_SESSION\[/', '__DOLLARSESSION__', $phpfullcodestring);
			$forbiddenphpstrings = array('$$', '$_', '}[');
		} else {
			$forbiddenphpstrings = array('$$', '}[');
		}
		//$forbiddenphpstrings = array_merge($forbiddenphpstrings, array('_ENV', '_FILES', '_SESSION', '_COOKIE', '_GET', '_POST', '_REQUEST', 'ReflectionFunction'));
		$forbiddenphpstrings = array_merge($forbiddenphpstrings, array('_ENV', 'ReflectionFunction'));

		$forbiddenphpfunctions = array();
		//$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("base64"."_"."decode", "rawurl"."decode", "url"."decode", "str"."_rot13", "hex"."2bin")); // name of forbidden functions are split to avoid false positive
		$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("override_function", "session_id", "session_create_id", "session_regenerate_id"));
		$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("get_defined_functions", "get_defined_vars", "get_defined_constants", "get_declared_classes"));
		$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("call_user_func", "call_user_func_array"));
		//$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("require", "include", "require_once", "include_once"));
		if (!getDolGlobalString('WEBSITE_PHP_ALLOW_EXEC')) {    // If option is not on, we disallow functions to execute commands
			$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("exec", "passthru", "shell_exec", "system", "proc_open", "popen"));
			$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("dol_eval", "executeCLI", "verifCond"));	// native dolibarr functions
			$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("eval", "create_function", "assert", "mb_ereg_replace")); // function with eval capabilities
		}
		if (!getDolGlobalString('WEBSITE_PHP_ALLOW_WRITE')) {    // If option is not on, we disallow functions to write files
			$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("dol_compress_dir", "dol_decode", "dol_delete_file", "dol_delete_dir", "dol_delete_dir_recursive", "dol_copy", "archiveOrBackupFile")); // more dolibarr functions
			$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("fopen", "file_put_contents", "fputs", "fputscsv", "fwrite", "fpassthru", "mkdir", "rmdir", "symlink", "touch", "unlink", "umask"));
		}
		//$forbiddenphpfunctions = array_merge($forbiddenphpfunctions, array("require", "include"));

		$forbiddenphpmethods = array('invoke', 'invokeArgs');	// Method of ReflectionFunction to execute a function

		foreach ($forbiddenphpstrings as $forbiddenphpstring) {
			if (preg_match('/'.preg_quote($forbiddenphpstring, '/').'/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpstring), null, 'errors');
				break;
			}
		}
		/* replaced with next block
		foreach ($forbiddenphpfunctions as $forbiddenphpfunction) {	// Check "function(" but also "'function'(" and "function ("
			if (preg_match('/'.$forbiddenphpfunction.'[\'\s]*\(/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpfunction), null, 'errors');
				break;
			}
		}*/
		foreach ($forbiddenphpfunctions as $forbiddenphpfunction) {	// Check "function" whatever is "function(" or "function'(" or "function (" or "function"
			if (preg_match('/\b'.$forbiddenphpfunction.'\b/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpfunction), null, 'errors');
				break;
			}
		}

		foreach ($forbiddenphpmethods as $forbiddenphpmethod) {
			if (preg_match('/->'.$forbiddenphpmethod.'/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpmethod), null, 'errors');
				break;
			}
		}
	}

	// This char can be used to execute RCE for example by using  echo `ls`
	if (!$error) {
		$forbiddenphpchars = array();
		if (!getDolGlobalString('WEBSITE_PHP_ALLOW_DANGEROUS_CHARS')) {    // If option is not on, we disallow functions to execute commands
			$forbiddenphpchars = array("`");
		}
		foreach ($forbiddenphpchars as $forbiddenphpchar) {
			if (preg_match('/'.$forbiddenphpchar.'/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpchar), null, 'errors');
				break;
			}
		}
	}

	// Deny code to call a function obfuscated with comment, like  "exec/*...*/ ('ls')";
	if (!$error) {
		if (preg_match('/\*\/\s*\(/ims', $phpfullcodestring)) {
				$error++;
				setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", "exec/*...*/ ('ls')"), null, 'errors');
		}
	}

	// Deny dynamic functions  '${a}('  or  '$a[b]('  => So we refuse '}('  and  ']('
	if (!$error) {
		if (preg_match('/[}\]]\s*\(/ims', $phpfullcodestring)) {
			$error++;
			setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", ']('), null, 'errors');
		}
	}

	// Deny dynamic functions '$xxx(' or '$xxx ('  or '$xxx" ('
	if (!$error) {
		if (preg_match('/\$[a-z0-9_\-\/\*\"]+\s*\(/ims', $phpfullcodestring)) {
			$error++;
			setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", '$...('), null, 'errors');
		}
	}

	// No need to block $conf->global->aaa() because PHP try to run the method aaa of $conf->global and not the function into $conf->global->aaa.

	// Then check if installmodules.lock does not block dynamic PHP code change.
	if ($phpfullcodestringold != $phpfullcodestringnew) {
		if (!$error) {
			$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
			$allowimportsite = true;
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
				$allowimportsite = false;
			}

			if (!$allowimportsite) {
				$error++;
				// Blocked by installmodules.lock
				if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
					// Show clean corporate message
					$message = $langs->trans('InstallModuleFromWebHasBeenDisabledContactUs');
				} else {
					// Show technical generic message
					$message = $langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock');
				}
				setEventMessages($message, null, 'errors');
			}
		}
	}

	return $error;
}
