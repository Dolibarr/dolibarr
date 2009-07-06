<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/html.formother.class.php
 *	\brief      Fichier de la classe des fonctions predefinie de composants html autre
 *	\version	$Id$
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
	 *	\brief     Constructeur
	 *	\param     DB      handler d'acces base de donnee
	 */
	function FormOther($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *    \brief      Retourne la liste des modeles d'export
	 *    \param      selected          Id modele pre-selectionne
	 *    \param      htmlname          Nom de la zone select
	 *    \param      type              Type des modeles recherches
	 *    \param      useempty          Affiche valeur vide dans liste
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
					print '<option value="'.$obj->rowid.'" selected="true">';
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
	 *    \brief     Retourne la liste des ecotaxes avec tooltip sur le libelle
	 *    \param     selected    code ecotaxes pre-selectionne
	 *    \param     htmlname    nom de la liste deroulante
	 */
	function select_ecotaxes($selected='',$htmlname='ecotaxe_id')
	{
		global $langs;

		$sql = "SELECT e.rowid, e.code, e.libelle, e.price, e.organization,";
		$sql.= " p.libelle as pays";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_ecotaxe as e,".MAIN_DB_PREFIX."c_pays as p";
		$sql.= " WHERE e.active = 1 AND e.fk_pays = p.rowid";
		$sql.= " ORDER BY pays, e.organization ASC, e.code ASC";

		if ($this->db->query($sql))
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows();
			$i = 0;
			print '<option value="-1">&nbsp;</option>'."\n";
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if ($selected && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="true">';
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
	 *	\brief     	Retourn list of project and tasks
	 *	\param     	selected    	Pre-selected value
	 * 	\param     	htmlname    	Name of html select
	 * 	\param		modeproject		1 to restrict on projects owned by user
	 * 	\param		modetask		1 to restrict on tasks associated to user
	 * 	\param		mode			0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 */
	function selectProjectTasks($selected='',$htmlname='task_parent', $modeproject=0, $modetask=0, $mode)
	{
		global $user, $langs;

		require_once(DOL_DOCUMENT_ROOT."/project.class.php");

		//print $modeproject.'-'.$modetask;
		$project=new Project($this->db);
		$tasksarray=$project->getTasksArray($modetask?$user:0, $modeproject?$user:0, $mode);
		if ($tasksarray)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			print '<option value="0" selected="true">&nbsp;</option>';
			$j=0;
			$level=0;
			PLineSelect($j, 0, $tasksarray, $level);
			print '</select>';
		}
		else
		{
			print '<div class="warning">'.$langs->trans("NoProject").'</div>';
		}
	}


	/**
	 *		Affiche zone de selection de couleur
	 *		@param	set_color		Couleur de pre-selection
	 *		@param	prefix			Prefix pour nom champ
	 *		@param	form_name		Nom du formulaire de provenance.
	 */
	function select_color($set_color='', $prefix='f_color', $form_name='objForm')
	{
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
		print '<img src="'.DOL_URL_ROOT.'/theme/common/colorpicker.png" width="21" height="20" border="0" onClick="fctShow(document.'.$form_name.'.'.$prefix.');" style="cursor:pointer;">'."\n";
		print '</td></tr></table>';
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
		// On cree la couleur et on l'attribue � une variable pour ne pas la perdre
		ImagePng($image,$file); //renvoie une image sous format png
		ImageDestroy($image);
	}

}


/**
 * Write all lines of a project (if parent = 0)
 *
 * @param unknown_type $inc
 * @param unknown_type $parent
 * @param unknown_type $lines
 * @param unknown_type $level
 */
function PLineSelect(&$inc, $parent, $lines, $level=0)
{
	global $langs, $user, $conf;

	$lastprojectid=0;

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;

			// Break on a new project
			if ($parent == 0)
			{
				if ($lines[$i]->projectid != $lastprojectid)
				{
					if ($i > 0 && $conf->browser->firefox) print '<option value="0" disabled="true">----------</option>';
					print '<option value="'.$lines[$i]->projectid.'_0">';	// Project -> Task
					print $langs->trans("Project").' '.$lines[$i]->projectref;
					if ($lines[$i]->name || $lines[$i]->fistname)
					{
						if ($user->admin) print ' ('.$langs->trans("Owner").': '.$lines[$i]->name.($lines[$i]->name && $lines[$i]->firstname?' ':'').$lines[$i]->firstname.')';
					}
					else
					{
						print ' ('.$langs->trans("SharedProject").')';
					}
					//print '-'.$parent.'-'.$lines[$i]->projectid.'-'.$lastprojectid;
					print "</option>\n";

					$lastprojectid=$lines[$i]->projectid;
					$inc++;
				}
			}

			// Print task
			if ($lines[$i]->id > 0)
			{
				print '<option value="'.$lines[$i]->projectid.'_'.$lines[$i]->id.'">';
				print $langs->trans("Project").' '.$lines[$i]->projectref;
				if ($lines[$i]->name || $lines[$i]->fistname)
				{
					if ($user->admin) print ' ('.$langs->trans("Owner").': '.$lines[$i]->name.($lines[$i]->name && $lines[$i]->firstname?' ':'').$lines[$i]->firstname.')';
				}
				else
				{
					print ' ('.$langs->trans("SharedProject").')';
				}
				if ($lines[$i]->id) print ' > ';
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp;&nbsp;&nbsp;";
				}
				print $lines[$i]->title."</option>\n";
				$inc++;
			}

			$level++;
			if ($lines[$i]->id) PLineSelect($inc, $lines[$i]->id, $lines, $level);
			$level--;
		}
	}
}

?>
