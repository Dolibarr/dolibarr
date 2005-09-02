<?php
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_utils.inc, utility methods for working with RSS
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * Version:     0.51
 * License:     GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 */


/*======================================================================*\
    Function: parse_w3cdtf
    Purpose:  parse a W3CDTF date into unix epoch

    NOTE: http://www.w3.org/TR/NOTE-datetime
\*======================================================================*/

function parse_w3cdtf ( $date_str ) {
    
    # regex to match wc3dtf
    $pat = "/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(:(\d{2}))?(?:([-+])(\d{2}):?(\d{2})|(Z))?/";
    
    if ( preg_match( $pat, $date_str, $match ) ) {
        list( $year, $month, $day, $hours, $minutes, $seconds) = 
            array( $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
        
        # calc epoch for current date assuming GMT
        $epoch = gmmktime( $hours, $minutes, $seconds, $month, $day, $year);
        
        $offset = 0;
        if ( $match[10] == 'Z' ) {
            # zulu time, aka GMT
        }
        else {
            list( $tz_mod, $tz_hour, $tz_min ) =
                array( $match[8], $match[9], $match[10]);
            
            # zero out the variables
            if ( ! $tz_hour ) { $tz_hour = 0; }
            if ( ! $tz_min ) { $tz_min = 0; }
        
            $offset_secs = (($tz_hour*60)+$tz_min)*60;
            
            # is timezone ahead of GMT?  then subtract offset
            #
            if ( $tz_mod == '+' ) {
                $offset_secs = $offset_secs * -1;
            }
            
            $offset = $offset_secs; 
        }
        $epoch = $epoch + $offset;
        return $epoch;
    }
    else {
        return -1;
    }
}

?>
