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
 * ***************************************************************************
 * File  : translate.class.php
 * Author  : Eric SEIGNE
 *           mailto:erics@rycks.com
 *           http://www.rycks.com/
 * Date    : 09/09/2001
 * Licence : GNU/GPL Version 2 ou plus
 * Modif : Rodolphe Quiedeville
 *
 * Description:
 * ------------
 *
 *
 *
 * @version    1.0
 * @author     Eric Seigne
 * @project    AbulEdu
 * @copyright  Eric Seigne 09/09/2001
 *
 * ************************************************************************* */

Class Translate {
    var $tab_langs;
    var $tab_translate;
    var $file_lang;
    /** Default language interface (isocode) */
    var $defaultiso;
    /** Source language (isocode) */
    var $sourceiso;
    /** This session language (isocode) */
    var $sessioniso;
    /** Where are languages files ? */
    var $dir;
    var $debug;

    //-------------------------------------------------
    /** Constructor */
    function Translate($dir = "", $sourceiso = "", $defaultiso = "", $sessioniso = "")
    {
        $this->tab_langs = array();
        $this->tab_translate = array();
        $this->file_lang = "";
        $this->debug = 0;
        $this->dir = $dir;
        $this->sessioniso = $sessioniso;
        $this->sourceiso = $sourceiso;
        $this->defaultiso = $defaultiso;
    
        if ($sessioniso == 'fr') {
            // Français demandé, on ne fait rien
            return;
        }

        //Si on a une langue par defaut
        if(($this->defaultiso != "") && ($this->sessioniso == ""))
        $this->file_lang = $this->dir . "/" . $this->defaultiso;
        else if($this->sessioniso != "")
        $this->file_lang = $this->dir . "/" . $this->sessioniso;

        /* initialize tabs */
        $i = 0;
        if(is_dir($this->dir)) {
            $filet = $this->dir . "/" . $sessioniso;
            //print "Ouverture fichier $filet";
            if($fp = @fopen($filet,"rt")){
                $finded = 0;
                while (($ligne = fgets($fp,4096)) && ($finded == 0)){
                    if ($ligne[0] != "\n" && $ligne[0] != " " && $ligne[0] != "#") {
                        $tab=split('=',$ligne);
                        //print "Found a string for $tab[0] with value $tab[1]<br>";
                        $this->tab_translate[$tab[0]]=$tab[1];
                    }
                }
                fclose($fp);
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
    function trans($str) {
        if ($this->tab_translate[$str]) {
            // Si la traduction est disponible
    
            return $this->tab_translate[$str];
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
