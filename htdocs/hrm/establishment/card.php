<?php
<<<<<<< HEAD
/* Copyright (C) 2015      Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
=======
/* Copyright (C) 2015      Alexandre Spangaro	<aspangaro@open-dsi.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       	htdocs/hrm/establishment/card.php
 *  \brief      	Page to show an establishment
 */
<<<<<<< HEAD
require('../../main.inc.php');
=======
require '../../main.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

// Security check
if (! $user->admin) accessforbidden();

$error=0;

<<<<<<< HEAD
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm','alpha');
$id = GETPOST('id','int');
=======
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// List of status
static $tmpstatus2label=array(
		'0'=>'CloseEtablishment',
        '1'=>'OpenEtablishment'
);
$status2label=array('');
foreach ($tmpstatus2label as $key => $val) $status2label[$key]=$langs->trans($val);

$object = new Establishment($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once


/*
 * Actions
 */

if ($action == 'confirm_delete' && $confirm == "yes")
{
    $result=$object->delete($id);
    if ($result >= 0)
    {
        header("Location: ../admin/admin_establishment.php");
        exit;
    }
    else
    {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

<<<<<<< HEAD
else if ($action == 'add')
=======
elseif ($action == 'add')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    if (! $cancel)
    {
        $error=0;

		$object->name = GETPOST('name', 'alpha');
        if (empty($object->name))
        {
<<<<<<< HEAD
	        setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Name")), null, 'errors');
=======
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name")), null, 'errors');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $error++;
        }

        if (empty($error))
        {
			$object->address 		= GETPOST('address', 'alpha');
			$object->zip 			= GETPOST('zipcode', 'alpha');
			$object->town			= GETPOST('town', 'alpha');
			$object->country_id     = $_POST["country_id"];
<<<<<<< HEAD
			$object->status     	= GETPOST('status','int');
			$object->fk_user_author	= $user->id;
			$object->datec			= dol_now();


=======
			$object->status     	= GETPOST('status', 'int');
			$object->fk_user_author	= $user->id;
			$object->datec			= dol_now();
			$object->entity			= GETPOST('entity', 'int')>0?GETPOST('entity', 'int'):$conf->entity;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$id = $object->create($user);

            if ($id > 0)
            {
                header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
                exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
        }
        else
        {
            $action='create';
        }
    }
    else
    {
        header("Location: ../admin/admin_establishment.php");
        exit;
    }
}

// Update record
<<<<<<< HEAD
else if ($action == 'update')
=======
elseif ($action == 'update')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$error = 0;

	if (! $cancel) {

		$name = GETPOST('name', 'alpha');
		if (empty($name)) {
<<<<<<< HEAD
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Name')), null, 'errors');
=======
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Name')), null, 'errors');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$error ++;
		}

		if (empty($error))
		{
			$object->name 			= GETPOST('name', 'alpha');
			$object->address 		= GETPOST('address', 'alpha');
			$object->zip 			= GETPOST('zipcode', 'alpha');
			$object->town			= GETPOST('town', 'alpha');
			$object->country_id     = GETPOST('country_id', 'int');
			$object->fk_user_mod	= $user->id;
<<<<<<< HEAD
			$object->status         = GETPOST('status','int');
=======
			$object->status         = GETPOST('status', 'int');
			$object->entity         = GETPOST('entity', 'int')>0?GETPOST('entity', 'int'):$conf->entity;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$result = $object->update($user);

            if ($result > 0)
            {
                header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $_POST['id']);
                exit;
            }
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} else {
        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $_POST['id']);
        exit;
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
$formcompany = new FormCompany($db);

/*
 * Action create
 */
if ($action == 'create')
{
    print load_fiche_titre($langs->trans("NewEstablishment"));

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

	dol_fiche_head();

    print '<table class="border" width="100%">';

	// Name
<<<<<<< HEAD
    print '<tr><td>'. fieldLabel('Name','name',1).'</td><td><input name="name" id="name" size="32" value="' . GETPOST("name", "alpha") . '"></td></tr>';

	// Address
	print '<tr>';
	print '<td>'.fieldLabel('Address','address',0).'</td>';
	print '<td>';
	print '<input name="address" id="address" class="qutrevingtpercent" value="' . GETPOST('address','alpha') . '">';
=======
	print '<tr>';
	print '<td>'. $form->editfieldkey('Name', 'name', '', $object, 0, 'string', '', 1).'</td>';
	print '<td><input name="name" id="name" size="32" value="' . GETPOST("name", "alpha") . '"></td>';
	print '</tr>';

	// Parent
	print '<tr>';
	print '<td>'.$form->editfieldkey('Parent', 'entity', '', $object, 0, 'string', '', 1).'</td>';
	print '<td class="maxwidthonsmartphone">';
	print $form->selectEstablishments(GETPOST('entity', 'int')>0?GETPOST('entity', 'int'):$conf->entity, 'entity', 1);
	print '</td>';
	print '</tr>';

	// Address
	print '<tr>';
	print '<td>'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
	print '<td>';
	print '<input name="address" id="address" class="qutrevingtpercent" value="' . GETPOST('address', 'alpha') . '">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '</td>';
	print '</tr>';

	// Zipcode
	print '<tr>';
<<<<<<< HEAD
	print '<td>'.fieldLabel('Zip','zipcode',0).'</td>';
	print '<td>';
	print $formcompany->select_ziptown(GETPOST('zipcode', 'alpha'), 'zipcode', array (
			'town',
			'selectcountry_id'
	), 6);
=======
	print '<td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td>';
	print '<td>';
	print $formcompany->select_ziptown(
		GETPOST('zipcode', 'alpha'),
		'zipcode',
		array (
			'town',
			'selectcountry_id'
		),
		6
	);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '</td>';
	print '</tr>';

	// Town
	print '<tr>';
<<<<<<< HEAD
	print '<td>'.fieldLabel('Town','town',0).'</td>';
=======
	print '<td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '<td>';
	print $formcompany->select_ziptown(GETPOST('town', 'alpha'), 'town', array (
			'zipcode',
			'selectcountry_id'
	));
	print '</td>';
	print '</tr>';

	// Country
	print '<tr>';
<<<<<<< HEAD
	print '<td>'.fieldLabel('Country','selectcountry_id',0).'</td>';
	print '<td class="maxwidthonsmartphone">';
	print $form->select_country(GETPOST('country_id','int')>0?GETPOST('country_id','int'):$mysoc->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
=======
	print '<td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';
	print $form->select_country(GETPOST('country_id', 'int')>0?GETPOST('country_id', 'int'):$mysoc->country_id, 'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '</td>';
	print '</tr>';

	// Status
    print '<tr>';
<<<<<<< HEAD
    print '<td>'.fieldLabel('Status','status',1).'</td>';
	print '<td>';
	print $form->selectarray('status',$status2label,GETPOST('status','alpha'));
=======
    print '<td>'.$form->editfieldkey('Status', 'status', '', $object, 0, 'string', '', 1).'</td>';
	print '<td>';
	print $form->selectarray('status', $status2label, GETPOST('status', 'alpha'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</td></tr>';

    print '</table>';

	dol_fiche_end();

    print '<div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
    $result = $object->fetch($id);
    if ($result > 0)
    {
        $head = establishment_prepare_head($object);

        if ($action == 'edit')
        {
        	dol_fiche_head($head, 'card', $langs->trans("Establishment"), 0, 'building');

        	print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            print '<table class="border" width="100%">';

            // Ref
            print "<tr>";
            print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
            print $object->id;
            print '</td></tr>';

            // Name
<<<<<<< HEAD
            print '<tr><td>'.fieldLabel('Name','name',1).'</td><td>';
            print '<input name="name" id="name" class="flat" size="32" value="'.$object->name.'">';
            print '</td></tr>';

			// Address
			print '<tr><td>'.fieldLabel('Address','address',0).'</td>';
=======
            print '<tr><td>'.$form->editfieldkey('Name', 'name', '', $object, 0, 'string', '', 1).'</td><td>';
            print '<input name="name" id="name" class="flat" size="32" value="'.$object->name.'">';
            print '</td></tr>';

			// Parent
            print '<tr><td>'.$form->editfieldkey('Parent', 'entity', '', $object, 0, 'string', '', 1).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print $form->selectEstablishments($object->entity>0?$object->entity:$conf->entity, 'entity', 1);
            print '</td></tr>';

			// Address
			print '<tr><td>'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print '<td>';
			print '<input name="address" id="address" size="32" value="' . $object->address . '">';
			print '</td></tr>';

			// Zipcode / Town
<<<<<<< HEAD
			print '<tr><td>'.fieldLabel('Zip','zipcode',0).'</td><td>';
=======
			print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print $formcompany->select_ziptown($object->zip, 'zipcode', array (
					'town',
					'selectcountry_id'
			), 6) . '</tr>';
<<<<<<< HEAD
			print '<tr><td>'.fieldLabel('Town','town',0).'</td><td>';
=======
			print '<tr><td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print $formcompany->select_ziptown($object->town, 'town', array (
					'zipcode',
					'selectcountry_id'
			)) . '</td></tr>';

			// Country
<<<<<<< HEAD
			print '<tr><td>'.fieldLabel('Country','selectcountry_id',0).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print $form->select_country($object->fk_country,'country_id');
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
=======
			print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print $form->select_country($object->fk_country, 'country_id');
				if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print '</td>';
			print '</tr>';

			// Status
<<<<<<< HEAD
			print '<tr><td>'.fieldLabel('Status','status',1).'</td><td>';
			print $form->selectarray('status',$status2label,$object->status);
=======
			print '<tr><td>'.$form->editfieldkey('Status', 'status', '', $object, 0, 'string', '', 1).'</td><td>';
			print $form->selectarray('status', $status2label, $object->status);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print '</td></tr>';

            print '</table>';

			dol_fiche_end();

            print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
            print '</div>';

            print '</form>';
        }
    }
    else dol_print_error($db);
}

if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    $res = $object->fetch_optionals();

    $head = establishment_prepare_head($object);
    dol_fiche_head($head, 'card', $langs->trans("Establishment"), -1, 'building');

    // Confirmation to delete
    if ($action == 'delete')
    {
<<<<<<< HEAD
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteEstablishment"),$langs->trans("ConfirmDeleteEstablishment"),"confirm_delete");

=======
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("DeleteEstablishment"), $langs->trans("ConfirmDeleteEstablishment"), "confirm_delete");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


	// Object card
	// ------------------------------------------------------------

	$linkback = '<a href="' . DOL_URL_ROOT . '/hrm/admin/admin_establishment.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
    $morehtmlref.='</div>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'id', $morehtmlref);


    print '<div class="fichecenter">';
    //print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent">'."\n";

	// Name
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Name").'</td>';
	print '<td>'.$object->name.'</td>';
	print '</tr>';

<<<<<<< HEAD
=======
	// Parent
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Parent").'</td>';
	print '<td>'.$object->getNomUrlParent($object->entity).'</td>';
	print '</tr>';

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	// Address
	print '<tr>';
	print '<td>'.$langs->trans("Address").'</td>';
	print '<td>'.$object->address.'</td>';
	print '</tr>';

	// Zipcode
	print '<tr>';
	print '<td>'.$langs->trans("Zipcode").'</td>';
	print '<td>'.$object->zip.'</td>';
	print '</tr>';

	// Town
	print '<tr>';
	print '<td>'.$langs->trans("Town").'</td>';
	print '<td>'.$object->town.'</td>';
	print '</tr>';

	// Country
	print '<tr>';
	print '<td>'.$langs->trans("Country").'</td>';
	print '<td>';
	if ($object->country_id > 0)
	{
		$img=picto_from_langcode($object->country_code);
		print $img?$img.' ':'';
<<<<<<< HEAD
		print getCountry($object->getCountryCode(),0,$db);
=======
		print getCountry($object->getCountryCode(), 0, $db);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	print '</td>';
	print '</tr>';

    print '</table>';
    print '</div>';

    print '<div class="clearboth"></div><br>';

    dol_fiche_end();

    /*
     * Barre d'actions
    */

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
    print '</div>';
}

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
