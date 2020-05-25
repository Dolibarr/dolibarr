<?php
// Add line to select existing file
if (empty($conf->global->EXPENSEREPORT_DISABLE_ATTACHMENT_ON_LINES))
{
    print '<!-- expensereport_linktofile.tpl.php -->'."\n";

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref);
    $arrayoffiles = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png|'.preg_quote(dol_sanitizeFileName($object->ref.'.pdf'), '/').')$');
    $nbFiles = count($arrayoffiles);
    $nbLinks = Link::count($db, $object->element, $object->id);

    if ($nbFiles > 0)
    {
        print '<tr class="trattachnewfilenow'.(empty($tredited) ? ' oddeven nohover' : ' '.$tredited).'"'.(!GETPOSTISSET('sendit') && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? ' style="display: none"' : '').'>';
        print '<td colspan="'.$colspan.'">';
        //print '<span class="opacitymedium">'.$langs->trans("AttachTheNewLineToTheDocument").'</span><br>';
        $modulepart = 'expensereport'; $maxheightmini = 48;
        $relativepath = (!empty($object->ref) ?dol_sanitizeFileName($object->ref) : '').'/';
        $filei = 0;
        foreach ($arrayoffiles as $file)
        {
            $urlforhref = array();
            $filei++;

            print '<div class="inline-block margintoponly marginleftonly marginrightonly center valigntop">';
            $fileinfo = pathinfo($file['fullname']);
            if (image_format_supported($file['name']) > 0)
            {
                $minifile = getImageFileNameForSize($file['name'], '_mini'); // For new thumbs using same ext (in lower case however) than original
                //print $file['path'].'/'.$minifile.'<br>';
                $urlforhref = getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));
                if (empty($urlforhref)) {
                    $urlforhref = DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($fileinfo['relativename'].'.'.strtolower($fileinfo['extension']));
                    print '<a href="'.$urlforhref.'" class="aphoto" target="_blank">';
                } else {
                    print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
                }
                print '<div class="photoref backgroundblank">';
                print '<img class="photoexpensereport photorefcenter" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($relativepath.$minifile).'" title="">';
                print '</div>';
                print '</a>';
            }
            else
            {
                $error = 0;
                $thumbshown = '';

                if (preg_match('/\.pdf$/i', $file['name']))
                {
                    $urlforhref = getAdvancedPreviewUrl($modulepart, $relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));

                    $filepdf = $conf->expensereport->dir_output.'/'.$relativepath.$file['name'];
                    $fileimage = $conf->expensereport->dir_output.'/'.$relativepath.$file['name'].'_preview.png';
                    $relativepathimage = $relativepath.$file['name'].'_preview.png';

                    $pdfexists = file_exists($filepdf);

                    if ($pdfexists)
                    {
                        // Conversion du PDF en image png si fichier png non existant
                        if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf)))
                        {
                            if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))		// If you experience trouble with pdf thumb generation and imagick, you can disable here.
                            {
                                include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                                $ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
                                if ($ret < 0) $error++;
                            }
                        }
                    }

                    if ($pdfexists && !$error)
                    {
                        $heightforphotref = 70;
                        if (!empty($conf->dol_optimize_smallscreen)) $heightforphotref = 60;
                        // If the preview file is found
                        if (file_exists($fileimage))
                        {
                            $thumbshown = '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
                        }
                    }
                }

                if (empty($urlforhref) || empty($thumbshown)) {
                    print '<a href="#" class="aphoto" target="_blank">';
                } else {
                    print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
                }
                print '<div class="photoref backgroundblank">';

                print $thumbshown ? $thumbshown : img_mime($minifile);

                print '</div></a>';
            }
            print '<br>';
            $checked = '';
            //var_dump(GETPOST($file['relativename'])); var_dump($file['relativename']); var_dump($_FILES['userfile']['name']);
            // If a file was just uploaded, we check to preselect it
            if (is_array($_FILES['userfile']['name'])) {
	            foreach ($_FILES['userfile']['name'] as $tmpfile)
	            {
	                if ($file['relativename'] == (GETPOST('savingdocmask', 'alpha') ? dol_sanitizeFileName($object->ref.'-') : '').$tmpfile)
	                {
	                    $checked = ' checked';
	                    break;
	                }
	                elseif ($file['relativename'] && in_array($file['relativename'], GETPOST('attachfile', 'array'))) {
	                    $checked = ' checked';
	                    break;
	                }
	            }
            }
            // If we edit a line already linked, then $filenamelinked is defined to the filename (without path) of linked file
            if (!empty($filenamelinked) && $filenamelinked == $file['relativename'])
            {
                $checked = ' checked';
            }
            print '<div class="margintoponly maxwidth150"><input type="checkbox"'.$checked.' id="radio'.$filei.'" name="attachfile[]" class="checkboxattachfile" value="'.$file['relativename'].'">';
            print '<label class="wordbreak checkboxattachfilelabel" for="radio'.$filei.'"> '.$file['relativename'].'</label>';
            print '</div>';

            print '</div>';
        }

        print '<script>';
        print '$(document).ready(function() {';
        print "$('.checkboxattachfile').on('change', function() { $('.checkboxattachfile').not(this).prop('checked', false); });";
        print '});';
        print '</script>';

        print '</td></tr>';
    }
    else
    {
        print '<tr class="oddeven nohover trattachnewfilenow"'.(!GETPOSTISSET('sendit') && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? ' style="display: none"' : '').'>';
        print '<td colspan="'.$colspan.'">';
        print '<span class="opacitymedium">'.$langs->trans("NoFilesUploadedYet").'</span>';
        print '</td></tr>';
    }
}
