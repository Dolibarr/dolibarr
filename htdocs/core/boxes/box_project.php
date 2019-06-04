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
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last projet
 */
class box_project extends ModeleBoxes
{
    public $boxcode="project";
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
        $this->boxlabel="OpenedProjects";

        $this->hidden=! ($user->rights->projet->lire);
    }

    /**
    *  Load data for box to show them later
    *
    *  @param   int		$max        Maximum number of records to load
    *  @return  void
    */
    public function loadBox($max = 5)
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

            include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
            $projectstatic = new Project($this->db);

            $socid=0;
            //if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.

            // Get list of project id allowed to user (in a string list separated by coma)
            $projectsListId='';
            if (! $user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);

            $sql = "SELECT p.rowid, p.ref, p.title, p.fk_statut, p.public";
            $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
            $sql.= " WHERE p.fk_statut = 1"; // Only open projects
            if (! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")"; // public and assigned to, or restricted to company for external users

            $sql.= " ORDER BY p.datec DESC";
            //$sql.= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result) {
                $num = $db->num_rows($result);
                $i = 0;
                while ($i < min($num, $max)) {
                    $objp = $db->fetch_object($result);

                    $projectstatic->id = $objp->rowid;
                    $projectstatic->ref = $objp->ref;
                    $projectstatic->title = $objp->title;
                    $projectstatic->public = $objp->public;

                    $this->info_box_contents[$i][] = array(
                        'td' => 'class="nowraponall"',
                        'text' => $projectstatic->getNomUrl(1),
                        'asis' => 1
                    );

                    $this->info_box_contents[$i][] = array(
                        'td' => '',
                        'text' => $objp->title,
                    );

                    $sql ="SELECT count(*) as nb, sum(progress) as totprogress";
                    $sql.=" FROM ".MAIN_DB_PREFIX."projet as p LEFT JOIN ".MAIN_DB_PREFIX."projet_task as pt on pt.fk_projet = p.rowid";
                       $sql.= " WHERE p.entity IN (".getEntity('project').')';
                    $sql.=" AND p.rowid = ".$objp->rowid;
                    $resultTask = $db->query($sql);
                    if ($resultTask) {
                        $objTask = $db->fetch_object($resultTask);
                        $this->info_box_contents[$i][] = array(
                            'td' => 'class="right"',
                            'text' => $objTask->nb."&nbsp;".$langs->trans("Tasks"),
                        );
                        if ($objTask->nb  > 0)
                            $this->info_box_contents[$i][] = array(
                                'td' => 'class="right"',
                                'text' => round($objTask->totprogress/$objTask->nb, 0)."%",
                            );
                        else
                            $this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
                        $totalnbTask += $objTask->nb;
                    } else {
                        $this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => round(0));
                        $this->info_box_contents[$i][] = array('td' => 'class="right"', 'text' => "N/A&nbsp;");
                    }

                    $i++;
                }
                if ($max < $num)
                {
                    $this->info_box_contents[$i][] = array('td' => 'colspan="5"', 'text' => '...');
                    $i++;
                }
            }
        }


        // Add the sum à the bottom of the boxes
        $this->info_box_contents[$i][] = array(
            'td' => '',
            'text' => $langs->trans("Total")."&nbsp;".$textHead,
             'text' => "&nbsp;",
        );
        $this->info_box_contents[$i][] = array(
            'td' => 'class="right" ',
            'text' => round($num, 0)."&nbsp;".$langs->trans("Projects"),
        );
        $this->info_box_contents[$i][] = array(
            'td' => 'class="right" ',
            'text' => (($max < $num) ? '' : (round($totalnbTask, 0)."&nbsp;".$langs->trans("Tasks"))),
        );
        $this->info_box_contents[$i][] = array(
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
     *	@return	string
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
