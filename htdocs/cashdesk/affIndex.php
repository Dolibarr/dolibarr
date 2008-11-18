<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>Caisse</title>

<meta name="robots" content="none" />

<meta name="author" content="Jeremie Ollivier - jeremie.o@laposte.net" />
<meta name="Generator" content="Kwrite, Gimp, Inkscape" />

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
<meta http-equiv="Content-Language" content="fr" />

<meta http-equiv="Content-Style-Type" content="text/css" />
<link href="style.css" rel="stylesheet" type="text/css" media="screen" />

<!-- Import des fichiers necessaires a JsCalendar -->
<style type="text/css">
@import url(include/jscalendar/calendar-blue.css);
</style>
<script type="text/javascript" src="include/jscalendar/calendar.js"></script>
<script type="text/javascript"
	src="include/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript"
	src="include/jscalendar/calendar-setup.js"></script>
</head>

<body>

<div class="conteneur">
<div class="conteneur_img_gauche">
<div class="conteneur_img_droite">

<h1 class="entete"><span>CAISSE</span></h1>

<div class="menu_principal"><?php
include ('templates/menu.tpl.php');
?></div>

<div class="contenu"><?php
include ('affContenu.php');
?></div>

<?php include ('affPied.php'); ?></div>
</div>
</div>

</body>

</html>
