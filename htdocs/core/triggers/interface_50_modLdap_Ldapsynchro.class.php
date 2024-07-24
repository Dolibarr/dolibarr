<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2021	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_50_modLdap_Ldapsynchro.class.php
 *  \ingroup    core
 *  \brief      Fichier de gestion des triggers LDAP
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for ldap module
 */
class InterfaceLdapsynchro extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "ldap";
		$this->description = "Triggers of this module allows to synchronize Dolibarr toward a LDAP database.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = self::VERSION_DOLIBARR;
		$this->picto = 'technic';
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->ldap) || empty($conf->ldap->enabled)) {
			return 0; // Module not active, we do nothing
		}
		if (defined('DISABLE_LDAP_SYNCHRO')) {
			return 0; // If constant defined, we do nothing
		}

		if (!function_exists('ldap_connect')) {
			dol_syslog("Warning, module LDAP is enabled but LDAP functions not available in this PHP", LOG_WARNING);
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
		require_once DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php";

		$result = 0;

		// Users
		if ($action == 'USER_CREATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					//For compatibility with Samba 4 AD
					if ($ldap->serverType == "activedirectory") {
						$info['userAccountControl'] = $conf->global->LDAP_USERACCOUNTCONTROL;
					}

					$result = $ldap->add($dn, $info, $user);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USER_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);
					$newrdn = $object->_load_ldap_dn($info, 2);
					$newparent = $object->_load_ldap_dn($info, 1);

					$result = $ldap->update($dn, $info, $user, $olddn, $newrdn, $newparent);

					if ($result > 0 && !empty($object->context['newgroupid'])) {      // We are in context of adding a new group to user
						$usergroup = new UserGroup($this->db);

						$usergroup->fetch($object->context['newgroupid'], '', true);

						$oldinfo = $usergroup->_load_ldap_info();
						$olddn = $usergroup->_load_ldap_dn($oldinfo);

						// Verify if entry exist
						$container = $usergroup->_load_ldap_dn($oldinfo, 1);
						$search = "(".$usergroup->_load_ldap_dn($oldinfo, 2).")";
						$records = $ldap->search($container, $search);
						if (count($records) && $records['count'] == 0) {
							$olddn = '';
						}

						$info = $usergroup->_load_ldap_info(); // Contains all members, included the new one (insert already done before trigger call)
						$dn = $usergroup->_load_ldap_dn($info);

						$result = $ldap->update($dn, $info, $user, $olddn);
					}

					if ($result > 0 && !empty($object->context['oldgroupid'])) {      // We are in context of removing a group from user
						$usergroup = new UserGroup($this->db);

						$usergroup->fetch($object->context['oldgroupid'], '', true);

						$oldinfo = $usergroup->_load_ldap_info();
						$olddn = $usergroup->_load_ldap_dn($oldinfo);

						// Verify if an entry exists
						$container = $usergroup->_load_ldap_dn($oldinfo, 1);
						$search = "(".$usergroup->_load_ldap_dn($oldinfo, 2).")";
						$records = $ldap->search($container, $search);
						if (count($records) && $records['count'] == 0) {
							$olddn = '';
						}

						$info = $usergroup->_load_ldap_info(); // Contains all members, except the old one (remove already done before trigger call)
						$dn = $usergroup->_load_ldap_dn($info);

						$result = $ldap->update($dn, $info, $user, $olddn);
					}
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USER_NEW_PASSWORD') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->update($dn, $info, $user, $olddn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USER_ENABLEDISABLE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalInt("LDAP_SYNCHRO_ACTIVE") === Ldap::SYNCHRO_DOLIBARR_TO_LDAP && getDolGlobalString('LDAP_SERVER_TYPE') == "activedirectory") {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();
				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);
					$search = "(" . $object->_load_ldap_dn($info, 2) . ")";
					$uAC = $ldap->getAttributeValues($search, "userAccountControl");
					if ($uAC["count"] == 1) {
						$userAccountControl = intval($uAC[0]);
						$enabledBitMask = 0x2;
						$isEnabled = ($userAccountControl & $enabledBitMask) === 0;
						if ($isEnabled && intval($object->statut) === 1) {
							$userAccountControl += 2;
						} elseif (!$isEnabled && intval($object->statut) === 0) {
							$userAccountControl -= 2;
						}
						$info['userAccountControl'] = $userAccountControl;
						$resUpdate = $ldap->update($dn, $info, $user, $dn);
						if ($resUpdate < 0) {
							$this->error = "ErrorLDAP " . $ldap->error;
						}
					}
				} else {
					$this->error = "ErrorLDAP " . $ldap->error;
				}
			}
		} elseif ($action == 'USER_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->delete($dn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USERGROUP_CREATE') {
			// Groupes
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					// Get a gid number for objectclass PosixGroup if none was provided
					if (empty($info[getDolGlobalString('LDAP_GROUP_FIELD_GROUPID')]) && in_array('posixGroup', $info['objectclass'])) {
						$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_GROUPS');
					}

					// Avoid Ldap error due to empty member
					if (isset($info['member']) && empty($info['member'])) {
						unset($info['member']);
					}

					$result = $ldap->add($dn, $info, $user);
				}

				if ($ldap->serverType == "activedirectory") {
					$info['sAMAccountName'] = $object->name;
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USERGROUP_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->update($dn, $info, $user, $olddn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'USERGROUP_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->delete($dn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'CONTACT_CREATE') {
			// Contacts
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_CONTACT_ACTIVE')) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->add($dn, $info, $user);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'CONTACT_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_CONTACT_ACTIVE')) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->update($dn, $info, $user, $olddn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'CONTACT_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_CONTACT_ACTIVE')) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->delete($dn);
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_CREATE') {
			// Members
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->add($dn, $info, $user);

					// For member type
					if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
						if ($object->typeid > 0) {
							require_once DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php";
							$membertype = new AdherentType($this->db);
							$membertype->fetch($object->typeid);
							$membertype->listMembersForMemberType('', 1);

							$oldinfo = $membertype->_load_ldap_info();
							$olddn = $membertype->_load_ldap_dn($oldinfo);

							// Verify if entry exist
							$container = $membertype->_load_ldap_dn($oldinfo, 1);
							$search = "(".$membertype->_load_ldap_dn($oldinfo, 2).")";
							$records = $ldap->search($container, $search);
							if (count($records) && $records['count'] == 0) {
								$olddn = '';
							}

							$info = $membertype->_load_ldap_info(); // Contains all members, included the new one (insert already done before trigger call)
							$dn = $membertype->_load_ldap_dn($info);

							$result = $ldap->update($dn, $info, $user, $olddn);
						}
					}
				}

				if ($result < 0) {
					$this->error = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_VALIDATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				// If status field is setup to be synchronized
				if (getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')) {
					$ldap = new Ldap();
					$result = $ldap->connect_bind();

					if ($result > 0) {
						$info = $object->_load_ldap_info();
						$dn = $object->_load_ldap_dn($info);
						$olddn = $dn; // We know olddn=dn as we change only status

						$result = $ldap->update($dn, $info, $user, $olddn);
					}

					if ($result < 0) {
						$this->error = "ErrorLDAP ".$ldap->error;
					}
				}
			}
		} elseif ($action == 'MEMBER_SUBSCRIPTION') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				// If subscriptions fields are setup to be synchronized
				if (getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE')
					|| getDolGlobalString('LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT')
					|| getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE')
					|| getDolGlobalString('LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT')
					|| getDolGlobalString('LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION')) {
					$ldap = new Ldap();
					$result = $ldap->connect_bind();

					if ($result > 0) {
						$info = $object->_load_ldap_info();
						$dn = $object->_load_ldap_dn($info);
						$olddn = $dn; // We know olddn=dn as we change only subscriptions

						$result = $ldap->update($dn, $info, $user, $olddn);
					}

					if ($result < 0) {
						$this->error = "ErrorLDAP ".$ldap->error;
					}
				}
			}
		} elseif ($action == 'MEMBER_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);
					$newrdn = $object->_load_ldap_dn($info, 2);
					$newparent = $object->_load_ldap_dn($info, 1);

					$result = $ldap->update($dn, $info, $user, $olddn, $newrdn, $newparent);

					// For member type
					if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
						require_once DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php";

						/*
						 * Change member info
						 */
						$newmembertype = new AdherentType($this->db);
						$newmembertype->fetch($object->typeid);
						$newmembertype->listMembersForMemberType('', 1);

						$oldinfo = $newmembertype->_load_ldap_info();
						$olddn = $newmembertype->_load_ldap_dn($oldinfo);

						// Verify if entry exist
						$container = $newmembertype->_load_ldap_dn($oldinfo, 1);
						$search = "(".$newmembertype->_load_ldap_dn($oldinfo, 2).")";
						$records = $ldap->search($container, $search);
						if (count($records) && $records['count'] == 0) {
							$olddn = '';
						}

						$info = $newmembertype->_load_ldap_info(); // Contains all members, included the new one (insert already done before trigger call)
						$dn = $newmembertype->_load_ldap_dn($info);

						$result = $ldap->update($dn, $info, $user, $olddn);

						if ($object->oldcopy->typeid != $object->typeid) {
							/*
							 * Remove member in old member type
							 */
							$oldmembertype = new AdherentType($this->db);
							$oldmembertype->fetch($object->oldcopy->typeid);
							$oldmembertype->listMembersForMemberType('', 1);

							$oldinfo = $oldmembertype->_load_ldap_info();
							$olddn = $oldmembertype->_load_ldap_dn($oldinfo);

							// Verify if entry exist
							$container = $oldmembertype->_load_ldap_dn($oldinfo, 1);
							$search = "(".$oldmembertype->_load_ldap_dn($oldinfo, 2).")";
							$records = $ldap->search($container, $search);
							if (count($records) && $records['count'] == 0) {
								$olddn = '';
							}

							$info = $oldmembertype->_load_ldap_info(); // Contains all members, included the new one (insert already done before trigger call)
							$dn = $oldmembertype->_load_ldap_dn($info);

							$result = $ldap->update($dn, $info, $user, $olddn);
						}
					}
				}

				if ($result <= 0) {
					$this->errors[] = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_NEW_PASSWORD') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				// If password field is setup to be synchronized
				if (getDolGlobalString('LDAP_FIELD_PASSWORD') || getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')) {
					$ldap = new Ldap();
					$result = $ldap->connect_bind();

					if ($result > 0) {
						$info = $object->_load_ldap_info();
						$dn = $object->_load_ldap_dn($info);
						$olddn = $dn; // We know olddn=dn as we change only password

						$result = $ldap->update($dn, $info, $user, $olddn);
					}

					if ($result <= 0) {
						$this->errors[] = "ErrorLDAP ".$ldap->error;
					}
				}
			}
		} elseif ($action == 'MEMBER_RESILIATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				// If status field is setup to be synchronized
				if (getDolGlobalString('LDAP_FIELD_MEMBER_STATUS')) {
					$ldap = new Ldap();
					$result = $ldap->connect_bind();

					if ($result > 0) {
						$info = $object->_load_ldap_info();
						$dn = $object->_load_ldap_dn($info);
						$olddn = $dn; // We know olddn=dn as we change only status

						$result = $ldap->update($dn, $info, $user, $olddn);
					}

					if ($result <= 0) {
						$this->errors[] = "ErrorLDAP ".$ldap->error;
					}
				}
			}
		} elseif ($action == 'MEMBER_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_ACTIVE') == Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->delete($dn);

					// For member type
					if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
						if ($object->typeid > 0) {
							require_once DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php";

							/*
							 * Remove member in member type
							 */
							$membertype = new AdherentType($this->db);
							$membertype->fetch($object->typeid);
							$membertype->listMembersForMemberType('a.rowid != '.$object->id, 1); // remove deleted member from the list

							$oldinfo = $membertype->_load_ldap_info();
							$olddn = $membertype->_load_ldap_dn($oldinfo);

							// Verify if entry exist
							$container = $membertype->_load_ldap_dn($oldinfo, 1);
							$search = "(".$membertype->_load_ldap_dn($oldinfo, 2).")";
							$records = $ldap->search($container, $search);
							if (count($records) && $records['count'] == 0) {
								$olddn = '';
							}

							$info = $membertype->_load_ldap_info(); // Contains all members, included the new one (insert already done before trigger call)
							$dn = $membertype->_load_ldap_dn($info);

							$result = $ldap->update($dn, $info, $user, $olddn);
						}
					}
				}

				if ($result <= 0) {
					$this->errors[] = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_TYPE_CREATE') {
			// Members types
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					// Get a gid number for objectclass PosixGroup
					if (in_array('posixGroup', $info['objectclass'])) {
						$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_MEMBERS_TYPE');
					}

					$result = $ldap->add($dn, $info, $user);
				}

				if ($result <= 0) {
					$this->errors[] = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_TYPE_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					if (empty($object->oldcopy) || !is_object($object->oldcopy)) {
						dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
						$object->oldcopy = clone $object;
					}

					$object->oldcopy->listMembersForMemberType('', 1);

					$oldinfo = $object->oldcopy->_load_ldap_info();
					$olddn = $object->oldcopy->_load_ldap_dn($oldinfo);

					// Verify if entry exist
					$container = $object->oldcopy->_load_ldap_dn($oldinfo, 1);
					$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo, 2).")";
					$records = $ldap->search($container, $search);
					if (count($records) && $records['count'] == 0) {
						$olddn = '';
					}

					$object->listMembersForMemberType('', 1);

					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->update($dn, $info, $user, $olddn);
				}

				if ($result <= 0) {
					$this->errors[] = "ErrorLDAP ".$ldap->error;
				}
			}
		} elseif ($action == 'MEMBER_TYPE_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (getDolGlobalString('LDAP_MEMBER_TYPE_ACTIVE') && getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
				$ldap = new Ldap();
				$result = $ldap->connect_bind();

				if ($result > 0) {
					$info = $object->_load_ldap_info();
					$dn = $object->_load_ldap_dn($info);

					$result = $ldap->delete($dn);
				}

				if ($result <= 0) {
					$this->errors[] = "ErrorLDAP ".$ldap->error;
				}
			}
		}

		return $result;
	}
}
