<!-- BEGIN TEMPLATE resource_view.tpl.php -->
<?php

$form= new Form($db);

if (! empty($conf->use_javascript_ajax))
{
	print '<div id="'.$resource_obj.'iddivjstreecontrol">';
	print '<a href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a>';
	print '&nbsp;&nbsp;|&nbsp;&nbsp;';
	print '<a href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a>';
	print '</div>';
}

if ($mode == 'edit') {
	print '<form action="'.$_SERVER["PHP_SELF"].'?element_type='.$element_type.'&element_id='.$element_id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
	print '<input type="hidden" name="id" value="'.$object->id.'" />';
	print '<input type="hidden" name="action" value="update_linked_resource" />';
}

print '<div class="fichecenter">';
print '<table class="liste nohover centpercent">';
print '<tr class="liste_titre">';
print '<td style="width: 23%">&nbsp;'.$langs->trans('Resources').'</td>';
print '<td style="width: 2%"></td>'; //Compensate icon displacement
print '<td style="width: 15%">'.$langs->trans('Type').'</td>';
print '<td style="width: 10%">'.$langs->trans('Available').'</td>';
print '<td style="width: 15%">';
if ($element_type == "product" || $element_type == 'service') {
	print $langs->trans('Dependency');
}
print '</td>';
print '<td style="width: 10%">'.$langs->trans('Mandatory').'</td>';
print '<td style="width: 15%">'.$langs->trans('CurrentStatus').'</td>';
print '<td style="width: 10%"></td>';
print '</tr>';

// Define data (format for treeview)
$data = array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($res_tree as $key => $val)
{
	$link = $val['link'];
	$resource = $val['resource'];
	if ($link->resource_type != $resource_obj)
	{
		continue;
	}
	$editline = $mode == 'edit' && $link->id == GETPOST('lineid');

	if ($editline)
	{
		print '<input type="hidden" name="lineid" value="'.$link->id.'" />';
	}

	//Generate entry
	$entry = '<table class="nobordernopadding centpercent"><tr>'; //Name and Columns table
	//Name and warning icon
	$entry.= '<td style="width: 23%; float: left">';
	$entry.= $resource->getNomUrl(3);
	$entry.= '</td><td style="width: 75%; float: right">';
	//Columns subtable
	$entry.= '<table class="nobordernopadding centpercent"><tr>';
	//Resource type
	$entry.= '<td style="width: 15%">'.$resource->type_label.'</td>';
	//Available
	$entry.= '<td style="width: 10%">';
	$entry.= yn($resource->available);
	$entry.= '</td>';
	//Dependency
	$entry.= '<td style="width: 15%">';
	if ($element_type == "product" || $element_type == 'service')
	{
		if ($editline) {
			$entry.= $form->selectarray('dependency', $dependency_modes, $link->dependency);
		} else{
			$entry.= $dependency_modes[$link->dependency];
			if (!$val['satisfied'])
			{
				$entry .= '&nbsp;'.img_warning($langs->trans("WarningDependentResourcesNotAvailable"));
			}
		}
	}
	$entry.= '</td>';
	//Mandatory
	$entry.= '<td style="width: 10%">';
	$entry.= $editline ? $form->selectyesno('mandatory', $link->mandatory ? 1 : 0, 1) : yn($link->mandatory);
	$entry.= '</td>';
	//Status
	$entry.= '<td style="width: 15%">';
	$entry.= $status_trans[$val['status']];
	if ($val['status'] != $val['status_priority'])
	{
		$entry .= '&nbsp;'.img_warning($langs->trans("WarningDependentResourceStatus", $status_trans[$val['status_priority']]));
	}
	$entry.= '</td>';
	//Buttons
	$entry.= '<td style="width: 10%" align = right>';
	if (!$editline) {
		$entry.= '<a href="'.$_SERVER['PHP_SELF'].'?mode=edit&resource_type='.$resource_obj.'&element_type='.$element_type.'&element_id='.$element_id.'&lineid='.$link->id.'">';
		$entry.= img_edit().'</a>';
		$entry.= '&nbsp;';
		$entry.= '<a href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&element_type='.$element_type.'&element_id='.$element_id.'&lineid='.$link->id.'">';
		$entry.= img_delete().'</a>';
	}
	$entry.= '</td>';

	//Close Columns subtable
	$entry.= '</tr></table>';
	//Close right td and table
	$entry.= '</td></tr></table>';

	$data[] = array(
	'rowid'=>$link->id,
	'fk_menu'=>$link->fk_parent,
	'entry'=>$entry
	);
}

$nbofentries=(count($data) - 1);
if ($nbofentries > 0)
{
	print '<tr '.$bc[0].'><td colspan="8">';
	tree_recur($data,$data[0],0, $resource_obj.'iddivjstree');
	print '</td></tr>';
}
else
{
	print '<tr><td colspan="7">';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">'.$langs->trans("NoResourceLinked").'</td>';
	print '</table></tr>';
	print '</td></tr>';
}

print '</table>';
print '</div>';

if ($mode == 'edit') {
	print '<br>';
	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Update").'">';
	print '&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '</form>';
}

?>
<!-- END TEMPLATE resource_view.tpl.php -->
