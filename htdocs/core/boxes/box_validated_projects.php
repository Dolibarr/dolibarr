<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
 * Copyright (C) 2020      Pierre Ardoin          <mapiolca@me.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/boxes/box_validated_projects.php
 *  \ingroup    projet
 *  \brief      Module to show validated projects whose tasks are assigned to the connected person, without any time entered by the connected person
 */
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last projet
 */
class box_validated_projects extends ModeleBoxes
{
    public $boxcode="validated_project";
    public $boximg="object_projectpub";
    public $boxlabel;
    //var $depends = array("projet");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;

    public $info_box_head = array();
    public $info_box_contents = array();

    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    public function __construct($db, $param = '')
    {
        global $user, $langs;

        // Load translation files required by the page
        $langs->loadLangs(array('boxes', 'projects'));

        $this->db = $db;
        $this->boxlabel = "ValidatedProjects";

        $this->hidden = ! ($user->rights->projet->lire);
    }

    /**
    *  Load data for box to show them later
    *
    *  @param   int		$max        Maximum number of records to load
    *  @return  void
    */
    public function loadBox($max = 5)
    {
        global $conf, $user, $langs;

        $this->max=$max;

        $totalMnt = 0;
        $totalnb = 0;
        $totalnbTask=0;

        $textHead = $langs->trans("ValidatedProjects");
        $this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

        // list the summary of the orders
        if ($user->rights->projet->lire) {
            include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
            $projectstatic = new Project($this->db);

            $socid=0;
            //if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.

            // Get list of project id allowed to user (in a string list separated by coma)
            $projectsListId='';
            if (! $user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);

            $sql = "SELECT p.rowid, p.ref as Ref, p.fk_soc as Client, p.dateo as startDate,";
            $sql.= " (SELECT COUNT(t.rowid) FROM ".MAIN_DB_PREFIX."projet_task AS t";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact AS c ON t.rowid = c.element_id";
            $sql.= " WHERE t.fk_projet = p.rowid AND c.fk_c_type_contact != 160 AND c.fk_socpeople = ".$user->id." AND t.rowid NOT IN (SELECT fk_task FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_user =".$user->id.")) AS 'taskNumber'";
            $sql.= " FROM ".MAIN_DB_PREFIX."projet AS p";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task AS t ON p.rowid = t.fk_projet";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact AS c ON t.rowid = c.element_id";
            $sql.= " WHERE p.fk_statut = 1"; // Only open projects
			$sql.= " AND t.rowid NOT IN (SELECT fk_task FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_user =".$user->id.")";
			$sql.= " AND c.fk_socpeople = ".$user->id;
			$sql.= " GROUP BY p.ref";
            $sql.= " ORDER BY p.dateo ASC";

            $result = $this->db->query($sql);
            if ($result) {
                $num = $this->db->num_rows($result);
                $i = 0;
				$this->info_box_contents[$i][] = array(
					'td' => 'class="nowraponall"',
					'text' => "Reference projet",
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Client',
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Date debut de projet',
				);
				$this->info_box_contents[$i][] = array(
					'td' => 'class="center"',
					'text' => 'Nombre de mes tâches sans temps saisi',
				);
				$i++;

                while ($i < min($num+1, $max+1)) {
                    $objp = $this->db->fetch_object($result);

                    $projectstatic->id = $objp->rowid;
                    $projectstatic->ref = $objp->Ref;
                    $projectstatic->customer = $objp->Client;
                    $projectstatic->startDate = $objp->startDate;
                    $projectstatic->taskNumber = $objp->taskNumber;

                    $this->info_box_contents[$i][] = array(
                        'td' => 'class="nowraponall"',
                        'text' => $projectstatic->getNomUrl(1),
                        'asis' => 1
                    );

                    $sql = 'SELECT rowid, nom FROM '.MAIN_DB_PREFIX.'societe WHERE rowid ='.$objp->Client;
					$resql = $this->db->query($sql);
					if ($resql){
						$socstatic = new Societe($this->db);
						$obj = $this->db->fetch_object($resql);
						$this->info_box_contents[$i][] = array(
							'td' => 'class="tdoverflowmax150 maxwidth200onsmartphone"',
							'text' => $obj->nom,
							'asis' => 1,
							'url' => DOL_URL_ROOT.'/societe/card.php?socid='.$obj->rowid
						);
					}
					else {
						dol_print_error($this->db);
					}

                    $this->info_box_contents[$i][] = array(
                        'td' => 'class="center"',
                        'text' => $objp->startDate,
                    );

                    $this->info_box_contents[$i][] = array(
                        'td' => 'class="center"',
                        'text' => $objp->taskNumber."&nbsp;".$langs->trans("Tasks"),
						'asis' => 1,
                    );
                    $i++;
                }
            }else {
            	dol_print_error($this->db);
			}
        }
    }

    /**
     *	Method to show box
     *
     *	@param	array	$head       Array with properties of box title
     *	@param  array	$contents   Array with properties of box lines
     *  @param	int		$nooutput	No print, only return string
     *	@return	string
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
