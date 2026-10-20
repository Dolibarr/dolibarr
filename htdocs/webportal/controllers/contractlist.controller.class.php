<?php
/* Copyright (C) 2026		Pierre Ardoin				<developpeur@lesmetiersdubatiment.fr>
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
 * \file		htdocs/webportal/controllers/contractlist.controller.class.php
 * \ingroup	webportal
 * \brief		This file is a controller for contract list.
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formlistwebportal.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/controllers/abstractlist.controller.class.php';

/**
 * Class for ContractListController
 */
class ContractListController extends AbstractListController
{
	/**
	 * Check current access to controller.
	 *
	 * @return	bool
	 */
	public function checkAccess()
	{
		$this->accessRight = isModEnabled('contract') && getDolGlobalInt('WEBPORTAL_CONTRACT_LIST_ACCESS');

		return parent::checkAccess();
	}

	/**
	 * Action method is called before html output.
	 *
	 * @return	int		Return integer < 0 on error, > 0 on success
	 */
	public function action()
	{
		global $langs;

		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return -1;
		}

		$langs->loadLangs(array('contracts', 'companies', 'products', 'categories'));

		$context->title = $langs->trans('WebPortalContractListTitle');
		$context->desc = $langs->trans('WebPortalContractListDesc');
		$context->menu_active[] = 'contract_list';

		$this->formList = new FormListWebPortal($this->db);
		$this->formList->init($this, 'contract');

		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$this->formList->doActions();
		}

		$sqlBody = " AND t.fk_soc = " . ((int) $context->logged_thirdparty->id);
		$sqlBody .= " AND t.statut <> 0";
		$this->formList->setSqlRequest('', $sqlBody);

		$this->formList->loadRecords();
		$this->formList->setParams();
		$this->formList->setColumnsVisibility();

		return 1;
	}

	/**
	 * Display
	 *
	 * @return	void
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
			$this->loadTemplate('list');
			print '</main>';
		}

		$this->loadTemplate('footer');
	}
}
