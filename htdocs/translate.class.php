<?php
/* ***************************************************************************
 * Copyright (C) 2001 Eric Seigne         <erics@rycks.com>
 * Copyright (C) 2004 Destailleur Laurent <eldy@users.sourceforge.net>
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

/*!	\file htdocs/translate.class.php
		\brief  Fichier de la classe de traduction
		\author	Laurent Destailleur
		\version $Revision$
*/


/*! \class Translate
		\brief Classe permettant de gérer les traductions
*/

class Translate {

    var $tab_loaded=array();
    var $tab_translate=array();

    var $defaultlang;
    var $dir;
    var $debug;

    /**
     *    \brief     Constructeur de la classe
     *    \param     dir             repertoire racine des fichiers de traduction
     *    \param     defaultlang     langue par defaut à utiliser
     */
		
    function Translate($dir = "", $defaultlang = "") {
        $this->dir=$dir;
        $this->defaultlang=$defaultlang;

        $this->tab_translate = array();
    }

    /*!
				\brief  Charge en mémoire le tableau de traduction pour un domaine particulier
                Si le domaine est deja chargé, la fonction ne fait rien
        \param  domain      Nom du domain (fichier lang) à charger
    */
		
    function Load($domain = "main") {
        if ($this->tab_loaded[$domain]) { return; }   # Ce fichier est deja chargé

        $scandir    = $this->dir."/".$this->defaultlang;    # Repertoire de traduction
        $scandiralt = $this->dir."/fr_FR";                  # Repertoire alternatif
    
        $file_lang =  $scandir . "/$domain.lang";
        if (! is_file($file_lang)) {
            $file_lang = $scandiralt . "/$domain.lang";
        }
        
        /* initialize tabs */
        $i = 0;
        if(is_file($file_lang)) {
            //print "Ouverture fichier $file_lang";
            if($fp = @fopen($file_lang,"rt")){
                $finded = 0;
                while (($ligne = fgets($fp,4096)) && ($finded == 0)){
                    if ($ligne[0] != "\n" && $ligne[0] != " " && $ligne[0] != "#") {
                        $tab=split('=',$ligne,2);
                        //print "Domain=$domain, found a string for $tab[0] with value $tab[1]<br>";
                        $this->tab_translate[$tab[0]]=trim($tab[1]);
                    }
                }
                fclose($fp);
                $this->tab_loaded[$domain]=1;   # Marque ce fichier comme chargé
            }

        }

    }

    /*!     
    		\brief      Retourne la liste des domaines chargées en memoire
            \return     array       Tableau des domaines chargées
    */
		
    function list_domainloaded() {
        return join(",",array_keys($this->tab_loaded));
    }
    
    
    /**
     *  \brief       Retourne la version traduite du texte passé en paramètre
     *               Si il n'y a pas de correspondance pour ce texte, il est retourné
     *               "tel quel" précédé d'un "<b>[vo]</b> <i>" et terminé par un </i>
     *  [en]         Return translated version of parameter string
     *  \param       str         original string to translate
     *  \param       param1      chaine de param1
     *  \param       param2      chaine de param1
     *  \param       param3      chaine de param1
     *  \return      string      translated version of parameter string, or original version of this string with "<b>[vo]</b> <i>" before and "</i>" after
     */
		 
    function trans($str, $param1='', $param2='', $param3='') {
        if ($this->tab_translate[$str]) {
            // Si la traduction est disponible
            return sprintf($this->tab_translate[$str],$param1,$param2,$param2);
        }
        return $str;
    }

    /**
    *  \brief       Retourne la liste des langues disponibles
    *  \return      array     list of languages
    */
		
    function get_available_languages()
    {
      // On parcour le répertoire langs pour détecter les langues dispo
      $handle=opendir(DOL_DOCUMENT_ROOT ."/langs");
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
    
        $charset = "iso-8859-1";
    
        //header("Content-Type: text/html; charset=$charset");
        $texte .= "<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">\n";
    
        return $texte;
    }

}

?>
