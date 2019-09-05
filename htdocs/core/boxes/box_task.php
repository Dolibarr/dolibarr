<?php
/* Copyright (C) 2012-2018 Charlene BENKE 	<charlie@patas-monkey.com>
 * Copyright (C) 2015-2019  Frederic France      <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/boxes/box_task.php
 *  \ingroup    Projet
 *  \brief      Module to Task activity of the current year
 */

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";


/**
 * Class to manage the box to show last task
 */
class box_task extends ModeleBoxes
{
    public $boxcode="projettask";
    public $boximg="object_projecttask";
    public $boxlabel;
    public $depends = array("projet");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $enabled = 1;		// enable because fixed ;-).

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

        $this->boxlabel="Tasks";
        $this->db = $db;

        $this->hidden = ! ($user->rights->projet->lire);
    }

	/**
	 *  Load data for box to show them later
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;
		include_once DOL_DOCUMENT_ROOT."/projet/class/task.class.php";
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
        require_once DOL_DOCUMENT_ROOT."/core/lib/project.lib.php";
        $projectstatic = new Project($this->db);
		$taskstatic=new Task($db);
		$form= new Form($db);
        $cookie_name='boxfilter_task';
        $boxcontent='';

        $textHead = $langs->trans("CurentlyOpenedTasks");

        $filterValue='all';
        if(in_array(GETPOST($cookie_name), array('all','im_project_contact','im_task_contact'))){
            $filterValue = GETPOST($cookie_name);
        }
        elseif(!empty($_COOKIE[$cookie_name])){
            $filterValue = $_COOKIE[$cookie_name];
        }


        if($filterValue == 'im_task_contact'){
            $textHead.= ' : '.$langs->trans("WhichIamLinkedTo");
        }
        elseif($filterValue == 'im_project_contact'){
            $textHead.= ' : '.$langs->trans("WhichIamLinkedToProject");
        }


		$this->info_box_head = array(
		    'text' => $textHead,
            'limit'=> dol_strlen($textHead),
            'sublink'=>'',
            'subtext'=>$langs->trans("Filter"),
            'subpicto'=>'filter.png',
            'subclass'=>'linkobject boxfilter',
            'target'=>'none'	// Set '' to get target="_blank"
        );

		// list the summary of the orders
		if ($user->rights->projet->lire) {
            $boxcontent.= '<div id="ancor-idfilter'.$this->boxcode.'" style="display: block; position: absolute; margin-top: -100px"></div>'."\n";
            $boxcontent.= '<div id="idfilter'.$this->boxcode.'" class="hideobject center" >'."\n";
            $boxcontent.= '<form class="flat " method="POST" action="'.$_SERVER["PHP_SELF"].'#ancor-idfilter'.$this->boxcode.'">'."\n";
            $boxcontent.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
            $selectArray = array('all' => $langs->trans("NoFilter"), 'im_task_contact' => $langs->trans("WhichIamLinkedTo"), 'im_project_contact' => $langs->trans("WhichIamLinkedToProject"));
            $boxcontent.= $form->selectArray($cookie_name, $selectArray, $filterValue);
            $boxcontent.= '<button type="submit" >'.$langs->trans("Change").'</button>';
            $boxcontent.= '</form>'."\n";
            $boxcontent.= '</div>'."\n";
            $boxcontent.= '<script type="text/javascript" language="javascript">
					jQuery(document).ready(function() {
						jQuery("#idsubimg'.$this->boxcode.'").click(function() {
							jQuery("#idfilter'.$this->boxcode.'").toggle();
						});
					});
					</script>';



            $sql = "SELECT pt.rowid, pt.ref, pt.fk_projet, pt.fk_task_parent, pt.datec, pt.dateo, pt.datee, pt.datev, pt.label, pt.description, pt.duration_effective, pt.planned_workload, pt.progress";
			$sql.= ", p.rowid project_id, p.ref project_ref, p.title project_title";

			$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
			$sql.= " JOIN ".MAIN_DB_PREFIX."projet as p ON (pt.fk_projet = p.rowid)";

            if($filterValue === 'im_task_contact') {
                $sql .= " JOIN " . MAIN_DB_PREFIX . "element_contact as ec ON (ec.element_id = pt.rowid AND ec.fk_socpeople = '" . $user->id . "' )";
                $sql .= " JOIN " . MAIN_DB_PREFIX . "c_type_contact  as tc ON (ec.fk_c_type_contact = tc.rowid AND tc.element = 'project_task' AND tc.source = 'internal' )";
            }
            elseif($filterValue === 'im_project_contact') {
                $sql .= " JOIN " . MAIN_DB_PREFIX . "element_contact as ec ON (ec.element_id = p.rowid AND ec.fk_socpeople = '" . $user->id . "' )";
                $sql .= " JOIN " . MAIN_DB_PREFIX . "c_type_contact  as tc ON (ec.fk_c_type_contact = tc.rowid AND tc.element = 'project' AND tc.source = 'internal' )";
            }

			$sql.= " WHERE ";
			$sql.= " pt.entity = ".$conf->entity;
			$sql.= " AND p.fk_statut = ".Project::STATUS_VALIDATED;
			$sql.= " AND (pt.progress < 100 OR pt.progress IS NULL ) "; // 100% is done and not displayed

			$sql.= " ORDER BY pt.datee ASC, pt.dateo ASC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			$i = 0;
			if ($result) {
				$num = $db->num_rows($result);
                while ($objp = $db->fetch_object($result)) {

                    $taskstatic->id=$objp->rowid;
                    $taskstatic->ref=$objp->ref;
                    $taskstatic->label=$objp->label;
                    $taskstatic->progress = $objp->progress;
                    $taskstatic->fk_statut = $objp->fk_statut;
                    $taskstatic->date_end = $objp->datee;
                    $taskstatic->planned_workload= $objp->planned_workload;
                    $taskstatic->duration_effective= $objp->duration_effective;

                    $projectstatic->id = $objp->project_id;
                    $projectstatic->ref = $objp->project_ref;
                    $projectstatic->title = $objp->project_title;

                    $label = $projectstatic->getNomUrl(1).' '.$taskstatic->getNomUrl(1).' '.dol_htmlentities($taskstatic->label);

                    $boxcontent.= getTaskProgressView($taskstatic, $label, true, false, true);

					$i++;
				}
			} else {
                dol_print_error($this->db);
            }
		}

        // set cookie by js
        if(empty($i)){
            $boxcontent.='<script >date = new Date(); date.setTime(date.getTime()+(30*86400000)); document.cookie = "'.$cookie_name.'='.$filterValue.'; expires= " + date.toGMTString() + "; path=/ "; </script>';
        }

        $this->info_box_contents[0][] = array(
            'td' => '',
            'text' => $boxcontent,
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
