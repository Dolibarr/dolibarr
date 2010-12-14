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
 *	\file       htdocs/commande/class/actions_proposal.class.php
 *	\ingroup    order
 *	\brief      Fichier de la classe des actions des commandes clients
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/actions_commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');

/**
 *	\class      ActionsCustomerorder
 *	\brief      Classe permettant la gestion des actions des commandes clients
 */
class ActionsCustomerorder extends ActionsCommonObject
{
	var $db;
	var $object;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsCustomerorder($DB)
	{
		$this->db = $DB;
	}
	
	/**
	 *	Get object and lines from database
	 *	@param      id       	Id of object to load
	 * 	@param		ref			Ref of object
	 *	@return     int         >0 if OK, <0 if KO
	 */
	function fetch($rowid,$ref='')
	{
		$this->object = new Commande($this->db);
		return $this->object->fetch($rowid,$ref);
	}

}

?>