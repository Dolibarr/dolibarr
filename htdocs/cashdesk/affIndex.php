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

/**
 *	\file       htdocs/cashdesk/affIndex.php
 *	\ingroup    cashdesk
 *	\brief      First page of point of sale module
 *	\version    $Id$
 */
include_once('../master.inc.php');
require_once('include/environnement.php');
if ( $_SESSION['uid'] <= 0 ) {
	header ('Location: index.php');
	exit;
}


print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

print '<html>'."\n";
print '<head>'."\n";
print '<title>'.$langs->trans("CashDesk").'</title>'."\n";

print '<meta name="robots" content="none" />'."\n";

print '<meta name="author" content="Jeremie Ollivier - jeremie.o@laposte.net" />'."\n";
print '<meta name="Generator" content="Kwrite, Gimp, Inkscape" />'."\n";

print '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";

print '<meta http-equiv="Content-Style-Type" content="text/css" />'."\n";
print '<link href="'.DOL_URL_ROOT.'/cashdesk/css/style.css" rel="stylesheet" type="text/css" media="screen" />'."\n";

print '<!-- Import des fichiers necessaires a JsCalendar -->'."\n";
print '<style type="text/css">'."\n";
print '@import url(include/jscalendar/calendar-blue.css);'."\n";
print '</style>'."\n";
print '<script type="text/javascript" src="include/jscalendar/calendar.js"></script>'."\n";
print '<script type="text/javascript" src="include/jscalendar/lang/calendar-fr.js"></script>'."\n";
print '<script type="text/javascript" src="include/jscalendar/calendar-setup.js"></script>'."\n";
print '</head>'."\n";

print '<body>'."\n";

if (!empty($error))
{
	print $error;
	print '</body></html>';
	exit;
}

print '<div class="conteneur">'."\n";
print '<div class="conteneur_img_gauche">'."\n";
print '<div class="conteneur_img_droite">'."\n";

print '<h1 class="entete"><span>POINT OF SALE</span></h1>'."\n";

print '<div class="menu_principal">'."\n";
include_once('tpl/menu.tpl.php');
print '</div>'."\n";

print '<div class="contenu">'."\n";
include_once('affContenu.php');
print '</div>'."\n";

include_once('affPied.php');

print '</div></div></div>'."\n";
print '</body></html>'."\n";
?>