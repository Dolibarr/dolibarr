<?php
/* Copyright (C) - 2013-2018    Jean-François FERRY    <hello@librethic.io>
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
 *
 * Module descriptor for ticket system
 */

/**
 *     \defgroup    ticket    Ticket module
 *     \brief       Ticket module descriptor.
 *     \file        core/modules/modTicket.class.php
 *     \ingroup     ticket
 *     \brief       Description and activation file for module Ticket
 */
require_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";


/**
 * Description and activation class for module Ticket
 */
class modTicket extends DolibarrModules
{
    /**
     *     Constructor. Define names, constants, directories, boxes, permissions
     *
     *     @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 56000;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'ticket';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "crm";
        // Module position in the family
        $this->module_position = '60';
        // Module label (no space allowed)
        // used if translation string 'ModuleXXXName' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description
        // used if translation string 'ModuleXXXDesc' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->description = "Incident/support ticket management";
        // Possible values for version are: 'development', 'experimental' or version
        $this->version = 'experimental';
        // Key used in llx_const table to save module status enabled/disabled
        // (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'ticket'; // mypicto@ticket
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /ticket/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /ticket/core/modules/barcode)
        // for specific css file (eg: /ticket/css/ticket.css.php)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory
            'triggers' => 1,
            // Set this to 1 if module has its own models directory
            'models' => 1,
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/ticket/temp");
        $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into ticket/admin directory, used to setup module.
        $this->config_page_url = array("ticket.php");

        // Dependencies
        $this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
        $this->langfiles = array("ticket");
        // Constants
        // List of particular constants to add when module is enabled
        // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example:
        $this->const = array();
        $this->const[1] = array('TICKET_ENABLE_PUBLIC_INTERFACE', 'chaine', '1', 'Enable ticket public interface');
        $this->const[2] = array('TICKET_ADDON', 'chaine', 'mod_ticket_simple', 'Ticket ref module');

        $this->tabs = array(
            'thirdparty:+ticket:Tickets:@ticket:$user->rights->ticket->read:/ticket/list.php?socid=__ID__',
            'project:+ticket:Tickets:@ticket:$user->rights->ticket->read:/ticket/list.php?projectid=__ID__',
        );

        // Dictionaries
        if (! isset($conf->ticket->enabled)) {
            $conf->ticket=new stdClass();
            $conf->ticket->enabled=0;
        }
        $this->dictionaries = array(
            'langs' => 'ticket',
            'tabname' => array(MAIN_DB_PREFIX . "c_ticket_type", MAIN_DB_PREFIX . "c_ticket_category", MAIN_DB_PREFIX . "c_ticket_severity"),
            'tablib' => array("TicketDictType", "TicketDictCategory", "TicketDictSeverity"),
            'tabsql' => array('SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default FROM ' . MAIN_DB_PREFIX . 'c_ticket_type as f', 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default FROM ' . MAIN_DB_PREFIX . 'c_ticket_category as f', 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default FROM ' . MAIN_DB_PREFIX . 'c_ticket_severity as f'),
            'tabsqlsort' => array("pos ASC", "pos ASC", "pos ASC"),
            'tabfield' => array("pos,code,label,use_default", "pos,code,label,use_default", "pos,code,label,use_default"),
            'tabfieldvalue' => array("pos,code,label,use_default", "pos,code,label,use_default", "pos,code,label,use_default"),
            'tabfieldinsert' => array("pos,code,label,use_default", "pos,code,label,use_default", "pos,code,label,use_default"),
            'tabrowid' => array("rowid", "rowid", "rowid"),
            'tabcond' => array($conf->ticket->enabled, $conf->ticket->enabled, $conf->ticket->enabled),
        );

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(); // Boxes list
        $r = 0;
        // Example:

        $this->boxes[$r][1] = "box_last_ticket";
        $r++;

        $this->boxes[$r][1] = "box_last_modified_ticket";
        $r++;

        // Permissions
        $this->rights = array(); // Permission array used by this module

        $r=0;
        $this->rights[$r][0] = 56001; // id de la permission
        $this->rights[$r][1] = "Read ticket"; // libelle de la permission
        $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'read';

        $r++;
        $this->rights[$r][0] = 56002; // id de la permission
        $this->rights[$r][1] = "Create les tickets"; // libelle de la permission
        $this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'write';

        $r++;
        $this->rights[$r][0] = 56003; // id de la permission
        $this->rights[$r][1] = "Delete les tickets"; // libelle de la permission
        $this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'delete';

        $r++;
        $this->rights[$r][0] = 56004; // id de la permission
        $this->rights[$r][1] = "Manage tickets"; // libelle de la permission
        //$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'manage';

        $r++;
        $this->rights[$r][0] = 56005; // id de la permission
        $this->rights[$r][1] = 'See all tickets, even if not assigned to (not effective for external users, always restricted to the thirdpardy they depends on)'; // libelle de la permission
        $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'view';
        $this->rights[$r][5] = 'all';

        // Main menu entries
        $this->menus = array(); // List of menus to add
        $r = 0;

        $this->menu[$r] = array('fk_menu' => 0, // Put 0 if this is a top menu
            'type' => 'top', // This is a Top menu entry
            'titre' => 'Ticket',
            'mainmenu' => 'ticket',
            'leftmenu' => '1', // Use 1 if you also want to add left menu entries using this descriptor.
            'url' => '/ticket/index.php',
            'langs' => 'ticket', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 88,
            'enabled' => '$conf->ticket->enabled', // Define condition to show or hide menu entry. Use '$conf->ticket->enabled' if entry must be visible if module is enabled.
            'perms' => '$user->rights->ticket->read', // Use 'perms'=>'$user->rights->ticket->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 2); // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket',
            'type' => 'left',
            'titre' => 'Ticket',
            'mainmenu' => 'ticket',
            'leftmenu' => 'ticket',
            'url' => '/ticket/index.php',
            'langs' => 'ticket',
            'position' => 101,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->read',
            'target' => '',
            'user' => 2);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
            'type' => 'left',
            'titre' => 'NewTicket',
            'mainmenu' => 'ticket',
            'url' => '/ticket/new.php?action=create_ticket',
            'langs' => 'ticket',
            'position' => 102,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->write',
            'target' => '',
            'user' => 2);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
            'type' => 'left',
            'titre' => 'List',
            'mainmenu' => 'ticket',
            'leftmenu' => 'ticketlist',
            'url' => '/ticket/list.php',
            'langs' => 'ticket',
            'position' => 103,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->read',
            'target' => '',
            'user' => 2);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticketlist',
            'type' => 'left',
            'titre' => 'MenuListNonClosed',
            'mainmenu' => 'ticket',
            'leftmenu' => 'ticketlist',
            'url' => '/ticket/list.php?search_fk_status=non_closed',
            'langs' => 'ticket',
            'position' => 104,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->read',
            'target' => '',
            'user' => 2);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
            'type' => 'left',
            'titre' => 'MenuTicketMyAssign',
            'mainmenu' => 'ticket',
            'leftmenu' => 'ticketmy',
            'url' => '/ticket/list.php?mode=my_assign',
            'langs' => 'ticket',
            'position' => 105,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->read',
            'target' => '',
            'user' => 0);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticketmy',
            'type' => 'left',
            'titre' => 'MenuTicketMyAssignNonClosed',
            'mainmenu' => 'ticket',
            'url' => '/ticket/list.php?mode=my_assign&search_fk_status=non_closed',
            'langs' => 'ticket',
            'position' => 106,
            'enabled' => '$conf->ticket->enabled',
            'perms' => '$user->rights->ticket->read',
            'target' => '',
            'user' => 0);
        $r++;
    }

    /**
     *	Function called when module is enabled.
     *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *	It also creates data directories
     *
     *	@param      string	$options    Options when enabling module ('', 'noboxes')
     *	@return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {

        $sql = array(
            array("sql" => "insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (110120, 'ticket',  'internal', 'SUPPORTTEC', 'Utilisateur assigné au ticket', 1);", "ignoreerror" => 1),
            array("sql" => "insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (110121, 'ticket',  'internal', 'CONTRIBUTOR', 'Intervenant', 1);", "ignoreerror" => 1),
            array("sql" => "insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (110122, 'ticket',  'external', 'SUPPORTCLI', 'Contact client suivi incident', 1);", "ignoreerror" => 1),
            array("sql" => "insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (110123, 'ticket',  'external', 'CONTRIBUTOR', 'Intervenant', 1);", "ignoreerror" => 1),
            array("sql" => "insert into llx_c_action_trigger (rowid,code,label,description,elementtype,rang) values ('','TICKETMESSAGE_SENTBYMAIL','Send email for ticket','Executed when a response is made on a ticket','ticket','');", "ignoreerror" => 1),
        );

        return $this->_init($sql, $options);
    }
}
