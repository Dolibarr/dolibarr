<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *	\brief      Fichier de la classe des fonctions prédéfinie de composants html autre
 *	\version	$Id$
 */


/**
 *	\class      FormOther
 *	\brief      Classe permettant la génération de composants html autre
 *	\remarks	Only common components must be here.
 */
class FormOther
{
	var $db;
	var $error;


	/**
	 *	\brief     Constructeur
	 *	\param     DB      handler d'accès base de donnée
	 */
	function FormOther($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *    \brief      Retourne la liste des modèles d'export
	 *    \param      selected          Id modèle pré-sélectionné
	 *    \param      htmlname          Nom de la zone select
	 *    \param      type              Type des modèles recherchés
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
			dolibarr_print_error($this->db);
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
			dolibarr_print_error($this->db);
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
