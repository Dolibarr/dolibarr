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
 *	\file       htdocs/multicompany/canvas/multicompany.class.php
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
	function DeleteCanvas($id)
	{

	}
	/**
	 *    \brief      Lecture des donnees dans la base
	 */
	function FetchCanvas($id='', $action='')
	{

	}
	
	
	/**
	 *    \brief      Mise a jour des donnees dans la base
	 *    \param      datas        Tableau de donnees
	 */
	function UpdateCanvas($datas)
	{
		 
	}


	/**
	 *    \brief      Assigne les valeurs pour les templates Smarty
	 *    \param      smarty     Instance de smarty
	 */
	function assign_smarty_values(&$smarty, $action='')
	{
		global $conf,$langs;
		
		$picto='title.png';
		if (empty($conf->browser->firefox)) $picto='title.gif';
		$smarty->assign('title_picto', img_picto('',$picto));
		$smarty->assign('title_text', $langs->trans('Setup'));

	}



}
?>