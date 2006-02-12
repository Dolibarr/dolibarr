<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/comm/propal/note.php
        \ingroup    propale
        \brief      Fiche d'information sur une proposition commerciale
        \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$langs->load('propal');
$langs->load('compta');

$user->getrights('propale');
if (!$user->rights->propale->lire)
	accessforbidden();


// Sécurité accés client
if ($user->societe_id > 0) 
{
	unset($_GET['action']);
	$socidp = $user->societe_id;
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->facture->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);

	$db->begin();
	
	$res=$propal->update_note_public($_POST["note_public"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$propal->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST['action'] == 'update' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);

	$db->begin();

	$res=$propal->update_note($_POST["note"]);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$propal->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$html = new Form($db);

if ($_GET['propalid'])
{
	if ($mesg) print $mesg;
	
	$propal = new Propal($db);
	if ( $propal->fetch($_GET['propalid']) )
	{
		$societe = new Societe($db);
		if ( $societe->fetch($propal->soc_id) )
		{
			$h=0;

        	$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
        	$head[$h][1] = $langs->trans('CommercialCard');
        	$h++;
        
        	$head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?propalid='.$propal->id;
        	$head[$h][1] = $langs->trans('AccountancyCard');
        	$h++;
        
			if ($conf->use_preview_tabs)
			{
    			$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?propalid='.$propal->id;
    			$head[$h][1] = $langs->trans("Preview");
    			$h++;
            }
            
			$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
			$head[$h][1] = $langs->trans('Note');
			$hselected=$h;
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
			$head[$h][1] = $langs->trans('Info');
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id;
			$head[$h][1] = $langs->trans('Documents');
			$h++;

			dolibarr_fiche_head($head, $hselected, $langs->trans("Proposal").": $propal->ref");

            print '<table class="border" width="100%">';

            print '<tr><td>'.$langs->trans('Company').'</td><td>';
            if ($societe->client == 1)
            {
                $url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
            }
            else
            {
                $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
            }
            print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
            print '<td align="left" width="25%">Conditions de réglement</td>';
            print '<td width="25%">'.'&nbsp;'.'</td>';
            print '</tr>';
    
            print '<tr><td>'.$langs->trans('Date').'</td><td>';
            print dolibarr_print_date($propal->date,'%a %d %B %Y');
            print '</td>';
    
            print '<td>'.$langs->trans('DateEndPropal').'</td><td>';
            if ($propal->fin_validite)
            {
                print dolibarr_print_date($propal->fin_validite,'%a %d %B %Y');
                if ($propal->statut == 1 && $propal->fin_validite < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
            }
            else
            {
                print $langs->trans("Unknown");
            }
            print '</td>';
            print '</tr>';

			// Note publique
		    print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
			print '<td valign="top" colspan="3">';
		    if ($_GET["action"] == 'edit')
		    {
		        print '<form method="post" action="note.php?propalid='.$propal->id.'">';
		        print '<input type="hidden" name="action" value="update_public">';
		        print '<textarea name="note_public" cols="80" rows="8">'.$propal->note_public."</textarea><br>";
		        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		        print '</form>';
		    }
		    else
		    {
			    print ($propal->note_public?nl2br($propal->note_public):"&nbsp;");
		    }
			print "</td></tr>";
		
			// Note privée
		    print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
			print '<td valign="top" colspan="3">';
		    if ($_GET["action"] == 'edit')
		    {
		        print '<form method="post" action="note.php?propalid='.$propal->id.'">';
		        print '<input type="hidden" name="action" value="update">';
		        print '<textarea name="note" cols="80" rows="8">'.$propal->note."</textarea><br>";
		        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		        print '</form>';
		    }
			else
			{
			    print ($propal->note?nl2br($propal->note):"&nbsp;");
			}
			print "</td></tr>";
		    print "</table>";

			print '</div>';

			/*
			 * Actions
			 */

			print '<div class="tabsAction">';
			if ($user->rights->propale->creer && $_GET['action'] <> 'edit')
			{
				print '<a class="tabAction" href="note.php?propalid='.$propal->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
			}
			print '</div>';
		}
    }
}
$db->close();
llxFooter('$Date$ - $Revision: 1.15 ');
?>
