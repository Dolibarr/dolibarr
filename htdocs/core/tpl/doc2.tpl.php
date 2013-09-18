<?php
/*
 * Confirm suppression
 */
if ($action == 'delete') {
    $ret = $form->form_confirm(
        $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&linkid=' . GETPOST('linkid', 'int'),
       $langs->trans('DeleteFile'),
       $langs->trans('ConfirmDeleteFile'),
       'confirm_deletefile',
       '',
       0,
       1
   );
    if ($ret == 'html') print '<br>';
}

$formfile=new FormFile($db);

// Show upload form
$formfile->form_attach_new_file(
    $_SERVER["PHP_SELF"].'?id='.$object->id,
    '',
    0,
    0,
    $permission,
    50,
    $object
);

// List of document
$formfile->list_of_documents(
    $filearray,
    $object,
    $modulepart,
    $param,
    0,
    '',
    $permission
);

print "<br>";
//List of links
$formfile->listOfLinks($object, $permission, $action, GETPOST('linkid', 'int'));
print "<br>";
