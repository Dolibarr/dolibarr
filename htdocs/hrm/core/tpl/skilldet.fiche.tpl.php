<?php

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}
/*
// $permissionnote 	must be defined by caller. For example $permissionnote=$user->rights->module->create
// $cssclass   		must be defined by caller. For example $cssclass='fieldtitle'
$module       = $object->element;
$note_public  = 'note_public';
$note_private = 'note_private';

$colwidth = (isset($colwidth) ? $colwidth : (empty($cssclass) ? '25' : ''));
// Set $permission from the $permissionnote var defined on calling page
$permission = (isset($permissionnote) ? $permissionnote : (isset($permission) ? $permission : (isset($user->rights->$module->create) ? $user->rights->$module->create : (isset($user->rights->$module->creer) ? $user->rights->$module->creer : 0))));
$moreparam = (isset($moreparam) ? $moreparam : '');
$value_public = $object->note_public;
$value_private = $object->note_private;
if (!empty($conf->global->MAIN_AUTO_TIMESTAMP_IN_PUBLIC_NOTES)) {
$stringtoadd = dol_print_date(dol_now(), 'dayhour').' '.$user->getFullName($langs).' --';
if (GETPOST('action', 'aZ09') == 'edit'.$note_public) {
$value_public = dol_concatdesc($value_public, ($value_public ? "\n" : "")."-- ".$stringtoadd);
if (dol_textishtml($value_public)) {
$value_public .= "<br>\n";
} else {
$value_public .= "\n";
}
}
}
if (!empty($conf->global->MAIN_AUTO_TIMESTAMP_IN_PRIVATE_NOTES)) {
$stringtoadd = dol_print_date(dol_now(), 'dayhour').' '.$user->getFullName($langs).' --';
if (GETPOST('action', 'aZ09') == 'edit'.$note_private) {
$value_private = dol_concatdesc($value_private, ($value_private ? "\n" : "")."-- ".$stringtoadd);
if (dol_textishtml($value_private)) {
$value_private .= "<br>\n";
} else {
$value_private .= "\n";
}
}
}*/
/*
// Special cases
if ($module == 'propal') {
$permission = $user->rights->propale->creer;
} elseif ($module == 'supplier_proposal') {
$permission = $user->rights->supplier_proposal->creer;
} elseif ($module == 'fichinter') {
$permission = $user->rights->ficheinter->creer;
} elseif ($module == 'project') {
$permission = $user->rights->projet->creer;
} elseif ($module == 'project_task') {
$permission = $user->rights->projet->creer;
} elseif ($module == 'invoice_supplier') {
if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
$permission = $user->rights->fournisseur->facture->creer;
} else {
$permission = $user->rights->supplier_invoice->creer;
}
} elseif ($module == 'order_supplier') {
if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
$permission = $user->rights->fournisseur->commande->creer;
} else {
$permission = $user->rights->supplier_order->creer;
}
} elseif ($module == 'societe') {
$permission = $user->rights->societe->creer;
} elseif ($module == 'contact') {
$permission = $user->rights->societe->creer;
} elseif ($module == 'shipping') {
$permission = $user->rights->expedition->creer;
} elseif ($module == 'product') {
$permission = $user->rights->produit->creer;
} elseif ($module == 'ecmfiles') {
$permission = $user->rights->ecm->setup;
}*/
//else dol_print_error('','Bad value '.$module.' for param module');

if (!empty($object->table_element_line)) {
	// Show object lines
	$result = $object->getLinesArray();
}



print '<!-- BEGIN PHP TEMPLATE SKILLDET -->'."\n";

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
