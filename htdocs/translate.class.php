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

Class Translate {
    var $tab_loaded;
    var $tab_translate;

    var $defaultlang;
    var $dir;
    var $debug;

    function Translate($dir = "", $defaultlang = "") {
        $this->dir=$dir;
        $this->defaultlang=$defaultlang;

        $this->tab_translate = array();
    }

    
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

    /*
    *  Return translated version of parameter string
    *  [fr] Retourne la version traduite du texte passé en paramètre
    *       Si il n'y a pas de correspondance pour ce texte, il est retourn
    *       "tel quel" précédé d'un "<b>[vo]</b> <i>" et terminé par un </i>
    *
    *  @access     public
    *  @return     string     translated version of parameter string, or original version of this string with "<b>[vo]</b> <i>" before and "</i>" after
    *  @param      string     $str  original string to translate
    *  @param      int        $mark bolean, 1 or nothing: add [vo] if this translation does not exists, 0 don't add [vo] tags
    */
    function trans($str, $param1='', $param2='', $param3='') {
        if ($this->tab_translate[$str]) {
            // Si la traduction est disponible
            return sprintf($this->tab_translate[$str],$param1,$param2,$param2);
        }
        return $str;
    }

    /**
    *  Return the list of available languages
    *  [fr] Retourne la liste des langues disponibles
    *
    *  @access     public
    *  @return     array: list of languages
    */
    function get_available_languages()
    {
    
    }
    
    /**
    *  Send header and return a string of html start page
    *  [fr] Expédie le header correct et retourne le début de la page html
    *
    *  @access     public
    *  @return     string
    */
    function lang_header()
    {
    
        $charset = "iso-8859-1";
    
        //header("Content-Type: text/html; charset=$charset");
        $texte .= "<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">\n";
    
        return $texte;
    }


}

// Pour compatibilité avec l'existant. Il existe quelques appels a une fonction
// tranlsate. On l'implémente donc ici mais il vaut mieux utiliser $langs->trans
function translate($str) {

    return $str;
}

?>
