<?php
use Splash\Tests\WsObjects\O00ObjectBaseTest;
use JMS\Serializer\Exception\ObjectConstructionException;

/* Copyright (C)           Kai Blankenhorn      <kaib@bitfolge.de>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
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
	return str_replace(";", "\;", (dol_quoted_printable_encode(utf8_decode($string))));
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
 *	Class to buld vCard files
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
	public $encoding = "ISO-8859-1;ENCODING=QUOTED-PRINTABLE";


	/**
	 *  mise en forme du numero de telephone
	 *
	 *  @param	int		$number		numero de telephone
	 *  @param	string	$type		Type
	 *  @return	void
	 */
	public function setPhoneNumber($number, $type = "")
	{
		// type may be PREF | WORK | HOME | VOICE | FAX | MSG | CELL | PAGER | BBS | CAR | MODEM | ISDN | VIDEO or any senseful combination, e.g. "PREF;WORK;VOICE"
		$key = "TEL";
		if ($type != "") {
			$key .= ";".$type;
		}
		$key .= ";CHARSET=".$this->encoding;
		$this->properties[$key] = encode($number);
	}

	/**
	 *	mise en forme de la photo
	 *  warning NON TESTE !
	 *
	 *  @param  string  $type			Type
	 *  @param  string  $photo			Photo
	 *  @return	void
	 */
	public function setPhoto($type, $photo)
	{
		// $type = "GIF" | "JPEG"
		$this->properties["PHOTO;TYPE=$type;ENCODING=BASE64"] = base64_encode($photo);
	}

	/**
	 *	mise en forme du nom formate
	 *
	 *	@param	string	$name			Name
	 *	@return	void
	 */
	public function setFormattedName($name)
	{
		$this->properties["FN;CHARSET=".$this->encoding] = encode($name);
	}

	/**
	 *	mise en forme du nom complet
	 *
	 *	@param	string	$family			Family name
	 *	@param	string	$first			First name
	 *	@param	string	$additional		Additional (e.g. second name, nick name)
	 *	@param	string	$prefix			Prefix (e.g. "Mr.", "Ms.", "Prof.")
	 *	@param	string	$suffix			Suffix (e.g. "sen." for senior, "jun." for junior)
	 *	@return	void
	 */
	public function setName($family = "", $first = "", $additional = "", $prefix = "", $suffix = "")
	{
		$this->properties["N;CHARSET=".$this->encoding] = encode($family).";".encode($first).";".encode($additional).";".encode($prefix).";".encode($suffix);
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
		// $date format is YYYY-MM-DD - RFC 2425 and RFC 2426
		$this->properties["BDAY"] = dol_print_date($date, 'dayrfc');
	}

	/**
	 *	mise en forme de l'adresse
	 *
	 *	@param	string	$postoffice		Postoffice
	 *	@param	string	$extended		Extended
	 *	@param	string	$street			Street
	 *	@param	string	$city			City
	 *	@param	string	$region			Region
	 *	@param	string	$zip			Zip
	 *	@param	string	$country		Country
	 *	@param	string	$type			Type
	 *	@return	void
	 */
	public function setAddress($postoffice = "", $extended = "", $street = "", $city = "", $region = "", $zip = "", $country = "", $type = "HOME;POSTAL")
	{
		// $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK or any combination of these: e.g. "WORK;PARCEL;POSTAL"
		$key = "ADR";
		if ($type != "") {
			$key .= ";".$type;
		}
		$key .= ";CHARSET=".$this->encoding;
		$this->properties[$key] = ";".encode($extended).";".encode($street).";".encode($city).";".encode($region).";".encode($zip).";".encode($country);

		//if ($this->properties["LABEL;".$type.";CHARSET=".$this->encoding] == '') {
			//$this->setLabel($postoffice, $extended, $street, $city, $region, $zip, $country, $type);
		//}
	}

	/**
	 *  mise en forme du label
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
	 */
	public function setLabel($postoffice = "", $extended = "", $street = "", $city = "", $region = "", $zip = "", $country = "", $type = "HOME;POSTAL")
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

		$this->properties["LABEL;$type;CHARSET=".$this->encoding] = encode($label);
	}

	/**
	 *	Add a e-mail address to this vCard
	 *
	 *	@param	string	$address		E-mail address
	 *	@param	string	$type			(optional) The type of the e-mail (typical "PREF;INTERNET" or "INTERNET")
	 *	@return	void
	 */
	public function setEmail($address, $type = "TYPE=INTERNET;PREF")
	{
		$key = "EMAIL";
		if ($type != "") {
			$key .= ";".$type;
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
		$this->properties["NOTE;CHARSET=".$this->encoding] = encode($note);
	}

	/**
	 * 	mise en forme de la fonction
	 *
	 *	@param	string	$title		Title
	 *	@return	void
	 */
	public function setTitle($title)
	{
		$this->properties["TITLE;CHARSET=".$this->encoding] = encode($title);
	}


	/**
	 *  mise en forme de la societe
	 *
	 *  @param	string	$org		Org
	 *  @return	void
	 */
	public function setOrg($org)
	{
		$this->properties["ORG;CHARSET=".$this->encoding] = encode($org);
	}


	/**
	 * 	mise en forme du logiciel generateur
	 *
	 *  @param	string	$prodid		Prodid
	 *	@return	void
	 */
	public function setProdId($prodid)
	{
		$this->properties["PRODID;CHARSET=".$this->encoding] = encode($prodid);
	}


	/**
	 * 	mise en forme du logiciel generateur
	 *
	 *  @param	string	$uid	Uid
	 *	@return	void
	 */
	public function setUID($uid)
	{
		$this->properties["UID;CHARSET=".$this->encoding] = encode($uid);
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
		$text .= "VERSION:3.0\r\n";
		//$text.= "VERSION:2.1\r\n";
		foreach ($this->properties as $key => $value) {
			$text .= "$key:$value\r\n";
		}
		$text .= "REV:".date("Y-m-d")."T".date("H:i:s")."Z\r\n";
		$text .= "MAILER: Dolibarr\r\n";
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
	 *
	 * @param	Object		$object		Object
	 * @param	Societe		$company	Company
	 * @param	Translate	$langs		Lang object
	 * @return	string					String
	 */
	public function buildVCardString($object, $company, $langs)
	{
		$this->setProdId('Dolibarr '.DOL_VERSION);

		$this->setUid('DOLIBARR-USERID-'.$object->id);
		$this->setName($object->lastname, $object->firstname, "", $object->civility_code, "");
		$this->setFormattedName($object->getFullName($langs, 1));

		$this->setPhoneNumber($object->office_phone, "TYPE=WORK;VOICE");
		$this->setPhoneNumber($object->personal_mobile, "TYPE=HOME;VOICE");
		$this->setPhoneNumber($object->user_mobile, "TYPE=CELL;VOICE");
		$this->setPhoneNumber($object->office_fax, "TYPE=WORK;FAX");

		$country = $object->country_code ? $object->country : '';

		$this->setAddress("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK;POSTAL");
		$this->setLabel("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK");

		$this->setEmail($object->email, "TYPE=WORK");
		$this->setNote($object->note_public);
		$this->setTitle($object->job);

		if ($company->id > 0) {
			$this->setURL($company->url, "TYPE=WORK");
			if (!$object->office_phone) {
				$this->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
			}
			if (!$object->office_fax) {
				$this->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
			}
			if (!$object->zip) {
				$this->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
			}

			// when company e-mail is empty, use only user e-mail
			if (empty(trim($company->email))) {
				// was set before, don't set twice
			} elseif (empty(trim($object->email))) {
				// when user e-mail is empty, use only company e-mail
				$this->setEmail($company->email, "TYPE=WORK");
			} else {
				$tmpuser2 = explode("@", trim($object->email));
				$tmpcompany = explode("@", trim($company->email));

				if (strtolower(end($tmpuser2)) == strtolower(end($tmpcompany))) {
					// when e-mail domain of user and company are the same, use user e-mail at first (and company e-mail at second)
					$this->setEmail($object->email, "TYPE=WORK");

					// support by Microsoft Outlook (2019 and possible earlier)
					$this->setEmail($company->email, 'INTERNET');
				} else {
					// when e-mail of user and company complete different use company e-mail at first (and user e-mail at second)
					$this->setEmail($company->email, "TYPE=WORK");

					// support by Microsoft Outlook (2019 and possible earlier)
					$this->setEmail($object->email, 'INTERNET');
				}
			}

			// Si user lie a un tiers non de type "particulier"
			if ($company->typent_code != 'TE_PRIVATE') {
				$this->setOrg($company->name);
			}
		}

		// Personal informations
		$this->setPhoneNumber($object->personal_mobile, "TYPE=HOME;VOICE");
		if ($object->birth) {
			$this->setBirthday($object->birth);
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
