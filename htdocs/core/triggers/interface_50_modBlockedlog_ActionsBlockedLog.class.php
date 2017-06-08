<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013	   Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2015       Bahfir Abbes        <bafbes@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/triggers/interface_50_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/blockedlog.class.php';

/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsBlockedLog extends DolibarrTriggers
{
	public $family = 'system';
	public $description = "Triggers of this module add blocklog.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'system';

	/**
	 * Function called on Dolibarrr payment or invoice event.
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		// Do not log events not enabled for this action
		if (empty($conf->blockedlog->enabled)) {
			return 0;
		}
		
		$b=new BlockedLog($this->db);
		$b->element = $object->element;
		$b->action = $action;
		$b->fk_object = $object->id;
		$b->key_value1 = 0;
		
		$res = $b->create($user);
		if($res<0) {
			setEventMessage($b->error,'errors');
			
			return -1;
		}
		else {
			return 1;
		}
		
		
    }

}
