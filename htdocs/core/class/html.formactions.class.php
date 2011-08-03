<?php
/* Copyright (c) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/core/class/html.formactions.class.php
 *      \ingroup    core
 *      \brief      Fichier de la classe des fonctions predefinie de composants html actions
 *		\version	$Id: html.formactions.class.php,v 1.21 2011/07/31 23:45:14 eldy Exp $
 */


/**
 *      \class  FormActions
 *      \brief  Class to manage building of HTML components
 */
class FormActions
{
    var $db;
    var $error;


    /**
     *		Constructor
     *		@param     DB      Database access handler
     */
    function FormActions($DB)
    {
        $this->db = $DB;

        return 1;
    }


    /**
     *      Show list of action status
     * 		@param		formname	Name of form where select in included
     * 		@param		selected	Preselected value
     * 		@param		canedit		1=can edit, 0=read only
     *      @param      htmlname    Name of html prefix for html fields (selectX and valX)
     */
    function form_select_status_action($formname,$selected,$canedit=1,$htmlname='complete')
    {
        global $langs,$conf;

        $listofstatus=array('-1'=>$langs->trans("ActionNotApplicable"),
                            '0'=>$langs->trans("ActionRunningNotStarted"),
                            '50'=>$langs->trans("ActionRunningShort"),
                            '100'=>$langs->trans("ActionDoneShort"));

        if ($conf->use_javascript_ajax)
        {
            print "\n";
            print '<script type="text/javascript">'."\n";
            print 'function select_status(mypercentage) {'."\n";
            print 'document.'.$formname.'.percentageshown.value=(mypercentage>=0?mypercentage:\'\');'."\n";
            print 'document.'.$formname.'.percentage.value=mypercentage;'."\n";
            print 'if (mypercentage == -1) { document.'.$formname.'.percentageshown.disabled=true; }'."\n";
            print 'else if (mypercentage == 0) { document.'.$formname.'.percentageshown.disabled=true; }'."\n";
            print 'else if (mypercentage == 100) { document.'.$formname.'.percentageshown.disabled=true; }'."\n";
            print 'else { document.'.$formname.'.percentageshown.disabled=false; }'."\n";
            print '}'."\n";
            print '</script>'."\n";
            print '<select '.($canedit?'':'disabled="true" ').'name="status" id="select'.$htmlname.'" class="flat" onChange="select_status(document.'.$formname.'.status.value)">';
            foreach($listofstatus as $key => $val)
            {
                print '<option value="'.$key.'"'.($selected == $key?' selected="selected"':'').'>'.$val.'</option>';
            }
            print '</select>';
            if ($selected == 0 || $selected == 100) $canedit=0;
            print ' <input type="text" id="val'.$htmlname.'" name="percentageshown" class="flat" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit&&($selected>=0)?'':' disabled="true"').' onChange="select_status(document.'.$formname.'.percentageshown.value)">%';
            print ' <input type="hidden" name="percentage" value="'.$selected.'">';
        }
        else
        {
            print ' <input type="text" id="val'.$htmlname.'" name="percentage" class="flat" value="'.($selected>=0?$selected:'').'" size="2"'.($canedit?'':' disabled="true"').'>%';
        }
    }


    /**
     *    	Show list of actions for element
     *    	@param      object			Object
     *    	@param      typeelement		'invoice','propal','order','invoice_supplier','order_supplier','fichinter'
     *		@param		socid			socid of user
     *		@return		int				<0 if KO, >=0 if OK
     */
    function showactions($object,$typeelement,$socid=0)
    {
        global $langs,$conf,$user;
        global $bc;

        require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

        $actioncomm = new ActionComm($this->db);
        $actioncomm->getActions($socid, $object->id, $typeelement);

        $num = count($actioncomm->actions);
        if ($num)
        {
        	if ($typeelement == 'invoice')   $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'invoice_supplier' || $typeelement == 'supplier_invoice') $title=$langs->trans('ActionsOnBill');
        	elseif ($typeelement == 'propal')    $title=$langs->trans('ActionsOnPropal');
        	elseif ($typeelement == 'order')     $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'order_supplier' || $typeelement == 'supplier_order')   $title=$langs->trans('ActionsOnOrder');
        	elseif ($typeelement == 'project')   $title=$langs->trans('ActionsOnProject');
        	elseif ($typeelement == 'shipping')  $title=$langs->trans('ActionsOnShipping');
            elseif ($typeelement == 'fichinter') $title=$langs->trans('ActionsOnFicheInter');
        	else $title=$langs->trans("Actions");

        	print_titre($title);

        	$total = 0;	$var=true;
        	print '<table class="border" width="100%">';
        	print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
        	print "\n";

        	foreach($actioncomm->actions as $action)
        	{
        		$var=!$var;
        		print '<tr '.$bc[$var].'>';
        		print '<td>'.$action->getNomUrl(1).'</td>';
        		print '<td>'.dol_print_date($action->datep,'day').'</td>';
        		print '<td title="'.dol_escape_htmltag($action->label).'">'.dol_trunc($action->label,32).'</td>';
        		$userstatic = new User($this->db);
        		$userstatic->id = $action->author->id;
        		$userstatic->prenom = $action->author->firstname;
        		$userstatic->nom = $action->author->lastname;
        		print '<td>'.$userstatic->getNomUrl(1).'</td>';
        		print '</tr>';
        	}
        	print '</table>';
        }

        return $num;
    }


    /**
     *    Output list of type of event
     *    @param      selected        Type pre-selectionne
     *    @param      htmlname        Nom champ formulaire
     */
    function select_type_actions($selected='',$htmlname='actioncode')
    {
        global $langs,$user;

        require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        $caction=new CActionComm($this->db);
        $form=new Form($this->db);

        $arraylist=$caction->liste_array(1,'code');
        array_unshift($arraylist,'&nbsp;');     // Add empty line at start
        //asort($arraylist);

        print $form->selectarray($htmlname, $arraylist, $selected);
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

}
