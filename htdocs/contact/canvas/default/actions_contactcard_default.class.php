<?php
/* Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/contact/canvas/default/actions_contactcard_default.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty contact card controller (default canvas)
 *	\version    $Id: actions_contactcard_default.class.php,v 1.10 2011/07/31 23:54:11 eldy Exp $
 */
include_once(DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php');

/**
 *	\class      ActionsContactCardDefault
 *	\brief      Classe permettant la gestion des contacts par defaut
 */
class ActionsContactCardDefault extends ActionsContactCardCommon
{
	var $db;
    var $targetmodule;
    var $canvas;
    var $card;

	/**
     *    Constructor
     *    @param   DB              Handler acces base de donnees
     *    @param   targmetmodule   Name of directory of module where canvas is stored
     *    @param   canvas          Name of canvas
     *    @param   card            Name of tab (sub-canvas)
	 */
	function ActionsContactCardDefault($DB,$targetmodule,$canvas,$card)
	{
        $this->db               = $DB;
        $this->targetmodule     = $targetmodule;
        $this->canvas           = $canvas;
        $this->card             = $card;
	}

    /**
     *  Execute actions
     *  @param      Id of object (may be empty for creation)
     */
    function doActions($id)
    {
        $return = parent::doActions($id);
        return $return;
    }


	/**
	 * 	Return the title of card
	 */
	function getTitle($action)
	{
		return parent::getTitle($action);
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
		global $conf, $db, $langs, $user;
		global $form;

		parent::assign_values($action);

        $this->tpl['title'] = $this->getTitle($action);
        $this->tpl['error'] = $this->error;
        $this->tpl['errors']= $this->errors;

		if ($action == 'view')
		{
            // Card header
            $head = contact_prepare_head($this->object);
            $title = $this->getTitle($action);

		    $this->tpl['showhead']=dol_get_fiche_head($head, 'card', $title, 0, 'contact');
		    $this->tpl['showend']=dol_get_fiche_end();

			// Confirm delete contact
        	if ($user->rights->societe->contact->supprimer)
        	{
        		if ($_GET["action"] == 'delete')
        		{
        			$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id,$langs->trans("DeleteContact"),$langs->trans("ConfirmDeleteContact"),"confirm_delete",'',0,1);
        		}
        	}

        	$objsoc = new Societe($db);
            $objsoc->fetch($this->object->fk_soc);

            $this->tpl['actionstodo']=show_actions_todo($conf,$langs,$db,$objsoc,$this->object,1);

            $this->tpl['actionsdone']=show_actions_done($conf,$langs,$db,$objsoc,$this->object,1);
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