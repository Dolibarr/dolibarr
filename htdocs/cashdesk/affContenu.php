<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2009 Laurent Destailleur   <eldy@uers.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2009      Regis Houssin         <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2009      Regis Houssin         <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
require_once 'class/Facturation.class.php';

// Si nouvelle vente, reinitialisation des donnees (destruction de l'objet et vidage de la table contenant la liste des articles)
if ( $_GET['id'] == 'NOUV' )
{
	unset($_SESSION['serObjFacturation']);
	unset($_SESSION['poscart']);
}

// Recuperation, s'il existe, de l'objet contenant les infos de la vente en cours ...
if (isset($_SESSION['serObjFacturation']))
{
    $obj_facturation = unserialize($_SESSION['serObjFacturation']);
    unset($_SESSION['serObjFacturation']);
}
else
{
	// ... sinon, c'est une nouvelle vente
	$obj_facturation = new Facturation();
}

// $obj_facturation contains data for all invoice total + selection of current product

$obj_facturation->calculTotaux();	// Redefine prix_total_ttc, prix_total_ht et montant_tva from $_SESSION['poscart']

$total_ttc = $obj_facturation->prixTotalTtc();

/*var_dump($obj_facturation);
var_dump($_SESSION['poscart']);
var_dump($total_ttc);
exit;*/


// Left area with selected articles (area for article, amount and payments)
print '<div class="inline-block" style="vertical-align: top">';
print '<div class="principal">';

<<<<<<< HEAD
$page=GETPOST('menutpl','alpha');
=======
$page=GETPOST('menutpl', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (empty($page)) $page='facturation';

if (in_array(
		$page,
		array(
			'deconnexion',
			'index','index_verif','facturation','facturation_verif','facturation_dhtml',
			'validation','validation_ok','validation_ticket','validation_verif',
		)
	))
{
	include $page.'.php';
}
else
{
<<<<<<< HEAD
	dol_print_error('','menu param '.$page.' is not inside allowed list');
=======
	dol_print_error('', 'menu param '.$page.' is not inside allowed list');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

print '</div>';
print '</div>';



// Right area with selected articles (shopping cart)
print '<div class="inline-block" style="vertical-align: top">';
print '<div class="liste_articles">';

<<<<<<< HEAD
require ('tpl/liste_articles.tpl.php');
=======
require 'tpl/liste_articles.tpl.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '</div>';
print '</div>';

$_SESSION['serObjFacturation'] = serialize($obj_facturation);
