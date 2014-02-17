<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *      \file       htdocs/employees/note.php
 *      \ingroup    employee
 *      \brief      Fiche de notes sur un salarié
*/

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';

$langs->load("companies");
$langs->load("employees");
$langs->load("bills");

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');

// Security check
$result=restrictedArea($user,'employee',$id);

$object = new Employee($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new EmployeeType($db);
    $result=$adht->fetch($object->typeid);
}


/*
 * Actions
 */

if ($action == 'update' && $user->rights->employee->creer && ! $_POST["cancel"])
{
	$db->begin();

	$res=$object->update_note(dol_html_entity_decode(GETPOST('note'), ENT_QUOTES));
	if ($res < 0)
	{
		setEventMessage($object->error, 'errors');
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}



/*
 * View
 */

llxHeader('',$langs->trans("Employee"),'EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados');

$form = new Form($db);

if ($id)
{
	$head = employee_prepare_head($object);

	dol_fiche_head($head, 'note', $langs->trans("Employee"), 0, 'user');

	print "<form method=\"post\" action=\"note.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/employees/liste.php">'.$langs->trans("BackToList").'</a>';

    // Reference
	  print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	  print '<td colspan="3">';
	  print $form->showrefnav($object, 'id', $linkback);
	  print '</td>';
	  print '</tr>';

    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
    print '</tr>';

    // Lastname
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td>';
	  print '</tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$object->getLibStatut(4).'</td></tr>';

    // Note
    print '<tr><td valign="top">'.$langs->trans("Note").'</td>';
  	print '<td valign="top" colspan="3">';
  	if ($action == 'edit' && $user->rights->employee->creer)
  	{
  	    print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  		print "<input type=\"hidden\" name=\"id\" value=\"".$object->id."\">";
          require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
          $doleditor=new DolEditor('note',$object->note,'',280,'dolibarr_notes','',true,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,10,80);
          $doleditor->Create();
  	}
  	else
  	{
  		print nl2br($object->note);
  	}
  	print "</td></tr>";
  
  	if ($action == 'edit')
  	{
  		print '<tr><td colspan="4" align="center">';
  		print '<input type="submit" class="button" name="update" value="'.$langs->trans("Save").'">';
  		print '&nbsp; &nbsp;';
  		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
  		print '</td></tr>';
  	}

    print "</table>";
	  print "</form>\n";


    /*
    * Actions
    */
    print '</div>';
    print '<div class="tabsAction">';

    if ($user->rights->employee->creer && $action != 'edit')
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="note.php?id='.$object->id.'&amp;action=edit">'.$langs->trans('Modify')."</a></div>";
    }

    print "</div>";


}


llxFooter();
$db->close();
?>
