<!-- BEGIN TEMPLATE resource_view.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


$form= new Form($db);


print '<div class="tagtable centpercent noborder allwidth">';

if($mode == 'edit' )
{
    print '<form class="tagtr liste_titre">';
    print '<div class="tagtd liste_titre">'.$langs->trans('Resource').'</div>';
    print '<div class="tagtd liste_titre">'.$langs->trans('Type').'</div>';
    print '<div class="tagtd liste_titre" align="center">'.$langs->trans('Busy').'</div>';
    print '<div class="tagtd liste_titre" align="center">'.$langs->trans('Mandatory').'</div>';
    print '<div class="tagtd liste_titre"></div>';
    print '</form>';
}
else
{
    print '<form class="tagtr liste_titre">';
    print '<div class="tagtd liste_titre">'.$langs->trans('Resource').'</div>';
    print '<div class="tagtd liste_titre">'.$langs->trans('Type').'</div>';
    print '<div class="tagtd liste_titre" align="center">'.$langs->trans('Busy').'</div>';
    print '<div class="tagtd liste_titre" align="center">'.$langs->trans('Mandatory').'</div>';
    print '<div class="tagtd liste_titre"></div>';
    print '</form>';
}

if( (array) $linked_resources && count($linked_resources) > 0)
{

	foreach ($linked_resources as $linked_resource)
	{

		$object_resource = fetchObjectByElement($linked_resource['resource_id'],$linked_resource['resource_type']);

		//$element_id = $linked_resource['rowid'];

		if ($mode == 'edit' && $linked_resource['rowid'] == GETPOST('lineid'))
		{

			print '<form class="tagtr oddeven" action="'.$_SERVER["PHP_SELF"].'?element='.$element.'&element_id='.$element_id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
			print '<input type="hidden" name="id" value="'.$object->id.'" />';
			print '<input type="hidden" name="action" value="update_linked_resource" />';
			print '<input type="hidden" name="resource_type" value="'.$resource_type.'" />';
			print '<input type="hidden" name="lineid" value="'.$linked_resource['rowid'].'" />';

			print '<div class="tagtd">'.$object_resource->getNomUrl(1).'</div>';
			print '<div class="tagtd">'.$object_resource->type_label.'</div>';
			print '<div class="tagtd" align="center">'.$form->selectyesno('busy',$linked_resource['busy']?1:0,1).'</div>';
			print '<div class="tagtd" align="center">'.$form->selectyesno('mandatory',$linked_resource['mandatory']?1:0,1).'</div>';
			print '<div class="tagtd" align="right"><input type="submit" class="button" value="'.$langs->trans("Update").'"></div>';
			print '</form>';
		}
		else
		{
			$style='';
			if ($linked_resource['rowid'] == GETPOST('lineid'))
				$style='style="background: orange;"';

			print '<form class="tagtr oddeven" '.$style.'>';

			print '<div class="tagtd">';
			print $object_resource->getNomUrl(1);
			print '</div>';

			print '<div class="tagtd">';
			print $object_resource->type_label;
			print '</div>';

			print '<div class="tagtd" align="center">';
			print yn($linked_resource['busy']);
			print '</div>';

			print '<div class="tagtd" align="center">';
			print yn($linked_resource['mandatory']);
			print '</div>';

			print '<div class="tagtd" align="right">';
			print '<a href="'.$_SERVER['PHP_SELF'].'?mode=edit&resource_type='.$linked_resource['resource_type'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&id='.$linked_resource['resource_id'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			print img_delete();
			print '</a>';
			print '</div>';

			print '</form>';
		}
	}
}
else {
	print '<form class="tagtr oddeven">';
	print '<div class="tagtd opacitymedium">'.$langs->trans('NoResourceLinked').'</div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '</form>';
}

print '</div>';

?>
<!-- END TEMPLATE resource_view.tpl.php -->
