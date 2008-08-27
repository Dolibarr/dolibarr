<?php
/* Copyright (C) 2006 Laurent Destailleur   <eldy@users.sourceforge.net>
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

/**
	\file       htdocs/accountancy/accountancyaccount.class.php
  	\ingroup    comptaexpert
  	\brief      Fichier de la classe des comptes comptables
  	\version    $Id$
*/


/**	\class 		AccountancyAccount
    \brief 		Classe permettant la gestion des comptes
*/

class AccountancyAccount
{
	var $db;
	var $error;

	var $rowid;
	var $fk_pcg_version;
	var $pcg_type;
	var $pcg_subtype;
	var $label;
	var $account_number;
	var $account_parent;


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler accès base de données
	 *    \param  id          id compte (0 par defaut)
	 */
	function AccountancyAccount($DB, $id=0)
	{
		$this->db = $DB;
		$this->id   = $id ;
	}


	/**
	 *    \brief  	Insère le compte en base
	 *    \param  	user 	Utilisateur qui effectue l'insertion
	 *    \return	int		<0 si ko, Id ligne ajoutée si ok
	 */
	function create($user)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."accountingaccount";
		$sql.= " (date_creation, fk_user_author, numero,intitule)";
		$sql.= " VALUES (".$this->db->idate(mktime()).",".$user->id.",'".$this->numero."','".$this->intitule."')";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."accountingaccount");

			if ($id > 0)
			{
				$this->id = $id;
				$result = $this->id;
			}
			else
			{
				$result = -2;
				$this->error="AccountancyAccount::Create Erreur $result";
				dolibarr_syslog($this->error);
			}
		}
		else
		{
			$result = -1;
			$this->error="AccountancyAccount::Create Erreur $result";
			dolibarr_syslog($this->error);
		}

		return $result;
	}
	
}
?>
