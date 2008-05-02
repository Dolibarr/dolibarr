<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
	    \file       htdocs/includes/boxes/box_external_rss.php
        \ingroup    external_rss
		\brief      Fichier de gestion d'une box pour le module external_rss
		\version    $Id$
*/

include_once(MAGPIERSS_PATH."rss_fetch.inc");
include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_external_rss extends ModeleBoxes {

    var $boxcode="lastrssinfos";
    var $boximg="object_rss";
    var $boxlabel;
    var $depends = array();

	var $db;
	var $param;
	
    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_external_rss($DB,$param)
    {
        global $langs;
        $langs->load("boxes");

		$this->db=$DB;
		$this->param=$param;
		
        $this->boxlabel=$langs->trans("BoxLastRssInfos");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $conf;
        $langs->load("boxes");

		// On recupere numero de param de la boite
		ereg('^([0-9]+) ',$this->param,$reg);
		$site=$reg[1];

		// Creation rep (pas besoin, on le cree apres recup flux)
		// documents/rss is created by module activation
		// documents/rss/tmp is created by magpie
		//$result=create_exdir($conf->externalrss->dir_temp);
		
		// Recupere flux RSS definie dans EXTERNAL_RSS_URLRSS_$site
        $url=@constant("EXTERNAL_RSS_URLRSS_".$site);
        //define('MAGPIE_DEBUG',1);
        $rss=fetch_rss($url);
        if (! is_object($rss))
        {
        	dolibarr_syslog("FETCH_RSS site=".$site);
        	dolibarr_syslog("FETCH_RSS url=".$url);
        	return -1;
        }
        
		// INFO sur le channel
		$description=$rss->channel['tagline'];
		$link=$rss->channel['link'];

        $title=$langs->trans("BoxTitleLastRssInfos",$max, @constant("EXTERNAL_RSS_TITLE_". $site));
        if ($rss->ERROR)
        {
            // Affiche warning car il y a eu une erreur
            $title.=" ".img_error($langs->trans("FailedToRefreshDataInfoNotUpToDate",(isset($rss->date)?dolibarr_print_date($rss->date,"dayhourtext"):$langs->trans("Unknown"))));
            $this->info_box_head = array('text' => $title,'limit' => 0);
        }
        else
        {         
        	$this->info_box_head = array('text' => $title,
        		'sublink' => $link, 'subtext'=>$langs->trans("LastRefreshDate").': '.(isset($rss->date)?dolibarr_print_date($rss->date,"dayhourtext"):$langs->trans("Unknown")), 'subpicto'=>'object_bookmark');
		}

		// INFO sur le élements
        for($i = 0; $i < $max && $i < sizeof($rss->items); $i++)
        {
            $item = $rss->items[$i];

			// Magpierss common fields
            $href  = $item['link'];
        	$title = urldecode($item['title']);
			$date  = $item['date_timestamp'];	// date will be empty if conversion into timestamp failed
			if ($rss->is_rss())		// If RSS
			{
				if (! $date && isset($item['pubdate']))    $date=$item['pubdate'];
				if (! $date && isset($item['dc']['date'])) $date=$item['dc']['date'];
				//$item['dc']['language']
				//$item['dc']['publisher']
			}
			if ($rss->is_atom())	// If Atom
			{
				if (! $date && isset($item['issued']))    $date=$item['issued'];
				if (! $date && isset($item['modified']))  $date=$item['modified'];
				//$item['issued']
				//$item['modified']
				//$item['atom_content']
			}
			if (is_numeric($date)) $date=dolibarr_print_date($date,"dayhour");
			$result = $this->utf8_check($title);
            if ($result)
            {
            	$title=utf8_decode($title);
            }
            $title=ereg_replace("([[:alnum:]])\?([[:alnum:]])","\\1'\\2",$title);   // Gère problème des apostrophes mal codée/décodée par utf8
            $title=ereg_replace("^\s+","",$title);                                  // Supprime espaces de début
            $this->info_box_contents["$href"]="$title";
            $this->info_box_contents[$i][0] = array('align' => 'left',
            'logo' => $this->boximg,
            'text' => $title,
            'url' => $href,
            'maxlength' => 64,
            'target' => 'newrss');
            $this->info_box_contents[$i][1] = array('align' => 'right',
            'text' => $date,
            'td' => 'nowrap="1"');
        }
    }
    
    /**
     *      \brief      Vérifie si le flux est en UTF8
     *      \param      $Str        chaine à vérifier
     */
    function utf8_check($Str) {
    	for ($i=0; $i<strlen($Str); $i++) {
    		if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
    		elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
    		elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
    		elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
    		elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
    		elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
    		else return false; # Does not match any model
    		for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
    			if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
    			return false;
    		}
    	}
    	return true;
    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
