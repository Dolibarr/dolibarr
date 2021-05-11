<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}


require_once(DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php');

$form = new Form($db);
$formresources = new FormResource($db);

$out  = '<div class="tagtable centpercent noborder allwidth nohover">';

$out .= '<form class="tagtr nohover '.($var==true?'pair':'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="element" value="'.$element.'">';
$out .= '<input type="hidden" name="element_id" value="'.$element_id.'">';
$out .= '<input type="hidden" name="resource_type" value="'.(empty($resource_type) ? 'dolresource' : $resource_type).'">';


// Place
$out .= '<div class="tagtd">'.$langs->trans("SelectResource").'</div>';
$out .= '<div class="tagtd">';
$events=array();
$out .= $formresources->select_resource_list('','fk_resource','',1,1,0,$events,'',2);
$out .= '</div>';

$out .= '<div class="tagtd"><label>'.$langs->trans('Busy').'</label> '.$form->selectyesno('busy',(isset($_POST['busy'])?$_POST['busy']:1),1).'</div>';
$out .= '<div class="tagtd"><label>'.$langs->trans('Mandatory').'</label> '.$form->selectyesno('mandatory',(isset($_POST['mandatory'])?$_POST['mandatory']:0),1).'</div>';

$out .= '<div class="tagtd" align="right">';
$out .='<input type="submit" id="add-resource-place" class="button" value="'.$langs->trans("Add").'"/>';
$out .= '</div>';

$out .='</form>';

$out .= '</div>';
$out .= '<br>';

print $out;
?>
<!-- END TEMPLATE resource_add.tpl.php -->
