<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

$form = new Form($db);
$formresources = new FormResource($db);

$out  = '<div class="tagtable centpercent noborder allwidth nohover">';

$out .= '<form class="tagtr nohover '.($var==true?'pair':'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="element" value="'.$element.'">';
$out .= '<input type="hidden" name="element_id" value="'.$element_id.'">';
$out .= '<input type="hidden" name="ref" value="'.$element_ref.'">';
$out .= '<input type="hidden" name="resource_type" value="'.(empty($resource_type) ? 'dolresource' : $resource_type).'">';

// Resource
$out .= '<div class="tagtd">'.$langs->trans("SelectResource").'</div>';
$out .= '<div class="tagtd">';
$events=array();
$out .= $formresources->select_resource_list($selected, 'fk_resource', '', 1, 1, 0, $events, '', 2, null);

// Parent
$outputmode = !empty($selected) && ($element == "product" || $element == 'service')?0:2;
if ($outputmode == 0) $out .= ' '.$langs->trans("In").' ';
$out .= $formresources->select_tree_resources($filtered_tree, $root_excluded, $parent, 'parent', 64, $outputmode);

//Close resource sel div
$out .= '</div>';

//Mandatory
$out .= '<div class="tagtd"><label>'.$langs->trans('Busy').'</label> ';
$out .= $form->selectyesno('busy',$busy?$busy:1,1);
$out .= '</div>';

//Mandatory
$out .= '<div class="tagtd"><label>'.$langs->trans('Mandatory').'</label> ';
$out .= $form->selectyesno('mandatory',$mandatory==""?1:$mandatory,1);
$out .= '</div>';

$out .= '<div class="tagtd right">';
$out .='<input type="submit" id="add-resource-place" class="button" value="'.$langs->trans("Add").'"/>';
$out .= '</div>';

$out .='</form>';

$out .= '</div>';
$out .= '<br>';

// This code reloads the page depending of selected option
$out .= '<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#fk_resource").change(function () {
			window.location = "'.$_SERVER["PHP_SELF"].'?element='.$element.$element_url.'&selected=" + $(this).val();
		});
	});
</script>';

print $out;
?>
<!-- END TEMPLATE resource_add.tpl.php -->
