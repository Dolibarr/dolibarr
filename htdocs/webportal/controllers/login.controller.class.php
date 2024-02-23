<?php

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
