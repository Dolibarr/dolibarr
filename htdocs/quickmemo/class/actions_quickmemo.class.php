<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2026		John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    quickmemo/class/actions_quickmemo.class.php
 * \ingroup quickmemo
 * \brief   Example hook overload.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

require_once __DIR__ . '/memo.class.php';


/**
 * Class ActionsQuickMemo
 */
class ActionsQuickMemo extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overload the llxFooter function : add or replace array of object linkable
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function llxFooter($parameters, &$object, &$action, $hookmanager)
	{
		if (!isModEnabled('quickmemo')) {
			return 0;
		}

		$context = Memo::getMemoContext($parameters['context']??'');
		if (empty($context)) {
			return 0;
		}

		$jsConfVars = [
			'context' => $context,
		];

		if (!empty($object) && is_object($object) && isset($object->element)) {
			$jsConfVars = array_merge([
				'archivesUrlParams' => '&search_fk_element='. (int) $object->id .'&search_element_type='.$object->element,
				'elementId' => (int) $object->id,
				'elementType' => $object->element
			], $jsConfVars);
		}

		Memo::loadQuickMemoJsInterface($jsConfVars);

		return 0;
	}


	/**
	 * Overload the addHtmlHeader function : add or replace array of object linkable
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		if (!isModEnabled('quickmemo')) {
			return 0;
		}

		if (empty($object) || !is_object($object) || !isset($object->element)) {
			return 0;
		}

		$jsConfVars = [
			'archivesUrlParams' => '&search_fk_element='. (int) $object->id .'&search_element_type='.$object->element,
			'elementId' => (int) $object->id,
			'elementType' => $object->element,
			'context' => Memo::getMemoContext($parameters['context']??'', $object),
		];

		Memo::loadQuickMemoJsInterface($jsConfVars);

		return 0;
	}
}
