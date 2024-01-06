<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/contact/canvas/default/actions_contactcard_default.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty contact card controller (default canvas)
 */
include_once DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php';

/**
 *	\class      ActionsContactCardDefault
 *	\brief      Classe permettant la gestion des contacts par defaut
 */
class ActionsContactCardDefault extends ActionsContactCardCommon
{
	/**
	 *  Constructor
	 *
	 *	@param	DoliDB	$db				Handler acces base de donnees
	 *	@param	string	$dirmodule		Name of directory of module
	 *	@param	string	$targetmodule	Name of directory of module where canvas is stored
	 *	@param	string	$canvas			Name of canvas
	 *	@param	string	$card			Name of tab (sub-canvas)
	 */
	public function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
	{
		$this->db = $db;
		$this->dirmodule = $dirmodule;
		$this->targetmodule = $targetmodule;
		$this->canvas = $canvas;
		$this->card = $card;
	}

	/**
	 * 	Return the title of card
	 *
	 * 	@param	string	$action		Code action
	 * 	@return	string				Title
	 */
	private function getTitle($action)
	{
		global $langs, $conf;

		$out = '';

		if ($action == 'view') {
			$out .= (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("Contact") : $langs->trans("ContactAddress"));
		}
		if ($action == 'edit') {
			$out .= (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("EditContact") : $langs->trans("EditContactAddress"));
		}
		if ($action == 'create') {
			$out .= (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("NewContact") : $langs->trans("NewContactAddress"));
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Assign custom values for canvas
	 *
	 *  @param	string		$action    	Type of action
	 *  @param	int			$id				Id
	 *  @return	void
	 */
	public function assign_values(&$action, $id)
	{
		// phpcs:enable
		global $conf, $db, $langs, $user;
		global $form;

		$ret = $this->getObject($id);

		parent::assign_values($action, $id);

		$this->tpl['title'] = $this->getTitle($action);
		$this->tpl['error'] = $this->error;
		$this->tpl['errors'] = $this->errors;

		if ($action == 'view') {
			// Card header
			$head = contact_prepare_head($this->object);
			$title = $this->getTitle($action);

			$this->tpl['showhead'] = dol_get_fiche_head($head, 'card', $title, 0, 'contact');
			$this->tpl['showend'] = dol_get_fiche_end();

			$objsoc = new Societe($db);
			$objsoc->fetch($this->object->socid);

			$this->tpl['actionstodo'] = show_actions_todo($conf, $langs, $db, $objsoc, $this->object, 1);

			$this->tpl['actionsdone'] = show_actions_done($conf, $langs, $db, $objsoc, $this->object, 1);
		} else {
			// Confirm delete contact
			if ($action == 'delete' && $user->hasRight('societe', 'contact', 'supprimer')) {
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id, $langs->trans("DeleteContact"), $langs->trans("ConfirmDeleteContact"), "confirm_delete", '', 0, 1);
			}
		}
	}
}
