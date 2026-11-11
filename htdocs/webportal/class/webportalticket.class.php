<?php
/* Copyright (C) 2026		Pierre Ardoin			<developpeur@lesmetiersdubatiment.fr>
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
 * \file        htdocs/webportal/class/webportalticket.class.php
 * \ingroup     webportal
 * \brief       This file is a class file for WebPortalTicket
 */

require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

/**
 * Class for WebPortalTicket
 */
class WebPortalTicket extends Ticket
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'webportal';

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'position' => 1, 'notnull' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => -2, 'enabled' => 1, 'position' => 5, 'notnull' => 1),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'visible' => 2, 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'showoncombobox' => 1),
		'subject' => array('type' => 'varchar(255)', 'label' => 'Subject', 'visible' => 2, 'enabled' => 1, 'position' => 20, 'notnull' => -1),
		'track_id' => array('type' => 'varchar(255)', 'label' => 'TicketTrackId', 'visible' => -2, 'enabled' => 1, 'position' => 30, 'notnull' => -1),
		'origin_email' => array('type' => 'mail', 'label' => 'OriginEmail', 'visible' => -2, 'enabled' => 1, 'position' => 35, 'notnull' => -1),
		'fk_user_create' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Author', 'visible' => -2, 'enabled' => 1, 'position' => 40, 'notnull' => 1),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'visible' => 2, 'enabled' => 1, 'position' => 500, 'notnull' => 1),
		'fk_user_assign' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'AssignedTo', 'visible' => 2, 'enabled' => 1, 'position' => 507, 'notnull' => 1),
		'fk_statut' => array('type' => 'integer', 'label' => 'Status', 'visible' => 2, 'enabled' => 1, 'position' => 600, 'notnull' => 1, 'arrayofkeyval' => array(0 => 'Unread', 1 => 'Read', 2 => 'Assigned', 3 => 'InProgress', 5 => 'NeedMoreInformation', 7 => 'OnHold', 8 => 'SolvedClosed', 9 => 'Deleted')),
	);

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);

		if (!getDolGlobalString('TICKET_INCLUDE_SUSPENDED_STATUS')) {
			unset($this->fields['fk_statut']['arrayofkeyval'][self::STATUS_WAITING]);
		}
	}
}
