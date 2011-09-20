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
 *	\file       htdocs/societe/canvas/individual/dao_thirdparty_individual.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe des particuliers
 */

/**
 *	\class      DaoThirdPartyIndividual
 *	\brief      Classe permettant la gestion des particuliers, cette classe surcharge la classe societe
 */
class DaoThirdPartyIndividual extends Societe
{
    public $list_datas = array();

	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$DB		Databae handler
	 */
	function DaoThirdPartyIndividual($DB)
	{
		$this->db = $DB;
	}

}

?>