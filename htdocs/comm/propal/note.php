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


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
	unset($_GET['action']);
	$socidp = $user->societe_id;
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST['action'] == 'update' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);
	$propal->update_note($_POST['note']);
}

llxHeader();
$html = new Form($db);
/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/

if ($_GET['propalid'])
{
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
                $url ='fiche.php?socid='.$societe->id;
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

			print '<tr><td valign="top" colspan="4">'.$langs->trans('Note').' :<br>'. nl2br($propal->note).'</td></tr>';

			if ($_GET['action'] == 'edit')
			{
				print '<form method="post" action="note.php?propalid='.$propal->id.'">';
				print '<input type="hidden" name="action" value="update">';
				print '<tr><td valign="top" colspan="4"><textarea name="note" cols="80" rows="8">'.$propal->note."</textarea></td></tr>";
				print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
				print '</form>';
			}

			print '</table>';

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
