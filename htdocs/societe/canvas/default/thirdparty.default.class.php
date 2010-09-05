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
 *	\file       htdocs/societe/canvas/default/thirdparty.default.class.php
 *	\ingroup    thirparty
 *	\brief      Fichier de la classe des tiers par defaut
 *	\version    $Id$
 */

/**
 *	\class      ThirdPartyDefault
 *	\brief      Classe permettant la gestion des tiers par defaut, cette classe surcharge la classe societe
 */
class ThirdPartyDefault extends Societe
{
	var $db;
	
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ThirdPartyDefault($DB)
	{
		$this->db = $DB;
	}

	/**
	 *    Lecture des donnees dans la base
	 *    @param	id          Element id
	 *    @param	action		Type of action
	 */
	function fetch($id='', $action='')
	{
		$result = parent::fetch($id);

		return $result;
	}

	/**
	 * 	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;

		$this->list_datas = array();
	}

}

?>