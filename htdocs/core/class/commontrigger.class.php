<?php
/* Copyright (C) 2006-2026  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * Parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 *
 * @phan-forbid-undeclared-magic-properties
 */
trait CommonTrigger
{
	/**
	 * @var DoliDB		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var string		Prefix to check for any trigger code of any business class to prevent bad value for trigger code.
	 * @see CommonTrigger::call_trigger()
	 *
	 * We do not use a constant because PHP does not support constant in Trait and does not allow overriding a constant without using "override" key
	 * that is not available on all PHP versions
	 */
	public $TRIGGER_PREFIX = ''; // to be overridden in child class implementations, i.e. 'BILL', 'TASK', 'PROPAL', etc. It is used to check that trigger code matches object name.


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Call trigger based on this instance.
	 * Some context information may also be provided into array property this->context.
	 * NB:  Error from trigger are stacked in interface->errors
	 * NB2: If return code of triggers are < 0, action calling trigger should cancel all transaction.
	 *
	 * @param   string    $triggerName  Trigger's name to execute
	 * @param   ?User     $user         Object user
	 * @return  int                     Result of run_triggers
	 */
	public function call_trigger($triggerName, $user)
	{
		// phpcs:enable
		global $langs, $conf;

		if (!empty($this->TRIGGER_PREFIX) && strpos($triggerName, $this->TRIGGER_PREFIX . '_') !== 0) {
			if (!preg_match('/^OBJECT_LINK/', $triggerName) && !preg_match('/^PAYMENTONLINE_/', $triggerName)) {	// We add this exception as we have some call_trigger with a triggeName that is not the business object (as in method inside the commonobjet.class.php to manage links)
				dol_syslog('The trigger "' . $triggerName . '" does not start with "' . $this->TRIGGER_PREFIX . '_" as required.', LOG_ERR);
				//dol_print_error(null, 'The trigger "' . $triggerName . '" does not start with "' . $this->TRIGGER_PREFIX . '_" as required.');
				//exit;
			}
		}
		if (!is_object($langs)) {	// If lang was not defined, we set it. It is required by run_triggers().
			dol_syslog("call_trigger was called with no langs variable defined so we init one. ".getCallerInfoString());
			include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', $conf);
			$langs->setDefaultLang('');
			$langs->load("main");
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
		$interface = new Interfaces($this->db);
		$result = $interface->run_triggers($triggerName, $this, $user, $langs, $conf);

		if ($result < 0) {
			if (!empty($this->errors)) {
				$this->errors = array_unique(array_merge($this->errors, $interface->errors)); // We use array_unique because when a trigger call another trigger on same object, this->errors is added twice.
			} else {
				$this->errors = $interface->errors;
			}
		}
		return $result;
	}
}
