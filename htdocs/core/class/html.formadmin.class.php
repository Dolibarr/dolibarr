<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin 		<patrick.raguin@gmail.com>
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
 *      \file       htdocs/html.formadmin.class.php
 *      \brief      File of class for html functions for admin pages
 *		\version	$Id$
 */


/**
 *       \class      FormAdmin
 *       \brief      Class to generate html code for admin pages
 */
class FormAdmin
{
	var $db;
	var $error;


	/**
	 *	\brief     Constructor
	 *	\param     DB      handler d'acces base de donnee
	 */
	function FormAdmin($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *    	\brief      Retourne la liste deroulante des langues disponibles
	 *    	\param      selected        Langue pre-selectionnee
	 *    	\param      htmlname        Nom de la zone select
	 *    	\param      showauto        Affiche choix auto
	 * 		\param		filter			Array of keys to exclude in list
	 * 		\param		showempty		Add empty value
	 */
	function select_lang($selected='',$htmlname='lang_id',$showauto=0,$filter=0,$showempty=0)
	{
		global $langs;

		$langs_available=$langs->get_available_languages(DOL_DOCUMENT_ROOT,12);

		print '<select class="flat" name="'.$htmlname.'">';
		if ($showempty)
		{
			print '<option value=""';
			if ($selected == '') print ' selected="true"';
			print '>&nbsp;</option>';
		}
		if ($showauto)
		{
			print '<option value="auto"';
			if ($selected == 'auto') print ' selected="true"';
			print '>'.$langs->trans("AutoDetectLang").'</option>';
		}

		asort($langs_available);

		foreach ($langs_available as $key => $value)
		{
			if ($filter && is_array($filter))
			{
				if ( ! array_key_exists($key, $filter))
				{
					print '<option value="'.$key.'">'.$value.'</option>';
				}
			}
			else if ($selected == $key)
			{
				print '<option value="'.$key.'" selected="true">'.$value.'</option>';
			}
			else
			{
				print '<option value="'.$key.'">'.$value.'</option>';
			}
			$i++;
		}
		print '</select>';
	}

	/**
     *    \brief      Retourne la liste deroulante des menus disponibles (eldy_backoffice, ...)
     *    \param      selected        Menu pre-selectionnee
     *    \param      htmlname        Nom de la zone select
     *    \param      dirmenu         Repertoire a scanner
     */
    function select_menu($selected='',$htmlname,$dirmenu)
    {
        global $langs,$conf;

        if ($selected == 'eldy.php') $selected='eldy_backoffice.php';  // Pour compatibilite

		$menuarray=array();
        $handle=opendir($dirmenu);
        while (($file = readdir($handle))!==false)
        {
            if (is_file($dirmenu."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                if (preg_match('/lib\.php$/i',$file)) continue;	// We exclude library files
            	$filelib=preg_replace('/\.php$/i','',$file);
				$prefix='';
				if (preg_match('/^eldy/i',$file)) $prefix='0';	// 0=Recommanded, 1=Experimental, 2=Other
				else $prefix='2';

                if ($file == $selected)
                {
					$menuarray[$prefix.'_'.$file]='<option value="'.$file.'" selected="true">'.$filelib.'</option>';
                }
                else
                {
                    $menuarray[$prefix.'_'.$file]='<option value="'.$file.'">'.$filelib.'</option>';
                }
            }
        }
		ksort($menuarray);

		// Affichage liste deroulante des menus
        print '<select class="flat" name="'.$htmlname.'">';
        $oldprefix='';
		foreach ($menuarray as $key => $val)
		{
			$tab=explode('_',$key);
			$newprefix=$tab[0];
			if (! empty($conf->browser->firefox) && $newprefix != $oldprefix)	// Add separators
			{
				// Affiche titre
				print '<option value="-1" disabled="disabled">';
				if ($newprefix=='0') print '-- '.$langs->trans("VersionRecommanded").' --';
				if ($newprefix=='1') print '-- '.$langs->trans("VersionExperimental").' --';
				if ($newprefix=='2') print '-- '.$langs->trans("Other").' --';
				print '</option>';
				$oldprefix=$newprefix;
			}
			print $val."\n";	// Show menu entry
		}
		print '</select>';
    }

    /**
     *    \brief      Retourne la liste deroulante des menus disponibles
     *    \param      selected        Menu pre-selectionnee
     *    \param      htmlname        Nom de la zone select
     *    \param      dirmenu         Repertoire a scanner
     */
    function select_menu_families($selected='',$htmlname,$dirmenu)
    {
		global $langs,$conf;

		$menuarray=array();
        $handle=opendir($dirmenu);
        while (($file = readdir($handle))!==false)
        {
            if (is_file($dirmenu."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                $filelib=preg_replace('/(_backoffice|_frontoffice)?\.php$/i','',$file);
				if (preg_match('/^default/i',$filelib)) continue;
				if (preg_match('/^empty/i',$filelib)) continue;
				if (preg_match('/\.lib/i',$filelib)) continue;

				$menuarray[$filelib]=1;
            }
			$menuarray['all']=1;
        }
		ksort($menuarray);

		// Affichage liste deroulante des menus
        print '<select class="flat" name="'.$htmlname.'">';
        $oldprefix='';
		foreach ($menuarray as $key => $val)
		{
			$tab=explode('_',$key);
			$newprefix=$tab[0];
			print '<option value="'.$key.'"';
            if ($key == $selected)
			{
				print '	selected="true"';
			}
            //if ($key == 'rodolphe') print ' disabled="true"';
			print '>';
			if ($key == 'all') print $langs->trans("AllMenus");
			else print $key;
			//if ($key == 'rodolphe') print ' ('.$langs->trans("PersonalizedMenusNotSupported").')';
			print '</option>'."\n";
		}
		print '</select>';
    }


    /**
     *    \brief      Retourne la liste deroulante des menus disponibles (eldy)
     *    \param      selected        Menu pre-selectionnee
     *    \param      htmlname        Nom de la zone select
     */
    function select_timezone($selected='',$htmlname)
    {
		global $langs,$conf;

        print '<select class="flat" name="'.$htmlname.'">';
		print '<option value="-1">&nbsp;</option>';

		$arraytz=array(
			"Pacific/Midway"=>"GMT-11:00",
			"Pacific/Fakaofo"=>"GMT-10:00",
			"America/Anchorage"=>"GMT-09:00",
			"America/Los_Angeles"=>"GMT-08:00",
			"America/Dawson_Creek"=>"GMT-07:00",
			"America/Chicago"=>"GMT-06:00",
			"America/Bogota"=>"GMT-05:00",
			"America/Anguilla"=>"GMT-04:00",
			"America/Araguaina"=>"GMT-03:00",
			"America/Noronha"=>"GMT-02:00",
			"Atlantic/Azores"=>"GMT-01:00",
			"Africa/Abidjan"=>"GMT+00:00",
			"Europe/Paris"=>"GMT+01:00",
			"Europe/Helsinki"=>"GMT+02:00",
			"Europe/Moscow"=>"GMT+03:00",
			"Asia/Dubai"=>"GMT+04:00",
			"Asia/Karachi"=>"GMT+05:00",
			"Indian/Chagos"=>"GMT+06:00",
			"Asia/Jakarta"=>"GMT+07:00",
			"Asia/Hong_Kong"=>"GMT+08:00",
			"Asia/Tokyo"=>"GMT+09:00",
			"Australia/Sydney"=>"GMT+10:00",
			"Pacific/Noumea"=>"GMT+11:00",
			"Pacific/Auckland"=>"GMT+12:00",
			"Pacific/Enderbury"=>"GMT+13:00"
		);
		foreach ($arraytz as $lib => $gmt)
		{
			print '<option value="'.$lib.'"';
			if ($selected == $lib || $selected == $gmt) print ' selected="true"';
			print '>'.$gmt.'</option>'."\n";
		}
		print '</select>';
	}

    /**
     *    \brief      Return colors list selector
     *    \param      selected        Color pre-selected
     *    \param      htmlname        Name of html select zone
     */
    function select_colors($selected='', $htmlname, $arrayofcolors='', $showcolorbox=1)
    {
		global $langs,$conf;

		if (! is_array($arrayofcolors)) $arrayofcolors=array('29527A','5229A3','A32929','7A367A','B1365F','0D7813');

		//$selected='';
		if ($showcolorbox) print '<table class="nobordernopadding"><tr valign="middle" class="nobordernopadding"><td class="nobordernopadding">';

		print '<select class="flat" name="'.$htmlname.'">';
		print '<option value="-1">&nbsp;</option>';
		foreach ($arrayofcolors as $val)
		{
			print '<option value="'.$val.'"';
			if ($selected == $val) print ' selected="true"';
			print '>'.$val.'</option>';
		}
		print '</select>';

		if ($showcolorbox)
		{
			print '</td><td style="padding-left: 4px" nowrap="nowrap">';
			print '<!-- Box color '.$selected.' -->';
			print '<table style="border-collapse: collapse; margin:0px; padding: 0px; border: 1px solid #888888; background: #'.$selected.';" width="12" height="10">';
			print '<tr class="nocellnopadd"><td></td></tr>';
			print '</table>';
			print '</td></tr></table>';
		}
	}
}

?>
