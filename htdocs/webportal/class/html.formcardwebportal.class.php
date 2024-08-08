<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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

/**
 *    Class to manage generation of HTML components
 *    Only common components for WebPortal must be here.
 *
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
	 * @var string Back to page for JS fields
	 */
	public $backtopagejsfields = '';

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
	 * @var Form  Instance of the Form
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
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->form = new FormWebPortal($this->db);
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

		// load module libraries
		dol_include_once('/webportal/class/webportal' . $elementEn . '.class.php');

		// Load translation files required by the page
		$langs->loadLangs(array('website', 'other'));

		// Get parameters
		//$id = $id > 0 ? $id : GETPOST('id', 'int');
		$ref = GETPOST('ref', 'alpha');
		$action = GETPOST('action', 'aZ09');
		$confirm = GETPOST('confirm', 'alpha');
		$cancel = GETPOST('cancel', 'aZ09');
		$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'webportal' . $elementEn . 'card'; // To manage different context of search
		$backtopage = GETPOST('backtopage', 'alpha');                    // if not set, a default page will be used
		$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');    // if not set, $backtopage will be used
		$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');

		// Initialize a technical objects
		$object = new $objectclass($this->db);
		//$extrafields = new ExtraFields($db);
		$hookmanager->initHooks(array('webportal' . $elementEn . 'card', 'globalcard')); // Note that conf->hooks_modules contains array

		// Fetch optionals attributes and labels
		//$extrafields->fetch_name_optionals_label($object->table_element);
		//$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

		if (empty($action) && empty($id) && empty($ref)) {
			$action = 'view';
		}

		// Load object
		include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

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
		$this->backtopagejsfields = $backtopagejsfields;
		$this->cancel = $cancel;
		$this->elementEn = $elementEn;
		$this->id = $id;
		$this->object = $object;
		$this->permissiontoread = $permissiontoread;
		$this->permissiontoadd = $permissiontoadd;
		$this->permissiontodelete = $permissiontodelete;
		$this->permissionnote = $permissionnote;
		$this->permissiondellink = $permissiondellink;
		$this->titleKey = $objectclass . 'CardTitle';
		$this->ref = $ref;
	}

	/**
	 * Do actions
	 *
	 * @return	void
	 */
	public function doActions()
	{
		global $langs;

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

		$error = 0;

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
			foreach ($object->fields as $key => $val) {
				// Check if field was submitted to be edited
				if ($object->fields[$key]['type'] == 'duration') {
					if (!GETPOSTISSET($key . 'hour') || !GETPOSTISSET($key . 'min')) {
						continue; // The field was not submitted to be saved
					}
				} elseif ($object->fields[$key]['type'] == 'boolean') {
					if (!GETPOSTISSET($key)) {
						$object->$key = 0; // use 0 instead null if the field is defined as not null
						continue;
					}
				} else {
					if (!GETPOSTISSET($key) && !preg_match('/^chkbxlst:/', $object->fields[$key]['type']) && $object->fields[$key]['type'] !== 'checkbox') {
						continue; // The field was not submitted to be saved
					}
				}
				// Ignore special fields
				if (in_array($key, array('rowid', 'entity', 'import_key'))) {
					continue;
				}
				if (in_array($key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
					if (!in_array(abs($val['visible']), array(1, 3, 4))) {
						continue; // Only 1 and 3 and 4, that are cases to update
					}
				}

				// Set value to update
				if (preg_match('/^text/', $object->fields[$key]['type'])) {
					$tmparray = explode(':', $object->fields[$key]['type']);
					if (!empty($tmparray[1])) {
						$value = GETPOST($key, $tmparray[1]);
					} else {
						$value = GETPOST($key, 'nohtml');
					}
				} elseif (preg_match('/^html/', $object->fields[$key]['type'])) {
					$tmparray = explode(':', $object->fields[$key]['type']);
					if (!empty($tmparray[1])) {
						$value = GETPOST($key, $tmparray[1]);
					} else {
						$value = GETPOST($key, 'restricthtml');
					}
				} elseif (in_array($object->fields[$key]['type'], array('date', 'datetime'))) {
					$postDate = GETPOST($key, 'alphanohtml');
					// extract date YYYY-MM-DD for year, month and day
					$dateArr = explode('-', $postDate);
					$dateYear = 0;
					$dateMonth = 0;
					$dateDay = 0;
					if (count($dateArr) == 3) {
						$dateYear = (int) $dateArr[0];
						$dateMonth = (int) $dateArr[1];
						$dateDay = (int) $dateArr[2];
					}
					// extract time HH:ii:ss for hours, minutes and seconds
					$postTime = GETPOST($key . '_time', 'alphanohtml');
					$timeArr = explode(':', $postTime);
					$timeHours = 12;
					$timeMinutes = 0;
					$timeSeconds = 0;
					if (!empty($timeArr)) {
						if (isset($timeArr[0])) {
							$timeHours = (int) $timeArr[0];
						}
						if (isset($timeArr[1])) {
							$timeMinutes = (int) $timeArr[1];
						}
						if (isset($timeArr[2])) {
							$timeSeconds = (int) $timeArr[2];
						}
					}
					$value = dol_mktime($timeHours, $timeMinutes, $timeSeconds, $dateMonth, $dateDay, $dateYear);
				} elseif ($object->fields[$key]['type'] == 'duration') {
					if (GETPOSTINT($key . 'hour') != '' || GETPOSTINT($key . 'min') != '') {
						$value = 60 * 60 * GETPOSTINT($key . 'hour') + 60 * GETPOSTINT($key . 'min');
					} else {
						$value = '';
					}
				} elseif (preg_match('/^(integer|price|real|double)/', $object->fields[$key]['type'])) {
					$value = price2num(GETPOST($key, 'alphanohtml')); // To fix decimal separator according to lang setup
				} elseif ($object->fields[$key]['type'] == 'boolean') {
					$value = ((GETPOST($key, 'aZ09') == 'on' || GETPOST($key, 'aZ09') == '1') ? 1 : 0);
					//}
					//elseif ($object->fields[$key]['type'] == 'reference') {
					//    $value = array_keys($object->param_list)[GETPOST($key)].','.GETPOST($key.'2');
				} elseif (preg_match('/^chkbxlst:/', $object->fields[$key]['type']) || $object->fields[$key]['type'] == 'checkbox') {
					$value = '';
					$values_arr = GETPOST($key, 'array');
					if (!empty($values_arr)) {
						$value = implode(',', $values_arr);
					}
				} else {
					if ($key == 'lang') {
						$value = GETPOST($key, 'aZ09');
					} else {
						$value = GETPOST($key, 'alphanohtml');
					}
				}
				if (preg_match('/^integer:/i', $object->fields[$key]['type']) && $value == '-1') {
					$value = ''; // This is an implicit foreign key field
				}
				if (!empty($object->fields[$key]['foreignkey']) && $value == '-1') {
					$value = ''; // This is an explicit foreign key field
				}

				$object->$key = $value;
				if (!empty($val['notnull']) && $val['notnull'] > 0 && $object->$key == '' && is_null($val['default'])) {
					$error++;
					$context->setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label'])), null, 'errors');
				}

				// Validation of fields values
				if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 || getDolGlobalString('MAIN_ACTIVATE_VALIDATION_RESULT')) {
					if (!$error && !empty($val['validate']) && is_callable(array($object, 'validateField'))) {
						if (!$object->validateField($object->fields, $key, $value)) {
							$error++;
						}
					}
				}

				if (isModEnabled('category')) {
					$categories = GETPOST('categories', 'array');
					if (method_exists($object, 'setCategories')) {
						$object->setCategories($categories);
					}
				}
			}

			// Fill array 'array_options' with data from add form
			//if (!$error) {
			//    $ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			//    if ($ret < 0) {
			//        $error++;
			//    }
			//}

			if (!$error) {
				$result = $object->update($context->logged_user);
				if ($result >= 0) {
					$action = 'view';
					$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
					$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (string) $object->id, $urltogo); // New method to autoselect project after a New on another form object creation
					if ($urltogo && empty($noback)) {
						header("Location: " . $urltogo);
						exit;
					}
				} else {
					$error++;
					// Creation KO
					$context->setEventMessages($object->error, $object->errors, 'errors');
					$action = 'edit';
				}
			} else {
				$action = 'edit';
			}
		}

		$this->object = $object;
		$this->action = $action;
	}

	/**
	 * Html for header
	 *
	 * @param	Context	$context	Context object
	 * @return	string
	 */
	protected function header($context)
	{
		global $langs;

		$html = '';

		// initialize
		$object = $this->object;
		$addgendertxt = '';
		//if (property_exists($object, 'gender') && !empty($object->gender)) {
		//    switch ($object->gender) {
		//        case 'man':
		//            $addgendertxt .= '<i class="fas fa-mars"></i>';
		//            break;
		//        case 'woman':
		//            $addgendertxt .= '<i class="fas fa-venus"></i>';
		//            break;
		//        case 'other':
		//            $addgendertxt .= '<i class="fas fa-transgender"></i>';
		//            break;
		//    }
		//}

		$html .= '<!-- html.formcardwebportal.class.php -->';
		$html .= '<header>';

		// Left block - begin
		$html .= '<div class="header-card-left-block inline-block" style="width: 75%;">';
		$html .= '<div>';

		// logo or photo
		$form = new Form($this->db);
		$html .= '<div class="inline-block floatleft valignmiddle">';
		$html .= '<div class="floatleft inline-block valignmiddle divphotoref">';
		$html .= $form->showphoto('memberphoto', $object, 0, 0, 0, 'photowithmargin photoref', 'small', 1, 0, 1);
		//include DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
		//$html .= getImagePublicURLOfObject($object, 1, '_small');
		$html .= '</div>';
		$html .= '</div>';

		// main information - begin
		$html .= '<div class="header-card-main-information inline-block valignmiddle">';
		// ref
		$html .= '<div><strong>' . $langs->trans("Ref").' : '.dol_escape_htmltag($object->ref) . '</strong></div>';
		// full name
		$fullname = '';
		if (method_exists($object, 'getFullName')) {
			$fullname = $object->getFullName($langs);
		}
		$html .= '<div><strong>';
		if ($object->element == 'member') {
			if ($object->morphy == 'mor' && !empty($object->societe)) {
				$html .= dol_htmlentities($object->societe);
				$html .= (!empty($fullname) && $object->societe != $fullname) ? ' (' . dol_htmlentities($fullname) . $addgendertxt . ')' : '';
			} else {
				$html .= dol_htmlentities($fullname) . $addgendertxt;
				if (empty($object->fk_soc)) {
					$html .= (!empty($object->societe) && $object->societe != $fullname) ? ' (' . dol_htmlentities($object->societe) . ')' : '';
				}
			}
		} else {
			$html .= dol_htmlentities(!empty($object->ref) ? $object->ref : '');
		}
		$html .= '</strong></div>';
		// address
		if (method_exists($object, 'getBannerAddressForWebPortal')) {
			$moreaddress = $object->getBannerAddressForWebPortal('refaddress');
			if ($moreaddress) {
				$html .= '<div class="refidno refaddress">';
				$html .= $moreaddress;
				$html .= '</div>';
			}
		}
		$html .= '</div>';
		// main information - end

		$html .= '</div>';
		$html .= '</div>';
		// Left block - end

		// Right block - begin
		$html .= '<div class="header-card-right-block inline-block" style="width: 24%;">';
		// show status
		$htmlStatus = $object->getLibStatut(6);
		if (empty($htmlStatus) || $htmlStatus == $object->getLibStatut(3)) {
			$htmlStatus = $object->getLibStatut(5);
		}
		$html .= $htmlStatus;
		$html .= '</div>';
		// Right block - end

		$html .= '</header>';

		return $html;
	}

	/**
	 * Html for body (view mode)
	 * @param	string	$keyforbreak	[=''] Key for break left block
	 * @return	string	Html for body
	 */
	protected function bodyView($keyforbreak = '')
	{
		global $langs;

		$html = '';

		// initialize
		$object = $this->object;

		$object->fields = dol_sort_array($object->fields, 'position');

		// separate fields to show on the left and on the right
		$fieldShowList = array();
		foreach ($object->fields as $key => $val) {
			// discard if it's a hidden field on form
			if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
				continue;
			}

			if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
				continue; // we don't want this field
			}

			if (!empty($val['showonheader'])) {
				continue; // already on header
			}

			$fieldShowList[$key] = $val;
		}

		$nbFieldShow = count($fieldShowList);
		$lastKeyFieldLeft = $keyforbreak;
		$lastNumFieldLeft = 0;
		if ($lastKeyFieldLeft == '') {
			$lastNumFieldLeft = ceil($nbFieldShow / 2);
		}
		$numField = 0;
		$html .= '<div class="grid">';
		$html .= '<div class="card-left">';
		foreach ($object->fields as $key => $val) {
			if (!array_key_exists($key, $fieldShowList)) {
				continue; // not to show
			}

			$value = $object->$key;

			$html .= '<div class="grid field_' . $key . '">';

			$html .= '<div class="' . (empty($val['tdcss']) ? '' : $val['tdcss']) . ' fieldname_' . $key;
			$html .= '">';
			$labeltoshow = '';
			$labeltoshow .= '<strong>' . $langs->trans($val['label']) . '</strong>';
			$html .= $labeltoshow;
			$html .= '</div>';

			$html .= '<div class="valuefield fieldname_' . $key;
			if (!empty($val['cssview'])) {
				$html .= ' ' . $val['cssview'];
			}
			$html .= '">';
			if ($key == 'lang') {
				$langs->load('languages');
				$labellang = ($value ? $langs->trans('Language_' . $value) : '');
				//$html .= picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
				$html .= $labellang;
			} else {
				$html .= $this->form->showOutputFieldForObject($object, $val, $key, $value, '', '', '', 0);
			}
			$html .= '</div>';

			$html .= '</div>';

			$numField++;

			// fields on the right
			$cardRight = false;
			if ($keyforbreak != '') {
				if ($key == $keyforbreak) {
					$cardRight = true;
				}
			} else {
				if ($numField == $lastNumFieldLeft) {
					$cardRight = true;
				}
			}
			if ($cardRight) {
				$html .= '</div>';
				$html .= '<div class="card-right">';
			}
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 *  Html for body (edit mode)
	 *
	 * @return	string
	 */
	protected function bodyEdit()
	{
		global $langs;

		$html = '';

		// initialize
		$object = $this->object;

		$object->fields = dol_sort_array($object->fields, 'position');

		foreach ($object->fields as $key => $val) {
			// Discard if filed is a hidden field on form
			if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
				continue;
			}

			if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
				continue; // We don't want this field
			}

			$html .= '<div class="grid field_' . $key . '">';
			$html .= '<div class="titlefieldcreate';
			if (isset($val['notnull']) && $val['notnull'] > 0) {
				$html .= ' required';
			}
			$html .= '">';
			$html .= $langs->trans($val['label']);
			$html .= '</div>';

			$html .= '<div class="valuefieldcreate">';
			if (in_array($val['type'], array('int', 'integer'))) {
				$value = GETPOSTISSET($key) ? GETPOSTINT($key) : $object->$key;
			} elseif ($val['type'] == 'double') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
			} elseif (preg_match('/^text/', $val['type'])) {
				$tmparray = explode(':', $val['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'nohtml';
				}
				$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
			} elseif (preg_match('/^html/', $val['type'])) {
				$tmparray = explode(':', $val['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'restricthtml';
				}
				$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
			} elseif (in_array($val['type'], array('date', 'datetime'))) {
				$isPostDate = GETPOSTISSET($key);
				$isPostTime = GETPOSTISSET($key . '_time');
				if ($isPostDate) {
					$postDate = GETPOST($key, 'alphanohtml');
					if ($isPostTime) {
						$postTime = GETPOST($key . '_time', 'alphanohtml') . ':00';
					} else {
						$postTime = '00:00:00';
					}
					$valueDateTimeStr = $postDate . ' ' . $postTime;
				} else {
					// format date timestamp to YYYY-MM-DD HH:ii:ss
					$valueDateTimeStr = dol_print_date($object->$key, '%Y-%m-%d %H:%M:%S');
				}

				$value = $valueDateTimeStr;
			} elseif ($val['type'] == 'price') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
			} elseif ($key == 'lang') {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
			} else {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : $object->$key;
			}

			if (!empty($val['noteditable'])) {
				$html .= $this->form->showOutputFieldForObject($object, $val, $key, $value, '', '', '', 0);
			} else {
				$html .= $this->form->showInputField($val, $key, $value, '', '', '', '');
			}
			$html .= '</div>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Html for footer
	 *
	 * @return	string
	 */
	protected function footer()
	{
		$html = '';
		$html .= '<footer>';
		$html .= '</footer>';

		return $html;
	}

	/**
	 * Card for an element in the page context
	 *
	 * @param	Context		$context	Context object
	 * @return	string		Html output
	 */
	public function elementCard($context)
	{
		global $hookmanager, $langs;

		$html = '<!-- elementCard -->';

		// initialize
		$action = $this->action;
		$backtopage = $this->backtopage;
		$backtopageforcancel = $this->backtopageforcancel;
		//$backtopagejsfields = $this->backtopagejsfields;
		//$elementEn = $this->elementEn;
		$id = $this->id;
		$object = $this->object;
		//$permissiontoread = $this->permissiontoread;
		$permissiontoadd = $this->permissiontoadd;
		$ref = $this->ref;
		$titleKey = $this->titleKey;
		$title = $langs->trans($titleKey);

		// Part to edit record
		if (($id || $ref) && $action == 'edit') {
			$html .= '<article>';
			//$html .= load_fiche_titre($title, '', 'object_'.$object->picto);
			$html .= '<header>';
			$html .= '<h2>' . $title . '</h2>';
			$html .= '</header>';

			$url_file = $context->getControllerUrl($context->controller, '', false);
			$html .= '<form method="POST" action="' . $url_file . '">';
			$html .= $context->getFormToken();
			$html .= '<input type="hidden" name="action" value="update">';
			$html .= '<input type="hidden" name="id" value="' . $object->id . '">';
			if ($backtopage) {
				$html .= '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
			}
			if ($backtopageforcancel) {
				$html .= '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
			}

			//$html .= '<table>'."\n";
			// Common attributes
			//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';
			$html .= $this->bodyEdit();

			// Other attributes
			//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';
			//$html  .= '</table>';

			// Save and Cancel buttons
			$html .= '<div class="grid">';
			$html .= '<div><input type="submit" name="save" role="button" value="' . dol_escape_htmltag($langs->trans('Save')) . '" /></div>';
			$html .= '<div><input type="submit" name="cancel" role="button" value="' . dol_escape_htmltag($langs->trans('Cancel')) . '" /></div>';
			$html .= '</div>';

			$html .= '</form>';
			$html .= '</article>';
		}

		// Part to show record
		if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
			$html .= '<article>';

			$formconfirm = '';

			// Call Hook formConfirm
			$parameters = array('formConfirm' => $formconfirm);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				$formconfirm .= $hookmanager->resPrint;
			} elseif ($reshook > 0) {
				$formconfirm = $hookmanager->resPrint;
			}

			// Print form confirm
			$html .= $formconfirm;

			// Object card
			// ------------------------------------------------------------
			$html .= $this->header($context);

			// Common attributes
			$keyforbreak = '';
			$html .= $this->bodyView($keyforbreak);

			// Other attributes. Fields from hook formObjectOptions and Extrafields.
			//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			//$html .= $this->footer();
			$html .= '</article>';

			// Buttons for actions
			if ($action != 'presend' && $action != 'editline') {
				$html .= '<div>' . "\n";
				$parameters = array();
				$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				if (empty($reshook)) {
					if ($permissiontoadd) {
						$url_file = $context->getControllerUrl($context->controller, '', false);
						$html .= '<a href="' . $url_file . '&id=' . $object->id . '&action=edit" role="button">' . $langs->trans('Modify') . '</a>';
					}
				}
				$html .= '</div>' . "\n";
			}
		}

		return $html;
	}
}
