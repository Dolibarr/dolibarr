<?php

	//
	//  FPDM - Filter ASCII Hex
	//  NOTE: Not tested but should work.
	//

	if(isset($FPDM_FILTERS)) array_push($FPDM_FILTERS,"ASCIIHexDecode");

	class FilterASCIIHex {


		/**
		*Get a binary string from its hexadecimal representation
		*
		*@internal same as _hex2bin ($hexString)
		*@access public
		*@note Function was written because PHP has a bin2hex, but not a hex2bin!
		*@internal note pack(�C�,hexdec(substr($data,$i,2))) DOES NOT WORK
		*
		**/
	  function decode($data) {

			$hexString=$data;
			$BinStr = '';

			$hexLength=strlen($hexString);
			// only hex numbers is allowed
			 if ($hexLength % 2 != 0 || preg_match("/[^\da-fA-F]/",$hexString)) return FALSE;


			//Loop through the input and convert it
			for ($i = 0; $i < $hexLength; $i += 2)
				$BinStr .= '%'.substr ($hexString, $i, 2);


			// Raw url-decode and return the result
			return rawurldecode ($BinStr);//chr(hexdec())
	  }


		/**
		*Encodes a binary string to its hexadecimal representation
		*
		*@internal same as bin2hex
		*@access public
		*@internal  dechex(ord($str{$i})); is buggy because for hex value of 0-15 heading 0 is missing! Using sprintf() to get it right.
		*@param string $str a binary string
		*@return string hex the hexified string
		**/
		function encode($data) {
		//----------------------
			$str=$data;
			$hex = "";
			$i = 0;
			do {
				$hex .= sprintf("%02x", ord($str[$i]));	// @CHANGE DOL ($str{$i} curly-brace offset syntax removed in PHP 8.0)
				$i++;
			} while ($i < strlen($str));
			return $hex;
		}

	}

?>
