<?php
/* Copyright (C) 2026		Pierre Ardoin			<developpeur@lesmetiersdubatiment.fr>
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
 * \file        htdocs/webportal/controllers/ficheinterlist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for intervention list
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formlistwebportal.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/controllers/abstractlist.controller.class.php';

/**
 * Class for FicheinterListController
 */
class FicheinterListController extends AbstractListController
{
	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = isModEnabled('ficheinter') && getDolGlobalInt('WEBPORTAL_FICHEINTER_LIST_ACCESS');

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

		$langs->loadLangs(array('fichinter', 'companies', 'products'));

		$context->title = $langs->trans('WebPortalFicheinterListTitle');
		$context->desc = $langs->trans('WebPortalFicheinterListDesc');
		$context->menu_active[] = 'ficheinter_list';

		$this->formList = new FormListWebPortal($this->db);
		$this->formList->init($this, 'ficheinter');

		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$this->formList->doActions();
		}

		$sqlBody = " AND t.fk_soc = ".((int) $context->logged_thirdparty->id);
		$sqlBody .= " AND t.fk_statut <> 0";

		$searchStatus = (string) ($this->formList->search['fk_statut'] ?? '');
		if ($searchStatus !== '') {
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_VALIDATED) {
				$sqlBody .= " AND (t.fk_statut = ".((int) WebPortalFicheinter::STATUS_VALIDATED)." OR (t.fk_statut = ".((int) WebPortalFicheinter::STATUS_VALIDATED)." AND t.signed_status IN (1, 2, 3, 9)))";
			}

			$searchSignedStatus = 0;
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_SIGN_NOT_SIGNED) {
				$searchSignedStatus = 0;
			}
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_SIGN_SIGNED_INTERNAL) {
				$searchSignedStatus = 1;
			}
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_SIGN_SIGNED_THIRDPARTY) {
				$searchSignedStatus = 2;
			}
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_SIGN_SIGNED_THIRDPARTY_ONLINE) {
				$searchSignedStatus = 3;
			}
			if ((int) $searchStatus === WebPortalFicheinter::STATUS_SIGN_SIGNED_ALL_PARTIES) {
				$searchSignedStatus = 9;
			}

			if ((int) $searchStatus >= WebPortalFicheinter::STATUS_SIGN_NOT_SIGNED && (int) $searchStatus <= WebPortalFicheinter::STATUS_SIGN_SIGNED_ALL_PARTIES) {
				$this->formList->search['fk_statut'] = '';
				$sqlBody .= " AND t.fk_statut = ".((int) WebPortalFicheinter::STATUS_VALIDATED);
				if ($searchSignedStatus === 0) {
					$sqlBody .= " AND (t.signed_status IS NULL OR t.signed_status = 0)";
				} else {
					$sqlBody .= " AND t.signed_status = ".((int) $searchSignedStatus);
				}
			}
		}

		$this->formList->setSqlRequest('', $sqlBody);

		$this->formList->loadRecords();
		$this->formList->setParams();
		$this->formList->setColumnsVisibility();

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
			$this->loadTemplate('list');
			print '</main>';
		}

		$this->loadTemplate('footer');
	}
}
