<!-- BEGIN TEMPLATE resource_view.tpl.php -->
<?php
/* Copyright (C) 2024		MDW	                    <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 *
 * @var string $element
 * @var int $element_id
 * @var string $mode
 * @var string $resource_type
 * @var array<array{rowid:int,resource_id:int,resource_type:string,busy:int<0,1>,mandatory:int<0,1>}> $linked_resources
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force string $element
@phan-var-force int $element_id
@phan-var-force string $resource_type
@phan-var-force array<array{rowid:int,resource_id:int,resource_type:string,busy:int<0,1>,mandatory:int<0,1>}> $linked_resources
';


$form = new Form($db);


print '<div class="tagtable centpercent noborder allwidth">';

print '<form method="POST" class="tagtable centpercent noborder borderbottom allwidth">';

print '<div class="tagtr liste_titre">';
print '<div class="tagtd liste_titre">'.$langs->trans('Resource').'</div>';
print '<div class="tagtd liste_titre">'.$langs->trans('Type').'</div>';
print '<div class="tagtd liste_titre center">'.$langs->trans('Busy').'</div>';
print '<div class="tagtd liste_titre center">'.$langs->trans('Mandatory').'</div>';
print '<div class="tagtd liste_titre"></div>';
print '</div>';

print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="id" value="'.$element_id.'" />';
print '<input type="hidden" name="action" value="update_linked_resource" />';
print '<input type="hidden" name="resource_type" value="'.$resource_type.'" />';

if ((array) $linked_resources && count($linked_resources) > 0) {
	foreach ($linked_resources as $linked_resource) {
		$object_resource = fetchObjectByElement($linked_resource['resource_id'], $linked_resource['resource_type']);

		//$element_id = $linked_resource['rowid'];

		if ($mode == 'edit' && $linked_resource['rowid'] == GETPOSTINT('lineid')) {
			print '<div class="tagtr oddeven">';
			print '<input type="hidden" name="lineid" value="'.$linked_resource['rowid'].'" />';
			print '<input type="hidden" name="element" value="'.$element.'" />';
			print '<input type="hidden" name="element_id" value="'.$element_id.'" />';

			print '<div class="tagtd">'.$object_resource->getNomUrl(1).'</div>';
			print '<div class="tagtd">'.$object_resource->type_label.'</div>';
			print '<div class="tagtd center">'.$form->selectyesno('busy', $linked_resource['busy'] ? 1 : 0, 1).'</div>';
			print '<div class="tagtd center">'.$form->selectyesno('mandatory', $linked_resource['mandatory'] ? 1 : 0, 1).'</div>';
			print '<div class="tagtd right"><input type="submit" class="button" value="'.$langs->trans("Update").'"></div>';
			print '</div>';
		} else {
			$class = '';
			if ($linked_resource['rowid'] == GETPOSTINT('lineid')) {
				$class = 'highlight';
			}

			print '<div class="tagtr oddeven'.($class ? ' '.$class : '').'">';

			print '<div class="tagtd">';
			print $object_resource->getNomUrl(1);
			print '</div>';

			print '<div class="tagtd">';
			print $object_resource->type_label;
			print '</div>';

			print '<div class="tagtd center">';
			print yn($linked_resource['busy']);
			print '</div>';

			print '<div class="tagtd center">';
			print yn($linked_resource['mandatory']);
			print '</div>';

			print '<div class="tagtd right">';
			print '<a class="editfielda marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=edit&token='.newToken().'&resource_type='.$linked_resource['resource_type'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a class="marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&token='.newToken().'&id='.$linked_resource['resource_id'].'&element='.$element.'&element_id='.$element_id.'&lineid='.$linked_resource['rowid'].'">';
			print img_picto($langs->trans("Unlink"), 'unlink');
			print '</a>';
			print '</div>';

			print '</div>';
		}
	}
} else {
	print '<div class="tagtr oddeven">';
	print '<div class="tagtd opacitymedium">'.$langs->trans('NoResourceLinked').'</div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '<div class="tagtd opacitymedium"></div>';
	print '</div>';
}

print '</form>';

print '</div>';

?>
<!-- END TEMPLATE resource_view.tpl.php -->
