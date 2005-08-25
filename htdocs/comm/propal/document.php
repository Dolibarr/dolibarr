<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis.houssin@cap-networks.com>
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
        \file       htdocs/comm/propal/document.php
        \ingroup    propale
        \brief      Page de gestion des documents attachées à une proposition commerciale
        \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$user->getrights('propale');

if (!$user->rights->propale->lire)
	accessforbidden();



llxHeader();

$propalid=empty($_GET['propalid']) ? 0 : intVal($_GET['propalid']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];


function do_upload ($upload_dir)
{
	global $local_file, $error_msg, $langs;

	if (! is_dir($upload_dir))
	{
		create_exdir($upload_dir);
	}

	if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . '/' . $_FILES['userfile']['name']))
	{
		echo $langs->trans('FileUploaded');
	}
	else
	{
		echo $langs->trans('FileNotUploaded');
	}
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($propalid > 0)
{
	$propal = new Propal($db);

	if ($propal->fetch($propalid))
    {
		$propref = sanitize_string($propal->ref);
		$upload_dir = $conf->propal->dir_output.'/'.$propref;
		if ( $error_msg )
		{ 
			echo '<B>'.$error_msg.'</B><BR><BR>';
		}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/
		if ($action=='delete')
		{
			$file = $upload_dir . '/' . urldecode($_GET['urlfile']);
			dol_delete_file($file);
		}

		if ( $_POST['sendit'] )
		{
			do_upload ($upload_dir);
		}

		$h=0;

    	$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
    	$head[$h][1] = $langs->trans('CommercialCard');
    	$h++;
    
    	$head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?propalid='.$propal->id;
    	$head[$h][1] = $langs->trans('AccountancyCard');
    	$h++;
    
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans("Preview");
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Note');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Info');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Documents');
		$hselected=$h;
		$h++;

		dolibarr_fiche_head($head, $hselected, $langs->trans('Proposal').': '.$propal->ref);

		print_titre($langs->trans('AssociatedDocuments').' '.$propal->ref_url);

		print '<form name="userfile" action="document.php?propalid='.$propal->id.'" enctype="multipart/form-data" method="POST">';
		print '<input type="hidden" name="max_file_size" value="2000000">';
		print '<input type="file"   name="userfile" size="40" maxlength="80" class="flat"><br>';
		print '<input type="submit" value="'.$langs->trans('Upload').'" name="sendit" class="button">';
		print '<input type="submit" value="'.$langs->trans('Cancel').'" name="cancelit" class="button"><br>';
		print '</form><br>';

		clearstatcache();

        $errorlevel=error_reporting();
		error_reporting(0);
		$handle=opendir($upload_dir);
		error_reporting($errorlevel);

		print '<table width="100%" class="noborder">';

		if ($handle)
		{
    		print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Document').'</td>';
			print '<td align="right">'.$langs->trans('Size').'</td>';
			print '<td align="center">'.$langs->trans('Date').'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			$var=true;
			while (($file = readdir($handle))!==false)
			{
				if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
				{
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td>';
					echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=propal&file='.$propref.'/'.urlencode($file).'">'.$file.'</a>';
					print "</td>\n";
					print '<td align="right">'.filesize($upload_dir.'/'.$file). ' bytes</td>';
					print '<td align="center">'.strftime('%d %b %Y %H:%M:%S',filemtime($upload_dir.'/'.$file)).'</td>';
					print '<td align="center">';
					if ($file == $propref . '.pdf')
					{
						echo '-';
					}
					else
					{
						echo '<a href="'.DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id.'&action=delete&urlfile='.urlencode($file).'">'.$langs->trans('Delete').'</a>';
					}
					print "</td></tr>\n";
				}
			}
			closedir($handle);
		}
		else
		{
			print '<div class="error">'.$langs->trans('ErrorCantOpenDir').'<b> '.$upload_dir.'</b></div>';
		}
		print '</table>';

        print '</div>';
	}
	else
	{
		dolibarr_print_error($db);
	}
}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
