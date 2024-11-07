<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file        htdocs/webportal/controllers/partnershipcard.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for partnership card
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formcardwebportal.class.php';

/**
 * Class for PartnershipCardController
 */
class PartnershipCardController extends Controller
{
	/**
	 * @var FormCardWebPortal Form for card
	 */
	protected $formCard;


	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$context = Context::getInstance();
		$cardAccess = getDolGlobalString('WEBPORTAL_PARTNERSHIP_CARD_ACCESS');
		$this->accessRight = isModEnabled('partnership') && in_array($cardAccess, array('visible', 'edit')) && $context->logged_partnership && $context->logged_partnership->id > 0;
		return parent::checkAccess();
	}

	/**
	 * Action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @return  int     Return integer < 0 on error, > 0 on success
	 */
	public function action()
	{
		global $langs;

		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return -1;
		}

		// Load translation files required by the page
		$langs->loadLangs(array('partnership'));

		$context->title = $langs->trans('WebPortalPartnershipCardTitle');
		$context->desc = $langs->trans('WebPortalPartnershipCardDesc');
		$context->menu_active[] = 'partnership_card';

		// set form card
		$cardAccess = getDolGlobalString('WEBPORTAL_PARTNERSHIP_CARD_ACCESS');
		$permissiontoread = (int) isModEnabled('partnership') && in_array($cardAccess, array('visible', 'edit'));
		$permissiontoadd = (int) isModEnabled('partnership') && in_array($cardAccess, array('edit'));
		$permissiontodelete = 0;
		$permissionnote = 0;
		$permissiondellink = 0;
		$formCardWebPortal = new FormCardWebPortal($this->db);
		$formCardWebPortal->init('partnership', $context->logged_partnership->id, $permissiontoread, $permissiontoadd, $permissiontodelete, $permissionnote, $permissiondellink);

		// hook for action
		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$formCardWebPortal->doActions();
		}

		$this->formCard = $formCardWebPortal;

		return 1;
	}

	/**
	 * Display
	 *
	 * @return  void
	 */
	public function display()
	{
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			$this->display404();
			return;
		}

		$this->loadTemplate('header');
		$this->loadTemplate('menu');
		$this->loadTemplate('hero-header-banner');

		$hookRes = $this->hookPrintPageView();
		if (empty($hookRes)) {
			print '<main class="container">';
			print $this->formCard->elementCard($context);
			print '</main>';
		}

		$this->loadTemplate('footer');
	}
}
