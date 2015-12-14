<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Mrsof                <virtualsof@yahoo.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/admin/translation.php
 *       \brief      Page to show translation information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("sms");
$langs->load("other");
$langs->load("errors");

if (!$user->admin) accessforbidden();


$action=GETPOST('action', 'alpha');
$iso_code=GETPOST('iso_code', 'alpha');
if (empty($iso_code))
    $iso_code = $langs->defaultlang;
$filename=GETPOST('filename','alpha');
$cancel=GETPOST('cancel','alpha');

if (!empty($cancel)) $action='';

// Langs directory
$langsdir = DOL_DOCUMENT_ROOT . "/langs/$iso_code/";
$langsbak = DOL_DATA_ROOT . "/langs/$iso_code/";
/*
 * Actions
 */

if ($action == 'update' && !empty($filename))
{
    $error = 0;
    $trans_keys = array();

    // Capture all _POST that contain "trans_key_"
    foreach($_POST as $key => $value)
    {
        if (substr($key, 0, 10) == 'trans_key_') $trans_keys[substr($key, 10)] = $value;
    }

    // Write new file
    $handle=opendir($langsdir);
    dol_syslog("Translation::Update open directory " . $langsdir . " handle=" . $handle);
    if (!is_resource($handle))
    {
        $error++;
        setEventMessages($langs->trans('ErrorResourceHandle'), null, 'errors');
    }
    elseif (!is_writable($langsdir))
    {
        $error++;
        $mesgs = array($langsdir);
        setEventMessages($langs->trans('ErrorDirectoryNotWritable'), $mesgs, 'errors');
    }
    else
    {
        if (!empty(GETPOST("backup")))
        {
            if (!dol_is_dir($langsbak))
            {
                if (dol_mkdir($langsbak) < 0)
                {
                    $error++;
                    $msgs = array($langsbak);
                    setEventMessages($langs->trans('ErrorCreateDirectory'), $msgs, 'errors');
                }
                elseif (!is_writable($langsbak))
                {
                    $error++;
                    $mesgs = array($langsbak);
                    setEventMessages($langs->trans('ErrorDirectoryNotWritable'), $mesgs, 'errors');
                }
                else
                {
                    $langscopy = $langsbak.$filename.'-'.date("Y-m-d-H-m-s").'.lang';
                    if (!dol_copy($langsdir.$filename.'.lang', $langscopy))
                    {
                        $error++;
                        $mesgs = array($langscopy);
                        setEventMessages($langs->trans('ErrorCopyFile'), $mesgs, 'errors');
                    }
                }
            }
        }

        if (!$error)
        {
            if (!dol_move($langsdir.$filename.'.lang', $langsdir.$filename.'.tmp'))
            {
                $error++;
                $mesgs = array($langsdir.$filename.'.tmp');
                setEventMessages($langs->trans('ErrorRenameFile'), $mesgs, 'errors');
            }
        }
    }


    if (!$error)
    {
        $langfile = $filename.'.lang';
        $tempfile = $filename.'.tmp';

        $langfileres = fopen($langsdir.$langfile,"w");
        $tempfileres = fopen($langsdir.$tempfile,"r");

        if ($tempfileres && $langfileres)
        {
            while (!feof($tempfileres))
            {
                $buffer = fgets($tempfileres, 4096);

                if (substr($buffer, 0, 1) == '#')
                {
                    fwrite($langfileres, $buffer);
                }
                else
                {
                    $transline = explode('=', $buffer, 2);
                    if (array_key_exists(trim($transline[0]), $trans_keys))
                    {
                        $line = $transline[0].'='.$trans_keys[trim($transline[0])];
                        fwrite($langfileres, $line.PHP_EOL);
                    }
                }
            }
            if (!dol_delete_file($langsdir.$tempfile))
            {
                $error++;
                $mesgs = array($tempfile);
                setEventMessages($langs->trans('ErrorDeletingFile'), $mesgs, 'errors');
            }
            fclose($tempfileres);
            fclose($langfileres);
        }
        closedir($handle);
        // Success
        setEventMessages($langs->trans('FileSuccessfullyModified'), null);
    }
}

/*
 * View
 */

$wikihelp='EN:Setup|FR:Paramétrage|ES:Configuración';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("TranslationSetup"),'','title_setup');

print $langs->trans("TranslationDesc")."<br>\n";
print "<br>\n";

print img_warning().' '.$langs->trans("SomeTranslationAreUncomplete").'<br>';

$urlwikitranslatordoc='http://wiki.dolibarr.org/index.php/Translator_documentation';
print $langs->trans("SeeAlso").': <a href="'.$urlwikitranslatordoc.'" target="_blank">'.$urlwikitranslatordoc.'</a><br>';

print '<br>';

// List of files in the lang dir selected
$langsfilesfound = 0;
$langsfilesarray = array();

$langsfilesdir = dol_dir_list($langsdir,'files',0);
foreach ($langsfilesdir as $key => $langsfile)
{
    $langsfilename = $langsfile['name'];
    if (preg_match('/\.lang$/i',$langsfilename))
    {
        $langsfilesfound++;
        $langsfilesarray[]=substr($langsfilename, 0, dol_strlen($langsfilename) - 5);
    }
}

$form=new Form($db);
$formadmin=new FormAdmin($db);

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
print '<input type="hidden" name="action" value="edit">';
if (empty($conf->global->MAIN_MULTILANGS))
{
    // Only main lang is editable
    print $langs->trans("CurrentUserLanguage").': <strong>'.$langs->defaultlang.'</strong>';
    print $form->textwithpicto('',$langs->trans('ActivateMultilangsToModifyAnotherLanguage'),1);
    print '<input type="hidden" name="iso_code" value="'.$langs->defaultlang.'">';
}
else
{
    // Multilangs is activated so we can choose which language to edit
    print $formadmin->select_language(!empty($iso_code)?$iso_code:$conf->global->MAIN_LANG_DEFAULT, 'iso_code', 0, 0, 0, 0, 0, 'minwidth300');
    print '<button type="submit" class="butAction">'.$langs->trans("SelectLanguage").'</button>';
    print '<br>';
}
print '<br>';
// Select lang file to edit
print $form->selectarray('filename', $langsfilesarray, GETPOST('filename', 'alpha'), 1, 0, 1, '', 0, 0, 0, 0, '', 1);
print '<button type="submit" class="butAction">'.$langs->trans("Edit").'</button>';
print '('.$langsfilesfound.' '.$langs->trans('Files').')';
print '</form>';
print '<br>';

if ($action == 'edit')
{
    if (!empty($filename) && $filename != -1)
    {
        print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="filename" value="'.$filename.'">';
        print '<input type="hidden" name="action" value="update">';

        $langs->load($filename);

        print '<table class="tagtable liste">'."\n";
        print '<caption class="titre">';
        print $langs->trans("File").' : <strong>'.$langs->trans($filename).".lang</strong>";
        print '</caption>';
        print '<thead>'."\n";
        print '<tr class="liste_titre">'."\n";
        print '  <th width="400px">'.$langs->trans("Label").'</th>';
        print '  <th width="20px"></th>';
        print '  <th width="500px" align="left">'.$langs->trans("Value").'</th>';
        print "</tr>\n";
        print '</thead>'."\n";
        print '<tbody>'."\n";

        $file = $filename.'.lang';
        $fp = fopen($langsdir.$file,"r");
        if ($fp)
        {
            $var=true;

            while (!feof($fp))
            {
                $buffer = fgets($fp, 4096);
                if (substr($buffer, 0, 1) <> '#')
                {
                    if (trim($buffer) != '')
                    {
                        $var=!$var;
                        print '<tr '.$bc[$var].'>';

                        $transline = explode('=', $buffer, 2);
                        $name_transline = trim($transline[0]);
                        $content_transline = dol_escape_htmltag(trim($transline[1]));

                        print '<td class="nowrap"><strong>'.$name_transline.'</strong></td>'."\n";
                        print '<td> = </td>';
                        print '<td class="nowrap">';

                        $alertsubs = (preg_match('/\%s/i',$content_transline)) ? true : false;

                        if (dol_strlen($content_transline) > 300) {
                            print '<textarea name="trans_key_'.$name_transline.'" class="input500';
                            if ($alertsubs) print ' alertsubs';
                            print '">'. $content_transline .'</textarea>';
                        }
                        else
                        {
                            print '<input type="text" name="trans_key_'.$name_transline.'" value="'.$content_transline.'" class="input500';
                            if ($alertsubs) print ' alertsubs';
                            print '">';
                        }

                        if ($alertsubs) print $form->textwithpicto('',$langs->trans('ThisExpressionUseSpecificSyntax%S'),1, 'warning');

                        print '</td>'."\n";
                        print '</tr>';
                    }
                }
            }
            fclose($fp);

        }
        else
        {
            print "<tr><td colspan='2'>".$langs->trans("FilesMissing",$name)."</td>";
            print '<td><div class="error">'.$langs->trans("Error").' Failed to open file '.$langsdir.$file.'</div></td></tr>';
            $error++;
            dol_syslog("Translation: failed to open file " . $langsdir . $file, LOG_ERR);
        }
        print '</tbody>'."\n";
        print '</table>';

        print '<div class="center">';
        print '<input type="checkbox" name="backup" class="flat checkforselect" checked>'.$langs->trans("MakeBackupFile");
        $info =  $langs->trans('BackUpFileInfoText') . ": " .$langsbak;
        print $form->textwithpicto('', $info,1, 'help');
        print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="creation" />';
        print '&nbsp; &nbsp; &nbsp;';
        print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel" />';
        print '</div>';

        print '</form>';
    }
    else
    {
        setEventMessages($langs->trans('NoLanguageFileSelected'), null, 'errors');
    }
}

llxFooter();

$db->close();
