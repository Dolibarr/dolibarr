<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/projet/ganttchart.inc.php
 *	\ingroup    projet
 *	\brief      Gantt diagram of a project
 */

?>

<div id="principal_content" style="margin-left: 0;">
	<div style="margin-left: 0; position: relative;" class="gantt" id="GanttChartDIV"></div>

	<script type="text/javascript">

function DisplayHideRessources(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowRessources = 1;
	}
	else {
		booShowRessources = 0;
	}
	reloadGraph();
}

function DisplayHideDurations(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowDurations = 1;
	}
	else {
		booShowDurations = 0;
	}
	reloadGraph();
}

function DisplayHideComplete(boxName) {
	graphFormat = g.getFormat();
	if(boxName.checked == true) {
		booShowComplete = 1;
	}
	else {
		booShowComplete = 0;
	}
	reloadGraph();
}

function selectBarText(value) {
	graphFormat = g.getFormat();
	id=value.options[value.selectedIndex].value;
	barText = id;
	reloadGraph();
}

function reloadGraph() {
	g.setShowRes(booShowRessources);
	g.setShowComp(booShowComplete);
	g.setShowDur(booShowDurations);
	g.setCaptionType(barText);
	g.setFormat(graphFormat);
	g.Draw(jQuery("#tabs").width()-40);
}


//var g = new JSGantt.GanttChart('g', document.getElementById('GanttChartDIV'), 'day');
var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'day');

if (g.getDivId() != null)
//if (g)
{
	var booShowRessources = 1;
	var booShowDurations = 1;
	var booShowComplete = 1;
	var barText = "Resource";
	var graphFormat = "day";

	g.setDateInputFormat('<?php echo $dateformatinput; ?>');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', does not work with 'yyyy-mm-dd')
	g.setDateTaskTableDisplayFormat('<?php echo $dateformat; ?>');	// Format of date used into line
	g.setDateTaskDisplayFormat('<?php echo $datehourformat; ?>');		// Format of date used into popup, not into line
	g.setDayMajorDateDisplayFormat('dd mon');
	g.setShowRes(1); 		// Show/Hide Responsible (0/1)
	g.setShowDur(1); 		// Show/Hide Duration (0/1)
	g.setShowComp(1); 		// Show/Hide % Complete(0/1)
	g.setShowStartDate(1); 	// Show/Hide % Complete(0/1)
	g.setShowEndDate(1); 	// Show/Hide % Complete(0/1)
	g.setFormatArr("day","week","month","quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
	g.setCaptionType('Caption');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
	/* g.setShowTaskInfoLink(1) */

	<?php
	$level=0;
	$tnums = count($tasks);
	for ($tcursor=0; $tcursor < $tnums; $tcursor++) {
		$t = $tasks[$tcursor];
		if ($t["task_parent"] == 0) {
			constructGanttLine($tasks,$t,$project_dependencies,$level,$project_id);
			findChildGanttLine($tasks,$t["task_id"],$project_dependencies,$level+1);
		}
	}
	?>

	g.Draw(jQuery("#tabs").width()-40);
	setTimeout('g.DrawDependencies()',100);
}
else
{
	alert("<?php echo $langs->trans("FailedToDefinGraph"); ?>");
}
</script>
</div>



<?php
/**
 * Add a gant chart line
 *
 * @param 	string	$tarr					tarr
 * @param	array	$task					Array with properties of one task
 * @param 	Project	$project_dependencies	Project object
 * @param 	int		$level					Level
 * @param 	int		$project_id				Id of project
 * @return	void
 */
function constructGanttLine($tarr,$task,$project_dependencies,$level=0,$project_id=null)
{
    global $dateformatinput2;
    
    $start_date = $task["task_start_date"];
    $end_date = $task["task_end_date"];
    if (!$end_date) $end_date = $start_date;
    $start_date = dol_print_date($start_date, $dateformatinput2);
    $end_date = dol_print_date($end_date, $dateformatinput2);
    // Resources
    $resources = $task["task_resources"];
    // Define depend (ex: "", "4,13", ...)
    $depend = '';
    $count = 0;
    foreach ($project_dependencies as $value) {
        // Not yet used project_dependencies = array(array(0=>idtask,1=>idtasktofinishfisrt))
        if ($value[0] == $task['task_id']) {
            $depend.=($count>0?",":"").$value[1];
            $count ++;
        }
    }
   // $depend .= "\"";
    // Define parent
    if ($project_id && $level < 0)
    $parent = 'p'.$project_id;
    else
    $parent = $task["task_parent"];
    // Define percent
    $percent = $task['task_percent_complete']?$task['task_percent_complete']:0;
    // Link
    $link=DOL_URL_ROOT.'/projet/tasks/task.php?withproject=1&id='.$task["task_id"];

    // Name
    $name=$task['task_name'];
    /*for($i=0; $i < $level; $i++) {
        $name=' - '.$name;
    }*/
    // Add line to gantt
    /*
	g.AddTaskItem(new JSGantt.TaskItem(1, 'Define Chart API','',          '',          'ggroupblack','', 0, 'Brian', 0,  1,0,1,'','','Some Notes text',g));
	g.AddTaskItem(new JSGantt.TaskItem(11,'Chart Object',    '2014-02-20','2014-02-20','gmilestone', '', 1, 'Shlomy',100,0,1,1,'','','',g));
	</pre>
	<p>Method definition:
	<strong>TaskItem(<em>pID, pName, pStart, pEnd, pColor, pLink, pMile, pRes, pComp, pGroup, pParent, pOpen, pDepend, pCaption, pNotes, pGantt</em>)</strong></p>
	<dl>
	<dt>pID</dt><dd>(required) a unique numeric ID used to identify each row</dd>
	<dt>pName</dt><dd>(required) the task Label</dd>
	<dt>pStart</dt><dd>(required) the task start date, can enter empty date ('') for groups. You can also enter specific time (2014-02-20 12:00) for additional precision.</dd>
	<dt>pEnd</dt><dd>(required) the task end date, can enter empty date ('') for groups</dd>
	<dt>pClass</dt><dd>(required) the css class for this task</dd>
	<dt>pLink</dt><dd>(optional) any http link to be displayed in tool tip as the "More information" link.</dd>
	<dt>pMile</dt><dd>(optional) indicates whether this is a milestone task - Numeric; 1 = milestone, 0 = not milestone</dd>
	<dt>pRes</dt><dd>(optional) resource name</dd>
	<dt>pComp</dt><dd>(required) completion percent, numeric</dd>
	<dt>pGroup</dt><dd>(optional) indicates whether this is a group task (parent) - Numeric; 0 = normal task, 1 = standard group task, 2 = combined group task<a href='#combinedtasks' class="footnote">*</a></dd>
	<dt>pParent</dt><dd>(required) identifies a parent pID, this causes this task to be a child of identified task. Numeric, top level tasks should have pParent set to 0</dd>
	<dt>pOpen</dt><dd>(required) indicates whether a standard group task is open when chart is first drawn. Value must be set for all items but is only used by standard group tasks.  Numeric, 1 = open, 0 = closed</dd>
	<dt>pDepend</dt><dd>(optional) comma separated list of id&#39;s this task is dependent on. A line will be drawn from each listed task to this item<br />Each id can optionally be followed by a dependency type suffix. Valid values are:<blockquote>'FS' - Finish to Start (default if suffix is omitted)<br />'SF' - Start to Finish<br />'SS' - Start to Start<br />'FF' - Finish to Finish</blockquote>If present the suffix must be added directly to the id e.g. '123SS'</dd>
	<dt>pCaption</dt><dd>(optional) caption that will be added after task bar if CaptionType set to "Caption"</dd>
	<dt>pNotes</dt><dd>(optional) Detailed task information that will be displayed in tool tip for this task</dd>
	<dt>pGantt</dt><dd>(required) javascript JSGantt.GanttChart object from which to take settings.  Defaults to &quot;g&quot; for backwards compatibility</dd>
    */

    //$note="";

    $s = "\n// Add taks id=".$task["task_id"]." level = ".$level."\n";
   // $s.= "g.AddTaskItem(new JSGantt.TaskItem(".$task['task_id'].",'".dol_escape_js($name)."','".$start_date."', '".$end_date."', '".$task['task_color']."', '".$link."', ".$task['task_milestone'].", '".$resources."', ".($percent >= 0 ? $percent : 0).", ".($task["task_is_group"]>0?1:0).", '".$parent."', 1, '".($depend?$depend:"")."', '".$note."'));";
    // For JSGanttImproved
    $s.= "g.AddTaskItem(new JSGantt.TaskItem(".$task['task_id'].",'".dol_escape_js(trim($name))."','".$start_date."', '".$end_date."', '".$task['task_css']."', '".$link."', ".$task['task_milestone'].", '".$resources."', ".($percent >= 0 ? $percent : 0).", ".($task["task_is_group"]).", '".$parent."', 1, '".($depend?$depend:$parent."SS")."', '".($percent >= 0 ? $percent.'%' : '0%')."','".$task['note']."'));";
    echo $s;


}

/**
 * Find child Gantt line
 *
 * @param 	string	$tarr					tarr
 * @param	int		$parent					Parent
 * @param 	Project	$project_dependencies	Project object
 * @param 	int		$level					Level
 * @return	void
 */
function findChildGanttLine($tarr,$parent,$project_dependencies,$level)
{
    $n=count($tarr);
    for ($x=0; $x < $n; $x++)
    {
        if($tarr[$x]["task_parent"] == $parent && $tarr[$x]["task_parent"] != $tarr[$x]["task_id"])
        {
            constructGanttLine($tarr,$tarr[$x],$project_dependencies,$level,null);
            findChildGanttLine($tarr,$tarr[$x]["task_id"],$project_dependencies,$level+1);
        }
    }
}

