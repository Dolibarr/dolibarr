<?php
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_cache.inc, a simple, rolling(no GC), cache 
 *				for RSS objects, keyed on URL.
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.51
 * License:		GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * http://lists.sourceforge.net/lists/listinfo/magpierss-general
 *
 */

class RSSCache {
	var $BASE_CACHE = './cache';	// where the cache files are stored
	var $MAX_AGE	= 3600;  		// when are files stale, default one hour
	var $ERROR 		= "";			// accumulate error messages
	
	function RSSCache ($base='', $age='') {
		if ( $base ) {
			$this->BASE_CACHE = $base;
		}
		if ( $age ) {
			$this->MAX_AGE = $age;
		}
		
		// attempt to make the cache directory
		if ( ! file_exists( $this->BASE_CACHE ) ) {
			$status = @mkdir( $this->BASE_CACHE, 0755 );
			
			// if make failed 
			if ( ! $status ) {
				$this->error(
					"Cache couldn't make dir '" . $this->BASE_CACHE . "'."
				);
			}
		}
	}
	
/*=======================================================================*\
	Function:	set
	Purpose:	add an item to the cache, keyed on url
	Input:		url from wich the rss file was fetched
	Output:		true on sucess	
\*=======================================================================*/
	function set ($url, $rss) {
		$this->ERROR = "";
		$cache_file = $this->file_name( $url );
		$fp = @fopen( $cache_file, 'w' );
		
		if ( ! $fp ) {
			$this->error(
				"Cache unable to open file for writing: $cache_file"
			);
			return 0;
		}
		
		
		$data = $this->serialize( $rss );
		fwrite( $fp, $data );
		fclose( $fp );
		
		return $cache_file;
	}
	
/*=======================================================================*\
	Function:	get
	Purpose:	fetch an item from the cache
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS	
\*=======================================================================*/	
	function get ($url) {
		$this->ERROR = "";
		$cache_file = $this->file_name( $url );
		
		if ( ! file_exists( $cache_file ) ) {
			$this->debug( 
				"Cache doesn't contain: $url (cache file: $cache_file)"
			);
			return 0;
		}
		
		$fp = @fopen($cache_file, 'r');
		if ( ! $fp ) {
			$this->error(
				"Failed to open cache file for reading: $cache_file"
			);
			return 0;
		}
		
		$data = fread( $fp, filesize($cache_file) );
		$rss = $this->unserialize( $data );
		
		return $rss;
	}

/*=======================================================================*\
	Function:	check_cache
	Purpose:	check a url for membership in the cache
				and whether the object is older then MAX_AGE (ie. STALE)
	Input:		url from wich the rss file was fetched
	Output:		cached object on HIT, false on MISS	
\*=======================================================================*/		
	function check_cache ( $url ) {
		$this->ERROR = "";
		$filename = $this->file_name( $url );
		
		if ( file_exists( $filename ) ) {
			// find how long ago the file was added to the cache
			// and whether that is longer then MAX_AGE
			$mtime = filemtime( $filename );
			$age = time() - $mtime;
			if ( $this->MAX_AGE > $age ) {
				// object exists and is current
				return 'HIT';
			}
			else {
				// object exists but is old
				return 'STALE';
			}
		}
		else {
			// object does not exist
			return 'MISS';
		}
	}

/*=======================================================================*\
	Function:	serialize
\*=======================================================================*/		
	function serialize ( $rss ) {
		return serialize( $rss );
	}

/*=======================================================================*\
	Function:	unserialize
\*=======================================================================*/		
	function unserialize ( $data ) {
		return unserialize( $data );
	}
	
/*=======================================================================*\
	Function:	file_name
	Purpose:	map url to location in cache
	Input:		url from wich the rss file was fetched
	Output:		a file name
\*=======================================================================*/		
	function file_name ($url) {
		$filename = md5( $url );
		return join( DIRECTORY_SEPARATOR, array( $this->BASE_CACHE, $filename ) );
	}

/*=======================================================================*\
	Function:	error
	Purpose:	register error
\*=======================================================================*/			
	function error ($errormsg, $lvl=E_USER_WARNING) {
		// append PHP's error message if track_errors enabled
		if ( isset($php_errormsg) ) { 
			$errormsg .= " ($php_errormsg)";
		}
		$this->ERROR = $errormsg;
		if ( MAGPIE_DEBUG ) {
			trigger_error( $errormsg, $lvl);
		}
		else {
			error_log( $errormsg, 0);
		}
	}
	
	function debug ($debugmsg, $lvl=E_USER_NOTICE) {
		if ( MAGPIE_DEBUG ) {
			$this->error("MagpieRSS [debug] $debugmsg", $lvl);
		}
	}

}

?>
