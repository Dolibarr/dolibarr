<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008      Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Régis Houssin         <regis@dolibarr.fr>
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

		// Envoie une requete et retourne le pointeur vers le resultat
		public function query ($aRequete);

		// Renvoie le nombre de resultats contenus dans la ressource
		public function num_rows ($aRes);

		// Parcours tous les resultats de la ressource et les enregistre dans un tableau a 2 dimensions : $tab[ligne][nom_champ/indice]
		public function fetch_array ($aRes);

		// Enregistre seulement le premier resultat de la ressource dans un tableau a 1 dimension : $tab[nom_champ/indice]
		public function fetchFirst ($aRes);

	}

?>
