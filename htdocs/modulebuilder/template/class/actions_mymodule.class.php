<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsMyModule
 */
class ActionsMyModule
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

        /*
		print_r($parameters);
		print_r($object);
		echo "action: " . $action;
        */

	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2'))) {    // do something only for the context 'somecontext1' or 'somecontext2'


		}

		if (! $error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0;                                    // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

	    $error = 0; // Error counter

        /*
	    print_r($parameters);
	    print_r($object);
	    echo "action: " . $action;
        */

	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2'))) {  // do something only for the context 'somecontext1' or 'somecontext2'

	        foreach($parameters['toselect'] as $objectid)
	        {
	            // Do action on each object id

	        }
	    }

	    if (! $error) {
	        $this->results = array('myreturn' => 999);
	        $this->resprints = 'A text to show';
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

	    $error = 0; // Error counter

	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))  // do something only for the context 'somecontext'
	    {
	        $this->resprints = '<option value="0"'.($disabled?' disabled="disabled"':'').'>'.$langs->trans("MyModuleMassAction").'</option>';
	    }

	    if (! $error) {
	        return 0;                                    // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}



}
