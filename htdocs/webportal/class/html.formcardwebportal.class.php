<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
 * \file       htdocs/webportal/class/html.formlistwebportal.class.php
 * \ingroup    webportal
 * \brief      File of class with all html predefined components for WebPortal
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/html.formwebportal.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/webportalfieldsmanager.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

/**
 *    Class to manage generation of HTML components
 *    Only common components for WebPortal must be here.
 */
class FormCardWebPortal
{
	/**
	 * @var string Action
	 */
	public $action = '';

	/**
	 * @var string Back to page
	 */
	public $backtopage = '';

	/**
	 * @var string Back to page for cancel
	 */
	public $backtopageforcancel = '';

	/**
	 * @var string Cancel
	 */
	public $cancel = '';

	/**
	 * @var DoliDB Database
	 */
	public $db;

	/**
	 * @var string Element in english
	 */
	public $elementEn = '';

	/**
	 * @var FormWebPortal  Instance of the Form
	 */
	public $form;

	/**
	 * @var int Id
	 */
	public $id;

	/**
	 * @var CommonObject Object
	 */
	public $object;

	/**
	 * @var ExtraFields Extra fields
	 */
	public $extrafields;

	/**
	 * @var int Permission to read
	 */
	public $permissiontoread = 0;

	/**
	 * @var int Permission to add
	 */
	public $permissiontoadd = 0;

	/**
	 * @var int Permission to delete
	 */
	public $permissiontodelete = 0;

	/**
	 * @var int Permission to note
	 */
	public $permissionnote = 0;

	/**
	 * @var int Permission to delete links
	 */
	public $permissiondellink = 0;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string Title key to translate
	 */
	public $titleKey = '';

	/**
	 * @var string Title desc key to translate
	 */
	public $titleDescKey = '';

	/**
	 * @var string Object field key to break on the view page
	 */
	public $key_for_break = '';

	/**
	 * @var bool The card displayed is in modal
	 */
	public $modal = false;

	/**
	 * @var FieldsManager	Fields manager
	 */
	public $fieldsmanager;

	/**
	 * @var int		Update function type (1: update(user), 2: update(id, user))
	 */
	public $updateType = 1;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->form = new FormWebPortal($this->db);
		$this->fieldsmanager = new WebPortalFieldsManager($this->db, $this->form);
	}

	/**
	 * Init
	 *
	 * @param	string	$elementEn				Element (english) : "member" (for adherent), "partnership"
	 * @param	int		$id						[=0] ID element
	 * @param	int		$permissiontoread		[=0] Permission to read (0 : access forbidden by default)
	 * @param	int		$permissiontoadd		[=0] Permission to add (0 : access forbidden by default), used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	 * @param	int		$permissiontodelete		[=0] Permission to delete (0 : access forbidden by default)
	 * @param	int		$permissionnote			[=0] Permission to note (0 : access forbidden by default)
	 * @param	int		$permissiondellink		[=0] Permission to delete links (0 : access forbidden by default)
	 * @return	void
	 */
	public function init($elementEn, $id = 0, $permissiontoread = 0, $permissiontoadd = 0, $permissiontodelete = 0, $permissionnote = 0, $permissiondellink = 0)
	{
		global $hookmanager, $langs;

		$elementEnUpper = strtoupper($elementEn);
		$objectclass = 'WebPortal' . ucfirst($elementEn);

		$elementCardAccess = getDolGlobalString('WEBPORTAL_' . $elementEnUpper . '_CARD_ACCESS', 'hidden');
		if ($elementCardAccess == 'hidden' || $id <= 0) {
			accessforbidden();
		}

		$context = Context::getInstance();

		// load module libraries
		dol_include_once('/webportal/class/webportal' . $elementEn . '.class.php');

		// Load translation files required by the page
		$langs->loadLangs(array('website', 'other'));

		// Get parameters
		//$id = $id > 0 ? $id : GETPOST('id', 'int');
		$ref = GETPOST('ref', 'alpha');
		$action = GETPOST('action', 'aZ09');
		$confirm = GETPOST('confirm', 'alpha');
		$cancel = GETPOST('cancel');
		$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'webportal' . $elementEn . 'card'; // To manage different context of search
		$backtopage = GETPOST('backtopage', 'alpha');                    // if not set, a default page will be used
		$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');    // if not set, $backtopage will be used
		$modal = GETPOSTINT('modal') == 1;

		// Initialize a technical objects
		$object = new $objectclass($this->db);
		'@phan-var-force CommonObject $object';
		/** @var CommonObject $object */
		//$extrafields = new ExtraFields($db);
		$hookmanager->initHooks(array('webportal' . $elementEn . 'card', 'globalcard')); // Note that conf->hooks_modules contains array

		// Fetch optionals attributes and labels
		$this->extrafields = new ExtraFields($this->db);
		$this->extrafields->fetch_name_optionals_label($object->table_element);
		//$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

		if (empty($action) && empty($id) && empty($ref)) {
			$action = 'view';
		}

		// Load object
		if (($id > 0 || (!empty($ref) && !in_array($action, array('create', 'createtask', 'add')))) && (empty($cancel) || $id > 0)) {
			if (($id > 0 && is_numeric((string) $id)) || !empty($ref)) {    // To discard case when id is list of ids like '1,2,3...'
				if ($object->element == 'usergroup') {
					$ret = $object->fetch($id, (empty($ref) ? '' : $ref), true); // to load $object->members
				} else {
					$ret = $object->fetch($id, (empty($ref) ? '' : $ref));
				}
				if ($ret > 0) {
					$object->fetch_thirdparty();
					$id = $object->id;
				} else {
					if (empty($object->error) && !count($object->errors)) {
						if ($ret < 0) {    // if $ret == 0, it means not found.
							$context->setEventMessages('Fetch on object (type ' . get_class($object) . ') return an error without filling $object->error nor $object->errors', null, 'errors');
						}
					} else {
						$context->setEventMessages($object->error, $object->errors, 'errors');
					}
					$action = '';
				}
			}
		}

		// Security check (enable the most restrictive one)
		if (!isModEnabled('webportal')) {
			accessforbidden();
		}
		if (!$permissiontoread) {
			accessforbidden();
		}

		// set form card
		$this->action = $action;
		$this->backtopage = $backtopage;
		$this->backtopageforcancel = $backtopageforcancel;
		$this->cancel = $cancel;
		$this->elementEn = $elementEn;
		$this->id = (int) $id;
		$this->object = $object;
		$this->permissiontoread = $permissiontoread;
		$this->permissiontoadd = $permissiontoadd;
		$this->permissiontodelete = $permissiontodelete;
		$this->permissionnote = $permissionnote;
		$this->permissiondellink = $permissiondellink;
		$this->titleKey = $objectclass . 'CardTitle';
		$this->ref = $ref;
		$this->modal = $modal;
	}

	/**
	 * Do actions
	 *
	 * @return	void
	 */
	public function doActions()
	{
		// initialize
		$action = $this->action;
		$backtopage = $this->backtopage;
		$backtopageforcancel = $this->backtopageforcancel;
		$cancel = $this->cancel;
		$elementEn = $this->elementEn;
		$id = $this->id;
		$object = $this->object;
		//$permissiontoread = $this->permissiontoread;
		$permissiontoadd = $this->permissiontoadd;

		$context = Context::getInstance();

		$backurlforlist = $context->getControllerUrl('default');
		$noback = 1;

		if (empty($backtopage) || ($cancel && empty($id))) {
			if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
				$backtopage = $context->getControllerUrl($elementEn . 'card');
			}
		}

		// Action to cancel record
		if ($cancel) {
			if (!empty($backtopageforcancel)) {
				header("Location: " . $backtopageforcancel);
				exit;
			} elseif (!empty($backtopage)) {
				header("Location: " . $backtopage);
				exit;
			}
			$action = '';
		}

		// Action to update record
		if ($action == 'update' && !empty($permissiontoadd)) {
			$error = 0;

			$result = $this->fieldsmanager->setFieldValuesFromPost($object, $this->extrafields, '', '', 'edit');
			if ($result < 0) {
				$error++;
				$context->setEventMessages($this->fieldsmanager->error, $this->fieldsmanager->errors, 'errors');
			} else {
				if ($this->updateType == 2) {
					$result = $object->update($this->id, $context->logged_user);
				} else { // $this->updateType == 1
					$result = $object->update($context->logged_user);
				}
				if ($result < 0) {
					$error++;
					$context->setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			if ($error) {
				$action = 'edit';
			} else {
				$action = 'view';
				$urltogo = $backtopage ? str_replace('__ID__', (string) $object->id, $backtopage) : $backurlforlist;
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (string) $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
				if ($urltogo && empty($noback)) {
					header("Location: " . $urltogo);
					exit;
				}
			}
		}

		$this->object = $object;
		$this->action = $action;
	}
}
