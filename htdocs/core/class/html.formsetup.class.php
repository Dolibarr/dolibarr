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
	public $arrayOfParameters = array();

	public $setupNotEmpty = 0;

	/** @var Translate */
	public $langs;

	/** @var Form */
	public $form;

	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
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
	 * @return string
	 */
	public function generateOutput($edit = false)
	{

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

		$out = '<table class="noborder centpercent">';
		$out.= '<tr class="liste_titre">';
		$out.= '	<td class="titlefield">'.$this->langs->trans("Parameter").'</td>';
		$out.= '	<td>'.$this->langs->trans("Value").'</td>';
		$out.= '</tr>';

		foreach ($this->arrayOfParameters as $item) {
			$out.= $this->generateLineOutput($item, $edit);
		}

		$out.= '</table>';
		return $out;
	}

	/**
	 * @param formSetupItem $item
	 * @param bool $edit
	 * @return string
	 */
	public function generateLineOutput($item, $edit = false)
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

			if ($edit) {
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
	 * @param string $confKey
	 * @param array $params
	 * @
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
	 * @param string $confKey
	 * @param array $params
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

		$item = new formSetupItem($this->db);
		$item->type = $params['type'];
		$item->confKey = $confKey;

		if (!empty($params['enabled'])) {
			$item->enabled = $params['enabled'];
		}

		if (!empty($params['css'])) {
			$item->cssClass = $params['css'];
		}

		$this->arrayOfParameters[$item->confKey] = $item;

		return true;
	}
}


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

	/** @var bool|string set this var to override field output */
	public $fieldOverride = false;

	/**
	 * @var string $errors
	 */
	public $errors = array();

	/**
	 * @var string $type  'string', 'textarea', 'category:'.Categorie::TYPE_CUSTOMER', 'emailtemplate', 'thirdparty_type'
	 */
	public $type;

	public $enabled = 0;

	public $cssClass = '';

	/**
	 * Constructor
	 *
	 * @param $confKey
	 */
	public function __construct($confKey)
	{
		global $langs, $db;
		$this->db = $db;
		$this->form = new Form($this->db);
		$this->langs = $langs;

		$this->confKey = $confKey;
	}

	public function getHelpText()
	{
		if (!empty($this->helpText)) { return $this->helpText; }
		return (($this->langs->trans($this->confKey . 'Tooltip') != $this->confKey . 'Tooltip') ? $this->langs->trans($this->confKey . 'Tooltip') : '');
	}

	public function getNameText()
	{
		if (!empty($this->nameText)) { return $this->nameText; }
		return (($this->langs->trans($this->confKey) != $this->confKey) ? $this->langs->trans($this->confKey) : $this->langs->trans('MissingTranslationForConfKey', $this->confKey));
	}

	public function generateInputField()
	{
		global $conf, $user;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		$out = '';

		if ($this->type == 'textarea') {
			$out.= '<textarea class="flat" name="'.$this->confKey.'" id="'.$this->confKey.'" cols="50" rows="5" wrap="soft">' . "\n";
			$out.= dol_htmlentities($conf->global->{$this->confKey});
			$out.= "</textarea>\n";
		} elseif ($this->type== 'html') {
			require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
			$doleditor = new DolEditor($this->confKey, $conf->global->{$this->confKey}, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
			$doleditor->Create();
		} elseif ($this->type == 'yesno') {
			$out.= $this->form->selectyesno($this->confKey, $conf->global->{$this->confKey}, 1);
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
			$out.= $this->form->selectarray($this->confKey, $arrayOfMessageName, $conf->global->{$this->confKey}, 'None', 0, 0, '', 0, 0, 0, '', '', 1);
		} elseif (preg_match('/category:/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
			$formother = new FormOther($this->db);

			$tmp = explode(':', $this->type);
			$out.= img_picto('', 'category', 'class="pictofixedwidth"');
			$out.= $formother->select_categories($tmp[1],  $conf->global->{$this->confKey}, $this->confKey, 0, $this->langs->trans('CustomersProspectsCategoriesShort'));
		} elseif (preg_match('/thirdparty_type/', $this->type)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
			$formcompany = new FormCompany($this->db);
			$out.= $formcompany->selectProspectCustomerType($conf->global->{$this->confKey}, $this->confKey);
		} elseif ($this->type == 'securekey') {
			$out.= '<input required="required" type="text" class="flat" id="'.$this->confKey.'" name="'.$this->confKey.'" value="'.(GETPOST($this->confKey, 'alpha') ?GETPOST($this->confKey, 'alpha') : $conf->global->{$this->confKey}).'" size="40">';
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
				$selected = (empty($conf->global->{$this->confKey}) ? '' : $conf->global->{$this->confKey});
				$this->form->select_produits($selected, $this->confKey, '', 0);
			}
		} else {
			$out.= '<input name="'.$this->confKey.'"  class="flat '.(empty($this->cssClass) ? 'minwidth200' : $this->cssClass).'" value="'.$conf->global->{$this->confKey}.'">';
		}

		return $out;
	}


	/**
	 * add error
	 * @param array|string $errors
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

	public function generateOutputField()
	{
		global $conf, $user;

		if (!empty($this->fieldOverride)) {
			return $this->fieldOverride;
		}

		$out = '';

		if ($this->type == 'textarea') {
			$out.= dol_nl2br($conf->global->{$this->confKey});
		} elseif ($this->type== 'html') {
			$out.=  $conf->global->{$this->confKey};
		} elseif ($this->type == 'yesno') {
			$out.= ajax_constantonoff($this->confKey);
		} elseif (preg_match('/emailtemplate:/', $this->type)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
			$formmail = new FormMail($this->db);

			$tmp = explode(':', $this->type);

			$template = $formmail->getEMailTemplate($this->db, $tmp[1], $user, $this->langs, $conf->global->{$this->confKey});
			if ($template<0) {
				$this->setErrors($formmail->errors);
			}
			$out.= $this->langs->trans($template->label);
		} elseif (preg_match('/category:/', $this->type)) {
			$c = new Categorie($this->db);
			$result = $c->fetch($conf->global->{$this->confKey});
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
			if ($conf->global->{$this->confKey}==2) {
				$out.= $this->langs->trans("Prospect");
			} elseif ($conf->global->{$this->confKey}==3) {
				$out.= $this->langs->trans("ProspectCustomer");
			} elseif ($conf->global->{$this->confKey}==1) {
				$out.= $this->langs->trans("Customer");
			} elseif ($conf->global->{$this->confKey}==0) {
				$out.= $this->langs->trans("NorProspectNorCustomer");
			}
		} elseif ($this->type == 'product') {
			$product = new Product($this->db);
			$resprod = $product->fetch($conf->global->{$this->confKey});
			if ($resprod > 0) {
				$out.= $product->ref;
			} elseif ($resprod < 0) {
				$this->setErrors($product->errors);
			}
		} else {
			$out.= $conf->global->{$this->confKey};
		}

		return $out;
	}
}
