<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008      Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin         <regis@dolibarr.fr>
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
 */

include('../master.inc.php');
require ('include/environnement.php');
if ( $_SESSION['uid'] <= 0 ) {
	header ('Location: index.php');
	exit;
}

print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

print '<html>';
print '<head>';
print '<title>Caisse</title>';

print '<meta name="robots" content="none" />';

print '<meta name="author" content="Jeremie Ollivier - jeremie.o@laposte.net" />';
print '<meta name="Generator" content="Kwrite, Gimp, Inkscape" />';

print '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />';
print '<meta http-equiv="Content-Language" content="fr" />';

print '<meta http-equiv="Content-Style-Type" content="text/css" />';
print '<link href="style.css" rel="stylesheet" type="text/css" media="screen" />';

print '<!-- Import des fichiers necessaires a JsCalendar -->';
print '<style type="text/css">';
print '@import url(include/jscalendar/calendar-blue.css);';
print '</style>';
print '<script type="text/javascript" src="include/jscalendar/calendar.js"></script>';
print '<script type="text/javascript"	src="include/jscalendar/lang/calendar-fr.js"></script>';
print '<script type="text/javascript"	src="include/jscalendar/calendar-setup.js"></script>';
print '</head>';

print '<body>';

print '<div class="conteneur">';
print '<div class="conteneur_img_gauche">';
print '<div class="conteneur_img_droite">';

print '<h1 class="entete"><span>CAISSE</span></h1>';

print '<div class="menu_principal">';
include('templates/menu.tpl.php');
print '</div>';

print '<div class="contenu">';
include('affContenu.php');
print '</div>';

include('affPied.php');

print '</div></div></div>';
print '</body></html>';
?>