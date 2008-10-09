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

    var $dir;						// Directory with translation files
    var $dir_bis;					// Second directory with translation files (for development on two workspaces)

    var $defaultlang;				// Langue courante en vigueur de l'utilisateur

    var $tab_loaded=array();		// Tableau pour signaler les fichiers deja charges
    var $tab_translate=array();		// Tableau des traductions
	
    var $cache_labels=array();		// Cache for labels
	
    var $charset_inputfile='ISO-8859-1';	// Codage used to encode lang files (used if CHARSET not found in file)
	var $charset_output='UTF-8';			// Codage used by defaut for "trans" method output if $conf->character_set_client not defined (character_set_client in conf.php)
	

    /**
     *  \brief      Constructeur de la classe
     *  \param      dir             Force directory that contains translation files
     *  \param      conf			Objet qui contient la config Dolibarr
     */
    function Translate($dir = "",$conf)
    {
		// If charset output is forced
		if (! empty($conf->character_set_client)) 
		{
			$this->charset_output=$conf->character_set_client;
		}
		$this->dir=(! $dir ? DOL_DOCUMENT_ROOT ."/langs" : $dir);
		// For developpement purpose
		$this->dir_bis=(defined('DOL_DOCUMENT_ROOT_BIS') ? DOL_DOCUMENT_ROOT_BIS ."/langs" : "");
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
		
        if ($srclang == 'auto')
        {
            $langpref=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $langpref=eregi_replace(";[^,]*","",$langpref);
            $langpref=eregi_replace("-","_",$langpref);

            $langlist=split("[;,]",$langpref);

            $langpart=split("_",$langlist[0]);

            if (isset($langpart[1])) $srclang=strtolower($langpart[0])."_".strtoupper($langpart[1]);
            else $srclang=strtolower($langpart[0])."_".strtoupper($langpart[0]);
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
        setlocale(LC_ALL, $this->defaultlang);    // Compenser pb de locale avec windows
        setlocale(LC_ALL, $code_lang_tiret);
        if (defined("MAIN_FORCE_SETLOCALE_LC_ALL") && MAIN_FORCE_SETLOCALE_LC_ALL) setlocale(LC_ALL, MAIN_FORCE_SETLOCALE_LC_ALL);
        if (defined("MAIN_FORCE_SETLOCALE_LC_TIME") && MAIN_FORCE_SETLOCALE_LC_TIME) setlocale(LC_TIME, MAIN_FORCE_SETLOCALE_LC_TIME);
        if (defined("MAIN_FORCE_SETLOCALE_LC_NUMERIC") && MAIN_FORCE_SETLOCALE_LC_NUMERIC) setlocale(LC_NUMERIC, MAIN_FORCE_SETLOCALE_LC_NUMERIC);
        if (defined("MAIN_FORCE_SETLOCALE_LC_MONETARY") && MAIN_FORCE_SETLOCALE_LC_MONETARY) setlocale(LC_MONETARY, MAIN_FORCE_SETLOCALE_LC_MONETARY);
    
        return 1;
    }


    /**
	 *  \brief      Charge en memoire le tableau de traduction pour un domaine particulier
     *              Si le domaine est deja charge, la fonction ne fait rien
     *  \param      domain      Nom du domain (fichier lang) a charger
     *  \param      alt         Utilise le fichier alternatif meme si fichier dans la langue est trouvee
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

		$searchdir=$this->dir;
		
		// If $domain is @xxx instead of xxx then we look for module lang file htdocs/xxx/langs/code_CODE/xxx.lang 
		// instead of global lang file htdocs/langs/code_CODE/xxx.lang
		if (eregi('@',$domain))	// It's a language file of a module, we look in dir of this module.
		{   
			$domain=eregi_replace('@','',$domain);
			$searchdir=DOL_DOCUMENT_ROOT ."/".$domain."/langs";
		}
		
        // Directory of translation files
        $scandir = $searchdir."/".$this->defaultlang;
        $file_lang =  $scandir . "/".$domain.".lang";
        $filelangexists=is_file($file_lang);
        
		// If development with 2 workspaces (for development purpose only)
        if (! $filelangexists && $this->dir_bis)
        {
	        $scandir = $this->dir_bis."/".$this->defaultlang;
	        $file_lang =  $scandir . "/".$domain.".lang";
	        $filelangexists=is_file($file_lang);
		}

        // Check in "always available" alternate file if not found or if asked
        if ($alt || ! $filelangexists)
        {
            // Dir of always available alternate file (en_US or fr_FR)
			if ($this->defaultlang == "en_US") $scandiralt = $searchdir."/fr_FR";
            elseif (eregi('^fr',$this->defaultlang) && $this->defaultlang != 'fr_FR') $scandiralt = $searchdir."/fr_FR";
            elseif (eregi('^en',$this->defaultlang) && $this->defaultlang != 'en_US') $scandiralt = $searchdir."/en_US";
           	else $scandiralt = $searchdir."/en_US";

            $file_lang = $scandiralt . "/".$domain.".lang";
            $filelangexists=is_file($file_lang);
            $alt=1;
        }

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
									$this->charset_inputfile=strtoupper($value);
									//print 'File '.$file_lang.' has format '.$this->charset_inputfile.'<br>';
								}
								else
								{
									// On stocke toujours dans le tableau Tab en ISO
		                        	if ($this->charset_inputfile == 'UTF-8')      $value=utf8_decode($value);
		                        	//if ($this->charset_inputfile == 'ISO-8859-1') $value=$value;

									//$this->setTransFromTab($key,$value);
									$this->tab_translate[$key]=$value;
									if ($enablelangcacheinsession) $tabtranslatedomain[$key]=$value;	// To save lang in session
								}
	                        }
	                    }
	                }
					fclose($fp);

	                // Pour les langues aux fichiers parfois incomplets, on charge la langue alternative
	                if (! $alt && $this->defaultlang != "fr_FR" && $this->defaultlang != "en_US")
	                {
	                    dolibarr_syslog("Translate::Load loading alternate translation file (to complete ".$this->defaultlang."/".$domain.".lang file)", LOG_DEBUG);
	                    $this->load($domain,1);
	                }

	                $this->tab_loaded[$domain]=1;           // Marque ce fichier comme charge

					// To save lang in session
					if ($enablelangcacheinsession && sizeof($tabtranslatedomain)) $_SESSION['lang_'.$domain]=$tabtranslatedomain;
	            }
			}
        }
        else
        {
	        $this->tab_loaded[$domain]=2;           // Marque ce fichier comme non trouve
        }
		
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

			$newstr=$this->convToOuptutCharset($newstr);
			
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
			return $this->convToOuptutCharset($newstr);
		}
    }


    /**
     *  \brief       Retourne la version traduite du texte pass� en param�tre
     *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
     *               et si toujours pas trouv�, il est retourn� tel quel.
     *               Les param�tres de cette m�thode ne doivent pas contenir de balises HTML.
     *  \param       key         cl� de chaine a traduire
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
        return $this->convToOuptutCharset($newstr);
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
     *  \brief       Convertit une chaine dans le charset de sortie
     *  \param       str            chaine a convertir
     *  \return      string         chaine traduite
     */
    function convToOuptutCharset($str)
    {
		if ($this->charset_output=='UTF-8')      	$str=utf8_encode($str);
		//if ($this->charset_output=='ISO-8859-1')	$str=$str;
		return $str;
    }


    /**
     *  \brief       Retourne la liste des langues disponibles
     *  \return      array     list of languages
     */
    function get_available_languages($langdir=DOL_DOCUMENT_ROOT)
    {
        // On parcour le r�pertoire langs pour d�tecter les langues disponibles
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
     *  \brief       Exp�die le header correct et retourne le d�but de la page html
     *  [en]         Send header and return a string of html start page
     *  \return      string      html header avec charset
     */
    function lang_header()
    {
        $texte = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$this->charset_output."\">\n";
        return $texte;
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
        $htmlfile=$this->dir."/".$this->defaultlang."/".$filename;
        if (is_readable($htmlfile)) return true;

        if ($searchalt) {
            // Test si fichier dans repertoire de la langue alternative
            if ($this->defaultlang != "en_US") $htmlfilealt = $this->dir."/en_US/".$filename;   
            else $htmlfilealt = $this->dir."/fr_FR/".$filename;
            if (is_readable($htmlfilealt)) return true;
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
        $htmlfile=$this->dir."/".$this->defaultlang."/".$filename;
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
            if ($this->defaultlang != "en_US") $htmlfilealt = $this->dir."/en_US/".$filename;   
            else $htmlfilealt = $this->dir."/fr_FR/".$filename;
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
        
        return false;
    }

    /**
     *      \brief      Return a label for a key. Store key-label in a cache.
     * 		\param		db			Database handler
     * 		\param		key			Key to get label
     * 		\param		tablename	Table name
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
        if (! empty($this->cache_labels[$tablename][$key]))
        {
        	return $this->cache_labels[$tablename][$key];    // Found in cache
        }

        $sql = "SELECT ".$fieldlabel." as label";
        $sql.= " FROM ".MAIN_DB_PREFIX.$tablename;
        $sql.= " WHERE ".$fieldkey." = '".$key."'";
        dolibarr_syslog('Translate::getLabelFromKey sql='.$sql,LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            $this->cache_labels[$tablename][$key]=$obj->label;
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
