<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * \file       htdocs/webportal/class/controller.class.php
 * \ingroup    webportal
 * \brief      File of controller class for WebPortal
 */

/**
 *  Class to manage pages
 */
class Controller
{
	/**
	 * if this controller need logged user or not
	 * @var bool
	 */
	public $accessNeedLoggedUser = true;

	/**
	 * define current user access
	 * @var bool
	 */
	public $accessRight = false;

	/**
	 * If controller is active
	 * @var bool
	 */
	public $controllerStatus = true;

	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Tpl path will use default context->tplPath if empty
	 */
	public $tplPath;


	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		global $db, $hookmanager;

		$this->db = $db;

		// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
		$hookmanager->initHooks(array('webportalpage', 'webportal'));
	}

	/**
	 * Action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @return  int     Return integer < 0 on error, > 0 on success
	 */
	public function action()
	{
		$resHook = $this->hookDoAction();

		return ($resHook < 0 ? -1 : 1);
	}

	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$context = Context::getInstance();

		if ($this->accessNeedLoggedUser) {
			if (!$context->userIslog()) {
				return false;
			}
		}

		if (!$this->accessRight) {
			return false;
		}

		return true;
	}

	/**
	 * Display
	 *
	 * @return  void
	 */
	public function display()
	{
		$context = Context::getInstance();

		$this->loadTemplate('header');

		$this->hookPrintPageView();

		if (!$context->controller_found) {
			$this->loadTemplate('404');
		}

		$this->loadTemplate('footer');
	}

	/**
	 * Display error template
	 *
	 * @return  void
	 */
	public function display404()
	{
		$this->loadTemplate('header');
		$this->loadTemplate('404');
		$this->loadTemplate('footer');
	}

	/**
	 * Execute hook doActions
	 *
	 * @param	array		$parameters		Parameters
	 * @return  int							Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function hookDoAction($parameters = array())
	{
		global $hookmanager;

		$context = Context::getInstance();

		/* Use $context singleton to modify menu, */
		$parameters['controller'] = $context->controller;

		$reshook = $hookmanager->executeHooks('doActions', $parameters, $context, $context->action);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		return $reshook;
	}

	/**
	 * Execute hook PrintPageView
	 *
	 * @param	array		$parameters		Parameters
	 * @return	int							Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function hookPrintPageView($parameters = array())
	{
		global $hookmanager;

		$context = Context::getInstance();

		/* Use $context singleton to modify menu, */
		$parameters['controller'] = $context->controller;

		$reshook = $hookmanager->executeHooks('PrintPageView', $parameters, $context, $context->action);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		return $reshook;
	}

	/**
	 * Load a template .tpl file
	 *
	 * @param	string	$templateName	Template file name (without the .tpl.php)
	 * @param	mixed	$vars			Data to transmit to template
	 * @return	bool					True if template found, else false
	 */
	public function loadTemplate($templateName, $vars = false)
	{
		global $conf, $langs, $hookmanager, $db; // may be used into the tpl

		$context = Context::getInstance(); // load for tpl

		if (!preg_match('/^[0-9\.A-ZaZ_\-]*$/ui', $templateName)) {
			return false;
		}

		if (!empty($this->tplPath)) {
			$tplPath = $this->tplPath . '/' . $templateName . '.tpl.php';
			if (file_exists($tplPath)) {
				include $tplPath;
				return true;
			}
		}

		$tplPath = $context->tplPath . '/' . $templateName . '.tpl.php';

		if (!file_exists($tplPath)) {
			print 'ERROR TPL NOT FOUND : ' . $templateName;
			return false;
		}

		$controller = $this; // transmit controller to tpl

		include $tplPath;

		return true;
	}
}
