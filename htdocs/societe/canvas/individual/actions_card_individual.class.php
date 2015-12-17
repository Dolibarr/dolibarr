<?php
/* Copyright (C) 2010-2011 Regis Houssin  <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/societe/canvas/individual/actions_card_individual.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (individual canvas)
 */
include_once DOL_DOCUMENT_ROOT.'/societe/canvas/actions_card_common.class.php';

/**
 *	\class      ActionsCardIndividual
 *	\brief      Class with controller methods for individual canvas
 */
class ActionsCardIndividual extends ActionsCardCommon
{
    /**
	 *    Constructor
	 *
     *    @param	DoliDB	$db				Handler acces base de donnees
     *    @param	string	$dirmodule		Name of directory of module
     *    @param	string	$targetmodule	Name of directory of module where canvas is stored
     *    @param	string	$canvas			Name of canvas
     *    @param	string	$card			Name of tab (sub-canvas)
     */
	function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
	{
		$this->db				= $db;
		$this->dirmodule		= $dirmodule;
		$this->targetmodule		= $targetmodule;
        $this->canvas			= $canvas;
        $this->card				= $card;
	}


    /**
     *  Return the title of card
     *
     *  @param	string	$action		Action code
     *  @return	string				Title
     */
    private function getTitle($action)
    {
        global $langs;

        $out='';

        if ($action == 'view')      $out.= $langs->trans("Individual");
        if ($action == 'edit')      $out.= $langs->trans("EditCompany");
        if ($action == 'create')    $out.= $langs->trans("NewCompany");

        return $out;
    }


	/**
	 * Execute actions
     * @deprecated Use the doActions of hooks instead of this.
	 *
	 * @param	string	$action	Action
	 * @param	int		$id			Id of object (may be empty for creation)
	 * @return	int					<0 if KO, >0 if OK
	 */
	function doActions(&$action, $id)
	{
		$ret = $this->getObject($id);

		$return = parent::doActions($action);

		return $return;
	}

	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
	 */
	function assign_values(&$action, $id=0, $ref='')
	{
		global $conf, $langs;
		global $form, $formcompany;

		$ret = $this->getObject($id,$ref);

		parent::assign_values($action);

        $this->tpl['title'] = load_fiche_titre($this->getTitle($action));

		if ($action == 'create' || $action == 'edit')
		{
			$this->tpl['select_civility'] = $formcompany->select_civility(GETPOST('civility_id'));
		}
		else
		{
			// Confirm delete third party
			if ($action == 'delete' || $conf->use_javascript_ajax)
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id,$langs->trans("DeleteAnIndividual"),$langs->trans("ConfirmDeleteIndividual"),"confirm_delete",'',0,"1,action-delete");
			}
		}
	}

	/**
	 * 	Check permissions of a user to show a page and an object. Check read permission
	 * 	If $_REQUEST['action'] defined, we also check write permission.
	 *
	 * 	@param      User	$user      	  	User to check
	 * 	@param      string	$features	    Features to check (in most cases, it's module name)
	 * 	@param      int		$objectid      	Object ID if we want to check permission on a particular record (optional)
	 *  @param      string	$dbtablename    Table name where object is stored. Not used if objectid is null (optional)
	 *  @param      string	$feature2		Feature to check (second level of permission)
	 *  @param      string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. (optional)
	 *  @param      string	$dbt_select		Field name for select if not rowid. (optional)
	 *  @return		int						1
	 */
	function restrictedArea($user, $features='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
	{
		return restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
	}

}

