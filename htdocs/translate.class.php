<?php
/* ***************************************************************************
 * Copyright (C) 2001 Eric Seigne <erics@rycks.com>
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
 * File  : rtplang.class.php
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

if(isset($RTPLANG_CLASS)){
  return;
}
$RTPLANG_CLASS=1;


Class rtplang {

  //-------------------------------------------------
  /** Constructor */
  function rtplang($dir = "", $sourceiso = "", $defaultiso = "", $sessioniso = "")
    {
    }
  /**
   *  Return translated version of parameter string 
   *  [fr] Retourne la version traduite du texte passé en paramètre
   *       Si il n'y a pas de correspondance pour ce texte, il est retourné
   *       "tel quel" précédé d'un "<b>[vo]</b> <i>" et terminé par un </i>
   *
   *  @access     public
   *  @return     string     translated version of parameter string, or original version of this string with "<b>[vo]</b> <i>" before and "</i>" after
   *  @param      string     $str  original string to translate
   *  @param      int        $mark bolean, 1 or nothing: add [vo] if this translation does not exists, 0 don't add [vo] tags
   */
  function translate($str, $mark){

    $retour = $str;

    return $retour;
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
      $texte .= "
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">\n";
      
      return $texte;
    }
}

/*
 *  Return translated version of parameter string 
 *  [fr] Retourne la version traduite du texte passé en paramètre
 *       Si il n'y a pas de correspondance pour ce texte, il est retourné
 *       "tel quel" précédé d'un "<b>[vo]</b> <i>" et terminé par un </i>
 *
 *  @access     public
 *  @return     string     translated version of parameter string, or original version of this string with "<b>[vo]</b> <i>" before and "</i>" after
 *  @param      string     $str  original string to translate
 *  @param      int        $mark bolean, 1 or nothing: add [vo] if this translation does not exists, 0 don't add [vo] tags
 */
function translate($str, $mark = 1){

  return $str;
}

?>
