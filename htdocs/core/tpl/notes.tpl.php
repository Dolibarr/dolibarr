<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2013 Florian Henry	      <florian.henry@open-concept.pro>
 * Copyright (C) 2014 Laurent Destailleur <eldy@destailleur.fr>
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

// $cssclass must be defined by caller. For example cssclass='fieldtitle"
$module = $object->element;
$note_public = 'note_public';
$note_private = 'note_private';

$colwidth=(isset($colwidth)?$colwidth:(empty($cssclass)?'25':''));

$permission=(isset($permission)?$permission:(isset($user->rights->$module->creer)?$user->rights->$module->creer:0));    // If already defined by caller page
$moreparam=(isset($moreparam)?$moreparam:'');
$value_public=$object->note_public;
$value_private=$object->note_private;
if (! empty($conf->global->MAIN_AUTO_TIMESTAMP_IN_PUBLIC_NOTES))
{
	$stringtoadd=dol_print_date(dol_now(), 'dayhour').' '.$user->getFullName($langs).' --';
	if (GETPOST('action') == 'edit'.$note_public)
	{
		$value_public=dol_concatdesc($value_public, ($value_public?"\n":"")."-- ".$stringtoadd);
		if (dol_textishtml($value_public)) $value_public.="<br>\n";
		else $value_public.="\n";
	}
}
if (! empty($conf->global->MAIN_AUTO_TIMESTAMP_IN_PRIVATE_NOTES))
{
	$stringtoadd=dol_print_date(dol_now(), 'dayhour').' '.$user->getFullName($langs).' --';
	if (GETPOST('action') == 'edit'.$note_private)
	{
		$value_private=dol_concatdesc($value_private, ($value_private?"\n":"")."-- ".$stringtoadd);
		if (dol_textishtml($value_private)) $value_private.="<br>\n";
		else $value_private.="\n";
	}
}

// Special cases
if ($module == 'propal')                 { $permission=$user->rights->propale->creer;}
elseif ($module == 'supplier_proposal')  { $permission=$user->rights->supplier_proposal->creer;}
elseif ($module == 'fichinter')          { $permission=$user->rights->ficheinter->creer;}
elseif ($module == 'project')            { $permission=$user->rights->projet->creer;}
elseif ($module == 'project_task')       { $permission=$user->rights->projet->creer;}
elseif ($module == 'invoice_supplier')   { $permission=$user->rights->fournisseur->facture->creer;}
elseif ($module == 'order_supplier')     { $permission=$user->rights->fournisseur->commande->creer;}
elseif ($module == 'societe')     	 	 { $permission=$user->rights->societe->creer;}
elseif ($module == 'contact')     		 { $permission=$user->rights->societe->creer;}
elseif ($module == 'shipping')    		 { $permission=$user->rights->expedition->creer;}
elseif ($module == 'product')    		 { $permission=$user->rights->produit->creer;}
//else dol_print_error('','Bad value '.$module.' for param module');

if (! empty($conf->global->FCKEDITOR_ENABLE_SOCIETE)) $typeofdata='ckeditor:dolibarr_notes:100%:200::1:12:95%';	// Rem: This var is for all notes, not only thirdparties note.
else $typeofdata='textarea:12:95%';

?>

<!-- BEGIN PHP TEMPLATE NOTES -->
<div class="tagtable border table-border centpercent">
<?php if ($module != 'product') {   // No public note yet on products ?>
	<div class="tagtr table-border-row">
		<div class="tagtd tdtop table-key-border-col<?php echo (empty($cssclass)?'':' '.$cssclass); ?>"<?php echo ($colwidth ? ' style="width: '.$colwidth.'%"' : ''); ?>><?php echo $form->editfieldkey("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, $moreparam, '', 0); ?></div>
		<div class="tagtd table-val-border-col"><?php echo $form->editfieldval("NotePublic", $note_public, $value_public, $object, $permission, $typeofdata, '', null, null, $moreparam, 1); ?></div>
	</div>
<?php } ?>
<?php if (empty($user->societe_id)) { ?>
	<div class="tagtr table-border-row">
		<div class="tagtd tdtop table-key-border-col<?php echo (empty($cssclass)?'':' '.$cssclass); ?>"<?php echo ($colwidth ? ' style="width: '.$colwidth.'%"' : ''); ?>><?php echo $form->editfieldkey("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, $moreparam, '', 0); ?></div>
		<div class="tagtd table-val-border-col"><?php echo $form->editfieldval("NotePrivate", $note_private, $value_private, $object, $permission, $typeofdata, '', null, null, $moreparam, 1); ?></div>
	</div>
<?php } ?>
</div>
<!-- END PHP TEMPLATE NOTES-->
