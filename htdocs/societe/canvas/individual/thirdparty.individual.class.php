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
 *	\file       htdocs/societe/canvas/default/thirdparty.individual.class.php
 *	\ingroup    thirparty
 *	\brief      Fichier de la classe des particuliers
 *	\version    $Id$
 */

/**
 *	\class      ThirdPartyIndividual
 *	\brief      Classe permettant la gestion des particuliers, cette classe surcharge la classe societe
 */
class ThirdPartyIndividual extends Societe
{
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;
	//! Template container
	var $tpl = array();

	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function ThirdPartyIndividual($DB)
	{
		$this->db 				= $DB;

		$this->smarty			= 0;
		$this->module 			= "societe";
		$this->canvas 			= "individual";
		$this->name 			= "individual";
		$this->definition 		= "Canvas des particuliers";
		$this->fieldListName    = "thirdparty_individual";
	}

	function getTitle()
	{
		global $langs;

		return $langs->trans("Individual");
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
	 *    Assign custom values for canvas
	 *    @param      action     Type of action
	 */
	function assign_values($action='')
	{
		global $conf, $langs, $user, $mysoc;
		global $form, $formadmin, $formcompany;
			
		parent::assign_values($action);
		
		$form = new Form($db);
		
		if ($action == 'create')
		{
			$this->tpl['select_civility'] = $formcompany->select_civility($contact->civilite_id);
		}
		
		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->id,$langs->trans("DeleteAnIndividual"),$langs->trans("ConfirmDeleteIndividual"),"confirm_delete",'',0,2);
			}
		}
	}

	/**
	 * 	\brief	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;

		$this->list_datas = array();
	}

}

?>