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
 *	\file       htdocs/societe/canvas/individual/card.individual.class.php
 *	\ingroup    thirparty
 *	\brief      Fichier de la classe Thirdparty card controller (individual canvas)
 *	\version    $Id$
 */
include_once(DOL_DOCUMENT_ROOT.'/societe/canvas/actions_card_common.class.php');

/**
 *	\class      ActionsCardIndividual
 *	\brief      Classe permettant la gestion des particuliers
 */
class ActionsCardIndividual extends CardCommon
{
	var $db;

	//! Canvas
	var $canvas;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsCardIndividual($DB)
	{
		$this->db 				= $DB;
	}

	/**
	 * 	Return the title of card
	 */
	function getTitle($action)
	{
		global $langs;

		$out='';

		if ($action == 'view') 		$out.= $langs->trans("Individual");
		if ($action == 'edit') 		$out.= $langs->trans("EditIndividual");
		if ($action == 'create')	$out.= $langs->trans("NewIndividual");

		return $out;
	}

	/**
     *    Assigne les valeurs POST dans l'objet
     */
    function assign_post()
    {
    	parent::assign_post();
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
	 *    Assign custom values for canvas
	 *    @param      action     Type of action
	 */
	function assign_values($action='')
	{
		global $langs;
		global $form, $formcompany;

		parent::assign_values($action);

		if ($action == 'create' || $action == 'edit')
		{
			$this->tpl['select_civility'] = $formcompany->select_civility($contact->civilite_id);
		}

		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id,$langs->trans("DeleteAnIndividual"),$langs->trans("ConfirmDeleteIndividual"),"confirm_delete",'',0,2);
			}
		}
	}

}

?>