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
		$this->accessRight = isModEnabled('intervention') && getDolGlobalInt('WEBPORTAL_FICHEINTER_LIST_ACCESS');

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
	 * Set array fields for intervention list
	 *
	 * @return	void
	 */
	public function listSetArrayFields()
	{
		$this->formList->arrayfields['download_link']['label'] = 'PDF';
		$this->formList->arrayfields['download_link']['enabled'] = isModEnabled('intervention');
		$this->formList->arrayfields['download_link']['checked'] = 1;
		$this->formList->arrayfields['signature_link']['enabled'] = isModEnabled('intervention');
		$this->formList->arrayfields['signature_link']['checked'] = 1;
	}

	/**
	 * Called before print value for list
	 *
	 * @param	string				$field_key		Field key
	 * @param	array<string,mixed>	$field_spec		Field specification
	 * @param	stdClass			$record			Contain data of object from database
	 * @return	string						HTML input
	 */
	public function listPrintValueBefore($field_key, $field_spec, &$record)
	{
		global $conf, $langs;

		if ($field_key === 'fk_statut') {
			$this->formList->object->status = (int) ($record->fk_statut ?? 0);
			$this->formList->object->statut = (int) ($record->fk_statut ?? 0);
			$this->formList->object->signed_status = (int) ($record->signed_status ?? 0);
			return $this->formList->object->getLibStatut(5);
		}

		if ($field_key === 'download_link') {
			$filename = dol_sanitizeFileName($this->formList->object->ref);
			$entity = (int) ($this->formList->object->entity ?? 1);
			$filedir = $conf->ficheinter->multidir_output[$entity] . '/' . dol_sanitizeFileName($this->formList->object->ref);
			return $this->formList->form->getDocumentsLink('ficheinter', $filename, $filedir);
		}

		if ($field_key === 'signature_link') {
			$status = (int) ($record->fk_statut ?? 0);
			$signedStatus = (int) ($record->signed_status ?? 0);
			if ($status === WebPortalFicheinter::STATUS_CLOSED || in_array($signedStatus, array(2, 3, 9), true)) {
				return $langs->trans('WebPortalInterSignedDone');
			}
			return $this->formList->form->getSignatureLink('fichinter', $this->formList->object);
		}

		return '';
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
