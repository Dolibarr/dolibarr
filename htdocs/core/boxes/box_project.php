<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
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
 *  \file       htdocs/core/boxes/box_activite.php
 *  \ingroup    projet
 *  \brief      Module to show Projet activity of the current Year
 */
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

/**
 * Class to manage the box to show last projet
 */
class box_project extends ModeleBoxes
{
	var $boxcode="project";
	var $boximg="object_projectpub";
	var $boxlabel;
	//var $depends = array("projet");
	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $langs;
        $langs->load("boxes");
        $langs->load("projects");

        $this->db = $db;
        $this->boxlabel="Projects";
    }

	/**
	*  Load data for box to show them later
	*
	*  @param   int		$max        Maximum number of records to load
	*  @return  void
	*/
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$totalMnt = 0;
		$totalnb = 0;
		$totalnbTask=0;

		$textHead = $langs->trans("OpenedProjects");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->projet->lire) {
		    
		    include_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
		    $projectstatic = new Project($this->db);
		    
		    $socid=$user->societe_id;
		    
    		// Get list of project id allowed to user (in a string list separated by coma)
		    $projectsListId='';
    		if (! $user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1,$socid);
		
		    $sql = "SELECT p.rowid, p.ref, p.title, p.fk_statut, p.public";
			$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
            if($user->socid) $sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=p.fk_soc";
			$sql.= " WHERE p.entity IN (".getEntity('project',1).')';
            if (! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";     // public and assigned to, or restricted to company for external users
			if ($user->socid) $sql.= " AND s.rowid = ".$user->socid;
            $sql.= " AND p.fk_statut = 1"; // Seulement les projets ouverts
            if ($socid) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
            if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
            
            $sql.= " ORDER BY p.datec DESC";
			//$sql.= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result) {
                $num = $db->num_rows($result);
                $i = 0;
                while ($i < min($num, $max)) {
                    $objp = $db->fetch_object($result);

                    $tooltip = $langs->trans('Project') . ': ' . $objp->ref;
                    $this->info_box_contents[$i][0] = array(
                        'td' => 'align="left" width="16"',
                        'logo' => 'object_project'.($objp->public?'pub':''),
                        'tooltip' => $tooltip,
                        'url' => DOL_URL_ROOT."/projet/card.php?id=".$objp->rowid,
                    );

                    $this->info_box_contents[$i][1] = array(
                        'td' => 'align="left"',
                        'text' => $objp->ref,
                        'tooltip' => $tooltip,
                        'url' => DOL_URL_ROOT."/projet/card.php?id=".$objp->rowid,
                    );

                    $this->info_box_contents[$i][2] = array(
                        'td' => 'align="left"',
                        'text' => $objp->title,
                    );

					$sql ="SELECT count(*) as nb, sum(progress) as totprogress";
					$sql.=" FROM ".MAIN_DB_PREFIX."projet as p LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt on pt.fk_projet = p.rowid";
	           		$sql.= " WHERE p.entity IN (".getEntity('project',1).')';
    				$sql.=" AND p.rowid = ".$objp->rowid;
					$resultTask = $db->query($sql);
					if ($resultTask) {
						$objTask = $db->fetch_object($resultTask);
                        $this->info_box_contents[$i][3] = array(
                            'td' => 'class="right"',
                            'text' => $objTask->nb."&nbsp;".$langs->trans("Tasks"),
                        );
						if ($objTask->nb  > 0)
                            $this->info_box_contents[$i][4] = array(
                                'td' => 'class="right"',
                                'text' => round($objTask->totprogress/$objTask->nb, 0)."%",
                            );
						else
							$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => "N/A&nbsp;");
						$totalnbTask += $objTask->nb;
					} else {
						$this->info_box_contents[$i][3] = array('td' => 'class="right"', 'text' => round(0));
						$this->info_box_contents[$i][4] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
					}

					$i++;
				}
				if ($max < $num)
				{
				    $this->info_box_contents[$i][0] = array('td' => 'colspan="5"', 'text' => '...');				    
				    $i++;
				}
			}
		}


		// Add the sum à the bottom of the boxes
        $this->info_box_contents[$i][0] = array(
            'tr' => 'class="liste_total"',
            'td' => 'align="left" ',
            'text' => "&nbsp;",
       );
        $this->info_box_contents[$i][1] = array(
            'td' => '',
            'text' => $langs->trans("Total")."&nbsp;".$textHead,
             'text' => "&nbsp;",
        );
        $this->info_box_contents[$i][2] = array(
            'td' => 'align="right" ',
            'text' => round($num, 0)."&nbsp;".$langs->trans("Projects"),
        );
        $this->info_box_contents[$i][3] = array(
            'td' => 'align="right" ',
            'text' => (($max < $num) ? '' : (round($totalnbTask, 0)."&nbsp;".$langs->trans("Tasks"))),
        );
        $this->info_box_contents[$i][4] = array(
            'td' => '',
            'text' => "&nbsp;",
        );

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}

