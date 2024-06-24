<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\defgroup   emailcollector     Module Emailcollector
 *  \brief      Module to collect emails
 *
 *  \file       htdocs/core/modules/modEmailCollector.class.php
 *  \ingroup    emailcollector
 *  \brief      Description and activation file for the module emailcollector
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module emailcollector
 */
class modEmailCollector extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 50320;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'emailcollector';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "interface";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '23';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuledavName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuledavDesc' not found (MyModue is name of module).
		$this->description = "EmailCollectorDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "EmailCollectorDescription";

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where DAV is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'email';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /dav/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /dav/core/modules/barcode)
		// for specific css file (eg: /dav/css/dav.css.php)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/dav/temp","/dav/subdir");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into dav/admin directory, to use to setup module.
		$this->config_page_url = array("emailcollector_list.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array('always'=>'modCron'); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->langfiles = array("admin");
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'davWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('DAV_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('DAV_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			//1=>array('DAV_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
		);


		if (!isset($conf->emailcollector) || !isset($conf->emailcollector->enabled)) {
			$conf->emailcollector = new stdClass();
			$conf->emailcollector->enabled = 0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@dav:$user->rights->dav->read:/dav/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@dav:$user->rights->othermodule->read:/dav/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sales order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		$this->dictionaries = array();


		// Boxes/Widgets
		// Add here list of php file(s) stored in dav/core/boxes that contains class to show a widget.
		$this->boxes = array(
			//0=>array('file'=>'davwidget1.php@dav','note'=>'Widget provided by dav','enabledbydefaulton'=>'Home'),
			//1=>array('file'=>'davwidget2.php@dav','note'=>'Widget provided by dav'),
			//2=>array('file'=>'davwidget3.php@dav','note'=>'Widget provided by dav')
		);


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			0=>array('label'=>'Email collector', 'priority'=>50, 'jobtype'=>'method', 'class'=>'/emailcollector/class/emailcollector.class.php', 'objectname'=>'EmailCollector', 'method'=>'doCollect', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>5, 'unitfrequency'=>60, 'status'=>1, 'test'=>'isModEnabled("emailcollector")')
		);


		// Permissions
		$this->rights = array(); // Permission array used by this module

		// Main menu entries
		$this->menu = array(); // List of menus to add

		$r = 0;
		$this->menu[$r] = array('fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'EmailCollectors',
			'url'=>'/admin/emailcollector_list.php?leftmenu=admintools',
			'langs'=>'admin', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>400,
			'enabled'=>'isModEnabled("emailcollector") && preg_match(\'/^(admintools|all)/\', $leftmenu)', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->admin', // Use 'perms'=>'$user->hasRight("mymodule","level1","level2")' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2); // 0=Menu for internal users, 1=external users, 2=both
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
		global $conf, $user, $langs;
		$langs->load("admin");

		$sql = array();

		$tmpsql = "SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Ticket_Requests' and entity = ".$conf->entity;
		$tmpresql = $this->db->query($tmpsql);
		if ($tmpresql) {
			if ($this->db->num_rows($tmpresql) == 0) {
				$descriptionA1 = $langs->trans('EmailCollectorExampleToCollectTicketRequestsDesc');
				$label = $langs->trans('EmailCollectorExampleToCollectTicketRequests');
				$sqlforexampleA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollector (entity, ref, label, description, source_directory, date_creation, fk_user_creat, status)";
				$sqlforexampleA1 .= " VALUES (".$conf->entity.", 'Collect_Ticket_Requests', '".$this->db->escape($label)."', '".$this->db->escape($descriptionA1)."', 'INBOX', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 0)";

				$sqlforexampleFilterA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterA1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Ticket_Requests' and entity = ".$conf->entity."), 'isnotanswer', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				//$sqlforexampleFilterA2 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				//$sqlforexampleFilterA2 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Ticket_Requests' and entity = ".$conf->entity."), 'withouttrackingid', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				$sqlforexampleFilterA3 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, rulevalue, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterA3 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Ticket_Requests' and entity = ".$conf->entity."), 'to', 'support@example.com', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sqlforexampleActionA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectoraction (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleActionA1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Ticket_Requests' and entity = ".$conf->entity."), 'ticket', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sql[] = $sqlforexampleA1;

				$sql[] = $sqlforexampleFilterA1;
				//$sql[] = $sqlforexampleFilterA2;
				$sql[] = $sqlforexampleFilterA3;

				$sql[] = $sqlforexampleActionA1;
			}
		} else {
			dol_print_error($this->db);
		}

		$tmpsql = "SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_Out' and entity = ".$conf->entity;
		$tmpresql = $this->db->query($tmpsql);
		if ($tmpresql) {
			if ($this->db->num_rows($tmpresql) == 0) {
				$descriptionA1 = $langs->trans('EmailCollectorExampleToCollectAnswersFromExternalEmailSoftware');
				$label = $langs->trans('EmailCollectorExampleToCollectAnswersFromExternalEmailSoftware');
				$sqlforexampleA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollector (entity, ref, label, description, source_directory, date_creation, fk_user_creat, status)";
				$sqlforexampleA1 .= " VALUES (".$conf->entity.", 'Collect_Responses_Out', '".$this->db->escape($label)."', '".$this->db->escape($descriptionA1)."', 'Sent', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 0)";

				$sqlforexampleFilterA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterA1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_Out' and entity = ".((int) $conf->entity)."), 'isanswer', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				$sqlforexampleFilterA2 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterA2 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_Out' and entity = ".((int) $conf->entity)."), 'withouttrackingidinmsgid', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sqlforexampleActionA1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectoraction (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleActionA1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_Out' and entity = ".((int) $conf->entity)."), 'recordevent', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sql[] = $sqlforexampleA1;

				$sql[] = $sqlforexampleFilterA1;
				$sql[] = $sqlforexampleFilterA2;

				$sql[] = $sqlforexampleActionA1;
			}
		}

		$tmpsql = "SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_In' and entity = ".((int) $conf->entity);
		$tmpresql = $this->db->query($tmpsql);
		if ($tmpresql) {
			if ($this->db->num_rows($tmpresql) == 0) {
				$descriptionB1 = $langs->trans('EmailCollectorExampleToCollectDolibarrAnswersDesc');
				$label = $langs->trans('EmailCollectorExampleToCollectDolibarrAnswers');
				$sqlforexampleB1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollector (entity, ref, label, description, source_directory, date_creation, fk_user_creat, status)";
				$sqlforexampleB1 .= " VALUES (".$conf->entity.", 'Collect_Responses_In', '".$this->db->escape($label)."', '".$this->db->escape($descriptionB1)."', 'INBOX', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 0)";

				$sqlforexampleFilterB2 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterB2 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_In' and entity = ".((int) $conf->entity)."), 'isanswer', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sqlforexampleActionB3 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectoraction (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleActionB3 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Responses_In' and entity = ".((int) $conf->entity)."), 'recordevent', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sql[] = $sqlforexampleB1;

				$sql[] = $sqlforexampleFilterB2;

				$sql[] = $sqlforexampleActionB3;
			}
		} else {
			dol_print_error($this->db);
		}

		$tmpsql = "SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Leads' and entity = ".((int) $conf->entity);
		$tmpresql = $this->db->query($tmpsql);
		if ($tmpresql) {
			if ($this->db->num_rows($tmpresql) == 0) {
				$descriptionC1 = $langs->trans("EmailCollectorExampleToCollectLeadsDesc");
				$label = $langs->trans('EmailCollectorExampleToCollectLeads');
				$sqlforexampleC1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollector (entity, ref, label, description, source_directory, date_creation, fk_user_creat, status)";
				$sqlforexampleC1 .= " VALUES (".$conf->entity.", 'Collect_Leads', '".$this->db->escape($label)."', '".$this->db->escape($descriptionC1)."', 'INBOX', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 0)";

				$sqlforexampleFilterC1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterC1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Leads' and entity = ".((int) $conf->entity)."), 'isnotanswer', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				//$sqlforexampleFilterC2 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				//$sqlforexampleFilterC2 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Leads' and entity = ".((int) $conf->entity)."), 'withouttrackingid', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				$sqlforexampleFilterC3 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, rulevalue, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterC3 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Leads' and entity = ".((int) $conf->entity)."), 'to', 'sales@example.com', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$paramstring = 'tmp_from=EXTRACT:HEADER:^From:(.*)'."\n".'socid=SETIFEMPTY:1'."\n".'usage_opportunity=SET:1'."\n".'description=EXTRACT:BODY:(.*)'."\n".'title=SET:Lead or message from __tmp_from__ received by email';
				$sqlforexampleActionC4 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectoraction (fk_emailcollector, type, actionparam, date_creation, fk_user_creat, status)";
				$sqlforexampleActionC4 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Leads' and entity = ".((int) $conf->entity)."), 'project', '".$this->db->escape($paramstring)."', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sql[] = $sqlforexampleC1;

				$sql[] = $sqlforexampleFilterC1;
				//$sql[] = $sqlforexampleFilterC2;
				$sql[] = $sqlforexampleFilterC3;

				$sql[] = $sqlforexampleActionC4;
			}
		} else {
			dol_print_error($this->db);
		}

		$tmpsql = "SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Candidatures' and entity = ".((int) $conf->entity);
		$tmpresql = $this->db->query($tmpsql);
		if ($tmpresql) {
			if ($this->db->num_rows($tmpresql) == 0) {
				$descriptionC1 = $langs->trans("EmailCollectorExampleToCollectJobCandidaturesDesc");
				$label = $langs->trans('EmailCollectorExampleToCollectJobCandidatures');
				$sqlforexampleC1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollector (entity, ref, label, description, source_directory, date_creation, fk_user_creat, status)";
				$sqlforexampleC1 .= " VALUES (".$conf->entity.", 'Collect_Candidatures', '".$this->db->escape($label)."', '".$this->db->escape($descriptionC1)."', 'INBOX', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 0)";

				$sqlforexampleFilterC1 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterC1 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Candidatures' and entity = ".((int) $conf->entity)."), 'isnotanswer', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				//$sqlforexampleFilterC2 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, date_creation, fk_user_creat, status)";
				//$sqlforexampleFilterC2 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Candidatures' and entity = ".((int) $conf->entity)."), 'withouttrackingid', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";
				$sqlforexampleFilterC3 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectorfilter (fk_emailcollector, type, rulevalue, date_creation, fk_user_creat, status)";
				$sqlforexampleFilterC3 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Candidatures' and entity = ".((int) $conf->entity)."), 'to', 'jobs@example.com', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$paramstring = 'tmp_from=EXTRACT:HEADER:^From:(.*)(<.*>)?'."\n".'fk_recruitmentjobposition=EXTRACT:HEADER:^To:[^\n]*\+([^\n]*)'."\n".'description=EXTRACT:BODY:(.*)'."\n".'lastname=SET:__tmp_from__';
				$sqlforexampleActionC4 = "INSERT INTO ".MAIN_DB_PREFIX."emailcollector_emailcollectoraction (fk_emailcollector, type, actionparam, date_creation, fk_user_creat, status)";
				$sqlforexampleActionC4 .= " VALUES ((SELECT rowid FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector WHERE ref = 'Collect_Candidatures' and entity = ".((int) $conf->entity)."), 'candidature', '".$this->db->escape($paramstring)."', '".$this->db->idate(dol_now())."', ".((int) $user->id).", 1)";

				$sql[] = $sqlforexampleC1;

				$sql[] = $sqlforexampleFilterC1;
				//$sql[] = $sqlforexampleFilterC2;
				$sql[] = $sqlforexampleFilterC3;

				$sql[] = $sqlforexampleActionC4;
			}
		} else {
			dol_print_error($this->db);
		}

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
