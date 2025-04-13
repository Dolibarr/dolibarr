<?php
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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
 *	\file       htdocs/core/class/commonobject.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/doldeprecationhandler.class.php';

/**
 *	Parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 *
 * @phan-forbid-undeclared-magic-properties
 */
class EmailTemplate extends CommonObject
{
	const TRIGGER_PREFIX = 'EMAILTEMPLATE';
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'objectlink';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_email_templates';

	/**
	 * @var int is the template active or not
	 */
	public $active;

	/**
	 * @var null|string content of the template
	 */
	public $content;

	/**
	 * @var null|string lines of content of the template
	 */
	public $content_lines;

	/**
	 * @var null|int is the template a default or not
	 */
	public $defaultfortype;

	/**
	 * @var null|string sender email address
	 */
	public $email_from;

	/**
	 * @var null|string recipient email address
	 */
	public $email_to;

	/**
	 * @var null|string Additional visible recipients
	 */
	public $email_tocc;

	/**
	 * @var null|string additional hidden recipients
	 */
	public $email_tobcc;

	/**
	 * @var null|string are the template enabled or not
	 */
	public $enabled;

	/**
	 * @var null|int The template belongs to this user
	 */
	public $fk_user;

	/**
	 * @var null|string label of the template
	 */
	public $label;

	/**
	 * @var null|string language of the template
	 */
	public $lang;

	/**
	 * @var null|string files to join
	 */
	public $joinfiles;

	/**
	 * @var null|int position on the list of template
	 */
	public $position;

	/**
	 * @var int is the template private or not
	 */
	public $private;

	/**
	 * @var null|string topic of the template
	 */
	public $topic;

	/**
	 * @var null|string type of the template
	 */
	public $type_template;

	/**
	 * Constructor of the class
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Get email template from database.
	 *
	 *	@param      int			$id       	row Id of email template
	 *	@param      string		$label    	label of email template
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id, $label = '')
	{
		// Check parameters
		if (empty($id) && empty($label)) {
			return -1;
		}

		$sql = "SELECT e.rowid, e.entity, e.module, e.type_template, e.lang,";
        $sql .= " e.private, e.fk_user, e.datec, e.tms, e.label, e.position,";
        $sql .= " e.defaultfortype, e.enabled, e.active, e.email_from, e.email_to,";
        $sql .= " e.email_tocc, e.email_tobcc, e.topic, e.joinfiles, e.content,";
        $sql .= " e.content_lines FROM ".$this->db->prefix().$this->table_element." as e";
		if ($id) {
			$sql .= " WHERE e.rowid = ".((int) $id);
        }
		if ($label) {
			$sql .= " WHERE e.label = '".((string) $label)."'";
        }

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = (int) $obj->rowid;
				$this->entity = (int) $obj->entity;

                $this->active = (int) $obj->active;
                $this->content = (string) $obj->content;
                $this->content_lines = (string) $obj->content_lines;
                $this->datec = (int) $obj->datec;
                $this->defaultfortype = (int) $obj->defaultfortype;
                $this->email_from = (string) $obj->email_from;
                $this->email_to = (string) $obj->email_to;
                $this->email_tobcc = (string) $obj->email_tobcc;
                $this->email_tocc = (string) $obj->email_tocc;
                $this->enabled = (string) $obj->enabled;
                $this->fk_user = (int) $obj->fk_user;
                $this->joinfiles = (string) $obj->joinfiles;
                $this->label = (string) $obj->label;
                $this->lang = (string) $obj->lang;
                $this->module = (string) $obj->module;
                $this->position = (int) $obj->position;
                $this->private = (int) $obj->private;
                $this->tms = $obj->tms;
                $this->topic = (string) $obj->topic;
                $this->type_template = (string) $obj->type_template;

                // direct copy from facture.class.php
				$this->date_creation        = $this->db->jdate($obj->datec);
                
				return 1;
			} else {
				$this->error = 'Email template with id '.((string) $rowid).' not found sql='.$sql;
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}
}
