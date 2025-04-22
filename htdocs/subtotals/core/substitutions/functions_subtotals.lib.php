<?php
/** 	Function called to complete substitution array (before generating on ODT, or a personalized email)
* 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
* 		is inside directory htdocs/core/substitutions
*
*		@param	array		$substitutionarray	Array with substitution key=>val
*		@param	Translate	$langs			Output langs
*		@param	Object		$object			Object to use to get values
*		@param 	Object 		$line 			Line to use to get values
* 		@return	void					The entry parameter $substitutionarray is modified
*/
function subtotals_completesubstitutionarray_lines(&$substitutionarray, $langs, $object, $line)
{
	global $conf,$db;

	$substitutionarray['is_subtotals_line'] = ($line->special_code == SUBTOTALS_SPECIAL_CODE);
	$substitutionarray['is_subtotals_title'] = ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty > 0);
	$substitutionarray['is_subtotals'] = ($line->special_code == SUBTOTALS_SPECIAL_CODE && $line->qty < 0);
	$subtotal_total = 0;
	if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
		$subtotal_total = $object->getSubtotalLineMulticurrencyAmount($line);
	} else {
		$subtotal_total = $object->getSubtotalLineAmount($line);
	}
	$substitutionarray['total_subtotal'] = $subtotal_total == 0 ? "" : $subtotal_total;
	$substitutionarray['subtotal_level'] = abs($line->qty);
}
