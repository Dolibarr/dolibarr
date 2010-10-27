<?php
/* Copyright (C) 2001      Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004-2010 Destailleur Laurent <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin       <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *   	\file       htdocs/core/class/translate.class.php
 *		\brief      File for Tanslate class
 *		\author	    Eric Seigne
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */


/**
 *      \class      Translate
 *		\brief      Class to manage translations
 */
class Translate {

	var $dir;						// Directories that contains /langs subdirectory

	var $defaultlang;				// Langue courante en vigueur de l'utilisateur
	var $direction = 'ltr';			// Left to right or Right to left

	var $tab_translate=array();		// Tableau des traductions
	var $tab_loaded=array();		// Array to store result after loading each language file

	var $cache_labels=array();		// Cache for labels

	var $charset_inputfile=array();	// To store charset encoding used for language
	var $charset_output='UTF-8';	// Codage used by "trans" method outputs


	/**
	 *  \brief      Constructeur de la classe
	 *  \param      dir             Force directory that contains /langs subdirectory
	 *  \param      conf			Objet qui contient la config Dolibarr
	 */
	function Translate($dir = "",$conf)
	{
		// If charset output is forced
		if (! empty($conf->file->character_set_client))
		{
			$this->charset_output=$conf->file->character_set_client;
		}
		if ($dir) $this->dir=array($dir);
		else $this->dir=$conf->file->dol_document_root;
	}


	/**
	 *  \brief      Set accessor for this->defaultlang
	 *  \param      srclang     	Language to use
	 */
	function setDefaultLang($srclang='fr_FR')
	{
		//dol_syslog("Translate::setDefaultLang srclang=".$srclang,LOG_DEBUG);

		$this->origlang=$srclang;

		if (empty($srclang) || $srclang == 'auto')
		{
			$langpref=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langpref=preg_replace("/;([^,]*)/i","",$langpref);
			$langpref=str_replace("-","_",$langpref);
			$langlist=preg_split("/[;,]/",$langpref);
			$codetouse=$langlist[0];
		}
		else $codetouse=$srclang;

		// We redefine $srclang
		$langpart=explode("_",$codetouse);
		//print "Short before _ : ".$langpart[0].'/ Short after _ : '.$langpart[1].'<br>';

		if (isset($langpart[1]))	// If its a long code xx_YY
		{
			// Array to convert short lang code into other long code.
			$longforshort=array('ar'=>'ar_AR');
			if (isset($longforshort[strtolower($langpart[0])])) $srclang=$longforshort[strtolower($langpart[0])];
			else {
				$srclang=strtolower($langpart[0])."_".strtoupper($langpart[1]);
				// Array to convert long lang code into other long code.
				$longforlong=array('no_nb'=>'nb_NO');
				if (isset($longforlong[strtolower($srclang)])) $srclang=$longforlong[strtolower($srclang)];
			}
		}
		else {							// If its a short code xx
			// Array to convert short lang code into long code.
			$longforshort=array('en'=>'en_US', 'ca'=>'ca_ES', 'nb'=>'nb_NO', 'no'=>'nb_NO');
			if (isset($longforshort[strtolower($langpart[0])])) $srclang=$longforshort[strtolower($langpart[0])];
			else $srclang=strtolower($langpart[0])."_".strtoupper($langpart[0]);
		}

		$this->defaultlang=$srclang;
		//print $this->defaultlang;
	}


	/**
	 *  \brief      Return active language code for current user
	 * 	\remarks	Accessor for this->defaultlang
	 *  \return     string      Language code used (en_US, en_AU, fr_FR, ...)
	 */
	function getDefaultLang()
	{
		return $this->defaultlang;
	}


	/**
	 *  \brief      Load translation key-value for a particular file, into a memory array.
	 *              If data for file already loaded, do nothing.
	 * 				All data in translation array are stored in UTF-8 format.
	 *  \param      domain      		File name to load (.lang file). Use file@module if file is in a module directory.
	 *  \param      alt         		0 (try xx_ZZ then 1), 1 (try xx_XX then 2), 2 (try en_US or fr_FR or es_ES)
	 * 	\param		stopafterdirection	Stop when the DIRECTION tag is found (optimize)
	 * 	\param		forcelangdir		To force a lang directory
	 *	\return		int					<0 if KO, 0 if already loaded, >0 if OK
	 *	\remarks	tab_loaded is completed with $domain key.
	 *				Value for hash are: 1:Loaded from disk, 2:Not found, 3:Loaded from cache
	 */
	function Load($domain,$alt=0,$stopafterdirection=0,$forcelangdir='')
	{
		global $conf;

		// Check parameters
		if (empty($domain))
		{
			dol_print_error('',"Translate::Load ErrorWrongParameters");
			exit;
		}

		//dol_syslog("Translate::Load Start domain=".$domain." alt=".$alt." forcelangdir=".$forcelangdir." this->defaultlang=".$this->defaultlang);

		$newdomain = $domain;
		$modulename = '';

		// Search if module directory name is different of lang file name
		if (preg_match('/^([^@]+)?@([^@]+)$/i',$domain,$regs))
		{
			$newdomain = (!empty($regs[1])?$regs[1]:$regs[2]);
			$modulename = (!empty($regs[1])?$regs[2]:'');
		}

		// Check cache
		if (! empty($this->tab_loaded[$newdomain]))	// File already loaded for this domain
		{
			//dol_syslog("Translate::Load already loaded for ".$domain);
			return 0;
		}

		$langofdir=(empty($forcelangdir)?$this->defaultlang:$forcelangdir);
		$fileread=0;
		//dol_syslog("Translate::Load langofdir=".$langofdir);

		// Redefine alt
		$langarray=explode('_',$langofdir);
		if ($alt < 1 && strtolower($langarray[0]) == strtolower($langarray[1])) $alt=1;
		if ($alt < 2 && (strtolower($langofdir) == 'en_us' || strtolower($langofdir) == 'fr_fr' || strtolower($langofdir) == 'es_es')) $alt=2;

		foreach($this->dir as $searchdir)
		{
			// If $domain is "file@module" instead of "file" then we look for module lang file htdocs/module/langs/code_CODE/file.lang
			// instead of file htdocs/langs/code_CODE/filelang
			if (preg_match('/@/',$domain))	$searchdir = $searchdir."/".(!empty($modulename)?$modulename:$newdomain)."/langs";
			else $searchdir=$searchdir."/langs";

			// Directory of translation files
			$scandir = $searchdir."/".$langofdir;
			$file_lang =  $scandir . "/".$newdomain.".lang";
			$file_lang_osencoded=dol_osencode($file_lang);
			$filelangexists=is_file($file_lang_osencoded);

			//dol_syslog('Translate::Load Try to read for alt='.$alt.' langofdir='.$langofdir.' file_lang='.$file_lang." => ".$filelangexists);

			if ($filelangexists)
			{
				$found=false;

				// Enable cache of lang file in memory (not by default)
				$usecachekey='';
				// Using a memcached server
				if (! empty($conf->memcached->enabled))
				{
					$usecachekey=$newdomain.'_'.$langofdir;
				}
				// Using cache with shmop. Speed gain: 40ms - Memory overusage: 200ko (Size of session cache file)
				else if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02))
				{
					$usecachekey=$newdomain;
				}

				if ($usecachekey)
				{
					require_once(DOL_DOCUMENT_ROOT ."/lib/memory.lib.php");
					$tmparray=dol_getcache($usecachekey);
					if (is_array($tmparray) && sizeof($tmparray))
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

						while ($ligne = fgets($fp,4096))	// Ex: Need 225ms for all fgets on all lang file for Third party page. Same speed than file_get_contents
						{
							if ($ligne[0] != "\n" && $ligne[0] != " " && $ligne[0] != "#")
							{
								$tab=explode('=',$ligne,2);
								$key=trim($tab[0]);
								//print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
								if (empty($this->tab_translate[$key]) && isset($tab[1]))
								{
									$value=trim(preg_replace('/\\n/',"\n",$tab[1]));

									if ($key == 'CHARSET')		// This is to declare in which charset files are encoded
									{
										$this->charset_inputfile[$newdomain]=strtoupper($value);
										//print 'File '.$file_lang.' is declared to have format '.$this->charset_inputfile[$newdomain].'<br>';
									}
									elseif ($key == 'DIRECTION')	// This is to declare direction of language
									{
										if ($alt < 2)	// We do not load direction for alternate files 2
										{
                                            $this->tab_translate[$key]=$value;
											if ($stopafterdirection) break;
										}
									}
									else
									{
										// On stocke toujours dans le tableau Tab en UTF-8
										if (empty($this->charset_inputfile[$newdomain]) || $this->charset_inputfile[$newdomain] == 'ISO-8859-1') $value=utf8_encode($value);

										//print 'XX'.$key;
										$this->tab_translate[$key]=$value;

										if ($usecachekey) $tabtranslatedomain[$key]=$value;	// To save lang content in cache
									}
								}
							}
						}
						fclose($fp);
						$fileread=1;

						// To save lang content for usecachekey into cache
						if ($usecachekey && sizeof($tabtranslatedomain))
						{
							require_once(DOL_DOCUMENT_ROOT ."/lib/memory.lib.php");
							$size=dol_setcache($usecachekey,$tabtranslatedomain);
						}
						//exit;
						break;		// Break loop on each root dir
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
			if ($fileread) $this->tab_loaded[$newdomain]=1;	// Set domain file as loaded

			if (empty($this->tab_loaded[$newdomain])) $this->tab_loaded[$newdomain]=2;           // Marque ce fichier comme non trouve
		}

		// Check to be sure that SeparatorDecimal differs from SeparatorThousand
		if (! empty($this->tab_translate["SeparatorDecimal"]) && ! empty($this->tab_translate["SeparatorThousand"])
		&& $this->tab_translate["SeparatorDecimal"] == $this->tab_translate["SeparatorThousand"]) $this->tab_translate["SeparatorThousand"]='';

		return 1;
	}


	/**
	 *  \brief      Mark domain as not loaded to be able to load it again.
	 *  \param      domain      File name to load (.lang file). Use @ before value if domain is in a module directory.
	 */
	function UnLoad($domain)
	{
		dol_syslog("Translate::UnLoad domain=".$domain." marked as not loaded", LOG_DEBUG);
		$this->tab_loaded[$domain]=0;
	}

	/**
	 *	\brief      Retourn list of files already loaded into memory
	 *  \return     string      List of files loaded into memory "main=1,errors=1,..."
	 */
	function list_domainloaded()
	{
		$ret='Loaded from '.join(',',$this->dir).': ';
		foreach($this->tab_loaded as $key=>$val)
		{
			if ($ret) $ret.=',';
			$ret.=$key.'='.$val;
		}
		return $ret;
	}

	/**
	 * Return translated value of key. Search in lang file, then into database.
	 * If not found, return key
	 *
	 * @param  key
	 * @return string
	 */
	function getTradFromKey($key)
	{
		global $db;
		$newstr=$key;
		if (preg_match('/CurrencySing([A-Z][A-Z][A-Z])$/i',$key,$reg))
		{
			$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','labelsing');
		}
		else if (preg_match('/Currency([A-Z][A-Z][A-Z])$/i',$key,$reg))
		{
			$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','label');
		}
		else if (preg_match('/SendingMethod([0-9A-Z]+)$/i',$key,$reg))
		{
			$newstr=$this->getLabelFromKey($db,$reg[1],'c_shipment_mode','code','libelle');
		}
        else if (preg_match('/PaymentTypeShort([0-9A-Z]+)$/i',$key,$reg))
        {
            $newstr=$this->getLabelFromKey($db,$reg[1],'c_paiement','code','libelle');
        }
		return $newstr;
	}


	/**
	 *  Return text translated of text received as parameter (and encode it into HTML)
	 *              Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *              et si toujours pas trouve, il est retourne tel quel
	 *              Les parametres de cette methode peuvent contenir de balises HTML.
	 *  @param      key         cle de chaine a traduire
	 *  @param      param1      chaine de param1
	 *  @param      param2      chaine de param2
	 *  @param      param3      chaine de param3
	 *  @param      param4      chaine de param4
	 *	@param		maxsize		taille max
	 *  @return     string      Chaine traduite et code en HTML
	 */
	function trans($key, $param1='', $param2='', $param3='', $param4='', $maxsize=0)
	{
		global $mysoc;

		if (! empty($this->tab_translate[$key]))	// Translation is available
		{
			$str=preg_replace('/\\\"/','"',$this->tab_translate[$key]);	// To solve some translation keys containing key=abc\"def\"ghi instead of abc"def"ghi

			if (! preg_match('/^Format/',$key)) $str=sprintf($str,$param1,$param2,$param3,$param4);	// Replace %s and %d except for FormatXXX strings.
			if ($maxsize) $str=dol_trunc($str,$maxsize);
			// On remplace les tags HTML par __xx__ pour eviter traduction par htmlentities
			$newstr=str_replace('<','__lt__',$str);
			$newstr=str_replace('>','__gt__',$newstr);
			$newstr=str_replace('"','__quot__',$newstr);

			$newstr=$this->convToOutputCharset($newstr);	// Convert string to $this->charset_output

			// Cryptage en html de la chaine
			// $newstr est une chaine stockee en memoire au format $this->charset_output
			$newstr=htmlentities($newstr,ENT_QUOTES,$this->charset_output);

			// On restaure les tags HTML
			$newstr=str_replace('__lt__','<',$newstr);
			$newstr=str_replace('__gt__','>',$newstr);
			$newstr=str_replace('__quot__','"',$newstr);
			return $newstr;
		}
		else								// Translation is not available
		{
			$newstr=$this->getTradFromKey($key);
			return $this->convToOutputCharset($newstr);
		}
	}


	/**
	 *  \brief       Return translated value of a text string
	 *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *               et si toujours pas trouve, il est retourne tel quel.
	 *               Parameters of this method must not contains any HTML tags.
	 *  \param       key         key of string to translate
	 *  \param       param1      chaine de param1
	 *  \param       param2      chaine de param2
	 *  \param       param3      chaine de param3
	 *  \param       param4      chaine de param4
	 *  \return      string      chaine traduite
	 */
	function transnoentities($key, $param1='', $param2='', $param3='', $param4='')
	{
		if (! empty($this->tab_translate[$key]))
		{
			// Si la traduction est disponible
			$newstr=sprintf($this->tab_translate[$key],$param1,$param2,$param3,$param4);
		}
		else
		{
			$newstr=$this->getTradFromKey($key);
		}
		return $this->convToOutputCharset($newstr);
	}


	/**
	 *  \brief       Return translated value of a text string
	 *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
	 *               et si toujours pas trouve, il est retourne tel quel.
	 *               No convert to encoding charset of lang object is done.
	 *               Parameters of this method must not contains any HTML tags.
	 *  \param       key         key of string to translate
	 *  \param       param1      chaine de param1
	 *  \param       param2      chaine de param1
	 *  \param       param3      chaine de param1
	 *  \param       param4      chaine de param1
	 *  \return      string      chaine traduite
	 */
	function transnoentitiesnoconv($key, $param1='', $param2='', $param3='', $param4='')
	{
		if (! empty($this->tab_translate[$key]))
		{
			// Si la traduction est disponible
			$newstr=sprintf($this->tab_translate[$key],$param1,$param2,$param3,$param4);
		}
		else
		{
			$newstr=$this->getTradFromKey($key);
		}
		return $newstr;
	}


	/**
	 *  \brief       Return translation of a key depending on country
	 *  \param       str            string root to translate
	 *  \param       countrycode    country code (FR, ...)
	 *  \return      string         translated string
	 */
	function transcountry($str, $countrycode)
	{
		if ($this->tab_translate["$str$countrycode"]) return $this->trans("$str$countrycode");
		else return $this->trans($str);
	}


	/**
	 *  Retourne la version traduite du texte passe en parametre complete du code pays
	 *  @param       str            string root to translate
	 *  @param       countrycode    country code (FR, ...)
	 *  @return      string         translated string
	 */
	function transcountrynoentities($str, $countrycode)
	{
		if ($this->tab_translate["$str$countrycode"]) return $this->transnoentities("$str$countrycode");
		else return $this->transnoentities($str);
	}


	/**
	 *  \brief      Convert a string into output charset (this->charset_output that should be defined to conf->file->character_set_client)
	 *  \param      str            	String to convert
	 *  \param		pagecodefrom	Page code of src string
	 *  \return     string         	Converted string
	 */
	function convToOutputCharset($str,$pagecodefrom='UTF-8')
	{
		if ($pagecodefrom == 'ISO-8859-1' && $this->charset_output == 'UTF-8')  $str=utf8_encode($str);
		if ($pagecodefrom == 'UTF-8' && $this->charset_output == 'ISO-8859-1')	$str=utf8_decode(str_replace('€',chr(128),$str));
		return $str;
	}


	/**
	 *  \brief      Return list of all available languages
	 * 	\param		langdir		Directory to scan
	 *  \return     array     	List of languages
	 */
	function get_available_languages($langdir=DOL_DOCUMENT_ROOT,$maxlength=0)
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

				if (isset($conf->global->MAIN_SHOW_LANGUAGE_CODE) && $conf->global->MAIN_SHOW_LANGUAGE_CODE)
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
	 *  \brief      Return if a filename $filename exists for current language (or alternate language)
	 *  \param      filename        Language filename to search
	 *  \param      searchalt       Search also alernate language file
	 *  \return     boolean         true if exists and readable
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
	 *      \brief      Return full text translated to language label for a key. Store key-label in a cache.
	 *		\param		number		Number to encode in full text
	 * 		\param		isamount	1=It's an amount, 0=it's just a number
	 *      \return     string		Label translated in UTF8 (but without entities)
	 * 								10 if setDefaultLang was en_US => ten
	 * 								123 if setDefaultLang was fr_FR => cent vingt trois
	 * 		\remarks	This function need module "numberwords" to be installed. If not it will return
	 * 					same number (this module is not provided by default as it use non GPL source code).
	 */
	function getLabelFromNumber($number,$isamount=0)
	{
		global $conf;

		/*
		$outlang=$this->defaultlang;	// Output language we want
		$outlangarray=explode('_',$outlang,2);
		// If lang is xx_XX, then we use xx
		if (strtolower($outlangarray[0]) == strtolower($outlangarray[1])) $outlang=$outlangarray[0];
		*/

		$newnumber=$number;
		foreach ($conf->file->dol_document_root as $dirroot)
		{
			$dir=$dirroot."/includes/modules/substitutions";
			$fonc='numberwords';
			if (file_exists($dir.'/functions_'.$fonc.'.lib.php'))
			{
				include_once($dir.'/functions_'.$fonc.'.lib.php');
				$newnumber=numberwords_getLabelFromNumber($this,$number,$isamount);
				break;
			}
		}

		return $newnumber;
	}


	/**
	 *      Return a label for a key. Store key-label into cache variable $this->cache_labels to save SQL requests to get labels.
	 * 		@param		db			Database handler
	 * 		@param		key			Key to get label (key in language file)
	 * 		@param		tablename	Table name without prefix
	 * 		@param		fieldkey	Field for key
	 * 		@param		fieldlabel	Field for label
	 *      @return     string		Label in UTF8 (but without entities)
	 *		@remarks	This function can be used to get label in database but more often to get code from key id.
	 */
	function getLabelFromKey($db,$key,$tablename,$fieldkey,$fieldlabel)
	{
		// If key empty
		if ($key == '') return '';

		// Check in language array
		if ($this->transnoentities($key) != $key)
		{
			return $this->transnoentities($key);    // Found in language array
		}

		// Check in cache
		if (isset($this->cache_labels[$tablename][$key]))	// Can be defined to 0 or ''
		{
			return $this->cache_labels[$tablename][$key];   // Found in cache
		}

		$sql = "SELECT ".$fieldlabel." as label";
		$sql.= " FROM ".MAIN_DB_PREFIX.$tablename;
		$sql.= " WHERE ".$fieldkey." = '".$key."'";
		dol_syslog('Translate::getLabelFromKey sql='.$sql,LOG_DEBUG);
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
			dol_syslog("Translate::getLabelFromKey error=".$this->error,LOG_ERR);
			return -1;
		}
	}

}

?>
