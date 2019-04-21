<!-- BEGIN TEMPLATE resource_view.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

$form= new Form($db);

if (! empty($conf->use_javascript_ajax))
{
	print '<div id="'.$resource_obj.'iddivjstreecontrol">';
	print '<a href="#">'.img_picto('', 'object_category').' '.$langs->trans("UndoExpandAll").'</a>';
	print '&nbsp;&nbsp;|&nbsp;&nbsp;';
	print '<a href="#">'.img_picto('', 'object_category-expanded').' '.$langs->trans("ExpandAll").'</a>';
	print '</div>';
}

if ($mode == 'edit') {
    print '<form method="POST" class="tagtable centpercent noborder allwidth">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
    print '<input type="hidden" name="action" value="update_linked_resource" />';
    print '<input type="hidden" name="lineid" value="'.$lineid.'" />';
    print '<input type="hidden" name="element" value="'.$element.'">';
    print '<input type="hidden" name="element_id" value="'.$element_id.'">';
    print '<input type="hidden" name="ref" value="'.$element_ref.'">';
    print '<input type="hidden" name="resource_type" value="'.$resource_type.'">';
}

print '<div class="fichecenter">';
print '<table class="liste nohover centpercent">';
print '<tr class="liste_titre">';
print '<td style="">&nbsp;'.$langs->trans('Resources').'</td>';
print '<td style="width: 15%">'.$langs->trans('Type').'</td>';
print '<td style="width: 10%">'.$langs->trans('Busy').'</td>';
print '<td style="width: 10%">'.$langs->trans('Mandatory').'</td>';
print '<td style="width: 10%"></td>'; //Buttons
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
	
	//Generate entry
    $entry = "";
	$entry.= '<table class="nobordernopadding centpercent"><tr>'; //Name and Columns table
	//Name and warning icon
	$entry.= '<td style="float: left;">';
	$entry.= $resource->getNomUrl(3);
	$entry.= '</td>';
	//Resource type
	$entry.= '<td style="width: 15%">'.$resource->type_label.'</td>';
	//Busy
	$entry.= '<td style="width: 10%">';
	$entry.= $editline ? $form->selectyesno('busy', $link->busy ? 1 : 0, 1) : yn($link->busy);
	$entry.= '</td>';
	//Mandatory
	$entry.= '<td style="width: 10%">';
	$entry.= $editline ? $form->selectyesno('mandatory', $link->mandatory ? 1 : 0, 1) : yn($link->mandatory);
	$entry.= '</td>';
	//Buttons
	$entry.= '<td style="width: 10%" align = right>';
	if (!$editline) {
		$entry.= '<a href="'.$_SERVER['PHP_SELF'].'?mode=edit&resource_type='.$resource_obj.'&element='.$element.$element_url.'&lineid='.$link->id.'">';
		$entry.= img_edit().'</a>';
		$entry.= '&nbsp;';
		$entry.= '<a href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&element='.$element.$element_url.'&lineid='.$link->id.'">';
		$entry.= img_delete().'</a>';
	}
	$entry.= '</td>';
	//Close table
	$entry.= '</tr></table>';

	$data[] = array(
	'rowid'=>$link->id,
	'fk_menu'=>$link->fk_parent,
	'entry'=>$entry
	);
}

$nbofentries=(count($data) - 1);
if ($nbofentries > 0)
{
	print '<tr '.$bc[0].'><td colspan="5">';
	tree_recur($data, $data[0], 0, $resource_obj.'iddivjstree');
	print '</td></tr>';
}
else
{
	print '<tr><td colspan="5">';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td>'.img_picto_common('', 'treemenu/branchbottom.gif').'</td>';
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
