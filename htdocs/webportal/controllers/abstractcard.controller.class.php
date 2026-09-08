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

require_once __DIR__ . '/../class/controller.class.php';

/**
 * \file        htdocs/webportal/controllers/abstractcard.controller.class.php
 * \ingroup     webportal
 * \brief       This file is an abstract controller with shared logic to display a card
 */

/**
 * Class for AbstractCardController
 */
abstract class AbstractCardController extends Controller
{
	/**
	 * @var FormCardWebPortal Form for card
	 */
	public $formCard;

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

		$this->loadTemplate('header', [
			'body-class' => empty($this->formCard->modal) ? '' : '--is-modal'
		]);

		if (empty($this->formCard->modal)) {
			$this->loadTemplate('menu');
			$this->loadTemplate('hero-header-banner');
		}

		// @phpstan-ignore-next-line
		if (isset($this->formCard)) {
			$hookRes = $this->hookPrintPageView();
			if (empty($hookRes)) {
				print '<main class="container">';
				if ($this->formCard->object->id > 0) {
					if ($this->formCard->action == 'edit' && $this->formCard->permissiontoadd) {
						$this->loadTemplate('card-edit');
					} else {
						$this->loadTemplate('card-view');
					}
				}
				print '</main>';
			}
		} else {
			$this->loadTemplate('404');
		}

		$this->loadTemplate('footer');
	}
}
