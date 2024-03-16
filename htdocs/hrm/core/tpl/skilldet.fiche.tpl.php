<?php

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (!empty($object->table_element_line)) {
	// Show object lines
	$result = $object->getLinesArray();
}



print '<!-- BEGIN PHP TEMPLATE hrm/core/tpl/skilldet.fiche.tpl.php -->'."\n";

print '<div class="tagtable border table-border tableforfield centpercent">'."\n";
	print '<div class="tagtr table-border-row">'."\n";
		$editmode = (GETPOST('action', 'aZ09') == 'edit'.$note_public);
		print '<div class="tagtd tagtdnote tdtop'.($editmode ? '' : ' sensiblehtmlcontent').' table-key-border-col'.(empty($cssclass) ? '' : ' '.$cssclass).'"'.($colwidth ? ' style="width: '.$colwidth.'%"' : '').'>'."\n";
		print $form->editfieldkey("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, $moreparam, '', 0);
		print '</div>'."\n";
	print '<div class="tagtd wordbreak table-val-border-col'.($editmode ? '' : ' sensiblehtmlcontent').'">'."\n";
		print $form->editfieldval("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, '', null, null, $moreparam, 1)."\n";
		print '</div>'."\n";
	print '</div>'."\n";
if (empty($user->socid)) {
	// Private notes (always hidden to external users)
	print '<div class="tagtr table-border-row">'."\n";
	$editmode = (GETPOST('action', 'aZ09') == 'edit'.$note_private);
	print '<div class="tagtd tagtdnote tdtop'.($editmode ? '' : ' sensiblehtmlcontent').' table-key-border-col'.(empty($cssclass) ? '' : ' '.$cssclass).'"'.($colwidth ? ' style="width: '.$colwidth.'%"' : '').'>'."\n";
	print $form->editfieldkey("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, $moreparam, '', 0);
	print '</div>'."\n";
	print '<div class="tagtd wordbreak table-val-border-col'.($editmode ? '' : ' sensiblehtmlcontent').'">'."\n";
	print $form->editfieldval("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, '', null, null, $moreparam, 1);
	print '</div>'."\n";
	print '</div>'."\n";
}
print '</div>'."\n";
?>
<!-- END PHP TEMPLATE NOTES-->
