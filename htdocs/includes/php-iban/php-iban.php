<?php

# PHP IBAN - http://code.google.com/p/php-iban - LGPLv3

# Global flag by request
$__disable_iiban_gmp_extension=false;

# Verify an IBAN number.  Returns true or false.
#  NOTE: Input can be printed 'IIBAN xx xx xx...' or 'IBAN xx xx xx...' or machine 'xxxxx' format.
function verify_iban($iban) {

 # First convert to machine format.
 $iban = iban_to_machine_format($iban);

 # Get country of IBAN
 $country = iban_get_country_part($iban);

 # Test length of IBAN
 if(strlen($iban)!=iban_country_get_iban_length($country)) { return false; }

 # Get checksum of IBAN
 $checksum = iban_get_checksum_part($iban);

 # Get country-specific IBAN format regex
 $regex = '/'.iban_country_get_iban_format_regex($country).'/';

 # Check regex
 if(preg_match($regex,$iban)) {
  # Regex passed, check checksum
  if(!iban_verify_checksum($iban)) { 
   return false;
  }
 }
 else {
  return false;
 }

 # Otherwise it 'could' exist
 return true;
}

# Convert an IBAN to machine format.  To do this, we
# remove IBAN from the start, if present, and remove
# non basic roman letter / digit characters
function iban_to_machine_format($iban) {
 # Uppercase and trim spaces from left
 $iban = ltrim(strtoupper($iban));
 # Remove IIBAN or IBAN from start of string, if present
 $iban = preg_replace('/^I?IBAN/','',$iban);
 # Remove all non basic roman letter / digit characters
 $iban = preg_replace('/[^a-zA-Z0-9]/','',$iban);
 return $iban;
}

# Convert an IBAN to human format. To do this, we
# simply insert spaces right now, as per the ECBS
# (European Committee for Banking Standards) 
# recommendations available at:
# http://www.europeanpaymentscouncil.eu/knowledge_bank_download.cfm?file=ECBS%20standard%20implementation%20guidelines%20SIG203V3.2.pdf 
function iban_to_human_format($iban) {
 # First verify validity, or return
 if(!verify_iban($iban)) { return false; }
 # Add spaces every four characters
 $human_iban = '';
 for($i=0;$i<strlen($iban);$i++) {
  $human_iban .= substr($iban,$i,1);
  if(($i>0) && (($i+1)%4==0)) { $human_iban .= ' '; }
 }
 return $human_iban;
}

# Get the country part from an IBAN
function iban_get_country_part($iban) {
 $iban = iban_to_machine_format($iban);
 return substr($iban,0,2);
}

# Get the checksum part from an IBAN
function iban_get_checksum_part($iban) {
 $iban = iban_to_machine_format($iban);
 return substr($iban,2,2);
}

# Get the BBAN part from an IBAN
function iban_get_bban_part($iban) {
 $iban = iban_to_machine_format($iban);
 return substr($iban,4);
}

# Check the checksum of an IBAN - code modified from Validate_Finance PEAR class
function iban_verify_checksum($iban) {
 # convert to machine format
 $iban = iban_to_machine_format($iban);
 # move first 4 chars (countrycode and checksum) to the end of the string
 $tempiban = substr($iban, 4).substr($iban, 0, 4);
 # subsitutute chars
 $tempiban = iban_checksum_string_replace($tempiban);
 # mod97-10
 $result = iban_mod97_10($tempiban);
 # checkvalue of 1 indicates correct IBAN checksum
 if ($result != 1) {
  return false;
 }
 return true;
}

# Find the correct checksum for an IBAN
#  $iban  The IBAN whose checksum should be calculated
function iban_find_checksum($iban) {
 $iban = iban_to_machine_format($iban);
 # move first 4 chars to right
 $left = substr($iban,0,2) . '00'; # but set right-most 2 (checksum) to '00'
 $right = substr($iban,4);
 # glue back together
 $tmp = $right . $left;
 # convert letters using conversion table
 $tmp = iban_checksum_string_replace($tmp);
 # get mod97-10 output
 $checksum = iban_mod97_10_checksum($tmp);
 # return 98 minus the mod97-10 output, left zero padded to two digits
 return str_pad((98-$checksum),2,'0',STR_PAD_LEFT);
}

# Set the correct checksum for an IBAN
#  $iban  IBAN whose checksum should be set
function iban_set_checksum($iban) {
 $iban = iban_to_machine_format($iban);
 return substr($iban,0,2) . iban_find_checksum($iban) . substr($iban,4);
}

# Character substitution required for IBAN MOD97-10 checksum validation/generation
#  $s  Input string (IBAN)
function iban_checksum_string_replace($s) {
 $iban_replace_chars = range('A','Z');
 foreach (range(10,35) as $tempvalue) { $iban_replace_values[]=strval($tempvalue); }
 return str_replace($iban_replace_chars,$iban_replace_values,$s);
}

# Same as below but actually returns resulting checksum
function iban_mod97_10_checksum($numeric_representation) {
 $checksum = intval(substr($numeric_representation, 0, 1));
 for ($position = 1; $position < strlen($numeric_representation); $position++) {
  $checksum *= 10;
  $checksum += intval(substr($numeric_representation,$position,1));
  $checksum %= 97;
 }
 return $checksum;
}

# Perform MOD97-10 checksum calculation ('Germanic-level effiency' version - thanks Chris!)
function iban_mod97_10($numeric_representation) {
 global $__disable_iiban_gmp_extension;
 # prefer php5 gmp extension if available
 if(!($__disable_iiban_gmp_extension) && function_exists('gmp_intval')) { return gmp_intval(gmp_mod(gmp_init($numeric_representation, 10),'97')) === 1; }

/*
 # old manual processing (~16x slower)
 $checksum = intval(substr($numeric_representation, 0, 1));
 for ($position = 1; $position < strlen($numeric_representation); $position++) {
  $checksum *= 10;
  $checksum += intval(substr($numeric_representation,$position,1));
  $checksum %= 97;
 }
 return $checksum;
 */

 # new manual processing (~3x slower)
 $length = strlen($numeric_representation);
 $rest = "";
 $position = 0;
 while ($position < $length) {
        $value = 9-strlen($rest);
        $n = $rest . substr($numeric_representation,$position,$value);
        $rest = $n % 97;
        $position = $position + $value;
 }
 return ($rest === 1);
}

# Get an array of all the parts from an IBAN
function iban_get_parts($iban) {
 return array(
         'country'	=>      iban_get_country_part($iban),
 	 'checksum'	=>	iban_get_checksum_part($iban),
	 'bban'		=>	iban_get_bban_part($iban),
 	 'bank'		=>	iban_get_bank_part($iban),
	 'country'	=>	iban_get_country_part($iban),
	 'branch'	=>	iban_get_branch_part($iban),
	 'account'	=>	iban_get_account_part($iban)
        );
}

# Get the Bank ID (institution code) from an IBAN
function iban_get_bank_part($iban) {
 $iban = iban_to_machine_format($iban);
 $country = iban_get_country_part($iban);
 $start = iban_country_get_bankid_start_offset($country);
 $stop = iban_country_get_bankid_stop_offset($country);
 if($start!=''&&$stop!='') {
  $bban = iban_get_bban_part($iban);
  return substr($bban,$start,($stop-$start+1));
 }
 return '';
}

# Get the Branch ID (sort code) from an IBAN
function iban_get_branch_part($iban) {
 $iban = iban_to_machine_format($iban);
 $country = iban_get_country_part($iban);
 $start = iban_country_get_branchid_start_offset($country);
 $stop = iban_country_get_branchid_stop_offset($country);
 if($start!=''&&$stop!='') {
  $bban = iban_get_bban_part($iban);
  return substr($bban,$start,($stop-$start+1));
 }
 return '';
}

# Get the (branch-local) account ID from an IBAN
function iban_get_account_part($iban) {
 $iban = iban_to_machine_format($iban);
 $country = iban_get_country_part($iban);
 $start = iban_country_get_branchid_stop_offset($country);
 if($start=='') {
  $start = iban_country_get_bankid_stop_offset($country);
 }
 if($start!='') {
  $bban = iban_get_bban_part($iban);
  return substr($bban,$start+1);
 }
 return '';
}

# Get the name of an IBAN country
function iban_country_get_country_name($iban_country) {
 return _iban_country_get_info($iban_country,'country_name');
}

# Get the domestic example for an IBAN country
function iban_country_get_domestic_example($iban_country) {
 return _iban_country_get_info($iban_country,'domestic_example');
}

# Get the BBAN example for an IBAN country
function iban_country_get_bban_example($iban_country) {
 return _iban_country_get_info($iban_country,'bban_example');
}

# Get the BBAN format (in SWIFT format) for an IBAN country
function iban_country_get_bban_format_swift($iban_country) {
 return _iban_country_get_info($iban_country,'bban_format_swift');
}

# Get the BBAN format (as a regular expression) for an IBAN country
function iban_country_get_bban_format_regex($iban_country) {
 return _iban_country_get_info($iban_country,'bban_format_regex');
}

# Get the BBAN length for an IBAN country
function iban_country_get_bban_length($iban_country) {
 return _iban_country_get_info($iban_country,'bban_length');
}

# Get the IBAN example for an IBAN country
function iban_country_get_iban_example($iban_country) {
 return _iban_country_get_info($iban_country,'iban_example');
}

# Get the IBAN format (in SWIFT format) for an IBAN country
function iban_country_get_iban_format_swift($iban_country) {
 return _iban_country_get_info($iban_country,'iban_format_swift');
}

# Get the IBAN format (as a regular expression) for an IBAN country
function iban_country_get_iban_format_regex($iban_country) {
 return _iban_country_get_info($iban_country,'iban_format_regex');
}

# Get the IBAN length for an IBAN country
function iban_country_get_iban_length($iban_country) {
 return _iban_country_get_info($iban_country,'iban_length');
}

# Get the BBAN Bank ID start offset for an IBAN country
function iban_country_get_bankid_start_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_bankid_start_offset');
}

# Get the BBAN Bank ID stop offset for an IBAN country
function iban_country_get_bankid_stop_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_bankid_stop_offset');
}

# Get the BBAN Branch ID start offset for an IBAN country
function iban_country_get_branchid_start_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_branchid_start_offset');
}

# Get the BBAN Branch ID stop offset for an IBAN country
function iban_country_get_branchid_stop_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_branchid_stop_offset');
}

# Get the registry edition for an IBAN country
function iban_country_get_registry_edition($iban_country) {
 return _iban_country_get_info($iban_country,'registry_edition');
}

# Is the IBAN country a SEPA member?
function iban_country_is_sepa($iban_country) {
 return _iban_country_get_info($iban_country,'country_sepa');
}

# Get the list of all IBAN countries
function iban_countries() {
 global $_iban_registry;
 return array_keys($_iban_registry);
}

# Given an incorrect IBAN, return an array of zero or more checksum-valid
# suggestions for what the user might have meant, based upon common
# mistranscriptions.
function iban_mistranscription_suggestions($incorrect_iban) {
 
 # abort on ridiculous length input (but be liberal)
 $length = strlen($incorrect_iban);
 if($length<5 || $length>34) { return array('(length bad)'); }

 # abort if mistranscriptions data is unable to load
 if(!_iban_load_mistranscriptions()) { return array('(failed to load)'); }

 # init
 global $_iban_mistranscriptions;
 $suggestions = array();

 # we have a string of approximately IBAN-like length.
 # ... now let's make suggestions.
 $numbers = array('0','1','2','3','4','5','6','7','8','9');
 for($i=0;$i<$length;$i++) {
  # get the character at this position
  $character = substr($incorrect_iban,$i,1);
  # for each known transcription error resulting in this character
  foreach($_iban_mistranscriptions[$character] as $possible_origin) {
   # if we're:
   #  - in the first 2 characters (country) and the possible replacement
   #    is a letter
   #  - in the 3rd or 4th characters (checksum) and the possible
   #    replacement is a number
   #  - later in the string
   if(($i<2 && !in_array($possible_origin,$numbers)) ||
      ($i>=2 && $i<=3 && in_array($possible_origin,$numbers)) ||
      $i>3) {
    # construct a possible IBAN using this possible origin for the
    # mistranscribed character, replaced at this position only
    $possible_iban = substr($incorrect_iban,0,$i) . $possible_origin .  substr($incorrect_iban,$i+1);
    # if the checksum passes, return it as a possibility
    if(verify_iban($possible_iban)) {
     array_push($suggestions,$possible_iban);
    }
   }
  }
 }

 # now we check for the type of mistransposition case where all of
 # the characters of a certain type within a string were mistransposed.
 #  - first generate a character frequency table
 $char_freqs = array();
 for($i=0;$i<strlen($incorrect_iban);$i++) {
  if(!isset($char_freqs[substr($incorrect_iban,$i,1)])) {
   $char_freqs[substr($incorrect_iban,$i,1)] = 1;
  }
  else {
   $char_freqs[substr($incorrect_iban,$i,1)]++;
  }
 }
 #  - now, for each of the characters in the string...
 foreach($char_freqs as $char=>$freq) {
  # if the character occurs more than once
  if($freq>1) {
   # check the 'all occurrences of <char> were mistranscribed' case
   foreach($_iban_mistranscriptions[$char] as $possible_origin) {
    $possible_iban = str_replace($char,$possible_origin,$incorrect_iban);
    if(verify_iban($possible_iban)) {
     array_push($suggestions,$possible_iban);
    }
   }
  }
 }

 return $suggestions;
}


##### internal use functions - safe to ignore ######

# Load the IBAN registry from disk.
global $_iban_registry;
$_iban_registry = array();
_iban_load_registry();
function _iban_load_registry() {
 global $_iban_registry;
 # if the registry is not yet loaded, or has been corrupted, reload
 if(!is_array($_iban_registry) || count($_iban_registry)<1) {
  $data = file_get_contents(dirname(__FILE__) . '/registry.txt');
  $lines = explode("\n",$data);
  array_shift($lines); # drop leading description line
  # loop through lines
  foreach($lines as $line) {
   if($line!='') {
    # split to fields
    $old_display_errors_value = ini_get('display_errors');
    ini_set('display_errors',false);
    $old_error_reporting_value = ini_get('error_reporting');
    ini_set('error_reporting',false);
    list($country,$country_name,$domestic_example,$bban_example,$bban_format_swift,$bban_format_regex,$bban_length,$iban_example,$iban_format_swift,$iban_format_regex,$iban_length,$bban_bankid_start_offset,$bban_bankid_stop_offset,$bban_branchid_start_offset,$bban_branchid_stop_offset,$registry_edition,$country_sepa) = explode('|',$line);
    ini_set('display_errors',$old_display_errors_value);
    ini_set('error_reporting',$old_error_reporting_value);
    # assign to registry
    $_iban_registry[$country] = array(
                                'country'			=>	$country,
 				'country_name'			=>	$country_name,
				'country_sepa'			=>	$country_sepa,
 				'domestic_example'		=>	$domestic_example,
				'bban_example'			=>	$bban_example,
				'bban_format_swift'		=>	$bban_format_swift,
				'bban_format_regex'		=>	$bban_format_regex,
				'bban_length'			=>	$bban_length,
				'iban_example'			=>	$iban_example,
				'iban_format_swift'		=>	$iban_format_swift,
				'iban_format_regex'		=>	$iban_format_regex,
				'iban_length'			=>	$iban_length,
				'bban_bankid_start_offset'	=>	$bban_bankid_start_offset,
				'bban_bankid_stop_offset'	=>	$bban_bankid_stop_offset,
				'bban_branchid_start_offset'	=>	$bban_branchid_start_offset,
				'bban_branchid_stop_offset'	=>	$bban_branchid_stop_offset,
				'registry_edition'		=>	$registry_edition
                               );
   }
  }
 }
}

# Get information from the IBAN registry by example IBAN / code combination
function _iban_get_info($iban,$code) {
 $country = iban_get_country_part($iban);
 return _iban_country_get_info($country,$code);
}

# Get information from the IBAN registry by country / code combination
function _iban_country_get_info($country,$code) {
 global $_iban_registry;
 $country = strtoupper($country);
 $code = strtolower($code);
 if(array_key_exists($country,$_iban_registry)) {
  if(array_key_exists($code,$_iban_registry[$country])) {
   return $_iban_registry[$country][$code];
  }
 }
 return false;
}

# Load common mistranscriptions from disk.
function _iban_load_mistranscriptions() {
 global $_iban_mistranscriptions;
 # do not reload if already present
 if(is_array($_iban_mistranscriptions) && count($_iban_mistranscriptions) == 36) { return true; }
 $_iban_mistranscriptions = array();
 $file = dirname(__FILE__) . '/mistranscriptions.txt';
 if(!file_exists($file) || !is_readable($file)) { return false; }
 $data = file_get_contents($file);
 $lines = explode("\n",$data);
 foreach($lines as $line) {
  # match lines with ' c-<x> = <something>' where x is a word-like character
  if(preg_match('/^ *c-(\w) = (.*?)$/',$line,$matches)) {
   # normalize the character to upper case
   $character = strtoupper($matches[1]);
   # break the possible origins list at '/', strip quotes & spaces
   $chars = explode(' ',str_replace('"','',preg_replace('/ *?\/ *?/','',$matches[2])));
   # assign as possible mistranscriptions for that character
   $_iban_mistranscriptions[$character] = $chars;
  }
 }
 return true;
}

?>
