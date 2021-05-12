<?php
<<<<<<< HEAD
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012       Philippe Grand      <philippe.grand@atoo-net.com>
=======
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2018  Philippe Grand      <philippe.grand@atoo-net.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *	\file       htdocs/adherents/canvas/default/actions_adherentcard_default.class.php
 *	\ingroup    member
<<<<<<< HEAD
 *	\brief      Fichier de la classe Thirdparty adherent card controller (default canvas)
=======
 *	\brief      File of class Thirdparty member card controller (default canvas)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */
include_once DOL_DOCUMENT_ROOT.'/adherents/canvas/actions_adherentcard_common.class.php';

/**
 *	\class      ActionsAdherentCardDefault
<<<<<<< HEAD
 *	\brief      Classe permettant la gestion des adherents par defaut
=======
 *	\brief      Class allowing the management of the members by default
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */
class ActionsAdherentCardDefault extends ActionsAdherentCardCommon
{
	/**
     *	Constructor
     *
<<<<<<< HEAD
     *	@param	DoliDB	$db				Handler acces base de donnees
=======
     *	@param	DoliDB	$db				Handler acces data base
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *	@param	string	$dirmodule		Name of directory of module
     *	@param	string	$targetmodule	Name of directory of module where canvas is stored
     *	@param	string	$canvas			Name of canvas
     *	@param	string	$card			Name of tab (sub-canvas)
	 */
<<<<<<< HEAD
	function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
=======
	public function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
        $this->db               = $db;
        $this->dirmodule		= $dirmodule;
        $this->targetmodule     = $targetmodule;
        $this->canvas           = $canvas;
        $this->card             = $card;
	}

	/**
	 * 	Return the title of card
	 *
<<<<<<< HEAD
	 * 	@param	string	$action		Code action
=======
	 * 	@param	string	$action		Action code
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 * 	@return	string				Title
	 */
	private function getTitle($action)
	{
		global $langs,$conf;

		$out='';

		if ($action == 'view') 		$out.= (! empty($conf->global->ADHERENT_ADDRESSES_MANAGEMENT) ? $langs->trans("Adherent") : $langs->trans("ContactAddress"));
		if ($action == 'edit') 		$out.= (! empty($conf->global->ADHERENT_ADDRESSES_MANAGEMENT) ? $langs->trans("EditAdherent") : $langs->trans("EditAdherentAddress"));
		if ($action == 'create')	$out.= (! empty($conf->global->ADHERENT_ADDRESSES_MANAGEMENT) ? $langs->trans("NewAdherent") : $langs->trans("NewAdherentAddress"));

		return $out;
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Assign custom values for canvas
	 *
	 *  @param	string		$action    	Type of action
	 *  @param	int			$id				Id
	 *  @return	void
	 */
<<<<<<< HEAD
	function assign_values(&$action, $id)
	{
=======
	public function assign_values(&$action, $id)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $limit, $offset, $sortfield, $sortorder;
		global $conf, $db, $langs, $user;
		global $form;

		$ret = $this->getObject($id);

        parent::assign_values($action, $id);

        $this->tpl['title'] = $this->getTitle($action);
        $this->tpl['error'] = $this->error;
        $this->tpl['errors']= $this->errors;

		if ($action == 'view')
		{
            // Card header
            $head = member_prepare_head($this->object);
            $title = $this->getTitle($action);

		    $this->tpl['showhead']=dol_get_fiche_head($head, 'card', $title, 0, 'adherent');
		    $this->tpl['showend']=dol_get_fiche_end();

        	$objsoc = new Societe($db);
            $objsoc->fetch($this->object->socid);

<<<<<<< HEAD
            $this->tpl['actionstodo']=show_actions_todo($conf,$langs,$db,$objsoc,$this->object,1);

            $this->tpl['actionsdone']=show_actions_done($conf,$langs,$db,$objsoc,$this->object,1);
=======
            $this->tpl['actionstodo']=show_actions_todo($conf, $langs, $db, $objsoc, $this->object, 1);

            $this->tpl['actionsdone']=show_actions_done($conf, $langs, $db, $objsoc, $this->object, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
		else
		{
			// Confirm delete contact
        	if ($action == 'delete' && $user->rights->adherent->supprimer)
        	{
<<<<<<< HEAD
        		$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id,$langs->trans("DeleteAdherent"),$langs->trans("ConfirmDeleteAdherent"),"confirm_delete",'',0,1);
=======
        		$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id, $langs->trans("DeleteAdherent"), $langs->trans("ConfirmDeleteAdherent"), "confirm_delete", '', 0, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        	}
		}

		if ($action == 'list')
		{
	        $this->LoadListDatas($limit, $offset, $sortfield, $sortorder);
		}
<<<<<<< HEAD

	}


=======
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * 	Fetch datas list and save into ->list_datas
	 *
	 *  @param	int		$limit		Limit number of responses
	 *  @param	int		$offset		Offset for first response
	 *  @param	string	$sortfield	Sort field
	 *  @param	string	$sortorder	Sort order ('ASC' or 'DESC')
	 *  @return	void
	 */
<<<<<<< HEAD
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
=======
	public function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf, $langs;

        //$this->getFieldList();

        $this->list_datas = array();
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
