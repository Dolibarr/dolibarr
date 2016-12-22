<?php
/* Copyright (C) 2005-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 *  \file       htdocs/admin/system/filecheck.php
 *  \brief      Page to check Dolibarr files integrity
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");

if (!$user->admin)
    accessforbidden();

$error=0;


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("FileCheckDolibarr"),'','title_setup');

// Version
$var = true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var = ! $var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionLastInstall").'</td><td>'.$conf->global->MAIN_VERSION_LAST_INSTALL.'</td></tr>'."\n";
$var = ! $var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionLastUpgrade").'</td><td>'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</td></tr>'."\n";
$var = ! $var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionProgram").'</td><td>'.DOL_VERSION;
// If current version differs from last upgrade
if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE)) {
    // Compare version with last install database version (upgrades never occured)
    if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_INSTALL)
        print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_INSTALL));
} else {
    // Compare version with last upgrade database version
    if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_UPGRADE)
        print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE));
}
print '</td></tr>'."\n";
print '</table>';
print '<br>';


// Modified or missing files
$file_list = array('missing' => array(), 'updated' => array());

// File to analyze
//$xmlfile = DOL_DOCUMENT_ROOT.'/install/filelist-'.DOL_VERSION.'.xml';
$xmlfile = DOL_DOCUMENT_ROOT.'/install/filelist.xml';

if (file_exists($xmlfile))
{
    $xml = simplexml_load_file($xmlfile);
    if ($xml)
    {
        if (is_object($xml->dolibarr_htdocs_dir[0]))
        {
        	$file_list = array();
            $ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0]);		// Fill array $file_list
    
            print '<table class="noborder">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("FilesMissing") . '</td>';
            print '<td align="center">' . $langs->trans("ExpectedChecksum") . '</td>';
            print '</tr>'."\n";
            $var = true;
            $tmpfilelist = dol_sort_array($file_list['missing'], 'filename');
            if (is_array($tmpfilelist) && count($tmpfilelist))
            {
    	        foreach ($tmpfilelist as $file)
    	        {
    	            $var = !$var;
    	            print '<tr ' . $bc[$var] . '>';
    	            print '<td>'.$file['filename'].'</td>' . "\n";
    	            print '<td align="center">'.$file['expectedmd5'].'</td>' . "\n";
    	            print "</tr>\n";
    	        }
            }
            else 
            {
                print '<tr ' . $bc[false] . '><td colspan="2">'.$langs->trans("None").'</td></tr>';
            }            
            print '</table>';
    
            print '<br>';
    
            print '<table class="noborder">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("FilesUpdated") . '</td>';
            print '<td align="center">' . $langs->trans("ExpectedChecksum") . '</td>';
            print '<td align="center">' . $langs->trans("CurrentChecksum") . '</td>';
            print '<td align="right">' . $langs->trans("Size") . '</td>';
            print '<td align="right">' . $langs->trans("DateModification") . '</td>';
            print '</tr>'."\n";
            $var = true;
            $tmpfilelist = dol_sort_array($file_list['updated'], 'filename');
            if (is_array($tmpfilelist) && count($tmpfilelist))
            {
    	        foreach ($tmpfilelist as $file)
    	        {
    	            $var = !$var;
    	            print '<tr ' . $bc[$var] . '>';
    	            print '<td>'.$file['filename'].'</td>' . "\n";
    	            print '<td align="center">'.$file['expectedmd5'].'</td>' . "\n";
    	            print '<td align="center">'.$file['md5'].'</td>' . "\n";
    	            print '<td align="right">'.dol_print_size(dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename'])).'</td>' . "\n";
    	            print '<td align="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']),'dayhour').'</td>' . "\n";
    	            print "</tr>\n";
    	        }
            }
            else 
            {
                print '<tr ' . $bc[false] . '><td colspan="5">'.$langs->trans("None").'</td></tr>';
            }
            print '</table>';
        }
        else
        {
            print 'Error: Failed to found dolibarr_htdocs_dir into XML file '.$xmlfile;
            $error++;
        }
    }
    else
    {
        print 'Error: Failed to parse XML for input file '.$xmlfile;
        $error++;
    }
}
else
{
    print $langs->trans('XmlNotFound') . ': ' . $xmlfile;
    $error++;
}

llxFooter();

$db->close();

exit($error);


/**
 * Function to get list of updated or modified files.
 * $file_list is used as global variable
 *
 * @param	array				$file_list	Array for response
 * @param   SimpleXMLElement	$dir    	SimpleXMLElement of files to test
 * @param   string   			$path   	Path of file
 * @return  array               			Array of filenames
 */
function getFilesUpdated(&$file_list, SimpleXMLElement $dir, $path = '')
{
    $exclude = 'install';

    foreach ($dir->md5file as $file)
    {
        $filename = $path.$file['name'];

        if (preg_match('#'.$exclude.'#', $filename)) continue;

        if (!file_exists(DOL_DOCUMENT_ROOT.'/'.$filename))
        {
            $file_list['missing'][] = array('filename'=>$filename, 'expectedmd5'=>(string) $file);
        }
        else
		{
            $md5_local = md5_file(DOL_DOCUMENT_ROOT.'/'.$filename);
            if ($md5_local != (string) $file) $file_list['updated'][] = array('filename'=>$filename, 'expectedmd5'=>(string) $file, 'md5'=>(string) $md5_local);
        }
    }

    foreach ($dir->dir as $subdir) getFilesUpdated($file_list, $subdir, $path.$subdir['name'].'/');

    return $file_list;
}
