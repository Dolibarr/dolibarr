<?php
/* Copyright (C) 2013-2018 Jean-François Ferry <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
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
 *  \file       ticket/class/ticket.class.php
 *  \ingroup    ticket
 *  \brief      Class file for object ticket
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
//require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
//require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";


/**
 *    Class to manage ticket
 */
class Ticket extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'ticket';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'ticket';

    /**
     * @var string Name of field for link to tickets
     */
    public $fk_element='fk_ticket';

    /**
     * @var int  Does ticketcore support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Does ticketcore support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for ticketcore. Must be the part after the 'object_' into object_ticketcore.png
     */
    public $picto = 'ticket';


    /**
     * @var string Hash to identify ticket
     */
    public $track_id;

    /**
     * @var int Thirdparty ID
     */
    public $fk_soc;

    /**
     * @var int Project ID
     */
    public $fk_project;

    /**
     * @var string Person email who have create ticket
     */
    public $origin_email;

    /**
     * @var int User id who have create ticket
     */
    public $fk_user_create;

    /**
     * @var int User id who have ticket assigned
     */
    public $fk_user_assign;

    /**
     * var string Ticket subject
     */
    public $subject;

    /**
     * @var string Ticket message
     */
    public $message;

    /**
     * @var int  Ticket statut
     */
    public $fk_statut;

    /**
     * @var string State resolution
     */
    public $resolution;

    /**
     * @var int Progress in percent
     */
    public $progress;

    /**
     * @var int Duration for ticket
     */
    public $timing;

    /**
     * @var string Type code
     */
    public $type_code;

    /**
     * @var string Category code
     */
    public $category_code;

    /**
     * @var string Severity code
     */
    public $severity_code;

    /**
     * @var int Création date
     */
    public $datec = '';

    /**
     * @var int Read date
     */
    public $date_read = '';

    /**
     * @var int Close ticket date
     */
    public $date_close = '';

    /**
     * @var array cache_types_tickets
     */
    public $cache_types_tickets;

    /**
     * @var array tickets categories
     */
    public $cache_category_tickets;

    /**
     * @var int Notify tiers at create
     */
    public $notify_tiers_at_create;

    public $lines;

    /**
     * @var string Regex pour les images
     */
    public $regeximgext = '\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff';

    public $fields=array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'position'=>1, 'visible'=>-2, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id"),
    	'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>0, 'enabled'=>1, 'position'=>5, 'notnull'=>1, 'index'=>1),
    	'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'visible'=>1, 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object", 'css'=>''),
	    'track_id' => array('type'=>'varchar(255)', 'label'=>'TrackID', 'visible'=>0, 'enabled'=>1, 'position'=>11, 'notnull'=>-1, 'searchall'=>1, 'help'=>"Help text"),
	    'fk_user_create' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Author', 'visible'=>1, 'enabled'=>1, 'position'=>15, 'notnull'=>1, 'css'=>'nowraponall'),
    	'origin_email' => array('type'=>'mail', 'label'=>'OriginEmail', 'visible'=>1, 'enabled'=>1, 'position'=>16, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object"),
    	'subject' => array('type'=>'varchar(255)', 'label'=>'Subject', 'visible'=>1, 'enabled'=>1, 'position'=>18, 'notnull'=>-1, 'searchall'=>1, 'help'=>""),
    	'type_code' => array('type'=>'varchar(32)', 'label'=>'Type', 'visible'=>1, 'enabled'=>1, 'position'=>20, 'notnull'=>-1, 'searchall'=>1, 'help'=>"", 'css'=>'maxwidth100'),
    	'category_code' => array('type'=>'varchar(32)', 'label'=>'Category', 'visible'=>1, 'enabled'=>1, 'position'=>21, 'notnull'=>-1, 'searchall'=>1, 'help'=>"", 'css'=>'maxwidth100'),
	    'severity_code' => array('type'=>'varchar(32)', 'label'=>'Severity', 'visible'=>1, 'enabled'=>1, 'position'=>22, 'notnull'=>-1, 'searchall'=>1, 'help'=>"", 'css'=>'maxwidth100'),
    	'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'visible'=>1, 'enabled'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'help'=>"LinkToThirparty"),
	    'notify_tiers_at_create' => array('type'=>'integer', 'label'=>'NotifyThirdparty', 'visible'=>-2, 'enabled'=>0, 'position'=>51, 'notnull'=>1, 'index'=>1),
    	'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php', 'label'=>'Project', 'visible'=>1, 'enabled'=>1, 'position'=>52, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'help'=>"LinkToProject"),
        'fk_user_assign' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'AssignedTo', 'visible'=>1, 'enabled'=>1, 'position'=>510, 'notnull'=>1),
        'message' => array('type'=>'text', 'label'=>'Message', 'visible'=>-2, 'enabled'=>1, 'position'=>60, 'notnull'=>-1,),
        'progress' => array('type'=>'varchar(100)', 'label'=>'Progression', 'visible'=>1, 'enabled'=>1, 'position'=>41, 'notnull'=>-1, 'searchall'=>1, 'css'=>'right', 'help'=>""),
        'timing' => array('type'=>'varchar(20)', 'label'=>'Timing', 'visible'=>-1, 'enabled'=>1, 'position'=>42, 'notnull'=>-1, 'searchall'=>1, 'help'=>""),
        'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1),
        'date_read' => array('type'=>'datetime', 'label'=>'TicketReadOn', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1),
        'date_close' => array('type'=>'datetime', 'label'=>'TicketCloseOn', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1),
	    'resolution' => array('type'=>'integer', 'label'=>'Resolution', 'visible'=>-2, 'enabled'=>1, 'position'=>550, 'notnull'=>1),
    	'fk_statut' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>600, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array(0 => 'Unread', 1 => 'Read', 3 => 'Answered', 4 => 'Assigned', 5 => 'InProgress', 6 => 'Waiting', 8 => 'Closed', 9 => 'Deleted'))
    );

    /**
     * Status
     */
    const STATUS_NOT_READ = 0;
    const STATUS_READ = 1;
    const STATUS_ANSWERED = 3;
    const STATUS_ASSIGNED = 4;
    const STATUS_IN_PROGRESS = 5;
    const STATUS_WAITING = 6;
    const STATUS_CLOSED = 8;
    const STATUS_CANCELED = 9;


    /**
     *  Constructor
     *
     *  @param DoliDb $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->statuts_short = array(0 => 'Unread', 1 => 'Read', 3 => 'Answered', 4 => 'Assigned', 5 => 'InProgress', 6 => 'Waiting', 8 => 'Closed', 9 => 'Deleted');
        $this->statuts = array(0 => 'Unread', 1 => 'Read', 3 => 'Answered', 4 => 'Assigned', 5 => 'InProgress', 6 => 'Waiting', 8 => 'Closed', 9 => 'Deleted');
    }

    /**
     *    Check properties of ticket are ok (like ref, track_id, ...).
     *    All properties must be already loaded on object (this->ref, this->track_id, ...).
     *
     *    @return int        0 if OK, <0 if KO
     */
    private function verify()
    {
        $this->errors = array();

        $result = 0;

        // Clean parameters
        if (isset($this->ref)) {
            $this->ref = trim($this->ref);
        }

        if (isset($this->track_id)) {
            $this->track_id = trim($this->track_id);
        }

        if (isset($this->fk_soc)) {
            $this->fk_soc = trim($this->fk_soc);
        }

        if (isset($this->fk_project)) {
            $this->fk_project = trim($this->fk_project);
        }

        if (isset($this->origin_email)) {
            $this->origin_email = trim($this->origin_email);
        }

        if (isset($this->fk_user_create)) {
            $this->fk_user_create = trim($this->fk_user_create);
        }

        if (isset($this->fk_user_assign)) {
            $this->fk_user_assign = trim($this->fk_user_assign);
        }

        if (isset($this->subject)) {
            $this->subject = trim($this->subject);
        }

        if (isset($this->message)) {
            $this->message = trim($this->message);
        }

        if (isset($this->fk_statut)) {
            $this->fk_statut = trim($this->fk_statut);
        }

        if (isset($this->resolution)) {
            $this->resolution = trim($this->resolution);
        }

        if (isset($this->progress)) {
            $this->progress = trim($this->progress);
        }

        if (isset($this->timing)) {
            $this->timing = trim($this->timing);
        }

        if (isset($this->type_code)) {
            $this->type_code = trim($this->type_code);
        }

        if (isset($this->category_code)) {
            $this->category_code = trim($this->category_code);
        }

        if (isset($this->severity_code)) {
            $this->severity_code = trim($this->severity_code);
        }

        if (empty($this->ref)) {
            $this->errors[] = 'ErrorBadRef';
            dol_syslog(get_class($this) . "::create error -1 ref null", LOG_ERR);
            $result = -1;
        }

        return $result;
    }

    /**
     *  Create object into database
     *
     *  @param  User $user      User that creates
     *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     *  @return int                      <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->datec = dol_now();

        // Check more parameters
        // If error, this->errors[] is filled
        $result = $this->verify();
        if ($result >= 0) {
            // Insert request
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ticket(";
            $sql .= "ref,";
            $sql .= "track_id,";
            $sql .= "fk_soc,";
            $sql .= "fk_project,";
            $sql .= "origin_email,";
            $sql .= "fk_user_create,";
            $sql .= "fk_user_assign,";
            $sql .= "subject,";
            $sql .= "message,";
            $sql .= "fk_statut,";
            $sql .= "resolution,";
            $sql .= "progress,";
            $sql .= "timing,";
            $sql .= "type_code,";
            $sql .= "category_code,";
            $sql .= "severity_code,";
            $sql .= "datec,";
            $sql .= "date_read,";
            $sql .= "date_close,";
            $sql .= "entity,";
            $sql .= "notify_tiers_at_create";
            $sql .= ") VALUES (";
            $sql .= " " . (!isset($this->ref) ? '' : "'" . $this->db->escape($this->ref) . "'") . ",";
            $sql .= " " . (!isset($this->track_id) ? 'NULL' : "'" . $this->db->escape($this->track_id) . "'") . ",";
            $sql .= " " . ($this->fk_soc > 0 ? $this->db->escape($this->fk_soc) : "null") . ",";
            $sql .= " " . ($this->fk_project > 0 ? $this->db->escape($this->fk_project) : "null") . ",";
            $sql .= " " . (!isset($this->origin_email) ? 'NULL' : "'" . $this->db->escape($this->origin_email) . "'") . ",";
            $sql .= " " . ($this->fk_user_create > 0 ? $this->fk_user_create : ($user->id > 0 ? $user->id : 'NULL')) . ",";
            $sql .= " " . ($this->fk_user_assign > 0 ? $this->fk_user_assign : 'NULL') . ",";
            $sql .= " " . (!isset($this->subject) ? 'NULL' : "'" . $this->db->escape($this->subject) . "'") . ",";
            $sql .= " " . (!isset($this->message) ? 'NULL' : "'" . $this->db->escape($this->message) . "'") . ",";
            $sql .= " " . (!isset($this->fk_statut) ? '0' : "'" . $this->db->escape($this->fk_statut) . "'") . ",";
            $sql .= " " . (!isset($this->resolution) ? 'NULL' : "'" . $this->db->escape($this->resolution) . "'") . ",";
            $sql .= " " . (!isset($this->progress) ? '0' : "'" . $this->db->escape($this->progress) . "'") . ",";
            $sql .= " " . (!isset($this->timing) ? 'NULL' : "'" . $this->db->escape($this->timing) . "'") . ",";
            $sql .= " " . (!isset($this->type_code) ? 'NULL' : "'" . $this->db->escape($this->type_code) . "'") . ",";
            $sql .= " " . (!isset($this->category_code) ? 'NULL' : "'" . $this->db->escape($this->category_code) . "'") . ",";
            $sql .= " " . (!isset($this->severity_code) ? 'NULL' : "'" . $this->db->escape($this->severity_code) . "'") . ",";
            $sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'" . $this->db->idate($this->datec) . "'") . ",";
            $sql .= " " . (!isset($this->date_read) || dol_strlen($this->date_read) == 0 ? 'NULL' : "'" . $this->db->idate($this->date_read) . "'") . ",";
            $sql .= " " . (!isset($this->date_close) || dol_strlen($this->date_close) == 0 ? 'NULL' : "'" . $this->db->idate($this->date_close) . "'") . "";
            $sql .= ", " . $conf->entity;
            $sql .= ", " . (!isset($this->notify_tiers_at_create) ? '1' : "'" . $this->db->escape($this->notify_tiers_at_create) . "'");
            $sql .= ")";

            $this->db->begin();

            dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }

            if (!$error) {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "ticket");

                if (!$notrigger) {
                	// Call trigger
                	$result=$this->call_trigger('TICKET_CREATE', $user);
                	if ($result < 0) {
                        $error++;
                    }
                	// End call triggers
                }
            }

            //Update extrafield
            if (! $error) {
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                    $result = $this->insertExtraFields();
                    if ($result < 0) {
                        $error++;
                    }
                }
            }

            // Commit or rollback
            if ($error) {
                foreach ($this->errors as $errmsg) {
                    dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                    $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
                }
                $this->db->rollback();
                return -1 * $error;
            } else {
                $this->db->commit();
                return $this->id;
            }
        } else {
            $this->db->rollback();
            dol_syslog(get_class($this) . "::Create fails verify " . join(',', $this->errors), LOG_WARNING);
            return -3;
        }
    }

    /**
     *  Load object in memory from the database
     *
     *  @param  int        	$id    		Id object
     *  @param	string		$ref		Ref
     *  @param	string		$track_id	Track id, a hash like ref
     *  @return int              		<0 if KO, >0 if OK
     */
    public function fetch($id = '', $ref = '', $track_id = '')
    {
        global $langs;

        // Check parameters
        if (! $id && ! $track_id && ! $ref) {
            $this->error = 'ErrorWrongParameters';
            dol_print_error(get_class($this) . "::fetch " . $this->error);
            return -1;
        }

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.ref,";
        $sql .= " t.track_id,";
        $sql .= " t.fk_soc,";
        $sql .= " t.fk_project,";
        $sql .= " t.origin_email,";
        $sql .= " t.fk_user_create,";
        $sql .= " t.fk_user_assign,";
        $sql .= " t.subject,";
        $sql .= " t.message,";
        $sql .= " t.fk_statut,";
        $sql .= " t.resolution,";
        $sql .= " t.progress,";
        $sql .= " t.timing,";
        $sql .= " t.type_code,";
        $sql .= " t.category_code,";
        $sql .= " t.severity_code,";
        $sql .= " t.datec,";
        $sql .= " t.date_read,";
        $sql .= " t.date_close,";
        $sql .= " t.tms";
        $sql .= ", type.code as type_code, type.label as type_label, category.code as category_code, category.label as category_label, severity.code as severity_code, severity.label as severity_label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_type as type ON type.code=t.type_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_category as category ON category.code=t.category_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_severity as severity ON severity.code=t.severity_code";

        if ($id) {
            $sql .= " WHERE t.rowid = " . $this->db->escape($id);
        } else {
            $sql .= " WHERE t.entity IN (" . getEntity($this->element, 1) . ")";
            if ($track_id) {
                $sql .= " AND t.track_id = '" . $this->db->escape($track_id) . "'";
            } elseif ($ref) {
                $sql .= " AND t.ref = '" . $this->db->escape($ref) . "'";
            }
        }

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->track_id = $obj->track_id;
                $this->fk_soc = $obj->fk_soc;
                $this->socid = $obj->fk_soc; // for fetch_thirdparty() method
                $this->fk_project = $obj->fk_project;
                $this->origin_email = $obj->origin_email;
                $this->fk_user_create = $obj->fk_user_create;
                $this->fk_user_assign = $obj->fk_user_assign;
                $this->subject = $obj->subject;
                $this->message = $obj->message;
                $this->fk_statut = $obj->fk_statut;
                $this->resolution = $obj->resolution;
                $this->progress = $obj->progress;
                $this->timing = $obj->timing;

                $this->type_code = $obj->type_code;
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label_type = ($langs->trans("TicketTypeShort" . $obj->type_code) != ("TicketTypeShort" . $obj->type_code) ? $langs->trans("TicketTypeShort" . $obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
                $this->type_label = $label_type;

                $this->category_code = $obj->category_code;
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label_category = ($langs->trans("TicketCategoryShort" . $obj->category_code) != ("TicketCategoryShort" . $obj->category_code) ? $langs->trans("TicketCategoryShort" . $obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
                $this->category_label = $label_category;

                $this->severity_code = $obj->severity_code;
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label_severity = ($langs->trans("TicketSeverityShort" . $obj->severity_code) != ("TicketSeverityShort" . $obj->severity_code) ? $langs->trans("TicketSeverityShort" . $obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
                $this->severity_label = $label_severity;

                $this->datec = $this->db->jdate($obj->datec);
                $this->date_read = $this->db->jdate($obj->date_read);
                $this->date_close = $this->db->jdate($obj->date_close);
                $this->tms = $this->db->jdate($obj->tms);

                $this->fetch_optionals();

                $this->db->free($resql);
                return 1;
            }
			else
			{
            	return 0;
			}
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * Load all objects in memory from database
     *
     * @param  User   $user      User for action
     * @param  string $sortorder Sort order
     * @param  string $sortfield Sort field
     * @param  int    $limit     page number
     * @param  int    $offset    Offset for query
     * @param  int    $arch      archive or not (not used)
     * @param  array  $filter    Filter for query
     *                           output
     * @return int <0 if KO, >0 if OK
     */
    public function fetchAll($user, $sortorder = 'ASC', $sortfield = 't.datec', $limit = '', $offset = 0, $arch = '', $filter = '')
    {
        global $langs;

        $extrafields = new ExtraFields($this->db);

        // fetch optionals attributes and labels
        $extralabels = $extrafields->fetch_name_optionals_label($this->element);

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.ref,";
        $sql .= " t.track_id,";
        $sql .= " t.fk_soc,";
        $sql .= " t.fk_project,";
        $sql .= " t.origin_email,";
        $sql .= " t.fk_user_create, uc.lastname as user_create_lastname, uc.firstname as user_create_firstname,";
        $sql .= " t.fk_user_assign, ua.lastname as user_assign_lastname, ua.firstname as user_assign_firstname,";
        $sql .= " t.subject,";
        $sql .= " t.message,";
        $sql .= " t.fk_statut,";
        $sql .= " t.resolution,";
        $sql .= " t.progress,";
        $sql .= " t.timing,";
        $sql .= " t.type_code,";
        $sql .= " t.category_code,";
        $sql .= " t.severity_code,";
        $sql .= " t.datec,";
        $sql .= " t.date_read,";
        $sql .= " t.date_close,";
        $sql .= " t.tms";
        $sql .= ", type.label as type_label, category.label as category_label, severity.label as severity_label";
        // Add fields for extrafields
        foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
        	$sql .= ($extrafields->attributes[$this->table_element]['type'][$key] != 'separate' ? ",ef." . $key . ' as options_' . $key : '');
        }
        $sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_type as type ON type.code=t.type_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_category as category ON category.code=t.category_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_severity as severity ON severity.code=t.severity_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=t.fk_soc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as uc ON uc.rowid=t.fk_user_create";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON ua.rowid=t.fk_user_assign";
        if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "ticket_extrafields as ef on (t.rowid = ef.fk_object)";
        }
        if (!$user->rights->societe->client->voir && !$user->socid) {
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        }

        $sql .= " WHERE t.entity IN (" . getEntity('ticket') . ")";

        // Manage filter
        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
                    $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                } elseif (($key == 't.fk_user_assign') || ($key == 't.type_code') || ($key == 't.category_code') || ($key == 't.severity_code') || ($key == 't.fk_soc')) {
                    $sql .= " AND " . $key . " = '" . $this->db->escape($value) ."'";
                } elseif ($key == 't.fk_statut') {
                    if (is_array($value) && count($value) > 0) {
                        $sql .= 'AND ' . $key . ' IN (' . implode(',', $value) . ')';
                    } else {
                        $sql .= ' AND ' . $key . ' = ' . $this->db->escape($value);
                    }
                } else {
                    $sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
                }
            }
        }
        if (!$user->rights->societe->client->voir && !$user->socid) {
            $sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        } elseif ($user->socid) {
            $sql .= " AND t.fk_soc = " . $user->socid;
        }

        $sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
        if (!empty($limit)) {
            $sql .= ' ' . $this->db->plimit($limit + 1, $offset);
        }

        dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql) {
            $this->lines = array();

            $num = $this->db->num_rows($resql);
            $i = 0;

            if ($num) {
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);

                    $line = new TicketsLine();

                    $line->rowid = $obj->rowid;
                    $line->ref = $obj->ref;
                    $line->track_id = $obj->track_id;
                    $line->fk_soc = $obj->fk_soc;
                    $line->fk_project = $obj->fk_project;
                    $line->origin_email = $obj->origin_email;

                    $line->fk_user_create = $obj->fk_user_create;
                    $line->user_create_lastname = $obj->user_create_lastname;
                    $line->user_create_firstname = $obj->user_create_firstname;

                    $line->fk_user_assign = $obj->fk_user_assign;
                    $line->user_assign_lastname = $obj->user_assign_lastname;
                    $line->user_assign_firstname = $obj->user_assign_firstname;

                    $line->subject = $obj->subject;
                    $line->message = $obj->message;
                    $line->fk_statut = $obj->fk_statut;
                    $line->resolution = $obj->resolution;
                    $line->progress = $obj->progress;
                    $line->timing = $obj->timing;

                    // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                    $label_type = ($langs->trans("TicketTypeShort" . $obj->type_code) != ("TicketTypeShort" . $obj->type_code) ? $langs->trans("TicketTypeShort" . $obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
                    $line->type_label = $label_type;

                    $this->category_code = $obj->category_code;
                    // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                    $label_category = ($langs->trans("TicketCategoryShort" . $obj->category_code) != ("TicketCategoryShort" . $obj->category_code) ? $langs->trans("TicketCategoryShort" . $obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
                    $line->category_label = $label_category;

                    $this->severity_code = $obj->severity_code;
                    // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                    $label_severity = ($langs->trans("TicketSeverityShort" . $obj->severity_code) != ("TicketSeverityShort" . $obj->severity_code) ? $langs->trans("TicketSeverityShort" . $obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
                    $line->severity_label = $label_severity;

                    $line->datec = $this->db->jdate($obj->datec);
                    $line->date_read = $this->db->jdate($obj->date_read);
                    $line->date_close = $this->db->jdate($obj->date_close);

                    // Extra fields
                    if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
                    	foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
                            $tmpkey = 'options_' . $key;
                            $line->{$tmpkey} = $obj->$tmpkey;
                        }
                    }

                    $this->lines[$i] = $line;
                    $i++;
                }
            }
            $this->db->free($resql);
            return $num;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Update object into database
     *
     *  @param  User $user      User that modifies
     *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     *  @return int                     <0 if KO, >0 if OK
     */
    public function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs, $hookmanager;
        $error = 0;

        // Clean parameters
        if (isset($this->ref)) {
            $this->ref = trim($this->ref);
        }

        if (isset($this->track_id)) {
            $this->track_id = trim($this->track_id);
        }

        if (isset($this->fk_soc)) {
            $this->fk_soc = trim($this->fk_soc);
        }

        if (isset($this->fk_project)) {
            $this->fk_project = trim($this->fk_project);
        }

        if (isset($this->origin_email)) {
            $this->origin_email = trim($this->origin_email);
        }

        if (isset($this->fk_user_create)) {
            $this->fk_user_create = trim($this->fk_user_create);
        }

        if (isset($this->fk_user_assign)) {
            $this->fk_user_assign = trim($this->fk_user_assign);
        }

        if (isset($this->subject)) {
            $this->subject = trim($this->subject);
        }

        if (isset($this->message)) {
            $this->message = trim($this->message);
        }

        if (isset($this->fk_statut)) {
            $this->fk_statut = trim($this->fk_statut);
        }

        if (isset($this->resolution)) {
            $this->resolution = trim($this->resolution);
        }

        if (isset($this->progress)) {
            $this->progress = trim($this->progress);
        }

        if (isset($this->timing)) {
            $this->timing = trim($this->timing);
        }

        if (isset($this->type_code)) {
            $this->timing = trim($this->type_code);
        }

        if (isset($this->category_code)) {
            $this->timing = trim($this->category_code);
        }

        if (isset($this->severity_code)) {
            $this->timing = trim($this->severity_code);
        }

        // Check parameters
        // Put here code to add a control on parameters values
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "ticket SET";
        $sql .= " ref=" . (isset($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : "") . ",";
        $sql .= " track_id=" . (isset($this->track_id) ? "'" . $this->db->escape($this->track_id) . "'" : "null") . ",";
        $sql .= " fk_soc=" . (isset($this->fk_soc) ? "'" . $this->db->escape($this->fk_soc) . "'" : "null") . ",";
        $sql .= " fk_project=" . (isset($this->fk_project) ? "'" . $this->db->escape($this->fk_project) . "'" : "null") . ",";
        $sql .= " origin_email=" . (isset($this->origin_email) ? "'" . $this->db->escape($this->origin_email) . "'" : "null") . ",";
        $sql .= " fk_user_create=" . (isset($this->fk_user_create) ? $this->fk_user_create : "null") . ",";
        $sql .= " fk_user_assign=" . (isset($this->fk_user_assign) ? $this->fk_user_assign : "null") . ",";
        $sql .= " subject=" . (isset($this->subject) ? "'" . $this->db->escape($this->subject) . "'" : "null") . ",";
        $sql .= " message=" . (isset($this->message) ? "'" . $this->db->escape($this->message) . "'" : "null") . ",";
        $sql .= " fk_statut=" . (isset($this->fk_statut) ? $this->fk_statut : "null") . ",";
        $sql .= " resolution=" . (isset($this->resolution) ? $this->resolution : "null") . ",";
        $sql .= " progress=" . (isset($this->progress) ? "'" . $this->db->escape($this->progress) . "'" : "null") . ",";
        $sql .= " timing=" . (isset($this->timing) ? "'" . $this->db->escape($this->timing) . "'" : "null") . ",";
        $sql .= " type_code=" . (isset($this->type_code) ? "'" . $this->db->escape($this->type_code) . "'" : "null") . ",";
        $sql .= " category_code=" . (isset($this->category_code) ? "'" . $this->db->escape($this->category_code) . "'" : "null") . ",";
        $sql .= " severity_code=" . (isset($this->severity_code) ? "'" . $this->db->escape($this->severity_code) . "'" : "null") . ",";
        $sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . ",";
        $sql .= " date_read=" . (dol_strlen($this->date_read) != 0 ? "'" . $this->db->idate($this->date_read) . "'" : 'null') . ",";
        $sql .= " date_close=" . (dol_strlen($this->date_close) != 0 ? "'" . $this->db->idate($this->date_close) . "'" : 'null') . "";
        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (! $error) {
            // Update extrafields
            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }
        }

        if (! $error && ! $notrigger) {
        	// Call trigger
        	$result=$this->call_trigger('TICKET_MODIFY', $user);
        	if ($result < 0) {
                $error++;
            }
          	// End call triggers
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Delete object in database
     *
     *     @param  User $user      User that deletes
     *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     *  @return int                     <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('TICKET_DELETE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        if (!$error) {
            // Delete linked contacts
            $res = $this->delete_linked_contact();
            if ($res < 0) {
                dol_syslog(get_class($this) . "::delete error", LOG_ERR);
                $error++;
            }
        }

        if (!$error) {
            // Delete linked object
            $res = $this->deleteObjectLinked();
            if ($res < 0) $error++;
        }

        if (!$error) {
        	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ticket_msg";
        	$sql .= " WHERE fk_track_id = '" . $this->db->escape($this->track_id) . "'";
        	$resql = $this->db->query($sql);
        }

        // Removed extrafields
        if (!$error) {
        	$result = $this->deleteExtraFields();
        	if ($result < 0) {
        		$error++;
        		dol_syslog(get_class($this) . "::delete error -3 " . $this->error, LOG_ERR);
        	}
        }

        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ticket";
            $sql .= " WHERE rowid=" . $this->id;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *     Load an object from its id and create a new one in database
     *
     *     @param  int $fromid Id of object to clone
     *     @return int                    New id of clone
     */
    public function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new Ticket($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;

        // Clear fields
        // ...
        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result = $object->create($user);

        // Other options
        if ($result < 0) {
            $this->error = $object->error;
            $error++;
        }

        if (!$error) {
        }

        unset($object->context['createfromclone']);

        // End
        if (!$error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *     Initialise object with example values
     *     Id must be 0 if object instance is a specimen
     *
     *     @return void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;

        $this->ref = 'TI0501-001';
        $this->track_id = 'XXXXaaaa';
        $this->origin_email = 'email@email.com';
        $this->fk_project = '1';
        $this->fk_user_create = '1';
        $this->fk_user_assign = '1';
        $this->subject = 'Subject of ticket';
        $this->message = 'Message of ticket';
        $this->fk_statut = '0';
        $this->resolution = '1';
        $this->progress = '10';
        $this->timing = '30';
        $this->type_code = 'TYPECODE';
        $this->category_code = 'CATEGORYCODE';
        $this->severity_code = 'SEVERITYCODE';
        $this->datec = '';
        $this->date_read = '';
        $this->date_close = '';
        $this->tms = '';
    }

    /**
     * print selected status
     *
     * @param string    $selected   selected status
     * @return void
     */
    public function printSelectStatus($selected = "")
    {
        print Form::selectarray('search_fk_statut', $this->statuts_short, $selected, $show_empty = 1, $key_in_label = 0, $value_as_key = 0, $option = '', $translate = 1, $maxlen = 0, $disabled = 0, $sort = '', $morecss = '');
    }
    /**
     *      Charge dans cache la liste des types de tickets (paramétrable dans dictionnaire)
     *
     *      @return int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function loadCacheTypesTickets()
    {
        global $langs;

        if (! empty($this->cache_types_tickets) && count($this->cache_types_tickets)) {
            return 0;
        }
        // Cache deja charge

        $sql = "SELECT rowid, code, label, use_default, pos, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_ticket_type";
        $sql .= " WHERE active > 0";
        $sql .= " ORDER BY pos";
        dol_syslog(get_class($this) . "::load_cache_type_tickets sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label = ($langs->trans("TicketTypeShort" . $obj->code) != ("TicketTypeShort" . $obj->code) ? $langs->trans("TicketTypeShort" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
                $this->cache_types_tickets[$obj->rowid]['code'] = $obj->code;
                $this->cache_types_tickets[$obj->rowid]['label'] = $label;
                $this->cache_types_tickets[$obj->rowid]['use_default'] = $obj->use_default;
                $this->cache_types_tickets[$obj->rowid]['pos'] = $obj->pos;
                $i++;
            }
            return $num;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Charge dans cache la liste des catégories de tickets (paramétrable dans dictionnaire)
     *
     *      @return int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function loadCacheCategoriesTickets()
    {
        global $langs;

        if (! empty($this->cache_category_ticket) && count($this->cache_category_tickets)) {
            return 0;
        }
        // Cache deja charge

        $sql = "SELECT rowid, code, label, use_default, pos, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_ticket_category";
        $sql .= " WHERE active > 0";
        $sql .= " ORDER BY pos";
        dol_syslog(get_class($this) . "::load_cache_categories_tickets sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $this->cache_category_tickets[$obj->rowid]['code'] = $obj->code;
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label = ($langs->trans("TicketCategoryShort" . $obj->code) != ("TicketCategoryShort" . $obj->code) ? $langs->trans("TicketCategoryShort" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
                $this->cache_category_tickets[$obj->rowid]['label'] = $label;
                $this->cache_category_tickets[$obj->rowid]['use_default'] = $obj->use_default;
                $this->cache_category_tickets[$obj->rowid]['pos'] = $obj->pos;
                $i++;
            }
            return $num;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Charge dans cache la liste des sévérité de tickets (paramétrable dans dictionnaire)
     *
     *      @return int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function loadCacheSeveritiesTickets()
    {
        global $langs;

        if (! empty($this->cache_severity_tickets) && count($this->cache_severity_tickets)) {
            return 0;
        }
        // Cache deja charge

        $sql = "SELECT rowid, code, label, use_default, pos, description";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_ticket_severity";
        $sql .= " WHERE active > 0";
        $sql .= " ORDER BY pos";
        dol_syslog(get_class($this) . "::loadCacheSeveritiesTickets sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $this->cache_severity_tickets[$obj->rowid]['code'] = $obj->code;
                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label = ($langs->trans("TicketSeverityShort" . $obj->code) != ("TicketSeverityShort" . $obj->code) ? $langs->trans("TicketSeverityShort" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
                $this->cache_severity_tickets[$obj->rowid]['label'] = $label;
                $this->cache_severity_tickets[$obj->rowid]['use_default'] = $obj->use_default;
                $this->cache_severity_tickets[$obj->rowid]['pos'] = $obj->pos;
                $i++;
            }
            return $num;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * Return status label of object
     *
     * @param      int		$mode     0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * @return     string    			  Label
     */
    public function getLibStatut($mode = 0)
    {
        return $this->libStatut($this->fk_statut, $mode);
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *    Return status label of object
     *
     *    @param      string 	$statut      id statut
     *    @param      int		$mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *    @return     string     			 Label
     */
    function LibStatut($statut, $mode = 0)
    {
        // phpcs:enable
        global $langs;

        if ($mode == 0) {
            return $langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 1) {
            return $langs->trans($this->statuts_short[$statut]);
        }
        elseif ($mode == 2) {
            if ($statut == 0) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 1) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut1.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 3) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut3.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 4) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 5) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut5.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 6) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 8) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut8.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 9) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut9.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }
        }
        elseif ($mode == 3) {
            if ($statut == 0) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0.png@ticket');
            }

            elseif ($statut == 1) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut1.png@ticket');
            }

            elseif ($statut == 3) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut3.png@ticket');
            }

            elseif ($statut == 4) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4.png@ticket');
            }

            elseif ($statut == 5) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut5.png@ticket');
            }

            elseif ($statut == 6) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6.png@ticket');
            }

            elseif ($statut == 8) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut8.png@ticket');
            }

            elseif ($statut == 9) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut9.png@ticket');
            }
        }
        elseif ($mode == 4) {
            if ($statut == 0) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 1) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut1.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 3) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut3.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 4) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 5) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut5.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 6) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 8) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut8.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }

            elseif ($statut == 9) {
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut9.png@ticket') . ' ' . $langs->trans($this->statuts_short[$statut]);
            }
        }
        elseif ($mode == 5) {
            if ($statut == 0) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut0.png@ticket');
            }

            elseif ($statut == 1) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut1.png@ticket');
            }

            elseif ($statut == 3) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut3.png@ticket');
            }

            elseif ($statut == 4) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut4.png@ticket');
            }

            elseif ($statut == 5) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut5.png@ticket');
            }

            elseif ($statut == 6) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut6.png@ticket');
            }

            elseif ($statut == 8) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut8.png@ticket');
            }

            elseif ($statut == 9) {
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut9.png@ticket');
            }
        }
    }


    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *	@param	string	$option						On what the link point to ('nolink', ...)
     *  @param	int  	$notooltip					1=Disable tooltip
     *  @param  string  $morecss            		Add more css on link
     *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *	@return	string								String with URL
     */
    function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $save_lastsearch_value=-1)
    {
    	global $db, $conf, $langs;
    	global $dolibarr_main_authentication, $dolibarr_main_demo;
    	global $menumanager;

    	if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

    	$result = '';
    	$companylink = '';

    	$label = '<u>' . $langs->trans("ShowTicket") . '</u>';
    	$label.= '<br>';
    	$label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref.'<br>';
    	$label.= '<b>' . $langs->trans('TicketTrackId') . ':</b> ' . $this->track_id.'<br>';
    	$label.= '<b>' . $langs->trans('Subject') . ':</b> ' . $this->subject;

    	$url = dol_buildpath('/ticket/card.php',1).'?id='.$this->id;

    	if ($option != 'nolink')
    	{
    		// Add param to save lastsearch_values or not
    		$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
    		if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
    		if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
    	}

    	$linkclose='';
    	if (empty($notooltip))
    	{
    		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
    		{
    			$label=$langs->trans("ShowTicket");
    			$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
    		}
    		$linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
    		$linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
    	}
    	else $linkclose = ($morecss?' class="'.$morecss.'"':'');

    	$linkstart = '<a href="'.$url.'"';
    	$linkstart.=$linkclose.'>';
    	$linkend='</a>';

    	$result .= $linkstart;
    	if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
    	if ($withpicto != 2) $result.= $this->ref;
    	$result .= $linkend;
    	//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

    	return $result;
    }


    /**
     *    Mark a message as read
     *
     *    @param    User		$user			    Object user
     *    @param	  int			$notrigger		No trigger
     *    @return   int							      <0 if KO, >0 if OK
     */
    public function markAsRead($user, $notrigger = 0)
    {
        global $conf, $langs;

        if ($this->statut != 9) { // no closed
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "ticket";
            $sql .= " SET fk_statut = 1, date_read='" . $this->db->idate(dol_now()) . "'";
            $sql .= " WHERE rowid = " . $this->id;

            dol_syslog(get_class($this) . "::markAsRead sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql) {
            	if (!$error && !$notrigger) {
            		// Call trigger
            		$result=$this->call_trigger('TICKET_MARK_READ', $user);
            		if ($result < 0) {
                        $error++;
                    }
            		// End call triggers
            	}


                if (!$error) {
                    $this->db->commit();
                    return 1;
                } else {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this) . "::markAsRead " . $this->error, LOG_ERR);
                    return -1;
                }
            } else {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this) . "::markAsRead " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

	/**
	 *    Mark a message as read
	 *
	 *    @param    User	$user				Object user
	 *    @param    int 	$id_assign_user		ID of user assigned
	 *    @param    int 	$notrigger        	Disable trigger
	 *    @return   int							<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function assignUser($user, $id_assign_user, $notrigger = 0)
	{
		global $conf, $langs;

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . "ticket";
		if ($id_assign_user > 0)
		{
			$sql .= " SET fk_user_assign=".$id_assign_user.", fk_statut=4";
		}
		else
		{
			$sql .= " SET fk_user_assign=null, fk_statut=1";
		}
		$sql .= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this) . "::assignUser sql=" . $sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->fk_user_assign = $id_assign_user; // May be used by trigger

			if (! $notrigger) {
				// Call trigger
				$result = $this->call_trigger('TICKET_ASSIGNED', $user);
				if ($result < 0) {
					$error ++;
				}
				// End call triggers
			}

			if (! $error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->error = join(',', $this->errors);
				dol_syslog(get_class($this) . "::assignUser " . $this->error, LOG_ERR);
				return - 1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::assignUser " . $this->error, LOG_ERR);
			return - 1;
		}
	}

    /**
     *   Create log for the ticket
     *         1- create entry into database for message storage
     *         2- if trigger, send an email to ticket contacts
     *
     *   @param  User   $user    	User that create
     *   @param  string $message 	Log message
     *   @param  int    $noemail 	0=send email after, 1=disable emails
     *   @return int             	<0 if KO, >0 if OK
     */
    public function createTicketLog(User $user, $message, $noemail = 0)
    {
        global $conf, $langs;

        $error = 0;

        $this->db->begin();

        // Clean parameters
        $this->message = trim($this->message);

        // Check parameters
        if (!$message) {
            $this->error = 'ErrorBadValueForParameter';
            return -1;
        }

        // TODO Should call the trigger TICKET_MODIFY with $this->context with all data to record event
        // so the event is stored by the agenda/event trigger

        if (!$error) {
        	$this->db->commit();

        	if ($conf->global->TICKET_ACTIVATE_LOG_BY_EMAIL && !$noemail) {
        		$this->sendLogByEmail($user, $message);
        	}

        	return 1;
        }
        else
        {
        	$this->db->rollback();

        	return -1;
        }
    }

    /**
     *  Send notification of changes by email
     *
     * 	@param  User   $user    		User that create
     * 	@param  string $message 		Log message
     * 	@return int                 	<0 if KO, >0 if OK (number of emails sent)
     */
    private function sendLogByEmail($user, $message)
    {
        global $conf, $langs;

        $nb_sent = 0;

        $langs->load('ticket');

        // Retrieve email of all contacts (internal and external)
        $contacts = $this->listeContact(-1, 'internal');
        $contacts = array_merge($contacts, $this->listeContact(-1, 'external'));

        /* If origin_email and no socid, we add email to the list * */
        if (!empty($this->origin_email) && empty($this->fk_soc)) {
            $array_ext = array(array('firstname' => '', 'lastname' => '', 'email' => $this->origin_email, 'libelle' => $langs->transnoentities('TicketEmailOriginIssuer'), 'socid' => "-1"));
            $contacts = array_merge($contacts, $array_ext);
        }

        if (!empty($this->fk_soc)) {
            $this->fetch_thirdparty($this->fk_soc);
            $array_company = array(array('firstname' => '', 'lastname' => $this->client->name, 'email' => $this->client->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $this->client->id));
            $contacts = array_merge($contacts, $array_company);
        }

        // foreach contact send email with notification message
        if (count($contacts) > 0) {
            foreach ($contacts as $key => $info_sendto) {
                $message = '';
                $subject = '[' . $conf->global->MAIN_INFO_SOCIETE_NOM . '] ' . $langs->transnoentities('TicketNotificationEmailSubject', $this->track_id);
                $message .= $langs->transnoentities('TicketNotificationEmailBody', $this->track_id) . "\n\n";
                $message .= $langs->transnoentities('Title') . ' : ' . $this->subject . "\n";

                $recipient_name = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1');
                $recipient = (!empty($recipient_name) ? $recipient_name : $info_sendto['email']) . ' (' . strtolower($info_sendto['libelle']) . ')';
                $message .= $langs->transnoentities('TicketNotificationRecipient') . ' : ' . $recipient . "\n";
                $message .= "\n";
                $message .= '* ' . $langs->transnoentities('TicketNotificationLogMessage') . ' *' . "\n";
                $message .= dol_html_entity_decode($log_message, ENT_QUOTES) . "\n";

                if ($info_sendto['source'] == 'internal') {
                    $url_internal_ticket = dol_buildpath('/ticket/card.php', 2) . '?track_id=' . $this->track_id;
                    $message .= "\n" . $langs->transnoentities('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : ' . '<a href="' . $url_internal_ticket . '">' . $this->track_id . '</a>' . "\n";
                } else {
                    $url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE . '/' : dol_buildpath('/public/ticket/view.php', 2)) . '?track_id=' . $this->track_id;
                    $message .= "\n" . $langs->transnoentities('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : ' . '<a href="' . $url_public_ticket . '">' . $this->track_id . '</a>' . "\n";
                }

                $message .= "\n";
                $message .= $langs->transnoentities('TicketEmailPleaseDoNotReplyToThisEmail') . "\n";

                $from = $conf->global->MAIN_INFO_SOCIETE_NOM . '<' . $conf->global->TICKET_NOTIFICATION_EMAIL_FROM . '>';
                $replyto = $from;

                // Init to avoid errors
                $filepath = array();
                $filename = array();
                $mimetype = array();

                $message = dol_nl2br($message);

                if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                }
                include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject, $info_sendto['email'], $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, 0);
                if ($mailfile->error || $mailfile->errors) {
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                    if ($result > 0) {
                        $nb_sent++;
                    }
                }
                if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                }
            }

            setEventMessages($langs->trans('TicketNotificationNumberEmailSent', $nb_sent), null, 'mesgs');
        }

        return $nb_sent;
    }

    /**
     *      Charge la liste des actions sur le ticket
     *
     *      @return int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function loadCacheLogsTicket()
    {
        global $langs;

        if (is_array($this->cache_logs_ticket) && count($this->cache_logs_ticket)) {
            return 0;
        }
        // Cache deja charge

        // TODO Read the table llx_actioncomm
        /*
        $sql = "SELECT rowid, fk_user_create, datec, message";
        $sql .= " FROM " . MAIN_DB_PREFIX . "ticket_logs";
        $sql .= " WHERE fk_track_id ='" . $this->db->escape($this->track_id) . "'";
        $sql .= " ORDER BY datec DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $this->cache_logs_ticket[$i]['id'] = $obj->rowid;
                $this->cache_logs_ticket[$i]['fk_user_create'] = $obj->fk_user_create;
                $this->cache_logs_ticket[$i]['datec'] = $this->db->jdate($obj->datec);
                $this->cache_logs_ticket[$i]['message'] = $obj->message;
                $i++;
            }
            return $num;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::loadCacheLogsTicket " . $this->error, LOG_ERR);
            return -1;
        }*/

        return 0;
    }

    /**
     *  Add message into database
     *
     *  @param  User $user      	User that creates
     *  @param  int  $notrigger 	0=launch triggers after, 1=disable triggers
     *  @return int                 <0 if KO, Id of created object if OK
     */
    public function createTicketMessage($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters
        if (isset($this->fk_track_id)) {
            $this->fk_track_id = trim($this->fk_track_id);
        }

        if (isset($this->message)) {
            $this->message = trim($this->message);
        }

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ticket_msg(";

        $sql .= "fk_track_id,";
        $sql .= "fk_user_action,";
        $sql .= "datec,";
        $sql .= "message,";
        $sql .= "private";
        $sql .= ") VALUES (";
        $sql .= " " . (!isset($this->fk_track_id) ? "'" . $this->db->escape($this->track_id) . "'" : "'" . $this->db->escape($this->fk_track_id) . "'") . ",";
        $sql .= " " . ($this->fk_user_action > 0 ? $this->fk_user_action : $user->id) . ",";
        $sql .= " '" . $this->db->idate(dol_now()) . "',";
        $sql .= " " . (!isset($this->message) ? 'NULL' : "'" . $this->db->escape($this->message) . "'") . ",";
        $sql .= " " . (empty($this->private) ? '0' : "'" . $this->db->escape($this->private) . "'") . "";
        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create_ticket_message sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create_ticket_message " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *      Charge la liste des messages sur le ticket
     *
     *      @return int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    public function loadCacheMsgsTicket()
    {
        global $langs;

        if (is_array($this->cache_msgs_ticket) && count($this->cache_msgs_ticket)) {
            return 0;
        }
        // Cache deja charge

        $sql = "SELECT rowid, fk_user_action, datec, message, private";
        $sql .= " FROM " . MAIN_DB_PREFIX . "ticket_msg";
        $sql .= " WHERE fk_track_id ='" . $this->db->escape($this->track_id) . "'";
        $sql .= " ORDER BY datec DESC";
        dol_syslog(get_class($this) . "::load_cache_actions_ticket sql=" . $sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $this->cache_msgs_ticket[$i]['id'] = $obj->rowid;
                $this->cache_msgs_ticket[$i]['fk_user_action'] = $obj->fk_user_action;
                $this->cache_msgs_ticket[$i]['datec'] = $this->db->jdate($obj->datec);
                $this->cache_msgs_ticket[$i]['message'] = $obj->message;
                $this->cache_msgs_ticket[$i]['private'] = $obj->private;
                $i++;
            }
            return $num;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::load_cache_actions_ticket " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *    Close a ticket
     *
     *    @return     int		<0 if KO, >0 if OK
     */
    public function close()
    {
        global $conf, $user, $langs;

        if ($this->fk_statut != 9) { // not closed
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "ticket";
            $sql .= " SET fk_statut=8, progress=100, date_close='" . $this->db->idate(dol_now()) . "'";
            $sql .= " WHERE rowid = " . $this->id;

            dol_syslog(get_class($this) . "::close sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql) {
                $error = 0;

                // Valid and close fichinter linked
                $this->fetchObjectLinked($this->id, $this->element, null, 'fichinter');
                if ($this->linkedObjectsIds)
                {
	                foreach ($this->linkedObjectsIds['fichinter'] as $fichinter_id) {
	                    $fichinter = new Fichinter($this->db);
	                    $fichinter->fetch($fichinter_id);
	                    if($fichinter->statut == 0) {
	                        $result = $fichinter->setValid($user);
	                        if (!$result) {
	                            $this->errors[] = $fichinter->error;
	                            $error++;
	                        }
	                    }
	                    if ($fichinter->statut < 3) {
	                        $result = $fichinter->setStatut(3);
	                        if (!$result) {
	                            $this->errors[] = $fichinter->error;
	                            $error++;
	                        }
	                    }
	                }
                }

            	// Call trigger
            	$result=$this->call_trigger('TICKET_CLOSE', $user);
            	if ($result < 0) {
                    $error++;
                }
            	// End call triggers

                if (!$error) {
                    $this->db->commit();
                    return 1;
                } else {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this) . "::close " . $this->error, LOG_ERR);
                    return -1;
                }
            } else {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this) . "::close " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     *     Search and fetch thirparties by email
     *
     *     @param  string $email   		Email
     *     @param  int    $type    		Type of thirdparties (0=any, 1=customer, 2=prospect, 3=supplier)
     *     @param  array  $filters 		Array of couple field name/value to filter the companies with the same name
     *     @param  string $clause  		Clause for filters
     *     @return array        		Array of thirdparties object
     */
    public function searchSocidByEmail($email, $type = '0', $filters = array(), $clause = 'AND')
    {
        $thirdparties = array();

        // Generation requete recherche
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe";
        $sql .= " WHERE entity IN (" . getEntity('ticket', 1) . ")";
        if (!empty($type)) {
            if ($type == 1 || $type == 2) {
                $sql .= " AND client = " . $type;
            } elseif ($type == 3) {
                $sql .= " AND fournisseur = 1";
            }
        }
        if (!empty($email)) {
            if (!$exact) {
                if (preg_match('/^([\*])?[^*]+([\*])?$/', $email, $regs) && count($regs) > 1) {
                    $email = str_replace('*', '%', $email);
                } else {
                    $email = '%' . $email . '%';
                }
            }
            $sql .= " AND ";
            if (is_array($filters) && !empty($filters)) {
                $sql .= "(";
            }

            if (!$case) {
                $sql .= "email LIKE '" . $this->db->escape($email) . "'";
            } else {
                $sql .= "email LIKE BINARY '" . $this->db->escape($email) . "'";
            }
        }
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $field => $value) {
                $sql .= " " . $clause . " " . $field . " LIKE BINARY '" . $this->db->escape($value) . "'";
            }
            if (!empty($email)) {
                $sql .= ")";
            }
        }

        $res = $this->db->query($sql);
        if ($res) {
            while ($rec = $this->db->fetch_array($res)) {
                $soc = new Societe($this->db);
                $soc->fetch($rec['rowid']);
                $thirdparties[] = $soc;
            }

            return $thirdparties;
        } else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            dol_syslog(get_class($this) . "::searchSocidByEmail " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *     Search and fetch contacts by email
     *
     *     @param  string $email 	Email
     *     @param  array  $socid 	Limit to a thirdparty
     *     @param  string $case  	Respect case
     *     @return array        	Array of contacts object
     */
    public function searchContactByEmail($email, $socid = '', $case = '')
    {
        $contacts = array();

        // Generation requete recherche
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "socpeople";
        $sql .= " WHERE entity IN (" . getEntity('socpeople') . ")";
        if (!empty($socid)) {
            $sql .= " AND fk_soc='" . $this->db->escape($socid) . "'";
        }

        if (!empty($email)) {
            $sql .= " AND ";

            if (!$case) {
                $sql .= "email LIKE '" . $this->db->escape($email) . "'";
            } else {
                $sql .= "email LIKE BINARY '" . $this->db->escape($email) . "'";
            }
        }

        $res = $this->db->query($sql);
        if ($res) {
            while ($rec = $this->db->fetch_array($res)) {
                include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $contactstatic = new Contact($this->db);
                $contactstatic->fetch($rec['rowid']);
                $contacts[] = $contactstatic;
            }

            return $contacts;
        } else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            dol_syslog(get_class($this) . "::searchContactByEmail " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *    Define parent commany of current ticket
     *
     *    @param  int $id Id of thirdparty to set or '' to remove
     *    @return int             <0 if KO, >0 if OK
     */
    public function setCustomer($id)
    {
        if ($this->id) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ticket";
            $sql .= " SET fk_soc = " . ($id > 0 ? $id : "null");
            $sql .= " WHERE rowid = " . $this->id;
            dol_syslog(get_class($this) . '::setCustomer sql=' . $sql);
            $resql = $this->db->query($sql);
            if ($resql) {
                return 1;
            } else {
                return -1;
            }
        } else {
            return -1;
        }
    }

    /**
     *    Define progression of current ticket
     *
     *    @param  int $percent Progression percent
     *    @return int             <0 if KO, >0 if OK
     */
    public function setProgression($percent)
    {
        if ($this->id) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "ticket";
            $sql .= " SET progress = " . ($percent > 0 ? $percent : "null");
            $sql .= " WHERE rowid = " . $this->id;
            dol_syslog(get_class($this) . '::set_progression sql=' . $sql);
            $resql = $this->db->query($sql);
            if ($resql) {
                return 1;
            } else {
                return -1;
            }
        } else {
            return -1;
        }
    }

    /**
     *     Link element with a project
     * 	   Override core function because of key name 'fk_project' used for this module
     *
     *     @param  int $projectid Project id to link element to
     *     @return int                        <0 if KO, >0 if OK
     */
    public function setProject($projectid)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this) . "::setProject was called on objet with property table_element not defined", LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element;
        if ($projectid) {
            $sql .= ' SET fk_project = ' . $projectid;
        } else {
            $sql .= ' SET fk_project = NULL';
        }

        $sql .= ' WHERE rowid = ' . $this->id;

        dol_syslog(get_class($this) . "::setProject sql=" . $sql);
        if ($this->db->query($sql)) {
            $this->fk_project = $projectid;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *     Link element with a contract
     *
     *     @param  int $contractid Contract id to link element to
     *     @return int                        <0 if KO, >0 if OK
     */
    public function setContract($contractid)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this) . "::setContract was called on objet with property table_element not defined", LOG_ERR);
            return -1;
        }

        $result = $this->add_object_linked('contrat', $contractid);
        if ($result) {
            $this->fk_contract = $contractid;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /* gestion des contacts d'un ticket */

    /**
     *  Return id des contacts interne de suivi
     *
     *  @return array       Liste des id contacts suivi ticket
     */
    public function getIdTicketInternalContact()
    {
        return $this->getIdContact('internal', 'SUPPORTTEC');
    }

    /**
     * Retrieve informations about internal contacts
     *
     *  @return array       Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
     */
    public function getInfosTicketInternalContact()
    {
        return $this->listeContact(-1, 'internal');
    }

    /**
     *  Return id des contacts clients pour le suivi ticket
     *
     *  @return array       Liste des id contacts suivi ticket
     */
    public function getIdTicketCustomerContact()
    {
        return $this->getIdContact('external', 'SUPPORTCLI');
    }

    /**
     * Retrieve informations about external contacts
     *
     *  @return array       Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
     */
    public function getInfosTicketExternalContact()
    {
        return $this->listeContact(-1, 'external');
    }

    /**
     *  Return id des contacts clients des intervenants
     *
     *  @return array       Liste des id contacts intervenants
     */
    public function getIdTicketInternalInvolvedContact()
    {
        return $this->getIdContact('internal', 'CONTRIBUTOR');
    }

    /**
     *  Return id des contacts clients des intervenants
     *
     *  @return array       Liste des id contacts intervenants
     */
    public function getIdTicketCustomerInvolvedContact()
    {
        return $this->getIdContact('external', 'CONTRIBUTOR');
    }

    /**
     * Return id of all contacts for ticket
     *
     * @return	array		Array of contacts for tickets
     */
    public function getTicketAllContacts()
    {
        $array_contact = array();

        $array_contact = $this->getIdTicketInternalContact($exclude_self);

        $array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact($exclude_self));

        $array_contact = array_merge($array_contact, $this->getIdTicketInternalInvolvedContact($exclude_self));
        $array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact($exclude_self));

        return $array_contact;
    }

    /**
     * Return id of all contacts for ticket
     *
     * @return	array		Array of contacts
     */
    public function getTicketAllCustomerContacts()
    {
        $array_contact = array();

        $array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact($exclude_self));
        $array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact($exclude_self));

        return $array_contact;
    }

    /**
     * Send message
     *
     *  @param  string $subject	  Subject
     *  @param  string $texte      Message to send
     *  @return int                <0 if KO, or number of changes if OK
     */
    public function messageSend($subject, $texte)
    {
        global $conf, $langs, $mysoc, $dolibarr_main_url_root;

        $langs->load("other");

        dol_syslog(get_class($this) . "::message_send action=$action, socid=$socid, texte=$texte, objet_type=$objet_type, objet_id=$objet_id, file=$file");

        $internal_contacts = $this->getIdContact('internal', 'SUPPORTTEC');
        $external_contacts = $this->getIdContact('external', 'SUPPORTTEC');

        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num) { // For each notification couple defined (third party/actioncode)
                $obj = $this->db->fetch_object($result);

                $sendto = $obj->firstname . " " . $obj->lastname . " <" . $obj->email . ">";
                $actiondefid = $obj->adid;

                if (dol_strlen($sendto))
                {
                    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                    $application = ($conf->global->MAIN_APPLICATION_TITLE ? $conf->global->MAIN_APPLICATION_TITLE : 'Dolibarr ERP/CRM');

                    $subject = '[' . $application . '] ' . $langs->transnoentitiesnoconv("DolibarrNotification");

                    $message = $langs->transnoentities("YouReceiveMailBecauseOfNotification", $application, $mysoc->name) . "\n";
                    $message .= $langs->transnoentities("YouReceiveMailBecauseOfNotification2", $application, $mysoc->name) . "\n";
                    $message .= "\n";
                    $message .= $texte;
                    // Add link
                    $link = '';
                    switch ($objet_type) {
                        case 'ficheinter':
                            $link = '/fichinter/card.php?id=' . $objet_id;
                            break;
                        case 'propal':
                            $link = '/comm/propal.php?id=' . $objet_id;
                            break;
                        case 'facture':
                            $link = '/compta/facture/card.php?facid=' . $objet_id;
                            break;
                        case 'order':
                            $link = '/commande/card.php?facid=' . $objet_id;
                            break;
                        case 'order_supplier':
                            $link = '/fourn/commande/card.php?facid=' . $objet_id;
                            break;
                    }
                    // Define $urlwithroot
                    $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
                    $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file
                    //$urlwithroot=DOL_MAIN_URL_ROOT;                        // This is to use same domain name than current
                    if ($link) {
                        $message .= "\n" . $urlwithroot . $link;
                    }

                    $filename = basename($file);

                    $mimefile = dol_mimetype($file);

                    $msgishtml = 0;

                    $replyto = $conf->notification->email_from;

                    $message = dol_nl2br($message);

                    if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                        $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                        $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                    }
                    $mailfile = new CMailFile(
                        $subject,
                        $sendto,
                        $replyto,
                        $message,
                        array($file),
                        array($mimefile),
                        array($filename[count($filename) - 1]),
                        '',
                        '',
                        0,
                        $msgishtml
                    );

                    if ($mailfile->sendfile()) {
                        $now = dol_now();
                        $sendto = htmlentities($sendto);

                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "notify (daten, fk_action, fk_contact, objet_type, objet_id, email)";
                        $sql .= " VALUES ('" . $this->db->idate($now) . "', " . $actiondefid . ", " . $obj->cid . ", '" . $this->db->escape($objet_type) . "', " . $objet_id . ", '" . $this->db->escape($obj->email) . "')";
                        dol_syslog("Notify::send sql=" . $sql);
                        if (!$this->db->query($sql)) {
                            dol_print_error($this->db);
                        }
                    } else {
                        $this->error = $mailfile->error;
                        //dol_syslog("Notify::send ".$this->error, LOG_ERR);
                    }
                    if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                        $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                    }
                }
                $i++;
            }
            return $i;
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     *    Get array of all contacts for a ticket
     *    Override method of file commonobject.class.php to add phone number
     *
     *    @param	int    	$statut 	Status of lines to get (-1=all)
     *    @param	string 	$source 	Source of contact: external or thirdparty (llx_socpeople) or internal (llx_user)
     *    @param	int    	$list   	0:Return array contains all properties, 1:Return array contains just id
     *    @param    string  $code       Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
     *    @return 	array          		Array of contacts
     */
    function listeContact($statut = -1, $source = 'external', $list = 0, $code = '')
    {
        global $langs;

        $tab = array();

        $sql = "SELECT ec.rowid, ec.statut  as statuslink, ec.fk_socpeople as id, ec.fk_c_type_contact"; // This field contains id of llx_socpeople or id of llx_user
        if ($source == 'internal') {
            $sql .= ", '-1' as socid, t.statut as statuscontact";
        }

        if ($source == 'external' || $source == 'thirdparty') {
            $sql .= ", t.fk_soc as socid, t.statut as statuscontact";
        }

        $sql .= ", t.civility, t.lastname as lastname, t.firstname, t.email";
        if ($source == 'internal') {
            $sql .= ", t.office_phone as phone, t.user_mobile as phone_mobile";
        }

        if ($source == 'external') {
            $sql .= ", t.phone as phone, t.phone_mobile as phone_mobile, t.phone_perso as phone_perso";
        }

        $sql .= ", tc.source, tc.element, tc.code, tc.libelle";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact tc";
        $sql .= ", " . MAIN_DB_PREFIX . "element_contact ec";
        if ($source == 'internal') {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user t on ec.fk_socpeople = t.rowid";
        }

        if ($source == 'external' || $source == 'thirdparty') {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople t on ec.fk_socpeople = t.rowid";
        }

        $sql .= " WHERE ec.element_id =" . $this->id;
        $sql .= " AND ec.fk_c_type_contact=tc.rowid";
        $sql .= " AND tc.element='" . $this->db->escape($this->element) . "'";
        if ($source == 'internal') {
            $sql .= " AND tc.source = 'internal'";
        }

        if ($source == 'external' || $source == 'thirdparty') {
            $sql .= " AND tc.source = 'external'";
        }

        $sql .= " AND tc.active=1";
        if ($statut >= 0) {
            $sql .= " AND ec.statut = '" . $statut . "'";
        }

        $sql .= " ORDER BY t.lastname ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                if (!$list) {
                    $transkey = "TypeContact_" . $obj->element . "_" . $obj->source . "_" . $obj->code;
                    $libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->libelle);
                    $tab[$i] = array(
                    		'source' => $obj->source,
                    		'socid' => $obj->socid,
                    		'id' => $obj->id,
                        	'nom' => $obj->lastname, // For backward compatibility
                        	'civility' => $obj->civility,
                    		'lastname' => $obj->lastname,
                    		'firstname' => $obj->firstname,
                    		'email' => $obj->email,
                        	'rowid' => $obj->rowid,
                    		'code' => $obj->code,
                    		'libelle' => $libelle_type,
                    		'status' => $obj->statuslink,
                    		'statuscontact'=>$obj->statuscontact,
                    		'fk_c_type_contact' => $obj->fk_c_type_contact,
                        	'phone' => $obj->phone,
                    		'phone_mobile' => $obj->phone_mobile);
                } else {
                    $tab[$i] = $obj->id;
                }

                $i++;
            }

            return $tab;
        } else {
            $this->error = $this->db->error();
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     * Get a default reference.
     *
     * @param	Societe		$thirdparty		Thirdparty
     * @return 	string   					Reference
     */
    public function getDefaultRef($thirdparty = '')
    {
        global $conf;

        $defaultref = '';
        $modele = empty($conf->global->TICKETSUP_ADDON) ? 'mod_ticket_simple' : $conf->global->TICKETSUP_ADDON;

        // Search template files
        $file = '';
        $classname = '';
        $filefound = 0;
        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
        foreach ($dirmodels as $reldir) {
            $file = dol_buildpath($reldir . "core/modules/ticket/" . $modele . '.php', 0);
            if (file_exists($file)) {
                $filefound = 1;
                $classname = $modele;
                break;
            }
        }

        if ($filefound) {
            $result = dol_include_once($reldir . "core/modules/ticket/" . $modele . '.php');
            $modTicket = new $classname;

            $defaultref = $modTicket->getNextValue($thirdparty, $this);
        }

        if (is_numeric($defaultref) && $defaultref <= 0) {
            $defaultref = '';
        }

        return $defaultref;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
	 *  Return if at least one photo is available
	 *
	 *  @param      string		$sdir       Directory to scan
	 *  @return     boolean     			True if at least one photo is available, False if not
	 */
    function is_photo_available($sdir)
    {
        // phpcs:enable
        include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        global $conf;

        $dir = $sdir . '/';
        $nbphoto = 0;

        $dir_osencoded = dol_osencode($dir);
        if (file_exists($dir_osencoded)) {
            $handle = opendir($dir_osencoded);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) != false) {
                    if (!utf8_check($file)) {
                        $file = utf8_encode($file);
                    }
                    // To be sure data is stored in UTF8 in memory
                    if (dol_is_file($dir . $file)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}


/**
 * Ticket line Class
 */
class TicketsLine
{
    /**
	 * @var int ID
	 */
	public $id;

    /**
     * @var string  $ref    Ticket reference
     */
    public $ref;

    /**
	 * Hash to identify ticket
	 */
    public $track_id;

    /**
	 * @var int Thirdparty ID
	 */
    public $fk_soc;

    /**
	 * Project ID
	 */
    public $fk_project;

    /**
	 * Person email who have create ticket
	 */
    public $origin_email;

    /**
	 * User id who have create ticket
	 */
    public $fk_user_create;

    /**
	 * User id who have ticket assigned
	 */
    public $fk_user_assign;

    /**
	 * Ticket subject
	 */
    public $subject;

    /**
	 * Ticket message
	 */
    public $message;

    /**
 	 * Ticket statut
	 */
    public $fk_statut;

    /**
	 * State resolution
	 */
    public $resolution;

    /**
	 * Progress in percent
	 */
    public $progress;

    /**
	 * Duration for ticket
	 */
    public $timing;

    /**
	 * Type code
	 */
    public $type_code;

    /**
	 * Category code
	 */
    public $category_code;

    /**
	 * Severity code
	 */
    public $severity_code;

    /**
	 * Type label
	 */
    public $type_label;

    /**
	 * Category label
	 */
    public $category_label;

    /**
	 * Severity label
	 */
    public $severity_label;

    /**
 	 * Creation date
	 */
    public $datec = '';

    /**
 	 * Read date
	 */
    public $date_read = '';

    /**
 	 * Close ticket date
	 */
    public $date_close = '';
}
