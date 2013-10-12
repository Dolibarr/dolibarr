<?php
/* Copyright (C) 2001      Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004-2012 Destailleur Laurent <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin       <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	var $dir;							// Directories that contains /langs subdirectory

	var $defaultlang;					// Current language for current user
	var $direction = 'ltr';				// Left to right or Right to left
	var $charset_inputfile=array();	// To store charset encoding used for language
	var $charset_output='UTF-8';		// Codage used by "trans" method outputs

	var $tab_translate=array();		// Array of all translations key=>value
	private $_tab_loaded=array();		// Array to store result after loading each language file

	var $cache_labels=array();			// Cache for labels return by getLabelFromKey method
	var $cache_currencies=array();		// Cache to store currency symbols



	/**
	 *	Constructor
	 *
	 *  @param	string	$dir            Force directory that contains /langs subdirectory (value is sometine '..' like into install/* pages or support/* pages).
	 *  @param  Conf	$conf			Object with Dolibarr configuration
	 */
	function __construct($dir,$conf)
	{
		if (! empty($conf->file->character_set_client)) $this->charset_output=$conf->file->character_set_client;	// If charset output is forced
		if ($dir) $this->dir=array($dir);
		else $this->dir=$conf->file->dol_document_root;
	}


	/**
	 *  Set accessor for this->defaultlang
	 *
	 *  @param	string	$srclang     	Language to use. If '' or 'auto', we use browser lang.
	 *  @return	void
	 */
	function setDefaultLang($srclang='en_US')
	{
		global $conf;

		//dol_syslog(get_class($this)."::setDefaultLang srclang=".$srclang,LOG_DEBUG);

		// If a module ask to force a priority on langs directories (to use its own lang files)
		if (! empty($conf->global->MAIN_FORCELANGDIR))
		{
			$more=array();
			$i=0;
			foreach($conf->file->dol_document_root as $dir)
			{
				$newdir=$dir.$conf->global->MAIN_FORCELANGDIR;    // For example $conf->global->MAIN_FORCELANGDIR is '/mymodule' meaning we search files into '/mymodule/langs/xx_XX'
				if (! in_array($newdir,$this->dir))
				{
				    $more['module_'.$i]=$newdir; $i++;   // We add the forced dir into the array $more. Just after, we add entries into $more to list of lang dir $this->dir.
				}
			}
			$this->dir=array_merge($more,$this->dir);    // Forced dir ($more) are before standard dirs ($this->dir)
		}

		$this->origlang=$srclang;

		if (empty($srclang) || $srclang == 'auto')
		{
			$langpref=empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])?'':$_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langpref=preg_replace("/;([^,]*)/i","",$langpref);
			$langpref=str_replace("-","_",$langpref);
			$langlist=preg_split("/[;,]/",$langpref);
			$codetouse=$langlist[0];
		}
		else $codetouse=$srclang;

		// We redefine $srclang
		$langpart=explode("_",$codetouse);
		//print "Short before _ : ".$langpart[0].'/ Short after _ : '.$langpart[1].'<br>';
		if (! empty($langpart[1]))	// If it's for a codetouse that is a long code xx_YY
		{
			// Array force long code from first part, even if long code is defined
			$longforshort=array('ar'=>'ar_SA');
			if (isset($longforshort[strtolower($langpart[0])])) $srclang=$longforshort[strtolower($langpart[0])];
			else if (! is_numeric($langpart[1])) {		// Second part YY may be a numeric with some Chrome browser
				$srclang=strtolower($langpart[0])."_".strtoupper($langpart[1]);
				$longforlong=array('no_nb'=>'nb_NO');
				if (isset($longforlong[strtolower($srclang)])) $srclang=$longforlong[strtolower($srclang)];
			}
			else $srclang=strtolower($langpart[0])."_".strtoupper($langpart[0]);
		}
		else {						// If it's for a codetouse that is a short code xx
    	    // Array to convert short lang code into long code.
	        $longforshort=array('ar'=>'ar_SA', 'el'=>'el_GR', 'ca'=>'ca_ES', 'en'=>'en_US', 'nb'=>'nb_NO', 'no'=>'nb_NO');
			if (isset($longforshort[strtolower($langpart[0])])) $srclang=$longforshort[strtolower($langpart[0])];
			else if (! empty($langpart[0])) $srclang=strtolower($langpart[0])."_".strtoupper($langpart[0]);
			else $srclang='en_US';
		}

		$this->defaultlang=$srclang;
		//print 'this->defaultlang='.$this->defaultlang;
	}


	/**
	 *  Return active language code for current user
	 * 	It's an accessor for this->defaultlang
	 *
	 *  @param	int		$mode       0=Long language code, 1=Short language code
	 *  @return string      		Language code used (en_US, en_AU, fr_FR, ...)
	 */
	function getDefaultLang($mode=0)
	{
	    if (empty($mode)) return $this->defaultlang;
	    else return substr($this->defaultlang,0,2);
	}


	/**
	 *  Load translation key-value for a particular file, into a memory array.
	 *  If data for file already loaded, do nothing.
	 * 	All data in translation array are stored in UTF-8 format.
     *  tab_loaded is completed with $domain key.
     *  Warning: MAIN_USE_CUSTOM_TRANSLATION is an old deprecated feature. Do not use it. It will revert
     *  rule "we keep first entry found with we keep last entry found" so it is probably not what you want to do.
     *
     *  Value for hash are: 1:Loaded from disk, 2:Not found, 3:Loaded from cache
     *
	 *  @param	string	$domain      		File name to load (.lang file). Must be "file" or "file@module" for module language files:
 	 *										If $domain is "file@module" instead of "file" then we look for module lang file
	 *										in htdocs/custom/modules/mymodule/langs/code_CODE/file.lang
	 *										then in htdocs/module/langs/code_CODE/file.lang instead of htdocs/langs/code_CODE/file.lang
	 *  @param	string	$alt         		0 (try xx_ZZ then 1), 1 (try xx_XX then 2), 2 (try en_US or fr_FR or es_ES)
	 * 	@param	int		$stopafterdirection	Stop when the DIRECTION tag is found (optimize speed)
	 * 	@param	int		$forcelangdir		To force a different lang directory
	 *	@return	int							<0 if KO, 0 if already loaded or loading not required, >0 if OK
	 */
	function Load($domain,$alt=0,$stopafterdirection=0,$forcelangdir='')
	{
		global $conf;

		// Check parameters
		if (empty($domain))
		{
			dol_print_error('',get_class($this)."::Load ErrorWrongParameters");
			exit;
		}
		if ($this->defaultlang == 'none_NONE') return 0;    // Special language code to not translate keys

		//dol_syslog("Translate::Load Start domain=".$domain." alt=".$alt." forcelangdir=".$forcelangdir." this->defaultlang=".$this->defaultlang);

		$newdomain = $domain;
		$modulename = '';

		// Search if a module directory name is provided into lang file name
		if (preg_match('/^([^@]+)@([^@]+)$/i',$domain,$regs))
		{
			$newdomain = $regs[1];
			$modulename = $regs[2];
		}

        // Check cache
		if (! empty($this->_tab_loaded[$newdomain]))	// File already loaded for this domain
		{
			//dol_syslog("Translate::Load already loaded for newdomain=".$newdomain);
			return 0;
		}

        $fileread=0;
		$langofdir=(empty($forcelangdir)?$this->defaultlang:$forcelangdir);

		// Redefine alt
		$langarray=explode('_',$langofdir);
		if ($alt < 1 && isset($langarray[1]) && strtolower($langarray[0]) == strtolower($langarray[1])) $alt=1;
		if ($alt < 2 && (strtolower($langofdir) == 'en_us' || strtolower($langofdir) == 'fr_fr' || strtolower($langofdir) == 'es_es')) $alt=2;

		foreach($this->dir as $keydir => $searchdir)
		{
			// Directory of translation files
			$file_lang = $searchdir.($modulename?'/'.$modulename:'')."/langs/".$langofdir."/".$newdomain.".lang";
			$file_lang_osencoded=dol_osencode($file_lang);

			$filelangexists=is_file($file_lang_osencoded);

			//dol_syslog(get_class($this).'::Load Try to read for alt='.$alt.' langofdir='.$langofdir.' newdomain='.$domain.' modulename='.$modulename.' file_lang='.$file_lang." => filelangexists=".$filelangexists);

			if ($filelangexists)
			{
				// TODO Move cache read out of loop on dirs or at least filelangexists
			    $found=false;

				// Enable caching of lang file in memory (not by default)
				$usecachekey='';
				// Using a memcached server
				if (! empty($conf->memcached->enabled) && ! empty($conf->global->MEMCACHED_SERVER))
				{
					$usecachekey=$newdomain.'_'.$langofdir.'_'.md5($file_lang);    // Should not contains special chars
				}
				// Using cache with shmop. Speed gain: 40ms - Memory overusage: 200ko (Size of session cache file)
				else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02))
				{
					$usecachekey=$newdomain;
				}

				if ($usecachekey)
				{
			        //dol_syslog('Translate::Load we will cache result into usecachekey '.$usecachekey);

				    require_once DOL_DOCUMENT_ROOT .'/core/lib/memory.lib.php';
					$tmparray=dol_getcache($usecachekey);
					if (is_array($tmparray) && count($tmparray))
					{
						$this->tab_translate=array_merge($tmparray,$this->tab_translate);	// Already found values tab_translate overwrites duplicates
						//print $newdomain."\n";
						//var_dump($this->tab_translate);
						if ($alt == 2) $fileread=1;
						$found=true;						// Found in dolibarr PHP cache
					}
				}

				if (! $found)
				{
					if ($fp = @fopen($file_lang,"rt"))
					{
						if ($usecachekey) $tabtranslatedomain=array();	// To save lang content in cache

						while ($line = fgets($fp,4096))	// Ex: Need 225ms for all fgets on all lang file for Third party page. Same speed than file_get_contents
						{
							if ($line[0] != "\n" && $line[0] != " " && $line[0] != "#")
							{
								$tab=explode('=',$line,2);
								$key=trim($tab[0]);
								//print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
								if ((! empty($conf->global->MAIN_USE_CUSTOM_TRANSLATION) || empty($this->tab_translate[$key])) && isset($tab[1]))    // If translation was already found, we must not continue, even if MAIN_FORCELANGDIR is set (MAIN_FORCELANGDIR is to replace lang dir, not to overwrite entries)
								{
									$value=trim(preg_replace('/\\n/',"\n",$tab[1]));

									if ($key == 'DIRECTION')	// This is to declare direction of language
									{
										if ($alt < 2 || empty($this->tab_translate[$key]))	// We load direction only for primary files or if not yet loaded
										{
                                            $this->tab_translate[$key]=$value;
											if ($stopafterdirection) break;	// We do not save tab if we stop after DIRECTION
											else if ($usecachekey) $tabtranslatedomain[$key]=$value;
										}
									}
									else
									{
										$this->tab_translate[$key]=$value;
										if ($usecachekey) $tabtranslatedomain[$key]=$value;	// To save lang content in cache
									}
								}
							}
						}
						fclose($fp);
						$fileread=1;

						// TODO Move cache write out of loop on dirs
						// To save lang content for usecachekey into cache
						if ($usecachekey && count($tabtranslatedomain))
						{
							$ressetcache=dol_setcache($usecachekey,$tabtranslatedomain);
							if ($ressetcache < 0)
							{
							    $error='Failed to set cache for usecachekey='.$usecachekey.' result='.$ressetcache;
							    dol_syslog($error, LOG_ERR);
							}
						}

						if (empty($conf->global->MAIN_FORCELANGDIR) && empty($conf->global->MAIN_USE_CUSTOM_TRANSLATION)) break;		// Break loop on each root dir. If a module has forced dir, we do not stop loop.
					}
				}
			}
		}

		// Now we complete with next file
		if ($alt == 0)
		{
			// This function MUST NOT contains call to syslog
			//dol_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$newdomain.".lang file)", LOG_DEBUG);
			$langofdir=strtolower($langarray[0]).'_'.strtoupper($langarray[0]);
			$this->load($domain,$alt+1,$stopafterdirection,$langofdir);
		}

		// Now we complete with reference en_US/fr_FR/es_ES file
		if ($alt == 1)
		{
			// This function MUST NOT contains call to syslog
			//dol_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$newdomain.".lang file)", LOG_DEBUG);
			$langofdir='en_US';
			if (preg_match('/^fr/i',$langarray[0])) $langofdir='fr_FR';
			if (preg_match('/^es/i',$langarray[0])) $langofdir='es_ES';
			$this->load($domain,$alt+1,$stopafterdirection,$langofdir);
		}

		if ($alt == 2)
		{
			if ($fileread) $this->_tab_loaded[$newdomain]=1;	// Set domain file as loaded

			if (empty($this->_tab_loaded[$newdomain])) $this->_tab_loaded[$newdomain]=2;           // Marque ce fichier comme non trouve
		}

		// Check to be sure that SeparatorDecimal differs from SeparatorThousand
		if (! empty($this->tab_translate["SeparatorDecimal"]) && ! empty($this->tab_translate["SeparatorThousand"])
		&& $this->tab_translate["SeparatorDecimal"] == $this->tab_translate["SeparatorThousand"]) $this->tab_translate["SeparatorThousand"]='';

		return 1;
	}

	/**
	 * Return translated value of key. Search in lang file, then into database.
	 * Key must be any complete entry into lang file: CurrencyEUR, ...
	 * If not found, return key.
	 * WARNING: To avoid infinite loop (getLabelFromKey->transnoentities->getTradFromKey), getLabelFromKey must
	 * not be called with same value than input.
	 *
	 * @param	string		$key		Key to translate
	 * @return 	string					Translated string
	 */
	private function getTradFromKey($key)
	{
		global $db;

		//print 'xx'.$key;
		$newstr=$key;
		if (preg_match('/^Currency([A-Z][A-Z][A-Z])$/i',$key,$reg))
		{
			$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','label');
		}
		else if (preg_match('/^SendingMethod([0-9A-Z]+)$/i',$key,$reg))
		{
			$newstr=$this->getLabelFromKey($db,$reg[1],'c_shipment_mode','code','libelle');
		}
        else if (preg_match('/^PaymentTypeShort([0-9A-Z]+)$/i',$key,$reg))
        {
            $newstr=$this->getLabelFromKey($db,$reg[1],'c_paiement','code','libelle');
        }
        else if (preg_match('/^Civility([0-9A-Z]+)$/i',$key,$reg))
        {
            $newstr=$this->getLabelFromKey($db,$reg[1],'c_civilite','code','civilite');
        }
        else if (preg_match('/^OrderSource([0-9A-Z]+)$/i',$key,$reg))
        {
        	// TODO Add a table for OrderSourceX
            //$newstr=$this->getLabelFromKey($db,$reg[1],'c_ordersource','code','label');
        }
        return $newstr;
	}


	/**
	 *  Return text translated of text received as parameter (and encode it into HTML)
	 *              Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *              et si toujours pas trouve, il est retourne tel quel
	 *              Les parametres de cette methode peuvent contenir de balises HTML.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     chaine de param1
	 *  @param  string	$param2     chaine de param2
	 *  @param  string	$param3     chaine de param3
	 *  @param  string	$param4     chaine de param4
	 *	@param	int		$maxsize	Max length of text
	 *  @return string      		Translated string (encoded into HTML entities and UTF8)
	 */
	function trans($key, $param1='', $param2='', $param3='', $param4='', $maxsize=0)
	{
        global $conf;

	    if (! empty($this->tab_translate[$key]))	// Translation is available
		{
            $str=$this->tab_translate[$key];

		    // Overwrite translation (TODO Move this at a higher level when we load tab_translate to avoid doing it for each trans call)
		    $overwritekey='MAIN_OVERWRITE_TRANS_'.$this->defaultlang;
            if (! empty($conf->global->$overwritekey))    // Overwrite translation with key1:newstring1,key2:newstring2
            {
                $tmparray=explode(',', $conf->global->$overwritekey);
                foreach($tmparray as $tmp)
                {
                    $tmparray2=explode(':',$tmp);
                	if ($tmparray2[0]==$key) { $str=$tmparray2[1]; break; }
                }
            }

            if (! preg_match('/^Format/',$key)) $str=sprintf($str,$param1,$param2,$param3,$param4);	// Replace %s and %d except for FormatXXX strings.

			if ($maxsize) $str=dol_trunc($str,$maxsize);

			// We replace some HTML tags by __xx__ to avoid having them encoded by htmlentities
            $str=str_replace(array('<','>','"',),array('__lt__','__gt__','__quot__'),$str);

			// Crypt string into HTML
			$str=htmlentities($str,ENT_QUOTES,$this->charset_output);

			// Restore HTML tags
            $str=str_replace(array('__lt__','__gt__','__quot__'),array('<','>','"',),$str);

			return $str;
		}
		else								// Translation is not available
		{
		    if ($key[0] == '$') { return dol_eval($key,1); }
			return $this->getTradFromKey($key);
		}
	}


	/**
	 *  Return translated value of a text string
	 *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *               et si toujours pas trouve, il est retourne tel quel.
	 *               Parameters of this method must not contains any HTML tags.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     chaine de param1
	 *  @param  string	$param2     chaine de param2
	 *  @param  string	$param3     chaine de param3
	 *  @param  string	$param4     chaine de param4
	 *  @return string      		Translated string (encoded into UTF8)
	 */
	function transnoentities($key, $param1='', $param2='', $param3='', $param4='')
	{
		return $this->convToOutputCharset($this->transnoentitiesnoconv($key, $param1, $param2, $param3, $param4));
	}


	/**
	 *  Return translated value of a text string
	 *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *               et si toujours pas trouve, il est retourne tel quel.
	 *               No convert to encoding charset of lang object is done.
	 *               Parameters of this method must not contains any HTML tags.
	 *
	 *  @param	string	$key        Key to translate
	 *  @param  string	$param1     chaine de param1
	 *  @param  string	$param2     chaine de param2
	 *  @param  string	$param3     chaine de param3
	 *  @param  string	$param4     chaine de param4
	 *  @return string      		Translated string
	 */
	function transnoentitiesnoconv($key, $param1='', $param2='', $param3='', $param4='')
	{
		global $conf;

		if (! empty($this->tab_translate[$key]))	// Translation is available
		{
		    $str=$this->tab_translate[$key];

		    // Overwrite translation
		    $overwritekey='MAIN_OVERWRITE_TRANS_'.$this->defaultlang;
            if (! empty($conf->global->$overwritekey))    // Overwrite translation with key1:newstring1,key2:newstring2
            {
                $tmparray=explode(',', $conf->global->$overwritekey);
                foreach($tmparray as $tmp)
                {
                    $tmparray2=explode(':',$tmp);
                	if ($tmparray2[0]==$key) { $str=$tmparray2[1]; break; }
                }
            }

            if (! preg_match('/^Format/',$key)) $str=sprintf($str,$param1,$param2,$param3,$param4);	// Replace %s and %d except for FormatXXX strings.

            return $str;
		}
		else
		{
		    if ($key[0] == '$') { return dol_eval($key,1); }
			return $this->getTradFromKey($key);
		}
	}


	/**
	 *  Return translation of a key depending on country
	 *
	 *  @param	string	$str            string root to translate
	 *  @param  string	$countrycode    country code (FR, ...)
	 *  @return	string         			translated string
	 */
	function transcountry($str, $countrycode)
	{
		if ($this->tab_translate["$str$countrycode"]) return $this->trans("$str$countrycode");
		else return $this->trans($str);
	}


	/**
	 *  Retourne la version traduite du texte passe en parametre complete du code pays
	 *
	 *  @param	string	$str            string root to translate
	 *  @param  string	$countrycode    country code (FR, ...)
	 *  @return string         			translated string
	 */
	function transcountrynoentities($str, $countrycode)
	{
		if ($this->tab_translate["$str$countrycode"]) return $this->transnoentities("$str$countrycode");
		else return $this->transnoentities($str);
	}


	/**
	 *  Convert a string into output charset (this->charset_output that should be defined to conf->file->character_set_client)
	 *
	 *  @param	string	$str            String to convert
	 *  @param	string	$pagecodefrom	Page code of src string
	 *  @return string         			Converted string
	 */
	function convToOutputCharset($str,$pagecodefrom='UTF-8')
	{
		if ($pagecodefrom == 'ISO-8859-1' && $this->charset_output == 'UTF-8')  $str=utf8_encode($str);
		if ($pagecodefrom == 'UTF-8' && $this->charset_output == 'ISO-8859-1')	$str=utf8_decode(str_replace('€',chr(128),$str));
		return $str;
	}


	/**
	 *  Return list of all available languages
	 *
	 * 	@param	string	$langdir		Directory to scan
	 *  @param  string	$maxlength   	Max length for each value in combo box (will be truncated)
	 *  @param	int		$usecode		Show code instead of country name for language variant
	 *  @return array     				List of languages
	 */
	function get_available_languages($langdir=DOL_DOCUMENT_ROOT,$maxlength=0,$usecode=0)
	{
		global $conf;

		// We scan directory langs to detect available languages
		$handle=opendir($langdir."/langs");
		$langs_available=array();
		while ($dir = trim(readdir($handle)))
		{
			if (preg_match('/^[a-z]+_[A-Z]+/i',$dir))
			{
				$this->load("languages");

				if ($usecode || ! empty($conf->global->MAIN_SHOW_LANGUAGE_CODE))
				{
					$langs_available[$dir] = $dir.': '.dol_trunc($this->trans('Language_'.$dir),$maxlength);
				}
				else
				{
					$langs_available[$dir] = $this->trans('Language_'.$dir);
				}
			}
		}
		return $langs_available;
	}


	/**
	 *  Return if a filename $filename exists for current language (or alternate language)
	 *
	 *  @param	string	$filename       Language filename to search
	 *  @param  string	$searchalt      Search also alernate language file
	 *  @return boolean         		true if exists and readable
	 */
	function file_exists($filename,$searchalt=0)
	{
		// Test si fichier dans repertoire de la langue
		foreach($this->dir as $searchdir)
		{
			if (is_readable(dol_osencode($searchdir."/langs/".$this->defaultlang."/".$filename))) return true;

			if ($searchalt)
			{
				// Test si fichier dans repertoire de la langue alternative
				if ($this->defaultlang != "en_US") $filenamealt = $searchdir."/langs/en_US/".$filename;
				else $filenamealt = $searchdir."/langs/fr_FR/".$filename;
				if (is_readable(dol_osencode($filenamealt))) return true;
			}
		}

		return false;
	}


	/**
	 *      Return full text translated to language label for a key. Store key-label in a cache.
     *      This function need module "numberwords" to be installed. If not it will return
     *      same number (this module is not provided by default as it use non GPL source code).
	 *
	 *		@param	int		$number		Number to encode in full text
	 * 		@param	int		$isamount	1=It's an amount, 0=it's just a number
	 *      @return string				Label translated in UTF8 (but without entities)
	 * 									10 if setDefaultLang was en_US => ten
	 * 									123 if setDefaultLang was fr_FR => cent vingt trois
	 */
	function getLabelFromNumber($number,$isamount=0)
	{
		global $conf;

		$newnumber=$number;

		$dirsubstitutions=array_merge(array(),$conf->modules_parts['substitutions']);
		foreach($dirsubstitutions as $reldir)
		{
		    $dir=dol_buildpath($reldir,0);
		    $newdir=dol_osencode($dir);

		    // Check if directory exists
		    if (! is_dir($newdir)) continue;	// We must not use dol_is_dir here, function may not be loaded

			$fonc='numberwords';
			if (file_exists($newdir.'/functions_'.$fonc.'.lib.php'))
			{
				include_once $newdir.'/functions_'.$fonc.'.lib.php';
				$newnumber=numberwords_getLabelFromNumber($this,$number,$isamount);
				break;
			}
		}

		return $newnumber;
	}


	/**
	 *      Return a label for a key.
	 *      Search into translation array, then into cache, then if still not found, search into database.
	 *      Store key-label found into cache variable $this->cache_labels to save SQL requests to get labels.
	 *
	 * 		@param	DoliBD	$db				Database handler
	 * 		@param	string	$key			Translation key to get label (key in language file)
	 * 		@param	string	$tablename		Table name without prefix
	 * 		@param	string	$fieldkey		Field for key
	 * 		@param	string	$fieldlabel		Field for label
	 *      @param	string	$keyforselect	Use another value than the translation key for the where into select
	 *      @return string					Label in UTF8 (but without entities)
	 *      @see dol_getIdFromCode
	 */
	function getLabelFromKey($db,$key,$tablename,$fieldkey,$fieldlabel,$keyforselect='')
	{
		// If key empty
		if ($key == '') return '';

        //print 'param: '.$key.'-'.$keydatabase.'-'.$this->trans($key); exit;

		// Check if in language array (this can call getTradFromKey)
		if ($this->transnoentitiesnoconv($key) != $key)
		{
			return $this->transnoentitiesnoconv($key);    // Found in language array
		}

		// Check in cache
		if (isset($this->cache_labels[$tablename][$key]))	// Can be defined to 0 or ''
		{
			return $this->cache_labels[$tablename][$key];   // Found in cache
		}

		$sql = "SELECT ".$fieldlabel." as label";
		$sql.= " FROM ".MAIN_DB_PREFIX.$tablename;
		$sql.= " WHERE ".$fieldkey." = '".($keyforselect?$keyforselect:$key)."'";
		dol_syslog(get_class($this).'::getLabelFromKey sql='.$sql,LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $this->cache_labels[$tablename][$key]=$obj->label;
			else $this->cache_labels[$tablename][$key]=$key;

			$db->free($resql);
			return $this->cache_labels[$tablename][$key];
		}
		else
		{
			$this->error=$db->lasterror();
			dol_syslog(get_class($this).'::getLabelFromKey error='.$this->error,LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Return a currency code into its symbol
	 *
	 *  @param	string	$currency_code		Currency Code
	 *  @param	string	$amount				If not '', show currency + amount according to langs ($10, 10€).
	 *  @return	string						Amount + Currency symbol encoded into UTF8
	 */
	function getCurrencyAmount($currency_code, $amount)
	{
		$symbol=$this->getCurrencSymbol($currency_code);

		if (in_array($currency_code, array('USD'))) return $symbol.$amount;
		else return $amount.$symbol;
	}

	/**
	 *	Return a currency code into its symbol
	 *
	 *  @param	string	$currency_code		Currency code
	 *  @param	string	$forceloadall		1=Force to load all currencies into cache. We know we need to use all of them. By default read and cache only required currency.
	 *  @return	string						Currency symbol encoded into UTF8
	 */
	function getCurrencySymbol($currency_code, $forceloadall=0)
	{
		$currency_sign = '';	// By default return iso code

		if (function_exists("mb_convert_encoding"))
		{
			$this->load_cache_currencies($forceloadall?'':$currency_code);

			if (isset($this->cache_currencies[$currency_code]) && ! empty($this->cache_currencies[$currency_code]['unicode']) && is_array($this->cache_currencies[$currency_code]['unicode']))
			{
				foreach($this->cache_currencies[$currency_code]['unicode'] as $unicode)
				{
					$currency_sign .= mb_convert_encoding("&#{$unicode};", "UTF-8", 'HTML-ENTITIES');
				}
			}
		}

		return ($currency_sign?$currency_sign:$currency_code);
	}

	/**
	 *  Load into the cache, all currencies
	 *
	 *	@param	string	$currency_code		Get only currency. Get all if ''.
	 *  @return int             			Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function load_cache_currencies($currency_code)
	{
		global $db;

		if (! empty($currency_code) && isset($this->cache_currencies[$currency_code])) return 0;    // Value already into cache

		$sql = "SELECT code_iso, label, unicode";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_currencies";
		$sql.= " WHERE active = 1";
		if (! empty($currency_code)) $sql.=" AND code_iso = '".$currency_code."'";
		//$sql.= " ORDER BY code_iso ASC"; // Not required, a sort is done later

		dol_syslog(get_class($this).'::load_cache_currencies sql='.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$this->load("dict");
			$label=array();
			foreach($this->cache_currencies as $key => $val) $label[$key]=$val['label'];

			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$this->cache_currencies[$obj->code_iso]['label'] = ($obj->code_iso && $this->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$this->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
				$this->cache_currencies[$obj->code_iso]['unicode'] = (array) json_decode($obj->unicode, true);
				$label[$obj->code_iso] = $this->cache_currencies[$obj->code_iso]['label'];
				$i++;
			}
			//print count($label).' '.count($this->cache_currencies);
			array_multisort($label, SORT_ASC, $this->cache_currencies);
			//var_dump($this->cache_currencies);	$this->cache_currencies is now sorted onto label
			return $num;
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}

	function get_translations_for_substitutions() {
		$substitutionarray = array();

		foreach($this->tab_translate as $code => $label) {
			$substitutionarray['lang_'.$code] = $label;
		}

		return $substitutionarray;
	}
}

?>
