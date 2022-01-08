<?php
/* Copyright (C) 2010-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	g.setShowTaskInfoLink(1);
	g.setFormatArr("day","week","month") // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
	g.setCaptionType('Caption');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
	g.setUseFade(0);
	g.setDayColWidth(20);
	/* g.setShowTaskInfoLink(1) */
	g.addLang('<?php print $langs->getDefaultLang(1); ?>', vLangs['<?php print $langs->getDefaultLang(1); ?>']);
	g.setLang('<?php print $langs->getDefaultLang(1); ?>');

	<?php

	echo "\n";
	echo "/* g.AddTaskItem(new JSGantt.TaskItem(task_id, 'label', 'start_date', 'end_date', 'css', 'link', milestone, 'Resources', Compl%, Group, Parent, Open, 'Dependency', 'label','note', g)); */\n";

	$level = 0;
	$tnums = count($tasks);
	$old_project_id = 0;
	for ($tcursor = 0; $tcursor < $tnums; $tcursor++) {
		$t = $tasks[$tcursor];

		if (empty($old_project_id) || $old_project_id != $t['task_project_id']) {
			// Break on project, create a fictive task for project id $t['task_project_id']
			$projecttmp = new Project($db);
			$projecttmp->fetch($t['task_project_id']);
			$tmpt = array(
				'task_id'=> '-'.$t['task_project_id'],
				'task_alternate_id'=> '-'.$t['task_project_id'],
				'task_name'=>$projecttmp->ref.' '.$projecttmp->title,
				'task_resources'=>'',
				'task_start_date'=>'',
				'task_end_date'=>'',
				'task_is_group'=>1, 'task_position'=>0, 'task_css'=>'ggroupblack', 'task_milestone'=> 0, 'task_parent'=>0, 'task_parent_alternate_id'=>0,
				'task_notes'=>'',
				'task_planned_workload'=>0
			);
			constructGanttLine($tasks, $tmpt, array(), 0, $t['task_project_id']);
			$old_project_id = $t['task_project_id'];
		}

		if ($t["task_parent"] <= 0) {
			constructGanttLine($tasks, $t, $task_dependencies, $level, $t['task_project_id']);
			findChildGanttLine($tasks, $t["task_id"], $task_dependencies, $level + 1);
		}
	}

	echo "\n";
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
 * @param 	array	$tarr					Array of all tasks
 * @param	array	$task					Array with properties of one task
 * @param 	array	$task_dependencies		Task dependencies (array(array(0=>idtask,1=>idtasktofinishfisrt))
 * @param 	int		$level					Level
 * @param 	int		$project_id				Id of project
 * @return	void
 */
function constructGanttLine($tarr, $task, $task_dependencies, $level = 0, $project_id = null)
{
	global $langs;
	global $dateformatinput2;

	$start_date = $task["task_start_date"];
	$end_date = $task["task_end_date"];
	if (!$end_date) {
		$end_date = $start_date;
	}
	$start_date = dol_print_date($start_date, $dateformatinput2);
	$end_date = dol_print_date($end_date, $dateformatinput2);
	// Resources
	$resources = $task["task_resources"];

	// Define depend (ex: "", "4,13", ...)
	$depend = '';
	$count = 0;
	foreach ($task_dependencies as $value) {
		// Not yet used project_dependencies = array(array(0=>idtask,1=>idtasktofinishfisrt))
		if ($value[0] == $task['task_id']) {
			$depend .= ($count > 0 ? "," : "").$value[1];
			$count++;
		}
	}
	// $depend .= "\"";
	// Define parent
	if ($project_id && $level < 0) {
		$parent = '-'.$project_id;
	} else {
		$parent = $task["task_parent_alternate_id"];
		//$parent = $task["task_parent"];
	}
	// Define percent
	$percent = $task['task_percent_complete'] ? $task['task_percent_complete'] : 0;
	// Link (more information)
	if ($task["task_id"] < 0) {
		//$link=DOL_URL_ROOT.'/projet/tasks.php?withproject=1&id='.abs($task["task_id"]);
		$link = '';
	} else {
		$link = DOL_URL_ROOT.'/projet/tasks/contact.php?withproject=1&id='.$task["task_id"];
	}

	// Name
	//$name='<a href="'.DOL_URL_ROOT.'/projet/task/tasks.php?id='.$task['task_id'].'">'.$task['task_name'].'</a>';
	$name = $task['task_name'];

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
	<dt>pDepend</dt><dd>(optional) comma separated list of id&#39;s this task is dependent on. A line will be drawn from each listed task to this item<br>Each id can optionally be followed by a dependency type suffix. Valid values are:<blockquote>'FS' - Finish to Start (default if suffix is omitted)<br>'SF' - Start to Finish<br>'SS' - Start to Start<br>'FF' - Finish to Finish</blockquote>If present the suffix must be added directly to the id e.g. '123SS'</dd>
	<dt>pCaption</dt><dd>(optional) caption that will be added after task bar if CaptionType set to "Caption"</dd>
	<dt>pNotes</dt><dd>(optional) Detailed task information that will be displayed in tool tip for this task</dd>
	<dt>pGantt</dt><dd>(required) javascript JSGantt.GanttChart object from which to take settings.  Defaults to &quot;g&quot; for backwards compatibility</dd>
	*/

	//$note="";

	$s = "\n// Add task level = ".$level." id=".$task["task_id"]." parent_id=".$task["task_parent"]." aternate_id=".$task["task_alternate_id"]." parent_aternate_id=".$task["task_parent_alternate_id"]."\n";

	//$task["task_is_group"]=1;		// When task_is_group is 1, content will be autocalculated from sum of all low tasks

	// For JSGanttImproved
	$css = $task['task_css'];
	$line_is_auto_group = $task["task_is_group"];
	//$line_is_auto_group=0;
	//if ($line_is_auto_group) $css = 'ggroupblack';
	//$dependency = ($depend?$depend:$parent."SS");
	$dependency = '';
	//$name = str_repeat("..", $level).$name;

	$taskid = $task["task_alternate_id"];
	//$taskid = $task['task_id'];

	$note = $task['note'];

	$note = dol_concatdesc($note, $langs->trans("Workload").' : '.($task['task_planned_workload'] ? convertSecondToTime($task['task_planned_workload'], 'allhourmin') : ''));

	$s .= "g.AddTaskItem(new JSGantt.TaskItem('".$taskid."', '".dol_escape_js(trim($name))."', '".$start_date."', '".$end_date."', '".$css."', '".$link."', ".$task['task_milestone'].", '".dol_escape_js($resources)."', ".($percent >= 0 ? $percent : 0).", ".$line_is_auto_group.", '".$parent."', 1, '".$dependency."', '".(empty($task["task_is_group"]) ? (($percent >= 0 && $percent != '') ? $percent.'%' : '') : '')."', '".dol_escape_js($note)."', g));";
	echo $s;
}

/**
 * Find child Gantt line
 *
 * @param 	array	$tarr					tarr
 * @param	int		$parent					Parent
 * @param 	array	$task_dependencies		Task dependencies
 * @param 	int		$level					Level
 * @return	void
 */
function findChildGanttLine($tarr, $parent, $task_dependencies, $level)
{
	$n = count($tarr);

	$old_parent_id = 0;
	for ($x = 0; $x < $n; $x++) {
		if ($tarr[$x]["task_parent"] == $parent && $tarr[$x]["task_parent"] != $tarr[$x]["task_id"]) {
			// Create a grouping parent task for the new level
			/*if (empty($old_parent_id) || $old_parent_id != $tarr[$x]['task_project_id'])
			{
				$tmpt = array(
				'task_id'=> -98, 'task_name'=>'Level '.$level, 'task_resources'=>'', 'task_start_date'=>'', 'task_end_date'=>'',
				'task_is_group'=>1, 'task_css'=>'ggroupblack', 'task_milestone'=> 0, 'task_parent'=>$tarr[$x]["task_parent"], 'task_notes'=>'');
				constructGanttLine($tasks, $tmpt, array(), 0, $tarr[$x]['task_project_id']);
				$old_parent_id = $tarr[$x]['task_project_id'];
			}*/

			constructGanttLine($tarr, $tarr[$x], $task_dependencies, $level, null);
			findChildGanttLine($tarr, $tarr[$x]["task_id"], $task_dependencies, $level + 1);
		}
	}
}

