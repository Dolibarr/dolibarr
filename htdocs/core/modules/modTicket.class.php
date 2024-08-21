<?php
/* Copyright (C) - 2013-2018    Jean-François FERRY    <hello@librethic.io>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Module descriptor for ticket system
 */

/**
 *     \defgroup    ticket    Module Ticket
 *     \brief       Module for ticket and request management.
 *     \file        core/modules/modTicket.class.php
 *     \ingroup     ticket
 *     \brief       Description and activation file for the module Ticket
 */
require_once DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php";


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
		$langs->load("ticket");

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
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled
		// (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
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
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/ticket/temp");
		$this->dirs = array();

		// Config pages. Put here list of php pages
		// stored into ticket/admin directory, used to setup module.
		$this->config_page_url = array("ticket.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array('modAgenda'); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->langfiles = array("ticket");

		// Constants
		// List of particular constants to add when module is enabled
		// (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example:
		$default_footer = $langs->trans('TicketMessageMailFooterText', getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));
		$this->const = array(
			1 => array('TICKET_ENABLE_PUBLIC_INTERFACE', 'chaine', '0', 'Enable ticket public interface', 0),
			2 => array('TICKET_ADDON', 'chaine', 'mod_ticket_simple', 'Ticket ref module', 0),
			3 => array('TICKET_ADDON_PDF_ODT_PATH', 'chaine', 'DOL_DATA_ROOT/doctemplates/tickets', 'Ticket templates ODT/ODS directory for templates', 0),
			4 => array('TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND', 'chaine', 0, 'Automatically mark ticket as read when created from backend', 0),
			5 => array('TICKET_DELAY_BEFORE_FIRST_RESPONSE', 'chaine', '0', 'Maximum wanted elapsed time before a first answer to a ticket (in hours). Display a warning in tickets list if not respected.', 0),
			6 => array('TICKET_DELAY_SINCE_LAST_RESPONSE', 'chaine', '0', 'Maximum wanted elapsed time between two answers on the same ticket (in hours). Display a warning in tickets list if not respected.', 0),
			7 => array('TICKET_NOTIFY_AT_CLOSING', 'chaine', '0', 'Default notify contacts when closing a module', 0),
			8 => array('TICKET_PRODUCT_CATEGORY', 'chaine', 0, 'The category of product that is being used for ticket accounting', 0),
			9 => array('TICKET_NOTIFICATION_EMAIL_FROM', 'chaine', getDolGlobalString('MAIN_MAIL_EMAIL_FROM'), 'Email to use by default as sender for messages sent from Dolibarr', 0),
			10 => array('TICKET_MESSAGE_MAIL_INTRO', 'chaine', $langs->trans('TicketMessageMailIntroText'), 'Introduction text of ticket replies sent from Dolibarr', 0),
			11 => array('TICKET_MESSAGE_MAIL_SIGNATURE', 'chaine', $default_footer, 'Signature to use by default for messages sent from Dolibarr', 0),
			12 => array('MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER', 'chaine', "1", 'Disable the rendering of headers in tickets', 0),
			13 => array('MAIN_SECURITY_ENABLECAPTCHA_TICKET', 'chaine', getDolGlobalInt('MAIN_SECURITY_ENABLECAPTCHA_TICKET'), 'Enable captcha code by default', 0),
			14 => array('TICKET_SHOW_COMPANY_LOGO', 'chaine', getDolGlobalInt('TICKET_SHOW_COMPANY_LOGO', 1), 'Enable logo header on ticket public page', 0),
			15 => array('TICKET_SHOW_COMPANY_FOOTER', 'chaine', getDolGlobalInt('TICKET_SHOW_COMPANY_FOOTER', 1), 'Enable footer on ticket public page', 0)
		);

		/*
		$this->tabs = array(
			'thirdparty:+ticket:Tickets:ticket:$user->hasRight("ticket","read"):/ticket/list.php?socid=__ID__',
		);
		*/

		// Dictionaries
		if (!isset($conf->ticket->enabled)) {
			$conf->ticket = new stdClass();
			$conf->ticket->enabled = 0;
		}

		// Dictionary of ticket types
		$this->declareNewDictionary(
			array(
				'name' => 'c_ticket_type',
				'lib' => 'TicketDictType',
				'sql' => 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default, f.entity FROM '.$this->db->prefix().'c_ticket_type as f WHERE f.entity IN ('.getEntity('c_ticket_type').')',
				'sqlsort' => 'pos ASC',
				'field' => 'code,label,pos,use_default',
				'fieldvalue' => 'code,label,pos,use_default',
				'fieldinsert' => 'code,label,pos,use_default,entity',
				'rowid' => 'rowid',
				'cond' => isModEnabled('ticket'),
				'help' => array('code' => $langs->trans('EnterAnyCode'), 'use_default' => $langs->trans('EnterYesOrNo'))
			)
		);

		// Dictionary of ticket severities
		$this->declareNewDictionary(
			array(
				'name' => 'c_ticket_severity',
				'lib' => 'TicketDictSeverity',
				'sql' => 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default, f.entity FROM '.$this->db->prefix().'c_ticket_severity as f WHERE f.entity IN ('.getEntity('c_ticket_severity').')',
				'sqlsort' => 'pos ASC',
				'field' => 'code,label,pos,use_default',
				'fieldvalue' => 'code,label,pos,use_default',
				'fieldinsert' => 'code,label,pos,use_default,entity',
				'rowid' => 'rowid',
				'cond' => isModEnabled('ticket'),
				'help' => array('code' => $langs->trans('EnterAnyCode'), 'use_default' => $langs->trans('EnterYesOrNo'))
			)
		);

		// Dictionary of ticket categories
		$this->declareNewDictionary(
			array(
				'name' => 'c_ticket_category',
				'lib' => 'TicketDictCategory',
				'sql' => 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default, f.public, f.fk_parent, f.entity FROM '.$this->db->prefix().'c_ticket_category as f WHERE f.entity IN ('.getEntity('c_ticket_category').')',
				'sqlsort' => 'pos ASC',
				'field' => 'code,label,pos,use_default,public,fk_parent',
				'fieldvalue' => 'code,label,pos,use_default,public,fk_parent',
				'fieldinsert' => 'code,label,pos,use_default,public,fk_parent,entity',
				'rowid' => 'rowid',
				'cond' => isModEnabled('ticket'),
				'help' => array(
					'code' => $langs->trans('EnterAnyCode'),
					'use_default' => $langs->trans('EnterYesOrNo'),
					'public' => $langs->trans('Enter0or1').'<br>'.$langs->trans('TicketGroupIsPublicDesc'),
					'fk_parent' => $langs->trans('IfThisCategoryIsChildOfAnother')
				)
			)
		);

		// Dictionary of ticket resolutions (apparently unused except if TICKET_ENABLE_RESOLUTION is on)
		$this->declareNewDictionary(
			array(
				'name' => 'c_ticket_resolution',
				'lib' => 'TicketDictResolution',
				'sql' => 'SELECT f.rowid as rowid, f.code, f.pos, f.label, f.active, f.use_default, f.entity FROM '.$this->db->prefix().'c_ticket_resolution as f WHERE f.entity IN ('.getEntity('c_ticket_resolution').')',
				'sqlsort' => 'pos ASC',
				'field' => 'code,label,pos,use_default',
				'fieldvalue' => 'code,label,pos,use_default',
				'fieldinsert' => 'code,label,pos,use_default,entity',
				'rowid' => 'rowid',
				'cond' => isModEnabled('ticket') && getDolGlobalString('TICKET_ENABLE_RESOLUTION'),
				'help' => array('code' => $langs->trans('EnterAnyCode'), 'use_default' => $langs->trans('Enter0or1'))
			)
		);

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(
			0 => array('file' => 'box_last_ticket.php', 'enabledbydefaulton' => 'Home'),
			1 => array('file' => 'box_last_modified_ticket.php', 'enabledbydefaulton' => 'Home'),
			2 => array('file' => 'box_ticket_by_severity.php', 'enabledbydefaulton' => 'ticketindex'),
			3 => array('file' => 'box_graph_nb_ticket_last_x_days.php', 'enabledbydefaulton' => 'ticketindex'),
			4 => array('file' => 'box_graph_nb_tickets_type.php', 'enabledbydefaulton' => 'ticketindex'),
			5 => array('file' => 'box_new_vs_close_ticket.php', 'enabledbydefaulton' => 'ticketindex')
		); // Boxes list

		// Permissions
		$this->rights = array(); // Permission array used by this module

		$r = 0;
		$this->rights[$r][0] = 56001; // id de la permission
		$this->rights[$r][1] = "Read ticket"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 56002; // id de la permission
		$this->rights[$r][1] = "Create les tickets"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'write';

		$r++;
		$this->rights[$r][0] = 56003; // id de la permission
		$this->rights[$r][1] = "Delete les tickets"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'delete';

		$r++;
		$this->rights[$r][0] = 56004; // id de la permission
		$this->rights[$r][1] = "Manage tickets"; // libelle de la permission
		//$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'manage';

		$r++;
		$this->rights[$r][0] = 56006; // id de la permission
		$this->rights[$r][1] = "Export ticket"; // libelle de la permission
		//$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'export';

		/* Seems not used and in conflict with societe->client->voir (see all thirdparties)
		$r++;
		$this->rights[$r][0] = 56005; // id de la permission
		$this->rights[$r][1] = 'See all tickets, even if not assigned to (not effective for external users, always restricted to the thirdpardy they depends on)'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'view';
		$this->rights[$r][5] = 'all';
		*/

		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		/*$this->menu[$r] = array('fk_menu' => 0, // Put 0 if this is a top menu
			'type' => 'top', // This is a Top menu entry
			'titre' => 'Ticket',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth em092"'),
			'mainmenu' => 'ticket',
			'leftmenu' => '1', // Use 1 if you also want to add left menu entries using this descriptor.
			'url' => '/ticket/index.php',
			'langs' => 'ticket', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 88,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 2); // 0=Menu for internal users, 1=external users, 2=both
		$r++;*/

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket',
			'type' => 'left',
			'titre' => 'Ticket',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth em092"'),
			'mainmenu' => 'ticket',
			'leftmenu' => 'ticket',
			'url' => '/ticket/index.php',
			'langs' => 'ticket',
			'position' => 101,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 2);
		$r++;

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
			'type' => 'left',
			'titre' => 'NewTicket',
			'mainmenu' => 'ticket',
			'url' => '/ticket/card.php?action=create',
			'langs' => 'ticket',
			'position' => 102,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->rights->ticket->write',
			'target' => '',
			'user' => 2);
		$r++;

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
			'type' => 'left',
			'titre' => 'List',
			'mainmenu' => 'ticket',
			'leftmenu' => 'ticketlist',
			'url' => '/ticket/list.php?search_fk_status=non_closed',
			'langs' => 'ticket',
			'position' => 103,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 2);
		$r++;

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
			'type' => 'left',
			'titre' => 'MenuTicketMyAssign',
			'mainmenu' => 'ticket',
			'leftmenu' => 'ticketmy',
			'url' => '/ticket/list.php?mode=mine&search_fk_status=non_closed',
			'langs' => 'ticket',
			'position' => 105,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 0);
		$r++;

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
			'type' => 'left',
			'titre' => 'Statistics',
			'mainmenu' => 'ticket',
			'url' => '/ticket/stats/index.php',
			'langs' => 'ticket',
			'position' => 107,
			'enabled' => 'isModEnabled("ticket")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 0);
		$r++;

		$this->menu[$r] = array('fk_menu' => 'fk_mainmenu=ticket,fk_leftmenu=ticket',
			'type' => 'left',
			'titre' => 'Categories',
			'mainmenu' => 'ticket',
			'url' => '/categories/index.php?type=12',
			'langs' => 'ticket',
			'position' => 107,
			'enabled' => 'isModEnabled("ticket") && isModEnabled("categorie")',
			'perms' => '$user->hasRight("ticket","read")',
			'target' => '',
			'user' => 0);
		$r++;

		// Exports
		//--------
		$r = 1;

		// Export list of tickets and attributes
		$langs->load("ticket");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ExportDataset_ticket_1';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r] = array(array("ticket", "export"));
		$this->export_icon[$r] = 'ticket';
		$keyforclass = 'Ticket';
		$keyforclassfile = '/ticket/class/ticket.class.php';
		$keyforelement = 'ticket';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect = 'ticket';
		$keyforaliasextra = 'extra';
		$keyforelement = 'ticket';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'ticket as t';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as extra on (t.rowid = extra.fk_object)';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
		$this->export_sql_end[$r] .= ' AND t.entity IN ('.getEntity('ticket').')';
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
		global $conf, $langs;

		$result = $this->_load_tables('/install/mysql/', 'ticket');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Permissions
		$this->remove($options);

		//ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/tickets/template_ticket.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/tickets';
		$dest = $dirodt.'/template_ticket.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, 0, 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
			array("sql" => "insert into ".$this->db->prefix()."c_type_contact(rowid, element, source, code, libelle, active ) values (110120, 'ticket',  'internal', 'SUPPORTTEC', 'Utilisateur assigné au ticket', 1);", "ignoreerror" => 1),
			array("sql" => "insert into ".$this->db->prefix()."c_type_contact(rowid, element, source, code, libelle, active ) values (110121, 'ticket',  'internal', 'CONTRIBUTOR', 'Intervenant', 1);", "ignoreerror" => 1),
			array("sql" => "insert into ".$this->db->prefix()."c_type_contact(rowid, element, source, code, libelle, active ) values (110122, 'ticket',  'external', 'SUPPORTCLI', 'Contact client suivi incident', 1);", "ignoreerror" => 1),
			array("sql" => "insert into ".$this->db->prefix()."c_type_contact(rowid, element, source, code, libelle, active ) values (110123, 'ticket',  'external', 'CONTRIBUTOR', 'Intervenant', 1);", "ignoreerror" => 1),
			// remove old settings
			"DELETE FROM ".$this->db->prefix()."document_model WHERE nom = 'TICKET_ADDON_PDF_ODT_PATH' AND type = 'ticket' AND entity = ".((int) $conf->entity),
			// activate default odt templates
			array("sql" => "INSERT INTO ".$this->db->prefix()."document_model (nom, type, libelle, entity, description) VALUES('generic_ticket_odt','ticket','ODT templates',".((int) $conf->entity).",'TICKET_ADDON_PDF_ODT_PATH');", "ignoreerror" => 1),
		);

		return $this->_init($sql, $options);
	}
}
