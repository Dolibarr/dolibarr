<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/ganttchart.php
 *	\ingroup    projet
 *	\brief      Gantt diagram of a project
 */

?>

<div id="principal_content" style="margin-left: 0px;">
	<div style="margin-left: 0; position: relative;" class="gantt"
		id="GanttChartDIV"></div>

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


var g = new JSGantt.GanttChart('g',document.getElementById('GanttChartDIV'), 'day');
var booShowRessources = 1;
var booShowDurations = 1;
var booShowComplete = 1;
var barText = "Resource";
var graphFormat = "day";

g.setDateInputFormat('mm/dd/yyyy');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
g.setDateDisplayFormat('<?php echo $dateformat; ?>');	// Set format to display dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
g.setShowRes(1); 		// Show/Hide Responsible (0/1)
g.setShowDur(1); 		// Show/Hide Duration (0/1)
g.setShowComp(1); 		// Show/Hide % Complete(0/1)
g.setShowStartDate(1); 	// Show/Hide % Complete(0/1)
g.setShowEndDate(1); 	// Show/Hide % Complete(0/1)
g.setFormatArr("day","week","month","quarter") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
g.setCaptionType('Caption');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
if(g) {
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
 * @param	Task	$task					Task object
 * @param 	Project	$project_dependencies	Project object
 * @param 	int		$level					Level
 * @param 	int		$project_id				Id of project
 * @return	void
 */
function constructGanttLine($tarr,$task,$project_dependencies,$level=0,$project_id=null)
{
    $start_date = $task["task_start_date"];
    $end_date = $task["task_end_date"];
    if (!$end_date) $end_date = $start_date;
    $start_date = dol_print_date($start_date,"%m/%d/%Y");
    $end_date = dol_print_date($end_date,"%m/%d/%Y");
    // Resources
    $resources = $task["task_resources"];
    // Define depend (ex: "", "4,13", ...)
    $depend = "\"";
    $count = 0;
    foreach ($project_dependencies as $value) {
        // Not yet used project_dependencies = array(array(0=>idtask,1=>idtasktofinishfisrt))
        if ($value[0] == $task['task_id']) {
            $depend.=($count>0?",":"").$value[1];
            $count ++;
        }
    }
    $depend .= "\"";
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
    for($i=0; $i < $level; $i++) {
        $name=' &nbsp; &nbsp; '.$name;
    }
    // Add line to gantt
    $s = "// Add taks id=".$task["task_id"]." level = ".$level."\n";
    //$s.= "g.AddElementItem(new JSGantt.ElementItem('task',".$task['task_id'].",'".$name."','".$start_date."', '".$end_date."', '".$task['task_color']."', '', ".$task['task_milestone'].", '".$resources."', ".$percent.", ".($task["task_is_group"]>0?1:0).", ".$parent.", 1".($depend?", ".$depend:"")."));";
    $s = "g.AddTaskItem(new JSGantt.TaskItem(".$task['task_id'].",'".dol_escape_js($name)."','".$start_date."', '".$end_date."', '".$task['task_color']."', '".$link."', ".$task['task_milestone'].", '".$resources."', ".$percent.", ".($task["task_is_group"]>0?1:0).", '".$parent."', 1, '".($depend?$depend:"")."'));";
    echo $s."\n";
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

?>
