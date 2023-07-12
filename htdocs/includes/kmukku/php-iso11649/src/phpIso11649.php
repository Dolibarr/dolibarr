<?php 

namespace kmukku\phpIso11649;

class phpIso11649 { 

	var $charTable = array(
		"A" => 10, 
		"B" => 11, 
		"C" => 12, 
		"D" => 13, 
		"E" => 14, 
		"F" => 15, 
		"G" => 16, 
		"H" => 17, 
		"I" => 18, 
		"J" => 19, 
		"K" => 20, 
		"L" => 21, 
		"M" => 22, 
		"N" => 23, 
		"O" => 24, 
		"P" => 25, 
		"Q" => 26, 
		"R" => 27, 
		"S" => 28, 
		"T" => 29, 
		"U" => 30, 
		"V" => 31, 
		"W" => 32, 
		"X" => 33, 
		"Y" => 34, 
		"Z" => 35
		);

	private function normalizeRef($ref) {
		return strtoupper(preg_replace('/\s+/','', $ref));
	}

	private function replaceChars($string) {
		return str_replace(array_keys($this->charTable), array_values($this->charTable), strtoupper($string));
	}

	public function calculateRfChecksum($ref) {
		$preResult = $ref."RF00"; // add 'RF00' to the end of ref
		$preResult = $this->replaceChars($preResult); // Replace to numeric
		$checksum = (98 - bcmod($preResult, '97')); // Calculate checksum
		$checksum = str_pad($checksum, 2, "0", STR_PAD_LEFT); // pad to 2 digits if under 10
		return $checksum;
	}

	public function generateRfReference($input, $chunksplit = true) {
		$normalizedRef = $this->normalizeRef($input); // Remove whitespace, uppercase
		$checksum = $this->calculateRFChecksum($normalizedRef); // Generate checksum
		$rfReference = "RF".$checksum.$normalizedRef; // Join to required format
		if($this->validateRfReference($rfReference)) { // Check if validates
			return ($chunksplit) ? chunk_split($rfReference,4,' ') : $rfReference;
		} else {
			return false;
		}
	}

	public function validateRfReference($ref) {
		$pre = $this->normalizeRef($ref); // Remove whitespace, uppercase

		if (!preg_match('/^[\w ]*$/', $pre)) { // Only A-Z and numbers are allowed
			return false;
		}

		$ref = substr($pre,4).substr($pre,0,4); // Move first 4 chars to the end of $ref
		$num = $this->replaceChars($ref); // Replace to numeric
		// Valid if up to 25 characters long and remainder is 1
		return ((strlen($pre) < 26) && bcmod($num, '97') == 1);
	}

}
