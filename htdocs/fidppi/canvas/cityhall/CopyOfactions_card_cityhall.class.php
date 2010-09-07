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
 *	\file       htdocs/fidppi/canvas/cityhall/actions_card_cityhall.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (cityhall canvas)
 *	\version    $Id$
 */
include_once(DOL_DOCUMENT_ROOT.'/societe/canvas/actions_card_common.class.php');

/**
 *	\class      ActionsCardCityhall
 *	\brief      Classe permettant la gestion des mairies
 */
class ActionsCardCityhall extends ActionsCardCommon
{
	var $db;
	
	//! Canvas
	var $canvas;
	
	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsCardCityhall($DB)
	{
		$this->db = $DB;
	}

	/**
	 * 	Return the title of card
	 */
	function getTitle($action)
	{
		global $langs;

		$out='';

		if ($action == 'view') 		$out.= $langs->trans("CityHall");
		if ($action == 'edit') 		$out.= $langs->trans("EditCityHall");
		if ($action == 'create')	$out.= $langs->trans("NewCityHall");
		
		return $out;
	}
	
	/**
	 * 	Load data control
	 */
	function loadControl($socid)
	{
		$return = parent::loadControl($socid);
		
		return $return;
	}
	
	/**
     *    Assigne les valeurs POST dans l'objet
     */
    function assign_post()
    {
    	parent::assign_post();
    }
	
	/**
	 *    Assign custom values for canvas
	 *    @param      action     Type of action
	 */
	function assign_values($action='')
	{
		global $langs;
		global $form, $formcompany;	
		
		parent::assign_values($action);
		
		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id,$langs->trans("DeleteACityHall"),$langs->trans("ConfirmDeleteCityHall"),"confirm_delete",'',0,2);
			}
		}
		
		if ($action == 'create' || $action == 'edit')
		{
			// Workforce
			$this->tpl['select_workforce'] = $form->selectarray("effectif_id",$formcompany->effectif_array(0), $this->object->effectif_id);
		}
	}
	
}

?>