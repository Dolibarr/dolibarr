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
	require_once ('Sql.interface.php');

	class Sql implements intSql {

		/**
		* Constructeur : initialise la connection à la base de données
		* @param $aHost Domaine ou adresse IP du serveur de base de données (ex : localhost ou db.monsite.fr)
		* @param $aUser Utilisateur de la base de données
		* @param $aPass Mot de passe de l'utilisateur de la base de données
		* @param $aBase Nom de la base de données à utiliser
		*/
		public function __construct ($aHost, $aUser, $aPass, $aBase) {

			$db = mysql_connect ($aHost, $aUser, $aPass);
			mysql_select_db ($aBase, $db);

		}

		/**
		* Destructeur : ferme la connection à la base de données
		*/
		// Désactivation pour cause bug avec 1and1
// 		public function __destruct () {
//
// 			mysql_close ();
//
// 		}

		/**
		* Effectue une requête sur la base de données, et renvoi la ressource correspondante
		* @param $aRequete Requête SQL (ex : SELECT nom, prenom FROM table1 WHERE id = 127)
		* @return Ressource vers la requête venant d'être effectuée
		*/
		public function query ($aRequete) {

			return mysql_query($aRequete);

		}

		/**
		* Renvoi le nombre de résultats d'une requête
		* @param $aRes Ressource d'une requête effectuée précédemment
		* @return Entier : nombre de résultats de la requête
		*/
		public function numRows ($aRes) {

			return mysql_num_rows($aRes);

		}

		/**
		* Enregistre tous les résultats d'une requête dans un tableau à deux dimensions
		* @param $aRes Ressource d'une requête effectuée précédemment
		* @return Tableau à deux dimensions : $tab[indice_resultat(integer)][indice_champ(integer) / nom_champ(string)]
		*/
		public function fetchAll ($aRes) {

			$i = 0;
			while ( $tab = mysql_fetch_array($aRes) ) {

				foreach ( $tab as $cle => $valeur ) {

					$ret[$i][$cle] = $valeur;

				}
				$i++;

			}

			return $ret;

		}

		/**
		* Enregistre seulement le premier résultat d'une requête dans un tableau à une dimension
		* @param $aRes Ressource d'une requête effectuée précédemment
		* @return Tableau à une dimension : $tab[indice_champ(integer) / nom_champ(string)]
		*/
		public function fetchFirst ($aRes) {

			$tab = mysql_fetch_array($aRes);

			foreach ( $tab as $cle => $valeur ) {

				$ret[$cle] = $valeur;

			}

			return $ret;

		}

	}

?>
