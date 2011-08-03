<?php
/***************************************************************************

php vCard class
(c) Kai Blankenhorn
www.bitfolge.de/en
kaib@bitfolge.de


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

***************************************************************************
2007	v3.0	Laurent Destailleur		eldy@users.sourceforge.net
Added functions (as in http://www.ietf.org/rfc/rfc2426.txt):
setTitle  setOrg setProdId	setUID
***************************************************************************/

/**
 *	\file       htdocs/includes/vcard/vcard.class.php
 *	\brief      Classe permettant de creer un fichier vcard.
 *	\author     Kai Blankenhorn.
 *	\version    2.0
 *
 *  Ensemble des fonctions permettant de creer un fichier vcard.
 */

function encode($string) {
	//return escape($string);
	return escape(dol_quoted_printable_encode(utf8_decode($string)));
}

function escape($string) {
	return str_replace(";","\;",$string);
}

/** \brief	Taken from php documentation comments
 *  No more used
 */
function dol_quoted_printable_encode($input, $line_max = 76) {
	$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
	$lines = preg_split("/(\?:\r\n|\r|\n)/", $input);
	$eol = "\r\n";
	$linebreak = "=0D=0A";
	$escape = "=";
	$output = "";

	for ($j=0;$j<count($lines);$j++) {
		$line = $lines[$j];
		$linlen = strlen($line);
		$newline = "";
		for($i = 0; $i < $linlen; $i++) {
			$c = substr($line, $i, 1);
			$dec = ord($c);
			if ( ($dec == 32) && ($i == ($linlen - 1)) ) { // convert space at eol only
				$c = "=20";
			} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) { // always encode "\t", which is *not* required
				$h2 = floor($dec/16); $h1 = floor($dec%16);
				$c = $escape.$hex["$h2"].$hex["$h1"];
			}
			if ( (strlen($newline) + strlen($c)) >= $line_max ) { // CRLF is not counted
				$output .= $newline.$escape.$eol; // soft line break; " =\r\n" is okay
				$newline = "    ";
			}
			$newline .= $c;
		} // end of for
		$output .= $newline;
		if ($j<count($lines)-1) $output .= $linebreak;
	}
	return trim($output);
}

/** \class vCard
 \brief Classe permettant de creer un fichier vcard

 Ensemble des fonctions permettant de creer un fichier vcard
 */

class vCard {
	var $properties;
	var $filename;

	//var $encoding="UTF-8";
	var $encoding="ISO-8859-1;ENCODING=QUOTED-PRINTABLE";

	/**
		\brief mise en forme du numero de telephone
		\param	number		numero de telephone
		\param	type
		*/

	function setPhoneNumber($number, $type="") {
		// type may be PREF | WORK | HOME | VOICE | FAX | MSG | CELL | PAGER | BBS | CAR | MODEM | ISDN | VIDEO or any senseful combination, e.g. "PREF;WORK;VOICE"
		$key = "TEL";
		if ($type!="") $key .= ";".$type;
		$key.= ";CHARSET=".$this->encoding;
		$this->properties[$key] = encode($number);
	}

	/**
		\brief mise en forme de la photo
		\param	type
		\param	photo
		\warning NON TESTE !
		*/

	// UNTESTED !!!
	function setPhoto($type, $photo) { // $type = "GIF" | "JPEG"
		$this->properties["PHOTO;TYPE=$type;ENCODING=BASE64"] = base64_encode($photo);
	}

	/**
		\brief mise en forme du nom formate
		\param	name
		*/

	function setFormattedName($name) {
		$this->properties["FN;CHARSET=".$this->encoding] = encode($name);
	}

	/**
		\brief mise en forme du nom complet
		\param	family
		\param	first
		\param	additional
		\param	prefix
		\param	suffix
		*/

	function setName($family="", $first="", $additional="", $prefix="", $suffix="") {
		$this->properties["N;CHARSET=".$this->encoding] = encode($family).";".encode($first).";".encode($additional).";".encode($prefix).";".encode($suffix);
		$this->filename = "$first%20$family.vcf";
		if ($this->properties["FN"]=="") $this->setFormattedName(trim("$prefix $first $additional $family $suffix"));
	}

	/**
		\brief mise en forme de l'anniversaire
		\param	date
		*/

	function setBirthday($date) { // $date format is YYYY-MM-DD
		$this->properties["BDAY"] = $date;
	}

	/**
		\brief mise en forme de l'adresse
		\param	postoffice
		\param	extended
		\param	street
		\param	city
		\param	region
		\param	zip
		\param	country
		\param	type
		*/

	function setAddress($postoffice="", $extended="", $street="", $city="", $region="", $zip="", $country="", $type="HOME;POSTAL") {
		// $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK or any combination of these: e.g. "WORK;PARCEL;POSTAL"
		$key = "ADR";
		if ($type!="") $key.= ";$type";
		$key.= ";CHARSET=".$this->encoding;
		$this->properties[$key] = encode($name).";".encode($extended).";".encode($street).";".encode($city).";".encode($region).";".encode($zip).";".encode($country);

		if ($this->properties["LABEL;$type;CHARSET=".$this->encoding] == "") {
			//$this->setLabel($postoffice, $extended, $street, $city, $region, $zip, $country, $type);
		}
	}

	/**
		\brief mise en forme du label
		\param	postoffice
		\param	extended
		\param	street
		\param	city
		\param	region
		\param	zip
		\param	country
		\param	type
		*/

	function setLabel($postoffice="", $extended="", $street="", $city="", $region="", $zip="", $country="", $type="HOME;POSTAL") {
		$label = "";
		if ($postoffice!="") $label.= "$postoffice\r\n";
		if ($extended!="") $label.= "$extended\r\n";
		if ($street!="") $label.= "$street\r\n";
		if ($zip!="") $label.= "$zip ";
		if ($city!="") $label.= "$city\r\n";
		if ($region!="") $label.= "$region\r\n";
		if ($country!="") $country.= "$country\r\n";

		$this->properties["LABEL;$type;CHARSET=".$this->encoding] = encode($label);
	}

	/**
		\brief 	Mise en forme de l'email
		\param	address		EMail
		\param	type		Vcard type
		*/
	function setEmail($address,$type="internet,pref") {
		$this->properties["EMAIL;TYPE=".$type] = $address;
	}

	/**
		\brief mise en forme de la note
		\param	note
		*/

	function setNote($note) {
		$this->properties["NOTE;CHARSET=".$this->encoding] = encode($note);
	}

	/**
		\brief 	mise en forme de la fonction
		\param	title
		*/
	function setTitle($title) {
		$this->properties["TITLE;CHARSET=".$this->encoding] = encode($title);
	}


	/**
		\brief 	mise en forme de la societe
		\param	org
		*/
	function setOrg($org) {
		$this->properties["ORG;CHARSET=".$this->encoding] = encode($org);
	}


	/**
		\brief 	mise en forme du logiciel generateur
		\param	prodid
		*/
	function setProdId($prodid) {
		$this->properties["PRODID;CHARSET=".$this->encoding] = encode($prodid);
	}


	/**
		\brief 	mise en forme du logiciel generateur
		\param	uid
		*/
	function setUID($uid) {
		$this->properties["UID;CHARSET=".$this->encoding] = encode($uid);
	}


	/**
	 \brief mise en forme de l'url
	 \param	url
	 \param	type
	 */
	function setURL($url, $type="") {
		// $type may be WORK | HOME
		$key = "URL";
		if ($type!="") $key.= ";$type";
		$this->properties[$key] = $url;
	}

	/**
		\brief permet d'obtenir une vcard
		*/

	function getVCard() {
		$text = "BEGIN:VCARD\r\n";
		//$text.= "VERSION:3.0\r\n";
		$text.= "VERSION:2.1\r\n";
		foreach($this->properties as $key => $value) {
			$text.= "$key:$value\r\n";
		}
		$text.= "REV:".date("Y-m-d")."T".date("H:i:s")."Z\r\n";
		$text.= "MAILER:php vCard class by Kai Blankenhorn\r\n";
		$text.= "END:VCARD\r\n";
		return $text;
	}

	/**
		\brief permet d'obtenir le nom de fichier
		*/

	function getFileName() {
		return $this->filename;
	}
}
?>
