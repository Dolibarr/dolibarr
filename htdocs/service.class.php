<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/service.class.php
 *       \ingroup    service
 *       \brief      Fichier de la classe des services predefinis
 *       \version    $Id$
 */


/**
 *       \class      Service
 *       \brief      Classe permettant la gestion des services predefinis
 */
class Service
{
	var $db;

	var $id;
	var $libelle;
	var $price;
	var $tms;
	var $debut;
	var $fin;

	var $debut_epoch;
	var $fin_epoch;


	function Service($DB, $id=0) {
		$this->db = $DB;
		$this->id = $id;

		return 1;
	}


	/**
	 *      \brief      Charge indicateurs this->nb de tableau de bord
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
		}
		$sql.= " WHERE p.fk_product_type = 1";
		$sql.= " AND p.entity = ".$conf->entity;
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= " AND IFNULL(c.visible,1)=1";
		}
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["services"]=$obj->nb;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}

	}

}
?>
