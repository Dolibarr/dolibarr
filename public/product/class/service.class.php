<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *       \file       htdocs/product/class/service.class.php
 *       \ingroup    service
 *       \brief      Fichier de la classe des services predefinis
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage predefined services
 */
class Service extends CommonObject
{
	var $libelle;
	var $price;
	var $tms;
	var $debut;
	var $fin;

	var $debut_epoch;
	var $fin_epoch;

	/**
	*  Constructor
	*
	*  @param      DoliDB		$db      Database handler
	*/
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Charge indicateurs this->nb de tableau de bord
	 *
	 *	@return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
		$sql.= " AND p.fk_product_type = 1";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["services"]=$obj->nb;
			}
            $this->db->free($resql);
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
