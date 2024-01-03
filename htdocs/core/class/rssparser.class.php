<?php
/* Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/class/rssparser.class.php
 *      \ingroup    core
 *      \brief      File of class to parse RSS feeds
 */

/**
 * 	Class to parse RSS files
 */
class RssParser
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	public $feed_version;

	private $_format = '';
	private $_urlRSS;
	private $_language;
	private $_generator;
	private $_copyright;
	private $_lastbuilddate;
	private $_imageurl;
	private $_link;
	private $_title;
	private $_description;
	private $_lastfetchdate; // Last successful fetch
	private $_rssarray = array();

	private $current_namespace;
	public $items = array();
	public $current_item = array();
	public $channel = array();
	public $textinput = array();
	public $image = array();

	private $initem;
	private $intextinput;
	private $incontent;
	private $inimage;
	private $inchannel;

	// For parsing with xmlparser
	public $stack = array(); // parser stack
	private $_CONTENT_CONSTRUCTS = array('content', 'summary', 'info', 'title', 'tagline', 'copyright');


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * getFormat
	 *
	 * @return string
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * getUrlRss
	 *
	 * @return string
	 */
	public function getUrlRss()
	{
		return $this->_urlRSS;
	}
	/**
	 * getLanguage
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->_language;
	}
	/**
	 * getGenerator
	 *
	 * @return string
	 */
	public function getGenerator()
	{
		return $this->_generator;
	}
	/**
	 * getCopyright
	 *
	 * @return string
	 */
	public function getCopyright()
	{
		return $this->_copyright;
	}
	/**
	 * getLastBuildDate
	 *
	 * @return string
	 */
	public function getLastBuildDate()
	{
		return $this->_lastbuilddate;
	}
	/**
	 * getImageUrl
	 *
	 * @return string
	 */
	public function getImageUrl()
	{
		return $this->_imageurl;
	}
	/**
	 * getLink
	 *
	 * @return string
	 */
	public function getLink()
	{
		return $this->_link;
	}
	/**
	 * getTitle
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_title;
	}
	/**
	 * getDescription
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->_description;
	}
	/**
	 * getLastFetchDate
	 *
	 * @return string
	 */
	public function getLastFetchDate()
	{
		return $this->_lastfetchdate;
	}
	/**
	 * getItems
	 *
	 * @return array
	 */
	public function getItems()
	{
		return $this->_rssarray;
	}


	/**
	 * 	Parse rss URL
	 *
	 * 	@param	string	$urlRSS		Url to parse
	 * 	@param	int		$maxNb		Max nb of records to get (0 for no limit)
	 * 	@param	int		$cachedelay	0=No cache, nb of seconds we accept cache files (cachedir must also be defined)
	 * 	@param	string	$cachedir	Directory where to save cache file (For example $conf->externalrss->dir_temp)
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function parser($urlRSS, $maxNb = 0, $cachedelay = 60, $cachedir = '')
	{
		global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$rss = '';
		$str = ''; // This will contain content of feed

		// Check parameters
		if (!dol_is_url($urlRSS)) {
			$this->error = "ErrorBadUrl";
			return -1;
		}

		$this->_urlRSS = $urlRSS;
		$newpathofdestfile = $cachedir.'/'.dol_hash($this->_urlRSS, 3); // Force md5 hash (does not contain special chars)
		$newmask = '0644';

		//dol_syslog("RssPArser::parser parse url=".$urlRSS." => cache file=".$newpathofdestfile);
		$nowgmt = dol_now();

		// Search into cache
		$foundintocache = 0;
		if ($cachedelay > 0 && $cachedir) {
			$filedate = dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay)) {
				//dol_syslog("RssParser::parser cache file ".$newpathofdestfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we use it.");
				$foundintocache = 1;

				$this->_lastfetchdate = $filedate;
			} else {
				dol_syslog(get_class($this)."::parser cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
		}

		// Load file into $str
		if ($foundintocache) {    // Cache file found and is not too old
			$str = file_get_contents($newpathofdestfile);
		} else {
			try {
				$result = getURLContent($this->_urlRSS, 'GET', '', 1, array(), array('http', 'https'), 0);

				if (!empty($result['content'])) {
					$str = $result['content'];
				} elseif (!empty($result['curl_error_msg'])) {
					$this->error = 'Error retrieving URL '.$this->_urlRSS.' - '.$result['curl_error_msg'];
					return -1;
				}
			} catch (Exception $e) {
				$this->error = 'Error retrieving URL '.$this->_urlRSS.' - '.$e->getMessage();
				return -2;
			}
		}

		if ($str !== false) {
			// Convert $str into xml
			if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
				//print 'xx'.LIBXML_NOCDATA;
				libxml_use_internal_errors(false);
				if (LIBXML_VERSION < 20900) {
					// Avoid load of external entities (security problem).
					// Required only if LIBXML_VERSION < 20900
					libxml_disable_entity_loader(true);
				}

				$rss = simplexml_load_string($str, "SimpleXMLElement", LIBXML_NOCDATA);
			} else {
				if (!function_exists('xml_parser_create')) {
					$this->error = 'Function xml_parser_create are not supported by your PHP';
					return -1;
				}

				try {
					$xmlparser = xml_parser_create(null);

					if (!is_resource($xmlparser) && !is_object($xmlparser)) {
						$this->error = "ErrorFailedToCreateParser";
						return -1;
					}

					xml_set_object($xmlparser, $this);
					xml_set_element_handler($xmlparser, 'feed_start_element', 'feed_end_element');
					xml_set_character_data_handler($xmlparser, 'feed_cdata');

					$status = xml_parse($xmlparser, $str, false);

					xml_parser_free($xmlparser);
					$rss = $this;
					//var_dump($status.' '.$rss->_format);exit;
				} catch (Exception $e) {
					$rss = null;
				}
			}
		}

		// If $rss loaded
		if ($rss) {
			// Save file into cache
			if (empty($foundintocache) && $cachedir) {
				dol_syslog(get_class($this)."::parser cache file ".$newpathofdestfile." is saved onto disk.");
				if (!dol_is_dir($cachedir)) {
					dol_mkdir($cachedir);
				}
				$fp = fopen($newpathofdestfile, 'w');
				if ($fp) {
					fwrite($fp, $str);
					fclose($fp);
					dolChmod($newpathofdestfile);

					$this->_lastfetchdate = $nowgmt;
				} else {
					print 'Error, failed to open file '.$newpathofdestfile.' for write';
				}
			}

			unset($str); // Free memory

			if (empty($rss->_format)) {    // If format not detected automatically
				$rss->_format = 'rss';
				if (empty($rss->channel)) {
					$rss->_format = 'atom';
				}
			}

			$items = array();

			// Save description entries
			if ($rss->_format == 'rss') {
				//var_dump($rss);
				if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
					if (!empty($rss->channel->language)) {
						$this->_language = sanitizeVal((string) $rss->channel->language);
					}
					if (!empty($rss->channel->generator)) {
						$this->_generator = sanitizeVal((string) $rss->channel->generator);
					}
					if (!empty($rss->channel->copyright)) {
						$this->_copyright = sanitizeVal((string) $rss->channel->copyright);
					}
					if (!empty($rss->channel->lastbuilddate)) {
						$this->_lastbuilddate = sanitizeVal((string) $rss->channel->lastbuilddate);
					}
					if (!empty($rss->channel->image->url[0])) {
						$this->_imageurl = sanitizeVal((string) $rss->channel->image->url[0]);
					}
					if (!empty($rss->channel->link)) {
						$this->_link = sanitizeVal((string) $rss->channel->link);
					}
					if (!empty($rss->channel->title)) {
						$this->_title = sanitizeVal((string) $rss->channel->title);
					}
					if (!empty($rss->channel->description)) {
						$this->_description = sanitizeVal((string) $rss->channel->description);
					}
				} else {
					//var_dump($rss->channel);
					if (!empty($rss->channel['language'])) {
						$this->_language = sanitizeVal((string) $rss->channel['language']);
					}
					if (!empty($rss->channel['generator'])) {
						$this->_generator = sanitizeVal((string) $rss->channel['generator']);
					}
					if (!empty($rss->channel['copyright'])) {
						$this->_copyright = sanitizeVal((string) $rss->channel['copyright']);
					}
					if (!empty($rss->channel['lastbuilddate'])) {
						$this->_lastbuilddate = sanitizeVal((string) $rss->channel['lastbuilddate']);
					}
					if (!empty($rss->image['url'])) {
						$this->_imageurl = sanitizeVal((string) $rss->image['url']);
					}
					if (!empty($rss->channel['link'])) {
						$this->_link = sanitizeVal((string) $rss->channel['link']);
					}
					if (!empty($rss->channel['title'])) {
						$this->_title = sanitizeVal((string) $rss->channel['title']);
					}
					if (!empty($rss->channel['description'])) {
						$this->_description = sanitizeVal((string) $rss->channel['description']);
					}
				}

				if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
					$items = $rss->channel->item; // With simplexml
				} else {
					$items = $rss->items; // With xmlparse
				}
				//var_dump($items);exit;
			} elseif ($rss->_format == 'atom') {
				//var_dump($rss);
				if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
					if (!empty($rss->generator)) {
						$this->_generator = sanitizeVal((string) $rss->generator);
					}
					if (!empty($rss->lastbuilddate)) {
						$this->_lastbuilddate = sanitizeVal((string) $rss->modified);
					}
					if (!empty($rss->link->href)) {
						$this->_link = sanitizeVal((string) $rss->link->href);
					}
					if (!empty($rss->title)) {
						$this->_title = sanitizeVal((string) $rss->title);
					}
					if (!empty($rss->description)) {
						$this->_description = sanitizeVal((string) $rss->description);
					}
				} else {
					//if (!empty($rss->channel['rss_language']))	$this->_language = (string) $rss->channel['rss_language'];
					if (!empty($rss->channel['generator'])) {
						$this->_generator = sanitizeVal((string) $rss->channel['generator']);
					}
					//if (!empty($rss->channel['rss_copyright']))	$this->_copyright = (string) $rss->channel['rss_copyright'];
					if (!empty($rss->channel['modified'])) {
						$this->_lastbuilddate = sanitizeVal((string) $rss->channel['modified']);
					}
					//if (!empty($rss->image['rss_url']))			$this->_imageurl = (string) $rss->image['rss_url'];
					if (!empty($rss->channel['link'])) {
						$this->_link = sanitizeVal((string) $rss->channel['link']);
					}
					if (!empty($rss->channel['title'])) {
						$this->_title = sanitizeVal((string) $rss->channel['title']);
					}
					//if (!empty($rss->channel['rss_description']))	$this->_description = (string) $rss->channel['rss_description'];

					if (!empty($rss->channel)) {
						$this->_imageurl = sanitizeVal($this->getAtomImageUrl($rss->channel));
					}
				}
				if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
					$tmprss = xml2php($rss);
					$items = $tmprss['entry'];
				} else {
					// With simplexml
					$items = $rss->items; // With xmlparse
				}
				//var_dump($items);exit;
			}

			$i = 0;

			// Loop on each record
			if (is_array($items)) {
				foreach ($items as $item) {
					//var_dump($item);exit;
					if ($rss->_format == 'rss') {
						if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
							$itemLink = sanitizeVal((string) $item->link);
							$itemTitle = sanitizeVal((string) $item->title);
							$itemDescription = sanitizeVal((string) $item->description);
							$itemPubDate = sanitizeVal((string) $item->pubDate);
							$itemId = '';
							$itemAuthor = '';
						} else {
							$itemLink = sanitizeVal((string) $item['link']);
							$itemTitle = sanitizeVal((string) $item['title']);
							$itemDescription = sanitizeVal((string) $item['description']);
							$itemPubDate = sanitizeVal((string) $item['pubdate']);
							$itemId = sanitizeVal((string) $item['guid']);
							$itemAuthor = sanitizeVal((string) ($item['author'] ?? ''));
						}

						// Loop on each category
						$itemCategory = array();
						if (!empty($item->category) && is_array($item->category)) {
							foreach ($item->category as $cat) {
								$itemCategory[] = (string) $cat;
							}
						}
					} elseif ($rss->_format == 'atom') {
						if (getDolGlobalString('EXTERNALRSS_USE_SIMPLEXML')) {
							$itemLink = (isset($item['link']) ? sanitizeVal((string) $item['link']) : '');
							$itemTitle = sanitizeVal((string) $item['title']);
							$itemDescription = sanitizeVal($this->getAtomItemDescription($item));
							$itemPubDate = sanitizeVal((string) $item['created']);
							$itemId = sanitizeVal((string) $item['id']);
							$itemAuthor = sanitizeVal((string) ($item['author'] ? $item['author'] : $item['author_name']));
						} else {
							$itemLink = (isset($item['link']) ? sanitizeVal((string) $item['link']) : '');
							$itemTitle = sanitizeVal((string) $item['title']);
							$itemDescription = sanitizeVal($this->getAtomItemDescription($item));
							$itemPubDate = sanitizeVal((string) $item['created']);
							$itemId = sanitizeVal((string) $item['id']);
							$itemAuthor = sanitizeVal((string) ($item['author'] ? $item['author'] : $item['author_name']));
						}
						$itemCategory = array();
					} else {
						$itemCategory = array();
						$itemLink = '';
						$itemTitle = '';
						$itemDescription = '';
						$itemPubDate = '';
						$itemId = '';
						$itemAuthor = '';
						print 'ErrorBadFeedFormat';
					}

					// Add record to result array
					$this->_rssarray[$i] = array(
						'link'=>$itemLink,
						'title'=>$itemTitle,
						'description'=>$itemDescription,
						'pubDate'=>$itemPubDate,
						'category'=>$itemCategory,
						'id'=>$itemId,
						'author'=>$itemAuthor
					);
					//var_dump($this->_rssarray);

					$i++;

					if ($i > $maxNb) {
						break; // We get all records we want
					}
				}
			}

			return 1;
		} else {
			$this->error = 'ErrorFailedToLoadRSSFile';
			return -1;
		}
	}



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Triggered when opened tag is found
	 *
	 * 	@param	string		$p			Start
	 *  @param	string		$element	Tag
	 *  @param	array		$attrs		Attributes of tags
	 *  @return	void
	 */
	public function feed_start_element($p, $element, $attrs)
	{
		// phpcs:enable
		$el = $element = strtolower($element);
		$attrs = array_change_key_case($attrs, CASE_LOWER);

		// check for a namespace, and split if found
		$ns = false;
		if (strpos($element, ':')) {
			list($ns, $el) = explode(':', $element, 2);
		}
		if ($ns and $ns != 'rdf') {
			$this->current_namespace = $ns;
		}

		// if feed type isn't set, then this is first element of feed identify feed from root element
		if (empty($this->_format)) {
			if ($el == 'rdf') {
				$this->_format = 'rss';
				$this->feed_version = '1.0';
			} elseif ($el == 'rss') {
				$this->_format = 'rss';
				$this->feed_version = $attrs['version'];
			} elseif ($el == 'feed') {
				$this->_format = 'atom';
				$this->feed_version = $attrs['version'];
				$this->inchannel = true;
			}
			return;
		}

		if ($el == 'channel') {
			$this->inchannel = true;
		} elseif ($el == 'item' || $el == 'entry') {
			$this->initem = true;
			if (isset($attrs['rdf:about'])) {
				$this->current_item['about'] = $attrs['rdf:about'];
			}
		} elseif ($this->_format == 'rss' && $this->current_namespace == '' && $el == 'textinput') {
			// if we're in the default namespace of an RSS feed,
			//  record textinput or image fields
			$this->intextinput = true;
		} elseif ($this->_format == 'rss' && $this->current_namespace == '' && $el == 'image') {
			$this->inimage = true;
		} elseif ($this->_format == 'atom' && in_array($el, $this->_CONTENT_CONSTRUCTS)) {
			// handle atom content constructs
			// avoid clashing w/ RSS mod_content
			if ($el == 'content') {
				$el = 'atom_content';
			}

			$this->incontent = $el;
		} elseif ($this->_format == 'atom' && $this->incontent) {
			// if inside an Atom content construct (e.g. content or summary) field treat tags as text
			// if tags are inlined, then flatten
			$attrs_str = join(' ', array_map('map_attrs', array_keys($attrs), array_values($attrs)));

			$this->append_content("<$element $attrs_str>");

			array_unshift($this->stack, $el);
		} elseif ($this->_format == 'atom' && $el == 'link') {
			// Atom support many links per containging element.
			// Magpie treats link elements of type rel='alternate'
			// as being equivalent to RSS's simple link element.
			if (isset($attrs['rel']) && $attrs['rel'] == 'alternate') {
				$link_el = 'link';
			} elseif (!isset($attrs['rel'])) {
				$link_el = 'link';
			} else {
				$link_el = 'link_'.$attrs['rel'];
			}

			$this->append($link_el, $attrs['href']);
		} else {
			// set stack[0] to current element
			array_unshift($this->stack, $el);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Triggered when CDATA is found
	 *
	 * 	@param	string	$p		P
	 *  @param	string	$text	Tag
	 *  @return	void
	 */
	public function feed_cdata($p, $text)
	{
		// phpcs:enable
		if ($this->_format == 'atom' and $this->incontent) {
			$this->append_content($text);
		} else {
			$current_el = join('_', array_reverse($this->stack));
			$this->append($current_el, $text);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Triggered when closed tag is found
	 *
	 * 	@param	string		$p		P
	 *  @param	string		$el		Tag
	 *  @return	void
	 */
	public function feed_end_element($p, $el)
	{
		// phpcs:enable
		$el = strtolower($el);

		if ($el == 'item' or $el == 'entry') {
			$this->items[] = $this->current_item;
			$this->current_item = array();
			$this->initem = false;
		} elseif ($this->_format == 'rss' and $this->current_namespace == '' and $el == 'textinput') {
			$this->intextinput = false;
		} elseif ($this->_format == 'rss' and $this->current_namespace == '' and $el == 'image') {
			$this->inimage = false;
		} elseif ($this->_format == 'atom' and in_array($el, $this->_CONTENT_CONSTRUCTS)) {
			$this->incontent = false;
		} elseif ($el == 'channel' or $el == 'feed') {
			$this->inchannel = false;
		} elseif ($this->_format == 'atom' and $this->incontent) {
			// balance tags properly
			// note:  i don't think this is actually neccessary
			if ($this->stack[0] == $el) {
				$this->append_content("</$el>");
			} else {
				$this->append_content("<$el />");
			}

			array_shift($this->stack);
		} else {
			array_shift($this->stack);
		}

		$this->current_namespace = false;
	}


	/**
	 * 	To concat 2 strings with no warning if an operand is not defined
	 *
	 * 	@param	string	$str1		Str1
	 *  @param	string	$str2		Str2
	 *  @return	string				String cancatenated
	 */
	public function concat(&$str1, $str2 = "")
	{
		if (!isset($str1)) {
			$str1 = "";
		}
		$str1 .= $str2;
		return $str1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Enter description here ...
	 *
	 * @param	string	$text		Text
	 * @return	void
	 */
	public function append_content($text)
	{
		// phpcs:enable
		if (!empty($this->initem)) {
			$this->concat($this->current_item[$this->incontent], $text);
		} elseif (!empty($this->inchannel)) {
			$this->concat($this->channel[$this->incontent], $text);
		}
	}

	/**
	 * 	smart append - field and namespace aware
	 *
	 * 	@param	string	$el		El
	 * 	@param	string	$text	Text
	 * 	@return	void
	 */
	public function append($el, $text)
	{
		if (!$el) {
			return;
		}
		if (!empty($this->current_namespace)) {
			if (!empty($this->initem)) {
				$this->concat($this->current_item[$this->current_namespace][$el], $text);
			} elseif (!empty($this->inchannel)) {
				$this->concat($this->channel[$this->current_namespace][$el], $text);
			} elseif (!empty($this->intextinput)) {
				$this->concat($this->textinput[$this->current_namespace][$el], $text);
			} elseif (!empty($this->inimage)) {
				$this->concat($this->image[$this->current_namespace][$el], $text);
			}
		} else {
			if (!empty($this->initem)) {
				$this->concat($this->current_item[$el], $text);
			} elseif (!empty($this->intextinput)) {
				$this->concat($this->textinput[$el], $text);
			} elseif (!empty($this->inimage)) {
				$this->concat($this->image[$el], $text);
			} elseif (!empty($this->inchannel)) {
				$this->concat($this->channel[$el], $text);
			}
		}
	}

	/**
	 * Return a description/summary for one item from a ATOM feed
	 *
	 * @param	array	$item		A parsed item of a ATOM feed
	 * @param	int		$maxlength	(optional) The maximum length for the description
	 * @return	string				A summary description
	 */
	private function getAtomItemDescription(array $item, $maxlength = 500)
	{
		$result = "";

		if (isset($item['summary'])) {
			$result = $item['summary'];
		} elseif (isset($item['atom_content'])) {
			$result = $item['atom_content'];
		}

		// remove all HTML elements that can possible break the maximum size of a tooltip,
		// like headings, image, video etc. and allow only simple style elements
		$result = strip_tags($result, "<br><p><ul><ol><li>");

		$result = str_replace("\n", "", $result);

		if (strlen($result) > $maxlength) {
			$result = substr($result, 0, $maxlength);
			$result .= "...";
		}

		return $result;
	}

	/**
	 * Return a URL to a image of the given ATOM feed
	 *
	 * @param	array	$feed	The ATOM feed that possible contain a link to a logo or icon
	 * @return	string			A URL to a image from a ATOM feed when found, otherwise a empty string
	 */
	private function getAtomImageUrl(array $feed)
	{
		if (isset($feed['icon'])) {
			return $feed['logo'];
		}

		if (isset($feed['icon'])) {
			return $feed['logo'];
		}

		if (isset($feed['webfeeds:logo'])) {
			return $feed['webfeeds:logo'];
		}

		if (isset($feed['webfeeds:icon'])) {
			return $feed['webfeeds:icon'];
		}

		if (isset($feed['webfeeds:wordmark'])) {
			return $feed['webfeeds:wordmark'];
		}

		return "";
	}
}


/**
 * Function to convert an XML object into an array
 *
 * @param	SimpleXMLElement			$xml		Xml
 * @return	array|string
 */
function xml2php($xml)
{
	$fils = 0;
	$tab = false;
	$array = array();
	foreach ($xml->children() as $key => $value) {
		$child = xml2php($value);

		//To deal with the attributes
		foreach ($value->attributes() as $ak => $av) {
			$child[$ak] = (string) $av;
		}

		//Let see if the new child is not in the array
		if ($tab === false && in_array($key, array_keys($array))) {
			//If this element is already in the array we will create an indexed array
			$tmp = $array[$key];
			$array[$key] = null;
			$array[$key][] = $tmp;
			$array[$key][] = $child;
			$tab = true;
		} elseif ($tab === true) {
			//Add an element in an existing array
			$array[$key][] = $child;
		} else {
			//Add a simple element
			$array[$key] = $child;
		}

		$fils++;
	}


	if ($fils == 0) {
		return (string) $xml;
	}

	return $array;
}
