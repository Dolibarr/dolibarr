<?php
// Envoi fichier
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($object->id)
    {
        dol_add_file_process($upload_dir,0,1,'userfile');
    }
} elseif (GETPOST('linkit') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
    if ($object->id) {
        $link = GETPOST('link', 'alpha');
        if (substr($link, 0, 7) != 'http://' && substr($link, 0, 8) != 'https://') {
            $link = 'http://' . $link;
        }
        dol_add_file_process($upload_dir,0,1,'userfile', $link);
    }
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
    if ($object->id)
    {
        $file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

        $ret = dol_delete_file($file,0,0,0,$object);
        if ($ret) {
            setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
        } else {
            setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
        }
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        exit;
    }
}

