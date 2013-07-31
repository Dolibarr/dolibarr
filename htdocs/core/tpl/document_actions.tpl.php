<?php
// Send file/link
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
    if ($object->id) {
        dol_add_file_process($upload_dir, 0, 1, 'userfile');
    }
} elseif (GETPOST('linkit') && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
    if ($object->id) {
        $link = GETPOST('link', 'alpha');
        if ($link) {
            if (substr($link, 0, 7) != 'http://' && substr($link, 0, 8) != 'https://') {
                $link = 'http://' . $link;
            }
            dol_add_file_process($upload_dir, 0, 1, 'userfile', $link);
        }
    }
}


// Delete file/link
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
    if ($object->id) {
        $urlfile = GETPOST('urlfile', 'alpha');
        $linkid = GETPOST('linkid', 'int');
        if ($urlfile) {
            $file = $upload_dir . "/" . $urlfile;	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) {
                setEventMessage($langs->trans("FileWasRemoved", $urlfile));
            } else {
                setEventMessage($langs->trans("ErrorFailToDeleteFile", $urlfile), 'errors');
            }
        } elseif ($linkid) {
            require_once DOL_DOCUMENT_ROOT . '/link/class/link.class.php';
            $link = new Link($db);
            $link->id = $linkid;
            $link->fetch();
            $res = $link->delete($user);
            $langs->load('link');
            if ($res) {
                setEventMessage($langs->trans("LinkRemoved", $link->label));
            } else {
                setEventMessage($langs->trans("ErrorFailedToDeleteLink", $link->label), 'errors');
            }
        }
        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
        exit;
    }
} elseif ($action == 'confirm_updateline' && GETPOST('save') && GETPOST('link', 'alpha')) {
    require_once DOL_DOCUMENT_ROOT . '/link/class/link.class.php';
    $langs->load('link');
    $link = new Link($db);
    $link->id = GETPOST('linkid', 'int');
    $f = $link->fetch();
    if ($f) {
        $link->url = GETPOST('link', 'alpha');
        if (substr($link->url, 0, 7) != 'http://' && substr($link->url, 0, 8) != 'https://') {
            $link->url = 'http://' . $link->url;
        }
        $link->label = GETPOST('label', 'alpha');
        $res = $link->update($user);
        if (!$res) {
            setEventMessage($langs->trans("ErrorFailedToUpdateLink", $link->label));
        }
    } else {
        //error fetching
    }
}

