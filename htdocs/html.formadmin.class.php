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
        \file       htdocs/html.formadmin.class.php
        \brief      File of class for html functions for admin pages
		\version	$Id$
*/


/**
        \class      FormAdmin
        \brief      Class to generate html code for admin pages
*/
class FormAdmin
{
	var $db;
	var $error;


	/**
		\brief     Constructeur
		\param     DB      handler d'acc�s base de donn�e
	*/
	function FormAdmin($DB)
	{
		$this->db = $DB;
		
		return 1;
	}
  
	/**
     *    \brief      Retourne la liste d�roulante des menus disponibles (eldy_backoffice, ...)
     *    \param      selected        Menu pr�-s�lectionn�e
     *    \param      htmlname        Nom de la zone select
     *    \param      dirmenu         Rep�rtoire � scanner
     */
    function select_menu($selected='',$htmlname,$dirmenu)
    {
        global $langs,$conf;
    
        if ($selected == 'eldy.php') $selected='eldy_backoffice.php';  // Pour compatibilit�
    
		$menuarray=array();
        $handle=opendir($dirmenu);
        while (($file = readdir($handle))!==false)
        {
            if (is_file($dirmenu."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                $filelib=eregi_replace('\.php$','',$file);
				$prefix='';
				if (eregi('^eldy',$file)) $prefix='0';			// Recommanded
				else if (eregi('^auguria',$file)) $prefix='2';	// Other
				else if (eregi('^default',$file)) $prefix='2';	// Other
				else if (eregi('^rodolphe',$file)) $prefix='2';	// Other
				else if (eregi('^empty',$file)) $prefix='2';	// Other
				else $prefix='1';								// Experimental
				
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
			$tab=split('_',$key);
			$newprefix=$tab[0];
			if ($conf->browser->firefox && $newprefix != $oldprefix)
			{
				// Affiche titre
				print '<option value="-1" disabled="disabled">';
				if ($newprefix=='0') print '-- '.$langs->trans("VersionRecommanded").' --';
				if ($newprefix=='1') print '-- '.$langs->trans("VersionExperimental").' --';
				if ($newprefix=='2') print '-- '.$langs->trans("Other").' --';
				print '</option>';
				$oldprefix=$newprefix;
			}
			print $val."\n";
		}
		print '</select>';
    }

    /**
     *    \brief      Retourne la liste d�roulante des menus disponibles (eldy)
     *    \param      selected        Menu pr�-s�lectionn�e
     *    \param      htmlname        Nom de la zone select
     *    \param      dirmenu         Repertoire � scanner
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
                $filelib=eregi_replace('(_backoffice|_frontoffice)?\.php$','',$file);
				if (eregi('^default',$filelib)) continue;
				if (eregi('^empty',$filelib)) continue;

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
			$tab=split('_',$key);
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
     *    \brief      Retourne la liste d�roulante des menus disponibles (eldy)
     *    \param      selected        Menu pr�-s�lectionn�e
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
