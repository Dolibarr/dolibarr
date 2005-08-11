<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/docsoc.php
        \brief      Fichier onglet documents liés à la société
        \ingroup    societe
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");


llxHeader();

$mesg = "";
$socid=$_GET["socid"];


/*
 * Creation répertoire si n'existe pas
 */
if (! is_dir($conf->societe->dir_output)) { mkdir($conf->societe->dir_output); }
$upload_dir = $conf->societe->dir_output . "/" . $socid ;
if (! is_dir($upload_dir))
{
    umask(0);
    if (! mkdir($upload_dir, 0755))
    {
        print $langs->trans("ErrorCanNotCreateDir",$upload_dir);
    }
}


/*
 * Action envoie fichier
 */
if ( $_POST["sendit"] && $conf->upload)
{
    if (is_dir($upload_dir))
    {
        if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
        }
        else
        {
            // Echec transfert (fichier dépassant la limite ?)
            $mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            // print_r($_FILES);
        }
    }
}


/*
 * Action suppression fichier
 */
if ($_GET["action"]=='delete')
{
    $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
    dol_delete_file($file);
    $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
 * Affichage liste
 */

if ($socid > 0)
{
    $societe = new Societe($db);
    if ($societe->fetch($socid))
    {
        $h = 0;

        $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Company");
        $h++;

        if ($societe->client==1)
        {
            $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
            $head[$h][1] = $langs->trans("Customer");
            $h++;
        }

        if ($societe->client==2)
        {
            $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
            $head[$h][1] = $langs->trans("Prospect");
            $h++;
        }
        if ($societe->fournisseur)
        {
            $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
            $head[$h][1] = $langs->trans("Supplier");
            $h++;
        }

        if ($conf->compta->enabled) {
            $langs->load("compta");
            $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
            $head[$h][1] = $langs->trans("Accountancy");
            $h++;
        }

        $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Note");
        $h++;

        if ($user->societe_id == 0)
        {
            $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
            $head[$h][1] = $langs->trans("Documents");
            $hselected = $h;
            $h++;
        }

        $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Notifications");

        dolibarr_fiche_head($head, $hselected, $societe->nom);

        // Construit liste des fichiers
        clearstatcache();

        $totalsize=0;
        $filearray=array();
        $handle=opendir($upload_dir);
        if ($handle)
        {
            $i=0;
            while (($file = readdir($handle))!==false)
            {
                if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
                {
                    $filearray[$i]=$file;
                    $totalsize+=filesize($upload_dir."/".$file);
                    $i++;
                }
            }
            closedir($handle);
        }
        else
        {
            print $langs->trans("ErrorCanNotReadDir",$upload_dir);
        }
        
        print '<table class="border"width="100%">';
        print '<tr><td width="30%">'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';
        print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
        print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
        print '</table>';

        print '</div>';

        if ($mesg) { print "$mesg<br>"; }

        // Affiche forumlaire upload
        if (defined('MAIN_UPLOAD_DOC') && $conf->upload)
        {
            print_titre($langs->trans("AttachANewFile"));
            echo '<form name="userfile" action="docsoc.php?socid='.$socid.'" enctype="multipart/form-data" METHOD="POST">';

            print '<table class="noborder" width="100%">';
            print '<tr><td width="50%" valign="top">';

            print '<input type="hidden" name="max_file_size" value="2000000">';
            print '<input class="flat" type="file" name="userfile" size="40" maxlength="80">';
            print ' &nbsp; ';
            print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="sendit">';

            print "</td></tr>";
            print "</table>";

            print '</form>';
            print '<br>';
        }

        // Affiche liste des documents existant
        print_titre($langs->trans("AttachedFiles"));

        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td>'.$langs->trans("Document").'</td><td align="right">'.$langs->trans("Size").'</td><td align="center">'.$langs->trans("Date").'</td><td>&nbsp;</td></tr>';

        $var=true;
        foreach($filearray as $key => $file)
        {
            if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                $var=!$var;
                print "<tr $bc[$var]><td>";
                echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=societe&type=application/binary&file='.urlencode($socid.'/'.$file).'">'.$file.'</a>';
                print "</td>\n";

                print '<td align="right">'.filesize($upload_dir."/".$file). ' '.$langs->trans("bytes").'</td>';
                print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir."/".$file),"%d %b %Y %H:%M:%S").'</td>';

                print '<td align="center">';
                echo '<a href="docsoc.php?socid='.$socid.'&action=delete&urlfile='.urlencode($file).'">'.img_delete().'</a>';
                print "</td></tr>\n";
            }
        }

        print "</table>";
    }
    else
    {
        dolibarr_print_error($db);
    }
}
else
{
    dolibarr_print_error();
}

$db->close();


llxFooter('$Date$ - $Revision$');

?>
