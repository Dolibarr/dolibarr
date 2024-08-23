<?php
/* Copyright (C)           Kai Blankenhorn      <kaib@bitfolge.de>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
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
 *	\file       htdocs/core/class/vcard.class.php
 *	\brief      Class to manage vCard files
 */


/**
 * Encode a string for vCard
 *
 * @param	string	$string		String to encode
 * @return	string				String encoded
 */
function encode($string)
{
	return str_replace(";", "\;", (dol_quoted_printable_encode($string)));
}


/**
 * Taken from php documentation comments
 * No more used
 *
 * @param	string	$input		String
 * @param	int		$line_max	Max length of lines
 * @return	string				Encoded string
 */
function dol_quoted_printable_encode($input, $line_max = 76)
{
	$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	$lines = preg_split("/(\?:\r\n|\r|\n)/", $input);
	$eol = "\r\n";
	$linebreak = "=0D=0A";
	$escape = "=";
	$output = "";

	$num = count($lines);
	for ($j = 0; $j < $num; $j++) {
		$line = $lines[$j];
		$linlen = strlen($line);
		$newline = "";
		for ($i = 0; $i < $linlen; $i++) {
			$c = substr($line, $i, 1);
			$dec = ord($c);
			if (($dec == 32) && ($i == ($linlen - 1))) { // convert space at eol only
				$c = "=20";
			} elseif (($dec == 61) || ($dec < 32) || ($dec > 126)) { // always encode "\t", which is *not* required
				$h2 = floor($dec / 16);
				$h1 = floor($dec % 16);
				$c = $escape.$hex["$h2"].$hex["$h1"];
			}
			if ((strlen($newline) + strlen($c)) >= $line_max) { // CRLF is not counted
				$output .= $newline.$escape.$eol; // soft line break; " =\r\n" is okay
				$newline = "    ";
			}
			$newline .= $c;
		} // end of for
		$output .= $newline;
		if ($j < count($lines) - 1) {
			$output .= $linebreak;
		}
	}
	return trim($output);
}


/**
 *	Class to build vCard files
 */
class vCard
{
	/**
	 * @var array array of properties
	 */
	public $properties;

	/**
	 * @var string filename
	 */
	public $filename;

	/**
	 * @var string encoding
	 */
	public $encoding = "ENCODING=QUOTED-PRINTABLE";


	/**
	 *  mise en forme du numero de telephone
	 *
	 *  @param	int		$number		numero de telephone
	 *  @param	string	$type		Type ('cell')
	 *  @return	void
	 */
	public function setPhoneNumber($number, $type = "")
	{
		// type may be PREF | WORK | HOME | VOICE | FAX | MSG | CELL | PAGER | BBS | CAR | MODEM | ISDN | VIDEO or any senseful combination, e.g. "PREF;WORK;VOICE"
		$key = "TEL";
		if ($type != "") {
			$key .= ";".$type;
		}
		$key .= ";VALUE=uri";
		//$key .= ";".$this->encoding;
		$this->properties[$key] = 'tel:'.$number;
	}

	/**
	 *	mise en forme de la photo
	 *  warning NON TESTE !
	 *
	 *  @param  string  $type			Type 'image/jpeg' or 'JPEG'
	 *  @param  string  $photo			Photo
	 *  @return	void
	 */
	public function setPhoto($type, $photo)
	{
		// $type = "GIF" | "JPEG"
		//$this->properties["PHOTO;MEDIATYPE=$type;ENCODING=BASE64"] = base64_encode($photo);
		$this->properties["PHOTO;MEDIATYPE=$type"] = $photo;		// must be url of photo
		//$this->properties["PHOTO;TYPE=$type;ENCODING=BASE64"] = base64_encode($photo);   // must be content of image
	}

	/**
	 *	mise en forme du nom format
	 *
	 *	@param	string	$name			Name
	 *	@return	void
	 */
	public function setFormattedName($name)
	{
		$this->properties["FN;".$this->encoding] = encode($name);
	}

	/**
	 *	mise en forme du nom complete
	 *
	 *	@param	string	$family			Family name
	 *	@param	string	$first			First name
	 *	@param	string	$additional		Additional (e.g. second name, nick name)
	 *	@param	string	$prefix			Title prefix (e.g. "Mr.", "Ms.", "Prof.")
	 *	@param	string	$suffix			Suffix (e.g. "sen." for senior, "jun." for junior)
	 *	@return	void
	 */
	public function setName($family = "", $first = "", $additional = "", $prefix = "", $suffix = "")
	{
		//$this->properties["N;".$this->encoding] = encode($family).";".encode($first).";".encode($additional).";".encode($prefix).";".encode($suffix);
		$this->properties["N"] = encode($family).";".encode($first).";".encode($additional).";".encode($prefix).";".encode($suffix);
		$this->filename = "$first%20$family.vcf";
		if (empty($this->properties["FN"])) {
			$this->setFormattedName(trim("$prefix $first $additional $family $suffix"));
		}
	}

	/**
	 *	mise en forme de l'anniversaire
	 *
	 *	@param	integer	  $date		Date
	 *	@return	void
	 */
	public function setBirthday($date)
	{
		// $date format is YYYY-MM-DD - RFC 2425 and RFC 2426 for vcard v3
		// $date format is YYYYMMDD or ISO8601 for vcard v4
		$this->properties["BDAY"] = dol_print_date($date, 'dayxcard');
	}

	/**
	 *	Address
	 *
	 *	@param	string	$postoffice		Postoffice
	 *	@param	string	$extended		Extended
	 *	@param	string	$street			Street
	 *	@param	string	$city			City
	 *	@param	string	$region			Region
	 *	@param	string	$zip			Zip
	 *	@param	string	$country		Country
	 *	@param	string	$type			Type
	 *  @param	string	$label			Label
	 *	@return	void
	 */
	public function setAddress($postoffice = "", $extended = "", $street = "", $city = "", $region = "", $zip = "", $country = "", $type = "", $label = "")
	{
		// $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK or any combination of these: e.g. "WORK;PARCEL;POSTAL"
		$key = "ADR";
		if ($type != "") {
			$key .= ";".$type;
		}
		if ($label != "") {
			$key .= ';LABEL="'.encode($label).'"';
		}
		$key .= ";".$this->encoding;
		$this->properties[$key] = encode($postoffice).";".encode($extended).";".encode($street).";".encode($city).";".encode($region).";".encode($zip).";".encode($country);

		//if ($this->properties["LABEL;".$type.";".$this->encoding] == '') {
		//$this->setLabel($postoffice, $extended, $street, $city, $region, $zip, $country, $type);
		//}
	}

	/**
	 *  Address (old standard)
	 *
	 *  @param	string	$postoffice		Postoffice
	 *  @param	string	$extended		Extended
	 *  @param	string	$street			Street
	 *  @param	string	$city			City
	 *  @param	string	$region			Region
	 *  @param	string	$zip			Zip
	 *  @param	string	$country		Country
	 *  @param	string	$type			Type
	 *  @return	void
	 *  @deprecated
	 */
	public function setLabel($postoffice = "", $extended = "", $street = "", $city = "", $region = "", $zip = "", $country = "", $type = "HOME")
	{
		$label = "";
		if ($postoffice != "") {
			$label .= "$postoffice\r\n";
		}
		if ($extended != "") {
			$label .= "$extended\r\n";
		}
		if ($street != "") {
			$label .= "$street\r\n";
		}
		if ($zip != "") {
			$label .= "$zip ";
		}
		if ($city != "") {
			$label .= "$city\r\n";
		}
		if ($region != "") {
			$label .= "$region\r\n";
		}
		if ($country != "") {
			$country .= "$country\r\n";
		}

		$this->properties["LABEL;$type;".$this->encoding] = encode($label);
	}

	/**
	 *	Add a e-mail address to this vCard
	 *
	 *	@param	string	$address		E-mail address
	 *	@param	string	$type			(optional) The type of the e-mail (typical "PREF" or "INTERNET")
	 *	@return	void
	 */
	public function setEmail($address, $type = "")
	{
		$key = "EMAIL";
		if ($type == "PREF") {
			$key .= ";PREF=1";
		} elseif (!empty($type)) {
			$key .= ";TYPE=".dol_strtolower($type);
		}
		$this->properties[$key] = $address;
	}

	/**
	 *	mise en forme de la note
	 *
	 *	@param	string	$note		Note
	 *	@return	void
	 */
	public function setNote($note)
	{
		$this->properties["NOTE;".$this->encoding] = encode($note);
	}

	/**
	 * 	mise en forme de la fonction
	 *
	 *	@param	string	$title		Title
	 *	@return	void
	 */
	public function setTitle($title)
	{
		$this->properties["TITLE;".$this->encoding] = encode($title);
	}


	/**
	 *  mise en forme de la societe
	 *
	 *  @param	string	$org		Org
	 *  @return	void
	 */
	public function setOrg($org)
	{
		$this->properties["ORG;".$this->encoding] = encode($org);
	}


	/**
	 * 	mise en forme du logiciel generateur
	 *
	 *  @param	string	$prodid		Prodid
	 *	@return	void
	 */
	public function setProdId($prodid)
	{
		$this->properties["PRODID"] = encode($prodid);
	}


	/**
	 * 	mise en forme du logiciel generateur
	 *
	 *  @param	string	$uid	Uid
	 *	@return	void
	 */
	public function setUID($uid)
	{
		$this->properties["UID"] = encode($uid);
	}


	/**
	 *  mise en forme de l'url
	 *
	 *	@param	string	$url		URL
	 *  @param	string	$type		Type
	 *	@return	void
	 */
	public function setURL($url, $type = "")
	{
		// $type may be WORK | HOME
		$key = "URL";
		if ($type != "") {
			$key .= ";$type";
		}
		$this->properties[$key] = $url;
	}

	/**
	 *  permet d'obtenir une vcard
	 *
	 *  @return	string
	 */
	public function getVCard()
	{
		$text = "BEGIN:VCARD\r\n";
		$text .= "VERSION:4.0\r\n";		// With V4, all encoding are UTF-8
		//$text.= "VERSION:2.1\r\n";
		foreach ($this->properties as $key => $value) {
			$newkey = preg_replace('/-.*$/', '', $key);	// remove suffix -twitter, -facebook, ...
			$text .= $newkey.":".$value."\r\n";
		}
		$text .= "REV:".date("Ymd")."T".date("His")."Z\r\n";
		//$text .= "MAILER: Dolibarr\r\n";
		$text .= "END:VCARD\r\n";
		return $text;
	}

	/**
	 *  permet d'obtenir le nom de fichier
	 *
	 *  @return	string		Filename
	 */
	public function getFileName()
	{
		return $this->filename;
	}

	/**
	 * Return a VCARD string
	 * See RFC https://datatracker.ietf.org/doc/html/rfc6350
	 *
	 * @param	Object			$object		Object (User or Contact)
	 * @param	Societe|null	$company	Company. May be null
	 * @param	Translate		$langs		Lang object
	 * @param	string			$urlphoto	Full public URL of photo
	 * @return	string						String
	 */
	public function buildVCardString($object, $company, $langs, $urlphoto = '')
	{
		global $dolibarr_main_instance_unique_id;

		$this->setProdId('Dolibarr '.DOL_VERSION);

		$this->setUID('DOLIBARR-USERID-'.dol_trunc(md5('vcard'.$dolibarr_main_instance_unique_id), 8, 'right', 'UTF-8', 1).'-'.$object->id);
		$this->setName($object->lastname, $object->firstname, "", $object->civility_code, "");
		$this->setFormattedName($object->getFullName($langs, 1));

		if ($urlphoto) {
			$mimetype = dol_mimetype($urlphoto);
			if ($mimetype) {
				$this->setPhoto($mimetype, $urlphoto);
			}
		}

		if ($object->office_phone) {
			$this->setPhoneNumber($object->office_phone, "TYPE=WORK,VOICE");
		}
		/* disabled
		if ($object->personal_mobile) {
			$this->setPhoneNumber($object->personal_mobile, "TYPE=CELL,VOICE");
		}*/
		if ($object->user_mobile) {
			$this->setPhoneNumber($object->user_mobile, "TYPE=CELL,VOICE");
		}
		if ($object->office_fax) {
			$this->setPhoneNumber($object->office_fax, "TYPE=WORK,FAX");
		}

		if (!empty($object->socialnetworks)) {
			foreach ($object->socialnetworks as $key => $val) {
				if (empty($val)) {	// Discard social network if empty
					continue;
				}
				$urlsn = '';
				if ($key == 'linkedin') {
					if (!preg_match('/^http/', $val)) {
						$urlsn = 'https://www.'.$key.'.com/company/'.urlencode($val);
					} else {
						$urlsn = $val;
					}
				} elseif ($key == 'youtube') {
					if (!preg_match('/^http/', $val)) {
						$urlsn = 'https://www.'.$key.'.com/user/'.urlencode($val);
					} else {
						$urlsn = $val;
					}
				} else {
					if (!preg_match('/^http/', $val)) {
						$urlsn = 'https://www.'.$key.'.com/'.urlencode($val);
					} else {
						$urlsn = $val;
					}
				}
				if ($urlsn) {
					$this->properties["SOCIALPROFILE;TYPE=WORK-".$key] = $key.':'.$urlsn;
				}
			}
		}

		$country = $object->country_code ? $object->country : '';

		// User address
		if (!($object->element != 'user') || getDolUserInt('USER_PUBLIC_SHOW_ADDRESS', 0, $object)) {
			if ($object->address || $object->town || $object->state || $object->zip || $object->country) {
				$this->setAddress("", "", $object->address, $object->town, $object->state, $object->zip, $country, "");
				//$this->setLabel("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=HOME");
			}
		}

		if ($object->email) {
			$this->setEmail($object->email, "TYPE=WORK");
		}
		/* disabled
		if ($object->personal_email) {
			$this->setEmail($object->personal_email, "TYPE=HOME");
		} */
		if ($object->note_public) {
			$this->setNote($object->note_public);
		}
		if ($object->job) {
			$this->setTitle($object->job);
		}

		// For user, $object->url is not defined
		// For contact, $object->url is not defined
		if (!empty($object->url)) {
			$this->setURL($object->url, "");
		}

		if (is_object($company)) {
			// Si user linked to a thirdparty and not a physical people
			if ($company->typent_code != 'TE_PRIVATE') {
				$this->setOrg($company->name);
			}

			$this->setURL($company->url, "");

			if ($company->phone && $company->phone != $object->office_phone) {
				$this->setPhoneNumber($company->phone, "TYPE=WORK,VOICE");
			}
			if ($company->fax && $company->fax != $object->office_fax) {
				$this->setPhoneNumber($company->fax, "TYPE=WORK,FAX");
			}
			if ($company->address || $company->town || $company->state || $company->zip || $company->country) {
				$this->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK");
			}

			if ($company->email && $company->email != $object->email) {
				$this->setEmail($company->email, "TYPE=WORK");
			}

			/*
			if (!empty($company->socialnetworks)) {
				foreach ($company->socialnetworks as $key => $val) {
					$urlsn = '';
					if ($key == 'linkedin') {
						if (!preg_match('/^http/', $val)) {
							$urlsn = 'https://www.'.$key.'.com/company/'.urlencode($val);
						} else {
							$urlsn = $val;
						}
					} elseif ($key == 'youtube') {
						if (!preg_match('/^http/', $val)) {
							$urlsn = 'https://www.'.$key.'.com/user/'.urlencode($val);
						} else {
							$urlsn = $val;
						}
					} else {
						if (!preg_match('/^http/', $val)) {
							$urlsn = 'https://www.'.$key.'.com/'.urlencode($val);
						} else {
							$urlsn = $val;
						}
					}
					if ($urlsn) {
						$this->properties["socialProfile;type=".$key] = $urlsn;
					}
				}
			}
			*/
		}

		// Birthday
		if (!($object->element != 'user') || getDolUserInt('USER_PUBLIC_SHOW_BIRTH', 0, $object)) {
			if ($object->birth) {
				$this->setBirthday($object->birth);
			}
		}

		// Return VCard string
		return $this->getVCard();
	}


	/* Example from Microsoft Outlook 2019

	BEGIN:VCARD
	VERSION:2.1

	N;LANGUAGE=de:surename;forename;secondname;Sir;jun.
	FN:Sir surename secondname forename jun.
	ORG:Companyname
	TITLE:position
	TEL;WORK;VOICE:work-phone-number
	TEL;HOME;VOICE:private-phone-number
	TEL;CELL;VOICE:mobile-phone-number
	TEL;WORK;FAX:fax-phone-number
	ADR;WORK;PREF:;;street and number;town;region;012345;Deutschland
	LABEL;WORK;PREF;ENCODING=QUOTED-PRINTABLE:street and number=0D=0A=
	=0D=0A=
	012345  town  region
	X-MS-OL-DEFAULT-POSTAL-ADDRESS:2
	URL;WORK:www.mywebpage.de
	EMAIL;PREF;INTERNET:test1@test1.de
	EMAIL;INTERNET:test2@test2.de
	EMAIL;INTERNET:test3@test3.de
	X-MS-IMADDRESS:test@jabber.org
	REV:20200424T104242Z

	END:VCARD
	*/
}
