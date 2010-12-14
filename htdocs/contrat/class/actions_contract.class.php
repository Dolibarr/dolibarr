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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/contrat/class/actions_contract.class.php
 *	\ingroup    contract
 *	\brief      Fichier de la classe des actions des contrats
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/actions_commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');

/**
 *	\class      ActionsContract
 *	\brief      Classe permettant la gestion des actions des contrats
 */
class ActionsContract extends ActionsCommonObject
{
	var $db;
	var $object;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsContract($DB)
	{
		$this->db = $DB;
	}
	
	/**
	 *    Load a contract from database
	 *    @param      id      Id of contract to load
	 *    @return     int     <0 if KO, id of contract if OK
	 */
	function fetch($rowid,$ref='')
	{
		$this->object = new Contrat($this->db);
		return $this->object->fetch($rowid,$ref);
	}

}

?>