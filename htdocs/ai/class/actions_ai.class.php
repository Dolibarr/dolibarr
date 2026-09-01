<?php
/* Copyright (C) 2026  Braito                  <braito4@hotmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/ai/class/actions_ai.class.php
 * \ingroup ai
 * \brief   Hooks to integrate AI cleaner with EmailCollector.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/emailcleaner.class.php';

/**
 * Class ActionsAi
 */
class ActionsAi extends CommonHookActions
{
	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var array<int,string>
	 */
	public $errors = array();

	/**
	 * @var array<string,mixed>
	 */
	public $results = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Add AI Email Cleaner action in EmailCollector operations.
	 *
	 * @param array<string,mixed> $parameters Hook context parameters
	 * @param CommonObject $object Email collector object
	 * @param string $action Current action code
	 * @param HookManager $hookmanager Hook manager
	 * @return int
	 */
	public function addMoreActionsEmailCollector($parameters, &$object, &$action, $hookmanager)
	{
		if (!EmailCleaner::isRuntimeAvailable()) return 0;
		if (!getDolGlobalInt('AI_EMAILCLEANER_EXPOSE_OPERATION', 0)) return 0;

		$arrayoftypes = (!empty($parameters['arrayoftypes']) && is_array($parameters['arrayoftypes'])) ? $parameters['arrayoftypes'] : array();
		$arrayoftypes['hook_ai_emailcleaner'] = 'AI cleaner (no business decision)';
		$this->results = $arrayoftypes;

		return 1;
	}

	/**
	 * Execute AI cleaner when EmailCollector operation type is "hook_ai_emailcleaner".
	 *
	 * @param array<string,mixed> $parameters Hook context parameters
	 * @param CommonObject $object Email collector object
	 * @param string $action Current action code
	 * @param HookManager $hookmanager Hook manager
	 * @return int
	 */
	public function doCollectImapOneCollector($parameters, &$object, &$action, $hookmanager)
	{
		if (!EmailCleaner::isRuntimeAvailable()) return 0;
		if (!EmailCleaner::isEmailCollectorHookAction($action)) return 0;
		if (!getDolGlobalInt('AI_EMAILCLEANER_ENABLED', 0)) return 0;

		$emailCleaner = new EmailCleaner($this->db);
		$result = $emailCleaner->processEmailCollectorMessage($parameters, $object);
		if (!empty($result['file'])) {
			$this->results['ai_emailcleaner_file'] = (string) $result['file'];
		}

		return 0;
	}
}
