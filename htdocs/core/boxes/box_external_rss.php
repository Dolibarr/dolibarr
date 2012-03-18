<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * 	    \file       htdocs/core/boxes/box_external_rss.php
 *      \ingroup    external_rss
 *      \brief      Fichier de gestion d'une box pour le module external_rss
 */

include_once(DOL_DOCUMENT_ROOT."/core/class/rssparser.class.php");
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


/**
 * Class to manage the box to show RSS feeds
 */
class box_external_rss extends ModeleBoxes
{
    var $boxcode="lastrssinfos";
    var $boximg="object_rss";
    var $boxlabel;
    var $depends = array("externalrss");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *  Constructor
     *
     * 	@param	DoliDB	$db		Database handler
     */
    function box_external_rss($db,$param)
    {
        global $langs;
        $langs->load("boxes");

		$this->db=$db;
		$this->param=$param;

        $this->boxlabel=$langs->transnoentitiesnoconv("BoxLastRssInfos");
    }

    /**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        	Maximum number of records to load
     *  @param	int		$cachedelay		Delay we accept for cache file
     *  @return	void
     */
    function loadBox($max=5, $cachedelay=3600)
    {
        global $user, $langs, $conf;
        $langs->load("boxes");

		$this->max=$max;

		// On recupere numero de param de la boite
		preg_match('/^([0-9]+) /',$this->param,$reg);
		$site=$reg[1];

		// Create dir nor required
		// documents/externalrss is created by module activation
		// documents/externalrss/tmp is created by rssparser

		// Get RSS feed
        $url=@constant("EXTERNAL_RSS_URLRSS_".$site);

        $rssparser=new RssParser($this->db);
		$result = $rssparser->parser($url, $this->max, $cachedelay, $conf->externalrss->dir_temp);

		// INFO on channel
		$description=$rssparser->getDescription();
		$link=$rssparser->getLink();

        $title=$langs->trans("BoxTitleLastRssInfos",$max, @constant("EXTERNAL_RSS_TITLE_". $site));
        if ($result < 0 || ! empty($rssparser->error))
        {
            // Show warning
            $title.=" ".img_error($langs->trans("FailedToRefreshDataInfoNotUpToDate",($rssparser->getLastFetchDate()?dol_print_date($rssparser->getLastFetchDate(),"dayhourtext"):$langs->trans("Unknown"))));
            $this->info_box_head = array('text' => $title,'limit' => 0);
        }
        else
        {
        	$this->info_box_head = array('text' => $title,
        		'sublink' => $link, 'subtext'=>$langs->trans("LastRefreshDate").': '.($rssparser->getLastFetchDate()?dol_print_date($rssparser->getLastFetchDate(),"dayhourtext"):$langs->trans("Unknown")), 'subpicto'=>'object_bookmark');
		}

		// INFO on items
		$items=$rssparser->getItems();
		$nbitems=count($items);
        for($i = 0; $i < $max && $i < $nbitems; $i++)
        {
            $item = $items[$i];

			// Feed common fields
            $href  = $item['link'];
        	$title = urldecode($item['title']);
			$date  = $item['date_timestamp'];	// date will be empty if conversion into timestamp failed
			if ($rssparser->getFormat() == 'rss')		// If RSS
			{
				if (! $date && isset($item['pubdate']))    $date=$item['pubdate'];
				if (! $date && isset($item['dc']['date'])) $date=$item['dc']['date'];
				//$item['dc']['language']
				//$item['dc']['publisher']
			}
			if ($rssparser->getFormat() == 'atom')	// If Atom
			{
				if (! $date && isset($item['issued']))    $date=$item['issued'];
				if (! $date && isset($item['modified']))  $date=$item['modified'];
				//$item['issued']
				//$item['modified']
				//$item['atom_content']
			}
			if (is_numeric($date)) $date=dol_print_date($date,"dayhour");

			$isutf8 = utf8_check($title);
	        if (! $isutf8 && $conf->file->character_set_client == 'UTF-8') $title=utf8_encode($title);
	        elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') $title=utf8_decode($title);

	        $title=preg_replace("/([[:alnum:]])\?([[:alnum:]])/","\\1'\\2",$title);   // Gere probleme des apostrophes mal codee/decodee par utf8
            $title=preg_replace("/^\s+/","",$title);                                  // Supprime espaces de debut
            $this->info_box_contents["$href"]="$title";

            $this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
            'logo' => $this->boximg,
            'url' => $href,
            'target' => 'newrss');

            $this->info_box_contents[$i][1] = array('td' => 'align="left"',
            'text' => $title,
            'url' => $href,
            'maxlength' => 64,
            'target' => 'newrss');

            $this->info_box_contents[$i][2] = array('td' => 'align="right" nowrap="1"',
            'text' => $date);
        }
    }


	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
