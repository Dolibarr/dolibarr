<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\file       	htdocs/ecm/class/htmlecm.form.class.php
 * 	\brief      	Fichier de la classe des fonctions predefinie de composants html
 */
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';


/**
 * \class      	FormEcm
 * \brief      	Classe permettant la generation de composants html
 * \remarks		Only common components must be here.
 */
class FormEcm
{
	var $db;
	var $error;


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Retourne la liste des categories du type choisi
	 *
	 *  @param	int		$selected    		Id categorie preselectionnee
	 *  @param  string	$select_name		Nom formulaire HTML
	 *  @return	string						String with HTML select
	 */
	function select_all_sections($selected=0,$select_name='')
	{
		global $langs;
		$langs->load("ecm");

		if ($select_name=="") $select_name="catParent";

		$cat = new EcmDirectory($this->db);
		$cate_arbo = $cat->get_full_arbo();

		$output = '<select class="flat" name="'.$select_name.'">';
		if (is_array($cate_arbo))
		{
			if (! count($cate_arbo)) $output.= '<option value="-1" disabled>'.$langs->trans("NoCategoriesDefined").'</option>';
			else
			{
				$output.= '<option value="-1">&nbsp;</option>';
				foreach($cate_arbo as $key => $value)
				{
					if ($cate_arbo[$key]['id'] == $selected)
					{
						$add = 'selected ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.$cate_arbo[$key]['fulllabel'].'</option>';
				}
			}
		}
		$output.= '</select>';
		$output.= "\n";
		return $output;
	}
}

