<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004 Sebastien DiCintio   <sdicintio@ressource-toi.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**	    \file       htdocs/install/inc.php
		\brief      Fichier include du programme d'installation
		\version    $Revision$
*/

define('DOL_DOCUMENT_ROOT','../');

require_once('../translate.class.php');
require_once('../lib/functions.inc.php');



// Forcage du parametrage PHP magic_quots_gpc (Sinon il faudrait a chaque POST, conditionner
// la lecture de variable par stripslashes selon etat de get_magic_quotes).
// En mode off (recommande il faut juste faire addslashes au moment d'un insert/update.
function stripslashes_deep($value)
{
   return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}
if (get_magic_quotes_gpc())
{
   $_GET    = array_map('stripslashes_deep', $_GET);
   $_POST  = array_map('stripslashes_deep', $_POST);
   $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
   $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}
@set_magic_quotes_runtime(0);


$docurl = '<a href="doc/dolibarr-install.html">documentation</a>';
$conffile = "../conf/conf.php";

// Defini objet langs
$langs = new Translate('../langs');
$langs->setDefaultLang('auto');
$langs->setPhpLang();

$bc[false]=' class="bg1"';
$bc[true]=' class="bg2"';

function pHeader($soutitre,$next,$action='set')
{
    global $langs;
    $langs->load("main");
    $langs->load("admin");

	// On force contenu en ISO-8859-1
	header("Content-type: text/html; charset=iso-8859-1");
    //header("Content-type: text/html; charset=UTF-8");

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
    print '<html>';
    print '<head>';
    print '<meta http-equiv="content-type" content="text/html; charset='.$langs->trans("charset").'">';
    print '<link rel="stylesheet" type="text/css" href="./default.css">';
    print '<title>'.$langs->trans("DolibarrSetup").'</title>';
    print '</head>';
    print '<body>';
    print '<span class="titre"><a class="titre" href="index.php">'.$langs->trans("DolibarrSetup").'</a></span>';
    print '<form action="'.$next.'.php" method="POST">';
    print '<input type="hidden" name="action" value="'.$action.'">';
    print '<div class="main">';
    if ($soutitre) {
        print '<div class="soustitre">'.$soutitre.'</div>';
    }
    print '<div class="main-inside">';
}

function pFooter($nonext=0,$setuplang='')
{
    global $langs;
    $langs->load("main");
    $langs->load("admin");
    
    print '</div></div>';
    if (! $nonext)
    {
        print '<div class="barrebottom"><input type="submit" value="'.$langs->trans("NextStep").' ->"></div>';
    }
    if ($setuplang)
    {
        print '<input type="hidden" name="selectlang" value="'.$setuplang.'">';
    }
    print '</form>';
    print '</body>';
    print '</html>';
}


function dolibarr_install_syslog($message)
{
    // Ajout user a la log
    $login='install';
    $message=sprintf("%-8s",$login)." ".$message;

	$fileinstall="/tmp/dolibarr_install.log";
    $file=@fopen($fileinstall,"a+");
    if ($file) {
        fwrite($file,strftime("%Y-%m-%d %H:%M:%S",time())." ".$level." ".$message."\n");
        fclose($file);
    }
}


/**
		\brief      Compare 2 versions
		\param	    versionarray1       Tableau de version (vermajeur,vermineur,autre)
		\param	    versionarray2       Tableau de version (vermajeur,vermineur,autre)
        \return     int                 <0 si versionarray1<versionarray2, 0 si =, >0 si versionarray1>versionarray2
*/
function aaversioncompare($versionarray1,$versionarray2)
{
    $ret=0;
    $i=0;
    while ($i < max(sizeof($versionarray1),sizeof($versionarray1)))
    {
        $operande1=isset($versionarray1[$i])?$versionarray1[$i]:0;
        $operande2=isset($versionarray2[$i])?$versionarray2[$i]:0;
        if ($operande1 < $operande2) { $ret = -1; break; }
        if ($operande1 > $operande2) { $ret =  1; break; }
        $i++;
    }    
    return $ret;
}

?>