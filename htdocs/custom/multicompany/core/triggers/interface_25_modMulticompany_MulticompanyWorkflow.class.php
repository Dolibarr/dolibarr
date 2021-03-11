<?php
/* Copyright (C) 2010-2020	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       /multicompany/core/triggers/interface_25_modMulticompany_MulticompanyWorkflow.class.php
 *      \ingroup    multicompany
 *      \brief      Trigger file for create multicompany data
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *      \class      InterfaceMulticompanyWorkflow
 *      \brief      Classe des fonctions triggers des actions personnalisees du module multicompany
 */

class InterfaceMulticompanyWorkflow extends DolibarrTriggers
{
    public $family = 'multicompany';

    public $description = "Triggers of this module allows to create multicompany data";

    /**
     * Version of the trigger
     *
     * @var string
     */
    public $version = self::VERSION_DOLIBARR;

    /**
     *
     * @var string Image of the trigger
     */
    public $picto = 'multicompany@multicompany';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/core/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 * $object->id (id of entity)
	 * $object->element (element type of object)
	 *
	 * 	@param		string		$action		Event action code
	 * 	@param		Object		$object		Object
	 * 	@param		User		$user		Object user
	 * 	@param		Translate	$langs		Object langs
	 * 	@param		conf		$conf		Object conf
	 * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		// Mettre ici le code a executer en reaction de l'action
		// Les donnees de l'action sont stockees dans $object

		/*if ($action == 'COMPANY_CREATE')
		{
			$entity = GETPOST('new_entity', 'int', 2); // limit to POST

			if ($entity > 0)
			{
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ". __FILE__ .". id=".$object->rowid);

				return $ret;
			}
		}*/

		return 0;
	}

}
