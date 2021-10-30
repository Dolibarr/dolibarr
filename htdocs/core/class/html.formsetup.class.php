<?php
/* Copyright (C) 2021  John BOTELLA    <john.botella@atm-consulting.fr>
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
class formSetup
{

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/** @var formSetupItem[]  */
	public $params = array();

	public $setupNotEmpty = 0;

	/** @var Translate */
	public $langs;

	/** @var Form */
	public $form;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param Translate $outputLangs if needed can use another lang
	 */
	public function __construct($db, $outputLangs = false)
	{
		global $langs;
		$this->db = $db;
		$this->form = new Form($this->db);

		if ($outputLangs) {
			$this->langs = $outputLangs;
		} else {
			$this->langs = $langs;
		}
	}

	/**
	 * @param bool $editMode true will display output on edit mod
	 * @return string
	 */
	public function generateOutput($editMode = false)
	{

		$out = '';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

		$out.= '<input type="hidden" name="token" value="'.newToken().'">';
		if ($editMode) {
			$out .= '<input type="hidden" name="action" value="update">';
		}

		$out.= '<table class="noborder centpercent">';
		$out.= '<thead>';
		$out.= '<tr class="liste_titre">';
		$out.= '	<td class="titlefield">'.$this->langs->trans("Parameter").'</td>';
		$out.= '	<td>'.$this->langs->trans("Value").'</td>';
		$out.= '</tr>';
		$out.= '</thead>';

		$out.= '<tbody>';
		foreach ($this->params as $item) {
			$out.= $this->generateLineOutput($item, $editMode);
		}
		$out.= '</tbody>';

		$out.= '</table>';
		return $out;
	}

	/**
	 * @param formSetupItem $item the setup item
	 * @param bool $editMode Display as edit mod
	 * @return string the html output for an setup item
	 */
	public function generateLineOutput($item, $editMode = false)
	{

		$out = '';
		if ($item->enabled==1) {
			$this->setupNotEmpty++;
			$out.= '<tr class="oddeven">';

			$out.= '<td class="col-setup-title">';
			$out.= '<span id="helplink'.$item->confKey.'" class="spanforparamtooltip">';
			$out.= $this->form->textwithpicto($item->getNameText(), $item->getHelpText(), 1, 'info', '', 0, 3, 'tootips'.$item->confKey);
			$out.= '</span>';
			$out.= '</td>';

			$out.= '<td>';

			if ($editMode) {
				$out.= $item->generateInputField();
			} else {
				$out.= $item->generateOutputField();
			}

			if (!empty($item->errors)) {
				// TODO : move set event message in a methode to be called by cards not by this class
				setEventMessages(null, $item->errors, 'errors');
			}

			$out.= '</td>';
			$out.= '</tr>';
		}

		return $out;
	}


	/**
	 * @param array $params an array of arrays of params from old modulBuilder params
	 * @deprecated was used to test  module builder convertion to this form usage
	 * @return null
	 */
	public function addItemsFromParamsArray($params)
	{
		if (!array($params)) { return false; }
		foreach ($params as $confKey => $param) {
			$this->addItemFromParams($confKey, $param); // todo manage error
		}
	}


	/**
	 * From old
	 * @param string $confKey the conf name to store
	 * @param array $params an array of params from old modulBuilder params
	 * @deprecated was used to test  module builder convertion to this form usage
	 * @return bool
	 */
	public function addItemFromParams($confKey, $params)
	{
		if (empty($confKey) || empty($params['type'])) { return false; }

		/*
		 * Exemple from old module builder setup page
		 * 	// 'MYMODULE_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1),
			// 'MYMODULE_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
			//'MYMODULE_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
			//'MYMODULE_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
			//'MYMODULE_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
			//'MYMODULE_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
			//'MYMODULE_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
			//'MYMODULE_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
		 */

		$item = new formSetupItem($confKey);
		$item->type = $params['type'];

		if (!empty($params['enabled'])) {
			$item->enabled = $params['enabled'];
		}

		if (!empty($params['css'])) {
			$item->cssClass = $params['css'];
		}

		$this->params[$item->confKey] = $item;

		return true;
	}

	/**
	 * used to export param array for /core/actions_setmoduleoptions.inc.php template
	 * @return array $arrayofparameters for /core/actions_setmoduleoptions.inc.php
	 * @deprecated
	 */
	public function exportItemsAsParamsArray()
	{
		$arrayofparameters = array();
		foreach ($this->params as $key => $item) {
			$arrayofparameters[$item->confKey] = array(
				'type' => $item->type,
				'enabled' => $item->enabled
			);
		}

		return $arrayofparameters;
	}

	/**
	 * Reload for each item default conf
	 * note: this will override custom configuration
	 * @return bool
	 */
	public function reloadConfs()
	{

		if (!array($this->params)) { return false; }
		foreach ($this->params as $item) {
			$item->reloadConf();
		}

		return true;
	}



	/**
	 * Create a new item
	 * @param $confKey the conf key used in database
	 * @return formSetupItem the new setup item created
	 */
	public function newItem($confKey)
	{
		$item = new formSetupItem($confKey);
		$this->params[$item->confKey] = $item;
		return $this->params[$item->confKey];
	}
}

/**
 * This class help to create item for class formSetup
 */
class formSetupItem
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/** @var Translate */
	public $langs;

	/** @var Form */
	public $form;

	/** @var string $confKey the conf key used in database */
	public $confKey;

	/** @var string|false $nameText  */
	public $nameText = false;

	/** @var string $helpText  */
	public $helpText = '';

	/** @var string $value  */
	public $fieldValue;

	/** @var bool|string set this var to override field output will override $fieldInputOverride and $fieldOutputOverride too */
	public $fieldOverride = false;

	/** @var bool|string set this var to override field output */
	public $fieldInputOverride = false;

	/** @var bool|string set this var to override field output */
	public $fieldOutputOverride = false;

	/**
	 * @var string $errors
	 */
	public $errors = array();

	/**
	 * TODO each type must have setAs{type} method to help configuration
	 *   And set var as protected when its done configuration must be done by method
	 * @var string $type  'string', 'textarea', 'category:'.Categorie::TYPE_CUSTOMER', 'emailtemplate', 'thirdparty_type'
	 */
	public $type = 'string';

	public $enabled = 1;

	public $cssClass = '';

	/**
	 * Constructor
	 *
	 * @param $confKey the conf key used in database
	 */
	public function __construct($confKey)
	{
		global $langs, $db, $conf;
		$this->db = $db;
		$this->form = new Form($this->db);
		$this->langs = $langs;

		$this->confKey = $confKey;
		$this->fieldValue = $conf->global->{$this->confKey};
	}

	/**
	 * reload conf value from databases
	 * @return null
	 */
	public function reloadConf()
	{
		global $conf;
		$this->fieldValue = $conf->global->{$this->confKey};
	}

	/**
	 * Get help text or generate it
	 * @return int|string
	 */
	public function getHelpText()
	{
		if (!empty($this->helpText)) { return $this->helpText; }
		return (($this->langs->trans($this->confKey . 'Tooltip') != $this->confKey . 'Tooltip') ? $this->langs->trans($this->confKey . 'Tooltip') : '');
	}

	/**
	 * Get field name text or generate it
	 * @return false|int|string
	 */
	public function getNameText()
	{
		if (!empty($this->nameText)) { return $this->nameText; }
		return (($this->langs->trans($this->confKey) != $this->confKey) ? $this->langs->trans($this->confKey) : $this->langs->trans('MissingTranslationForConfKey', $this->confKey));
	}

	/**
	 * generate input field
	 * @return bool|string
	 */
	public function generateInputField()
	{
		global $conf, $user;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		if (!empty($this->fieldInputOverride)) {
			return $this->fieldInputOverride;
		}

		$out = '';

		if ($this->type == 'textarea') {
			$out.= '<textarea class="flat" name="'.$this->confKey.'" id="'.$this->confKey.'" cols="50" rows="5" wrap="soft">' . "\n";
			$out.= dol_htmlentities($this->fieldValue);
			$out.= "</textarea>\n";
		} elseif ($this->type== 'html') {
			require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
			$doleditor = new DolEditor($this->confKey, $this->fieldValue, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
			$doleditor->Create();
		} elseif ($this->type == 'yesno') {
			$out.= $this->form->selectyesno($this->confKey, $this->fieldValue, 1);
		} elseif (preg_match('/emailtemplate:/', $this->type)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
			$formmail = new FormMail($this->db);

			$tmp = explode(':', $this->type);
			$nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang
			//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
			$arrayOfMessageName = array();
			if (is_array($formmail->lines_model)) {
				foreach ($formmail->lines_model as $modelMail) {
					//var_dump($modelmail);
					$moreonlabel = '';
					if (!empty($arrayOfMessageName[$modelMail->label])) {
						$moreonlabel = ' <span class="opacitymedium">(' . $this->langs->trans("SeveralLangugeVariatFound") . ')</span>';
					}
					// The 'label' is the key that is unique if we exclude the language
					$arrayOfMessageName[$modelMail->id] = $this->langs->trans(preg_replace('/\(|\)/', '', $modelMail->label)) . $moreonlabel;
				}
			}
			$out.= $this->form->selectarray($this->confKey, $arrayOfMessageName, $this->fieldValue, 'None', 0, 0, '', 0, 0, 0, '', '', 1);
		} elseif (preg_match('/category:/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
			$formother = new FormOther($this->db);

			$tmp = explode(':', $this->type);
			$out.= img_picto('', 'category', 'class="pictofixedwidth"');
			$out.= $formother->select_categories($tmp[1],  $this->fieldValue, $this->confKey, 0, $this->langs->trans('CustomersProspectsCategoriesShort'));
		} elseif (preg_match('/thirdparty_type/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
			$formcompany = new FormCompany($this->db);
			$out.= $formcompany->selectProspectCustomerType($this->fieldValue, $this->confKey);
		} elseif ($this->type == 'securekey') {
			$out.= '<input required="required" type="text" class="flat" id="'.$this->confKey.'" name="'.$this->confKey.'" value="'.(GETPOST($this->confKey, 'alpha') ?GETPOST($this->confKey, 'alpha') : $this->fieldValue).'" size="40">';
			if (!empty($conf->use_javascript_ajax)) {
				$out.= '&nbsp;'.img_picto($this->langs->trans('Generate'), 'refresh', 'id="generate_token'.$this->confKey.'" class="linkobject"');
			}
			if (!empty($conf->use_javascript_ajax)) {
				$out.= "\n".'<script type="text/javascript">';
				$out.= '$(document).ready(function () {
                        $("#generate_token'.$this->confKey.'").click(function() {
                	        $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                		      action: \'getrandompassword\',
                		      generic: true
    				        },
    				        function(token) {
    					       $("#'.$this->confKey.'").val(token);
            				});
                         });
                    });';
				$out.= '</script>';
			}
		} elseif ($this->type == 'product') {
			if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
				$selected = (empty($this->fieldValue) ? '' : $this->fieldValue);
				$out.= $this->form->select_produits($selected, $this->confKey, '', 0, 0, 1, 2, '', 0, array(), 0, '1', 0, $this->cssClass, 0, '', null, 1);
			}
		} else {
			$out.= '<input name="'.$this->confKey.'"  class="flat '.(empty($this->cssClass) ? 'minwidth200' : $this->cssClass).'" value="'.$this->fieldValue.'">';
		}

		return $out;
	}


	/**
	 * Add error
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
	}

	/**
	 * @return bool|string Generate the output html for this item
	 */
	public function generateOutputField()
	{
		global $conf, $user;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		if (!empty($this->fieldOutputOverride)) {
			return $this->fieldOutputOverride;
		}

		$out = '';

		if ($this->type == 'textarea') {
			$out.= dol_nl2br($this->fieldValue);
		} elseif ($this->type== 'html') {
			$out.=  $this->fieldValue;
		} elseif ($this->type == 'yesno') {
			$out.= ajax_constantonoff($this->confKey);
		} elseif (preg_match('/emailtemplate:/', $this->type)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
			$formmail = new FormMail($this->db);

			$tmp = explode(':', $this->type);

			$template = $formmail->getEMailTemplate($this->db, $tmp[1], $user, $this->langs, $this->fieldValue);
			if ($template<0) {
				$this->setErrors($formmail->errors);
			}
			$out.= $this->langs->trans($template->label);
		} elseif (preg_match('/category:/', $this->type)) {
			$c = new Categorie($this->db);
			$result = $c->fetch($this->fieldValue);
			if ($result < 0) {
				$this->setErrors($c->errors);
			}
			$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
			$toprint = array();
			foreach ($ways as $way) {
				$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
			}
			$out.= '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		} elseif (preg_match('/thirdparty_type/', $this->type)) {
			if ($this->fieldValue==2) {
				$out.= $this->langs->trans("Prospect");
			} elseif ($this->fieldValue==3) {
				$out.= $this->langs->trans("ProspectCustomer");
			} elseif ($this->fieldValue==1) {
				$out.= $this->langs->trans("Customer");
			} elseif ($this->fieldValue==0) {
				$out.= $this->langs->trans("NorProspectNorCustomer");
			}
		} elseif ($this->type == 'product') {
			$product = new Product($this->db);
			$resprod = $product->fetch($this->fieldValue);
			if ($resprod > 0) {
				$out.= $product->ref;
			} elseif ($resprod < 0) {
				$this->setErrors($product->errors);
			}
		} else {
			$out.= $this->fieldValue;
		}

		return $out;
	}

	/*
	 * METHODS FOR SETTING DISPLAY TYPE
	 */

	/**
	 * Set type of input as string
	 * @return self
	 */
	public function setAsString()
	{
		$this->type = 'string';
		return $this;
	}

	/**
	 * Set type of input as textarea
	 * @return self
	 */
	public function setAsTextarea()
	{
		$this->type = 'textarea';
		return $this;
	}

	/**
	 * Set type of input as html editor
	 * @return self
	 */
	public function setAsHtml()
	{
		$this->type = 'html';
		return $this;
	}

	/**
	 * Set type of input as emailtemplate selector
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
	 * @return self
	 */
	public function setAsThirdpartyType()
	{
		$this->type = 'thirdparty_type';
		return $this;
	}

	/**
	 * Set type of input as Yes
	 * @return self
	 */
	public function setAsYesNo()
	{
		$this->type = 'yesno';
		return $this;
	}

	/**
	 * Set type of input as secure key
	 * @return self
	 */
	public function setAsSecureKey()
	{
		$this->type = 'securekey';
		return $this;
	}

	/**
	 * Set type of input as product
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
	 * @param	int		$catType		Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 * @return self
	 */
	public function setAsCategory($catType)
	{
		$this->type = 'category:'.$catType;
		return $this;
	}
}
