<?php
/* Copyright (C) 2023       Frédéric France     <frederic.france@netlogic.fr>
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
 *       \file       htdocs/core/class/commonpeople.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of object classes that support people
 */


/**
 *      Support class for third parties, contacts, members, users or resources
 *
 *
 * Properties expected in the host class receiving this trait.
 *
 * @property int 	$id
 * @property int 	$contact_id
 * @property int 	$fk_soc
 * @property string $civility_code
 * @property DoliDB	$db
 * @property string $element
 * @property string $name
 * @property string $name_alias
 * @property string $nom
 * @property string $company
 * @property string $firstname
 * @property string $lastname
 * @property string $personal_email
 * @property array<string,string> $socialnetworks
 * @property string $fax
 * @property string $office_fax
 * @property string $office_phone
 * @property string $phone
 * @property string $phone_perso
 * @property string $phone_pro
 * @property string $phone_mobile
 * @property string $user_mobile
 * @property string $country_code
 * @property string $region
 */
trait CommonPeople
{
	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string zip code
	 */
	public $zip;

	/**
	 * @var string town
	 */
	public $town;

	/**
	 * @var int	The state/department
	 */
	public $state_id;
	/**
	 * @var string
	 */
	public $state_code;
	/**
	 * @var string
	 */
	public $state;

	/**
	 * @var string email
	 */
	public $email;

	/**
	 * @var string url
	 */
	public $url;


	/**
	 *	Return full name (civility+' '+name+' '+lastname)
	 *
	 *	@param	Translate	$langs			Language object for translation of civility (used only if option is 1)
	 *	@param	int			$option			0=No option, 1=Add civility
	 * 	@param	int			$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname, 2=Firstname, 3=Firstname if defined else lastname, 4=Lastname, 5=Lastname if defined else firstname
	 * 	@param	int			$maxlen			Maximum length
	 * 	@return	string						String with full name
	 */
	public function getFullName($langs, $option = 0, $nameorder = -1, $maxlen = 0)
	{
		//print "lastname=".$this->lastname." name=".$this->name." nom=".$this->nom."<br>\n";
		$lastname = $this->lastname;
		$firstname = $this->firstname;
		if (empty($lastname)) {
			// societe is deprecated - @suppress-next-line PhanUndeclaredProperty
			$lastname = (isset($this->lastname) ? $this->lastname : (isset($this->name) ? $this->name : (property_exists($this, 'nom') && isset($this->nom) ? $this->nom : (property_exists($this, 'societe') && isset($this->societe) ? $this->societe : (property_exists($this, 'company') && isset($this->company) ? $this->company : '')))));
		}

		$ret = '';
		if (!empty($option) && !empty($this->civility_code)) {
			if ($langs->transnoentitiesnoconv("Civility".$this->civility_code) != "Civility".$this->civility_code) {
				$ret .= $langs->transnoentitiesnoconv("Civility".$this->civility_code).' ';
			} else {
				$ret .= $this->civility_code.' ';
			}
		}

		$ret .= dolGetFirstLastname($firstname, $lastname, $nameorder);

		return dol_string_nohtmltag(dol_trunc($ret, $maxlen));
	}

	/**
	 * 	Return full address for banner
	 *
	 * 	@param		string		$htmlkey            HTML id to make banner content unique
	 *  @param      Object      $object				Object (thirdparty, thirdparty of contact for contact, null for a member)
	 *	@return		string							Full address string
	 */
	public function getBannerAddress($htmlkey, $object)
	{
		global $conf, $langs, $form, $extralanguages;

		$countriesusingstate = array('AU', 'US', 'IN', 'GB', 'ES', 'UK', 'TR'); // See also option MAIN_FORCE_STATE_INTO_ADDRESS

		$contactid = 0;
		$thirdpartyid = 0;
		$elementforaltlanguage = $this->element;
		if ($this->element == 'societe') {
			/** @var Societe $this */
			$thirdpartyid = $this->id;
		}
		if ($this->element == 'contact') {
			/** @var Contact $this */
			$contactid = $this->id;
			$thirdpartyid = empty($this->fk_soc) ? 0 : $this->fk_soc;
		}
		if ($this->element == 'user') {
			/** @var User $this */
			$contactid = $this->contact_id;
			$thirdpartyid = empty($object->fk_soc) ? 0 : $object->fk_soc;
		}

		$out = '';

		$outdone = 0;
		$coords = $this->getFullAddress(1, ', ', getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT'));
		if ($coords) {
			if (!empty($conf->use_javascript_ajax)) {
				// Add picto with tooltip on map
				$namecoords = '';
				if ($this->element == 'contact' && getDolGlobalString('MAIN_SHOW_COMPANY_NAME_IN_BANNER_ADDRESS')) {
					$namecoords .= $object->name.'<br>';
				}
				$namecoords .= $this->getFullName($langs, 1).'<br>'.$coords;
				// hideonsmatphone because copyToClipboard call jquery dialog that does not work with jmobile
				$out .= '<a href="#" class="hideonsmartphone" onclick="return copyToClipboard(\''.dol_escape_js($namecoords).'\',\''.dol_escape_js($langs->trans("HelpCopyToClipboard")).'\');">';
				$out .= img_picto($langs->trans("Address"), 'map-marker-alt');
				$out .= '</a> ';
			}
			$address = dol_print_address($coords, 'address_'.$htmlkey.'_'.$this->id, $this->element, $this->id, 1, ', ');
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
					include_once DOL_DOCUMENT_ROOT.'/core/class/extralanguages.class.php';
					$extralanguages = new ExtraLanguages($this->db);
				}
				$extralanguages->fetch_name_extralanguages($elementforaltlanguage);

				if (!empty($extralanguages->attributes[$elementforaltlanguage]['address']) || !empty($extralanguages->attributes[$elementforaltlanguage]['town'])) {
					$out .= "<!-- alternatelanguage for '".$elementforaltlanguage."' set to fields '".implode(',', $extralanguages->attributes[$elementforaltlanguage])."' -->\n";
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
						$htmltext .= $s.dol_print_address($coords, 'address_'.$htmlkey.'_'.$this->id, $this->element, $this->id, 1, ', ');
					}
					$out .= $form->textwithpicto('', $htmltext, -1, 'language', 'opacitymedium paddingleft');
				}
			}
		}

		// If MAIN_FORCE_STATE_INTO_ADDRESS is on, state is already returned previously with getFullAddress
		if (!in_array($this->country_code, $countriesusingstate) && !getDolGlobalString('MAIN_FORCE_STATE_INTO_ADDRESS')
				&& !getDolGlobalString('SOCIETE_DISABLE_STATE') && $this->state) {
			if (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 && $this->region) {
				$out .= ($outdone ? ' - ' : '').$this->region.' - '.$this->state;
			} else {
				$out .= ($outdone ? ' - ' : '').$this->state;
			}
			$outdone++;
		}

		if ($outdone) {
			$out = '<div class="address inline-block">'.$out.'</div>';
		}

		if (!empty($this->phone) || !empty($this->phone_pro) || !empty($this->phone_mobile) || !empty($this->phone_perso) || !empty($this->fax) || !empty($this->office_phone) || !empty($this->user_mobile) || !empty($this->office_fax)) {
			$out .= ($outdone ? '<br>' : '');
		}
		if (!empty($this->phone) && empty($this->phone_pro)) {		// For objects that store pro phone into ->phone
			$out .= dol_print_phone($this->phone, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->phone_pro)) {
			$out .= dol_print_phone($this->phone_pro, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->phone_mobile)) {
			$out .= dol_print_phone($this->phone_mobile, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
			$outdone++;
		}
		if (!empty($this->phone_perso)) {
			$out .= dol_print_phone($this->phone_perso, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePerso"));
			$outdone++;
		}
		if (!empty($this->office_phone)) {
			$out .= dol_print_phone($this->office_phone, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'phone', $langs->trans("PhonePro"));
			$outdone++;
		}
		if (!empty($this->user_mobile)) {
			$out .= dol_print_phone($this->user_mobile, $this->country_code, $contactid, $thirdpartyid, 'AC_TEL', '&nbsp;', 'mobile', $langs->trans("PhoneMobile"));
			$outdone++;
		}
		if (!empty($this->fax)) {
			$out .= dol_print_phone($this->fax, $this->country_code, $contactid, $thirdpartyid, 'AC_FAX', '&nbsp;', 'fax', $langs->trans("Fax"));
			$outdone++;
		}
		if (!empty($this->office_fax)) {
			$out .= dol_print_phone($this->office_fax, $this->country_code, $contactid, $thirdpartyid, 'AC_FAX', '&nbsp;', 'fax', $langs->trans("Fax"));
			$outdone++;
		}

		if ($out) {
			$out .= '<div style="clear: both;"></div>';
		}
		$outdone = 0;
		if (!empty($this->email)) {
			$out .= dol_print_email($this->email, $this->id, $object->id, 1, 0, 0, 1);
			$outdone++;
		}
		if (!empty($this->url)) {
			//$out.=dol_print_url($this->url,'_goout',0,1);//steve changed to blank
			if (!empty($this->email)) {
				$out .= ' ';
			}
			$out .= dol_print_url($this->url, '_blank', 0, 1);
			$outdone++;
		}

		if (isModEnabled('socialnetworks')) {
			$outsocialnetwork = '';

			if (!empty($this->socialnetworks) && is_array($this->socialnetworks) && count($this->socialnetworks) > 0) {
				$socialnetworksdict = getArrayOfSocialNetworks();
				foreach ($this->socialnetworks as $key => $value) {
					if ($value) {
						$outsocialnetwork .= dol_print_socialnetworks($value, $this->id, $object->id, $key, $socialnetworksdict);
					}
					$outdone++;
				}
			}

			if ($outsocialnetwork) {
				$out .= '<div style="clear: both;">'.$outsocialnetwork.'</div>';
			}
		}

		if ($out) {
			return '<!-- BEGIN part to show address block -->'."\n".$out.'<!-- END Part to show address block -->'."\n";
		} else {
			return '';
		}
	}

	/**
	 * Set to upper or ucwords/lower if needed
	 *
	 * @return void
	 */
	public function setUpperOrLowerCase()
	{
		if (getDolGlobalString('MAIN_TE_PRIVATE_FIRST_AND_LASTNAME_TO_UPPER')) {
			$this->lastname = dol_ucwords(dol_strtolower($this->lastname));
			$this->firstname = dol_ucwords(dol_strtolower($this->firstname));
			if (empty($this->typent_code) || $this->typent_code != "TE_PRIVATE") {
				$this->name = dol_ucwords(dol_strtolower($this->name));
			}
			if (!empty($this->firstname)) {
				$this->lastname = dol_strtoupper($this->lastname);
			}
			if (property_exists($this, 'name_alias')) {
				$this->name_alias = isset($this->name_alias) ? dol_ucwords(dol_strtolower($this->name_alias)) : '';
			}
		}
		if (getDolGlobalString('MAIN_FIRST_TO_UPPER')) {
			$this->lastname = dol_ucwords(dol_strtolower($this->lastname));
			$this->firstname = dol_ucwords(dol_strtolower($this->firstname));
			$this->name = dol_ucwords(dol_strtolower($this->name));
			if (property_exists($this, 'name_alias')) {
				$this->name_alias = isset($this->name_alias) ? dol_ucwords(dol_strtolower($this->name_alias)) : '';
			}
		}
		if (getDolGlobalString('MAIN_ALL_TO_UPPER')) {
			$this->lastname = dol_strtoupper($this->lastname);
			$this->name = dol_strtoupper($this->name);
			if (property_exists($this, 'name_alias')) {
				$this->name_alias = dol_strtoupper($this->name_alias);
			}
		}
		if (getDolGlobalString('MAIN_ALL_TOWN_TO_UPPER')) {
			$this->address = dol_strtoupper($this->address);
			$this->town = dol_strtoupper($this->town);
		}
		if (isset($this->email)) {
			$this->email = dol_strtolower($this->email);
		}
		if (isset($this->personal_email)) {
			$this->personal_email = dol_strtolower($this->personal_email);
		}
	}


	// Methods used by this Trait that must be implemented in the parent class.
	// Note: this helps static type checking

	/**
	 *  Return full address of contact
	 *
	 *  @param      int<0,1>    $withcountry        1=Add country into address string
	 *  @param      string      $sep                Separator to use to build string
	 *  @param      int<0,1>    $withregion         1=Add region into address string
	 *  @param      string      $extralangcode      User extralanguages as value
	 *  @return     string                          Full address string
	 */
	abstract public function getFullAddress($withcountry = 0, $sep = "\n", $withregion = 0, $extralangcode = '');


	/**
	 *  Function to get alternative languages of a data into $this->array_languages
	 *  This method is NOT called by method fetch of objects but must be called separately.
	 *
	 *  @return int<-1,1>                   Return integer <0 if error, 0 if no values of alternative languages to find nor found, 1 if a value was found and loaded
	 *  @see fetch_optionnals()
	 */
	abstract public function fetchValuesForExtraLanguages();
}
