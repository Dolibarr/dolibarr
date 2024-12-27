<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php
/* Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * @var string $element_ref
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

$form = new Form($db);
$formresources = new FormResource($db);

$out = '';

$out .= '<div class="centpercent allwidth nohover">';

$out .= '<form class="nohover '.(!empty($var) && $var == true ? 'pair' : 'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.newToken().'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="element" value="'.$element.'">';
$out .= '<input type="hidden" name="element_id" value="'.$element_id.'">';
$out .= '<input type="hidden" name="ref" value="'.$element_ref.'">';
$out .= '<input type="hidden" name="resource_type" value="'.(empty($resource_type) ? 'dolresource' : $resource_type).'">';

$out .= '<div class="noborder borderbottom">';

// Place
$out .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">'.$langs->trans("SelectResource").'</div>';
$out .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">';
$events = array();
$out .= img_picto('', 'resource', 'class="pictofixedwidth"');
$out .= $formresources->select_resource_list(0, 'fk_resource', [], 1, 1, 0, $events, '', 2, 0);
$out .= '</div>';

$out .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block marginleftonly"><label for="resbusy">'.$langs->trans('Busy').'</label> ';
//$out .= $form->selectyesno('busy', (GETPOSTISSET('busy') ? GETPOST('busy') : 1), 1);
$out .= '<input type="checkbox" id="resbusy" name="busy" value="1"'.(GETPOSTISSET('fk_resource') ? (GETPOST('busy') ? ' checked' : '') : ' checked').'>';
$out .= '</div>';
$out .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block marginleftonly"><label for="resmandatory">'.$langs->trans('Mandatory').'</label> ';
//$out .= $form->selectyesno('mandatory', (GETPOSTISSET('mandatory') ? GETPOST('mandatory') : 0), 1);
$out .= '<input type="checkbox" id="resmandatory" name="mandatory" value="1"'.(GETPOSTISSET('fk_resource') ? (GETPOST('mandatory') ? ' checked' : '') : ' checked').'>';
$out .= '</div>';

$out .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block right">';
$out .= '<input type="submit" id="add-resource-place" class="button button-add small" value="'.$langs->trans("Add").'"/>';
$out .= '</div>';

$out .= '</div>';

$out .= '</form>';

$out .= '</div>';
$out .= '<br>';

print $out;
?>
<!-- END TEMPLATE resource_add.tpl.php -->
