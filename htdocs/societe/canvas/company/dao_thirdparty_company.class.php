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
 *	\file       htdocs/societe/canvas/company/dao_thirdparty_company.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe des tiers par defaut
 */

/**
 *	\class      DaoThirdPartyCompany
 *	\brief      Classe permettant la gestion des tiers par defaut, cette classe surcharge la classe societe
 */
class DaoThirdPartyCompany extends Societe
{

	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$DB		Databae handler
	 */
	function DaoThirdPartyCompany($DB)
	{
		$this->db = $DB;
	}

}

?>