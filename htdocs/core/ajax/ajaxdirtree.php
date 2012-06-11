<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       htdocs/core/ajax/ajaxdirtree.php
 *      \ingroup    ecm
 *      \brief      This script returns content of a directory for filetree
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

$res=@include("../../main.inc.php");
include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
include_once(DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php');
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php');
include_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");

$openeddir = GETPOST('openeddir');
$modulepart= GETPOST('modulepart');
$selecteddir = urldecode(GETPOST('dir'));        // relative patch. We must keep the urldecode here because para comes from jqueyrFileTree that url encode it.
if ($selecteddir != '/') $selecteddir = preg_replace('/\/$/','',$selecteddir);    // We removed last '/' except if it is '/'

$langs->load("ecm");

// Define selecteddir (fullpath).
if ($modulepart == 'ecm') $fullpathselecteddir=$conf->ecm->dir_output.'/'.($selecteddir != '/' ? $selecteddir : '');


// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$fullpathselecteddir) || preg_match('/[<>|]/',$fullpathselecteddir))
{
    dol_syslog("Refused to deliver file ".$original_file);
    // Do no show plain path in shown error message
    dol_print_error(0,$langs->trans("ErrorFileNameInvalid",GETPOST("file")));
    exit;
}

// Check permissions
if ($modulepart == 'ecm')
{
    if (! $user->rights->ecm->read) accessforbidden();
}



/*
 * View
 */

$userstatic=new User($db);
$form=new Form($db);
$ecmdirstatic = new EcmDirectory($db);

// Load full tree. We will use it to define nbofsubdir and nboffilesinsubdir
if (empty($sqltree)) $sqltree=$ecmdirstatic->get_full_arbo(0);

// Try to find key into $sqltree
$current_ecmdir_id=-1;
foreach($sqltree as $keycursor => $val)
{
    //print $val['fullrelativename']." == ".$selecteddir;
    if ($val['fullrelativename'] == $selecteddir)
    {
        $current_ecmdir_id = $keycursor;
    }
}

//var_dump($sqltree);

if( file_exists($fullpathselecteddir) )
{
	$files = @scandir($fullpathselecteddir);
    if ($files)
    {
    	natcasesort($files);
    	if( count($files) > 2 )    /* The 2 accounts for . and .. */
    	{
    		echo "<ul class=\"ecmjqft\" style=\"display: none;\">\n";

    		// All dirs
    		foreach( $files as $file )    // $file can be '.', '..', or 'My dir' or 'My file'
    		{
    		    if ($file == 'temp') continue;

    	        $nbofsubdir=0;
    	        $nboffilesinsubdir=0;

    		    // Try to find key into $sqltree
    	        $val=array();
		        foreach($sqltree as $key => $tmpval)
		        {
    	            //print "-- ".$val['fullrelativename']." vs ".(($selecteddir != '/'?$selecteddir.'/':'').$file).'<br>';
		            if ($tmpval['fullrelativename'] == (($selecteddir != '/'?$selecteddir.'/':'').$file))
		            {
		                $val=$tmpval;
                        $resarray=tree_showpad($sqltree,$key,1);
                        $a=$resarray[0];
                        $nbofsubdir=$resarray[1];
                        $nboffilesinsubdir=$resarray[2];
                        break;
		            }
		        }

		        //if (file_exists($fullpathselecteddir . $file) && $file != '.' && $file != '..' && is_dir($fullpathselecteddir . $file))
    		    if ($file != '.' && $file != '..' && ((! empty($val['fullrelativename']) && $val['id'] >= 0) || dol_is_dir($fullpathselecteddir . $file)))
    		    {
    				print '<li class="directory collapsed">';

    				print "<a class=\"fmdirlia jqft ecmjqft\" href=\"#\" rel=\"" . dol_escape_htmltag($val['fullrelativename'].'/') . "\" id=\"fmdirlia_id_".$val['id']."\"";
    				print " onClick=\"loadandshowpreview('".dol_escape_js($val['fullrelativename'])."',".$val['id'].")\">";
    				print dol_escape_htmltag($file);
    				print "</a>";

    				print '<div class="ecmjqft">';

    				print '<table class="nobordernopadding"><tr>';

    				/*print '<td align="left">';
    				print dol_escape_htmltag($file);
    				print '</td>';*/

    				// Nb of docs
    				print '<td align="right">';
    				print isset($val['cachenbofdoc'])?$val['cachenbofdoc']:'&nbsp;';
    				print '</td>';
    				print '<td align="left">';
    				if ($nbofsubdir && $nboffilesinsubdir) print '<font color="#AAAAAA">+'.$nboffilesinsubdir.'</font> ';
    				print '</td>';

    				// Edit link
    				print '<td align="right" width="18"><a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$val['id'].'&relativedir='.urlencode($val['fullrelativename']).'">'.img_view('',$langs->trans("Edit").' - '.$langs->trans("View")).'</a></td>';

    				// Add link
    				//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
    				//print '<td align="right" width="14">&nbsp;</td>';

    				// Info
    				print '<td align="right" width="18">';
    				$userstatic->id=$val['fk_user_c'];
    				$userstatic->lastname=$val['login_c'];
    				$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
    				$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
    				$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1).'<br>';
    				$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dol_print_date($val['date_c'],"dayhour").'<br>';
    				$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
    				$htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.$val['cachenbofdoc'].'<br>';
    				if ($nbofsubdir) $htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
    				else $htmltooltip.='<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.$nbofsubdir.'<br>';
    				print $form->textwithpicto('',$htmltooltip,1,"info");
    				print "</td>";

    				print "</tr></table>\n";
                    print '</div>';

                    //print '<div>&nbsp;</div>';
    				print "</li>\n";
    			}
    		}

    		// Enable jquery handlers on new generated HTML objects
            print '<script type="text/javascript">';
            print 'jQuery(".classfortooltip").tipTip({ maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});';
            print 'jQuery(".fmdirlia").click(function(e) {
            			id=jQuery(this).attr(\'id\').substr(12);
            			jQuery("#formuserfile_section_dir").val(jQuery(this).attr(\'rel\'));
            			jQuery("#formuserfile_section_id").val(id);
    				});';
            print '</script>';

    		echo "</ul>\n";

    	}
    }
    else print "PermissionDenied";
}

// This ajax service is called only when a directory $selecteddir is opened but not closed.
//print '<script language="javascript">';
//print "loadandshowpreview('".dol_escape_js($selecteddir)."');";
//print '</script>';

if (is_object($db)) $db->close();
?>