<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}


require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

$form = new Form($db);
$formresources = new FormResource($db);

$out = '';

$out .= '<div class="centpercent allwidth nohover">';

$out .= '<form class="nohover '.($var == true ? 'pair' : 'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
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
$out .= $formresources->select_resource_list('', 'fk_resource', '', 1, 1, 0, $events, '', 2, null);
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
