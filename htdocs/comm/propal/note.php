<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/comm/propal/note.php
        \ingroup    propale
        \brief      Fiche d'information sur une proposition commerciale
        \version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");

$langs->load('propal');
$langs->load('compta');
$langs->load('bills');

$propalid = isset($_GET["propalid"])?$_GET["propalid"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $propalid, 'propal');



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);

	$db->begin();
	
	$res=$propal->update_note_public($_POST["note_public"],$user);
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

	$res=$propal->update_note($_POST["note"],$user);
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
	
	$now=gmmktime();
	
	$propal = new Propal($db);
	if ( $propal->fetch($_GET['propalid']) )
	{
		$societe = new Societe($db);
		if ( $societe->fetch($propal->socid) )
		{
			$head = propal_prepare_head($propal);
			dol_fiche_head($head, 'note', $langs->trans('Proposal'));

            print '<table class="border" width="100%">';

	        print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">'.$propal->ref.'</td></tr>';

            // Soci�t�
            print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';
            
			// Ligne info remises tiers
            print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';
			if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			$absolute_discount=$societe->getAvailableDiscounts();
			print '. ';
			if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
			else print $langs->trans("CompanyHasNoAbsoluteDiscount");
			print '.';
			print '</td></tr>';
    
			// Date
            print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
            print dol_print_date($propal->date,'daytext');
            print '</td>';
    		print '</tr>';
    		
    		// Date fin propal
            print '<tr>';
            print '<td>'.$langs->trans('DateEndPropal').'</td><td colspan="3">';
            if ($propal->fin_validite)
            {
                print dol_print_date($propal->fin_validite,'daytext');
                if ($propal->statut == 1 && $propal->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
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
		
			// Note priv�e
			if (! $user->societe_id)
			{
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
			}
			
		    print "</table>";

			print '</div>';

			/*
			 * Actions
			 */

			print '<div class="tabsAction">';
			if ($user->rights->propale->creer && $_GET['action'] <> 'edit')
			{
				print '<a class="butAction" href="note.php?propalid='.$propal->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
			}
			print '</div>';
		}
    }
}
$db->close();
llxFooter('$Date$ - $Revision: 1.15 ');
?>
