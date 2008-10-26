<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
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

	require ('classes/Facturation.class.php');

	// Si nouvelle vente, réinitialisation des données (destruction de l'objet et vidage de la table contenant la liste des articles)
	if ( $_GET['id'] == 'NOUV' ) {

		unset ($_SESSION['serObjFacturation']);

		$sql->query ('DELETE FROM '.MAIN_DB_PREFIX.'tmp_caisse');

	}

	// Récupération, s'il existe, de l'objet contenant les infos de la vente en cours ...
	if ( isset ($_SESSION['serObjFacturation']) ) {

		$obj_facturation = unserialize ($_SESSION['serObjFacturation']);
		unset ($_SESSION['serObjFacturation']);

	// ... sinon, c'est une nouvelle vente
	} else {

		$obj_facturation = new Facturation;

	}

?>

<div class="liste_articles">
	<?php include ('liste_articles.php'); ?>
</div>

<div class="principal">

	<?php
		if ( $_GET['menu'] ) {

			include ($_GET['menu'].'.php');

		} else {

			include ('facturation.php');

		}
	?>
</div>

<?php

	$_SESSION['serObjFacturation'] = serialize ($obj_facturation);

?>

