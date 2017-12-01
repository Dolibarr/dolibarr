<!-- BEGIN TEMPLATE resource_add.tpl.php -->
<?php

require_once(DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php');

$form = new Form($db);
$formresources = new FormResource($db);

$out  = '<div class="tagtable centpercent noborder allwidth nohover">';

$out .= '<form class="tagtr nohover '.($var==true?'pair':'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="element_type" value="'.$element_type.'">';
$out .= '<input type="hidden" name="element_id" value="'.$element_id.'">';
$out .= '<input type="hidden" name="resource_type" value="'.$resource_obj.'">';


// Resource
$out .= '<div class="tagtd">'.$langs->trans("SelectResource").'</div>';
$out .= '<div class="tagtd">';
$events=array();
$out .= $formresources->select_resource_list('',$resource_obj.'resource_id','',1,1,0,$events,'',2);
$out .= '</div>';

$out .= '<div class="tagtd"><label>'.$langs->trans('Busy').'</label> '.$form->selectyesno('busy',(isset($_POST['busy'])?$_POST['busy']:1),1).'</div>';
$out .= '<div class="tagtd"><label>'.$langs->trans('Mandatory').'</label> '.$form->selectyesno('mandatory',(isset($_POST['mandatory'])?$_POST['mandatory']:0),1).'</div>';


//Buttons
$out .= '<div class="tagtd" align="right">';
$out .= '<input type="submit" class="button" name="save" value="'.$langs->trans("Add").'">';
$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
$out .= '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
$out .= '</div>';

$out .='</form>';

$out .= '</div>';
$out .= '<br>';

print $out;
?>
<!-- END TEMPLATE resource_add.tpl.php -->
