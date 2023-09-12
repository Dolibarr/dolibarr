<?php

/**
 * Class for DefaultController
 */
class DefaultController extends Controller
{
	/**
	 * Check current access to controller
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = true;

		return parent::checkAccess();
	}

	/**
	 * Action method is called before html output
	 * can be used to manage security and change context
	 *
	 * @return void
	 */
	public function action()
	{
		global $langs;
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return;
		}

		$hookRes = $this->hookDoAction();
		if (empty($hookRes)) {
			$context->title = $langs->trans('WebPortalHomeTitle');
			$context->desc = $langs->trans('WebPortalHomeDesc');
			//$context->doNotDisplayHeaderBar=1;// hide default header
		}
	}

	/**
	 * Display
	 *
	 * @return void
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

		$hookRes = $this->hookPrintPageView();

		if (empty($hookRes)) {
			$this->loadTemplate('home');
		}

		$this->loadTemplate('footer');
	}
}
