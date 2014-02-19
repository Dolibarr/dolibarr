<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro  <alexandre.spangaro@gmail.com>
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
 *   	\file       htdocs/emcontract/fiche.php
 *		\ingroup    employment_contract
 *		\brief      Form and file creation of employment contract.
 */

$res=@include("../main.inc.php");
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/employees/emcontract/class/emcontract.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';

// Get parameters
$myparam = GETPOST("myparam");
$action=GETPOST('action', 'alpha');

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$user_id = $user->id;
$now=dol_now();


/*******************************************************************
 * Actions
********************************************************************/

// Si création de la demande
if ($action == 'create')
{
	  $em = new Emcontract($db);

    // Si pas le droit de créer un contrat
    if(!$user->rights->emcontract->add)
    {
        header('Location: fiche.php?action=request&error=CantCreate');
        exit;
    }

    $date_start_contract = dol_mktime(0, 0, 0, GETPOST('date_start_contract_month'), GETPOST('date_start_contract_day'), GETPOST('date_start_contract_year'));
    $date_end_contract = dol_mktime(0, 0, 0, GETPOST('date_end_contract_month'), GETPOST('date_end_contract_day'), GETPOST('date_end_contract_year'));
    $date_dpae = dol_mktime(0, 0, 0, GETPOST('date_dpae_month'), GETPOST('date_dpae_day'), GETPOST('date_dpae_year'));
    $date_medicalexam = dol_mktime(0, 0, 0, GETPOST('date_medicalexam_month'), GETPOST('date_medicalexam_day'), GETPOST('date_medicalexam_year'));
    $date_sign_employee = dol_mktime(0, 0, 0, GETPOST('date_sign_employee_month'), GETPOST('date_sign_employee_day'), GETPOST('date_sign_employee_year'));
    $date_sign_management = dol_mktime(0, 0, 0, GETPOST('date_sign_management_month'), GETPOST('date_sign_management_day'), GETPOST('date_sign_management_year'));
    
    $type_contract=GETPOST('type_contract');

    $fk_employee = GETPOST('fk_employee');
    $description = trim(GETPOST('description'));
    $fk_user_author = GETPOST('userID');

    // If no start date
    if (empty($date_start_contract))
    {
        header('Location: fiche.php?action=request&error=nostartdate');
        exit;
    }

    // If no user choose
    if ($fk_user < 1)
    {
        header('Location: fiche.php?action=request&error=nouser');
        exit;
    }

    $em->fk_employee = $fk_employee;
    $em->description = $description;
    $em->date_start_contract = $date_start_contract;
    $em->date_end_contract = $date_end_contract;
    $em->date_dpae = $date_dpae;
    $em->date_medicalexam = $date_medicalexam;
    $em->date_sign_employee = $date_sign_employee;
    $em->date_sign_management = $date_sign_management;
    $em->fk_user_author = $fk_user_author;
	  $em->type_contract = $type_contract;

    $verif = $em->create($fk_user_author);

    // Si pas d'erreur SQL on redirige vers la fiche du contrat de travail
    if ($verif > 0)
    {
        header('Location: fiche.php?id='.$verif);
        exit;
    }
    else
    {
        // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
        header('Location: fiche.php?action=request&error=SQL_Create&msg='.$em->error);
        exit;
    }

}

if ($action == 'update')
{
	  // Si pas le droit de modifier un contrat
    if(!$user->rights->emcontract->add)
    {
        header('Location: fiche.php?action=request&error=CantUpdate');
        exit;
    }

    $em = new Emcontract($db);
    $em->fetch($_POST['contract_id']);

    if ($user->rights->emcontract->add)
    {
        $date_start_contract = dol_mktime(0, 0, 0, GETPOST('date_start_contract_month'), GETPOST('date_start_contract_day'), GETPOST('date_start_contract_year'));
        $date_end_contract = dol_mktime(0, 0, 0, GETPOST('date_end_contract_month'), GETPOST('date_end_contract_day'), GETPOST('date_end_contract_year'));
        $date_dpae = dol_mktime(0, 0, 0, GETPOST('date_dpae_month'), GETPOST('date_dpae_day'), GETPOST('date_dpae_year'));
        $date_medicalexam = dol_mktime(0, 0, 0, GETPOST('date_medicalexam_month'), GETPOST('date_medicalexam_day'), GETPOST('date_medicalexam_year'));
        $date_sign_employee = dol_mktime(0, 0, 0, GETPOST('date_sign_employee_month'), GETPOST('date_sign_employee_day'), GETPOST('date_sign_employee_year'));
        $date_sign_management = dol_mktime(0, 0, 0, GETPOST('date_sign_management_month'), GETPOST('date_sign_management_day'), GETPOST('date_sign_management_year'));
        $type_contract = GETPOST('type_contract');
        $description = trim($_POST['description']);
        $fk_user_modif = $user->id;

        // Si pas de date de début
        if (empty($_POST['date_start_contract_'])) {
          header('Location: fiche.php?id='.$_POST['contract_id'].'&action=edit&error=nostartdate');
          exit;
        }
        
        $em->date_start_contract = $date_start_contract;
        $em->date_end_contract = $date_end_contract;
        $em->date_dpae = $date_dpae;
        $em->date_medicalexam = $date_medicalexam;
        $em->date_sign_employee = $date_sign_employee;
        $em->date_sign_management = $date_sign_management;
        $em->type_contract = $type_contract;
        $em->description = $description;
        $em->fk_user_modif = $fk_user_modif;

      	// Update
      	$verif = $em->update($user->id);
        if ($verif > 0)
        {
          header('Location: fiche.php?id='.$_POST['contract_id']);
          exit;
        }
        else
        {
          // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
          header('Location: fiche.php?id='.$_POST['contract_id'].'&action=edit&error=SQL_Create&msg='.$em->error);
          exit;
        }
    }
    else {
        header('Location: fiche.php?id='.$_POST['contract_id']);
        exit;
    }
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->emcontract->delete)
{
    $em = new Emcontract($db);

    $result=$em->delete($id);
    if ($result >= 0)
    {
        header("Location: index.php");
        exit;
    }
    else
    {
        $mesg=$em->error;
    }
}

/*
 * View
 */

$form = new Form($db);
$em = new Emcontract($db);

llxHeader(array(),$langs->trans('ContractTitle'));

if (empty($id) || $action == 'add' || $action == 'request')
{
    // Si l'utilisateur n'a pas le droit de faire une demande
    if(!$user->rights->emcontract->add)
    {
        $errors[]=$langs->trans('CantCreate');
    }
    else
    {
        // Formulaire de demande de congés payés
        print_fiche_titre($langs->trans('MenuAddContract'));

        // Si il y a une erreur
        if (GETPOST('error')) {

            switch(GETPOST('error')) {
                case 'SQL_Create' :
                    $errors[] = $langs->trans('ErrorSQLCreate').' <b>'.htmlentities($_GET['msg']).'</b>';
                    break;
                case 'CantCreate' :
                    $errors[] = $langs->trans('CantCreate');
                    break;
                case 'nostartdate' :
                    $errors[] = $langs->trans('NoDateStart');
                    break;
            }

            dol_htmloutput_mesg('',$errors,'error');
        }

		  print '<script type="text/javascript">
	    function valider()
	    {
    	    if(document.addcontract.date_start_contract_.value != "")
    	    {
	           	if(document.addcontract.usercontract.value != "-1") {
	            return true;
	            }
	            else {
	               alert("'.dol_escape_js($langs->transnoentities('InvalidUserContract')).'");
	               return false;
	            }
	        }
	        else
	        {
	           alert("'.dol_escape_js($langs->transnoentities('NoDateStart')).'");
	           return false;
	        }
       	}
       </script>'."\n";

        // Formulaire d'ajout de contrat
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return valider()" name="addcontract">'."\n";
        print '<input type="hidden" name="action" value="create" />'."\n";
        print '<input type="hidden" name="userID" value="'.$user_id.'" />'."\n";
        print '<div class="tabBar">';

        print '<table class="border" width="100%">';
        print '<tbody>';
        
        // Employee
        print '<tr>';
        print '<td width="25%" class="fieldrequired">'.$langs->trans("Employee").'</td>';
        print '<td colspan="3">';
        print $form->select_dolemployees($em->fk_employee, "fk_employee", 1, "", 0 );	// By default, hierarchical parent
        print '</td>';
        print '</tr>';
        
        // Type of contract
        print '<tr>';
        print '<td width="25%" class="fieldrequired">'.$langs->trans("Typecontract").'</td>';
        print '<td colspan="3">';
        //print $form->selectarray('type_contract', $listtype, GETPOST('type_contract'));
        print $em->select_typec(GETPOST('type_contract','int'),'type_contract',0);
        print '</td>';
        print '</tr>';
        
        // Date Start
        print '<tr>';
        print '<td width="25%" class="fieldrequired">'.$langs->trans("DateStart").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_start_contract_');
        print '</td>';
                
        // Date End
        print '<td width="25%">'.$langs->trans("DateEnd").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_end_contract_');
        print '</td>';
        print '</tr>';
        
        // Date Sign Employee
        print '<tr>';
        print '<td width="25%">'.$langs->trans("DateSignEmployee").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_sign_employee_');
        print '</td>';
                
        // Date Sign Management
        print '<td width="25%">'.$langs->trans("DateSignManagement").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_sign_management_');
        print '</td>';
        print '</tr>';
        
        // Date DPAE
        print '<tr>';
        print '<td width="25%">'.$langs->trans("DateDPAE").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_dpae_');
        print '</td>';
                
        // Date Medical Exam
        print '<td width="25%">'.$langs->trans("DateMedicalExam").'</td>';
        print '<td width="25%">';
        $form->select_date(-1,'date_medicalexam_');
        print '</td>';
        print '</tr>';

        // Description
        print '<tr>';
        print '<td width="25%">'.$langs->trans("Description").'</td>';
        print '<td colspan="3">';
        print '<textarea name="description" class="flat" rows="'.ROWS_3.'" cols="70"></textarea>';
        print '</td>';
        print '</tr>';

        print '</tbody>';
        print '</table>';
        print '<div style="clear: both;"></div>';
        print '</div>';
        print '</from>';

        print '<center>';
        print '<input type="submit" value="'.$langs->trans("SendContract").'" name="bouton" class="button">';
        print '&nbsp; &nbsp; ';
        print '<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)">';
        print '</center>';
    }

}
else
{
    if ($error)
    {
        print '<div class="tabBar">';
        print $error;
        print '<br /><br /><input type="button" value="'.$langs->trans("Return").'" class="button" onclick="history.go(-1)" />';
        print '</div>';
    }
    else
    {
        // Affichage de la fiche
        if ($id > 0)
        {
            $em->fetch($id);

            $fuser = new User($db);
            $fuser->fetch($em->fk_user);

            // Si il y a une erreur
            if (GETPOST('error'))
            {
                switch(GETPOST('error'))
                {
                    case 'SQL_Create' :
                        $errors[] = $langs->transnoentitiesnoconv('ErrorSQLCreate').' '.$_GET['msg'];
                        break;
                    case 'CantCreate' :
                        $errors[] = $langs->transnoentitiesnoconv('CantCreate');
                        break;
                    case 'nostartdate' :
                        $errors[] = $langs->transnoentitiesnoconv('NoStartDate');
                        break;
                }

                dol_htmloutput_mesg('',$errors,'error');
            }

            // On vérifie si l'utilisateur à le droit de lire cette demande
            if($user->rights->employee->creer)
            {

                if ($action == 'delete') {
                    if($user->rights->emcontract->delete)
                    {
                        $ret=$form->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteContract"),$langs->trans("ConfirmDelete"),"confirm_delete", '', 0, 1);
                        if ($ret == 'html') print '<br />';
                    }
                }

                $head=employee_prepare_head($em);

                dol_fiche_head($head,'contract',$langs->trans("ContractTitle"),0,'user');

                if ($action == 'edit')
                {
                    $edit = true;
                    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$_GET['id'].'">'."\n";
                    print '<input type="hidden" name="action" value="update"/>'."\n";
                    print '<input type="hidden" name="contract_id" value="'.$_GET['id'].'" />'."\n";
                }

                print '<table class="border" width="100%">';
                print '<tbody>'; 

                $linkback = '<a href="'.DOL_URL_ROOT.'/emcontract/index.php">'.$langs->trans("BackToList").'</a>';
                
                print '<tr>';
                print '<td width="25%">'.$langs->trans("Ref").'</td>';
                print '<td colspan="3">';
                print $form->showrefnav($em, 'id', $linkback, 1, 'rowid', 'ref');
  	            print '</td>';
                print '</tr>';
                
                // Employee
                print '<tr>';
                print '<td>'.$langs->trans("Employee").'</td>';
                print '<td colspan="3">';
                print $fuser->lastname.'&nbsp;'.$fuser->firstname;
  	            print '</td>';
                print '</tr>';
                
                // Type contract
                print '<tr>';
                print '<td';
                if($edit) {
                  print ' class="fieldrequired"';
                } 
                print '>'.$langs->trans("Typecontract").'</td>';
                if(!$edit) {
                    print '<td colspan="3">'.$em->LibTypeContract($em->type_contract);
			              print '</td>';
                } else {
                    print '<td colspan="3">';
                    print $em->select_typec($em->type_contract,'type_contract',0);
                    print '</td>';
                }
                print '</tr>';
                
                // Date Start Contract
                print '<tr>';
                print '<td'; 
                if($edit) {
                  print ' class="fieldrequired"';
                }  
                print '>'.$langs->trans('DateStart').'</td>';
                if(!$edit) {
                    print '<td width="25%">'.dol_print_date($em->date_start_contract,'day');
			              print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_start_contract,'date_start_contract_');
			              print '</td>';
                }

                // Date End Contract
                print '<td width="25%">'.$langs->trans('DateEnd').'</td>';
                if (!$edit)
                {
                    print '<td width="25%">'.dol_print_date($em->date_end_contract,'day');
                    print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_end_contract,'date_end_contract_');
			              print '</td>';
                }
                print '</tr>';
                
                // Date Sign Employee
                print '<tr>';
                print '<td>'.$langs->trans('DateSignEmployee').'</td>';
                if(!$edit) {
                    print '<td width="25%">'.dol_print_date($em->date_sign_employee,'day');
			              print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_sign_employee,'date_sign_employee_');
			              print '</td>';
                }

                // Date Sign Management
                print '<td width="25%">'.$langs->trans('DateSignManagement').'</td>';
                if (!$edit)
                {
                    print '<td width="25%">'.dol_print_date($em->date_sign_management,'day');
                    print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_sign_management,'date_sign_management_');
			              print '</td>';
                }
                print '</tr>';
                
                // Date DPAE
                print '<tr>';
                print '<td>'.$langs->trans('DateDPAE').'</td>';
                if(!$edit) {
                    print '<td width="25%">'.dol_print_date($em->date_dpae,'day');
			              print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_dpae,'date_dpae_');
			              print '</td>';
                }

                // Date Medical exam
                print '<td width="25%">'.$langs->trans('DateMedicalExam').'</td>';
                if (!$edit)
                {
                    print '<td width="25%">'.dol_print_date($em->date_medicalexam,'day');
                    print '</td>';
                } else {
                    print '<td width="25%">';
                    $form->select_date($em->date_medicalexam,'date_medicalexam_');
			              print '</td>';
                }
                print '</tr>';
                
                // Description
                print '<tr>';
                print '<td>'.$langs->trans('Description').'</td>';
                if (!$edit)
                {
                    print '<td colspan="3">'.nl2br($em->description).'</td>';
                }
                else
                {
                    print '<td colspan="3"><textarea name="description" class="flat" rows="'.ROWS_3.'" cols="70">'.$em->description.'</textarea></td>';
                }
                print '</tr>';

                print '</tbody>';
                print '</table>'."\n";

                if ($edit)
                {
                    print '<div style="clear: both;"></div>';
                    print '</div>';
                    print '<div align="center">';
                    if($user->rights->emcontract->add && $_GET['action'] == 'edit')
                    {
                        print '<input type="submit" value="'.$langs->trans("Update").'" class="button">';
                        print '&nbsp;&nbsp;';
                        print '<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)">';
                    }
                    print '</div>';

                    print '</form>';
                }

                dol_fiche_end();

                print '<div class="tabsAction">';

                // Boutons d'actions
                if($user->rights->emcontract->add && $_GET['action'] != 'edit')
                {
                    print '<a href="fiche.php?id='.$_GET['id'].'&action=edit" class="butAction">'.$langs->trans("Update").'</a>';
                }
                
                if ($user->rights->emcontract->delete && $_GET['action'] != 'edit')
                {
                    print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
                }
                else
                {
                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
                }
                    
                print '</div>';
                

            } else {
                print '<div class="tabBar">';
                print $langs->trans('ErrorUserView');
                print '<br /><br /><input type="button" value="'.$langs->trans("Return").'" class="button" onclick="history.go(-1)" />';
                print '</div>';
            }

        } else {
            print '<div class="tabBar">';
            print $langs->trans('ErrorIDFiche');
            print '<br /><br /><input type="button" value="'.$langs->trans("Return").'" class="button" onclick="history.go(-1)" />';
            print '</div>';
        }

    }

}

// End of page
llxFooter();

if (is_object($db)) $db->close();
?>
