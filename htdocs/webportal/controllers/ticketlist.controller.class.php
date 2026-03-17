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
 * \file        htdocs/webportal/controllers/ticketlist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for ticket list
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formlistwebportal.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/controllers/abstractlist.controller.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * Class for TicketListController
 */
class TicketListController extends AbstractListController
{
	/**
	 * @var array<int,User>
	 */
	public $userStaticCache = array();

	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = isModEnabled('ticket') && getDolGlobalInt('WEBPORTAL_TICKET_LIST_ACCESS');

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

		$langs->loadLangs(array('ticket', 'companies', 'products'));

		$context->title = $langs->trans('WebPortalTicketListTitle');
		$context->desc = $langs->trans('WebPortalTicketListDesc');
		$context->menu_active[] = 'ticket_list';

		$this->formList = new FormListWebPortal($this->db);
		$this->formList->init($this, 'ticket');

		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$this->formList->doActions();
		}

		$sqlBody = " AND t.fk_soc = ".((int) $context->logged_thirdparty->id);
		$sqlBody .= " AND t.fk_statut <> 9";
		$this->formList->setSqlRequest('', $sqlBody);

		$this->formList->loadRecords();
		$this->formList->setParams();
		$this->formList->setColumnsVisibility();

		return 1;
	}


	/**
	 * Set array fields for ticket list
	 *
	 * @return	void
	 */
	public function listSetArrayFields()
	{
		$this->formList->arrayfields['consultation_link'] = array(
			'type' => '',
			'label' => 'WebPortalTicketConsultation',
			'checked' => 1,
			'enabled' => isModEnabled('ticket'),
			'visible' => 1,
			'position' => 10003,
			'help' => '',
		);
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
		global $langs;

		if ($field_key === 'fk_statut') {
			$this->formList->object->status = (int) ($record->fk_statut ?? 0);
			$this->formList->object->fk_statut = (int) ($record->fk_statut ?? 0);
			return $this->formList->object->getLibStatut(5);
		}

		if ($field_key === 'fk_user_assign') {
			$idUserAssign = (int) ($record->fk_user_assign ?? 0);
			if ($idUserAssign <= 0) {
				return $langs->trans('WebPortalTicketNotAssigned');
			}

			if (!isset($this->userStaticCache[$idUserAssign])) {
				$userStatic = new User($this->db);
				$userStatic->fetch($idUserAssign);
				$this->userStaticCache[$idUserAssign] = $userStatic;
			}

			if (!empty($this->userStaticCache[$idUserAssign]->id)) {
				return $this->userStaticCache[$idUserAssign]->getFullName($langs);
			}

			return $langs->trans('Unknown');
		}

		if ($field_key === 'consultation_link') {
			$baseurl = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');
			$baseurl = rtrim($baseurl, '/').'/';
			if ((int) ($record->fk_user_create ?? 0) <= 0 && !empty($record->track_id)) {
				$url = $baseurl.'view.php?track_id='.urlencode((string) $record->track_id);
				if (!empty($record->origin_email)) {
					$url .= '&email='.urlencode((string) $record->origin_email);
				}
				return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$langs->trans('WebPortalTicketConsultationLink').'</a>';
			}

			return img_object($langs->trans('WebPortalTicketConsultationTooltipNotPublic'), 'info', 'class="classfortooltip"');
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
