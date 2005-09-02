NAME

	MagpieRSS - a simple RSS integration tool

SYNOPSIS

	require_once(rss_fetch.inc);
	$url = $_GET['url'];
	$rss = fetch_rss( $url );
	
	echo "Channel Title: " . $rss->channel['title'] . "<p>";
	echo "<ul>";
	foreach ($rss->items as $item) {
		$href = $item['link'];
		$title = $item['title'];
		echo "<li><a href=$href>$title</a></li>";
	}
	echo "</ul>";

DESCRIPTION

	MapieRSS is an XML-based RSS parser in PHP.  It attempts to be "PHP-like",
	and simple to use.
	
	Some features include:
	
	* supports RSS 0.9 - 1.0, with limited RSS 2.0 support
	* supports namespaces, and modules, including mod_content and mod_event
	* open minded [1]
	* simple, functional interface, to object oriented backend parser
	* automatic caching of parsed RSS objects makes its easy to integrate
	* supports conditional GET with Last-Modified, and ETag
	* uses constants for easy override of default behaviour 
	* heavily commented


1. By open minded I mean Magpie will accept any tag it finds in good faith that
   it was supposed to be here.  For strict validation, look elsewhere.


GETTING STARTED

	

COPYRIGHT:
	Copyright(c) 2002 kellan@protest.net. All rights reserved.
	This software is released under the GNU General Public License.
	Please read the disclaimer at the top of the Snoopy.class.inc file.
