<?php

// BEGIN TPL RESOURCE_ADD.TPL.PHP

require_once(DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php');

$form = new Form($db);
$formresources = new FormResource($db);

$out .= '<div class="tagtable centpercent border allwidth nohover">';

$out .= '<form class="tagtr '.($var==true?'pair':'impair').'" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
$out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
$out .= '<input type="hidden" name="action" value="add_element_resource">';
$out .= '<input type="hidden" name="element" value="'.$element.'">';
$out .= '<input type="hidden" name="element_id" value="'.$element_id.'">';
$out .= '<input type="hidden" name="resource_type" value="'.(empty($resource_type) ? 'resource' : $resource_type).'">';


// Place
$out .= '<div class="tagtd">'.$langs->trans("SelectResource").'</div><div>';
$events=array();
$out .= $formresources->select_resource_list('','fk_resource','',1,1,0,$events,'',2);
$out .= '</div>';

$out .= '<div class="tagtd"><label>'.$langs->trans('Busy').'</label> '.$form->selectyesno('busy',$linked_resource['busy']?1:0,1).'</div>';
$out .= '<div class="tagtd"><label>'.$langs->trans('Mandatory').'</label> '.$form->selectyesno('mandatory',$linked_resource['mandatory']?1:0,1).'</div>';

$out .= '<div class="tagtd" align="right">';
$out .='<input type="submit" id="add-resource-place" class="button" value="'.$langs->trans("Add").'"/>';
$out .= '</div>';

$out .='</form>';

$out .= '</div>';
$out .= '<br />';

print $out;

// END BEGIN TPL RESOURCE_ADD.TPL.PHP
