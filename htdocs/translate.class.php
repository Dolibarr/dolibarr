<?php
/* ***************************************************************************
 * Copyright (C) 2001      Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004-2005 Destailleur Laurent <eldy@users.sourceforge.net>
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
    	\file       htdocs/translate.class.php
		\brief      Fichier de la classe de traduction
		\author	    Laurent Destailleur
		\version    $Revision$
*/


/** 
        \class      Translate
		\brief      Classe permettant de gérer les traductions
*/

class Translate {

    var $dir;
    var $defaultlang;

    var $tab_loaded=array();
    var $tab_translate=array();


    /**
     *  \brief      Constructeur de la classe
     *  \param      dir             repertoire racine des fichiers de traduction
     *  \param      defaultlang     langue par defaut à utiliser
     */
		
    function Translate($dir = "", $defaultlang = "") {
        $this->dir=$dir;
        $this->defaultlang=$defaultlang;
    }

    /**
	 *  \brief      Charge en mémoire le tableau de traduction pour un domaine particulier
     *              Si le domaine est deja chargé, la fonction ne fait rien
     *  \param      domain      Nom du domain (fichier lang) à charger
     *  \param      alt         Charge le fichier alternatif meme si fichier dans la langue est trouvé
     */
		
    function Load($domain,$alt=0)
    {
        if (isset($this->tab_loaded[$domain]) && $this->tab_loaded[$domain]) { return; }    // Le fichier de ce domaine est deja chargé
        
        // Repertoire de traduction
        $scandir = $this->dir."/".$this->defaultlang;
        $file_lang =  $scandir . "/$domain.lang";

        if ($alt || ! is_file($file_lang)) {
            // Repertoire de la langue alternative
            if ($this->defaultlang != "en_US") $scandiralt = $this->dir."/en_US";   
            else $scandiralt = $this->dir."/fr_FR";
            $file_lang = $scandiralt . "/$domain.lang";
            $alt=1;
        }
        
        $i = 0;
        if(is_file($file_lang)) {
            if($fp = @fopen($file_lang,"rt")){
                $finded = 0;
                while (($ligne = fgets($fp,4096)) && ($finded == 0)){
                    if ($ligne[0] != "\n" && $ligne[0] != " " && $ligne[0] != "#") {
                        $tab=split('=',$ligne,2);
                        //print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
                        if (! isset($this->tab_translate[$tab[0]])) $this->tab_translate[$tab[0]]=trim($tab[1]);
                    }
                }
                fclose($fp);

                // Pour les langues aux fichiers parfois incomplets, on charge la langue alternative
                if (! $alt && $this->defaultlang != "fr_FR" && $this->defaultlang != "en_US") {
                    dolibarr_syslog("translate::load loading alternate translation file");
                    $this->load($domain,1);
                }

                $this->tab_loaded[$domain]=1;           // Marque ce fichier comme chargé
            }
        
        }
        
   }

    /**     
     *	\brief      Retourne la liste des domaines chargées en memoire
     *  \return     array       Tableau des domaines chargées
     */
		
    function list_domainloaded() {
        return join(",",array_keys($this->tab_loaded));
    }
    
    
    /**
     *  \brief       Retourne la version traduite du texte passé en paramètre
     *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
     *               et si toujours pas trouvé, il est retourné tel quel
     *  \param       str         chaine a traduire
     *  \param       param1      chaine de param1
     *  \param       param2      chaine de param1
     *  \param       param3      chaine de param1
     *  \return      string      chaine traduite
     */
		 
    function trans($str, $param1='', $param2='', $param3='') {
        return $this->transnoentities($str,htmlentities($param1),htmlentities($param2),htmlentities($param3));
    }

    /**
     *  \brief       Retourne la version traduite du texte passé en paramètre
     *               Si il n'y a pas de correspondance pour ce texte, on cherche dans fichier alternatif
     *               et si toujours pas trouvé, il est retourné tel quel
     *  \param       str         chaine a traduire
     *  \param       param1      chaine de param1
     *  \param       param2      chaine de param1
     *  \param       param3      chaine de param1
     *  \return      string      chaine traduite
     */
		 
    function transnoentities($str, $param1='', $param2='', $param3='') {
        if (isset($this->tab_translate[$str]) && $this->tab_translate[$str]) {
            // Si la traduction est disponible
            return sprintf($this->tab_translate[$str],$param1,$param2,$param3);
        }
        return $str;
    }

    /**
     *  \brief       Retourne la version traduite du texte passé en paramètre complété du code pays
     *  \param       str            chaine a traduire
     *  \param       countrycode    code pays (FR, ...)
     *  \return      string         chaine traduite
     */

    function transcountry($str, $countrycode) {
        if ($this->tab_translate["$str$countrycode"]) return $this->trans("$str$countrycode");
        else return $this->trans("$str");
    }


    /**
     *  \brief       Retourne la liste des langues disponibles
     *  \return      array     list of languages
     */
		
    function get_available_languages($langdir=DOL_DOCUMENT_ROOT)
    {
      // On parcour le répertoire langs pour détecter les langues disponibles
      $handle=opendir($langdir ."/langs");
      $langs_available=array();
      while ($file = trim(readdir($handle))){
    	if($file != "." && $file != ".." && $file != "CVS") {
          array_push($langs_available,$file);
        }
      }
      return $langs_available;
    }
    
    /**
     *  \brief       Expédie le header correct et retourne le début de la page html
     *  [en]         Send header and return a string of html start page
     *  \return      string      html header avec charset
     */
		
    function lang_header()
    {
        $this->load("main");
        $charset=$this->trans("charset");
        if (! $charset) $charset="iso-8859-1";
    
        //header("Content-Type: text/html; charset=$charset");
        $texte = "<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">\n";
    
        return $texte;
    }

}

?>
