<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php

require_once(DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php');

$form = new Form($db);
$formresources = new FormResource($db);

$out  = '<div class="tagtable centpercent border allwidth nohover">';

$out .= '<form class="tagtr '.($var==true?'pair':'impair').'" action="'.$_SERVER['PHP_SELF']."?element_type=".$element_type."&element_id=".$element_id.'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="resource_type" value="'.$resource_obj.'">';

// Resource
$preselection = $resource_obj == $resource_type ? $selected : '';
$out .= '<div class="tagtd">'.$langs->trans("SelectResource").'</div><div>';
$out .= $formresources->select_resource_list($preselection, $resource_obj.'resource_id', '', 1, 0, array(), 2);

// Parent
$outputmode = !empty($preselection) && ($element_type == "product" || $element_type == 'service')?0:2;
if ($outputmode == 0) $out .= ' '.$langs->trans("In").' ';
$out .= $formresources->select_tree_resources($filtered_tree, $root_excluded, $parent, 'parent', 64, $outputmode);
$out .= '</div>';

// Dependency mode
if ($element_type == "product" || $element_type == 'service') {
	$out .= '<div class="tagtd">';
	$out .= '<label>'.$langs->trans('Dependency').'</label>';
	$out .= $form->selectarray('dependency', $dependency_modes);
	$out .= '</div>';
} else {
	$out .= '<input type="hidden" name="dependency" value="0">'; //Default
}

//Mandatory
$out .= '<div class="tagtd">';
$out.= '<label>'.$langs->trans('Mandatory').'</label>';
$out.= $form->selectyesno('mandatory',$mandatory==""?1:$mandatory,1);
$out.= '</div>';

//Buttons
$out .= '<div class="tagtd" align="right">';
$out .= '<input type="submit" class="button" name="save" value="'.$langs->trans("Add").'">';
$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$out .= '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
$out .= '</div>';

$out .='</form>';

$out .= '</div>';
$out .= '<br>';

// This code reloads the page depending of selected option
$out .= '<script type="text/javascript">
	jQuery(document).ready(run);
	function run() {
		jQuery("#'.$resource_obj.'resource_id").change(function () {
			window.location = "'.$_SERVER["PHP_SELF"].'?element_type='.$element_type.'&element_id='.$element_id.'&resource_type='.$resource_obj.'&selected=" + $("#'.$resource_obj.'resource_id").val();
		});
	}
</script>';

print $out;
?>
<!-- END TEMPLATE resource_add.tpl.php -->
