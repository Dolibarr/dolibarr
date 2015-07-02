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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
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
    var $db;
    var $error;

    private $_format='';
    private $_urlRSS;
    private $_language;
    private $_generator;
    private $_copyright;
    private $_lastbuilddate;
    private $_imageurl;
    private $_link;
    private $_title;
    private $_description;
    private $_lastfetchdate;    // Last successful fetch
    private $_rssarray=array();

    // For parsing with xmlparser
    var $stack               = array(); // parser stack
    var $_CONTENT_CONSTRUCTS = array('content', 'summary', 'info', 'title', 'tagline', 'copyright');


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
    	$this->db=$db;
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
     * @return string
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
     * 	@param	string	$cachedir	Directory where to save cache file
     *	@return	int					<0 if KO, >0 if OK
     */
    public function parser($urlRSS, $maxNb=0, $cachedelay=60, $cachedir='')
    {
        global $conf;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $rss='';
        $str='';    // This will contain content of feed

        // Check parameters
        if (! dol_is_url($urlRSS))
        {
            $this->error="ErrorBadUrl";
            return -1;
        }

        $this->_urlRSS = $urlRSS;
        $newpathofdestfile=$cachedir.'/'.dol_hash($this->_urlRSS,3);	// Force md5 hash (does not contains special chars)
        $newmask='0644';

        //dol_syslog("RssPArser::parser parse url=".$urlRSS." => cache file=".$newpathofdestfile);
        $nowgmt = dol_now();

        // Search into cache
        $foundintocache=0;
        if ($cachedelay > 0 && $cachedir)
        {
            $filedate=dol_filemtime($newpathofdestfile);
            if ($filedate >= ($nowgmt - $cachedelay))
            {
                //dol_syslog("RssParser::parser cache file ".$newpathofdestfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we use it.");
                $foundintocache=1;

                $this->_lastfetchdate=$filedate;
            }
            else
            {
                dol_syslog(get_class($this)."::parser cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
            }
        }

        // Load file into $str
        if ($foundintocache)    // Cache file found and is not too old
        {
            $str = file_get_contents($newpathofdestfile);
        }
        else
        {
            try {
                ini_set("user_agent","Dolibarr ERP-CRM RSS reader");
                ini_set("max_execution_time", $conf->global->MAIN_USE_RESPONSE_TIMEOUT);
                ini_set("default_socket_timeout", $conf->global->MAIN_USE_RESPONSE_TIMEOUT);

                $opts = array('http'=>array('method'=>"GET"));
                if (! empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)) $opts['http']['timeout']=$conf->global->MAIN_USE_CONNECT_TIMEOUT;
                if (! empty($conf->global->MAIN_PROXY_USE))           $opts['http']['proxy']='tcp://'.$conf->global->MAIN_PROXY_HOST.':'.$conf->global->MAIN_PROXY_PORT;
                //var_dump($opts);exit;
                $context = stream_context_create($opts);

                $str = file_get_contents($this->_urlRSS, false, $context);
            }
            catch (Exception $e) {
                print 'Error retrieving URL '.$this->urlRSS.' - '.$e->getMessage();
            }
        }

        if ($str !== false)
        {
	        // Convert $str into xml
	        if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))
	        {
	            //print 'xx'.LIBXML_NOCDATA;
	            libxml_use_internal_errors(false);
	            $rss = simplexml_load_string($str, "SimpleXMLElement", LIBXML_NOCDATA);
	        }
	        else
	        {
	            $xmlparser=xml_parser_create('');
	            if (!is_resource($xmlparser)) {
	                $this->error="ErrorFailedToCreateParser"; return -1;
	            }

	            xml_set_object($xmlparser, $this);
	            xml_set_element_handler($xmlparser, 'feed_start_element', 'feed_end_element');
	            xml_set_character_data_handler($xmlparser, 'feed_cdata');
	            $status = xml_parse($xmlparser, $str);
	            xml_parser_free($xmlparser);
	            $rss=$this;
	            //var_dump($rss->_format);exit;
	        }
        }

        // If $rss loaded
        if ($rss)
        {
            // Save file into cache
            if (empty($foundintocache) && $cachedir)
            {
                dol_syslog(get_class($this)."::parser cache file ".$newpathofdestfile." is saved onto disk.");
                if (! dol_is_dir($cachedir)) dol_mkdir($cachedir);
                $fp = fopen($newpathofdestfile, 'w');
                fwrite($fp, $str);
                fclose($fp);
                if (! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
                @chmod($newpathofdestfile, octdec($newmask));

                $this->_lastfetchdate=$nowgmt;
            }

            unset($str);    // Free memory

            if (empty($rss->_format))    // If format not detected automatically
            {
                $rss->_format='rss';
                if (empty($rss->channel)) $rss->_format='atom';
            }

            $items=array();

            // Save description entries
            if ($rss->_format == 'rss')
            {
                //var_dump($rss);
                if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))
                {
                    if (!empty($rss->channel->language))      $this->_language = (string) $rss->channel->language;
                    if (!empty($rss->channel->generator))     $this->_generator = (string) $rss->channel->generator;
                    if (!empty($rss->channel->copyright))     $this->_copyright = (string) $rss->channel->copyright;
                    if (!empty($rss->channel->lastbuilddate)) $this->_lastbuilddate = (string) $rss->channel->lastbuilddate;
                    if (!empty($rss->channel->image->url[0])) $this->_imageurl = (string) $rss->channel->image->url[0];
                    if (!empty($rss->channel->link))		  $this->_link = (string) $rss->channel->link;
                    if (!empty($rss->channel->title))         $this->_title = (string) $rss->channel->title;
                    if (!empty($rss->channel->description))	  $this->_description = (string) $rss->channel->description;
                }
                else
                {
                    //var_dump($rss->channel);
                    if (!empty($rss->channel['language']))      $this->_language = (string) $rss->channel['language'];
                    if (!empty($rss->channel['generator']))     $this->_generator = (string) $rss->channel['generator'];
                    if (!empty($rss->channel['copyright']))     $this->_copyright = (string) $rss->channel['copyright'];
                    if (!empty($rss->channel['lastbuilddate'])) $this->_lastbuilddate = (string) $rss->channel['lastbuilddate'];
                    if (!empty($rss->image['url']))             $this->_imageurl = (string) $rss->image['url'];
                    if (!empty($rss->channel['link']))		    $this->_link = (string) $rss->channel['link'];
                    if (!empty($rss->channel['title']))         $this->_title = (string) $rss->channel['title'];
                    if (!empty($rss->channel['description']))   $this->_description = (string) $rss->channel['description'];
                }

                if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML)) $items=$rss->channel->item;    // With simplexml
                else $items=$rss->items;                                                              // With xmlparse
                //var_dump($items);exit;
            }
            else if ($rss->_format == 'atom')
            {
                //var_dump($rss);
                if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))
                {
                    if (!empty($rss->generator))     $this->_generator = (string) $rss->generator;
                    if (!empty($rss->lastbuilddate)) $this->_lastbuilddate = (string) $rss->modified;
                    if (!empty($rss->link->href))    $this->_link = (string) $rss->link->href;
                    if (!empty($rss->title))         $this->_title = (string) $rss->title;
                    if (!empty($rss->description))	 $this->_description = (string) $rss->description;
                }
                else
                {
                    //if (!empty($rss->channel['rss_language']))      $this->_language = (string) $rss->channel['rss_language'];
                    if (!empty($rss->channel['generator']))     $this->_generator = (string) $rss->channel['generator'];
                    //if (!empty($rss->channel['rss_copyright']))     $this->_copyright = (string) $rss->channel['rss_copyright'];
                    if (!empty($rss->channel['modified'])) $this->_lastbuilddate = (string) $rss->channel['modified'];
                    //if (!empty($rss->image['rss_url']))             $this->_imageurl = (string) $rss->image['rss_url'];
                    if (!empty($rss->channel['link']))		    $this->_link = (string) $rss->channel['link'];
                    if (!empty($rss->channel['title']))         $this->_title = (string) $rss->channel['title'];
                    //if (!empty($rss->channel['rss_description']))   $this->_description = (string) $rss->channel['rss_description'];
                }
                if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))  {
                    $tmprss=xml2php($rss); $items=$tmprss['entry'];
                } // With simplexml
                else $items=$rss->items;                                                              // With xmlparse
                //var_dump($items);exit;
            }

            $i = 0;

            // Loop on each record
            if (is_array($items))
            {
                foreach($items as $item)
                {
                    //var_dump($item);exit;
                    if ($rss->_format == 'rss')
                    {
                        if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))
                        {
                            $itemLink = (string) $item->link;
                            $itemTitle = (string) $item->title;
                            $itemDescription = (string) $item->description;
                            $itemPubDate = (string) $item->pubDate;
                            $itemId = '';
                            $itemAuthor = '';
                        }
                        else
                        {
                            $itemLink = (string) $item['link'];
                            $itemTitle = (string) $item['title'];
                            $itemDescription = (string) $item['description'];
                            $itemPubDate = (string) $item['pubdate'];
                            $itemId = (string) $item['guid'];
                            $itemAuthor = (string) $item['author'];
                        }

                        // Loop on each category
                        $itemCategory=array();
                        if (is_array($item->category))
                        {
                            foreach ($item->category as $cat)
                            {
                                $itemCategory[] = (string) $cat;
                            }
                        }
                    }
                    else if ($rss->_format == 'atom')
                    {
                        if (! empty($conf->global->EXTERNALRSS_USE_SIMPLEXML))
                        {
                            $itemLink = (isset($item['link']['href']) ? (string) $item['link']['href'] : '');
                            $itemTitle = (string) $item['title'];
                            $itemDescription = (string) $item['summary'];
                            $itemPubDate = (string) $item['created'];
                            $itemId = (string) $item['id'];
                            $itemAuthor = (string) ($item['author']?$item['author']:$item['author_name']);
                        }
                        else
                        {
                            $itemLink = (isset($item['link']['href']) ? (string) $item['link']['href'] : '');
                            $itemTitle = (string) $item['title'];
                            $itemDescription = (string) $item['summary'];
                            $itemPubDate = (string) $item['created'];
                            $itemId = (string) $item['id'];
                            $itemAuthor = (string) ($item['author']?$item['author']:$item['author_name']);
                        }
                    }
                    else print 'ErrorBadFeedFormat';

                    // Add record to result array
                    $this->_rssarray[$i] = array(
    					'link'=>$itemLink,
    					'title'=>$itemTitle,
    					'description'=>$itemDescription,
    					'pubDate'=>$itemPubDate,
    					'category'=>$itemCategory,
    				    'id'=>$itemId,
    				    'author'=>$itemAuthor);
                    //var_dump($this->_rssarray);

                    $i++;

                    if ($i > $maxNb)    break;    // We get all records we want
                }
            }

            return 1;
        }
        else
        {
            $this->error='ErrorFailedToLoadRSSFile';
            return -1;
        }
    }



    /**
     * 	Triggered when opened tag is found
     *
     * 	@param	string		$p			Start
     *  @param	string		$element	Tag
     *  @param	array		$attrs		Attributes of tags
     *  @return	void
     */
    function feed_start_element($p, $element, &$attrs)
    {
        $el = $element = strtolower($element);
        $attrs = array_change_key_case($attrs, CASE_LOWER);

        // check for a namespace, and split if found
        $ns = false;
        if (strpos($element, ':'))
        {
            list($ns, $el) = explode(':', $element, 2);
        }
        if ( $ns and $ns != 'rdf' )
        {
            $this->current_namespace = $ns;
        }

        // if feed type isn't set, then this is first element of feed identify feed from root element
        if (empty($this->_format))
        {
            if ( $el == 'rdf' ) {
                $this->_format = 'rss';
                $this->feed_version = '1.0';
            }
            elseif ( $el == 'rss' ) {
                $this->_format = 'rss';
                $this->feed_version = $attrs['version'];
            }
            elseif ( $el == 'feed' ) {
                $this->_format = 'atom';
                $this->feed_version = $attrs['version'];
                $this->inchannel = true;
            }
            return;
        }

        if ( $el == 'channel' )
        {
            $this->inchannel = true;
        }
        elseif ($el == 'item' or $el == 'entry' )
        {
            $this->initem = true;
            if ( isset($attrs['rdf:about']) ) {
                $this->current_item['about'] = $attrs['rdf:about'];
            }
        }

        // if we're in the default namespace of an RSS feed,
        //  record textinput or image fields
        elseif (
        $this->_format == 'rss' and
        $this->current_namespace == '' and
        $el == 'textinput' )
        {
            $this->intextinput = true;
        }

        elseif (
        $this->_format == 'rss' and
        $this->current_namespace == '' and
        $el == 'image' )
        {
            $this->inimage = true;
        }

        // handle atom content constructs
        elseif ( $this->_format == 'atom' and in_array($el, $this->_CONTENT_CONSTRUCTS) )
        {
            // avoid clashing w/ RSS mod_content
            if ($el == 'content' ) {
                $el = 'atom_content';
            }

            $this->incontent = $el;


        }

        // if inside an Atom content construct (e.g. content or summary) field treat tags as text
        elseif ($this->_format == 'atom' and $this->incontent )
        {
            // if tags are inlined, then flatten
            $attrs_str = join(' ', array_map('map_attrs', array_keys($attrs), array_values($attrs)));

            $this->append_content("<$element $attrs_str>");

            array_unshift($this->stack, $el);
        }

        // Atom support many links per containging element.
        // Magpie treats link elements of type rel='alternate'
        // as being equivalent to RSS's simple link element.
        //
        elseif ($this->_format == 'atom' and $el == 'link' )
        {
            if ( isset($attrs['rel']) && $attrs['rel'] == 'alternate' )
            {
                $link_el = 'link';
            }
            else {
                $link_el = 'link_' . $attrs['rel'];
            }

            $this->append($link_el, $attrs['href']);
        }
        // set stack[0] to current element
        else {
            array_unshift($this->stack, $el);
        }
    }


    /**
     * 	Triggered when CDATA is found
     *
     * 	@param	string	$p		P
     *  @param	string	$text	Tag
     *  @return	void
     */
    function feed_cdata($p, $text)
    {
        if ($this->_format == 'atom' and $this->incontent)
        {
            $this->append_content($text);
        }
        else
        {
            $current_el = join('_', array_reverse($this->stack));
            $this->append($current_el, $text);
        }
    }

    /**
     * 	Triggered when closed tag is found
     *
     * 	@param	string		$p		P
     *  @param	string		$el		Tag
     *  @return	void
     */
    function feed_end_element($p, $el)
    {
        $el = strtolower($el);

        if ($el == 'item' or $el == 'entry')
        {
            $this->items[] = $this->current_item;
            $this->current_item = array();
            $this->initem = false;
        }
        elseif ($this->_format == 'rss' and $this->current_namespace == '' and $el == 'textinput' )
        {
            $this->intextinput = false;
        }
        elseif ($this->_format == 'rss' and $this->current_namespace == '' and $el == 'image' )
        {
            $this->inimage = false;
        }
        elseif ($this->_format == 'atom' and in_array($el, $this->_CONTENT_CONSTRUCTS) )
        {
            $this->incontent = false;
        }
        elseif ($el == 'channel' or $el == 'feed' )
        {
            $this->inchannel = false;
        }
        elseif ($this->_format == 'atom' and $this->incontent  ) {
            // balance tags properly
            // note:  i don't think this is actually neccessary
            if ( $this->stack[0] == $el )
            {
                $this->append_content("</$el>");
            }
            else {
                $this->append_content("<$el />");
            }

            array_shift($this->stack);
        }
        else {
            array_shift($this->stack);
        }

        $this->current_namespace = false;
    }


    /**
     * 	To concat 2 string with no warning if an operand is not defined
     *
     * 	@param	string	$str1		Str1
     *  @param	string	$str2		Str2
     *  @return	string				String cancatenated
     */
    function concat(&$str1, $str2="")
    {
        if (!isset($str1) ) {
            $str1="";
        }
        $str1 .= $str2;
    }

    /**
     * Enter description here ...
     *
     * @param	string	$text		Text
     * @return	void
     */
    function append_content($text)
    {
        if ( $this->initem ) {
            $this->concat($this->current_item[ $this->incontent ], $text);
        }
        elseif ( $this->inchannel ) {
            $this->concat($this->channel[ $this->incontent ], $text);
        }
    }

    /**
     * 	smart append - field and namespace aware
     *
     * 	@param	string	$el		El
     * 	@param	string	$text	Text
     * 	@return	void
     */
    function append($el, $text)
    {
        if (!$el) {
            return;
        }
        if ( $this->current_namespace )
        {
            if ( $this->initem ) {
                $this->concat($this->current_item[ $this->current_namespace ][ $el ], $text);
            }
            elseif ($this->inchannel) {
                $this->concat($this->channel[ $this->current_namespace][ $el ], $text);
            }
            elseif ($this->intextinput) {
                $this->concat($this->textinput[ $this->current_namespace][ $el ], $text);
            }
            elseif ($this->inimage) {
                $this->concat($this->image[ $this->current_namespace ][ $el ], $text);
            }
        }
        else {
            if ( $this->initem ) {
                $this->concat($this->current_item[ $el ], $text);
            }
            elseif ($this->intextinput) {
                $this->concat($this->textinput[ $el ], $text);
            }
            elseif ($this->inimage) {
                $this->concat($this->image[ $el ], $text);
            }
            elseif ($this->inchannel) {
                $this->concat($this->channel[ $el ], $text);
            }

        }
    }

}


/**
 * Function to convert an XML object into an array
 *
 * @param	SimpleXMLElement	$xml		Xml
 * @return	void
 */
function xml2php($xml)
{
    $fils = 0;
    $tab = false;
    $array = array();
    foreach($xml->children() as $key => $value)
    {
        $child = xml2php($value);

        //To deal with the attributes
        foreach($value->attributes() as $ak=>$av)
        {
            $child[$ak] = (string) $av;

        }

        //Let see if the new child is not in the array
        if($tab==false && in_array($key,array_keys($array)))
        {
            //If this element is already in the array we will create an indexed array
            $tmp = $array[$key];
            $array[$key] = NULL;
            $array[$key][] = $tmp;
            $array[$key][] = $child;
            $tab = true;
        }
        elseif($tab == true)
        {
            //Add an element in an existing array
            $array[$key][] = $child;
        }
        else
        {
            //Add a simple element
            $array[$key] = $child;
        }

        $fils++;
    }


    if($fils==0)
    {
        return (string) $xml;
    }

    return $array;

}

