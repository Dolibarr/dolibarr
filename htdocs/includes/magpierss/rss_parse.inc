<?php
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_parse.inc includes code for parsing
 *				RSS, and returning an RSS object
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * Version:		0.51
 * License:		GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 *
 */
 

/* 
 * NOTES ON RSS PARSING PHILOSOPHY (moderately important):
 * MagpieRSS parse all versions of RSS with a few limitation (mod_content, and
 * mod_taxonomy support is shaky) into a simple object, with 2 fields, 
 * the hash 'channel', and the array 'items'.
 *
 * MagpieRSS is a forgiving and inclusive parser.  It currently makes no
 * attempt to enforce the validity on an RSS feed.  It will include any
 * properly formatted tags it finds, allowing to you to mix RSS 0.93, with RSS
 * 1.0, with tags or your own imagining.  This sort of witches brew is a bad
 * bad idea!  But Magpie is less pendantic then I am.
 *
 * RSS validators are readily available on the web at:
 * http://feeds.archive.org/validator/
 * http://www.ldodds.com/rss_validator/1.0/validator.html
 *
 */

/*
 * EXAMPLE PARSE RESULTS:
 *
 * Magpie tries to parse RSS into ease to use PHP datastructures.
 *
 * For example, Magpie on encountering RSS 1.0 item entry:
 *
 * <item rdf:about="http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257">
 * <title>Weekly Peace Vigil</title>
 * <link>http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257</link>
 * <description>Wear a white ribbon</description>
 * <dc:subject>Peace</dc:subject>
 * <ev:startdate>2002-06-01T11:00:00</ev:startdate>
 * <ev:location>Northampton, MA</ev:location>
 * <ev:enddate>2002-06-01T12:00:00</ev:enddate>
 * <ev:type>Protest</ev:type>
 * </item>
 * 
 * Would transform it into the following associative array, and push it
 * onto the array $rss-items
 *
 * array(
 *	title => 'Weekly Peace Vigil',
 *	link =>
 *	'http://protest.net/NorthEast/calendrome.cgi?span=event&#38;ID=210257',
 *	description => 'Wear a white ribbon',
 *	dc => array (
 *			subject => 'Peace'
 *		),
 *	ev => array (
 *		startdate => '2002-06-01T11:00:00',
 *		enddate => '2002-06-01T12:00:00',
 *		type => 'Protest',
 *		location => 'Northampton, MA'
 *	)
 * )
 *
 */

class MagpieRSS {
	/*
	 * Hybrid parser, and object.  (probably a bad idea! :)
	 *
	 * Useage Example:
	 *
	 * $some_rss = "<?xml version="1.0"......
	 *
	 * $rss = new MagpieRSS( $some_rss );
	 *
	 * // print rss chanel title
	 * echo $rss->channel['title'];
	 *
	 * // print the title of each item
	 * foreach ($rss->items as $item ) {
	 *	  echo $item[title];
	 * }
	 *
	 * see rss_fetch.inc for a simpler interface
	 */
	 
	var $parser;
	
	var $current_item	= array();	// item currently being parsed
    var $items			= array();	// collection of parsed items
	var $channel		= array();	// hash of channel fields
	var $textinput		= array();
	var $image			= array();
	
	var $parent_field	= array('RDF');
	var $current_field	= '';
	var $current_namespace	= false;
	
	var $ERROR = "";
	
/*======================================================================*\
    Function: MagpieRSS
    Purpose:  Constructor, sets up XML parser,parses source,
			  and populates object.. 
	Input:	  String containing the RSS to be parsed
\*======================================================================*/
	function MagpieRSS ($source) {
		
		# if PHP xml isn't compiled in, die
		#
		if (!function_exists('xml_parser_create')) {
			$this->error( "Failed to load PHP's XML Extension. " . 
						  "http://www.php.net/manual/en/ref.xml.php",
						   E_USER_ERROR );
		}
		
		$parser = @xml_parser_create();
		
		if (!is_resource($parser))
		{
			$this->error( "Failed to create an instance of PHP's XML parser. " .
						  "http://www.php.net/manual/en/ref.xml.php",
						  E_USER_ERROR );
		}

		
		$this->parser = $parser;
		
		# pass in parser, and a reference to this object
		# setup handlers
		#
		xml_set_object( $this->parser, $this );
		xml_set_element_handler($this->parser, 'start_element', 'end_element');
		xml_set_character_data_handler( $this->parser, 'cdata' ); 
	
		
		$status = xml_parse( $this->parser, $source );
		
		if (! $status ) {
			$errorcode = xml_get_error_code( $this->parser );
			if ( $errorcode != XML_ERROR_NONE ) {
				$xml_error = xml_error_string( $errorcode );
				$error_line = xml_get_current_line_number($this->parser);
				$error_col = xml_get_current_column_number($this->parser);
				$errormsg = "$xml_error at line $error_line, column $error_col";

				$this->error( $errormsg );
			}
		}
		
		xml_parser_free( $this->parser );
	}
	
	function start_element ($p, $element, &$attrs) {
		$element 	= strtolower( $element );
				
		# check for a namespace, and split if found
		#
		$namespace	= false;
		if ( strpos( $element, ':' ) ) {
			list($namespace, $element) = split( ':', $element, 2); 
		}
		$this->current_field = $element;
		if ( $namespace and $namespace != 'rdf' ) {
			$this->current_namespace = $namespace;
		}
		
		if ( $element == 'channel' ) {
			array_unshift( $this->parent_field, 'channel' );
		}
		elseif ( $element == 'items' ) {
			array_unshift( $this->parent_field, 'items' );
		}
		elseif ( $element == 'item' ) {
			array_unshift( $this->parent_field, 'item' );
		}
		elseif ( $element == 'textinput' ) {
			array_unshift( $this->parent_field, 'textinput' );
		}
		elseif ( $element == 'image' ) {
			array_unshift( $this->parent_field, 'image' );
		}
		
	}
	
	function end_element ($p, $element) {
		$element = strtolower($element);
							
		if ( $element == 'item' ) {		
			$this->items[] = $this->current_item;
			$this->current_item = array();
			array_shift( $this->parent_field );
		}
		elseif ( $element == 'channel' or $element == 'items' or 
				 $element == 'textinput' or $element == 'image' ) {
			array_shift( $this->parent_field );
		}
		
		$this->current_field = '';
		$this->current_namespace = false;
	}
	
	function cdata ($p, $text) {
		# skip item, channel, items first time we see them
		#
		if ( $this->parent_field[0] == $this->current_field or
			 ! $this->current_field ) {
			return;
		}
		elseif ( $this->parent_field[0] == 'channel') {
			if ( $this->current_namespace ) {
				$this->append(
					$this->channel[ $this->current_namespace ][ $this->current_field ],
					$text);
			}
			else {
				$this->append($this->channel[ $this->current_field ], $text);
			}
		
		}
		elseif ( $this->parent_field[0] == 'item' ) {
			if ( $this->current_namespace ) {
				$this->append(
					$this->current_item[ $this->current_namespace ][$this->current_field ],
					$text);
			}
			else {
				$this->append(
					$this->current_item[ $this->current_field ],
					$text );
			}
		}
		elseif ( $this->parent_field[0] == 'textinput' ) {
			if ( $this->current_namespace ) {
				$this->append(
					$this->textinput[ $this->current_namespace ][ $this->current_field ],
					 $text );
			}
			else {
				$this->append(
					$this->textinput[ $this->current_field ],
					$text );
			}
		}
		elseif ( $this->parent_field[0] == 'image' ) {
			if ( $this->current_namespace ) {
				$this->append(
					$this->image[ $this->current_namespace ][ $this->current_field ],
					$text );
			}
			else {
				$this->append(
					$this->image[ $this->current_field ],
					$text );
			}
		}
	}
	
	function append (&$str1, $str2="") {
		if (!isset($str1) ) {
			$str1="";
		}
		$str1 .= $str2;
	}
	
	function error ($errormsg, $lvl=E_USER_WARNING) {
		// append PHP's error message if track_errors enabled
		if ( $php_errormsg ) { 
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
		

/*======================================================================*\
	EVERYTHING BELOW HERE IS FOR DEBUGGING PURPOSES
\*======================================================================*/
	function show_list () {
		echo "<ol>\n";
		foreach ($this->items as $item) {
			echo "<li>", $this->show_item( $item );
		}
		echo "</ol>";
	}
	
	function show_channel () {
		echo "channel:<br>";
		echo "<ul>";
		while ( list($key, $value) = each( $this->channel ) ) {
			echo "<li> $key: $value";
		}
		echo "</ul>";
	}
	
	function show_item ($item) {
		echo "item: $item[title]";
		echo "<ul>";
		while ( list($key, $value) = each($item) ) {
			if ( is_array($value) ) {
				echo "<br><b>$key</b>";
				echo "<ul>";
				while ( list( $ns_key, $ns_value) = each( $value ) ) {
					echo "<li>$ns_key: $ns_value";
				}
				echo "</ul>";
			}
			else {
				echo "<li> $key: $value";
			}
		}
		echo "</ul>";
	}

/*======================================================================*\
	END DEBUGGING FUNCTIONS	
\*======================================================================*/
	


} # end class RSS
?>
