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
  function rtplang($dir = "", $sourceiso = "", $defaultiso = "", $sessioniso = ""){
    $this->tab_langs = array();
    $this->tab_translate = array();
    $this->file_lang = "";
    $this->debug = 0;
    $this->dir = $dir;
    $this->sessioniso = $sessioniso;
    $this->sourceiso = $sourceiso;
    $this->defaultiso = $defaultiso;
    
    //Si on a une langue par defaut
    if(($this->defaultiso != "") && ($this->sessioniso == ""))
      $this->file_lang = $this->dir . "/" . $this->defaultiso;
    else if($this->sessioniso != "")
      $this->file_lang = $this->dir . "/" . $this->sessioniso;

    /* initialize tabs */
    $i = 0;
    if(is_dir($this->dir)) {
      $handle=opendir($this->dir);
      while ($file = trim(readdir($handle))){
	if($file != "." && $file != "..") {
	  $filet = $this->dir . "/" . $file;
	  if($fp = @fopen($filet,"r")){
	    $finded = 0;
	    while (($ligne = fgets($fp,10000)) && ($finded == 0)){
	      if($ligne[0] == "#" && $ligne[1] == "{" && $ligne[2] == "@") {
		$ligneok = "array(" . substr($ligne,2,strlen($ligne)-4) . ");";
		eval("\$tablanginfo = $ligneok;");
		$this->tablangs["htmltagoption"][$i] = $tablanginfo["htmltagoption"];
		$this->tablangs["charset"][$i] = $tablanginfo["charset"];
		$this->tablangs["name"][$i] = $tablanginfo["name"];
		$this->tablangs["iso"][$i] = $file;
		$finded = 1;
		//print "fichier indice $i $file " . $tablanginfo["charset"] . "<br>\n";
		$i++;
		
	      }
	    }
	    fclose($fp);
	  }
	}
      }
      closedir($handle);
    }
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
    //Si le tableau des langues n'est pas défini c'est que c'est le 1er appel
    if((count($this->tab_translate) < 1)  && (trim($this->file_lang) != "")){
      if($fp = @fopen($this->file_lang,"r")){
	while ($ligne = fgetcsv($fp,10000, "=")){
	  //On ne prends pas en compte les commentaires etc.
	  if(trim($ligne[0]) != "")
	    if($ligne[0][0] != "#" && $ligne[0][0] != ";"){
	      if(isset($ligne[1]) && $ligne[1] != "")
		$this->tab_translate[$ligne[0]] = $ligne[1];
	    }
	}
	fclose($fp);
      }
      else
	if($this->debug)
	  print "File <b>- $this->file_lang -</b> is unreadable";
    }
    $retour = $this->tab_translate[$str];
    
    if($retour == "") {
      //Si on est pas déjà en vo, on le marque
      if($this->sessioniso && $this->sourceiso != $this->sessioniso && $mark)
	$retour = "<b>[vo]</b> <i>$str</i>";
      else
	$retour = $str;
    }
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
      $tab = array();

      if($this->sessioniso != "") {
	$tab[$this->sessioniso] = array($this->sessioniso => "");
	$tab[$this->sourceiso] = array($this->sourceiso => "");
      }
      else if($this->defaultiso != "") {
	$tab[$this->defaultiso] = array($this->defaultiso => "");
	$tab[$this->sourceiso] = array($this->sourceiso => "");
      }
      else {
	$tab[$this->sourceiso] = array($this->sourceiso => "");
      }
      
      for($i = 0; $i < count($this->tablangs["iso"]); $i++) {
	$isocode = $this->tablangs["iso"][$i];
	$lang = $this->tablangs["name"][$i];
	$tab[$isocode] = array($isocode => $lang);
      }
      return $tab;
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
      $search = "";
      $ind = 0;

      if($this->sessioniso != "")
	$search = $this->sessioniso;
      else
	$search = $this->defaultiso;

      // indice du tab ?
      for($i = 0; $i < count($this->tablangs["iso"]) && !$ind; $i++)
	if($this->tablangs["iso"][$i] == $search)
	  $ind = $i;

      $htmltag = "<html";
      if($this->tablangs["htmltagoption"][$ind] != "nothing" && $this->tablangs["htmltagoption"][$ind] != "")
	$htmltag .= " " . $this->tablangs["htmltagoption"][$ind];
      $htmltag .= ">";

      if($this->tablangs["charset"][$ind] == "")
	$charset = "iso-8859-1";
      else
	$charset = $this->tablangs["charset"][$ind];

      //      print "fichier indice $ind $search / $charset" ;
      
      header("Content-Type: text/html; charset=$charset");
      $texte .= "$htmltag
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\">\n";
      
      return $texte;
    }
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
function translate($str, $mark = 1){
  global $rtplang;
  return $rtplang->translate($str, $mark);
}

?>
