<?php
/* Copyright (c) 2013 Florian Henry  <florian.henry@open-concept.pro>
 * Copyright (C) 2015 Marcos García  <marcosgdf@gmail.com>
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
	 *	Output a combo list with projects qualified for a third party
	 *
	 *	@param	int		$socid      	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *	@param  int		$selected   	Id project preselected
	 *	@param  string	$htmlname   	Nom de la zone html
	 *	@param	int		$maxlength		Maximum length of label
	 *	@param	int		$option_only	Return only html options lines without the select tag
	 *	@param	int		$show_empty		Add an empty line
	 *  @param	int		$discard_closed Discard closed projects (0=Keep,1=hide completely,2=Disable)
	 *  @param	int		$forcefocus		Force focus on field (works with javascript only)
	 *  @param	int		$disabled		Disabled
	 *  @param int  $mode               0 for HTML mode and 1 for JSON mode
	 * @param string $filterkey         Key to filter
	 *	@return int         			Nber of project if OK, <0 if KO
	 */
	function select_projects($socid=-1, $selected='', $htmlname='projectid', $maxlength=16, $option_only=0, $show_empty=1, $discard_closed=0, $forcefocus=0, $disabled=0, $mode = 0, $filterkey = '')
	{
		global $langs,$conf;

		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->PROJECT_USE_SEARCH_TO_SELECT))
		{
			$placeholder='';

			if ($selected && empty($selected_input_value))
			{
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$project = new Project($this->db);
				$project->fetch($selected);
				$selected_input_value=$project->ref;
			}
			$urloption='socid='.$socid.'&htmlname='.$htmlname;
			print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/projet/ajax/projects.php', $urloption, $conf->global->PROJECT_USE_SEARCH_TO_SELECT, 0, array(
//				'update' => array(
//					'projectid' => 'id'
//				)
			));

			print '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' />';
		}
		else
		{
			print $this->select_projects_list($socid, $selected, $htmlname, $maxlength, $option_only, $show_empty, $discard_closed, $forcefocus, $disabled, 0, $filterkey);
		}
	}

	/**
	 * Returns an array with projects qualified for a third party
	 *
	 * @param  int     $socid      	       Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 * @param  int     $selected   	       Id project preselected
	 * @param  string  $htmlname   	       Nom de la zone html
	 * @param  int     $maxlength          Maximum length of label
	 * @param  int     $option_only	       Return only html options lines without the select tag
	 * @param  int     $show_empty		   Add an empty line
	 * @param  int     $discard_closed     Discard closed projects (0=Keep,1=hide completely,2=Disable)
     * @param  int     $forcefocus		   Force focus on field (works with javascript only)
     * @param  int     $disabled           Disabled
	 * @param  int     $mode               0 for HTML mode and 1 for array return (to be used by json_encode for example)
	 * @param  string  $filterkey          Key to filter
	 * @return int         			       Nb of project if OK, <0 if KO
	 */
	function select_projects_list($socid=-1, $selected='', $htmlname='projectid', $maxlength=24, $option_only=0, $show_empty=1, $discard_closed=0, $forcefocus=0, $disabled=0, $mode=0, $filterkey = '')
	{
		global $user,$conf,$langs;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		$out='';
        $outarray=array();
        
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
		if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
		if (!empty($filterkey)) {
			$sql .= ' AND p.title LIKE "%'.$this->db->escape($filterkey).'%"';
			$sql .= ' OR p.ref LIKE "%'.$this->db->escape($filterkey).'%"';
		}
		$sql.= " ORDER BY p.ref ASC";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$minmax='';

			// Use select2 selector
			$nodatarole='';
			if (! empty($conf->use_javascript_ajax))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlname, '', 0, $forcefocus);
            	$out.=$comboenhancement;
            	$nodatarole=($comboenhancement?' data-role="none"':'');
            	$minmax='minwidth100 maxwidth300';
			}

			if (empty($option_only)) {
				$out.= '<select class="flat'.($minmax?' '.$minmax:'').'"'.($disabled?' disabled="disabled"':'').' id="'.$htmlname.'" name="'.$htmlname.'"'.$nodatarole.'>';
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
						if ($discard_closed == 1 && $obj->fk_statut == 2)
						{
							$i++;
							continue;
						}

						$labeltoshow=dol_trunc($obj->ref,18).' - '.$obj->title;
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
						$labeltoshow.=' '.dol_trunc($obj->title,$maxlength);

						$disabled=0;
						if ($obj->fk_statut == 0)
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("Draft");
						}
						else if ($obj->fk_statut == 2)
						{
							if ($discard_close == 2) $disabled=1;
							$labeltoshow.=' - '.$langs->trans("Closed");
						}
						else if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
						}

						if (!empty($selected) && $selected == $obj->rowid)
						{
							$out.= '<option value="'.$obj->rowid.'" selected';
							//if ($disabled) $out.=' disabled';						// with select2, field can't be preselected if disabled
							$out.= '>'.$labeltoshow.'</option>';
						}
						else
						{
							if ($hideunselectables && $disabled && ($selected != $obj->rowid))
							{
								$resultat='';
							}
							else
							{
								$resultat='<option value="'.$obj->rowid.'"';
								if ($disabled) $resultat.=' disabled';
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat.='>';
								$resultat.=$labeltoshow;
								$resultat.='</option>';
							}
							$out.= $resultat;

							$outarray[] = array(
								'key' => (int) $obj->rowid,
								'value' => $obj->ref,
								'ref' => $obj->ref,
								'label' => $labeltoshow,
								'disabled' => (bool) $disabled
							);
						}
					}
					$i++;
				}
			}

			$this->db->free($resql);

			if (!$mode) {
				if (empty($option_only)) {
					$out.= '</select>';
				}
				print $out;
			} else {
				return $outarray;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Output a combo list with projects qualified for a third party
	 *
	 *	@param	int		$socid      	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *	@param  int		$selected   	Id task preselected
	 *	@param  string	$htmlname   	Name of HTML select
	 *	@param	int		$maxlength		Maximum length of label
	 *	@param	int		$option_only	Return only html options lines without the select tag
	 *	@param	int		$show_empty		Add an empty line
	 *  @param	int		$discard_closed Discard closed projects (0=Keep,1=hide completely,2=Disable)
     *  @param	int		$forcefocus		Force focus on field (works with javascript only)
     *  @param	int		$disabled		Disabled
	 *	@return int         			Nber of project if OK, <0 if KO
	 */
	function selectTasks($socid=-1, $selected='', $htmlname='taskid', $maxlength=24, $option_only=0, $show_empty=1, $discard_closed=0, $forcefocus=0, $disabled=0)
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
		$sql = 'SELECT t.rowid, t.ref as tref, t.label as tlabel, p.ref, p.title, p.fk_soc, p.fk_statut, p.public,';
		$sql.= ' s.nom as name';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
		$sql.= ', '.MAIN_DB_PREFIX.'projet_task as t';
		$sql.= " WHERE p.entity = ".$conf->entity;
		$sql.= " AND t.fk_projet = p.rowid";
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
		$sql.= " ORDER BY p.ref, t.ref ASC";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$minmax='';

			// Use select2 selector
			$nodatarole='';
			if (! empty($conf->use_javascript_ajax))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlname, '', 0, $forcefocus);
            	$out.=$comboenhancement;
            	$nodatarole=($comboenhancement?' data-role="none"':'');
            	$minmax='minwidth200';
			}

			if (empty($option_only)) {
				$out.= '<select class="flat'.($minmax?' '.$minmax:'').'"'.($disabled?' disabled="disabled"':'').' id="'.$htmlname.'" name="'.$htmlname.'"'.$nodatarole.'>';
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
						if ($discard_closed == 1 && $obj->fk_statut == 2)
						{
							$i++;
							continue;
						}

						$labeltoshow=dol_trunc($obj->ref,18);
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
						$labeltoshow.=' '.dol_trunc($obj->title,$maxlength);

						if ($obj->name) $labeltoshow.=' ('.$obj->name.')';

						$disabled=0;
						if ($obj->fk_statut == 0)
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("Draft");
						}
						else if ($obj->fk_statut == 2)
						{
							if ($discard_closed == 2) $disabled=1;
							$labeltoshow.=' - '.$langs->trans("Closed");
						}
						else if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
						}
						// Label for task
						$labeltoshow.=' - '.$obj->tref.' '.dol_trunc($obj->tlabel,$maxlength);

						if (!empty($selected) && $selected == $obj->rowid)
						{
							$out.= '<option value="'.$obj->rowid.'" selected';
							//if ($disabled) $out.=' disabled';						// with select2, field can't be preselected if disabled
							$out.= '>'.$labeltoshow.'</option>';
						}
						else
						{
							if ($hideunselectables && $disabled && ($selected != $obj->rowid))
							{
								$resultat='';
							}
							else
							{
								$resultat='<option value="'.$obj->rowid.'"';
								if ($disabled) $resultat.=' disabled';
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat.='>';
								$resultat.=$labeltoshow;
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
	 *    Build a HTML select list of element of same thirdparty to suggest to link them to project
	 *
	 *    @param	string		$table_element		Table of the element to update
	 *    @param	int			$socid				If of thirdparty to use as filter
	 *    @param	string		$morecss			More CSS
	 *    @return	int|string						The HTML select list of element or '' if nothing or -1 if KO
	 */
	function select_element($table_element, $socid=0, $morecss='')
	{
		global $conf, $langs;

		if ($table_element == 'projet_task') return '';		// Special cas of element we never link to a project (already always done)

		$linkedtothirdparty=false;
		if (! in_array($table_element, array('don','expensereport_det','expensereport'))) $linkedtothirdparty=true;

		$projectkey="fk_projet";
		switch ($table_element)
		{
			case "facture":
				$sql = "SELECT t.rowid, t.facnumber as ref";
				break;
			case "facture_fourn":
				$sql = "SELECT t.rowid, t.ref, t.ref_supplier";
				break;
			case "commande_fourn":
				$sql = "SELECT t.rowid, t.ref, t.ref_supplier";
				break;
			case "facture_rec":
				$sql = "SELECT t.rowid, t.titre as ref";
				break;
			case "actioncomm":
				$sql = "SELECT t.id as rowid, t.label as ref";
				$projectkey="fk_project";
				break;
			case "expensereport_det":
				return '';
				/*$sql = "SELECT rowid, '' as ref";	// table is llx_expensereport_det
				$projectkey="fk_projet";
				break;*/
			default:
				$sql = "SELECT t.rowid, t.ref";
				break;
		}
		if ($linkedtothirdparty) $sql.=", s.nom as name";
		$sql.= " FROM ".MAIN_DB_PREFIX.$table_element." as t";
		if ($linkedtothirdparty) $sql.=", ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE ".$projectkey." is null";
		if (! empty($socid) && $linkedtothirdparty) $sql.= " AND t.fk_soc=".$socid;
		if (! in_array($table_element, array('expensereport_det'))) $sql.= ' AND t.entity='.getEntity('project');
		if ($linkedtothirdparty) $sql.=" AND s.rowid = t.fk_soc";
		$sql.= " ORDER BY ref DESC";

		dol_syslog(get_class($this).'::select_element', LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
				$sellist = '<select class="flat elementselect css'.$table_element.($morecss?' '.$morecss:'').'" name="elementselect">';
				$sellist .='<option value="-1"></option>';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$ref=$obj->ref?$obj->ref:$obj->rowid;
					if (! empty($obj->ref_supplier)) $ref.=' ('.$obj->ref_supplier.')';
					if (! empty($obj->name)) $ref.=' - '.$obj->name;
					$sellist .='<option value="'.$obj->rowid.'">'.$ref.'</option>';
					$i++;
				}
				$sellist .='</select>';
			}
			/*else
			{
				$sellist = '<select class="flat" name="elementselect">';
				$sellist.= '<option value="0" disabled>'.$langs->trans("None").'</option>';
				$sellist.= '</select>';
			}*/
			$this->db->free($resql);

			return $sellist;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->lasterror();
			$this->errors[]=$this->db->lasterror();
			dol_syslog(get_class($this) . "::select_element " . $this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    Build a HTML select list of element of same thirdparty to suggest to link them to project
	 *
	 *    @param   string      $htmlname           HTML name
	 *    @param   int         $preselected        Preselected
	 *    @param   int         $showempty          Add an empty line
	 *    @param   int         $useshortlabel      Use short label
	 *    @param   int         $showallnone        Add choice "All" and "None"
	 *    @return  int|string                      The HTML select list of element or '' if nothing or -1 if KO
	 */
	function selectOpportunityStatus($htmlname, $preselected=0, $showempty=1, $useshortlabel=0, $showallnone=0)
	{
		global $conf, $langs;

		$sql = "SELECT rowid, code, label, percent";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_lead_status';
		$sql.= " WHERE active = 1";
		$sql.= " ORDER BY position";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
				$sellist = '<select class="flat oppstatus" name="'.$htmlname.'">';
				if ($showempty) $sellist.= '<option value="-1"></option>';
				if ($showallnone) $sellist.= '<option value="all">--'.$langs->trans("Alls").'--</option>';
				if ($showallnone) $sellist.= '<option value="none">--'.$langs->trans("None").'--</option>';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$sellist .='<option value="'.$obj->rowid.'"';
					if ($obj->rowid == $preselected) $sellist .= ' selected="selected"';
					$sellist .= '>';
					if ($useshortlabel)
					{
						$finallabel = ($langs->transnoentitiesnoconv("OppStatusShort".$obj->code) != "OppStatusShort".$obj->code ? $langs->transnoentitiesnoconv("OppStatusShort".$obj->code) : $obj->label);
					}
					else
					{
						$finallabel = ($langs->transnoentitiesnoconv("OppStatus".$obj->code) != "OppStatus".$obj->code ? $langs->transnoentitiesnoconv("OppStatus".$obj->code) : $obj->label);
						$finallabel.= ' ('.$obj->percent.'%)';
					}
					$sellist .= $finallabel;
					$sellist .='</option>';
					$i++;
				}
				$sellist .='</select>';
			}
			/*else
			{
				$sellist = '<select class="flat" name="elementselect">';
				$sellist.= '<option value="0" disabled>'.$langs->trans("None").'</option>';
				$sellist.= '</select>';
			}*/
			$this->db->free($resql);

			return $sellist;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->errors[]=$this->db->lasterror();
			dol_syslog(get_class($this) . "::selectOpportunityStatus " . $this->error, LOG_ERR);
			return -1;
		}
	}

}
