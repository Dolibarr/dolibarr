<?php
/** 	Function called to complete substitution array (before generating on ODT, or a personalized email)
* 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
* 		is inside directory htdocs/core/substitutions
*
*		@param	array<string,string|float|null>		$substitutionarray	Array with substitution key=>val
*		@param	Translate							$langs				Output langs
*		@param	Object								$object				Object to use to get values
*		@param 	object 								$line 				Line to use to get values
* 		@return	void													The entry parameter $substitutionarray is modified
*/
function subtotals_completesubstitutionarray_lines(&$substitutionarray, $langs, $object, $line)
{
	global $conf, $db;


	if (defined('SUBTOTALS_SPECIAL_CODE')) {
		$substitutionarray['is_subtotals_line'] = ($line->special_code == SUBTOTALS_SPECIAL_CODE);
		$substitutionarray['is_not_subtotals_line'] = !$substitutionarray['is_subtotals_line'];
		$substitutionarray['is_subtotals_title'] = (($line->special_code == SUBTOTALS_SPECIAL_CODE) && $line->qty > 0);
		$substitutionarray['is_subtotals_subtotal'] = (($line->special_code == SUBTOTALS_SPECIAL_CODE) && $line->qty < 0);
		$subtotal_total = 0;
		if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
			$subtotal_total = $object->getSubtotalLineMulticurrencyAmount($line); // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
		} else {
			$subtotal_total = $object->getSubtotalLineAmount($line); // @phan-suppress-current-line PhanPluginUnknownObjectMethodCall
		}
		$substitutionarray['subtotals_total'] = ($subtotal_total == 0) ? "" : $subtotal_total;
		$substitutionarray['subtotals_level'] = abs($line->qty);
	} else {
		$substitutionarray['is_subtotals_line'] = false;
		$substitutionarray['is_not_subtotals_line'] = true;
		$substitutionarray['is_subtotals_title'] = false;
		$substitutionarray['is_subtotals_subtotal'] = false;
		$substitutionarray['subtotals_total'] = 0;
		$substitutionarray['subtotals_level'] = 0;
	}
}
