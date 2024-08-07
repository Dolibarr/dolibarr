<?php
/* Copyright (C) 2021  John BOTELLA    <john.botella@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * This class help you create setup render
 */
class FormSetup
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/** @var int */
	public $entity;

	/** @var FormSetupItem[]  */
	public $items = array();

	/**
	 * @var int
	 */
	public $setupNotEmpty = 0;

	/** @var Translate */
	public $langs;

	/** @var Form */
	public $form;

	/** @var int */
	protected $maxItemRank;

	/**
	 * this is an html string display before output form
	 * @var string
	 */
	public $htmlBeforeOutputForm = '';

	/**
	 * this is an html string display after output form
	 * @var string
	 */
	public $htmlAfterOutputForm = '';

	/**
	 * this is an html string display on buttons zone
	 * @var string
	 */
	public $htmlOutputMoreButton = '';


	/**
	 *
	 * @var array
	 */
	public $formAttributes = array(
		'action' => '', // set in __construct
		'method' => 'POST'
	);

	/**
	 * an list of hidden inputs used only in edit mode
	 * @var array
	 */
	public $formHiddenInputs = array();

	/**
	 * @var string[] $errors
	 */
	public $errors = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param Translate $outputLangs if needed can use another lang
	 */
	public function __construct($db, $outputLangs = null)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->form = new Form($this->db);
		$this->formAttributes['action'] = $_SERVER["PHP_SELF"];

		$this->formHiddenInputs['token'] = newToken();
		$this->formHiddenInputs['action'] = 'update';

		$this->entity = (is_null($this->entity) ? $conf->entity : $this->entity);

		if ($outputLangs) {
			$this->langs = $outputLangs;
		} else {
			$this->langs = $langs;
		}
	}

	/**
	 * Generate an attributes string form an input array
	 *
	 * @param 	array 	$attributes 	an array of attributes keys and values,
	 * @return 	string					attribute string
	 */
	public static function generateAttributesStringFromArray($attributes)
	{
		$Aattr = array();
		if (is_array($attributes)) {
			foreach ($attributes as $attribute => $value) {
				if (is_array($value) || is_object($value)) {
					continue;
				}
				$Aattr[] = $attribute.'="'.dol_escape_htmltag($value).'"';
			}
		}

		return !empty($Aattr) ? implode(' ', $Aattr) : '';
	}


	/**
	 * Generate the form (in read or edit mode depending on $editMode)
	 *
	 * @param 	bool 	$editMode 	true will display output on edit mod
	 * @param	bool	$hideTitle	True to hide the first title line
	 * @return 	string				Html output
	 */
	public function generateOutput($editMode = false, $hideTitle = false)
	{
		global $hookmanager, $action;

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

		$parameters = array(
			'editMode' => $editMode
		);
		$reshook = $hookmanager->executeHooks('formSetupBeforeGenerateOutput', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		} else {
			$out = '<!-- Start generateOutput from FormSetup class  -->';
			$out .= $this->htmlBeforeOutputForm;

			if ($editMode) {
				$out .= '<form ' . self::generateAttributesStringFromArray($this->formAttributes) . ' >';

				// generate hidden values from $this->formHiddenInputs
				if (!empty($this->formHiddenInputs) && is_array($this->formHiddenInputs)) {
					foreach ($this->formHiddenInputs as $hiddenKey => $hiddenValue) {
						$out .= '<input type="hidden" name="'.dol_escape_htmltag($hiddenKey).'" value="' . dol_escape_htmltag($hiddenValue) . '">';
					}
				}
			}

			// generate output table
			$out .= $this->generateTableOutput($editMode, $hideTitle);


			$reshook = $hookmanager->executeHooks('formSetupBeforeGenerateOutputButton', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}

			if ($reshook > 0) {
				return $hookmanager->resPrint;
			} elseif ($editMode) {
				$out .= '<div class="form-setup-button-container center">'; // Todo : remove .center by adding style to form-setup-button-container css class in all themes
				$out .= $this->htmlOutputMoreButton;
				$out .= '<input class="button button-save" type="submit" value="' . $this->langs->trans("Save") . '">'; // Todo fix dolibarr style for <button and use <button instead of input
				/*$out .= ' &nbsp;&nbsp; ';
				$out .= '<a class="button button-cancel" type="submit" href="' . $this->formAttributes['action'] . '">'.$this->langs->trans('Cancel').'</a>';
				*/
				$out .= '</div>';
			}

			if ($editMode) {
				$out .= '</form>';
			}

			$out .= $this->htmlAfterOutputForm;

			return $out;
		}
	}

	/**
	 * generateTableOutput
	 *
	 * @param 	bool 	$editMode 	True will display output on edit modECM
	 * @param	bool	$hideTitle	True to hide the first title line
	 * @return 	string				Html output
	 */
	public function generateTableOutput($editMode = false, $hideTitle = false)
	{
		global $hookmanager, $action;
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

		$parameters = array(
			'editMode' => $editMode
		);
		$reshook = $hookmanager->executeHooks('formSetupBeforeGenerateTableOutput', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		} else {
			$out = '<table class="noborder centpercent">';
			if (empty($hideTitle)) {
				$out .= '<thead>';
				$out .= '<tr class="liste_titre">';
				$out .= '	<td>' . $this->langs->trans("Parameter") . '</td>';
				$out .= '	<td>' . $this->langs->trans("Value") . '</td>';
				$out .= '</tr>';
				$out .= '</thead>';
			}

			// Sort items before render
			$this->sortingItems();

			$out .= '<tbody>';
			foreach ($this->items as $item) {
				$out .= $this->generateLineOutput($item, $editMode);
			}
			$out .= '</tbody>';

			$out .= '</table>';
			return $out;
		}
	}

	/**
	 * saveConfFromPost
	 *
	 * @param 	bool 		$noMessageInUpdate display event message on errors and success
	 * @return	int|null    Return -1 if KO, 1 if OK, null if no items
	 */
	public function saveConfFromPost($noMessageInUpdate = false)
	{
		global $hookmanager, $conf;

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formSetupBeforeSaveConfFromPost', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$this->errors = $hookmanager->errors;
			return -1;
		}

		if ($reshook > 0) {
			return $reshook;
		}

		if (empty($this->items)) {
			return null;
		}

		$this->db->begin();
		$error = 0;
		foreach ($this->items as $item) {
			if ($item->getType() == 'yesno' && !empty($conf->use_javascript_ajax)) {
				continue;
			}

			$res = $item->setValueFromPost();
			if ($res > 0) {
				$item->saveConfValue();
			} elseif ($res < 0) {
				$error++;
				break;
			}
		}

		if (!$error) {
			$this->db->commit();
			if (empty($noMessageInUpdate)) {
				setEventMessages($this->langs->trans("SetupSaved"), null);
			}
			return 1;
		} else {
			$this->db->rollback();
			if (empty($noMessageInUpdate)) {
				setEventMessages($this->langs->trans("SetupNotSaved"), null, 'errors');
			}
			return -1;
		}
	}

	/**
	 * generateLineOutput
	 *
	 * @param 	FormSetupItem 	$item 		the setup item
	 * @param 	bool 			$editMode 	Display as edit mod
	 * @return 	string 						the html output for an setup item
	 */
	public function generateLineOutput($item, $editMode = false)
	{
		$out = '';
		if ($item->enabled == 1) {
			$trClass = 'oddeven';
			if ($item->getType() == 'title') {
				$trClass = 'liste_titre';
			}
			if (!empty($item->fieldParams['trClass'])) {
				$trClass .= ' '.$item->fieldParams['trClass'];
			}

			$this->setupNotEmpty++;
			$out .= '<tr class="'.$trClass.'">';

			$out .= '<td class="col-setup-title">';
			$out .= '<span id="helplink'.$item->confKey.'" class="spanforparamtooltip">';
			$out .= $this->form->textwithpicto($item->getNameText(), $item->getHelpText(), 1, 'info', '', 0, 3, 'tootips'.$item->confKey);
			$out .= '</span>';
			$out .= '</td>';

			$out .= '<td>';

			if ($editMode) {
				$out .= $item->generateInputField();
			} else {
				$out .= $item->generateOutputField();
			}

			if (!empty($item->errors)) {
				// TODO : move set event message in a methode to be called by cards not by this class
				setEventMessages(null, $item->errors, 'errors');
			}

			$out .= '</td>';
			$out .= '</tr>';
		}

		return $out;
	}


	/**
	 * Method used to test  module builder conversion to this form usage
	 *
	 * @param 	array 	$params 	an array of arrays of params from old modulBuilder params
	 * @return 	boolean
	 */
	public function addItemsFromParamsArray($params)
	{
		if (!is_array($params) || empty($params)) {
			return false;
		}
		foreach ($params as $confKey => $param) {
			$this->addItemFromParams($confKey, $param); // todo manage error
		}
		return true;
	}


	/**
	 * From old
	 * Method was used to test  module builder conversion to this form usage.
	 *
	 * @param 	string 	$confKey 	the conf name to store
	 * @param 	array 	$params 	an array of params from old modulBuilder params
	 * @return 	bool
	 */
	public function addItemFromParams($confKey, $params)
	{
		if (empty($confKey) || empty($params['type'])) {
			return false;
		}

		/*
		 * Example from old module builder setup page
		 * 	// 'MYMODULE_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1),
			// 'MYMODULE_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
			//'MYMODULE_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
			//'MYMODULE_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
			//'MYMODULE_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
			//'MYMODULE_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
			//'MYMODULE_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
			//'MYMODULE_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
		 */

		$item = new FormSetupItem($confKey);
		// need to be ignored from scrutinizer setTypeFromTypeString was created as deprecated to incite developer to use object oriented usage
		// @phan-suppress-next-line PhanDeprecatedFunction
		/** @scrutinizer ignore-deprecated */ $item->setTypeFromTypeString($params['type']);

		if (!empty($params['enabled'])) {
			$item->enabled = $params['enabled'];
		}

		if (!empty($params['css'])) {
			$item->cssClass = $params['css'];
		}

		$this->items[$item->confKey] = $item;

		return true;
	}

	/**
	 * Used to export param array for /core/actions_setmoduleoptions.inc.php template
	 * Method exists only for manage setup conversion
	 *
	 * @return array $arrayofparameters for /core/actions_setmoduleoptions.inc.php
	 */
	public function exportItemsAsParamsArray()
	{
		$arrayofparameters = array();
		foreach ($this->items as $item) {
			$arrayofparameters[$item->confKey] = array(
				'type' => $item->getType(),
				'enabled' => $item->enabled
			);
		}

		return $arrayofparameters;
	}

	/**
	 * Reload for each item default conf
	 * note: this will override custom configuration
	 *
	 * @return bool
	 */
	public function reloadConfs()
	{
		if (!array($this->items)) {
			return false;
		}
		foreach ($this->items as $item) {
			$item->loadValueFromConf();
		}

		return true;
	}


	/**
	 * Create a new item
	 * The target is useful with hooks : that allow externals modules to add setup items on good place
	 *
	 * @param string	$confKey 				the conf key used in database
	 * @param string	$targetItemKey    		target item used to place the new item beside
	 * @param bool		$insertAfterTarget		insert before or after target item ?
	 * @return FormSetupItem 					the new setup item created
	 */
	public function newItem($confKey, $targetItemKey = '', $insertAfterTarget = false)
	{
		$item = new FormSetupItem($confKey);

		$item->entity = $this->entity;

		// set item rank if not defined as last item
		if (empty($item->rank)) {
			$item->rank = $this->getCurentItemMaxRank() + 1;
			$this->setItemMaxRank($item->rank); // set new max rank if needed
		}

		// try to get rank from target column, this will override item->rank
		if (!empty($targetItemKey)) {
			if (isset($this->items[$targetItemKey])) {
				$targetItem = $this->items[$targetItemKey];
				$item->rank = $targetItem->rank; // $targetItem->rank will be increase after
				if ($targetItem->rank >= 0 && $insertAfterTarget) {
					$item->rank++;
				}
			}

			// calc new rank for each item to make place for new item
			foreach ($this->items as $fItem) {
				if ($item->rank <= $fItem->rank) {
					$fItem->rank += 1;
					$this->setItemMaxRank($fItem->rank); // set new max rank if needed
				}
			}
		}

		$this->items[$item->confKey] = $item;
		return $this->items[$item->confKey];
	}

	/**
	 * Sort items according to rank
	 *
	 * @return bool
	 */
	public function sortingItems()
	{
		// Sorting
		return uasort($this->items, array($this, 'itemSort'));
	}

	/**
	 * getCurentItemMaxRank
	 *
	 * @param bool $cache To use cache or not
	 * @return int
	 */
	public function getCurentItemMaxRank($cache = true)
	{
		if (empty($this->items)) {
			return 0;
		}

		if ($cache && $this->maxItemRank > 0) {
			return $this->maxItemRank;
		}

		$this->maxItemRank = 0;
		foreach ($this->items as $item) {
			$this->maxItemRank = max($this->maxItemRank, $item->rank);
		}

		return $this->maxItemRank;
	}


	/**
	 * set new max rank if needed
	 *
	 * @param 	int 		$rank 	the item rank
	 * @return 	int|void			new max rank
	 */
	public function setItemMaxRank($rank)
	{
		$this->maxItemRank = max($this->maxItemRank, $rank);
	}


	/**
	 * get item position rank from item key
	 *
	 * @param	string		$itemKey    	the item key
	 * @return	int         				rank on success and -1 on error
	 */
	public function getLineRank($itemKey)
	{
		if (!isset($this->items[$itemKey]->rank)) {
			return -1;
		}
		return  $this->items[$itemKey]->rank;
	}


	/**
	 *  uasort callback function to Sort params items
	 *
	 *  @param	FormSetupItem	$a  formSetup item
	 *  @param	FormSetupItem	$b  formSetup item
	 *  @return	int					Return compare result
	 */
	public function itemSort(FormSetupItem $a, FormSetupItem $b)
	{
		if (empty($a->rank)) {
			$a->rank = 0;
		}
		if (empty($b->rank)) {
			$b->rank = 0;
		}
		if ($a->rank == $b->rank) {
			return 0;
		}
		return ($a->rank < $b->rank) ? -1 : 1;
	}
}


/**
 * This class help to create item for class formSetup
 */
class FormSetupItem
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/** @var Translate */
	public $langs;

	/** @var int */
	public $entity;

	/** @var Form */
	public $form;


	/** @var string $confKey the conf key used in database */
	public $confKey;

	/** @var string|false $nameText */
	public $nameText = false;

	/** @var string $helpText */
	public $helpText = '';

	/** @var string $picto */
	public $picto = '';

	/** @var string $fieldValue */
	public $fieldValue;

	/** @var string $defaultFieldValue */
	public $defaultFieldValue = null;

	/** @var array $fieldAttr  fields attribute only for compatible fields like input text */
	public $fieldAttr = array();

	/** @var bool|string set this var to override field output will override $fieldInputOverride and $fieldOutputOverride too */
	public $fieldOverride = false;

	/** @var bool|string set this var to override field input */
	public $fieldInputOverride = false;

	/** @var bool|string set this var to override field output */
	public $fieldOutputOverride = false;

	/** @var int $rank  */
	public $rank = 0;

	/** @var array set this var for options on select and multiselect items   */
	public $fieldOptions = array();

	/** @var array set this var to add more parameters */
	public $fieldParams = array();

	/** @var callable $saveCallBack  */
	public $saveCallBack;

	/** @var callable $setValueFromPostCallBack  */
	public $setValueFromPostCallBack;

	/**
	 * @var string[] $errors
	 */
	public $errors = array();

	/**
	 * TODO each type must have setAs{type} method to help configuration
	 *   And set var as protected when its done configuration must be done by method
	 *   this is important for retrocompatibility of futures versions
	 * @var string $type  'string', 'textarea', 'category:'.Categorie::TYPE_CUSTOMER', 'emailtemplate', 'thirdparty_type'
	 */
	protected $type = 'string';

	public $enabled = 1;

	/**
	 * @var string	The css to use on the input field of item
	 */
	public $cssClass = '';

	/**
	 * Constructor
	 *
	 * @param string	$confKey	the conf key used in database
	 */
	public function __construct($confKey)
	{
		global $langs, $db, $conf, $form;
		$this->db = $db;

		if (!empty($form) && is_object($form) && get_class($form) == 'Form') { // the form class has a cache inside so I am using it to optimize
			$this->form = $form;
		} else {
			$this->form = new Form($this->db);
		}

		$this->langs = $langs;
		$this->entity = (is_null($this->entity) ? $conf->entity : ((int) $this->entity));

		$this->confKey = $confKey;
		$this->loadValueFromConf();
	}

	/**
	 * load conf value from databases
	 *
	 * @return bool
	 */
	public function loadValueFromConf()
	{
		global $conf;
		if (isset($conf->global->{$this->confKey})) {
			$this->fieldValue = getDolGlobalString($this->confKey);
			return true;
		} else {
			$this->fieldValue = '';
			return false;
		}
	}

	/**
	 * Reload conf value from databases is an alias of loadValueFromConf
	 *
	 * @deprecated
	 * @return bool
	 */
	public function reloadValueFromConf()
	{
		return $this->loadValueFromConf();
	}


	/**
	 * Save const value based on htdocs/core/actions_setmoduleoptions.inc.php
	 *
	 * @return     int         			-1 if KO, 1 if OK
	 */
	public function saveConfValue()
	{
		global $hookmanager;

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formSetupBeforeSaveConfValue', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$this->setErrors($hookmanager->errors);
			return -1;
		}

		if ($reshook > 0) {
			return $reshook;
		}


		if (!empty($this->saveCallBack) && is_callable($this->saveCallBack)) {
			return call_user_func($this->saveCallBack, $this);
		}

		// Modify constant only if key was posted (avoid resetting key to the null value)
		if ($this->type != 'title') {
			$result = dolibarr_set_const($this->db, $this->confKey, $this->fieldValue, 'chaine', 0, '', $this->entity);
			if ($result < 0) {
				return -1;
			} else {
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Set an override function for saving data
	 *
	 * @param callable $callBack a callable function
	 * @return void
	 */
	public function setSaveCallBack(callable $callBack)
	{
		$this->saveCallBack = $callBack;
	}

	/**
	 * Set an override function for get data from post
	 *
	 * @param callable $callBack a callable function
	 * @return void
	 */
	public function setValueFromPostCallBack(callable $callBack)
	{
		$this->setValueFromPostCallBack = $callBack;
	}

	/**
	 * Save const value based on htdocs/core/actions_setmoduleoptions.inc.php
	 *
	 * @return     int         			-1 if KO, 0  nothing to do , 1 if OK
	 */
	public function setValueFromPost()
	{
		if (!empty($this->setValueFromPostCallBack) && is_callable($this->setValueFromPostCallBack)) {
			return call_user_func($this->setValueFromPostCallBack);
		}

		// Modify constant only if key was posted (avoid resetting key to the null value)
		if ($this->type != 'title') {
			if (preg_match('/category:/', $this->type)) {
				if (GETPOSTINT($this->confKey) == '-1') {
					$val_const = '';
				} else {
					$val_const = GETPOSTINT($this->confKey);
				}
			} elseif ($this->type == 'multiselect') {
				$val = GETPOST($this->confKey, 'array');
				if ($val && is_array($val)) {
					$val_const = implode(',', $val);
				} else {
					$val_const = '';
				}
			} elseif ($this->type == 'html') {
				$val_const = GETPOST($this->confKey, 'restricthtml');
			} else {
				$val_const = GETPOST($this->confKey, 'alpha');
			}

			// TODO add value check with class validate
			$this->fieldValue = $val_const;

			return 1;
		}

		return 0;
	}

	/**
	 * Get help text or generate it
	 *
	 * @return int|string
	 */
	public function getHelpText()
	{
		if (!empty($this->helpText)) {
			return $this->helpText;
		}
		return (($this->langs->trans($this->confKey . 'Tooltip') != $this->confKey . 'Tooltip') ? $this->langs->trans($this->confKey . 'Tooltip') : '');
	}

	/**
	 * Get field name text or generate it
	 *
	 * @return false|int|string
	 */
	public function getNameText()
	{
		if (!empty($this->nameText)) {
			return $this->nameText;
		}
		$out = (($this->langs->trans($this->confKey) != $this->confKey) ? $this->langs->trans($this->confKey) : $this->langs->trans('MissingTranslationForConfKey', $this->confKey));

		// if conf defined on entity 0, prepend a picto to indicate it will apply across all entities
		if (isModEnabled('multicompany') && $this->entity == 0) {
			$out = img_picto($this->langs->trans('AllEntities'), 'fa-globe-americas em088 opacityhigh') . '&nbsp;' . $out;
		}

		return $out;
	}

	/**
	 * generate input field
	 *
	 * @return bool|string
	 */
	public function generateInputField()
	{
		global $conf;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		if (!empty($this->fieldInputOverride)) {
			return $this->fieldInputOverride;
		}

		// Set default value
		if (is_null($this->fieldValue)) {
			$this->fieldValue = $this->defaultFieldValue;
		}


		$this->fieldAttr['name'] = $this->confKey;
		$this->fieldAttr['id'] = 'setup-'.$this->confKey;
		$this->fieldAttr['value'] = $this->fieldValue;

		$out = '';

		if ($this->type == 'title') {
			$out .= $this->generateOutputField(); // title have no input
		} elseif ($this->type == 'multiselect') {
			$out .= $this->generateInputFieldMultiSelect();
		} elseif ($this->type == 'select') {
			$out .= $this->generateInputFieldSelect();
		} elseif ($this->type == 'selectUser') {
			$out .= $this->generateInputFieldSelectUser();
		} elseif ($this->type == 'textarea') {
			$out .= $this->generateInputFieldTextarea();
		} elseif ($this->type == 'html') {
			$out .= $this->generateInputFieldHtml();
		} elseif ($this->type == 'color') {
			$out .=  $this->generateInputFieldColor();
		} elseif ($this->type == 'yesno') {
			if (!empty($conf->use_javascript_ajax)) {
				$out .= ajax_constantonoff($this->confKey);
			} else {
				$out .= $this->form->selectyesno($this->confKey, $this->fieldValue, 1);
			}
		} elseif (preg_match('/emailtemplate:/', $this->type)) {
			$out .= $this->generateInputFieldEmailTemplate();
		} elseif (preg_match('/category:/', $this->type)) {
			$out .= $this->generateInputFieldCategories();
		} elseif (preg_match('/thirdparty_type/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
			$formcompany = new FormCompany($this->db);
			$out .= $formcompany->selectProspectCustomerType($this->fieldValue, $this->confKey);
		} elseif ($this->type == 'securekey') {
			$out .= $this->generateInputFieldSecureKey();
		} elseif ($this->type == 'product') {
			if (isModEnabled("product") || isModEnabled("service")) {
				$selected = (empty($this->fieldValue) ? '' : $this->fieldValue);
				$out .= $this->form->select_produits($selected, $this->confKey, '', 0, 0, 1, 2, '', 0, array(), 0, '1', 0, $this->cssClass, 0, '', null, 1);
			}
		} elseif ($this->type == 'selectBankAccount') {
			if (isModEnabled("bank")) {
				$selected = (empty($this->fieldValue) ? '' : $this->fieldValue);
				$out .= $this->form->select_comptes($selected, $this->confKey, 0, '', 0, '', 0, '', 1);
			}
		} elseif ($this->type == 'password') {
			$out .= $this->generateInputFieldPassword('dolibarr');
		} elseif ($this->type == 'genericpassword') {
			$out .= $this->generateInputFieldPassword('generic');
		} else {
			$out .= $this->generateInputFieldText();
		}

		return $out;
	}

	/**
	 * generatec default input field
	 *
	 * @return string
	 */
	public function generateInputFieldText()
	{
		if (empty($this->fieldAttr) || empty($this->fieldAttr['class'])) {
			$this->fieldAttr['class'] = 'flat '.(empty($this->cssClass) ? 'minwidth200' : $this->cssClass);
		}
		return '<input '.FormSetup::generateAttributesStringFromArray($this->fieldAttr).' />';
	}

	/**
	 * generate input field for textarea
	 *
	 * @return string
	 */
	public function generateInputFieldTextarea()
	{
		$out = '<textarea class="flat" name="'.$this->confKey.'" id="'.$this->confKey.'" cols="50" rows="5" wrap="soft">' . "\n";
		$out .= dol_htmlentities($this->fieldValue);
		$out .= "</textarea>\n";
		return $out;
	}

	/**
	 * generate input field for html
	 *
	 * @return string
	 */
	public function generateInputFieldHtml()
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		$doleditor = new DolEditor($this->confKey, $this->fieldValue, '', 160, 'dolibarr_notes', '', false, false, isModEnabled('fckeditor'), ROWS_5, '90%');
		return $doleditor->Create(1);
	}

	/**
	 * generate input field for categories
	 *
	 * @return string
	 */
	public function generateInputFieldCategories()
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		$formother = new FormOther($this->db);

		$tmp = explode(':', $this->type);
		$out = img_picto('', 'category', 'class="pictofixedwidth"');

		$label = 'Categories';
		if ($this->type == 'customer') {
			$label = 'CustomersProspectsCategoriesShort';
		}
		$out .= $formother->select_categories($tmp[1], $this->fieldValue, $this->confKey, 0, $this->langs->trans($label));

		return $out;
	}

	/**
	 * generate input field for email template selector
	 * @return string
	 */
	public function generateInputFieldEmailTemplate()
	{
		global $user;

		$out = '';
		if (preg_match('/emailtemplate:/', $this->type)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
			$formmail = new FormMail($this->db);

			$tmp = explode(':', $this->type);
			$nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang
			$arrayOfMessageName = array();
			if (is_array($formmail->lines_model)) {
				foreach ($formmail->lines_model as $modelMail) {
					$moreonlabel = '';
					if (!empty($arrayOfMessageName[$modelMail->label])) {
						$moreonlabel = ' <span class="opacitymedium">(' . $this->langs->trans("SeveralLangugeVariatFound") . ')</span>';
					}
					// The 'label' is the key that is unique if we exclude the language
					$arrayOfMessageName[$modelMail->id] = $this->langs->trans(preg_replace('/\(|\)/', '', $modelMail->label)) . $moreonlabel;
				}
			}
			$out .= $this->form->selectarray($this->confKey, $arrayOfMessageName, $this->fieldValue, 'None', 0, 0, '', 0, 0, 0, '', '', 1);
		}

		return $out;
	}


	/**
	 * generate input field for secure key
	 *
	 * @return string
	 */
	public function generateInputFieldSecureKey()
	{
		global $conf;
		$out = '<input type="text" class="flat minwidth150'.($this->cssClass ? ' '.$this->cssClass : '').'" id="'.$this->confKey.'" name="'.$this->confKey.'" value="'.(GETPOST($this->confKey, 'alpha') ? GETPOST($this->confKey, 'alpha') : $this->fieldValue).'">';

		if (!empty($conf->use_javascript_ajax) && empty($this->fieldParams['hideGenerateButton'])) {
			$out .= '&nbsp;'.img_picto($this->langs->trans('Generate'), 'refresh', 'id="generate_token'.$this->confKey.'" class="linkobject"');

			// Add button to autosuggest a key
			include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$out .= dolJSToSetRandomPassword($this->confKey, 'generate_token'.$this->confKey);
		}

		return $out;
	}


	/**
	 * generate input field for a password
	 *
	 * @param   string  $type  'dolibarr' (dolibarr password rules apply) or 'generic'
	 *
	 * @return  string
	 */
	public function generateInputFieldPassword($type = 'generic')
	{
		global $conf, $langs, $user;

		$min = 6;
		$max = 50;
		if ($type == 'dolibarr') {
			$gen = getDolGlobalString('USER_PASSWORD_GENERATED', 'standard');
			if ($gen == 'none') {
				$gen = 'standard';
			}
			$nomclass = "modGeneratePass".ucfirst($gen);
			$nomfichier = $nomclass.".class.php";
			require_once DOL_DOCUMENT_ROOT."/core/modules/security/generate/".$nomfichier;
			$genhandler = new $nomclass($this->db, $conf, $langs, $user);
			$min = $genhandler->length;
			$max = $genhandler->length2;
		}
		$out = '<input required="required" type="password" class="flat" id="'.$this->confKey.'" name="'.$this->confKey.'" value="'.(GETPOST($this->confKey, 'alpha') ? GETPOST($this->confKey, 'alpha') : $this->fieldValue).'"';
		if ($min) {
			$out .= ' minlength="' . $min . '"';
		}
		if ($max) {
			$out .= ' maxlength="' . $max . '"';
		}
		$out .= '>';
		return $out;
	}



	/**
	 * generateInputFieldMultiSelect
	 *
	 * @return string
	 */
	public function generateInputFieldMultiSelect()
	{
		$TSelected = array();
		if ($this->fieldValue) {
			$TSelected = explode(',', $this->fieldValue);
		}

		return $this->form->multiselectarray($this->confKey, $this->fieldOptions, $TSelected, 0, 0, '', 0, 0, 'style="min-width:100px"');
	}


	/**
	 * generateInputFieldSelect
	 *
	 * @return string
	 */
	public function generateInputFieldSelect()
	{
		$s = '';
		if ($this->picto) {
			$s .= img_picto('', $this->picto, 'class="pictofixedwidth"');
		}

		$s .= $this->form->selectarray($this->confKey, $this->fieldOptions, $this->fieldValue, 0, 0, 0, '', 0, 0, 0, '', $this->cssClass);

		return $s;
	}

	/**
	 * @return string
	 */
	public function generateInputFieldSelectUser()
	{
		return $this->form->select_dolusers($this->fieldValue, $this->confKey);
	}

	/**
	 * get the type : used for old module builder setup conf style conversion and tests
	 * because this two class will quickly evolve it's important to not set or get directly $this->type (will be protected) so this method exist
	 * to be sure we can manage evolution easily
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * set the type from string : used for old module builder setup conf style conversion and tests
	 * because this two class will quickly evolve it's important to not set directly $this->type (will be protected) so this method exist
	 * to be sure we can manage evolution easily
	 *
	 * @param 		string 	$type 	Possible values based on old module builder setup : 'string', 'textarea', 'category:'.Categorie::TYPE_CUSTOMER', 'emailtemplate', 'thirdparty_type'
	 * @deprecated 					this setTypeFromTypeString came deprecated because it exists only for manage setup conversion
	 * @return 		bool
	 */
	public function setTypeFromTypeString($type)
	{
		$this->type = $type;

		return true;
	}

	/**
	 * Add error
	 *
	 * @param array|string $errors the error text
	 * @return null
	 */
	public function setErrors($errors)
	{
		if (is_array($errors)) {
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$this->setErrors($error);
				}
			}
		} elseif (!empty($errors)) {
			$this->errors[] = $errors;
		}
		return null;
	}

	/**
	 * generateOutputField
	 *
	 * @return bool|string 		Generate the output html for this item
	 */
	public function generateOutputField()
	{
		global $conf, $user, $langs;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		if (!empty($this->fieldOutputOverride)) {
			return $this->fieldOutputOverride;
		}

		$out = '';

		if ($this->type == 'title') {
			// nothing to do
		} elseif ($this->type == 'textarea') {
			$out .= dol_nl2br($this->fieldValue);
		} elseif ($this->type == 'multiselect') {
			$out .= $this->generateOutputFieldMultiSelect();
		} elseif ($this->type == 'select') {
			$out .= $this->generateOutputFieldSelect();
		} elseif ($this->type == 'selectUser') {
			$out .= $this->generateOutputFieldSelectUser();
		} elseif ($this->type == 'html') {
			$out .=  $this->fieldValue;
		} elseif ($this->type == 'color') {
			$out .=  $this->generateOutputFieldColor();
		} elseif ($this->type == 'yesno') {
			if (!empty($conf->use_javascript_ajax)) {
				$out .= ajax_constantonoff($this->confKey, array(), $this->entity); // TODO possibility to add $input parameter
			} else {
				if ($this->fieldValue == 1) {
					$out .= $langs->trans('yes');
				} else {
					$out .= $langs->trans('no');
				}
			}
		} elseif (preg_match('/emailtemplate:/', $this->type)) {
			if ($this->fieldValue > 0) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
				$formmail = new FormMail($this->db);

				$tmp = explode(':', $this->type);

				$template = $formmail->getEMailTemplate($this->db, $tmp[1], $user, $this->langs, $this->fieldValue);
				if (is_numeric($template) && $template < 0) {
					$this->setErrors($formmail->errors);
				}
				$out .= $this->langs->trans($template->label);
			}
		} elseif (preg_match('/category:/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$c = new Categorie($this->db);
			$result = $c->fetch($this->fieldValue);
			if ($result < 0) {
				$this->setErrors($c->errors);
			}
			$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
			$toprint = array();
			foreach ($ways as $way) {
				$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
			}
			$out .= '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		} elseif (preg_match('/thirdparty_type/', $this->type)) {
			if ($this->fieldValue == 2) {
				$out .= $this->langs->trans("Prospect");
			} elseif ($this->fieldValue == 3) {
				$out .= $this->langs->trans("ProspectCustomer");
			} elseif ($this->fieldValue == 1) {
				$out .= $this->langs->trans("Customer");
			} elseif ($this->fieldValue == 0) {
				$out .= $this->langs->trans("NorProspectNorCustomer");
			}
		} elseif ($this->type == 'product') {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

			$product = new Product($this->db);
			$resprod = $product->fetch($this->fieldValue);
			if ($resprod > 0) {
				$out .= $product->ref;
			} elseif ($resprod < 0) {
				$this->setErrors($product->errors);
			}
		} elseif ($this->type == 'selectBankAccount') {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$bankaccount = new Account($this->db);
			$resbank = $bankaccount->fetch($this->fieldValue);
			if ($resbank > 0) {
				$out .= $bankaccount->label;
			} elseif ($resbank < 0) {
				$this->setErrors($bankaccount->errors);
			}
		} elseif ($this->type == 'password' || $this->type == 'genericpassword') {
			$out .= str_repeat('*', strlen($this->fieldValue));
		} else {
			$out .= $this->fieldValue;
		}

		return $out;
	}


	/**
	 * generateOutputFieldMultiSelect
	 *
	 * @return string
	 */
	public function generateOutputFieldMultiSelect()
	{
		$outPut = '';
		$TSelected = array();
		if (!empty($this->fieldValue)) {
			$TSelected = explode(',', $this->fieldValue);
		}

		if (!empty($TSelected)) {
			foreach ($TSelected as $selected) {
				if (!empty($this->fieldOptions[$selected])) {
					$outPut .= dolGetBadge('', $this->fieldOptions[$selected], 'info').' ';
				}
			}
		}
		return $outPut;
	}

	/**
	 * generateOutputFieldColor
	 *
	 * @return string
	 */
	public function generateOutputFieldColor()
	{
		global $langs;
		$this->fieldAttr['disabled'] = null;
		$color = colorArrayToHex(colorStringToArray($this->fieldValue, array()), '');
		if ($color) {
			return '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		}
		return $langs->trans("Default");
	}
	/**
	 * generateInputFieldColor
	 *
	 * @return string
	 */
	public function generateInputFieldColor()
	{
		$this->fieldAttr['type'] = 'color';
		$default = $this->defaultFieldValue;
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		$formother = new FormOther($this->db);
		return $formother->selectColor(colorArrayToHex(colorStringToArray($this->fieldAttr['value'], array()), ''), $this->fieldAttr['name'], '', 1, array(), '', '', $default).' ';
	}

	/**
	 * generateOutputFieldSelect
	 *
	 * @return string
	 */
	public function generateOutputFieldSelect()
	{
		$outPut = '';
		if (!empty($this->fieldOptions[$this->fieldValue])) {
			$outPut = $this->fieldOptions[$this->fieldValue];
		}

		return $outPut;
	}

	/**
	 * generateOutputFieldSelectUser
	 *
	 * @return string
	 */
	public function generateOutputFieldSelectUser()
	{
		$outPut = '';
		$user = new User($this->db);
		$user->fetch($this->fieldValue);
		$outPut = $user->firstname . " "  . $user->lastname;
		return $outPut;
	}

	/*
	 * METHODS FOR SETTING DISPLAY TYPE
	 */

	/**
	 * Set type of input as string
	 *
	 * @return self
	 */
	public function setAsString()
	{
		$this->type = 'string';
		return $this;
	}

	/**
	 * Set type of input as color
	 *
	 * @return self
	 */
	public function setAsColor()
	{
		$this->type = 'color';
		return $this;
	}

	/**
	 * Set type of input as textarea
	 *
	 * @return self
	 */
	public function setAsTextarea()
	{
		$this->type = 'textarea';
		return $this;
	}

	/**
	 * Set type of input as html editor
	 *
	 * @return self
	 */
	public function setAsHtml()
	{
		$this->type = 'html';
		return $this;
	}

	/**
	 * Set type of input as emailtemplate selector
	 *
	 * @param string $templateType email template type
	 * @return self
	 */
	public function setAsEmailTemplate($templateType)
	{
		$this->type = 'emailtemplate:'.$templateType;
		return $this;
	}

	/**
	 * Set type of input as thirdparty_type selector
	 *
	 * @return self
	 */
	public function setAsThirdpartyType()
	{
		$this->type = 'thirdparty_type';
		return $this;
	}

	/**
	 * Set type of input as Yes
	 *
	 * @return self
	 */
	public function setAsYesNo()
	{
		$this->type = 'yesno';
		return $this;
	}

	/**
	 * Set type of input as secure key
	 *
	 * @return self
	 */
	public function setAsSecureKey()
	{
		$this->type = 'securekey';
		return $this;
	}

	/**
	 * Set type of input as product
	 *
	 * @return self
	 */
	public function setAsProduct()
	{
		$this->type = 'product';
		return $this;
	}

	/**
	 * Set type of input as a category selector
	 * TODO add default value
	 *
	 * @param	int		$catType		Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 * @return self
	 */
	public function setAsCategory($catType)
	{
		$this->type = 'category:'.$catType;
		return $this;
	}

	/**
	 * Set type of input as a simple title. No data to store
	 *
	 * @return self
	 */
	public function setAsTitle()
	{
		$this->type = 'title';
		return $this;
	}


	/**
	 * Set type of input as a simple title. No data to store
	 *
	 * @param array $fieldOptions A table of field options
	 * @return self
	 */
	public function setAsMultiSelect($fieldOptions)
	{
		if (is_array($fieldOptions)) {
			$this->fieldOptions = $fieldOptions;
		}

		$this->type = 'multiselect';
		return $this;
	}

	/**
	 * Set type of input as a simple title. No data to store
	 *
	 * @param array $fieldOptions  A table of field options
	 * @return self
	 */
	public function setAsSelect($fieldOptions)
	{
		if (is_array($fieldOptions)) {
			$this->fieldOptions = $fieldOptions;
		}

		$this->type = 'select';
		return $this;
	}

	/**
	 * Set type of input as a simple title. No data to store
	 *
	 * @return self
	 */
	public function setAsSelectUser()
	{
		$this->type = 'selectUser';
		return $this;
	}

	/**
	 * Set type of input as a simple title. No data to store
	 *
	 * @return self
	 */
	public function setAsSelectBankAccount()
	{
		$this->type = 'selectBankAccount';
		return $this;
	}

	/**
	 * Set type of input as a password with dolibarr password rules apply.
	 * Hide entry on display.
	 *
	 * @return self
	 */
	public function setAsPassword()
	{
		$this->type = 'password';
		return $this;
	}

	/**
	 * Set type of input as a generic password without dolibarr password rules (for external passwords for example).
	 * Hide entry on display.
	 *
	 * @return self
	 */
	public function setAsGenericPassword()
	{
		$this->type = 'genericpassword';
		return $this;
	}
}
