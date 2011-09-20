<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/contact/canvas/default/dao_contact_default.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe des contacts par defaut
 */

/**
 *	\class      DaoContactDefault
 *	\brief      Classe permettant la gestion des contacts par defaut, cette classe surcharge la classe contact
 */
class DaoContactDefault extends Contact
{
	/**
	 *    Constructor
	 *
	 *    @param	DoliDB	$DB		Handler acces base de donnees
	 */
	function DaoContactDefault($DB)
	{
		$this->db = $DB;
	}

}

?>