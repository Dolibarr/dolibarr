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
	 *	Output a combo list with projects qualified for a third party / user
	 *
	 *	@param	int		$socid      	Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 *	@param  string	$selected   	Id project preselected ('' or id of project)
	 *	@param  string	$htmlname   	Name of HTML field
	 *	@param	int		$maxlength		Maximum length of label
	 *	@param	int		$option_only	Return only html options lines without the select tag
	 *	@param	int		$show_empty		Add an empty line
	 *  @param	int		$discard_closed Discard closed projects (0=Keep,1=hide completely,2=Disable)
	 *  @param	int		$forcefocus		Force focus on field (works with javascript only)
	 *  @param	int		$disabled		Disabled
	 *  @param  int     $mode           0 for HTML mode and 1 for JSON mode
	 *  @param  string  $filterkey      Key to filter
	 *  @param  int     $nooutput       No print output. Return it only.
	 *  @param  int     $forceaddid     Force to add project id in list, event if not qualified
	 *  @param  string  $morecss        More css
	 *	@param  int     $htmlid         Html id to use instead of htmlname
	 *	@return string           		Return html content
	 */
	function select_projects($socid=-1, $selected='', $htmlname='projectid', $maxlength=16, $option_only=0, $show_empty=1, $discard_closed=0, $forcefocus=0, $disabled=0, $mode = 0, $filterkey = '', $nooutput=0, $forceaddid=0, $morecss='', $htmlid='')
	{
		global $langs,$conf,$form;

		$out='';

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
			$urloption='socid='.$socid.'&htmlname='.$htmlname.'&discardclosed='.$discard_closed;
			$out.=ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/projet/ajax/projects.php', $urloption, $conf->global->PROJECT_USE_SEARCH_TO_SELECT, 0, array(
//				'update' => array(
//					'projectid' => 'id'
//				)
			));

			$out.='<input type="text" class="minwidth200'.($morecss?' '.$morecss:'').'" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'"'.$placeholder.' />';
		}
		else
		{
			$out.=$this->select_projects_list($socid, $selected, $htmlname, $maxlength, $option_only, $show_empty, $discard_closed, $forcefocus, $disabled, 0, $filterkey, 1, $forceaddid, $htmlid);
		}
		if ($discard_closed)
		{
			if (class_exists('Form'))
			{
				if (empty($form)) $form=new Form($this->db);
				$out.=$form->textwithpicto('', $langs->trans("ClosedProjectsAreHidden"));
			}
		}

		if (empty($nooutput))
		{
		    print $out;
		    return '';
		}
		else return $out;
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
	 * @param  int     $nooutput           No print output. Return it only.
	 * @param  int     $forceaddid         Force to add project id in list, event if not qualified
	 * @param  int     $htmlid             Html id to use instead of htmlname
	 * @param  string  $morecss            More CSS
	 * @return int         			       Nb of project if OK, <0 if KO
	 */
	function select_projects_list($socid=-1, $selected='', $htmlname='projectid', $maxlength=24, $option_only=0, $show_empty=1, $discard_closed=0, $forcefocus=0, $disabled=0, $mode=0, $filterkey = '', $nooutput=0, $forceaddid=0, $htmlid='', $morecss='maxwidth500')
	{
		global $user,$conf,$langs;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		if (empty($htmlid)) $htmlid = $htmlname;

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
		$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public, s.nom as name, s.name_alias';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p LEFT JOIN '.MAIN_DB_PREFIX .'societe as s ON s.rowid = p.fk_soc';
		$sql.= " WHERE p.entity IN (".getEntity('project').")";
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		if ($socid > 0)
		{
		    if (empty($conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY))  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
		    else if ($conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY != 'all')    // PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY is 'all' or a list of ids separated by coma.
		    {
		        $sql.= " AND (p.fk_soc IN (".$socid.", ".$conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY.") OR p.fk_soc IS NULL)";
		    }
		}
		if (!empty($filterkey)) $sql .= natural_search(array('p.title', 'p.ref'), $filterkey);
		$sql.= " ORDER BY p.ref ASC";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Use select2 selector
			if (! empty($conf->use_javascript_ajax))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlid, array(), 0, $forcefocus);
            	$out.=$comboenhancement;
            	$morecss='minwidth100 maxwidth500';
			}

			if (empty($option_only)) {
				$out.= '<select class="flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled="disabled"':'').' id="'.$htmlid.'" name="'.$htmlname.'">';
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
						if ($discard_closed == 1 && $obj->fk_statut == 2 && $obj->rowid != $selected) // We discard closed except if selected
						{
							$i++;
							continue;
						}

						$labeltoshow=dol_trunc($obj->ref,18);
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
						$labeltoshow.=', '.dol_trunc($obj->title, $maxlength);
						if ($obj->name)
						{
						    $labeltoshow.=' - '.$obj->name;
						    if ($obj->name_alias) $labeltoshow.=' ('.$obj->name_alias.')';
						}

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
						else if ( empty($conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY) &&  $socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
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
				if (empty($option_only)) $out.= '</select>';
				if (empty($nooutput))
				{
				    print $out;
				    return '';
				}
				else return $out;
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
	 *	@param	string	$show_empty		Add an empty line ('1' or string to show for empty line)
	 *  @param	int		$discard_closed Discard closed projects (0=Keep,1=hide completely,2=Disable)
     *  @param	int		$forcefocus		Force focus on field (works with javascript only)
     *  @param	int		$disabled		Disabled
	 *  @param	string	$morecss        More css added to the select component
	 *	@return int         			Nbr of project if OK, <0 if KO
	 */
	function selectTasks($socid=-1, $selected='', $htmlname='taskid', $maxlength=24, $option_only=0, $show_empty='1', $discard_closed=0, $forcefocus=0, $disabled=0, $morecss='maxwidth500')
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
		$sql.= " WHERE p.entity IN (".getEntity('project').")";
		$sql.= " AND t.fk_projet = p.rowid";
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
		$sql.= " ORDER BY p.ref, t.ref ASC";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Use select2 selector
			if (! empty($conf->use_javascript_ajax))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	           	$comboenhancement = ajax_combobox($htmlname, '', 0, $forcefocus);
            	$out.=$comboenhancement;
            	$morecss='minwidth200 maxwidth500';
			}

			if (empty($option_only)) {
				$out.= '<select class="valignmiddle flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled="disabled"':'').' id="'.$htmlname.'" name="'.$htmlname.'">';
			}
			if (! empty($show_empty)) {
				$out.= '<option value="0" class="optiongrey">';
				if (! is_numeric($show_empty)) $out.=$show_empty;
				else $out.='&nbsp;';
				$out.= '</option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && empty($user->rights->societe->lire))
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

						$labeltoshow=dol_trunc($obj->ref,18);     // Project ref
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
	 *    @param	string		$socid				If of thirdparty to use as filter or 'id1,id2,...'
	 *    @param	string		$morecss			More CSS
	 *    @param    int         $limitonstatus      Add filters to limit length of list to opened status (for example to avoid ERR_RESPONSE_HEADERS_TOO_BIG on project/element.php page). TODO To implement
	 *    @return	int|string						The HTML select list of element or '' if nothing or -1 if KO
	 */
	function select_element($table_element, $socid=0, $morecss='', $limitonstatus=-2)
	{
		global $conf, $langs;

		if ($table_element == 'projet_task') return '';		// Special cas of element we never link to a project (already always done)

		$linkedtothirdparty=false;
		if (! in_array($table_element, array('don','expensereport_det','expensereport','loan','stock_mouvement','chargesociales'))) $linkedtothirdparty=true;

		$sqlfilter='';
		$projectkey="fk_projet";
		//print $table_element;
		switch ($table_element)
		{
			case "loan":
				$sql = "SELECT t.rowid, t.label as ref";
				break;
			case "facture":
				$sql = "SELECT t.rowid, t.facnumber as ref";
				break;
			case "facture_fourn":
				$sql = "SELECT t.rowid, t.ref, t.ref_supplier";
				break;
			case "commande_fourn":
			case "commande_fournisseur":
			    $sql = "SELECT t.rowid, t.ref, t.ref_supplier";
				break;
			case "facture_rec":
				$sql = "SELECT t.rowid, t.titre as ref";
				break;
			case "actioncomm":
				$sql = "SELECT t.id as rowid, t.label as ref";
				$projectkey="fk_project";
				break;
			case "expensereport":
				return '';
			case "expensereport_det":
				/*$sql = "SELECT rowid, '' as ref";	// table is llx_expensereport_det
				$projectkey="fk_projet";
				break;*/
				return '';
			case "commande":
		    case "contrat":
			case "fichinter":
			    $sql = "SELECT t.rowid, t.ref";
			    break;
			case 'stock_mouvement':
				$sql = 'SELECT t.rowid, t.label as ref';
				$projectkey='fk_origin';
				break;
			case "payment_various":
				$sql = "SELECT t.rowid, t.num_payment as ref";
				break;
			case "chargesociales":
			default:
				$sql = "SELECT t.rowid, t.ref";
				break;
		}
		if ($linkedtothirdparty) $sql.=", s.nom as name";
		$sql.= " FROM ".MAIN_DB_PREFIX.$table_element." as t";
		if ($linkedtothirdparty) $sql.=", ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE ".$projectkey." is null";
		if (! empty($socid) && $linkedtothirdparty)
		{
		    if (is_numeric($socid)) $sql.= " AND t.fk_soc=".$socid;
		    else $sql.= " AND t.fk_soc IN (".$socid.")";
		}
		if (! in_array($table_element, array('expensereport_det','stock_mouvement'))) $sql.= ' AND t.entity IN ('.getEntity('project').')';
		if ($linkedtothirdparty) $sql.=" AND s.rowid = t.fk_soc";
		if ($sqlfilter) $sql.= " AND ".$sqlfilter;
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
	 *    @param   string      $preselected        Preselected (int or 'all' or 'none')
	 *    @param   int         $showempty          Add an empty line
	 *    @param   int         $useshortlabel      Use short label
	 *    @param   int         $showallnone        Add choice "All" and "None"
	 *    @param   int         $showpercent        Show default probability for status
	 *    @param   string      $morecss            Add more css
	 *    @return  int|string                      The HTML select list of element or '' if nothing or -1 if KO
	 */
	function selectOpportunityStatus($htmlname, $preselected='-1', $showempty=1, $useshortlabel=0, $showallnone=0, $showpercent=0, $morecss='')
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
				$sellist = '<select class="flat oppstatus'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'">';
				if ($showempty) $sellist.= '<option value="-1">&nbsp;</option>';    // Without &nbsp, strange move of screen when switching value
				if ($showallnone) $sellist.= '<option value="all"'.($preselected == 'all'?' selected="selected"':'').'>--'.$langs->trans("OnlyOpportunitiesShort").'--</option>';
				if ($showallnone) $sellist.= '<option value="openedopp"'.($preselected == 'openedopp'?' selected="selected"':'').'>--'.$langs->trans("OpenedOpportunitiesShort").'--</option>';
				if ($showallnone) $sellist.= '<option value="none"'.($preselected == 'none'?' selected="selected"':'').'>--'.$langs->trans("NotAnOpportunityShort").'--</option>';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$sellist .='<option value="'.$obj->rowid.'" defaultpercent="'.$obj->percent.'" elemcode="'.$obj->code.'"';
					if ($obj->rowid == $preselected) $sellist .= ' selected="selected"';
					$sellist .= '>';
					if ($useshortlabel)
					{
						$finallabel = ($langs->transnoentitiesnoconv("OppStatusShort".$obj->code) != "OppStatusShort".$obj->code ? $langs->transnoentitiesnoconv("OppStatusShort".$obj->code) : $obj->label);
					}
					else
					{
						$finallabel = ($langs->transnoentitiesnoconv("OppStatus".$obj->code) != "OppStatus".$obj->code ? $langs->transnoentitiesnoconv("OppStatus".$obj->code) : $obj->label);
						if ($showpercent) $finallabel.= ' ('.$obj->percent.'%)';
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
