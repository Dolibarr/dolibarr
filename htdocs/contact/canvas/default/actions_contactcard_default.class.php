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
 *	\file       htdocs/contact/canvas/default/actions_contactcard_default.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty contact card controller (default canvas)
 *	\version    $Id$
 */
include_once(DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php');

/**
 *	\class      ActionsContactCardDefault
 *	\brief      Classe permettant la gestion des contacts par defaut
 */
class ActionsContactCardDefault extends ActionsContactCardCommon
{
	var $db;

	//! Canvas
	var $canvas;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsContactCardDefault($DB)
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

		if ($action == 'view') 		$out.= $langs->trans("ThirdParty");
		if ($action == 'edit') 		$out.= $langs->trans("EditCompany");
		if ($action == 'create')	$out.= $langs->trans("NewCompany");

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
	 * 	Execute actions
	 * 	@param 		Id of object (may be empty for creation)
	 */
	function doActions($socid)
	{
		$return = parent::doActions($socid);

		return $return;
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

		if ($action == 'create' || $action == 'edit')
		{

		}

		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,2);
			}
		}
	}
	
	/**
	 * 	Check permissions of a user to show a page and an object. Check read permission
	 * 	If $_REQUEST['action'] defined, we also check write permission.
	 * 	@param      user      	  	User to check
	 * 	@param      features	    Features to check (in most cases, it's module name)
	 * 	@param      objectid      	Object ID if we want to check permission on a particular record (optionnal)
	 *  @param      dbtablename    	Table name where object is stored. Not used if objectid is null (optionnal)
	 *  @param      feature2		Feature to check (second level of permission)
	 *  @param      dbt_keyfield    Field name for socid foreign key if not fk_soc. (optionnal)
	 *  @param      dbt_select      Field name for select if not rowid. (optionnal)
	 *  @return		int				1
	 */
	function restrictedArea($user, $features='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
	{
		return restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
	}

}

?>