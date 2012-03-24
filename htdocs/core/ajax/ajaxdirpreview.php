<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/filemanager/ajaxshowpreview.php
 *  \brief      Service to return a HTML preview of a file
 *  			Call of this service is made with URL:
 * 				ajaxpreview.php?action=preview&modulepart=repfichierconcerne&file=pathrelatifdufichier
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

// C'est un wrapper, donc header vierge
function llxHeader() { }

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
$action=GETPOST("action");
$file=urldecode(GETPOST('file'));
$section=GETPOST("section");
$module=GETPOST("module");
$urlsource=GETPOST("urlsource");
if (! $section)
{
    dol_print_error('',"ErrorSectionParamNotDefined");
    exit;
}

// Suppression de la chaine de caractere ../ dans $original_file
$original_file = str_replace("../","/", $file);
$original_file_osencoded=dol_osencode($original_file);  // New file name encoded in OS encoding charset

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
{
    dol_syslog("Refused to deliver file ".$original_file);
    // Do no show plain path in shown error message
    dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
    exit;
}

// Load ecm object
/*$ecmdir = new EcmDirectory($db);
 $result=$ecmdir->fetch(GETPOST("section"));
if (! $result > 0)
{
dol_print_error($db,$ecmdir->error);
exit;
}
$relativepath=$ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
*/



/*
 * Action
 */

if ($action == 'remove_file')   // Remove a file
{
    clearstatcache();

    dol_syslog(__FILE__." remove $original_file $urlsource", LOG_DEBUG);

    // This test should be useless. We keep it to find bug more easily
    if (! file_exists($original_file_osencoded))
    {
        dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
        exit;
    }

    dol_delete_file($original_file);

    dol_syslog(__FILE__." back to ".urldecode($urlsource), LOG_DEBUG);

    header("Location: ".urldecode($urlsource));

    return;
}



/*
 * View
 */

// Ajout directives pour resoudre bug IE
header('Cache-Control: Public, must-revalidate');
header('Pragma: public');

$filename = basename($original_file_osencoded);
$sizeoffile = filesize($original_file_osencoded);

if (dol_is_dir($original_file))
{
    $type='directory';
}
else
{
    // Define mime type
    $type = 'application/octet-stream';
    if (GETPOST("type") != 'auto') $type=$_GET["type"];
    else $type=dol_mimetype($original_file,'text/plain');
    //print 'X'.$type.'-'.$original_file;exit;
}

clearstatcache();

// Output file on browser
dol_syslog("document.php download $original_file $filename content-type=$type");

// This test if file exists should be useless. We keep it to find bug more easily
if (! file_exists($original_file_osencoded))
{
    dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
    exit;
}

print '<!-- TYPE='.$type.' -->'."\n";
print '<!-- SIZE='.$sizeoffile.' -->'."\n";
print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Les drois sont ok et fichier trouve, et fichier texte, on l'envoie
print '<b><font class="liste_titre">'.$langs->trans("Information").'</font></b><br>';
print '<hr>';

// Dir
if ($type == 'directory')
{
    print '<table class="nobordernopadding">';
    print '<tr><td>'.$langs->trans("Directory").':</td><td>&nbsp; <b><span class="fmvalue">'.$original_file.'</span></b></td></tr>';

    //print $langs->trans("FullPath").': '.$original_file_osencoded.'<br>';
    //print $langs->trans("Mime-type").': '.$type.'<br>';

    $info=stat($original_file_osencoded);
    //print '<br>'."\n";
    //print $langs->trans("Owner").": ".$info['udi']."<br>\n";
    //print $langs->trans("Group").": ".$info['gdi']."<br>\n";
    //print $langs->trans("Size").": ".dol_print_size($info['size'])."<br>\n";
    print '<tr><td>'.$langs->trans("DateLastAccess").':</td><td>&nbsp; <span class="fmvalue">'.dol_print_date($info['atime'],'%Y-%m-%d %H:%M:%S')."</span></td></tr>\n";
    print '<tr><td>'.$langs->trans("DateLastChange").':</td><td>&nbsp; <span class="fmvalue">'.dol_print_date($info['mtime'],'%Y-%m-%d %H:%M:%S')."</span></td></tr>\n";
    //print $langs->trans("Ctime").": ".$info['ctime']."<br>\n";
    print '</table>'."\n";

    print '<br><br>';
    print '<b>'.$langs->trans("Content")."</b><br>\n";
    print '<hr><br>';

    print '<div class="filedirelem"><ul class="filedirelem">'."\n";

    // Return content of dir
    $dircontent=dol_dir_list($original_file,'all',0,'','','name',SORT_ASC,0);
    foreach($dircontent as $key => $val)
    {
        if (dol_is_dir($val['name'])) $mimeimg='other.png';
        else $mimeimg=dol_mimetype($val['name'],'application/octet-stream',2);

        print '<li class="filedirelem">';
        print '<br><br>';
        print '<img src="'.DOL_URL_ROOT.'/theme/common/mime/'.$mimeimg.'"><br>';
        print dol_nl2br(dol_trunc($val['name'],24,'wrap'),1);
        print '</li>'."\n";
    }

    print '</ul></div>'."\n";
}
else {
    print '<table class="nobordernopadding" width="100%">';
    print '<tr><td>'.$langs->trans("File").':</td><td>&nbsp; <b><span class="fmvalue">'.$original_file.'</span></b></td></tr>';
    print '<tr><td>'.$langs->trans("Mime-type").':</td><td>&nbsp; <span class="fmvalue">'.$type.'</span></td>';
    print '<td align="right"><a href="'.dol_buildpath('/filemanager/document.php',1).'?modulepart=filemanager&id='.$id.'&rootpath='.$rootpath.'&file='.urlencode($original_file).'">'.$langs->trans("Download").'</a></td>';
    print '</tr>';

    $info=stat($original_file_osencoded);
    //print '<br>'."\n";
    //print $langs->trans("Owner").": ".$info['udi']."<br>\n";
    //print $langs->trans("Group").": ".$info['gdi']."<br>\n";
    print '<tr><td>'.$langs->trans("Size").':</td><td>&nbsp; <span class="fmvalue">'.dol_print_size($info['size'])."</span></td></tr>\n";
    print '<tr><td>'.$langs->trans("DateLastAccess").':</td><td>&nbsp; <span class="fmvalue">'.dol_print_date($info['atime'],'%Y-%m-%d %H:%M:%S')."</span></td></tr>\n";
    print '<tr><td>'.$langs->trans("DateLastChange").':</td><td>&nbsp; <span class="fmvalue">'.dol_print_date($info['mtime'],'%Y-%m-%d %H:%M:%S')."</span></td></tr>\n";
    //print $langs->trans("Ctime").": ".$info['ctime']."<br>\n";
    $sizearray=array();
    if (preg_match('/image/i',$type))
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php');
        $sizearray=dol_getImageSize($original_file_osencoded);
        print '<tr><td>'.$langs->trans("Width").':</td><td>&nbsp; <span class="fmvalue">'.$sizearray['width'].'px</span></td></tr>';
        print '<tr><td>'.$langs->trans("Height").':</td><td>&nbsp; <span class="fmvalue">'.$sizearray['height'].'px</span></td></tr>';
    }
    print '</table>'."\n";

    // Flush content before preview generation
    flush();    // This send all data to browser. Browser however may wait to have message complete or aborted before showing it.


    // Preview
    $preview=0;
    if (preg_match('/text/i',$type))
    {
        $minmem=64;        // Minimum of memory required to use Geshi (color syntax on text files)
        $maxsize=65536;      // Max size of data to read for text preview

        // Define memmax (memory_limit in bytes)
        $memmaxorig=@ini_get("memory_limit");
        $memmax=@ini_get("memory_limit");
        if ($memmaxorig != '')
        {
            preg_match('/([0-9]+)([a-zA-Z]*)/i',$memmax,$reg);
            if ($reg[2])
            {
                if (strtoupper($reg[2]) == 'M') $memmax=$reg[1]*1024*1024;
                if (strtoupper($reg[2]) == 'K') $memmax=$reg[1]*1024;
            }
        }
        //print "memmax php=".$memmax;

        $out='';
        $srclang=dol_mimetype($original_file,'text/plain',3);

        if (preg_match('/html/i',$type))    // If HTML file
        {
            print '<br><br>';
            print '<b>'.$langs->trans("Preview")."</b><br>\n";
            print '<hr>';

            readfile($original_file_osencoded);
            //$out=file_get_contents($original_file_osencoded);
            //print $out;
        }
        else                                // If not a HTML file
        {
            $warn='';

            // Check if we have enough memory for Geshi
            if ($memmax < $minmem*1024*1024)
            {
                $warn=img_warning().' '.$langs->trans("NotEnoughMemoryForSyntaxColor");
                $warn.=' (Have '.$memmax.' - Need '.$minmem.')';
                $srclang='';    // We disable geshi
            }

            if (empty($conf->global->FILEMANAGER_DISABLE_COLORSYNTAXING))
            {
                $warn=' ('.$langs->trans("ColoringDisabled").')';
                $srclang='';    // We disable geshi
            }

            // Try to detect srclang using first line
            if ($type=='text/plain' && empty($srclang))    // Try to enhance MIME detection with first line content
            {
                $firstline=file_get_contents($original_file_osencoded,false,null,0,32);
                $texts = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", strtolower($firstline));
                if (preg_match('/^#!.*\/bin\/(.*)$/',$texts[0],$reg))
                {
                    $converttogeshicode=array('ksh'=>'bash', 'sh'=>'bash', 'bash'=>'bash');
                    $srclang=$converttogeshicode[$reg[1]]?$converttogeshicode[$reg[1]]:$reg[1];
                    //print "ee".$srclang;
                }
            }
            if ($srclang=='php') $srclang='php-brief';
            //if ($srclang=='perl') $srclang='';              // Perl seems to be bugged

            if (! empty($srclang))
            {
                print '<br><br>';
                print '<b>'.$langs->trans("Preview")."</b> (".$srclang.")<br>\n";
                print '<hr>';

                // Translate with Geshi
                include_once('includes/geshi/geshi.php');

                $out=file_get_contents($original_file_osencoded,false,null,0,$maxsize);
                if (! utf8_check($out)) { $isoutf='iso'; $out=utf8_encode($out); }

                $geshi = new GeSHi($out, $srclang);
                $geshi->enable_strict_mode(true);
                $res='';
                $res=$geshi->parse_code();

                //print "zzzzzzzz";
                print $res;
            }
            else
            {
                print '<br><br>';
                print '<b>'.$langs->trans("Preview")."</b>";
                if ($warn) print ' '.$warn;
                print "<br>\n";
                print '<hr>';

                $maxlines=25;
                $i=0;$more=0;
                $handle = @fopen($original_file_osencoded, "r");
                if ($handle)
                {
                    while (!feof($handle) && $i < $maxlines)
                    {
                        $buffer = fgets($handle, $maxsize);
                        if (utf8_check($buffer)) $out.=$buffer."<br>\n";
                        else $out.=utf8_encode($buffer)."<br>\n";
                        $i++;
                    }
                    if (!feof($handle)) $more=1;
                    fclose($handle);
                }
                else
                {
                    print '<div class="error">'.$langs->trans("ErrorFailedToOpenFile",$original_file).'</div>';
                }

                print $out;

                print '<br>';

                if ($more)
                {
                    print '<b>...'.$langs->trans("More").'...</b><br>'."\n";
                }
            }
        }
        $preview=1;
    }
    // Preview if image
    if (preg_match('/image/i',$type))
    {
        print '<br><br>';
        print '<b>'.$langs->trans("Preview")."</b><br>\n";
        print '<hr><br>';


        print '<center><img';
        if (! empty($sizearray['width']) && $sizearray['width'] > 500) print ' width="500"';
        print ' src="'.dol_buildpath('/filemanager/viewimage.php',1).'?modulepart=filemanager&file='.urlencode($original_file).'"></center>';
        $preview=1;
    }
    // Preview if video
    if (preg_match('/video/i',$type))
    {
        $typecodec='';
        if (preg_match('/ogg/i',$type))     $typecodec=' type=\'video/ogg; codecs="theora, vorbis"\'';  // This works with HTML5 video
        //if (preg_match('/msvideo/i',$type)) $typecodec=' type=\'video/x-msvideo;\'';   // AVI
        //if (preg_match('/webm/i',$type))    $typecodec=' type=\'video/webm; codecs="vp8, vorbis"\'';
        //if (preg_match('/mp4/i',$type))     $typecodec=' type=\'video/mp4; codecs="avc1.42E01E, mp4a.40.2"\'';

        if ($typecodec)
        {
            print '<br><br>';
            print '<b>'.$langs->trans("Preview")."</b><br>\n";
            print '<hr><br>';

            print '<center>';
            print '<video id="movie" style="border: 1px solid #BBB;" width="320" height="240"';
            print ' preload';
            //print ' preload="none"';
            print ' controls';
            print '>';
            print '<source src="'.dol_buildpath('/filemanager/viewimage.php',1).'?modulepart=filemanager&file='.urlencode($original_file).'&type='.urlencode($type).'"';
            print $typecodec;
            print ' />';
            print '</video>';
            print '</center>';
            /*<video width="320" height="240"';
            print ' src="'.dol_buildpath('/filemanager/viewimage.php',1).'?modulepart=filemanager&file='.urlencode($original_file).'"';
            print '></center>';*/
            $preview=1;
        }
    }
    // No preview
    if (empty($preview))
    {
        print '<br><br>';
        print '<b>'.$langs->trans("Preview")."</b><br>\n";
        print '<hr>';

        print $langs->trans("PreviewNotAvailableForThisType");
    }
}

if (is_object($db)) $db->close();
?>
