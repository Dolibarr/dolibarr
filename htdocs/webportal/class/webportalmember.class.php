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
 * \file        htdocs/webportal/class/webportalmember.class.php
 * \ingroup     webportal
 * \brief       This file is a CRUD class file for WebPortalMember (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';

/**
 * Class for WebPortalMember
 */
class WebPortalMember extends Adherent
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'webportal';

	/**
	 * Status list (short label)
	 */
	const ARRAY_STATUS_LABEL = array(
		Adherent::STATUS_DRAFT => 'Draft',
		Adherent::STATUS_VALIDATED => 'Validated',
		Adherent::STATUS_RESILIATED => 'MemberStatusResiliatedShort',
		Adherent::STATUS_EXCLUDED => 'MemberStatusExcludedShort',
	);

	/**
	 * MorPhy list : Moral or Physical
	 */
	const MORPHY_LIST = array(
		'phy' => 'Physical',
		'mor' => 'Moral',
	);

	/**
	 * Gender list
	 */
	const GENDER_LIST = array(
		'man' => 'Genderman',
		'woman' => 'Genderwoman',
		'other' => 'Genderother',
	);

	/**
	 * @var Adherent Member for static methods
	 */
	protected $member_static = null;

	/**
	 *  'type' field format:
	 *    'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *    'select' (list of values are in 'options'),
	 *    'varchar(x)',
	 *    'text', 'html',
	 *    'double(24,8)', 'price',
	 *    'date', 'datetime',
	 *    'checkbox', 'radio',
	 *    'mail', 'phone', 'url', 'password'
	 *        Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt('MY_SETUP_PARAM') or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *    'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *  'showonheader' is 1 to show on the top of the card (header section)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 10,),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'default' => '1', 'enabled' => 1, 'visible' => 5, 'notnull' => 1, 'position' => 12, 'index' => 1, 'showonheader' => 1,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 15, 'index' => 1,),

		'lastname' => array('type' => 'varchar(50)', 'label' => 'Lastname', 'enabled' => 1, 'visible' => 4, 'position' => 30, 'showonheader' => 1,),
		'firstname' => array('type' => 'varchar(50)', 'label' => 'Firstname', 'enabled' => 1, 'visible' => 4, 'position' => 35, 'showonheader' => 1,),
		'gender' => array('type' => 'varchar(10)', 'label' => 'Gender', 'enabled' => 1, 'visible' => 4, 'position' => 50, 'arrayofkeyval' => self::GENDER_LIST, 'showonheader' => 1,),
		'company' => array('type' => 'varchar(128)', 'label' => 'Societe', 'enabled' => 1, 'visible' => 4, 'position' => 65, 'showonheader' => 1,),
		'address' => array('type' => 'text', 'label' => 'Address', 'enabled' => 1, 'visible' => 4, 'position' => 75, 'showonheader' => 1,),
		'zip' => array('type' => 'varchar(10)', 'label' => 'Zip', 'enabled' => 1, 'visible' => 4, 'position' => 80, 'showonheader' => 1,),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => 4, 'position' => 85, 'showonheader' => 1,),
		'state_id' => array('type' => 'integer', 'label' => 'State id', 'enabled' => '!getDolGlobalString("MEMBER_DISABLE_STATE")', 'visible' => -5, 'position' => 90, 'showonheader' => 1,),
		'country_id' => array('type' => 'integer:Ccountry:core/class/ccountry.class.php', 'label' => 'Country', 'enabled' => 1, 'visible' => 4, 'position' => 95, 'showonheader' => 1,),
		'phone' => array('type' => 'varchar(30)', 'label' => 'Phone', 'enabled' => 1, 'visible' => 4, 'position' => 115, 'showonheader' => 1,),
		'phone_perso' => array('type' => 'varchar(30)', 'label' => 'Phone perso', 'enabled' => 1, 'visible' => 4, 'position' => 120, 'showonheader' => 1,),
		'phone_mobile' => array('type' => 'varchar(30)', 'label' => 'Phone mobile', 'enabled' => 1, 'visible' => 4, 'position' => 125, 'showonheader' => 1,),
		'email' => array('type' => 'varchar(255)', 'label' => 'Email', 'enabled' => 1, 'visible' => 4, 'position' => 200, 'showonheader' => 1, 'picto' => 'email'),
		'url' => array('type' => 'varchar(255)', 'label' => 'Url', 'enabled' => 1, 'visible' => 4, 'position' => 210, 'showonheader' => 1,),

		'login' => array('type' => 'varchar(50)', 'label' => 'Login', 'enabled' => 1, 'visible' => 4, 'position' => 240,),
		'typeid' => array('type' => 'integer:AdherentType:adherents/class/adherent_type.class.php', 'label' => 'MemberType', 'enabled' => 1, 'visible' => 4, 'notnull' => 1, 'position' => 255),
		'morphy' => array('type' => 'varchar(3)', 'label' => 'MemberNature', 'enabled' => 1, 'visible' => 4, 'notnull' => 1, 'position' => 260, 'arrayofkeyval' => self::MORPHY_LIST,),
		'civility_id' => array('type' => 'sellist:c_civility:label:rowid::active=1', 'label' => 'Civility', 'enabled' => 1, 'visible' => 4, 'position' => 270,),
		'birth' => array('type' => 'date', 'label' => 'DateOfBirth', 'enabled' => 1, 'visible' => 4, 'position' => 290,),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 5, 'position' => 300,),

		'datefin' => array('type' => 'date', 'label' => 'SubscriptionEndDate', 'enabled' => 1, 'visible' => 5, 'position' => 400,),

		'status' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 5, 'notnull' => 1, 'position' => 500, 'arrayofkeyval' => self::ARRAY_STATUS_LABEL, 'showonheader' => 1,),
	);
	/**
	 * @var int
	 */
	public $rowid;
	//public $ref;
	//public $lastname;
	//public $firstname;
	//public $gender;
	//public $address;
	//public $zip;
	//public $town;
	//public $state_id;
	//public $country;
	//public $phone;
	//public $phone_perso;
	//public $phone_mobile;
	//public $email;
	//public $url;
	//public $socialnetworks;
	//public $login;
	/**
	 * @var int
	 */
	public $fk_adherent_type;
	//public $morphy;
	//public $societe;
	//public $civility_id;
	//public $datefin;
	//public $birth;
	//public $fk_soc;
	//public $status;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Get member for static method
	 *
	 * @return	Adherent
	 */
	protected function getMemberStatic()
	{
		if (!$this->member_static) {
			$this->member_static = new Adherent($this->db);
		}

		return $this->member_static;
	}

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		$this->isextrafieldmanaged = 0;

		$this->getMemberStatic();

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("WebPortalMember")];
		}
		$datas['picto'] = img_picto('', $this->picto) . ' <u>' . $langs->trans("WebPortalMember") . '</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' ' . $this->getLibStatut(5);
		}
		$datas['ref'] .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		return $datas;
	}

	/**
	 *  Return clickable name (with picto eventually)
	 *
	 * @param	int		$withpictoimg			0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 * @param	int		$maxlen					Length max label
	 * @param	string	$option					Page for link ('card', 'category', 'subscription', ...)
	 * @param	string	$mode					''=Show firstname+lastname as label (using default order), 'firstname'=Show only firstname, 'lastname'=Show only lastname, 'login'=Show login, 'ref'=Show ref
	 * @param	string	$morecss				Add more css on link
	 * @param	int		$save_lastsearch_value	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @param	int		$notooltip				1=Disable tooltip
	 * @param	int		$addlinktonotes			1=Add link to notes
	 * @return	string 	String with Url
	 */
	public function getNomUrl($withpictoimg = 0, $maxlen = 0, $option = 'card', $mode = '', $morecss = '', $save_lastsearch_value = -1, $notooltip = 0, $addlinktonotes = 0)
	{
		global $langs, $hookmanager;

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && $withpictoimg) {
			$withpictoimg = 0;
		}

		$option = 'nolink';

		$result = '';
		$linkstart = '';
		$linkend = '';
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
		];
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="' . dol_escape_htmltag(json_encode($params)) . '"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = '';
		//$url = DOL_URL_ROOT.'/adherents/card.php?rowid='.((int) $this->id);
		//if ($option == 'subscription') {
		//    $url = DOL_URL_ROOT.'/adherents/subscription.php?rowid='.((int) $this->id);
		//}

		//if ($option != 'nolink') {
		//    // Add param to save lastsearch_values or not
		//    $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		//    if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
		//        $add_save_lastsearch_values = 1;
		//    }
		//    if ($add_save_lastsearch_values) {
		//        $url .= '&save_lastsearch_values=1';
		//    }
		//}

		$linkstart .= '<a href="' . $url . '"';
		$linkclose = "";
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$langs->load("users");
				$label = $langs->trans("ShowUser");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ($label ? ' title="' . dol_escape_htmltag($label, 1) . '"' : ' title="tocomplete"');
			$linkclose .= $dataparams . ' class="' . $classfortooltip . ($morecss ? ' ' . $morecss : '') . '"';
		}

		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		if ($option === 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpictoimg) {
			$result .= '<div class="inline-block nopadding valignmiddle">';
		}
		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs($withpictoimg) == 1 || abs($withpictoimg) == 4) {
				$morecss .= ' paddingrightonly';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<span class="nopadding' . ($morecss ? ' userimg' . $morecss : '') . '">' . img_object('', 'user', $paddafterimage . ' ' . ($notooltip ? '' : $dataparams), 0, 0, $notooltip ? 0 : 1) . '</span>';
			} else {
				// Picto must be a photo
				$picto = '<span class="nopadding' . ($morecss ? ' userimg' . $morecss : '') . '"' . ($paddafterimage ? ' ' . $paddafterimage : '') . '>';
				$picto .= Form::showphoto('memberphoto', $this, 0, 0, 0, 'userphoto' . (($withpictoimg == -3 || $withpictoimg == -4) ? 'small' : ''), 'mini', 0, 1);
				$picto .= '</span>';
			}
			$result .= $picto;
		}
		if (($withpictoimg > -2 && $withpictoimg != 2) || $withpictoimg == -4) {
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '<span class="nopadding valignmiddle' . ((!isset($this->statut) || $this->statut) ? '' : ' strikefordisabled') .
					($morecss ? ' usertext' . $morecss : '') . '">';
			}
			if ($mode == 'login') {
				$result .= dol_trunc($this->login, $maxlen);
			} elseif ($mode == 'ref') {
				$result .= $this->ref;
			} else {
				$result .= $this->getFullName($langs, 0, ($mode == 'firstname' ? 2 : ($mode == 'lastname' ? 4 : -1)), $maxlen);
			}
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '</span>';
			}
		}
		if ($withpictoimg) {
			$result .= '</div>';
		}
		$result .= $linkend;

		//if ($addlinktonotes) {
		//    if ($this->note_private) {
		//        $notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($this->note_private, 1);
		//        $result .= ' <span class="note inline-block">';
		//        $result .= '<a href="'.DOL_URL_ROOT.'/adherents/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
		//        $result .= img_picto('', 'note');
		//        $result .= '</a>';
		//        $result .= '</span>';
		//    }
		//}
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 * Retourne le libelle du statut d'un adherent (brouillon, valide, resilie, exclu)
	 *
	 * @param	int		$mode		0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return	string	Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $this->need_subscription, $this->datefin, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param	int		$status					Id status
	 * @param	int		$need_subscription		1 if member type need subscription, 0 otherwise
	 * @param	int		$date_end_subscription	Date fin adhesion
	 * @param	int		$mode					0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string	Label
	 */
	public function LibStatut($status, $need_subscription, $date_end_subscription, $mode = 0)
	{
		// phpcs:enable
		return $this->getMemberStatic()->LibStatut($status, $need_subscription, $date_end_subscription, $mode);
	}

	/**
	 * Return full address for banner
	 *
	 * @param	string	$htmlkey	HTML id to make banner content unique
	 * @return	string	Full address string
	 */
	public function getBannerAddressForWebPortal($htmlkey)
	{
		global $conf, $langs, $form, $extralanguages;

		$countriesusingstate = array('AU', 'US', 'IN', 'GB', 'ES', 'UK', 'TR'); // See also option MAIN_FORCE_STATE_INTO_ADDRESS

		$contactid = 0;
		$thirdpartyid = 0;
		$elementforaltlanguage = $this->element;

		$out = '';

		$outdone = 0;
		$coords = $this->getFullAddress(1, ', ', getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT'));
		if ($coords) {
			$address = dol_print_address($coords, 'address_' . $htmlkey . '_' . $this->id, $this->element, $this->id, 1, ', ');
			if ($address) {
				$out .= $address;
				$outdone++;
			}
			$outdone++;

			// List of extra languages
			$arrayoflangcode = array();
			if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
				$arrayoflangcode[] = getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE');
			}

			if (is_array($arrayoflangcode) && count($arrayoflangcode)) {
				if (!is_object($extralanguages)) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/extralanguages.class.php';
					$extralanguages = new ExtraLanguages($this->db);
				}
				$extralanguages->fetch_name_extralanguages($elementforaltlanguage);

				if (!empty($extralanguages->attributes[$elementforaltlanguage]['address']) || !empty($extralanguages->attributes[$elementforaltlanguage]['town'])) {
					$out .= "<!-- alternatelanguage for '" . $elementforaltlanguage . "' set to fields '" . implode(',', $extralanguages->attributes[$elementforaltlanguage]) . "' -->\n";
					$this->fetchValuesForExtraLanguages();
					if (!is_object($form)) {
						$form = new Form($this->db);
					}
					$htmltext = '';
					// If there is extra languages
					foreach ($arrayoflangcode as $extralangcode) {
						$s = picto_from_langcode($extralangcode, 'class="pictoforlang paddingright"');
						// This also call dol_format_address()
						$coords = $this->getFullAddress(1, ', ', $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT, $extralangcode);
						$htmltext .= $s . dol_print_address($coords, 'address_' . $htmlkey . '_' . $this->id, $this->element, $this->id, 1, ', ');
					}
					$out .= $form->textwithpicto('', $htmltext, -1, 'language', 'opacitymedium paddingleft');
				}
			}
		}

		// If MAIN_FORCE_STATE_INTO_ADDRESS is on, state is already returned previously with getFullAddress
		if (!in_array($this->country_code, $countriesusingstate) && !getDolGlobalString('MAIN_FORCE_STATE_INTO_ADDRESS')
			&& !getDolGlobalString('SOCIETE_DISABLE_STATE') && $this->state) {
			if (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 && $this->region) {
				$out .= ($outdone ? ' - ' : '') . $this->region . ' - ' . $this->state;
			} else {
				$out .= ($outdone ? ' - ' : '') . $this->state;
			}
			$outdone++;
		}

		if ($outdone) {
			$out = '<div class="address inline-block">' . $out . '</div>';
		}

		if (!empty($this->phone) || !empty($this->phone_pro) || !empty($this->phone_mobile) || !empty($this->phone_perso) || !empty($this->fax) || !empty($this->office_phone) || !empty($this->user_mobile) || !empty($this->office_fax)) {
			$out .= ($outdone ? '<br>' : '');
		}
		if (!empty($this->phone) && empty($this->phone_pro)) {        // For objects that store pro phone into ->phone
			$out .= dol_print_phone($this->phone, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->phone_pro)) {
			$out .= dol_print_phone($this->phone_pro, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->phone_mobile)) {
			$out .= dol_print_phone($this->phone_mobile, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
			$outdone++;
		}
		if (!empty($this->phone_perso)) {
			$out .= dol_print_phone($this->phone_perso, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'phone', $langs->trans("PhonePerso"));
			$outdone++;
		}
		if (!empty($this->office_phone)) {
			$out .= dol_print_phone($this->office_phone, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->user_mobile)) {
			$out .= dol_print_phone($this->user_mobile, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
			$outdone++;
		}
		if (!empty($this->fax)) {
			$out .= dol_print_phone($this->fax, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'fax', $langs->trans("Fax"));
			$outdone++;
		}
		if (!empty($this->office_fax)) {
			$out .= dol_print_phone($this->office_fax, $this->country_code, $contactid, $thirdpartyid, '', '&nbsp;', 'fax', $langs->trans("Fax"));
			$outdone++;
		}

		if ($out) {
			$out .= '<div style="clear: both;"></div>';
		}
		$outdone = 0;
		if (!empty($this->email)) {
			$out .= dol_print_email($this->email, $this->id, $this->id, '', 0, 0, 1);
			$outdone++;
		}
		if (!empty($this->url)) {
			$out .= dol_print_url($this->url, '_blank', 0, 1);
			$outdone++;
		}

		return $out;
	}
}
