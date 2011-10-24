<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo  <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/html.formother.class.php
 *  \ingroup    core
 *	\brief      Fichier de la classe des fonctions predefinie de composants html autre
 */


/**
 *	\class      FormOther
 *	\brief      Classe permettant la generation de composants html autre
 *	\remarks	Only common components must be here.
 */
class FormOther
{
	var $db;
	var $error;


	/**
	 *	Constructeur
	 *	@param     DB      Database handler
	 */
	function FormOther($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
     *    Return HTML select list of export models
	 *    @param      selected          Id modele pre-selectionne
	 *    @param      htmlname          Nom de la zone select
	 *    @param      type              Type des modeles recherches
	 *    @param      useempty          Affiche valeur vide dans liste
	 */
	function select_export_model($selected='',$htmlname='exportmodelid',$type='',$useempty=0)
	{
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."export_model";
		$sql.= " WHERE type = '".$type."'";
		$sql.= " ORDER BY rowid";
		$result = $this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($useempty)
			{
				print '<option value="-1">&nbsp;</option>';
			}

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($selected == $obj->rowid)
				{
					print '<option value="'.$obj->rowid.'" selected="selected">';
				}
				else
				{
					print '<option value="'.$obj->rowid.'">';
				}
				print $obj->label;
				print '</option>';
				$i++;
			}
			print "</select>";
		}
		else {
			dol_print_error($this->db);
		}
	}


	/**
     *    Return list of export models
	 *    @param      selected          Id modele pre-selectionne
	 *    @param      htmlname          Nom de la zone select
	 *    @param      type              Type des modeles recherches
	 *    @param      useempty          Affiche valeur vide dans liste
	 */
	function select_import_model($selected='',$htmlname='importmodelid',$type='',$useempty=0)
	{
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."import_model";
		$sql.= " WHERE type = '".$type."'";
		$sql.= " ORDER BY rowid";
		$result = $this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($useempty)
			{
				print '<option value="-1">&nbsp;</option>';
			}

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($selected == $obj->rowid)
				{
					print '<option value="'.$obj->rowid.'" selected="selected">';
				}
				else
				{
					print '<option value="'.$obj->rowid.'">';
				}
				print $obj->label;
				print '</option>';
				$i++;
			}
			print "</select>";
		}
		else {
			dol_print_error($this->db);
		}
	}


	/**
	 *    Retourne la liste des ecotaxes avec tooltip sur le libelle
	 *    @param     selected    code ecotaxes pre-selectionne
	 *    @param     htmlname    nom de la liste deroulante
	 */
	function select_ecotaxes($selected='',$htmlname='ecotaxe_id')
	{
		global $langs;

		$sql = "SELECT e.rowid, e.code, e.libelle, e.price, e.organization,";
		$sql.= " p.libelle as pays";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_ecotaxe as e,".MAIN_DB_PREFIX."c_pays as p";
		$sql.= " WHERE e.active = 1 AND e.fk_pays = p.rowid";
		$sql.= " ORDER BY pays, e.organization ASC, e.code ASC";

        $resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			print '<option value="-1">&nbsp;</option>'."\n";
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($selected && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="selected">';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">';
						//print '<option onmouseover="showtip(\''.$obj->libelle.'\')" onMouseout="hidetip()" value="'.$obj->rowid.'">';
					}
					$selectOptionValue = $obj->code.' : '.price($obj->price).' '.$langs->trans("HT").' ('.$obj->organization.')';
					print $selectOptionValue;
					print '</option>';
					$i++;
				}
			}
			print '</select>';
			return 0;
		}
		else
		{
			dol_print_error($this->db);
			return 1;
		}
	}

	/**
	 *    Return a HTML select list to select a percent
	 *    @param     selected      pourcentage pre-selectionne
	 *    @param     htmlname      nom de la liste deroulante
	 *    @param     increment     increment value
	 *    @param     start         start value
	 *    @param     end           end value
	 *    @return    return        combo
	 */
	function select_percent($selected=0,$htmlname='percent',$disabled=0,$increment=5,$start=0,$end=100)
	{
		$return = '<select class="flat" name="'.$htmlname.'" '.($disabled?'disabled="disabled"':'').'>';

		for ($i = $start ; $i <= $end ; $i += $increment)
		{
			if ($selected == $i)
			{
				$return.= '<option value="'.$i.'" selected="selected">';
			}
			else
			{
				$return.= '<option value="'.$i.'">';
			}
			$return.= $i.' % ';
			$return.= '</option>';
		}

		$return.= '</select>';

		return $return;
	}

	/**
	 *  Return select list for categories (to use in form search selectors)
	 *	@param    	type			Type of categories (0=product, 1=suppliers, 2=customers, 3=members)
	 *  @param     	selected     	Preselected value
	 *  @param     	htmlname      	Name of combo list
	 *  @return    	return        	Html combo list code
	 */
	function select_categories($type,$selected=0,$htmlname='search_categ')
	{
		global $langs;
	 	require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

	 	// Load list of "categories"
	 	$static_categs = new Categorie($this->db);
	 	$tab_categs = $static_categs->get_full_arbo($type);

	 	// Print a select with each of them
	 	$moreforfilter ='<select class="flat" name="'.$htmlname.'">';
	 	$moreforfilter.='<option value="">&nbsp;</option>';

	 	if (is_array($tab_categs))
	 	{
	 		foreach ($tab_categs as $categ)
	 		{
	 			$moreforfilter.='<option value="'.$categ['id'].'"';
	 			if ($categ['id'] == $selected) $moreforfilter.=' selected="selected"';
	 			$moreforfilter.='>'.dol_trunc($categ['fulllabel'],50,'middle').'</option>';
	 		}
	 	}
	 	$moreforfilter.='</select>';

		return $moreforfilter;
	}


	/**
	 *  Return select list for categories (to use in form search selectors)
	 *  @param     	selected     	Preselected value
	 *  @param     	htmlname      	Name of combo list
	 *  @param      user            Object user
	 *  @return    	return        	Html combo list code
	 */
	function select_salesrepresentatives($selected=0,$htmlname='search_sale',$user)
	{
		global $conf;

	 	// Select each sales and print them in a select input
 		$moreforfilter ='<select class="flat" name="'.$htmlname.'">';
 		$moreforfilter.='<option value="">&nbsp;</option>';

 		// Get list of users allowed to be viewed
 		$sql_usr = "SELECT u.rowid, u.name as name, u.firstname, u.login";
 		$sql_usr.= " FROM ".MAIN_DB_PREFIX."user as u";
 		$sql_usr.= " WHERE u.entity IN (0,".$conf->entity.")";
 		if (empty($user->rights->user->user->lire)) $sql_usr.=" AND u.fk_societe = ".($user->societe_id?$user->societe_id:0);
        // Add existing sales representatives of company
 		if (empty($user->rights->user->user->lire) && $user->societe_id)
 		{
            $sql_usr.=" UNION ";
            $sql_usr.= "SELECT u2.rowid, u2.name as name, u2.firstname, u2.login";
            $sql_usr.= " FROM ".MAIN_DB_PREFIX."user as u2, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql_usr.= " WHERE u2.entity IN (0,".$conf->entity.")";
            $sql_usr.= " AND u2.rowid = sc.fk_user AND sc.fk_soc=".$user->societe_id;
 		}
        $sql_usr.= " ORDER BY name ASC";
        //print $sql_usr;exit;

        $resql_usr = $this->db->query($sql_usr);
 		if ($resql_usr)
 		{
 			while ($obj_usr = $this->db->fetch_object($resql_usr))
 			{
 				$moreforfilter.='<option value="'.$obj_usr->rowid.'"';

 				if ($obj_usr->rowid == $selected) $moreforfilter.=' selected="selected"';

 				$moreforfilter.='>';
 				$moreforfilter.=$obj_usr->firstname." ".$obj_usr->name." (".$obj_usr->login.')';
 				$moreforfilter.='</option>';
 				$i++;
 			}
 			$this->db->free($resql_usr);
 		}
 		else
 		{
 			dol_print_error($this->db);
 		}
 		$moreforfilter.='</select>';

 		return $moreforfilter;
	}

	/**
	 *	Return list of project and tasks
	 *	@param     	selectedtask   	Pre-selected task
	 *  @param      projectid       Project id
	 * 	@param     	htmlname    	Name of html select
	 * 	@param		modeproject		1 to restrict on projects owned by user
	 * 	@param		modetask		1 to restrict on tasks associated to user
	 * 	@param		mode			0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 *  @param      useempty        0=Allow empty values
	 */
	function selectProjectTasks($selectedtask='', $projectid=0, $htmlname='task_parent', $modeproject=0, $modetask=0, $mode=0, $useempty=0)
	{
		global $user, $langs;

		require_once(DOL_DOCUMENT_ROOT."/projet/class/task.class.php");

		//print $modeproject.'-'.$modetask;
		$task=new Task($this->db);
		$tasksarray=$task->getTasksArray($modetask?$user:0, $modeproject?$user:0, $projectid, 0, $mode);
		if ($tasksarray)
		{
		    print '<select class="flat" name="'.$htmlname.'">';
			if ($useempty) print '<option value="0">&nbsp;</option>';
			$j=0;
			$level=0;
			PLineSelect($j, 0, $tasksarray, $level, $selectedtask, $projectid);
			print '</select>';
		}
		else
		{
			print '<div class="warning">'.$langs->trans("NoProject").'</div>';
		}
	}


	/**
	 *		Output a HTML code to select a color
	 *		@param	set_color		Pre-selected color
	 *		@param	prefix			Name of HTML field
	 *		@param	form_name		Name of form
	 * 		@param	showcolorbox	1=Show color code and color box, 0=Show only color code
	 * 		@param 	arrayofcolors	Array of colors. Example: array('29527A','5229A3','A32929','7A367A','B1365F','0D7813')
	 */
	function select_color($set_color='', $prefix='f_color', $form_name='objForm', $showcolorbox=1, $arrayofcolors='')
	{
	    global $langs;
		if (! is_array($arrayofcolors) || count($arrayofcolors) < 1)
		{
			$langs->load("other");
		    print '<link rel="stylesheet" media="screen" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css" />';
            print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/jpicker-1.1.6.js"></script>';
            print '<script type="text/javascript">
             jQuery(document).ready(function(){
                $(\'#colorpicker'.$prefix.'\').jPicker( {
                window: {
                  title: \''.dol_escape_js($langs->trans("SelectAColor")).'\', /* any title for the jPicker window itself - displays "Drag Markers To Pick A Color" if left null */
                  effects:
                    {
                    type: \'show\', /* effect used to show/hide an expandable picker. Acceptable values "slide", "show", "fade" */
                    speed:
                    {
                      show: \'fast\', /* duration of "show" effect. Acceptable values are "fast", "slow", or time in ms */
                      hide: \'fast\' /* duration of "hide" effect. Acceptable values are "fast", "slow", or time in ms */
                    }
                    },
                  position:
                    {
                    x: \'screenCenter\', /* acceptable values "left", "center", "right", "screenCenter", or relative px value */
                    y: \'center\' /* acceptable values "top", "bottom", "center", or relative px value */
                    },
                },
                images: {
                    clientPath: \''.DOL_URL_ROOT.'/includes/jquery/plugins/jpicker/images/\',
                    picker: { file: \'../../../../../theme/common/colorpicker.png\', width: 14, height: 14 }
          		},
                localization: // alter these to change the text presented by the picker (e.g. different language)
                  {
                    text:
                    {
                      title: \''.dol_escape_js($langs->trans("SelectAColor")).'\',
                      newColor: \''.dol_escape_js($langs->trans("New")).'\',
                      currentColor: \''.dol_escape_js($langs->trans("Current")).'\',
                      ok: \''.dol_escape_js($langs->trans("Change")).'\',
                      cancel: \''.dol_escape_js($langs->trans("Cancel")).'\'
                    }
                  }
		        } ); });
             </script>';
            print '<input id="colorpicker'.$prefix.'" name="'.$prefix.'" size="6" maxlength="7" class="flat" type="text" value="'.$set_color.'" />';

            /*
			// No list of colors forced, we can suggest any color
			print "\n".'<table class="nobordernopadding"><tr><td valign="middle">';
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_colorpicker.js"></script>'."\n";
			print '<script type="text/javascript">
		           window.onload = function()
		           {
		             fctLoad();
		           }
		           window.onscroll = function()
		           {
		             fctShow();
		           }
		           window.onresize = function()
		           {
		             fctShow();
		           }
		         </script>'."\n";
			print '<input type="text" size="10" name="'.$prefix.'" value="'.$set_color.'" maxlength="7" class="flat">'."\n";
			print '</td><td valign="middle">';
			print '<img src="'.DOL_URL_ROOT.'/theme/common/colorpicker.png" width="21" height="18" border="0" onClick="fctShow(document.'.$form_name.'.'.$prefix.');" style="cursor:pointer;">'."\n";
			print '</td>';

			if ($showcolorbox)
			{
				print '<td style="padding-left: 4px" nowrap="nowrap">';
				print '<!-- Box color '.$set_color.' -->';
				print '<table style="border-collapse: collapse; margin:0px; padding: 0px; border: 1px solid #888888; background: #'.(preg_replace('/#/','',$set_color)).';" width="12" height="10">';
				print '<tr class="nocellnopadd"><td></td></tr>';
				print '</table>';
				print '</td>';
			}

			print '</tr></table>';
			*/
		}
		else  // In most cases, this is not used. We used instead function with no specific list of colors
		{
            print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.css" type="text/css" media="screen" />';
            print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/colorpicker/jquery.colorpicker.js" type="text/javascript"></script>';
		    print '<script type="text/javascript">
             jQuery(document).ready(function(){
                 jQuery(\'#colorpicker'.$prefix.'\').colorpicker({
                     size: 14,
                     label: \'\',
                     hide: true
                 });
             });
             </script>';

			print '<select id="colorpicker'.$prefix.'" class="flat" name="'.$prefix.'">';
			//print '<option value="-1">&nbsp;</option>';
			foreach ($arrayofcolors as $val)
			{
				print '<option value="'.$val.'"';
				if ($set_color == $val) print ' selected="selected"';
				print '>'.$val.'</option>';
			}
			print '</select>';
		}
	}

	/**
	 *		Creation d'un icone de couleur
	 *		@param	color		Couleur de l'image
	 *		@param	module  Nom du module
	 *		@param	name	  Nom de l'image
	 *		@param	x       Largeur de l'image en pixels
	 *		@param	y       Hauteur de l'image en pixels
	 */
	function CreateColorIcon($color,$module,$name,$x='12',$y='12')
	{
		global $conf;

		$file = $conf->$module->dir_temp.'/'.$name.'.png';

		// On cree le repertoire contenant les icones
		if (! file_exists($conf->$module->dir_temp))
		{
			create_exdir($conf->$module->dir_temp);
		}

		// On cree l'image en vraies couleurs
		$image = imagecreatetruecolor($x,$y);

		$color = substr($color,1,6);

		$rouge = hexdec(substr($color,0,2)); //conversion du canal rouge
		$vert  = hexdec(substr($color,2,2)); //conversion du canal vert
		$bleu  = hexdec(substr($color,4,2)); //conversion du canal bleu

		$couleur = imagecolorallocate($image,$rouge,$vert,$bleu);
		//print $rouge.$vert.$bleu;
		imagefill($image,0,0,$couleur); //on remplit l'image
		// On cree la couleur et on l'attribue a une variable pour ne pas la perdre
		ImagePng($image,$file); //renvoie une image sous format png
		ImageDestroy($image);
	}

    /**
     *    	Return HTML combo list of week
     *    	@param      selected          Preselected value
     *    	@param      htmlname          Nom de la zone select
     *    	@param      useempty          Affiche valeur vide dans liste
     */
    function select_dayofweek($selected='',$htmlname='weekid',$useempty=0)
    {
        global $langs;

        $week = array(	0=>$langs->trans("Day0"),
        1=>$langs->trans("Day1"),
        2=>$langs->trans("Day2"),
        3=>$langs->trans("Day3"),
        4=>$langs->trans("Day4"),
        5=>$langs->trans("Day5"),
        6=>$langs->trans("Day6"));

        $select_week = '<select class="flat" name="'.$htmlname.'">';
        if ($useempty)
        {
            $select_week .= '<option value="-1">&nbsp;</option>';
        }
        foreach ($week as $key => $val)
        {
            if ($selected == $key)
            {
                $select_week .= '<option value="'.$key.'" selected="selected">';
            }
            else
            {
                $select_week .= '<option value="'.$key.'">';
            }
            $select_week .= $val;
        }
        $select_week .= '</select>';
        return $select_week;
    }

    /**
     *    	Return HTML combo list of month
     *    	@param      selected          Preselected value
     *    	@param      htmlname          Nom de la zone select
     *    	@param      useempty          Affiche valeur vide dans liste
     */
    function select_month($selected='',$htmlname='monthid',$useempty=0)
    {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

        $month = monthArrayOrSelected(-1);	// Get array

        $select_month = '<select class="flat" name="'.$htmlname.'">';
        if ($useempty)
        {
            $select_month .= '<option value="0">&nbsp;</option>';
        }
        foreach ($month as $key => $val)
        {
            if ($selected == $key)
            {
                $select_month .= '<option value="'.$key.'" selected="selected">';
            }
            else
            {
                $select_month .= '<option value="'.$key.'">';
            }
            $select_month .= $val;
        }
        $select_month .= '</select>';
        return $select_month;
    }

    /**
     *    	Return HTML combo list of years
     *      @param      selected          Preselected value (''=current year, -1=none, year otherwise)
     *    	@param      htmlname          Name of HTML select object
     *    	@param      useempty          Affiche valeur vide dans liste
     *    	@param      $min_year         Offset of minimum year into list (by default current year -10)
     *    	@param      $max_year         Offset of maximum year into list (by default current year + 5)
     */
	function select_year($selected='',$htmlname='yearid',$useempty=0, $min_year=10, $max_year=5, $offset=0, $invert=0, $option='')
    {
    	print $this->selectyear($selected,$htmlname,$useempty,$min_year,$max_year,$offset,$invert,$option);
    }

    /**
     *    	Return HTML combo list of years
     *      @param      selected          Preselected value (''=current year, -1=none, year otherwise)
     *    	@param      htmlname          Name of HTML select object
     *    	@param      useempty          Affiche valeur vide dans liste
     *    	@param      $min_year         Offset of minimum year into list (by default current year -10)
     *    	@param      $max_year         Offset of maximum year into list (by default current year + 5)
     */
	function selectyear($selected='',$htmlname='yearid',$useempty=0, $min_year=10, $max_year=5, $offset=0, $invert=0, $option='')
    {
    	$out='';

        $currentyear = date("Y")+$offset;
    	$max_year = $currentyear+$max_year;
        $min_year = $currentyear-$min_year;
        if(empty($selected)) $selected = $currentyear;

        $out.= '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '"'.$option.' >';
        if($useempty)
        {
            if ($selected == '') $selected_html = ' selected="selected"';
            $out.= '<option value=""' . $selected_html . '>&nbsp;</option>';
        }
        if (! $invert)
        {
	        for ($y = $max_year; $y >= $min_year; $y--)
	        {
	            $selected_html='';
	            if ($selected > 0 && $y == $selected) $selected_html = ' selected="selected"';
	            $out.= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
	        }
        }
        else
        {
        	for ($y = $min_year; $y <= $max_year; $y++)
	        {
	            $selected_html='';
	            if ($selected > 0 && $y == $selected) $selected_html = ' selected="selected"';
	            $out.= '<option value="'.$y.'"'.$selected_html.' >'.$y.'</option>';
	        }
        }
        $out.= "</select>\n";

        return $out;
    }

}


/**
 * Write all lines of a project (if parent = 0)
 * @param 	$inc
 * @param 	$parent
 * @param 	$lines
 * @param 	$level
 * @param 	$selectedtask
 * @param 	$selectedproject
 */
function PLineSelect(&$inc, $parent, $lines, $level=0, $selectedtask=0, $selectedproject=0)
{
	global $langs, $user, $conf;

	$lastprojectid=0;

	$numlines=count($lines);
	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;

			// Break on a new project
			if ($parent == 0)
			{
				if ($lines[$i]->fk_project != $lastprojectid)
				{
					if ($i > 0 && $conf->browser->firefox) print '<option value="0" disabled="disabled">----------</option>';
					print '<option value="'.$lines[$i]->fk_project.'_0"';
					if ($selectedproject == $lines[$i]->fk_project) print ' selected="selected"';
					print '>';	// Project -> Task
					print $langs->trans("Project").' '.$lines[$i]->projectref;
					if (empty($lines[$i]->public))
					{
						print ' ('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')';
					}
					else
					{
						print ' ('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')';
					}
					//print '-'.$parent.'-'.$lines[$i]->fk_project.'-'.$lastprojectid;
					print "</option>\n";

					$lastprojectid=$lines[$i]->fk_project;
					$inc++;
				}
			}

			// Print task
			if ($lines[$i]->id > 0)
			{
				print '<option value="'.$lines[$i]->fk_project.'_'.$lines[$i]->id.'"';
				if ($lines[$i]->id == $selectedtask) print ' selected="selected"';
				print '>';
				print $langs->trans("Project").' '.$lines[$i]->projectref;
				if (empty($lines[$i]->public))
				{
					print ' ('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')';
				}
				else
				{
					print ' ('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')';
				}
				if ($lines[$i]->id) print ' > ';
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp;&nbsp;&nbsp;";
				}
				print $lines[$i]->label."</option>\n";
				$inc++;
			}

			$level++;
			if ($lines[$i]->id) PLineSelect($inc, $lines[$i]->id, $lines, $level, $selectedtask, $selectedproject);
			$level--;
		}
	}
}

?>
