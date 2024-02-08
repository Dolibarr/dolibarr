<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
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
 * \file        htdocs/webportal/controllers/propallist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for propal list
 */

dol_include_once('/webportal/class/html.formlistwebportal.class.php');

/**
 * Class for PropalListController
 */
class PropalListController extends Controller
{
	/**
	 * @var FormListWebPortal Form for list
	 */
	protected $formList;


	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = isModEnabled('propal') && getDolGlobalInt('WEBPORTAL_PROPAL_LIST_ACCESS');

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
		$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'categories'));
		if (isModEnabled('expedition')) {
			$langs->loadLangs(array('sendings'));
		}

		$context->title = $langs->trans('WebPortalPropalListTitle');
		$context->desc = $langs->trans('WebPortalPropalListDesc');
		$context->menu_active[] = 'propal_list';

		// set form list
		$formListWebPortal = new FormListWebPortal($this->db);
		$formListWebPortal->init('propal');

		// hook for action
		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$formListWebPortal->doActions();
		}

		$this->formList = $formListWebPortal;

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
			//print '<figure>';
			print $this->formList->elementList($context);
			//print '</figure>';
			print '</main>';
		}

		$this->loadTemplate('footer');
	}
}
