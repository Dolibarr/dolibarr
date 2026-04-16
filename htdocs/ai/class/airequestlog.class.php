<?php
/* Copyright (C) 2004-2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/class/airequestlog.class.php
 * \ingroup ai
 * \brief Class file used for multicompany entity filtering with getEntity()
 */


require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for AI Request Log entries
 */
class AiRequestLog extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 *             Used by getEntity('airequestlog')
	 */
	public $element = 'airequestlog';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'ai_request_log';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * @var int|string Date of request
	 */
	public $date_request;

	/**
	 * @var string Query text
	 */
	public $query_text;

	/**
	 * @var string Tool name
	 */
	public $tool_name;

	/**
	 * @var string Provider
	 */
	public $provider;

	/**
	 * @var float Execution time
	 */
	public $execution_time;

	/**
	 * @var float Confidence
	 */
	public $confidence;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var string Error message
	 */
	public $error_msg;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}
