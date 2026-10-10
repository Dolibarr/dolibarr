<?php

	//
	//  FPDM - Filter Standard
	//  NOTE: dummy filter for unfiltered streams!
	//

	if(isset($FPDM_FILTERS)) array_push($FPDM_FILTERS,"Standard");

	class FilterStandard {

		function decode($data) {
			return $data;
		}

		function encode($data) {
			return $data;
		}
	}
?>
