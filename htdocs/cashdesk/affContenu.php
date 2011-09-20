<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2009 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/affContenu.php
 *	\ingroup    cashdesk
 *	\brief      Include to show main page for cashdesk module
 */
require_once('class/Facturation.class.php');

// Si nouvelle vente, reinitialisation des donnees (destruction de l'objet et vidage de la table contenant la liste des articles)
if ( $_GET['id'] == 'NOUV' )
{
	unset($_SESSION['serObjFacturation']);
	$db->query('DELETE FROM '.MAIN_DB_PREFIX.'pos_tmp');
}

// Recuperation, s'il existe, de l'objet contenant les infos de la vente en cours ...
if ( isset ($_SESSION['serObjFacturation']) )
{
	$obj_facturation = unserialize($_SESSION['serObjFacturation']);
	unset ($_SESSION['serObjFacturation']);
}
else
{
	// ... sinon, c'est une nouvelle vente
	$obj_facturation = new Facturation();
}

print '<div class="liste_articles">';
include('liste_articles.php');
print '</div>';

print '<div class="principal">';

if ( $_GET['menu'] )
{
	include($_GET['menu'].'.php');
}
else
{
	include ('facturation.php');
}

print '</div>';

$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

?>