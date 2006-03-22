<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/includes/modules/societe/mod_codecompta_aquarium.class.php
        \ingroup    societe
        \brief      Fichier de la classe des gestion aquarium des codes compta des societes clientes
        \version    $Revision$
*/


/**
        \class 		mod_codecompta_aquarium
        \brief 		Classe permettant la gestion aquarium des codes compta des societes clients
*/

class mod_codecompta_aquarium
{
	var $nom;

	function mod_codecompta_aquarium()
	{
		$this->nom = "Aquarium";
	}
	
	function info()
	{
		return "Renvoie un code compta composé de 401 suivi du code tiers si c'est un fournisseur, et 411 suivit du code tiers si c'est un client";
	}


	/**
 	 *    	\brief      Renvoi code compta d'une societe
 	 *    	\param      DB              Handler d'accès base
	 *    	\param      societe         Objet societe
	 *    	\param      type			Type de tiers ('customer' ou 'supplier')
	 *		\return		int				>=0 ok, <0 ko
	 */
	function get_code($DB, $societe, $type)
	{
		$i = 0;
		$this->db = $DB;
	
		// Regle gestion compte compta
		if ($type == 'customer') $codetouse=$societe->code_client;
		if ($type == 'supplier') $codetouse=$societe->code_fournisseur;
		
		if ($type == 'customer') $this->code = "411".$codetouse;
		if ($type == 'supplier') $this->code = "401".$codetouse;
	
		$is_dispo = $this->verif($DB, $this->code, $societe);
		while ($is_dispo == 0 && $i < 37)		// 40 char max
		{
			$arr = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	
			$this->code = $societe->prefix_comm . $codetouse . substr($arr, $i, 1);
	
			$is_dispo = $this->verif($DB, $this->code, $societe);
	
			$i++;
		}

		dolibarr_syslog("mod_codecompta_aquarium::get_code type=".$type." code=".$this->code);
		return $is_dispo;
	}


	/**
	 *		\brief		Renvoi si un code compta est dispo
	 *		\return		int			0 non dispo, >0 dispo, <0 erreur
	 */
	function verif($db, $code, $societe)
	{
		$sql = "SELECT code_compta FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE code_compta = '".$code."'";
		$sql.= " AND idp != ".$societe->id;
	
		if ($db->query($sql))
		{
			if ($db->num_rows() == 0)
			{
				return 1;	// Dispo
			}
			else
			{
				return 0;	// Non dispo
			}
		}
		else
		{
			return -1;		// Erreur
		}
	}
  
}

?>
