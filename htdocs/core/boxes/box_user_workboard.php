<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016 Frederic France      <frederic.france@free.fr>
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
 *	\file       htdocs/core/boxes/box_user_workboard.php
 *	\ingroup    user
 *	\brief      Module to show box of user workboard
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 * Class to manage the box to show user workboard
 */
class box_user_workboard extends ModeleBoxes
{
    var $boxcode="userinfo";
    var $boximg="object_user";
    var $boxlabel="BoxTitleDolibarrWorkingBoard";
    var $depends = array("user");

    var $db;
    var $param;
    var $enabled = 1;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $conf, $user;

        $this->db = $db;

        // disable module for such cases

    }

    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param  int     $max        Maximum number of records to load
     *  @return void
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db, $conf, $form, $bc;
        $langs->load("boxes");

        $this->max = $max;


        $this->info_box_head = array(
            'text' => $langs->trans("BoxTitleDolibarrWorkingBoard"),
        );
        $showweather=empty($conf->global->MAIN_DISABLE_METEO)?1:0;

        //Array that contains all WorkboardResponse classes to process them
        $dashboardlines=array();

        $boxwork='';
        $boxwork.='<table summary="'.dol_escape_htmltag($langs->trans("WorkingBoard")).'" class="noborder noshadow" width="100%">'."\n";
        //$boxwork.='<tr class="liste_titre">';
        //$boxwork.='<th class="liste_titre" colspan="2">'.$langs->trans("DolibarrWorkBoard").'</th>';
        //$boxwork.='<th class="liste_titre" align="right">'.$langs->trans("Number").'</th>';
        //$boxwork.='<th class="liste_titre" align="right">'.$form->textwithpicto($langs->trans("Late"),$langs->trans("LateDesc")).'</th>';
        //$boxwork.='<th class="liste_titre">&nbsp;</th>';
        ////print '<th class="liste_titre" width="20">&nbsp;</th>';
        //if ($showweather) $boxwork.='<th class="liste_titre hideonsmartphone" width="80">&nbsp;</th>';
        //$boxwork.='</tr>'."\n";

        // Do not include sections without management permission
        require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

        // Number of actions to do (late)
        if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->read)
        {
            include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
            $board=new ActionComm($db);

            $dashboardlines[] = $board->load_board($user);
        }

        // Number of project opened
        if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
            $board=new Project($db);

            $dashboardlines[] = $board->load_board($user);
        }

        // Number of tasks to do (late)
        if (! empty($conf->projet->enabled) && empty($conf->global->PROJECT_HIDE_TASKS) && $user->rights->projet->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
            $board=new Task($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of commercial proposals opened (expired)
        if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $board=new Propal($db);
            $dashboardlines[] = $board->load_board($user,"opened");
            // Number of commercial proposals CLOSED signed (billed)
            $dashboardlines[] = $board->load_board($user,"signed");
        }

        // Number of customer orders a deal
        if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $board=new Commande($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of suppliers orders a deal
        if (! empty($conf->supplier_order->enabled) && $user->rights->fournisseur->commande->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $board=new CommandeFournisseur($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of services enabled (delayed)
        if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $board=new Contrat($db);
            $dashboardlines[] = $board->load_board($user,"inactives");
            // Number of active services (expired)
            $dashboardlines[] = $board->load_board($user,"expired");
        }
        // Number of invoices customers (has paid)
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $board=new Facture($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of supplier invoices (has paid)
        if (! empty($conf->supplier_invoice->enabled) && ! empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $board=new FactureFournisseur($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of transactions to conciliate
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
            $board=new Account($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of cheque to send
        if (! empty($conf->banque->enabled) && $user->rights->banque->lire && ! $user->societe_id)
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
            $board=new RemiseCheque($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of foundation members
        if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire && ! $user->societe_id)
        {
            include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
            $board=new Adherent($db);
            $dashboardlines[] = $board->load_board($user);
        }

        // Number of expense reports to approve
        if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->approve)
        {
            include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
            $board = new ExpenseReport($db);
            $dashboardlines[] = $board->load_board($user,'toapprove');
        }

        // Number of expense reports to pay
        if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->to_paid)
        {
            include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
            $board = new ExpenseReport($db);
            $dashboardlines[] = $board->load_board($user,'topay');
        }

        // Calculate total nb of late
        $totallate = 0;
        $var = true;

        //Remove any invalid response
        //load_board can return an integer if failed or WorkboardResponse if OK
        $valid_dashboardlines=array();
        foreach($dashboardlines as $tmp)
        {
            if ($tmp instanceof WorkboardResponse) $valid_dashboardlines[] = $tmp;
        }
        $rowspan = count($valid_dashboardlines);

        // We calculate $totallate. Must be defined before start of next loop because it is show in first fetch on next loop
        foreach($valid_dashboardlines as $board)
        {
            if ($board->nbtodolate > 0) {
                $totallate += $board->nbtodolate;
            }
        }

        // Show dashboard
        foreach($valid_dashboardlines as $board)
        {
            $var=!$var;
            $boxwork.= '<tr '.$bc[$var].'><td width="16">'.$board->img.'</td><td>'.$board->label.'</td>';
            $boxwork.= '<td align="right"><a class="dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator">'.$board->nbtodo.'</span></a></td>';
            $boxwork.= '<td align="right">';
            //if ($board->nbtodolate > 0)
            //{
                $textlate = $langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($board->warning_delay) >= 0 ? '+' : '').ceil($board->warning_delay).' '.$langs->trans("days");
                $boxwork.= '<a title="'.dol_escape_htmltag($textlate).'" class="dashboardlineindicatorlate'.($board->nbtodolate>0?' dashboardlineko':' dashboardlineok').'" href="'.$board->url.'"><span class="dashboardlineindicatorlate'.($board->nbtodolate>0?' dashboardlineko':' dashboardlineok').'">';
                $boxwork.= $board->nbtodolate;
                $boxwork.= '</span></a>';
            //}
            $boxwork.='</td>';
            $boxwork.='<td align="left">';
            if ($board->nbtodolate > 0) $boxwork.=img_picto($langs->trans("NActionsLate",$board->nbtodolate).' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')',"warning");
            else $boxwork.='&nbsp;';
            $boxwork.='</td>';
            /*print '<td class="nowrap" align="right">';
            print ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
            print '</td>';*/
    
            if ($showweather)
            {
                $boxwork.= '<td class="nohover hideonsmartphone noborderbottom" rowspan="'.$rowspan.'" width="80" style="border-left: 1px solid #DDDDDD" align="center">';
                $text='';
                if ($totallate > 0) $text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv("NActionsLate",$totallate).')';
                $options = 'height="64px"';
                if ($rowspan <= 2) $options = 'height="24"';      // Weather logo is smaller if dashboard has few elements
                else if ($rowspan <= 3) $options = 'height="48"'; // Weather logo is smaller if dashboard has few elements
                $boxwork.= showWeather($totallate,$text,$options);
                $boxwork.= '</td>';
                $showweather = 0;
            }
            $boxwork.='</tr>';
            $boxwork.="\n";
        }

        $boxwork.='</table>';   // End table array of working board

        //print $boxwork;

        $this->info_box_contents[0][0] = array('td' => 'align="center" class="nohover"','textnoformat'=>$boxwork);


    }

    /**
     *  Method to show box
     *
     *  @param  array   $head       Array with properties of box title
     *  @param  array   $contents   Array with properties of box lines
     *  @return void
     */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

