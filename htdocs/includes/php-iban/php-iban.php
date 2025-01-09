<?php

# PHP IBAN - http://github.com/globalcitizen/php-iban - LGPLv3

# Global flag by request
$__disable_iiban_gmp_extension=false;

# Verify an IBAN number.
#  If $machine_format_only, do not tolerate unclean (eg. spaces, dashes, leading 'IBAN ' or 'IIBAN ', lower case) input.
#  (Otherwise, input can be printed 'IIBAN xx xx xx...' or 'IBAN xx xx xx...' or machine 'xxxxx' format.)
#  Returns true or false.
function verify_iban($iban,$machine_format_only=false) {

 # First convert to machine format.
 if(!$machine_format_only) { $iban = iban_to_machine_format($iban); }

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
 # Remove all spaces
 $iban = str_replace(' ','',$iban);
 # Add spaces every four characters
 return wordwrap($iban,4,' ',true);
}

# Convert an IBAN to obfuscated presentation. To do this, we
# replace the checksum and all subsequent characters with an
# asterisk, except for the final four characters, and then
# return in human format, ie.
#  HU69107000246667654851100005 -> HU** **** **** **** **** **** 0005
# 
# We avoid the checksum as it may be used to infer the rest
# of the IBAN in cases where the country has few valid banks
# and branches, or other information about the account such
# as bank, branch, or date issued is known (where a sequential
# issuance scheme is in use).
# 
# Note that output of this function should be presented with 
# other information to a user, such as the date last used or 
# the date added to their account, in order to better facilitate
# unambiguous relative identification.
function iban_to_obfuscated_format($iban) {
 $iban = iban_to_machine_format($iban);
 $tr = substr($iban,0,2);
 for($i=2;$i<strlen($iban)-4;$i++) {
  $tr .= '*';
 }
 $tr .= substr($iban,strlen($iban)-4);
 return iban_to_human_format($tr);
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

# Perform MOD97-10 checksum calculation ('Germanic-level efficiency' version - thanks Chris!)
function iban_mod97_10($numeric_representation) {
 global $__disable_iiban_gmp_extension;
 # prefer php5 gmp extension if available
 if(!($__disable_iiban_gmp_extension) && function_exists('gmp_intval') && $numeric_representation!='') { return gmp_intval(gmp_mod(gmp_init($numeric_representation, 10),'97')) === 1; }

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
 	 'checksum'		=>	iban_get_checksum_part($iban),
	 'bban'			=>	iban_get_bban_part($iban),
 	 'bank'			=>	iban_get_bank_part($iban),
	 'country'		=>	iban_get_country_part($iban),
	 'branch'		=>	iban_get_branch_part($iban),
	 'account'		=>	iban_get_account_part($iban),
	 'nationalchecksum'	=>	iban_get_nationalchecksum_part($iban)
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

# Get the national checksum part from an IBAN
function iban_get_nationalchecksum_part($iban) {
 $iban = iban_to_machine_format($iban);
 $country = iban_get_country_part($iban);
 $start = iban_country_get_nationalchecksum_start_offset($country);
 if($start == '') { return ''; }
 $stop = iban_country_get_nationalchecksum_stop_offset($country);
 if($stop == '') { return ''; }
 $bban = iban_get_bban_part($iban);
 return substr($bban,$start,($stop-$start+1));
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

# Get the BBAN (national) checksum start offset for an IBAN country
#  Returns '' when (often) not present)
function iban_country_get_nationalchecksum_start_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_checksum_start_offset');
}

# Get the BBAN (national) checksum stop offset for an IBAN country
#  Returns '' when (often) not present)
function iban_country_get_nationalchecksum_stop_offset($iban_country) {
 return _iban_country_get_info($iban_country,'bban_checksum_stop_offset');
}

# Get the registry edition for an IBAN country
function iban_country_get_registry_edition($iban_country) {
 return _iban_country_get_info($iban_country,'registry_edition');
}

# Is the IBAN country one official issued by SWIFT?
function iban_country_get_country_swift_official($iban_country) {
 return _iban_country_get_info($iban_country,'country_swift_official');
}

# Is the IBAN country a SEPA member?
function iban_country_is_sepa($iban_country) {
 return _iban_country_get_info($iban_country,'country_sepa');
}

# Get the IANA code of an IBAN country
function iban_country_get_iana($iban_country) {
 return _iban_country_get_info($iban_country,'country_iana');
}

# Get the ISO3166-1 alpha-2 code of an IBAN country
function iban_country_get_iso3166($iban_country) {
 return _iban_country_get_info($iban_country,'country_iso3166');
}

# Get the parent registrar IBAN country of an IBAN country
function iban_country_get_parent_registrar($iban_country) {
 return _iban_country_get_info($iban_country,'parent_registrar');
}

# Get the official currency of an IBAN country as an ISO4217 alpha code
# (Note: Returns '' if there is no official currency, eg. for AA (IIBAN))
function iban_country_get_currency_iso4217($iban_country) {
 return _iban_country_get_info($iban_country,'currency_iso4217');
}

# Get the URL of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
function iban_country_get_central_bank_url($iban_country) {
 $result = _iban_country_get_info($iban_country,'central_bank_url');
 if($result!='') { $result = 'http://' . $result . '/'; }
 return $result;
}

# Get the name of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
function iban_country_get_central_bank_name($iban_country) {
 return _iban_country_get_info($iban_country,'central_bank_name');
}

# Get the list of all IBAN countries
function iban_countries() {
 _iban_load_registry();
 global $_iban_registry;
 return array_keys($_iban_registry);
}

# Get the membership of an IBAN country
# (Note: Possible Values eu_member, efta_member, other_member, non_member)
function iban_country_get_membership($iban_country) {
 return _iban_country_get_info($iban_country,'membership');
}

# Get the membership of an IBAN country
# (Note: Possible Values eu_member, efta_member, other_member, non_member)
function iban_country_get_is_eu_member($iban_country) {
 $membership = _iban_country_get_info($iban_country,'membership');
 if ($membership === 'eu_member') {
  $result = true;
 } else {
  $result = false;
 }

 return $result;
}

# Given an incorrect IBAN, return an array of zero or more checksum-valid
# suggestions for what the user might have meant, based upon common
# mistranscriptions.
#  IDEAS:
#   - length correction via adding/removing leading zeros from any single component
#   - overlength correction via dropping final digit(s)
#   - national checksum algorithm checks (apply same testing methodology, abstract to separate function)
#   - length correction by removing double digits (xxyzabxybaaz = change aa to a, or xx to x)
#   - validate bank codes
#   - utilize format knowledge with regards to alphanumeric applicability in that offset in that national BBAN format
#   - turkish TL/TK thing
#   - norway NO gets dropped due to mis-identification with "No." for number (ie. if no country code try prepending NO)
function iban_mistranscription_suggestions($incorrect_iban) {
 
 # remove funky characters
 $incorrect_iban = iban_to_machine_format($incorrect_iban);
 
 # abort on ridiculous length input (but be liberal)
 $length = strlen($incorrect_iban);
 if($length<5 || $length>34) { return array('(supplied iban length insane)'); }

 # abort if mistranscriptions data is unable to load
 if(!_iban_load_mistranscriptions()) { return array('(failed to load mistranscriptions)'); }

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
    # avoid spewing tonnes of PHP warnings under bad PHP configs - see issue #69
    if(function_exists('ini_set')) {
     # split to fields
     $old_display_errors_value = ini_get('display_errors');
     ini_set('display_errors',false);
     $old_error_reporting_value = ini_get('error_reporting');
     ini_set('error_reporting',false);
    }
    list($country,$country_name,$domestic_example,$bban_example,$bban_format_swift,$bban_format_regex,$bban_length,$iban_example,$iban_format_swift,$iban_format_regex,$iban_length,$bban_bankid_start_offset,$bban_bankid_stop_offset,$bban_branchid_start_offset,$bban_branchid_stop_offset,$registry_edition,$country_sepa,$country_swift_official,$bban_checksum_start_offset,$bban_checksum_stop_offset,$country_iana,$country_iso3166,$parent_registrar,$currency_iso4217,$central_bank_url,$central_bank_name,$membership) = explode('|',$line);
    # avoid spewing tonnes of PHP warnings under bad PHP configs - see issue #69
    if(function_exists('ini_set')) {
     ini_set('display_errors',$old_display_errors_value);
     ini_set('error_reporting',$old_error_reporting_value);
    }
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
				'registry_edition'		=>	$registry_edition,
                'country_swift_official'        =>      $country_swift_official,
				'bban_checksum_start_offset'	=>	$bban_checksum_start_offset,
				'bban_checksum_stop_offset'	=>	$bban_checksum_stop_offset,
				'country_iana'			=>	$country_iana,
				'country_iso3166'		=>	$country_iso3166,
				'parent_registrar'		=>	$parent_registrar,
				'currency_iso4217'		=>	$currency_iso4217,
				'central_bank_url'		=>	$central_bank_url,
				'central_bank_name'		=>	$central_bank_name,
                'membership'            =>  $membership
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
 _iban_load_registry();
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

# Find the correct national checksum for an IBAN
#  (Returns the correct national checksum as a string, or '' if unimplemented for this IBAN's country)
#  (NOTE: only works for some countries)
function iban_find_nationalchecksum($iban) {
 return _iban_nationalchecksum_implementation($iban,'find');
}

# Verify the correct national checksum for an IBAN
#  (Returns true or false, or '' if unimplemented for this IBAN's country)
#  (NOTE: only works for some countries)
function iban_verify_nationalchecksum($iban) {
 return _iban_nationalchecksum_implementation($iban,'verify');
}

# Verify the correct national checksum for an IBAN
#  (Returns the (possibly) corrected IBAN, or '' if unimplemented for this IBAN's country)
#  (NOTE: only works for some countries)
function iban_set_nationalchecksum($iban) {
 $result = _iban_nationalchecksum_implementation($iban,'set');
 if($result != '' ) {
  $result = iban_set_checksum($result); # recalculate IBAN-level checksum
 }
 return $result;
}

# Internal function to overwrite the national checksum portion of an IBAN
function _iban_nationalchecksum_set($iban,$nationalchecksum) {
 $country = iban_get_country_part($iban);
 $start = iban_country_get_nationalchecksum_start_offset($country);
 if($start == '') { return ''; }
 $stop = iban_country_get_nationalchecksum_stop_offset($country);
 if($stop == '') { return ''; }
 # determine the BBAN
 $bban = iban_get_bban_part($iban);
 # alter the BBAN
 $firstbit = substr($bban,0,$start);  # 'string before the checksum'
 $lastbit = substr($bban,$stop+1);    # 'string after the checksum'
 $fixed_bban = $firstbit . $nationalchecksum . $lastbit;
 # reconstruct the fixed IBAN
 $fixed_iban = $country . iban_get_checksum_part($iban) . $fixed_bban;
 return $fixed_iban;
}

# Currently unused but may be useful for Norway. 
# ISO7064 MOD11-2
# Adapted from https://gist.github.com/andreCatita/5714353 by Andrew Catita
function _iso7064_mod112_catita($input) {
 $p = 0;
 for ($i = 0; $i < strlen($input); $i++) {
  $c = $input[$i];
  $p = 2 * ($p + $c);
 }
 $p %= 11;
 $result = (12 - $p) % 11;
 if($result == 10) { $result = 'X'; }
 return $result;
}

# Currently unused but may be useful for Norway. 
# ISO 7064:1983.MOD 11-2
# by goseaside@sina.com
function _iso7064_mod112_goseaside($vString) {
 $sigma = '';
 $wi = array(1, 2, 4, 8, 5, 10, 9, 7, 3, 6);
 $hash_map = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
 $i_size = strlen($vString);
 $bModify = '?' == substr($vString, -1);
 $i_size1 = $bModify ? $i_size : $i_size + 1;
 for ($i = 1; $i <= $i_size; $i++) { 
  $i1 = $vString[$i - 1] * 1;
  $w1 = $wi[($i_size1 - $i) % 10];
  $sigma += ($i1 * $w1) % 11; 
 }
 if($bModify) return str_replace('?', $hash_map[($sigma % 11)], $vString);
 else return $hash_map[($sigma % 11)];
}

# ISO7064 MOD97-10 (Bosnia, etc.)
# (Credit: Adapted from https://github.com/stvkoch/ISO7064-Mod-97-10/blob/master/ISO7064Mod97_10.php)
function _iso7064_mod97_10($str) {
 $ai=1;
 $ch = ord($str[strlen($str)-1]) - 48;
 if($ch < 0 || $ch > 9) return false;
 $check=$ch;
 for($i=strlen($str)-2;$i>=0;$i--) {
  $ch = ord($str[$i]) - 48;
  if ($ch < 0 || $ch > 9) return false;
  $ai=($ai*10)%97;
  $check+= ($ai * ((int)$ch));
 }
 return (98-($check%97));
}

# Implement the national checksum for a Belgium (BE) IBAN
#  (Credit: @gaetan-be, fixed by @Olympic1)
function _iban_nationalchecksum_implementation_be($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $bban = iban_get_bban_part($iban);
 $bban_less_checksum = substr($bban, 0, -strlen($nationalchecksum));
 $expected_nationalchecksum = $bban_less_checksum % 97;
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return ($nationalchecksum == $expected_nationalchecksum);
 }
}

# MOD11 helper function for the Spanish (ES) IBAN national checksum implementation
#  (Credit: @dem3trio, code lifted from Spanish Wikipedia at https://es.wikipedia.org/wiki/C%C3%B3digo_cuenta_cliente)
function _iban_nationalchecksum_implementation_es_mod11_helper($numero) {
 if(strlen($numero)!=10) return "?";
 $cifras = Array(1,2,4,8,5,10,9,7,3,6);
 $chequeo=0;
 for($i=0; $i<10; $i++) {
  $chequeo += substr($numero,$i,1) * $cifras[$i];
 }
 $chequeo = 11 - ($chequeo % 11);
 if ($chequeo == 11) $chequeo = 0;
 if ($chequeo == 10) $chequeo = 1;
 return $chequeo;
}

# Implement the national checksum for a Spanish (ES) IBAN
#  (Credit: @dem3trio, adapted from code on Spanish Wikipedia at https://es.wikipedia.org/wiki/C%C3%B3digo_cuenta_cliente)
function _iban_nationalchecksum_implementation_es($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # extract appropriate substrings
 $bankprefix = iban_get_bank_part($iban) . iban_get_branch_part($iban);
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $account = iban_get_account_part($iban);
 $account_less_checksum = substr($account,2);
 # first we calculate the initial checksum digit, which is MOD11 of the bank prefix with '00' prepended
 $expected_nationalchecksum  = _iban_nationalchecksum_implementation_es_mod11_helper("00".$bankprefix);
 # then we append the second digit, which is MOD11 of the account
 $expected_nationalchecksum .= _iban_nationalchecksum_implementation_es_mod11_helper($account_less_checksum);
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return ($nationalchecksum == $expected_nationalchecksum);
 }
}

# Helper function for the France (FR) BBAN national checksum implementation
#  (Credit: @gaetan-be)
function _iban_nationalchecksum_implementation_fr_letters2numbers_helper($bban) {
 $allNumbers = "";
 $conversion = array(
                     "A" => 1, "B" => 2, "C" => 3, "D" => 4, "E" => 5, "F" => 6, "G" => 7, "H" => 8, "I" => 9, 
                     "J" => 1, "K" => 2, "L" => 3, "M" => 4, "N" => 5, "O" => 6, "P" => 7, "Q" => 8, "R" => 9, 
                     "S" => 2, "T" => 3, "U" => 4, "V" => 5, "W" => 6, "X" => 7, "Y" => 8, "Z" => 9
                    );
 for ($i=0; $i < strlen($bban); $i++) {
  if(is_numeric($bban[$i])) {
   $allNumbers .= $bban[$i];
  }
  else {
   $letter = strtoupper($bban[$i]);
   if(array_key_exists($letter, $conversion)) {
    $allNumbers .= $conversion[$letter];
   }
   else {
    return null;
   }
  }
 }
 return $allNumbers;
}

# NOTE: Worryingly at least one domestic number found within CF online is
#       not passing national checksum support. Perhaps banks do not issue
#       with correct RIB (French-style national checksum) despite using
#       the legacy format? Perhaps this is a mistranscribed number?
#        http://www.radiomariacentrafrique.org/virement-bancaire.aspx
#	  ie. CF19 20001 00001 01401832401 40
#	The following two numbers work:
#        http://fondationvoixducoeur.net/fr/pour-contribuer.html
#	  ie. CF4220002002003712551080145 and CF4220001004113717538890110
#       Since in the latter case the bank is the same as the former and
#       the French structure, terminology and 2/3 correct is a fairly high
#       correlation, we are going to assume that the first error is theirs.
#
# Implement the national checksum for a Central African Republic (CF) IBAN
function _iban_nationalchecksum_implementation_cf($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Chad (TD) IBAN
function _iban_nationalchecksum_implementation_td($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Comoros (KM) IBAN
function _iban_nationalchecksum_implementation_km($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Congo (CG) IBAN
function _iban_nationalchecksum_implementation_cg($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Djibouti (DJ) IBAN
function _iban_nationalchecksum_implementation_dj($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for an Equitorial Guinea (GQ) IBAN
function _iban_nationalchecksum_implementation_gq($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Gabon (GA) IBAN
function _iban_nationalchecksum_implementation_ga($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a Monaco (MC) IBAN
#  (Credit: @gaetan-be)
function _iban_nationalchecksum_implementation_mc($iban,$mode) {
 return _iban_nationalchecksum_implementation_fr($iban,$mode);
}

# Implement the national checksum for a France (FR) IBAN
#  (Credit: @gaetan-be, http://www.credit-card.be/BankAccount/ValidationRules.htm#FR_Validation and 
#           https://docs.oracle.com/cd/E18727_01/doc.121/e13483/T359831T498954.htm)
function _iban_nationalchecksum_implementation_fr($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # first, extract the BBAN
 $bban = iban_get_bban_part($iban);
 # convert to numeric form
 $bban_numeric_form = _iban_nationalchecksum_implementation_fr_letters2numbers_helper($bban);
 # if the result was null, something is horribly wrong
 if(is_null($bban_numeric_form)) { return ''; }
 # extract other parts
 $bank = substr($bban_numeric_form,0,5);
 $branch = substr($bban_numeric_form,5,5);
 $account = substr($bban_numeric_form,10,11);
 # actual implementation: mod97( (89 x bank number "Code banque") + (15 x branch code "Code guichet") + (3 x account number "Numéro de compte") )
 $sum = (89*($bank+0)) + ((15*($branch+0)));
 $sum += (3*($account+0));
 $expected_nationalchecksum = 97 - ($sum % 97);
 if(strlen($expected_nationalchecksum) == 1) { $expected_nationalchecksum = '0' . $expected_nationalchecksum; }
 # return
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return (iban_get_nationalchecksum_part($iban) == $expected_nationalchecksum);
 }
}

# Implement the national checksum for a Norway (NO) IBAN
#  (NOTE: Built from description at https://docs.oracle.com/cd/E18727_01/doc.121/e13483/T359831T498954.htm, not well tested)
function _iban_nationalchecksum_implementation_no($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # first, extract the BBAN
 $bban = iban_get_bban_part($iban);
 # then, the account
 $account = iban_get_account_part($iban);
 # existing checksum
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 # bban less checksum
 $bban_less_checksum = substr($bban,0,strlen($bban)-strlen($nationalchecksum));
 # factor table
 $factors = array(5,4,3,2,7,6,5,4,3,2);
 # calculate checksum
 $total = 0;
 for($i=0;$i<10;$i++) {
  $total += $bban_less_checksum[$i] * $factors[$i];
 }
 $total += $nationalchecksum;
 # mod11
 $remainder = $total % 11;
 # to find the correct check digit, we add the remainder to the current check digit,
 #  mod10 (ie. rounding at 10, such that 10 = 0, 11 = 1, etc.)
 $calculated_checksum = ($nationalchecksum + $remainder)%10;
 if($mode == 'find') {
  if($remainder == 0) { return $nationalchecksum; }
  else {
   return $calculated_checksum;
  }
 }
 elseif($mode == 'set') {
  return _iban_nationalchecksum_set($iban,$calculated_checksum);
 }
 elseif($mode == 'verify') {
  if($remainder == 0) { return true; }
  return false;
 }
}

# ISO/IEC 7064, MOD 11-2
# @param $input string Must contain only characters ('0123456789').
# @output A 1 character string containing '0123456789X',
#         or '' (empty string) on failure due to bad input.
# (Credit: php-iso7064 @ https://github.com/globalcitizen/php-iso7064)
function _iso7064_mod11_2($input) {
 $input = strtoupper($input); # normalize
 if(!preg_match('/^[0123456789]+$/',$input)) { return ''; } # bad input
 $modulus       = 11;
 $radix         = 2;
 $output_values = '0123456789X';
 $p             = 0;
 for($i=0; $i<strlen($input); $i++) {
  $val = strpos($output_values,substr($input,$i,1));
  if($val < 0) { return ''; } # illegal character encountered
  $p = (($p + $val) * $radix) % $modulus;
 }
 $checksum = ($modulus - $p + 1) % $modulus;
 return substr($output_values,$checksum,1);
}

# Implement the national checksum systems based on ISO7064 MOD11-2 Algorithm
function _iban_nationalchecksum_implementation_iso7064_mod11_2($iban,$mode,$drop_at_front=0,$drop_at_end=1) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # first, extract the BBAN
 $bban = iban_get_bban_part($iban);
 # get the current and computed checksum
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 # drop characters from the front and end of the BBAN as requested
 $bban_less_checksum = substr($bban,$drop_at_front,strlen($bban)-$drop_at_end);
 # calculate expected checksum
 $expected_nationalchecksum = _iso7064_mod11_2($bban_less_checksum);
 # return
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return (iban_get_nationalchecksum_part($iban) == $expected_nationalchecksum);
 }
}

# Implement the national checksum systems based on Damm Algorithm
function _iban_nationalchecksum_implementation_damm($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # first, extract the BBAN
 $bban = iban_get_bban_part($iban);
 # get the current and computed checksum
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 # drop trailing checksum characters
 $bban_less_checksum = substr($bban,0,strlen($bban)-strlen($nationalchecksum));
 # calculate expected checksum
 $expected_nationalchecksum = _damm($bban_less_checksum);
 # return
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return (iban_get_nationalchecksum_part($iban) == $expected_nationalchecksum);
 }
}

# Implement the national checksum systems based on Verhoeff Algorithm
function _iban_nationalchecksum_implementation_verhoeff($iban,$mode,$strip_length_end,$strip_length_front=0) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 # first, extract the BBAN
 $bban = iban_get_bban_part($iban);
 # if necessary, drop this many leading characters
 $bban = substr($bban,$strip_length_front);
 # drop the trailing checksum digit
 $bban_less_checksum = substr($bban,0,strlen($bban)-$strip_length_end);
 # get the current and computed checksum
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $expected_nationalchecksum = _verhoeff($bban_less_checksum);
 # return
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return (iban_get_nationalchecksum_part($iban) == $expected_nationalchecksum);
 }
}

# ISO/IEC 7064, MOD 97-10
# @param $input string Must contain only characters ('0123456789').
# @output A 2 character string containing '0123456789',
#         or '' (empty string) on failure due to bad input.
# (Credit: php-iso7064 @ https://github.com/globalcitizen/php-iso7064)
function _iso7064_mod97_10_generated($input) {
 $input = strtoupper($input); # normalize
 if(!preg_match('/^[0123456789]+$/',$input)) { return ''; } # bad input
 $modulus       = 97;
 $radix         = 10;
 $output_values = '0123456789';
 $p             = 0;
 for($i=0; $i<strlen($input); $i++) {
  $val = strpos($output_values,substr($input,$i,1));
  if($val < 0) { return ''; } # illegal character encountered
  $p = (($p + $val) * $radix) % $modulus;
 }
$p = ($p*$radix) % $modulus;
 $checksum = ($modulus - $p + 1) % $modulus;
 $second = $checksum % $radix;
 $first = ($checksum - $second) / $radix;
 return substr($output_values,$first,1) . substr($output_values,$second,1);
}

# Implement the national checksum for an Montenegro (ME) IBAN
#  (NOTE: Reverse engineered)
function _iban_nationalchecksum_implementation_me($iban,$mode) {
 return _iban_nationalchecksum_implementation_mod97_10($iban,$mode);
}

# Implement the national checksum for an Macedonia (MK) IBAN
#  (NOTE: Reverse engineered)
function _iban_nationalchecksum_implementation_mk($iban,$mode) {
 return _iban_nationalchecksum_implementation_mod97_10($iban,$mode);
}

# Implement the national checksum for an Netherlands (NL) IBAN
#  This applies to most banks, but not to 'INGB', therefore we
#  treat it specially here.
#  (Original code: Validate_NL PEAR class, since extended)
function _iban_nationalchecksum_implementation_nl($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $bank = iban_get_bank_part($iban);
 if(strtoupper($bank) == 'INGB') {
  return '';
 }
 $account = iban_get_account_part($iban);
 $checksum = 0;
 for ($i = 0; $i < 10; $i++) {
  $checksum += ((int)$account[$i] * (10 - $i));
 }
 $remainder = $checksum % 11;
 if($mode=='verify') {
  return ($remainder==0); # we return the result of mod11, if 0 it's good
 }
 elseif($mode=='set') {
  if($remainder==0) {
   return $iban; # we return as expected if the checksum is ok
  }
  return ''; # we return unimplemented if the checksum is bad
 }
 elseif($mode=='find') {
  return ''; # does not make sense for this 0-digit checksum
 }
}

# Implement the national checksum for an Portugal (PT) IBAN
#  (NOTE: Reverse engineered)
function _iban_nationalchecksum_implementation_pt($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $bban = iban_get_bban_part($iban);
 $bban_less_checksum = substr($bban,0,strlen($bban)-2);
 $expected_nationalchecksum = _iso7064_mod97_10_generated($bban_less_checksum);
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return ($nationalchecksum == $expected_nationalchecksum);
 }
}

# Implement the national checksum for an Serbia (RS) IBAN
#  (NOTE: Reverse engineered, including bank 'Narodna banka Srbije' (908) exception. For two 
#         separately published and legitimate looking IBANs from that bank, there appears to
#         be a +97 offset on the checksum, so we simply ignore all checksums for this bank.)
function _iban_nationalchecksum_implementation_rs($iban,$mode) {
 $bank = iban_get_bank_part($iban);
 if($bank == '908') {
  return '';
 }
 return _iban_nationalchecksum_implementation_mod97_10($iban,$mode);
}

# Implement the national checksum for an Slovenia (SI) IBAN
#  Note: It appears that the central bank does not use these
#        checksums, thus an exception has been added.
#  (NOTE: Reverse engineered)
function _iban_nationalchecksum_implementation_si($iban,$mode) {
 $bank = iban_get_bank_part($iban);
 # Bank of Slovenia does not use the legacy checksum scheme.
 #  Accounts in this namespace appear to be the central bank 
 #  accounts for licensed local banks.
 if($bank == '01') {
  return '';
 }
 return _iban_nationalchecksum_implementation_mod97_10($iban,$mode);
}

# Implement the national checksum for Slovak Republic (SK) IBAN
# Source of algorithm: https://www.nbs.sk/_img/Documents/_Legislativa/_Vestnik/OPAT8-09.pdf
# Account number is currently verified only, it's possible to also add validation for bank code and account number prefix
function _iban_nationalchecksum_implementation_sk($iban,$mode) {
 if ($mode !== 'verify') {
  return '';
 }

 $account = iban_get_account_part($iban);
 $weights = array(6, 3, 7, 9, 10, 5, 8, 4, 2, 1);

 $sum = 0;
 for ($i=0; $i < 10; $i++) {
  $sum += $account[$i] * $weights[$i];
 }

 return $sum % 11 === 0;
}

# Implement the national checksum for MOD97-10 countries
function _iban_nationalchecksum_implementation_mod97_10($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $bban = iban_get_bban_part($iban);
 $bban_less_checksum = substr($bban,0,strlen($bban)-2);
 $expected_nationalchecksum = _iso7064_mod97_10_generated($bban_less_checksum);
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return ($nationalchecksum == $expected_nationalchecksum);
 }
}

# Implement the national checksum for an Timor-Lest (TL) IBAN
#  (NOTE: Reverse engineered, but works on 2 different IBAN from official sources)
function _iban_nationalchecksum_implementation_tl($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $bban = iban_get_bban_part($iban);
 $bban_less_checksum = substr($bban,0,strlen($bban)-2);
 $expected_nationalchecksum = _iso7064_mod97_10_generated($bban_less_checksum);
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return ($nationalchecksum == $expected_nationalchecksum);
 }
}

# Luhn Check
# (Credit: Adapted from @gajus' https://gist.github.com/troelskn/1287893#gistcomment-857491)
function _luhn($string) {
 $checksum='';
 foreach (str_split(strrev((string) $string)) as $i => $d) {
  $checksum .= $i %2 !== 0 ? $d * 2 : $d;
 }
 return array_sum(str_split($checksum)) % 10;
}

# Verhoeff checksum
# (Credit: Adapted from Semyon Velichko's code at https://en.wikibooks.org/wiki/Algorithm_Implementation/Checksums/Verhoeff_Algorithm#PHP)
function _verhoeff($input) {
 if($input == '' || preg_match('/[^0-9]/',$input)) { return ''; } # reject non-numeric input
 $d = array(
       array(0,1,2,3,4,5,6,7,8,9),
       array(1,2,3,4,0,6,7,8,9,5),
       array(2,3,4,0,1,7,8,9,5,6),
       array(3,4,0,1,2,8,9,5,6,7),
       array(4,0,1,2,3,9,5,6,7,8),
       array(5,9,8,7,6,0,4,3,2,1),
       array(6,5,9,8,7,1,0,4,3,2),
       array(7,6,5,9,8,2,1,0,4,3),
       array(8,7,6,5,9,3,2,1,0,4),
       array(9,8,7,6,5,4,3,2,1,0)
      );
  $p = array(
        array(0,1,2,3,4,5,6,7,8,9),
        array(1,5,7,6,2,8,3,0,9,4),
        array(5,8,0,3,7,9,6,1,4,2),
        array(8,9,1,6,0,4,3,5,2,7),
        array(9,4,5,3,1,2,6,8,7,0),
        array(4,2,8,6,5,7,3,9,0,1),
        array(2,7,9,3,8,0,6,4,1,5),
        array(7,0,4,6,9,1,3,2,5,8)
       );
  $inv = array(0,4,3,2,1,5,6,7,8,9);
  $r = 0;
  foreach(array_reverse(str_split($input)) as $n => $N) {
   $r = $d[$r][$p[($n+1)%8][$N]];
  }
  return $inv[$r];
}

# Damm checksum
# (Credit: https://en.wikibooks.org/wiki/Algorithm_Implementation/Checksums/Damm_Algorithm#PHP)
function _damm($input) {
 if($input=='' || preg_match('/[^0-9]/',$input)) { return ''; } # non-numeric input
 // from http://www.md-software.de/math/DAMM_Quasigruppen.txt
 $matrix = array(
                array(0, 3, 1, 7, 5, 9, 8, 6, 4, 2),
                array(7, 0, 9, 2, 1, 5, 4, 8, 6, 3),
                array(4, 2, 0, 6, 8, 7, 1, 3, 5, 9),
                array(1, 7, 5, 0, 9, 8, 3, 4, 2, 6),
                array(6, 1, 2, 3, 0, 4, 5, 9, 7, 8),
                array(3, 6, 7, 4, 2, 0, 9, 5, 8, 1),
                array(5, 8, 6, 9, 7, 2, 0, 1, 3, 4),
                array(8, 9, 4, 5, 3, 6, 2, 0, 1, 7),
                array(9, 4, 3, 8, 6, 1, 7, 2, 0, 5),
                array(2, 5, 8, 1, 4, 3, 6, 7, 9, 0),
           );
 $checksum = 0;
 for ($i=0; $i<strlen($input); $i++) {
  $character = substr($input,$i,1);
  $checksum = $matrix[$checksum][$character];
 }
 return $checksum;
}

# Implement the national checksum for an Italian (IT) IBAN
function _iban_nationalchecksum_implementation_it($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } # blank value on return to distinguish from correct execution
 $nationalchecksum = iban_get_nationalchecksum_part($iban);
 $bban = iban_get_bban_part($iban);
 $bban_less_checksum = substr($bban,1);
 $expected_nationalchecksum = _italian($bban_less_checksum);
 if($mode=='find') {
  return $expected_nationalchecksum;
 }
 elseif($mode=='set') {
  return _iban_nationalchecksum_set($iban,$expected_nationalchecksum);
 }
 elseif($mode=='verify') {
  return (iban_get_nationalchecksum_part($iban) == $expected_nationalchecksum);
 }
}

# Implement the national checksum for a San Marino (SM) IBAN
function _iban_nationalchecksum_implementation_sm($iban,$mode) {
  // San Marino adheres to Italian rules.
  return _iban_nationalchecksum_implementation_it($iban,$mode);
}

# Italian (and San Marino's) checksum
# (Credit: Translated by Francesco Zanoni from http://community.visual-basic.it/lucianob/archive/2004/12/26/2464.aspx)
# (Source: European Commettee of Banking Standards' Register of European Account Numbers (TR201 V3.23 — FEBRUARY 2007), 
#          available at URL http://www.cnb.cz/cs/platebni_styk/iban/download/TR201.pdf)
function _italian($input)
{
  $digits = str_split('0123456789');
  $letters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ-. ');
  $lengthOfBbanWithoutChecksum = 22;
  $divisor = 26;
  $evenList = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28);
  $oddList = array(1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23, 27, 28, 26);

  // Character value computation
  $sum = 0;

  for ($k = 0; $k < $lengthOfBbanWithoutChecksum; $k++) {

    $i = array_search($input[$k], $digits);
    if ($i === false) {
      $i = array_search($input[$k], $letters);
    }

    // In case of wrong characters,
    // an unallowed checksum value is returned.
    if ($i === false) {
      return '';
    }

    $sum += (($k % 2) == 0 ? $oddList[$i] : $evenList[$i]);

  }

  return $letters[$sum % $divisor];
}


# Internal proxy function to access national checksum implementations
#  $iban = IBAN to work with (length and country must be valid, IBAN checksum and national checksum may be incorrect)
#  $mode = 'find', 'set', or 'verify'
#    - In 'find' mode, the correct national checksum for $iban is returned.
#    - In 'set' mode, a (possibly) modified version of $iban with the national checksum corrected is returned.
#    - In 'verify' mode, the checksum within $iban is compared to correctly calculated value, and true or false is returned.
#  If a national checksum algorithm does not exist or remains unimplemented for this country, or the supplied $iban or $mode is invalid, '' is returned.
#  (NOTE: We cannot collapse 'verify' mode and implement here via simple string comparison between 'find' mode output and the nationalchecksum part,
#         because some countries have systems which do not map to this approach, for example the Netherlands has no checksum part yet an algorithm exists)
function _iban_nationalchecksum_implementation($iban,$mode) {
 if($mode != 'set' && $mode != 'find' && $mode != 'verify') { return ''; } #  blank value on return to distinguish from correct execution
 $iban = iban_to_machine_format($iban);
 $country = iban_get_country_part($iban);
 if(strlen($iban)!=iban_country_get_iban_length($country)) { return ''; }
 $function_name = '_iban_nationalchecksum_implementation_' . strtolower($country);
 if(function_exists($function_name)) {
  return $function_name($iban,$mode);
 }
 return '';
}

?>
