<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \brief      File of class to parse rss feeds
 *      \version    $Id: rssparser.class.php,v 1.2 2011/08/26 17:59:14 eldy Exp $
 */
class RssParser
{
    var $db;
    var $error;

	protected $_format='rss';
	protected $_urlRSS;
	protected $_language;
	protected $_generator;
	protected $_copyright;
	protected $_lastbuilddate;
	protected $_imageurl;
	protected $_link;
	protected $_title;
	protected $_description;
	protected $_lastfetchdate;    // Last successful fetch
	protected $_rssarray=array();

	// Accessors
	public function getFormat()        { return $this->_format; }
	public function getUrlRss()        { return $this->_urlRSS; }
	public function getLanguage()      { return $this->_language; }
	public function getGenerator()     { return $this->_generator; }
	public function getCopyright()     { return $this->_copyright; }
	public function getLastBuildDate() { return $this->_lastbuilddate; }
	public function getImageUrl()      { return $this->_imageurl; }
	public function getLink()          { return $this->_link; }
	public function getTitle()         { return $this->_title; }
	public function getDescription()   { return $this->_description; }
	public function getLastFetchDate() { return $this->_lastfetchdate; }
	public function getItems()         { return $this->_rssarray; }

	/**
	 * 		Constructor
	 */
	public function RssParser($db)
	{
	    $this->db=$db;
	}


	/**
	 * 	Parse rss URL
	 *
	 * 	@param		urlRSS		Url to parse
	 * 	@param		maxNb		Max nb of records to get (0 for no limit)
	 * 	@param		cachedelay	0=No cache, nb of seconds we accept cache files (cachedir must also be defined)
	 * 	@param		cachedir	Directory where to save cache file
	 *	@return		int			<0 if KO, >0 if OK
	 */
	public function parser($urlRSS, $maxNb=0, $cachedelay=60, $cachedir='')
	{
	    include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

	    // Check parameters
	    if (! dol_is_url($urlRSS))
	    {
	        $this->error="ErrorBadUrl";
	        return -1;
	    }

		$this->_urlRSS = $urlRSS;
	    $newpathofdestfile=$cachedir.'/'.md5($this->_urlRSS);
		$newmask=octdec('0644');

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
			    dol_syslog("RssParser::parser cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
        }

		// Load file into $rss
		if ($foundintocache)    // Cache file found and is not too old
		{
		    $str = file_get_contents($newpathofdestfile);
		    $rss = simplexml_load_string(unserialize($str));
		}
		else
		{
		    try {
		        $rss = @simplexml_load_file($this->_urlRSS);
var_dump($this->_urlRSS);
		    		    }
		    catch (Exception $e) {
		         print 'Error retrieving URL '.$this->urlRSS.' - '.$e->getMessage();
		    }
		}

		// If $rss loaded
		if ($rss)
		{
		    // Save file into cache
		    if (empty($foundintocache) && $cachedir)
		    {
				dol_syslog("RssParser::parser cache file ".$newpathofdestfile." is saved onto disk.");
		        if (! dol_is_dir($cachedir)) dol_mkdir($cachedir);
		        $fp = fopen($newpathofdestfile, 'w');
                fwrite($fp, serialize($rss->asXML()));
                fclose($fp);
		        if (! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
		        @chmod($newpathofdestfile, octdec($newmask));

		        $this->_lastfetchdate=$nowgmt;
		    }

			// Save description entries
			if (!empty($rss->channel->language))      $this->_language = (string) $rss->channel->language;
			if (!empty($rss->channel->generator))     $this->_generator = (string) $rss->channel->generator;
			if (!empty($rss->channel->copyright))     $this->_copyright = (string) $rss->channel->copyright;
			if (!empty($rss->channel->lastbuilddate)) $this->_lastbuilddate = (string) $rss->channel->lastbuilddate;
			if (!empty($rss->channel->image->url[0])) $this->_imageurl = (string) $rss->channel->image->url[0];
			if (!empty($rss->channel->link))		  $this->_link = (string) $rss->channel->link;
			if (!empty($rss->channel->title))         $this->_title = (string) $rss->channel->title;
			if (!empty($rss->channel->description))	  $this->_description = (string) $rss->channel->description;
            // TODO imageurl

			$i = 0;

			// Loop on each record
			foreach($rss->channel->item as $item)
			{
				$itemLink = (string) $item->link;
			    $itemTitle = (string) $item->title;
				$itemDescription = (string) $item->description;
			    $itemPubDate = (string) $item->pubDate;

				// Loop on each category
				$itemCategory=array();
				foreach ($item->category as $cat)
				{
					$itemCategory[] = (string) $cat;
				}

				// Add record to result array
				$this->_rssarray[$i] = array(
					'link'=>$itemLink,
					'title'=>$itemTitle,
					'description'=>$itemDescription,
					'pubDate'=>$itemPubDate,
					'category'=>$itemCategory);

				$i++;

				if ($i > $maxNb)    break;    // We get all records we want
			}

			return 1;
		}
		else
		{
		    $this->error='ErrorFailedToLoadRSSFile';
			return -1;
		}
	}

}
?>