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
$docurl = '<a href="doc/dolibarr-install.html">documentation</a>';
$conffile = "../conf/conf.php";
// Defini objet langs
require_once('../translate.class.php');
$langs = new Translate('../langs');
$langs->setDefaultLang('auto');
$langs->setPhpLang();
$tab[0]=' class="bg1"';
$tab[1]=' class="bg2"';
function pHeader($soutitre,$next,$action='set')
{
    global $langs;
    $langs->load("main");
    $langs->load("admin");
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
function dolibarr_syslog($message)
{
    // Les fonctions syslog ne sont pas toujours installés ou autorisées chez les hébergeurs
    if (function_exists("define_syslog_variables"))
    {
        // \todo    Désactiver sous Windows (gros problème mémoire et faute de protections)
        //  if (1 == 2) {
              define_syslog_variables();
              openlog("dolibarr", LOG_PID | LOG_PERROR, LOG_USER);	# LOG_USER au lieu de LOG_LOCAL0 car non accepté par tous les php
              syslog(LOG_WARNING, $message);
              closelog();
        //  }
    }
}
?>