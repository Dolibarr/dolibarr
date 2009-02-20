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

require_once ('Sql.interface.php');


class Sql implements intSql {

	/**
		* Constructeur : initialise la connection � la base de donn�es
		* @param $aHost Domaine ou adresse IP du serveur de base de donn�es (ex : localhost ou db.monsite.fr)
		* @param $aUser Utilisateur de la base de donn�es
		* @param $aPass Mot de passe de l'utilisateur de la base de donn�es
		* @param $aBase Nom de la base de donn�es � utiliser
		*/
	public function __construct ($aHost, $aUser, $aPass, $aBase) {
	
		$db = mysql_connect ($aHost, $aUser, $aPass);
		mysql_select_db ($aBase, $db);

	}

	/**
		* Destructeur : ferme la connection � la base de donn�es
		*/
	// D�sactivation pour cause bug avec 1and1
	// 		public function __destruct () {
	//
	// 			mysql_close ();
	//
	// 		}

	/**
		* Effectue une requ�te sur la base de donn�es, et renvoi la ressource correspondante
		* @param $aRequete Requ�te SQL (ex : SELECT nom, prenom FROM table1 WHERE id = 127)
		* @return Ressource vers la requ�te venant d'�tre effectu�e
		*/
	public function query ($aRequete) {
		dol_syslog("cashdesk query sql=".$aRequete, LOG_DEBUG);
		return mysql_query($aRequete);

	}

	/**
		* Renvoi le nombre de r�sultats d'une requ�te
		* @param $aRes Ressource d'une requ�te effectu�e pr�c�demment
		* @return Entier : nombre de r�sultats de la requ�te
		*/
	public function num_rows ($aRes) {

		return mysql_num_rows($aRes);

	}

	/**
		* Enregistre tous les r�sultats d'une requ�te dans un tableau � deux dimensions
		* @param $aRes Ressource d'une requ�te effectu�e pr�c�demment
		* @return Tableau � deux dimensions : $tab[indice_resultat(integer)][indice_champ(integer) / nom_champ(string)]
		*/
	public function fetch_array ($aRes) {

		$ret=array(); $i=0;
		while ( $tab = mysql_fetch_array($aRes) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}

		return $ret;

	}

	/**
		* Enregistre seulement le premier r�sultat d'une requ�te dans un tableau � une dimension
		* @param $aRes Ressource d'une requ�te effectu�e pr�c�demment
		* @return Tableau � une dimension : $tab[indice_champ(integer) / nom_champ(string)]
		*/
	public function fetchFirst ($aRes)
	{

		$ret=array();
		$tab = mysql_fetch_array($aRes);
		foreach ( $tab as $cle => $valeur )
		{
			$ret[$cle] = $valeur;
		}

		return $ret;
	}

}

?>
