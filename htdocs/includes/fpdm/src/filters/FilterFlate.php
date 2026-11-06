<?php
//
//  FPDM - Filter Flate
//  NOTE: requires ZLIB >= 1.0.9!
//

$__tmp = version_compare(phpversion(), "5") == -1 ? array('FilterFlateDecode') : array('FilterFlateDecode', false);
if (!call_user_func_array('class_exists', $__tmp)) {


	if(isset($FPDM_FILTERS)) array_push($FPDM_FILTERS,"FlateDecode");

    class FilterFlate {

        var $data = null;
        var $dataLength = 0;

        function error($msg) {
            die($msg);
        }

        /**
         * Method to decode GZIP compressed data.
         *
         * @param string data    The compressed data.
         * @return uncompressed data
         */
        function decode($data) {

            $this->data = $data;
            $this->dataLength = strlen($data);

            // uncompress
            $data=gzuncompress($data);

            if(!$data) $this->error("FilterFlateDecode: invalid stream data.");

            return $data;
        }


        function encode($in) {
            return gzcompress($in, 9);
        }
   }

}
//unset $__tmp;
?>
