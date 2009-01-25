<?php
/* ***************************************************************************
 * Copyright (C) 2001      Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004-2008 Destailleur Laurent <eldy@users.sourceforge.net>
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
 * ************************************************************************* */

/**
 *   	\file       htdocs/translate.class.php
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

    var $tab_loaded=array();		// Tableau pour signaler les fichiers deja charges
    var $tab_translate=array();		// Tableau des traductions

    var $cache_labels=array();		// Cache for labels

    var $charset_inputfile=array();	// To store charset encoding used for language
	var $charset_output='UTF-8';	// Codage used by default for "trans" method output if $conf->character_set_client not defined (should never happen)


    /**
     *  \brief      Constructeur de la classe
     *  \param      dir             Force directory that contains /langs subdirectory
     *  \param      conf			Objet qui contient la config Dolibarr
     */
    function Translate($dir = "",$conf)
    {
		// If charset output is forced
		if (! empty($conf->character_set_client))
		{
			$this->charset_output=$conf->character_set_client;
		}
		if ($dir) $this->dir=array($dir);
		else $this->dir=$conf->dol_document_root;
    }


	/**
	 *	\brief		Return string translated for a key
	 *				Translation array must have been loaded before.
	 *	\param		key			Key to translate
	 *	\return		string		Translated string
	 */
	function getTransFromTab($key)
	{
		if (! empty($this->tab_translate[$key]))
		{
			return $this->tab_translate[$key];
		}
		else
		{
			return '';
		}
	}

	/**
	 *	\brief		Positionne la chaine traduite pour une cl� donn�e.
	 *	\param		key			Key to translate
	 *	\return		string		Translated string
	 */
	function setTransFromTab($key,$value)
	{
		$this->tab_translate[$key]=$value;
	}


    /**
     *  \brief      Set accessor for this->defaultlang
     *  \param      srclang     	Language to use
     */
    function setDefaultLang($srclang='fr_FR')
    {
        //dolibarr_syslog("Translate::setDefaultLang ".$this->defaultlang,LOG_DEBUG);

    	$this->origlang=$srclang;

        if (empty($srclang) || $srclang == 'auto')
        {
            $langpref=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $langpref=eregi_replace(";[^,]*","",$langpref);
            $langpref=eregi_replace("-","_",$langpref);

            $langlist=split("[;,]",$langpref);

            $langpart=split("_",$langlist[0]);
            //print $langpart[0].'/'.$langpart[1];

            if (isset($langpart[1])) $srclang=strtolower($langpart[0])."_".strtoupper($langpart[1]);
            else {
            	// Array to convert short lang code into long code.
            	$longforshort=array('ca'=>'ca_ES');
            	if (isset($longforshort[strtolower($langpart[0])])) $srclang=$longforshort[strtolower($langpart[0])];
            	else $srclang=strtolower($langpart[0])."_".strtoupper($langpart[0]);
            }
        }

        $this->defaultlang=$srclang;
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
    		\brief      Positionne environnement PHP en fonction du langage
    		\remarks    Le code langue long (fr_FR, en_US, ...) doit avoir etre positionne par setDefaultLang
    		\return     int             >0 si ok, <0 so ko
    */
    function setPhpLang()
    {
        //dolibarr_syslog("Translate::setPhpLang ".$this->defaultlang,LOG_DEBUG);

        $code_lang_tiret=ereg_replace('_','-',$this->defaultlang);
        //print 'code_lang_tiret='.$code_lang_tiret;
        setlocale(LC_ALL, $this->defaultlang);    	// Some OS (Windows) need local with _
        setlocale(LC_ALL, $code_lang_tiret);    	// Other OS need local with -

        if (defined("MAIN_FORCE_SETLOCALE_LC_ALL") && MAIN_FORCE_SETLOCALE_LC_ALL)
        	$res_lc_all=setlocale(LC_ALL, MAIN_FORCE_SETLOCALE_LC_ALL.'.UTF-8', MAIN_FORCE_SETLOCALE_LC_ALL);
        if (defined("MAIN_FORCE_SETLOCALE_LC_TIME") && MAIN_FORCE_SETLOCALE_LC_TIME)
        	$res_lc_time=setlocale(LC_TIME, MAIN_FORCE_SETLOCALE_LC_TIME.'.UTF-8', MAIN_FORCE_SETLOCALE_LC_TIME);
        if (defined("MAIN_FORCE_SETLOCALE_LC_NUMERIC") && MAIN_FORCE_SETLOCALE_LC_NUMERIC)
        	$res_lc_numeric=setlocale(LC_NUMERIC, MAIN_FORCE_SETLOCALE_LC_NUMERIC.'.UTF-8', MAIN_FORCE_SETLOCALE_LC_NUMERIC);
        if (defined("MAIN_FORCE_SETLOCALE_LC_MONETARY") && MAIN_FORCE_SETLOCALE_LC_MONETARY)
        	$res_lc_monetary=setlocale(LC_MONETARY, MAIN_FORCE_SETLOCALE_LC_MONETARY.'UTF-8', MAIN_FORCE_SETLOCALE_LC_MONETARY);
        //print 'x'.$res_lc_all;
        return 1;
    }


    /**
	 *  \brief      Load in a memory array, translation key-value for a particular file.
     *              If data for file already loaded, do nothing.
     * 				All data in translation array are stored in ISO-8859-1 format.
     *  \param      domain      File name to load (.lang file). Use @ before value if domain is in a module directory.
     *  \param      alt         Use alternate file even if file in target language is found
	 *	\return		int			<0 if KO, >0 if OK
     *	\remarks	tab_loaded is completed with $domain key.
	 *				Value for key is: 1:Loaded from disk, 2:Not found, 3:Loaded from cache
	 */
    function Load($domain,$alt=0)
    {
    	// dolibarr_syslog("Translate::Load domain=".$domain." alt=".$alt);

		// Check parameters
		if (empty($domain))
		{
			dolibarr_syslog("Translate::Load ErrorWrongParameters",LOG_WARNING);
			return -1;
		}

		// Check cache
		if (! empty($this->tab_loaded[$domain])) { return; }    // Le fichier de ce domaine est deja charge

		foreach($this->dir as $searchdir)
		{
			$newalt=$alt;

			// If $domain is @xxx instead of xxx then we look for module lang file htdocs/xxx/langs/code_CODE/xxx.lang
			// instead of global lang file htdocs/langs/code_CODE/xxx.lang
			if (eregi('@',$domain))	// It's a language file of a module, we look in dir of this module.
			{
				$domain=eregi_replace('@','',$domain);
				$searchdir=$searchdir ."/".$domain."/langs";
			}
			else $searchdir=$searchdir."/langs";

	        // Directory of translation files
	        $scandir = $searchdir."/".$this->defaultlang;
	        $file_lang =  $scandir . "/".$domain.".lang";
	        $filelangexists=is_file($file_lang);
			//print 'Load default_lang='.$this->defaultlang.' alt='.$alt.' newalt='.$newalt.' '.$file_lang."-".$filelangexists.'<br>';

	        // Check in "always available" alternate file if not found or if asked
	        if ($newalt || ! $filelangexists)
	        {
	            // Dir of always available alternate file (en_US or fr_FR)
				if ($this->defaultlang == "en_US") $scandiralt = $searchdir."/fr_FR";
	            elseif (eregi('^fr',$this->defaultlang) && $this->defaultlang != 'fr_FR') $scandiralt = $searchdir."/fr_FR";
	            elseif (eregi('^en',$this->defaultlang) && $this->defaultlang != 'en_US') $scandiralt = $searchdir."/en_US";
	            else $scandiralt = $searchdir."/en_US";

	            $file_lang = $scandiralt . "/".$domain.".lang";
	            $filelangexists=is_file($file_lang);
	            $newalt=1;
	        }
			//print 'Load alt='.$alt.' newalt='.$newalt.' '.$file_lang."-".$filelangexists.'<br>';

	        if ($filelangexists)
	        {
				// Enable cache of lang file in session (faster but need more memory)
				// Speed gain: 40ms - Memory overusage: 200ko (Size of session cache file)
				$enablelangcacheinsession=false;

				if ($enablelangcacheinsession && isset($_SESSION['lang_'.$domain]))
				{
					foreach($_SESSION['lang_'.$domain] as $key => $value)
					{
						$this->tab_translate[$key]=$value;
						$this->tab_loaded[$domain]=3;           // Marque ce fichier comme charge depuis cache session
					}
				}
				else
				{
					if ($fp = @fopen($file_lang,"rt"))
		            {
		            	if ($enablelangcacheinsession) $tabtranslatedomain=array();	// To save lang in session
		                $finded = 0;
		                while (($ligne = fgets($fp,4096)) && ($finded == 0))
		                {
		                    if ($ligne[0] != "\n" && $ligne[0] != " " && $ligne[0] != "#")
		                    {
		                        $tab=split('=',$ligne,2);
		                        $key=trim($tab[0]); $value='';
		                        //print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
		                        //if (! $this->getTransFromTab($key))
		                        if (empty($this->tab_translate[$key]) && isset($tab[1]))
		                        {
			                        $value=trim(ereg_replace('\\\n',"\n",$tab[1]));

									if (eregi('^CHARSET$',$key))
									{
										// On est tombe sur une balise qui declare le format du fichier lu
										$this->charset_inputfile[$domain]=strtoupper($value);
										//print 'File '.$file_lang.' is declared to have format '.$this->charset_inputfile[$domain].'<br>';
									}
									else
									{
										// On stocke toujours dans le tableau Tab en UTF-8
			                        	//if (empty($this->charset_inputfile[$domain]) || $this->charset_inputfile[$domain] == 'UTF-8')      $value=utf8_decode($value);
			                        	if (empty($this->charset_inputfile[$domain]) || $this->charset_inputfile[$domain] == 'ISO-8859-1') $value=utf8_encode($value);

										// We do not load Separator values for alternate files
										if (! $newalt || (! eregi('^Separator',$key)))
										{
											//print 'XX'.$key;
											$this->tab_translate[$key]=$value;
										}
										if ($enablelangcacheinsession) $tabtranslatedomain[$key]=$value;	// To save lang in session
									}
		                        }
		                    }
		                }
						fclose($fp);

		                // Pour les langues aux fichiers parfois incomplets, on charge la langue alternative
		                if (! $newalt && $this->defaultlang != "fr_FR" && $this->defaultlang != "en_US")
		                {
		                    dolibarr_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$domain.".lang file)", LOG_DEBUG);
		                    $this->load($domain,1);
		                }

		                $this->tab_loaded[$domain]=1;           // Marque ce fichier comme charge

						// To save lang in session
						if ($enablelangcacheinsession && sizeof($tabtranslatedomain)) $_SESSION['lang_'.$domain]=$tabtranslatedomain;

						break;		// Break loop on each root dir
		            }
				}
	        }
		}

		if (empty($this->tab_loaded[$domain])) $this->tab_loaded[$domain]=2;           // Marque ce fichier comme non trouve

		return 1;
    }


    /**
     *	\brief      Retourne la liste des domaines charg�es en memoire
     *  \return     array       Tableau des domaines charg�es
     */
    function list_domainloaded()
    {
        $ret='';
		foreach($this->tab_loaded as $key=>$val)
		{
			if ($ret) $ret.=',';
			$ret.=$key.'='.$val;
		}
		return $ret;
    }


    /**
     *  \brief      Retourne la version traduite du texte passe en parametre en la codant en HTML
     *              Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
     *              et si toujours pas trouve, il est retourne tel quel
     *              Les parametres de cette methode peuvent contenir de balises HTML.
     *  \param      key         cle de chaine a traduire
     *  \param      param1      chaine de param1
     *  \param      param2      chaine de param2
     *  \param      param3      chaine de param3
     *  \param      param4      chaine de param4
     *	\param		maxsize		taille max
     *  \return     string      Chaine traduite et code en HTML
     */
    function trans($key, $param1='', $param2='', $param3='', $param4='', $maxsize=0)
    {
        if ($this->getTransFromTab($key))
        {
            // Translation is available
            $str=sprintf($this->tab_translate[$key],$param1,$param2,$param3,$param4);
            if ($maxsize) $str=dolibarr_trunc($str,$maxsize);
            // On remplace les tags HTML par __xx__ pour eviter traduction par htmlentities
            $newstr=ereg_replace('<','__lt__',$str);
            $newstr=ereg_replace('>','__gt__',$newstr);
            $newstr=ereg_replace('"','__quot__',$newstr);

			$newstr=$this->convToOutputCharset($newstr);	// Convert string to $this->charset_output

            // Cryptage en html de la chaine
			// $newstr est une chaine stockee en memoire au format $this->charset_output
            $newstr=htmlentities($newstr,ENT_QUOTES,$this->charset_output);

            // On restaure les tags HTML
            $newstr=ereg_replace('__lt__','<',$newstr);
            $newstr=ereg_replace('__gt__','>',$newstr);
            $newstr=ereg_replace('__quot__','"',$newstr);
            return $newstr;
        }
		else
		{
			// Translation is not available
			$newstr=$key;
			if (ereg('CurrencyShort([A-Z]+)$',$key,$reg))
			{
				global $db;
				//$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','labelshort');
				$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','code');
			}
			else if (ereg('Currency([A-Z]+)$',$key,$reg))
			{
				global $db;
				$newstr=$this->getLabelFromKey($db,$reg[1],'c_currencies','code_iso','label');
				//print "xxx".$key."-".$value."\n";
			}
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
     *  \param       param2      chaine de param1
     *  \param       param3      chaine de param1
     *  \param       param4      chaine de param1
     *  \return      string      chaine traduite
     */
    function transnoentities($key, $param1='', $param2='', $param3='', $param4='')
    {
    	$newstr=$key;
        if ($this->getTransFromTab($newstr))
        {
            // Si la traduction est disponible
            $newstr=sprintf($this->tab_translate[$newstr],$param1,$param2,$param3,$param4);
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
    	$newstr=$key;
        if ($this->getTransFromTab($newstr))
        {
            // Si la traduction est disponible
            $newstr=sprintf($this->tab_translate[$newstr],$param1,$param2,$param3,$param4);
        }
        return $newstr;
    }


    /**
     *  \brief       Retourne la version traduite du texte passe en parametre complete du code pays
     *  \param       str            chaine a traduire
     *  \param       countrycode    code pays (FR, ...)
     *  \return      string         chaine traduite
     */
    function transcountry($str, $countrycode)
    {
        if ($this->tab_translate["$str$countrycode"]) return $this->trans("$str$countrycode");
        else return $this->trans($str);
    }


    /**
     *  \brief       Retourne la version traduite du texte passe en parametre complete du code pays
     *  \param       str            chaine a traduire
     *  \param       countrycode    code pays (FR, ...)
     *  \return      string         chaine traduite
     */
    function transcountrynoentities($str, $countrycode)
    {
        if ($this->tab_translate["$str$countrycode"]) return $this->transnoentities("$str$countrycode");
        else return $this->transnoentities($str);
    }


	/**
     *  \brief      Convert a string into output charset (this->charset_output that should be defined to conf->character_set_client)
     *  \param      str            	String to convert
     *  \param		pagecodefrom	Page code of src string
     *  \return     string         	Converted string
     */
    function convToOutputCharset($str,$pagecodefrom='UTF-8')
    {
    	if ($pagecodefrom == 'ISO-8859-1' && $this->charset_output == 'UTF-8')  $str=utf8_encode($str);
		if ($pagecodefrom == 'UTF-8' && $this->charset_output == 'ISO-8859-1')	$str=utf8_decode($str);
		return $str;
    }


    /**
     *  \brief       Retourne la liste des langues disponibles
     *  \return      array     list of languages
     */
    function get_available_languages($langdir=DOL_DOCUMENT_ROOT)
    {
        // We scan directory langs to detect available languages
        $handle=opendir($langdir ."/langs");
        $langs_available=array();
        while ($file = trim(readdir($handle)))
        {
            if (eregi('^[a-z]+_[A-Z]+',$file))
            {
                array_push($langs_available,$file);
            }
        }
        return $langs_available;
    }


   /**
     *  \brief      Renvoi si le fichier $filename existe dans la version de la langue courante ou alternative
     *  \param      filename        nom du fichier � rechercher
     *  \param      searchalt       cherche aussi dans langue alternative
     *  \return     boolean         true si existe, false sinon
     */
    function file_exists($filename,$searchalt=0)
    {
        // Test si fichier dans repertoire de la langue
		foreach($this->dir as $searchdir)
		{
	        $htmlfile=$searchdir."/langs/".$this->defaultlang."/".$filename;
	        if (is_readable($htmlfile)) return true;

	        if ($searchalt) {
	            // Test si fichier dans repertoire de la langue alternative
	            if ($this->defaultlang != "en_US") $htmlfilealt = $searchdir."/langs/en_US/".$filename;
	            else $htmlfilealt = $searchdir."/langs/fr_FR/".$filename;
	            if (is_readable($htmlfilealt)) return true;
	        }
		}

        return false;
    }


   /**
     *  \brief      Renvoi le fichier $filename dans la version de la langue courante, sinon alternative
     *  \param      filename        nom du fichier a rechercher
     *  \param      searchalt       cherche aussi dans langue alternative
	 *	\return		boolean
     */
    function print_file($filename,$searchalt=0)
    {
    	global $conf;

        // Test si fichier dans repertoire de la langue
		foreach($this->dir as $searchdir)
		{
	        $htmlfile=($searchdir."/langs/".$this->defaultlang."/".$filename);
	        if (is_readable($htmlfile))
	        {
	        	$content=file_get_contents($htmlfile);
	            $isutf8=utf8_check($content);
		        if (! $isutf8 && $conf->character_set_client == 'UTF-8') print utf8_encode($content);
		        elseif ($isutf8 && $conf->character_set_client == 'ISO-8859-1') print utf8_decode($content);
		        else print $content;
	            return true;
	        }

	        if ($searchalt) {
	            // Test si fichier dans repertoire de la langue alternative
	            if ($this->defaultlang != "en_US") $htmlfilealt = $searchdir."/en_US/".$filename;
	            else $htmlfilealt = $searchdir."/langs/fr_FR/".$filename;
	            if (is_readable($htmlfilealt))
	            {
		            $content=file_get_contents($htmlfile);
	            	$isutf8=utf8_check($content);
		            if (! $isutf8 && $conf->character_set_client == 'UTF-8') print utf8_encode($content);
		            elseif ($isutf8 && $conf->character_set_client == 'ISO-8859-1') print utf8_decode($content);
		            else print $content;
		            return true;
	            }
	        }
		}

        return false;
    }

    /**
     *      \brief      Return a label for a key. Store key-label in a cache.
     * 		\param		db			Database handler
     * 		\param		key			Key to get label
     * 		\param		tablename	Table name without prefix
     * 		\param		fieldkey	Field for key
     * 		\param		fieldlabel	Field for label
     *      \return     string		Label
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
        dolibarr_syslog('Translate::getLabelFromKey sql='.$sql,LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $this->cache_labels[$tablename][$key]=$obj->label;
            else $this->cache_labels[$tablename][$key]='';
            $db->free($resql);
            return $this->cache_labels[$tablename][$key];
        }
        else
        {
			$this->error=$db->lasterror();
			dolibarr_syslog("Translate::getLabelFromKey error=".$this->error,LOG_ERR);
            return -1;
        }
    }

}

?>
