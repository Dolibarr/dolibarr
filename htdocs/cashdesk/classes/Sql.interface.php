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

	interface intSql {

		// Envoie une requête et retourne le pointeur vers le résultat
		public function query ($aRequete);

		// Renvoie le nombre de résultats contenus dans la ressource
		public function numRows ($aRes);

		// Parcours tous les résultats de la ressource et les enregistre dans un tableau à 2 dimensions : $tab[ligne][nom_champ/indice]
		public function fetchAll ($aRes);

		// Enregistre seulement le premier résultat de la ressource dans un tableau à 1 dimension : $tab[nom_champ/indice]
		public function fetchFirst ($aRes);

	}

?>
