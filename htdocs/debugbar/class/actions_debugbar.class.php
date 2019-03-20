<?php

/**
 * ActionsDebugBar class
 */

class ActionsDebugBar
{
	/**
	 * Load Debug bar
	 *
	 * @return void
	 */
	protected function loadDebugBar()
	{
		global $conf, $debugbar;

		dol_include_once('/debugbar/class/DebugBar.php');
		$debugbar = new DolibarrDebugBar();
		$renderer = $debugbar->getRenderer();
		$conf->global->MAIN_HTML_HEADER .= $renderer->renderHead();
	}

	/**
	 * Overloading the afterLogin function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function afterLogin($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		if (in_array('login', explode(':', $parameters['context'])))
		{
			$this->loadDebugBar();
		}

		if (! $error)
		{
			return 0; // or return 1 to replace standard code
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Overloading the updateSession function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function updateSession($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		if (in_array('main', explode(':', $parameters['context'])))
		{
			$this->loadDebugBar();
		}

		if (! $error)
		{
			return 0; // or return 1 to replace standard code
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Overloading the printCommonFooter function
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             <0 on error, 0 on success, 1 to replace standard code
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $debugbar, $langs;

		$error = 0; // Error counter
		$context = explode(':', $parameters['context']);

		if (in_array('main', $context) || in_array('login', $context))
		{
			if ($user->rights->debugbar->read && is_object($debugbar)) {
				$renderer = $debugbar->getRenderer();
				echo $renderer->render();
			}
		}

		if (! $error)
		{
			return 0; // or return 1 to replace standard code
		}
		else
		{
			return -1;
		}
	}
}
