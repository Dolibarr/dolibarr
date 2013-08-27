<?php
/* Copyright (c) 2013 Florian Henry  <florian.henry@open-concept.pro>
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
 *      \file       htdocs/core/class/html.formprojet.class.php
 *      \ingroup    core
 *      \brief      Class file for html component project
 */


/**
 *      Class to manage building of HTML components
 */
class FormProjets
{
	var $db;
	var $error;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 *	Show a combo list with projects qualified for a third party
	 *
	 *	@param	int		$socid      	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *	@param  int		$selected   	Id project preselected
	 *	@param  string	$htmlname   	Nom de la zone html
	 *	@param	int		$maxlength		Maximum length of label
	 *	@param	int		$option_only	Option only
	 *	@param	int		$show_empty		Add an empty line
	 *	@return int         			Nber of project if OK, <0 if KO
	 */
	function select_projects($socid=-1, $selected='', $htmlname='projectid', $maxlength=16, $option_only=0, $show_empty=1)
	{
		global $user,$conf,$langs;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		$out='';

		$hideunselectables = false;
		if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;

		$projectsListId = false;
		if (empty($user->rights->projet->all->lire))
		{
			$projectstatic=new Project($this->db);
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		}

		// Search all projects
		$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		$sql.= " ORDER BY p.title ASC";

		dol_syslog(get_class($this)."::select_projects sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if (empty($option_only)) {
				$out.= '<select class="flat" name="'.$htmlname.'">';
			}
			if (!empty($show_empty)) {
				$out.= '<option value="0">&nbsp;</option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
					{
						// Do nothing
					}
					else
					{
						$labeltoshow=dol_trunc($obj->ref,18);
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
						if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0)
						{
							$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.' - '.dol_trunc($obj->title,$maxlength).'</option>';
						}
						else
						{
							$disabled=0;
							if (! $obj->fk_statut > 0)
							{
								$disabled=1;
								$labeltoshow.=' - '.$langs->trans("Draft");
							}
							if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
							{
								$disabled=1;
								$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
							}

							if ($hideunselectables && $disabled)
							{
								$resultat='';
							}
							else
							{
								$resultat='<option value="'.$obj->rowid.'"';
								if ($disabled) $resultat.=' disabled="disabled"';
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat.='>'.$labeltoshow;
								if (! $disabled) $resultat.=' - '.dol_trunc($obj->title,$maxlength);
								$resultat.='</option>';
							}
							$out.= $resultat;
						}
					}
					$i++;
				}
			}
			if (empty($option_only)) {
				$out.= '</select>';
			}
			print $out;

			$this->db->free($resql);
			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    Build Select List of element associable to a project
	 *
	 *    @param	string	$table_element		Table of the element to update
	 *    @return	string						The HTML select list of element
	 */
	function select_element($table_element)
	{

		$projectkey="fk_projet";
		switch ($table_element)
		{
			case "facture":
				$sql = "SELECT rowid, facnumber as ref";
				break;
			case "facture_fourn":
				$sql = "SELECT rowid, ref";
				break;
			case "facture_rec":
				$sql = "SELECT rowid, titre as ref";
				break;
			case "actioncomm":
				$sql = "SELECT id as rowid, label as ref";
				$projectkey="fk_project";
				break;
			default:
				$sql = "SELECT rowid, ref";
				break;
		}

		$sql.= " FROM ".MAIN_DB_PREFIX.$table_element;
		$sql.= " WHERE ".$projectkey." is null";
		if (!empty($this->societe->id)) {
			$sql.= " AND fk_soc=".$this->societe->id;
		}
		$sql.= " ORDER BY ref DESC";

		dol_syslog(get_class($this).'::select_element sql='.$sql,LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
				$sellist = '<select class="flat" name="elementselect">';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$sellist .='<option value="'.$obj->rowid.'">'.$obj->ref.'</option>';
					$i++;
				}
				$sellist .='</select>';
			}
			return $sellist ;

			$this->db->free($resql);
		}
	}


}