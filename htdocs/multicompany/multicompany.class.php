<?php
/* Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>
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
 *	\file       htdocs/multicompany/multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 *	\version    $Id$
 */


/**
 *	\class      Multicompany
 *	\brief      Class of the module multicompany
 */
class Multicompany
{
	var $db;
	var $error;
	//! Numero de l'erreur
	var $errno = 0;
	
	var $entities = array();

	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function Multicompany($DB)
	{
		$this->db = $DB;
		
		$this->canvas = "default";
		$this->name = "admin";
		$this->description = "";
		
		/*
		$this->active = MAIN_MULTICOMPANY;
		
		$this->menu_new = 'Admin';
		$this->menu_add = 1;
		$this->menu_clear = 1;

		$this->no_button_copy = 1;

		$this->menus[0][0] = '';
		$this->menus[0][1] = '';
		$this->menus[1][0] = '';
		$this->menus[1][1] = '';

		$this->next_prev_filter = "canvas='default'";

		$this->onglets[0][0] = 'URL';
		$this->onglets[0][1] = 'name1';
		$this->onglets[1][0] = 'URL';
		$this->onglets[1][1] = 'name2';
		*/
	}

	/**
	 *    \brief      Creation
	 */
	function Create($user,$datas)
	{
		
	}
	
	/**
	 *    \brief      Supression
	 */
	function Delete($id)
	{

	}
	
	/**
	 *    \brief      List of entities
	 */
	function getEntities()
	{
		global $conf;
		
		$sql = "SELECT ".$this->db->decrypt('value',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." as value";
		$sql.= ", entity";
		$sql.= ", visible";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE ".$this->db->decrypt('name',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." = 'MAIN_INFO_SOCIETE_NOM'";
		$sql.= " ORDER BY entity ASC";
		
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				
				$active = 1;
				if ($obj->entity < 0) $active = 0;
				
				$this->entities[$i]['label']  = $obj->value;
				$this->entities[$i]['id']     = $obj->entity;
				$this->entities[$i]['active'] = $active;
				
				$i++;
			}
		}
		
	}
	
	/**
	 *    \brief      Return combo list of entities.
	 *    \param      entities    Entities array
	 *    \param      selected    Preselected entity
	 */
	function select_entities($entities,$selected='',$option='')
	{
		print '<select class="flat" name="entity" '.$option.'>';
				
		if (is_array($entities))
		{	
			foreach ($entities as $entity)
			{
				print '<option value="'.$entity['id'].'" ';
				if ($selected == $entity['id'])	print 'selected="true"';
				if ($entity['active'] == 0) print 'disabled="disabled"';
				print '>';
				print $entity['label'];
				print '</option>';
			}
		}
		print '</select>';
	}

	/**
	 *    \brief      Assigne les valeurs pour les templates Smarty
	 *    \param      smarty     Instance de smarty
	 */
	function assign_smarty_values(&$smarty, $action='')
	{
		global $conf,$langs;
		
		$smarty->assign('langs', $langs);
		
		$picto='title.png';
		if (empty($conf->browser->firefox)) $picto='title.gif';
		$smarty->assign('title_picto', img_picto('',$picto));
		
		$smarty->assign('entities',$this->entities);
		$smarty->assign('img_on',img_picto($langs->trans("Activated"),'on'));
		$smarty->assign('img_off',img_picto($langs->trans("Disabled"),'on'));

	}



}
?>