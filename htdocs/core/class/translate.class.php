<?php
/* Copyright (C) 2001      Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004-2015 Destailleur Laurent <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin       <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
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
 *   	\file       htdocs/core/class/translate.class.php
 *      \ingroup    core
 *		\brief      File for Tanslate class
 */


/**
 *		Class to manage translations
 */
class Translate
{
	public $dir; // Directories that contains /langs subdirectory

	public $defaultlang; // Current language for current user
	public $shortlang; // Short language for current user
	public $charset_output = 'UTF-8'; // Codage used by "trans" method outputs

	public $tab_translate = array(); // Array of all translations key=>value
	private $_tab_loaded = array(); // Array to store result after loading each language file

	public $cache_labels = array(); // Cache for labels return by getLabelFromKey method
	public $cache_currencies = array(); // Cache to store currency symbols
	private $cache_currencies_all_loaded = false;
	public $origlang;
	public $error;
	public $errors = array();


	/**
	 *	Constructor
	 *
	 *  @param	string	$dir            Force directory that contains /langs subdirectory (value is sometimes '..' like into install/* pages or support/* pages). Use '' by default.
	 *  @param  Conf	$conf			Object with Dolibarr configuration
	 */
	public function __construct($dir, $conf)
	{
		if (!empty($conf->file->character_set_client)) {
			$this->charset_output = $conf->file->character_set_client; // If charset output is forced
		}
		if ($dir) {
			$this->dir = array($dir);
		} else {
			$this->dir = $conf->file->dol_document_root;
		}
	}


	/**
	 *  Set accessor for this->defaultlang
	 *
	 *  @param	string	$srclang     	Language to use. If '' or 'auto', we use browser lang.
	 *  @return	void
	 */
	public function setDefaultLang($srclang = 'en_US')
	{
		global $conf;

		//dol_syslog(get_class($this)."::setDefaultLang srclang=".$srclang,LOG_DEBUG);

		// If a module ask to force a priority on langs directories (to use its own lang files)
		if (getDolGlobalString('MAIN_FORCELANGDIR')) {
			$more = array();
			$i = 0;
			foreach ($conf->file->dol_document_root as $dir) {
				$newdir = $dir . getDolGlobalString('MAIN_FORCELANGDIR'); // For example $conf->global->MAIN_FORCELANGDIR is '/mymodule' meaning we search files into '/mymodule/langs/xx_XX'
				if (!in_array($newdir, $this->dir)) {
					$more['module_' . $i] = $newdir;
					$i++; // We add the forced dir into the array $more. Just after, we add entries into $more to list of lang dir $this->dir.
				}
			}
			$this->dir = array_merge($more, $this->dir); // Forced dir ($more) are before standard dirs ($this->dir)
		}

		$this->origlang = $srclang;

		if (empty($srclang) || $srclang == 'auto') {
			// $_SERVER['HTTP_ACCEPT_LANGUAGE'] can be 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7,it;q=0.6' but can contains also malicious content
			$langpref = empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? '' : $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langpref = preg_replace("/;([^,]*)/i", "", $langpref); // Remove the 'q=x.y,' part
			$langpref = str_replace("-", "_", $langpref);
			$langlist = preg_split("/[;,]/", $langpref);
			$codetouse = preg_replace('/[^_a-zA-Z]/', '', $langlist[0]);
		} else {
			$codetouse = $srclang;
		}

		// We redefine $srclang
		$langpart = explode("_", $codetouse);
		//print "Short code before _ : ".$langpart[0].' / Short code after _ : '.$langpart[1].'<br>';
		if (!empty($langpart[1])) {	// If it's for a codetouse that is a long code xx_YY
			// Array force long code from first part, even if long code is defined
			$longforshort = array('ar' => 'ar_SA');
			$longforshortexcep = array('ar_EG');
			if (isset($longforshort[strtolower($langpart[0])]) && !in_array($codetouse, $longforshortexcep)) {
				$srclang = $longforshort[strtolower($langpart[0])];
			} elseif (!is_numeric($langpart[1])) {		// Second part YY may be a numeric with some Chrome browser
				$srclang = strtolower($langpart[0]) . "_" . strtoupper($langpart[1]);
				$longforlong = array('no_nb' => 'nb_NO');
				if (isset($longforlong[strtolower($srclang)])) {
					$srclang = $longforlong[strtolower($srclang)];
				}
			} else {
				$srclang = strtolower($langpart[0]) . "_" . strtoupper($langpart[0]);
			}
		} else {						// If it's for a codetouse that is a short code xx
			// Array to convert short lang code into long code.
			$longforshort = array(
				'am' => 'am_ET', 'ar' => 'ar_SA', 'bn' => 'bn_DB', 'el' => 'el_GR', 'ca' => 'ca_ES', 'cs' => 'cs_CZ', 'en' => 'en_US', 'fa' => 'fa_IR',
				'gl' => 'gl_ES', 'he' => 'he_IL', 'hi' => 'hi_IN', 'ja' => 'ja_JP',
				'ka' => 'ka_GE', 'km' => 'km_KH', 'kn' => 'kn_IN', 'ko' => 'ko_KR', 'lo' => 'lo_LA', 'nb' => 'nb_NO', 'no' => 'nb_NO', 'ne' => 'ne_NP',
				'sl' => 'sl_SI', 'sq' => 'sq_AL', 'sr' => 'sr_RS', 'sv' => 'sv_SE', 'uk' => 'uk_UA', 'vi' => 'vi_VN', 'zh' => 'zh_CN'
			);
			if (isset($longforshort[strtolower($langpart[0])])) {
				$srclang = $longforshort[strtolower($langpart[0])];
			} elseif (!empty($langpart[0])) {
				$srclang = strtolower($langpart[0]) . "_" . strtoupper($langpart[0]);
			} else {
				$srclang = 'en_US';
			}
		}

		$this->defaultlang = $srclang;
		$this->shortlang = substr($srclang, 0, 2);
		//print 'this->defaultlang='.$this->defaultlang;
	}


	/**
	 *  Return active language code for current user
	 * 	It's an accessor for this->defaultlang
	 *
	 *  @param	int		$mode       0=Long language code, 1=Short language code (en, fr, es, ...)
	 *  @return string      		Language code used (en_US, en_AU, fr_FR, ...)
	 */
	public function getDefaultLang($mode = 0)
	{
		if (empty($mode)) {
			return $this->defaultlang;
		} else {
			return substr($this->defaultlang, 0, 2);
		}
	}


	/**
	 *  Load translation files.
	 *
	 *  @param	array	$domains      		Array of lang files to load
	 *	@return	int							Return integer <0 if KO, 0 if already loaded or loading not required, >0 if OK
	 */
	public function loadLangs($domains)
	{
		$loaded = 0;
		foreach ($domains as $domain) {
			$result = $this->load($domain);
			if ($result > 0) {
				$loaded = $result;
			} elseif ($result < 0) {
				return $result;
			}
		}
		return $loaded;
	}

	/**
	 *  Load translation key-value for a particular file, into a memory array.
	 *  If data for file already loaded, do nothing.
	 * 	All data in translation array are stored in UTF-8 format.
	 *  tab_loaded is completed with $domain key.
	 *  rule "we keep first entry found with we keep last entry found" so it is probably not what you want to do.
	 *
	 *  Value for hash are: 1:Loaded from disk, 2:Not found, 3:Loaded from cache
	 *
	 *  @param	string	$domain      				File name to load (.lang file). Must be "file" or "file@module" for module language files:
	 *												If $domain is "file@module" instead of "file" then we look for module lang file
	 *												in htdocs/custom/modules/mymodule/langs/code_CODE/file.lang
	 *												then in htdocs/module/langs/code_CODE/file.lang instead of htdocs/langs/code_CODE/file.lang
	 *  @param	integer	$alt         				0 (try xx_ZZ then 1), 1 (try xx_XX then 2), 2 (try en_US)
	 * 	@param	int		$stopafterdirection			Stop when the DIRECTION tag is found (optimize speed)
	 * 	@param	int		$forcelangdir				To force a different lang directory
	 *  @param  int     $loadfromfileonly   		1=Do not load overwritten translation from file or old conf.
	 *  @param  int     $forceloadifalreadynotfound	Force attempt to reload lang file if it was previously not found
	 *	@return	int									Return integer <0 if KO, 0 if already loaded or loading not required, >0 if OK
	 *  @see loadLangs()
	 */
	public function load($domain, $alt = 0, $stopafterdirection = 0, $forcelangdir = '', $loadfromfileonly = 0, $forceloadifalreadynotfound = 0)
	{
		global $conf, $db;

		//dol_syslog("Translate::Load Start domain=".$domain." alt=".$alt." forcelangdir=".$forcelangdir." this->defaultlang=".$this->defaultlang);

		// Check parameters
		if (empty($domain)) {
			dol_print_error('', get_class($this) . "::Load ErrorWrongParameters");
			return -1;
		}
		if ($this->defaultlang === 'none_NONE') {
			return 0; // Special language code to not translate keys
		}


		// Load $this->tab_translate[] from database
		if (empty($loadfromfileonly) && count($this->tab_translate) == 0) {
			$this->loadFromDatabase($db); // No translation was never loaded yet, so we load database.
		}


		$newdomain = $domain;
		$modulename = '';

		// Search if a module directory name is provided into lang file name
		$regs = array();
		if (preg_match('/^([^@]+)@([^@]+)$/i', $domain, $regs)) {
			$newdomain = $regs[1];
			$modulename = $regs[2];
		}

		// Check cache
		if (
			!empty($this->_tab_loaded[$newdomain])
			&& ($this->_tab_loaded[$newdomain] != 2 || empty($forceloadifalreadynotfound))
		) { // File already loaded and found and not forced for this domain
			//dol_syslog("Translate::Load already loaded for newdomain=".$newdomain);
			return 0;
		}

		$fileread = 0;
		$langofdir = (empty($forcelangdir) ? $this->defaultlang : $forcelangdir);

		// Redefine alt
		$langarray = explode('_', $langofdir);
		if ($alt < 1 && isset($langarray[1]) && (strtolower($langarray[0]) == strtolower($langarray[1]) || in_array(strtolower($langofdir), array('el_gr')))) {
			$alt = 1;
		}
		if ($alt < 2 && strtolower($langofdir) == 'en_us') {
			$alt = 2;
		}

		if (empty($langofdir)) {	// This may occurs when load is called without setting the language and without providing a value for forcelangdir
			dol_syslog("Error: " . get_class($this) . "::load was called for domain=" . $domain . " but language was not set yet with langs->setDefaultLang(). Nothing will be loaded.", LOG_WARNING);
			return -1;
		}

		foreach ($this->dir as $searchdir) {
			// Directory of translation files
			$file_lang = $searchdir . ($modulename ? '/' . $modulename : '') . "/langs/" . $langofdir . "/" . $newdomain . ".lang";
			$file_lang_osencoded = dol_osencode($file_lang);

			//$filelangexists = is_file($file_lang_osencoded);
			$filelangexists = @is_file($file_lang_osencoded);	// avoid [php:warn]

			//dol_syslog(get_class($this).'::Load Try to read for alt='.$alt.' langofdir='.$langofdir.' domain='.$domain.' newdomain='.$newdomain.' modulename='.$modulename.' file_lang='.$file_lang." => filelangexists=".$filelangexists);
			//print 'Try to read for alt='.$alt.' langofdir='.$langofdir.' domain='.$domain.' newdomain='.$newdomain.' modulename='.$modulename.' this->_tab_loaded[newdomain]='.$this->_tab_loaded[$newdomain].' file_lang='.$file_lang." => filelangexists=".$filelangexists."\n";

			if ($filelangexists) {
				// TODO Move cache read out of loop on dirs or at least filelangexists
				$found = false;

				// Enable caching of lang file in memory (not by default)
				$usecachekey = '';
				// Using a memcached server
				if (isModEnabled('memcached') && getDolGlobalString('MEMCACHED_SERVER')) {
					$usecachekey = $newdomain . '_' . $langofdir . '_' . md5($file_lang); // Should not contains special chars
				} elseif (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02)) {
					// Using cache with shmop. Speed gain: 40ms - Memory overusage: 200ko (Size of session cache file)
					$usecachekey = $newdomain;
				}

				if ($usecachekey) {
					//dol_syslog('Translate::Load we will cache result into usecachekey '.$usecachekey);
					//global $aaa; $aaa+=1;
					//print $aaa." ".$usecachekey."\n";
					require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
					$tmparray = dol_getcache($usecachekey);
					if (is_array($tmparray) && count($tmparray)) {
						$this->tab_translate += $tmparray; // Faster than array_merge($tmparray,$this->tab_translate). Note: If a value already exists into tab_translate, value into tmparaay is not added.
						//print $newdomain."\n";
						//var_dump($this->tab_translate);
						if ($alt == 2) {
							$fileread = 1;
						}
						$found = true; // Found in dolibarr PHP cache
					}
				}

				if (!$found) {
					if ($fp = @fopen($file_lang, "rt")) {
						if ($usecachekey) {
							$tabtranslatedomain = array(); // To save lang content in cache
						}

						/**
						 * Read each lines until a '=' (with any combination of spaces around it)
						 * and split the rest until a line feed.
						 * This is more efficient than fgets + explode + trim by a factor of ~2.
						 */
						while ($line = fscanf($fp, "%[^= ]%*[ =]%[^\n\r]")) {
							if (isset($line[1])) {
								list($key, $value) = $line;
								//if ($domain == 'orders') print "Domain=$domain, found a string for $tab[0] with value $tab[1]. Currently in cache ".$this->tab_translate[$key]."<br>";
								//if ($key == 'Order') print "Domain=$domain, found a string for key=$key=$tab[0] with value $tab[1]. Currently in cache ".$this->tab_translate[$key]."<br>";
								if (empty($this->tab_translate[$key])) { // If translation was already found, we must not continue, even if MAIN_FORCELANGDIR is set (MAIN_FORCELANGDIR is to replace lang dir, not to overwrite entries)
									if ($key == 'DIRECTION') { // This is to declare direction of language
										if ($alt < 2 || empty($this->tab_translate[$key])) { // We load direction only for primary files or if not yet loaded
											$this->tab_translate[$key] = $value;
											if ($stopafterdirection) {
												break; // We do not save tab if we stop after DIRECTION
											} elseif ($usecachekey) {
												$tabtranslatedomain[$key] = $value;
											}
										}
									} elseif ($key[0] == '#') {
										continue;
									} else {
										// Convert some strings: Parse and render carriage returns. Also, change '\\s' into '\s' because transifex sync pull the string '\s' into string '\\s'
										$this->tab_translate[$key] = str_replace(array('\\n', '\\\\s'), array("\n", '\s'), $value);
										if ($usecachekey) {
											$tabtranslatedomain[$key] = $value;
										} // To save lang content in cache
									}
								}
							}
						}
						fclose($fp);
						$fileread = 1;

						// TODO Move cache write out of loop on dirs
						// To save lang content for usecachekey into cache
						if ($usecachekey && count($tabtranslatedomain)) {
							$ressetcache = dol_setcache($usecachekey, $tabtranslatedomain);
							if ($ressetcache < 0) {
								$error = 'Failed to set cache for usecachekey=' . $usecachekey . ' result=' . $ressetcache;
								dol_syslog($error, LOG_ERR);
							}
						}

						if (!getDolGlobalString('MAIN_FORCELANGDIR')) {
							break; // Break loop on each root dir. If a module has forced dir, we do not stop loop.
						}
					}
				}
			}
		}

		// Now we complete with next file (fr_CA->fr_FR, es_MX->ex_ES, ...)
		if ($alt == 0) {
			// This function MUST NOT contains call to syslog
			//dol_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$newdomain.".lang file)", LOG_DEBUG);
			$langofdir = strtolower($langarray[0]) . '_' . strtoupper($langarray[0]);
			if ($langofdir == 'el_EL') {
				$langofdir = 'el_GR'; // main parent for el_CY is not 'el_EL' but 'el_GR'
			}
			if ($langofdir == 'ar_AR') {
				$langofdir = 'ar_SA'; // main parent for ar_EG is not 'ar_AR' but 'ar_SA'
			}
			$this->load($domain, $alt + 1, $stopafterdirection, $langofdir);
		}

		// Now we complete with reference file (en_US)
		if ($alt == 1) {
			// This function MUST NOT contains call to syslog
			//dol_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$newdomain.".lang file)", LOG_DEBUG);
			$langofdir = 'en_US';
			$this->load($domain, $alt + 1, $stopafterdirection, $langofdir);
		}

		// We are in the pass of the reference file. No more files to scan to complete.
		if ($alt == 2) {
			if ($fileread) {
				$this->_tab_loaded[$newdomain] = 1; // Set domain file as found so loaded
			}

			if (empty($this->_tab_loaded[$newdomain])) {
				$this->_tab_loaded[$newdomain] = 2; // Set this file as not found
			}
		}

		// This part is deprecated and replaced with table llx_overwrite_trans
		// Kept for backward compatibility.
		if (empty($loadfromfileonly)) {
			$overwritekey = 'MAIN_OVERWRITE_TRANS_' . $this->defaultlang;
			if (!empty($conf->global->$overwritekey)) {    // Overwrite translation with key1:newstring1,key2:newstring2
				// Overwrite translation with param MAIN_OVERWRITE_TRANS_xx_XX
				$tmparray = explode(',', getDolGlobalString($overwritekey));
				foreach ($tmparray as $tmp) {
					$tmparray2 = explode(':', $tmp);
					if (!empty($tmparray2[1])) {
						$this->tab_translate[$tmparray2[0]] = $tmparray2[1];
					}
				}
			}
		}

		// Check to be sure that SeparatorDecimal differs from SeparatorThousand
		if (
			!empty($this->tab_translate["SeparatorDecimal"]) && !empty($this->tab_translate["SeparatorThousand"])
			&& $this->tab_translate["SeparatorDecimal"] == $this->tab_translate["SeparatorThousand"]
		) {
			$this->tab_translate["SeparatorThousand"] = '';
		}

		return 1;
	}

	/**
	 *  Load translation key-value from database into a memory array.
	 *  If data already loaded, do nothing.
	 * 	All data in translation array are stored in UTF-8 format.
	 *  tab_loaded is completed with $domain key.
	 *  rule "we keep first entry found with we keep last entry found" so it is probably not what you want to do.
	 *
	 *  Value for hash are: 1:Loaded from disk, 2:Not found, 3:Loaded from cache
	 *
	 *  @param  DoliDB    $db             Database handler
	 *	@return	int							Return integer <0 if KO, 0 if already loaded or loading not required, >0 if OK
	 */
	public function loadFromDatabase($db)
	{
		global $conf;

		$domain = 'database';

		// Check parameters
		if (empty($db)) {
			return 0; // Database handler can't be used
		}

		//dol_syslog("Translate::Load Start domain=".$domain." alt=".$alt." forcelangdir=".$forcelangdir." this->defaultlang=".$this->defaultlang);

		$newdomain = $domain;

		// Check cache
		if (!empty($this->_tab_loaded[$newdomain])) {	// File already loaded for this domain 'database'
			//dol_syslog("Translate::Load already loaded for newdomain=".$newdomain);
			return 0;
		}

		$this->_tab_loaded[$newdomain] = 1; // We want to be sure this function is called once only for domain 'database'

		$fileread = 0;
		$langofdir = $this->defaultlang;

		if (empty($langofdir)) {	// This may occurs when load is called without setting the language and without providing a value for forcelangdir
			dol_syslog("Error: " . get_class($this) . "::loadFromDatabase was called but language was not set yet with langs->setDefaultLang(). Nothing will be loaded.", LOG_WARNING);
			return -1;
		}

		// TODO Move cache read out of loop on dirs or at least filelangexists
		$found = false;

		// Enable caching of lang file in memory (not by default)
		$usecachekey = '';
		// Using a memcached server
		if (isModEnabled('memcached') && getDolGlobalString('MEMCACHED_SERVER')) {
			$usecachekey = $newdomain . '_' . $langofdir; // Should not contains special chars
		} elseif (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02)) {
			// Using cache with shmop. Speed gain: 40ms - Memory overusage: 200ko (Size of session cache file)
			$usecachekey = $newdomain;
		}

		if ($usecachekey) {
			//dol_syslog('Translate::Load we will cache result into usecachekey '.$usecachekey);
			//global $aaa; $aaa+=1;
			//print $aaa." ".$usecachekey."\n";
			require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
			$tmparray = dol_getcache($usecachekey);
			if (is_array($tmparray) && count($tmparray)) {
				$this->tab_translate += $tmparray; // Faster than array_merge($tmparray,$this->tab_translate). Note: If a value already exists into tab_translate, value into tmparaay is not added.
				//print $newdomain."\n";
				//var_dump($this->tab_translate);
				$fileread = 1;
				$found = true; // Found in dolibarr PHP cache
			}
		}

		if (!$found && getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
			// Overwrite translation with database read
			$sql = "SELECT transkey, transvalue FROM ".$db->prefix()."overwrite_trans where (lang='".$db->escape($this->defaultlang)."' OR lang IS NULL)";
			$sql .= " AND entity IN (0, ".getEntity('overwrite_trans').")";
			$sql .= $db->order("lang", "DESC");

			$resql = $db->query($sql);

			if ($resql) {
				$num = $db->num_rows($resql);
				if ($num) {
					if ($usecachekey) {
						$tabtranslatedomain = array(); // To save lang content in cache
					}

					$i = 0;
					while ($i < $num) {	// Ex: Need 225ms for all fgets on all lang file for Third party page. Same speed than file_get_contents
						$obj = $db->fetch_object($resql);

						$key = $obj->transkey;
						$value = $obj->transvalue;

						//print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
						if (empty($this->tab_translate[$key])) {    // If translation was already found, we must not continue, even if MAIN_FORCELANGDIR is set (MAIN_FORCELANGDIR is to replace lang dir, not to overwrite entries)
							// Convert some strings: Parse and render carriage returns. Also, change '\\s' int '\s' because transifex sync pull the string '\s' into string '\\s'
							$this->tab_translate[$key] = str_replace(array('\\n', '\\\\s'), array("\n", '\s'), $value);

							if ($usecachekey) {
								$tabtranslatedomain[$key] = $value; // To save lang content in cache
							}
						}

						$i++;
					}

					$fileread = 1;

					// TODO Move cache write out of loop on dirs
					// To save lang content for usecachekey into cache
					if ($usecachekey && count($tabtranslatedomain)) {
						$ressetcache = dol_setcache($usecachekey, $tabtranslatedomain);
						if ($ressetcache < 0) {
							$error = 'Failed to set cache for usecachekey=' . $usecachekey . ' result=' . $ressetcache;
							dol_syslog($error, LOG_ERR);
						}
					}
				}
			} else {
				dol_print_error($db);
			}
		}

		if ($fileread) {
			$this->_tab_loaded[$newdomain] = 1; // Set domain file as loaded
		}

		if (empty($this->_tab_loaded[$newdomain])) {
			$this->_tab_loaded[$newdomain] = 2; // Mark this case as not found (no lines found for language)
		}

		return 1;
	}

	/**
	 * Get information with result of loading data for domain
	 *
	 * @param	string		$domain		Domain to check
	 * @return 	int						0, 1, 2...
	 */
	public function isLoaded($domain)
	{
		return $this->_tab_loaded[$domain];
	}

	/**
	 * Return translated value of key for special keys ("Currency...", "Civility...", ...).
	 * Search in lang file, then into database. Key must be any complete entry into lang file: CurrencyEUR, ...
	 * If not found, return key.
	 * The string return is not formated (translated with transnoentitiesnoconv).
	 * NOTE: To avoid infinite loop (getLabelFromKey->transnoentities->getTradFromKey->getLabelFromKey), if you modify this function,
	 * check that getLabelFromKey is never called with the same value than $key.
	 *
	 * @param	string		$key		Key to translate
	 * @return 	string					Translated string (translated with transnoentitiesnoconv)
	 */
	private function getTradFromKey($key)
	{
		global $db;

		if (!is_string($key)) {
			//xdebug_print_function_stack('ErrorBadValueForParamNotAString');
			return 'ErrorBadValueForParamNotAString'; // Avoid multiple errors with code not using function correctly.
		}

		$newstr = $key;
		$reg = array();
		if (preg_match('/^Civility([0-9A-Z]+)$/i', $key, $reg)) {
			$newstr = $this->getLabelFromKey($db, $reg[1], 'c_civility', 'code', 'label');
		} elseif (preg_match('/^Currency([A-Z][A-Z][A-Z])$/i', $key, $reg)) {
			$newstr = $this->getLabelFromKey($db, $reg[1], 'c_currencies', 'code_iso', 'label');
		} elseif (preg_match('/^SendingMethod([0-9A-Z]+)$/i', $key, $reg)) {
			$newstr = $this->getLabelFromKey($db, $reg[1], 'c_shipment_mode', 'code', 'libelle');
		} elseif (preg_match('/^PaymentType(?:Short)?([0-9A-Z]+)$/i', $key, $reg)) {
			$newstr = $this->getLabelFromKey($db, $reg[1], 'c_paiement', 'code', 'libelle', '', 1);
		} elseif (preg_match('/^OppStatus([0-9A-Z]+)$/i', $key, $reg)) {
			$newstr = $this->getLabelFromKey($db, $reg[1], 'c_lead_status', 'code', 'label');
		} elseif (preg_match('/^OrderSource([0-9A-Z]+)$/i', $key, $reg)) {
			// TODO OrderSourceX must be replaced with content of table llx_c_input_reason or llx_c_input_method
			//$newstr=$this->getLabelFromKey($db,$reg[1],'llx_c_input_reason','code','label');
		}

		/* Disabled. There is too many cases where translation of $newstr is not defined is normal (like when output with setEventMessage an already translated string)
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2)
		{
			dol_syslog(__METHOD__." MAIN_FEATURES_LEVEL=DEVELOP: missing translation for key '".$newstr."' in ".$_SERVER["PHP_SELF"], LOG_DEBUG);
		}*/

		return $newstr;
	}


	/**
	 *  Return text translated of text received as parameter (and encode it into HTML)
	 *  If there is no match for this text, we look in alternative file and if still not found, it is returned as it is.
	 *  The parameters of this method should not contain HTML tags. If there is, they will be htmlencoded to have no effect.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     param1 string
	 *  @param  string	$param2     param2 string
	 *  @param  string	$param3     param3 string
	 *  @param  string	$param4     param4 string
	 *	@param	int		$maxsize	Max length of text. Warning: Will not work if paramX has HTML content. deprecated.
	 *  @return string      		Translated string (encoded into HTML entities and UTF8)
	 */
	public function trans($key, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $maxsize = 0)
	{
		global $conf;

		if (!empty($this->tab_translate[$key])) {	// Translation is available
			$str = $this->tab_translate[$key];

			// Make some string replacement after translation
			$replacekey = 'MAIN_REPLACE_TRANS_' . $this->defaultlang;
			if (!empty($conf->global->$replacekey)) {    // Replacement translation variable with string1:newstring1;string2:newstring2
				$tmparray = explode(';', getDolGlobalString($replacekey));
				foreach ($tmparray as $tmp) {
					$tmparray2 = explode(':', $tmp);
					$str = preg_replace('/' . preg_quote($tmparray2[0]) . '/', $tmparray2[1], $str);
				}
			}

			// We replace some HTML tags by __xx__ to avoid having them encoded by htmlentities because
			// we want to keep '"' '<b>' '</b>' '<strong' '</strong>' '<a ' '</a>' '<br>' '< ' '<span' '</span>' that are reliable HTML tags inside translation strings.
			$str = str_replace(
				array('"', '<b>', '</b>', '<u>', '</u>', '<i', '</i>', '<center>', '</center>', '<strong>', '</strong>', '<a ', '</a>', '<br>', '<span', '</span>', '< ', '>'), // We accept '< ' but not '<'. We can accept however '>'
				array('__quot__', '__tagb__', '__tagbend__', '__tagu__', '__taguend__', '__tagi__', '__tagiend__', '__tagcenter__', '__tagcenterend__', '__tagb__', '__tagbend__', '__taga__', '__tagaend__', '__tagbr__', '__tagspan__', '__tagspanend__', '__ltspace__', '__gt__'),
				$str
			);

			if (strpos($key, 'Format') !== 0) {
				try {
					$str = sprintf($str, $param1, $param2, $param3, $param4); // Replace %s and %d except for FormatXXX strings.
				} catch (Exception $e) {
					// No exception managed
				}
			}

			// Encode string into HTML
			$str = htmlentities($str, ENT_COMPAT, $this->charset_output); // Do not convert simple quotes in translation (strings in html are embraced by "). Use dol_escape_htmltag around text in HTML content

			// Restore reliable HTML tags into original translation string
			$str = str_replace(
				array('__quot__', '__tagb__', '__tagbend__', '__tagu__', '__taguend__', '__tagi__', '__tagiend__', '__tagcenter__', '__tagcenterend__', '__taga__', '__tagaend__', '__tagbr__', '__tagspan__', '__tagspanend__', '__ltspace__', '__gt__'),
				array('"', '<b>', '</b>', '<u>', '</u>', '<i', '</i>', '<center>', '</center>', '<a ', '</a>', '<br>', '<span', '</span>', '< ', '>'),
				$str
			);

			// Remove dangerous sequence we should never have. Not needed into a translated response.
			// %27 is entity code for ' and is replaced by browser automatically when translation is inside a javascript code called by a click like on a href link.
			$str = str_replace(array('%27', '&#39'), '', $str);

			if ($maxsize) {
				$str = dol_trunc($str, $maxsize);
			}

			return $str;
		} else { // Translation is not available
			//if ($key[0] == '$') { return dol_eval($key, 1, 1, '1'); }
			return $this->getTradFromKey($key);
		}
	}


	/**
	 *  Return translated value of a text string
	 *               If there is no match for this text, we look in alternative file and if still not found
	 *               it is returned as is.
	 *               Parameters of this method must not contain any HTML tags.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     chaine de param1
	 *  @param  string	$param2     chaine de param2
	 *  @param  string	$param3     chaine de param3
	 *  @param  string	$param4     chaine de param4
	 *  @param  string	$param5     chaine de param5
	 *  @return string      		Translated string (encoded into UTF8)
	 */
	public function transnoentities($key, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '')
	{
		return $this->convToOutputCharset($this->transnoentitiesnoconv($key, $param1, $param2, $param3, $param4, $param5));
	}


	/**
	 *  Return translated value of a text string
	 * 				 If there is no match for this text, we look in alternative file and if still not found,
	 * 				 it is returned as is.
	 *               No conversion to encoding charset of lang object is done.
	 *               Parameters of this method must not contains any HTML tags.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     chaine de param1
	 *  @param  string	$param2     chaine de param2
	 *  @param  string	$param3     chaine de param3
	 *  @param  string	$param4     chaine de param4
	 *  @param  string	$param5     chaine de param5
	 *  @return string      		Translated string
	 */
	public function transnoentitiesnoconv($key, $param1 = '', $param2 = '', $param3 = '', $param4 = '', $param5 = '')
	{
		global $conf;

		if (!empty($this->tab_translate[$key])) {	// Translation is available
			$str = $this->tab_translate[$key];

			// Make some string replacement after translation
			$replacekey = 'MAIN_REPLACE_TRANS_' . $this->defaultlang;
			if (!empty($conf->global->$replacekey)) {    // Replacement translation variable with string1:newstring1;string2:newstring2
				$tmparray = explode(';', getDolGlobalString($replacekey));
				foreach ($tmparray as $tmp) {
					$tmparray2 = explode(':', $tmp);
					$str = preg_replace('/' . preg_quote($tmparray2[0]) . '/', $tmparray2[1], $str);
				}
			}

			if (!preg_match('/^Format/', $key)) {
				//print $str;
				$str = sprintf($str, $param1, $param2, $param3, $param4, $param5); // Replace %s and %d except for FormatXXX strings.
			}

			// Remove dangerous sequence we should never have. Not needed into a translated response.
			// %27 is entity code for ' and is replaced by browser automatically when translation is inside a javascript code called by a click like on a href link.
			$str = str_replace(array('%27', '&#39'), '', $str);

			return $str;
		} else {
			/*if ($key[0] == '$') {
				return dol_eval($key, 1, 1, '1');
			}*/
			return $this->getTradFromKey($key);
		}
	}


	/**
	 *  Return translation of a key depending on country
	 *
	 *  @param	string	$str            string root to translate
	 *  @param  string	$countrycode    country code (FR, ...)
	 *  @return	string         			translated string
	 *  @see transcountrynoentities(), picto_from_langcode()
	 */
	public function transcountry($str, $countrycode)
	{
		if (!empty($this->tab_translate["$str$countrycode"])) {
			return $this->trans("$str$countrycode");
		} else {
			return $this->trans($str);
		}
	}


	/**
	 *  Retourne la version traduite du texte passe en parametre complete du code pays
	 *
	 *  @param	string	$str            string root to translate
	 *  @param  string	$countrycode    country code (FR, ...)
	 *  @return string         			translated string
	 *  @see transcountry(), picto_from_langcode()
	 */
	public function transcountrynoentities($str, $countrycode)
	{
		if (!empty($this->tab_translate["$str$countrycode"])) {
			return $this->transnoentities("$str$countrycode");
		} else {
			return $this->transnoentities($str);
		}
	}


	/**
	 *  Convert a string into output charset (this->charset_output that should be defined to conf->file->character_set_client)
	 *
	 *  @param	string	$str            String to convert
	 *  @param	string	$pagecodefrom	Page code of src string
	 *  @return string         			Converted string
	 */
	public function convToOutputCharset($str, $pagecodefrom = 'UTF-8')
	{
		if ($pagecodefrom == 'ISO-8859-1' && $this->charset_output == 'UTF-8') {
			$str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
		}
		if ($pagecodefrom == 'UTF-8' && $this->charset_output == 'ISO-8859-1') {
			$str = mb_convert_encoding(str_replace('€', chr(128), $str), 'ISO-8859-1');
			// TODO Replace with iconv("UTF-8", "ISO-8859-1", str_replace('€', chr(128), $str)); ?
		}
		return $str;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of all available languages
	 *
	 * 	@param	string	$langdir		Directory to scan
	 *  @param  integer	$maxlength   	Max length for each value in combo box (will be truncated)
	 *  @param	int		$usecode		1=Show code instead of country name for language variant, 2=Show only code
	 *  @param	int		$mainlangonly   1=Show only main languages ('fr_FR' no' fr_BE', 'es_ES' not 'es_MX', ...)
	 *  @return array     				List of languages
	 */
	public function get_available_languages($langdir = DOL_DOCUMENT_ROOT, $maxlength = 0, $usecode = 0, $mainlangonly = 0)
	{
		// phpcs:enable
		global $conf;

		$this->load("languages");

		// We scan directory langs to detect available languages
		$handle = opendir($langdir . "/langs");
		$langs_available = array();
		while ($dir = trim(readdir($handle))) {
			$regs = array();
			if (preg_match('/^([a-z]+)_([A-Z]+)/i', $dir, $regs)) {
				// We must keep only main languages
				if ($mainlangonly) {
					$arrayofspecialmainlanguages = array(
						'en' => 'en_US',
						'am' => 'am_ET',
						'ar' => 'ar_SA',
						'bn' => 'bn_DB',
						'bs' => 'bs_BA',
						'ca' => 'ca_ES',
						'cs' => 'cs_CZ',
						'da' => 'da_DK',
						'et' => 'et_EE',
						'el' => 'el_GR',
						'eu' => 'eu_ES',
						'fa' => 'fa_IR',
						'he' => 'he_IL',
						'ka' => 'ka_GE',
						'km' => 'km_KH',
						'kn' => 'kn_IN',
						'ko' => 'ko_KR',
						'ja' => 'ja_JP',
						'lo' => 'lo_LA',
						'nb' => 'nb_NO',
						'sq' => 'sq_AL',
						'sr' => 'sr_RS',
						'sv' => 'sv_SE',
						'sl' => 'sl_SI',
						'uk' => 'uk_UA',
						'vi' => 'vi_VN',
						'zh' => 'zh_CN'
					);
					if (strtolower($regs[1]) != strtolower($regs[2]) && !in_array($dir, $arrayofspecialmainlanguages)) {
						continue;
					}
				}
				// We must keep only languages into MAIN_LANGUAGES_ALLOWED
				if (getDolGlobalString('MAIN_LANGUAGES_ALLOWED') && !in_array($dir, explode(',', getDolGlobalString('MAIN_LANGUAGES_ALLOWED')))) {
					continue;
				}

				if ($usecode == 2) {
					$langs_available[$dir] = $dir;
				}

				if ($usecode == 1 || getDolGlobalString('MAIN_SHOW_LANGUAGE_CODE')) {
					$langs_available[$dir] = $dir . ': ' . dol_trunc($this->trans('Language_' . $dir), $maxlength);
				} else {
					$langs_available[$dir] = $this->trans('Language_' . $dir);
				}
				if ($mainlangonly) {
					$langs_available[$dir] = str_replace(' (United States)', '', $langs_available[$dir]);
				}
			}
		}
		return $langs_available;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if a filename $filename exists for current language (or alternate language)
	 *
	 *  @param	string	$filename       Language filename to search
	 *  @param  integer	$searchalt      Search also alernate language file
	 *  @return boolean         		true if exists and readable
	 */
	public function file_exists($filename, $searchalt = 0)
	{
		// phpcs:enable
		// Test si fichier dans repertoire de la langue
		foreach ($this->dir as $searchdir) {
			if (is_readable(dol_osencode($searchdir . "/langs/" . $this->defaultlang . "/" . $filename))) {
				return true;
			}

			if ($searchalt) {
				// Test si fichier dans repertoire de la langue alternative
				if ($this->defaultlang != "en_US") {
					$filenamealt = $searchdir . "/langs/en_US/" . $filename;
				}
				//else $filenamealt = $searchdir."/langs/fr_FR/".$filename;
				if (is_readable(dol_osencode($filenamealt))) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 *      Return full text translated to language label for a key. Store key-label in a cache.
	 *      This function need module "numberwords" to be installed. If not it will return
	 *      same number (this module is not provided by default as it use non GPL source code).
	 *
	 *		@param	int|string	$number		Number to encode in full text
	 *      @param  string		$isamount	''=it's just a number, '1'=It's an amount (default currency), 'currencycode'=It's an amount (foreign currency)
	 *      @return string					Label translated in UTF8 (but without entities)
	 * 										10 if setDefaultLang was en_US => ten
	 * 										123 if setDefaultLang was fr_FR => cent vingt trois
	 */
	public function getLabelFromNumber($number, $isamount = '')
	{
		global $conf;

		$newnumber = $number;

		$dirsubstitutions = array_merge(array(), $conf->modules_parts['substitutions']);
		foreach ($dirsubstitutions as $reldir) {
			$dir = dol_buildpath($reldir, 0);
			$newdir = dol_osencode($dir);

			// Check if directory exists
			if (!is_dir($newdir)) {
				continue; // We must not use dol_is_dir here, function may not be loaded
			}

			$fonc = 'numberwords';
			if (file_exists($newdir . '/functions_' . $fonc . '.lib.php')) {
				include_once $newdir . '/functions_' . $fonc . '.lib.php';
				if (function_exists('numberwords_getLabelFromNumber')) {
					$newnumber = numberwords_getLabelFromNumber($this, $number, $isamount);
					break;
				}
			}
		}

		return $newnumber;
	}


	/**
	 *      Return a label for a key.
	 *      Search into translation array, then into cache, then if still not found, search into database.
	 *      Store key-label found into cache variable $this->cache_labels to save SQL requests to get labels.
	 *
	 * 		@param	DoliDB	$db				Database handler
	 * 		@param	string	$key			Translation key to get label (key in language file)
	 * 		@param	string	$tablename		Table name without prefix. This value must always be a hardcoded string and not a value coming from user input.
	 * 		@param	string	$fieldkey		Field for key. This value must always be a hardcoded string and not a value coming from user input.
	 * 		@param	string	$fieldlabel		Field for label. This value must always be a hardcoded string and not a value coming from user input.
	 *      @param	string	$keyforselect	Use another value than the translation key for the where into select
	 *      @param  int		$filteronentity	Use a filter on entity
	 *      @return string					Label in UTF8 (but without entities)
	 *      @see dol_getIdFromCode()
	 */
	public function getLabelFromKey($db, $key, $tablename, $fieldkey, $fieldlabel, $keyforselect = '', $filteronentity = 0)
	{
		// If key empty
		if ($key == '') {
			return '';
		}
		// Test should be useless because the 3 variables are never set from user input but we keep it in case of.
		if (preg_match('/[^0-9A-Z_]/i', $tablename) || preg_match('/[^0-9A-Z_]/i', $fieldkey) || preg_match('/[^0-9A-Z_]/i', $fieldlabel)) {
			$this->error = 'Bad value for parameter tablename, fieldkey or fieldlabel';
			return -1;
		}

		//print 'param: '.$key.'-'.$keydatabase.'-'.$this->trans($key); exit;

		// Check if a translation is available (Note: this can call getTradFromKey that can call getLabelFromKey)
		$tmp = $this->transnoentitiesnoconv($key);
		if ($tmp != $key && $tmp != 'ErrorBadValueForParamNotAString') {
			return $tmp; // Found in language array
		}

		// Check in cache
		if (isset($this->cache_labels[$tablename][$key])) {	// Can be defined to 0 or ''
			return $this->cache_labels[$tablename][$key]; // Found in cache
		}

		// Not found in loaded language file nor in cache. So we will take the label into database.
		$sql = "SELECT " . $fieldlabel . " as label";
		$sql .= " FROM " . $db->prefix() . $tablename;
		$sql .= " WHERE " . $fieldkey . " = '" . $db->escape($keyforselect ? $keyforselect : $key) . "'";
		if ($filteronentity) {
			$sql .= " AND entity IN (" . getEntity($tablename) . ')';
		}
		dol_syslog(get_class($this) . '::getLabelFromKey', LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$this->cache_labels[$tablename][$key] = $obj->label;
			} else {
				$this->cache_labels[$tablename][$key] = $key;
			}

			$db->free($resql);
			return $this->cache_labels[$tablename][$key];
		} else {
			$this->error = $db->lasterror();
			return -1;
		}
	}


	/**
	 *	Return a currency code into its symbol
	 *
	 *  @param	string	$currency_code		Currency Code
	 *  @param	string	$amount				If not '', show currency + amount according to langs ($10, 10€).
	 *  @return	string						Amount + Currency symbol encoded into UTF8
	 *  @deprecated							Use method price to output a price
	 *  @see price()
	 */
	public function getCurrencyAmount($currency_code, $amount)
	{
		$symbol = $this->getCurrencySymbol($currency_code);

		if (in_array($currency_code, array('USD'))) {
			return $symbol . $amount;
		} else {
			return $amount . $symbol;
		}
	}

	/**
	 *	Return a currency code into its symbol.
	 *  If mb_convert_encoding is not available, return currency code.
	 *
	 *  @param	string	$currency_code		Currency code
	 *  @param	integer	$forceloadall		1=Force to load all currencies into cache. We know we need to use all of them. By default read and cache only the requested currency.
	 *  @return	string						Currency symbol encoded into UTF8
	 */
	public function getCurrencySymbol($currency_code, $forceloadall = 0)
	{
		$currency_sign = ''; // By default return iso code

		if (function_exists("mb_convert_encoding")) {
			$this->loadCacheCurrencies($forceloadall ? '' : $currency_code);

			if (isset($this->cache_currencies[$currency_code]) && !empty($this->cache_currencies[$currency_code]['unicode']) && is_array($this->cache_currencies[$currency_code]['unicode'])) {
				foreach ($this->cache_currencies[$currency_code]['unicode'] as $unicode) {
					$currency_sign .= mb_convert_encoding("&#" . $unicode . ";", "UTF-8", 'HTML-ENTITIES');
				}
			}
		}

		return ($currency_sign ? $currency_sign : $currency_code);
	}

	/**
	 *  Load into the cache this->cache_currencies, all currencies
	 *
	 *	@param	string	$currency_code		Get only currency. Get all if ''.
	 *  @return int             			Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	public function loadCacheCurrencies($currency_code)
	{
		global $db;

		if ($this->cache_currencies_all_loaded) {
			return 0; // Cache already loaded for all
		}
		if (!empty($currency_code) && isset($this->cache_currencies[$currency_code])) {
			return 0; // Cache already loaded for the currency
		}

		$sql = "SELECT code_iso, label, unicode";
		$sql .= " FROM " . $db->prefix() . "c_currencies";
		$sql .= " WHERE active = 1";
		if (!empty($currency_code)) {
			$sql .= " AND code_iso = '" . $db->escape($currency_code) . "'";
		}
		//$sql.= " ORDER BY code_iso ASC"; // Not required, a sort is done later

		dol_syslog(get_class($this) . '::loadCacheCurrencies', LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$this->load("dict");
			$label = array();
			if (!empty($currency_code)) {
				foreach ($this->cache_currencies as $key => $val) {
					$label[$key] = $val['label']; // Label in already loaded cache
				}
			}

			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// If a translation exists, we use it lese we use the default label
					$this->cache_currencies[$obj->code_iso]['label'] = ($obj->code_iso && $this->trans("Currency" . $obj->code_iso) != "Currency" . $obj->code_iso ? $this->trans("Currency" . $obj->code_iso) : ($obj->label != '-' ? $obj->label : ''));
					$this->cache_currencies[$obj->code_iso]['unicode'] = (array) json_decode((empty($obj->unicode) ? '' : $obj->unicode), true);
					$label[$obj->code_iso] = $this->cache_currencies[$obj->code_iso]['label'];
				}
				$i++;
			}
			if (empty($currency_code)) {
				$this->cache_currencies_all_loaded = true;
			}
			//print count($label).' '.count($this->cache_currencies);

			// Resort cache
			array_multisort($label, SORT_ASC, $this->cache_currencies);
			//var_dump($this->cache_currencies);	$this->cache_currencies is now sorted onto label
			return $num;
		} else {
			dol_print_error($db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return an array with content of all loaded translation keys (found into this->tab_translate) so
	 * we get a substitution array we can use for substitutions (for mail or ODT generation for example)
	 *
	 * @return array	Array of translation keys lang_key => string_translation_loaded
	 */
	public function get_translations_for_substitutions()
	{
		// phpcs:enable
		$substitutionarray = array();

		foreach ($this->tab_translate as $code => $label) {
			$substitutionarray['lang_' . $code] = $label;
			$substitutionarray['__(' . $code . ')__'] = $label;
		}

		return $substitutionarray;
	}
}
