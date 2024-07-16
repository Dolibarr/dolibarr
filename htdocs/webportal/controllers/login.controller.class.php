<?php
/*
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
 * \file        htdocs/webportal/controllers/login.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for login
 */
/**
 * Class for LoginController
 */
class LoginController extends Controller
{
	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = true;
		$context = Context::getInstance();
		return !$context->userIslog();
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

		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$context->title = $langs->trans('WebPortalHomeTitle');
			$context->desc = $langs->trans('WebPortalHomeDesc');
			//$context->doNotDisplayHeaderBar=1;// hide default header
		}

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

		$this->loadTemplate('header_login');

		$hookRes = $this->hookPrintPageView();

		if (empty($hookRes)) {
			$this->loadTemplate('login');
		}

		$this->loadTemplate('footer');
	}
}
