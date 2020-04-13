<?php
/*
 * Copyright (C) 2005-2017 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014 Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2013 Cedric GROSS <c.gross@kreiz-it.fr>
 * Copyright (C) 2014 Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2015 Bahfir Abbes <bafbes@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/core/triggers/interface_90_modSociete_ContactRoles.class.php
 * \ingroup agenda
 * \brief Trigger file for company - contactroles
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggered functions for agenda module
 */
class InterfaceContactRoles extends DolibarrTriggers
{

	public $family = 'agenda';

	public $description = "Triggers of this module auto link contact to company.";

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
	public $picto = 'action';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 * $object->socid or $object->fk_soc(id of thirdparty)
	 * $object->element (element type of object)
	 *
	 * @param string $action	Event action code
	 * @param Object $object	Object
	 * @param User $user		Object user
	 * @param Translate $langs	Object langs
	 * @param conf $conf		Object conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if ($action === 'PROPAL_CREATE' || $action === 'ORDER_CREATE' || $action === 'BILL_CREATE'
			|| $action === 'ORDER_SUPPLIER_CREATE' || $action === 'BILL_SUPPLIER_CREATE' || $action === 'PROPOSAL_SUPPLIER_CREATE'
			|| $action === 'CONTRACT_CREATE' || $action === 'FICHINTER_CREATE' || $action === 'PROJECT_CREATE' || $action === 'TICKET_CREATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$socid = (property_exists($object, 'socid') ? $object->socid : $object->fk_soc);

			if (!empty($socid) && $socid > 0) {
				require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$contactdefault = new Contact($this->db);
				$contactdefault->socid = $socid;
				$TContact = $contactdefault->getContactRoles($object->element);

				if (is_array($TContact) && !empty($TContact)) {
					$TContactAlreadyLinked = array();
					if ($object->id > 0) {
						$cloneFrom = dol_clone($object, 1);

						if (!empty($cloneFrom->id)) {
							$TContactAlreadyLinked = array_merge($cloneFrom->liste_contact(-1, 'external'), $cloneFrom->liste_contact(-1, 'internal'));
						}
					}

					foreach ($TContact as $i => $infos) {
						foreach ($TContactAlreadyLinked as $contactData) {
							if ($contactData['id'] == $infos['fk_socpeople'] && $contactData['fk_c_type_contact'] == $infos['type_contact'])
								unset($TContact[$i]);
						}
					}

					$nb = 0;
					foreach ($TContact as $infos) {
						$res = $object->add_contact($infos['fk_socpeople'], $infos['type_contact']);
						if ($res > 0)
							$nb++;
					}

					if ($nb > 0) {
						setEventMessages($langs->trans('ContactAddedAutomatically', $nb), null, 'mesgs');
					}
				}
			}
		}
		return 0;
	}
}
