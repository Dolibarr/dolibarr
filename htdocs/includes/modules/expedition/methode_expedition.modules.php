<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/expedition/methode_expedition.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier contenant la classe mere de generation de bon de livraison en PDF
 *				et la classe mere de numerotation des bons de livraisons
 * 	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 *	\class      methode_expedition
 *	\brief      Classe mere des methodes expeditions
 */
class methode_expedition
{

	function methode_expedition($db=0)
	{
		$this->db = $db;
		$this->name = "NOT DEFINED";
		$this->description = "ERROR IN MODULE DESCRIPTION";
	}


	/**
	 *      \brief      Renvoi la liste des mod�les actifs
	 *      \param      db      Handler de base
	 */
	function liste_modeles($db)
	{
		$type='invoice';
		$liste=array();
		$sql ="SELECT nom as id, nom as lib";
		$sql.=" FROM ".MAIN_DB_PREFIX."document_model";
		$sql.=" WHERE type = '".$type."'";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$liste[$row[0]]=$row[1];
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($db);
			return -1;
		}
		return $liste;
	}

}

?>
