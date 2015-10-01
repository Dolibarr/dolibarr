<?php
/* Copyright (C) 2005-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2012		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2015       Juanjo Menent       <jmenent@2byte.es>
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
 *       \file       htdocs/exports/export.php
 *       \ingroup    export
 *       \brief      Pages of export Wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("exports");
$langs->load("users");

// Everybody should be able to go on this page
//if (! $user->admin)
//  accessforbidden();

$entitytoicon = array(
	'invoice'      => 'bill',
    'invoice_line' => 'bill',
	'order'        => 'order',
    'order_line'   => 'order',
	'propal'       => 'propal',
    'propal_line'  => 'propal',
	'intervention' => 'intervention',
    'inter_line'   => 'intervention',
	'member'       => 'user',
    'member_type'  => 'group',
    'subscription' => 'payment',
    'payment'      => 'payment',
	'tax'          => 'generic',
    'tax_type'     => 'generic',
    'stock'        => 'generic',
    'other'        => 'generic',
	'account'      => 'account',
	'product'      => 'product',
    'warehouse'    => 'stock',
    'batch'        => 'stock',
	'category'     => 'category',
	'shipment'     => 'sending',
    'shipment_line'=> 'sending',
    'expensereport'=> 'trip',
    'expensereport_line'=> 'trip',
    'contract_line' => 'contract'
);

// Translation code
$entitytolang = array(
	'user'         => 'User',
	'company'      => 'Company',
    'contact'      => 'Contact',
	'invoice'      => 'Bill',
    'invoice_line' => 'InvoiceLine',
	'order'        => 'Order',
    'order_line'   => 'OrderLine',
    'propal'       => 'Proposal',
    'propal_line'  => 'ProposalLine',
	'intervention' => 'Intervention',
    'inter_line'   => 'InterLine',
	'member'       => 'Member',
    'member_type'  => 'MemberType',
    'subscription' => 'Subscription',
	'tax'          => 'SocialContribution',
    'tax_type'     => 'DictionarySocialContributions',
	'account'      => 'BankTransactions',
	'payment'      => 'Payment',
	'product'      => 'Product',
    'service'      => 'Service',
    'stock'        => 'Stock',
    'batch'        => 'Batch',
	'warehouse'    => 'Warehouse',
	'category'     => 'Category',
	'other'        => 'Other',
    'trip'         => 'TripsAndExpenses',
    'shipment'     => 'Shipments',
    'shipment_line'=> 'ShipmentLine',
    'project'      => 'Projects',
    'projecttask'  => 'Tasks',
    'task_time'    => 'TaskTimeSpent',
	'action'       => 'Event',
	'expensereport'=> 'ExpenseReport',
	'expensereport_line'=> 'ExpenseReportLine',
    'contract'     => 'Contract',
    'contract_line'=> 'ContractLine'
);

$array_selected=isset($_SESSION["export_selected_fields"])?$_SESSION["export_selected_fields"]:array();
$array_filtervalue=isset($_SESSION["export_filtered_fields"])?$_SESSION["export_filtered_fields"]:array();
$datatoexport=GETPOST("datatoexport");
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$step=GETPOST("step")?GETPOST("step"):1;
$export_name=GETPOST("export_name");
$hexa=GETPOST("hexa");
$exportmodelid=GETPOST("exportmodelid");
$field=GETPOST("field");

$objexport=new Export($db);
$objexport->load_arrays($user,$datatoexport);

$objmodelexport=new ModeleExports($db);
$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$sqlusedforexport='';

$upload_dir = $conf->export->dir_temp.'/'.$user->id;

//$usefilters=($conf->global->MAIN_FEATURES_LEVEL > 1);
$usefilters=1;


/*
 * Actions
 */

if ($action=='selectfield')
{
	$fieldsarray=$objexport->array_export_fields[0];
	$fieldsentitiesarray=$objexport->array_export_entities[0];
    $fieldsdependenciesarray=$objexport->array_export_dependencies[0];

    if ($field=='all')
    {
		foreach($fieldsarray as $key=>$val)
		{
			if (! empty($array_selected[$key])) continue;		// If already selected, check next
			$array_selected[$key]=count($array_selected)+1;
		    //print_r($array_selected);
		    $_SESSION["export_selected_fields"]=$array_selected;
		}
    }
    else
    {
        $warnings=array();

        $array_selected[$field]=count($array_selected)+1;    // We tag the key $field as "selected"
        // We check if there is a dependency
        if (! empty($fieldsentitiesarray[$field]) && ! empty($fieldsdependenciesarray[$fieldsentitiesarray[$field]]))
        {
            $tmp=$fieldsdependenciesarray[$fieldsentitiesarray[$field]]; // $fieldsdependenciesarray=array('element'=>'fd.rowid') or array('element'=>array('fd.rowid','ab.rowid'))
            if (is_array($tmp)) $listofdependencies=$tmp;
            else $listofdependencies=array($tmp);
            foreach($listofdependencies as $fieldid)
            {
                if (empty($array_selected[$fieldid]))
                {
                    $array_selected[$fieldid]=count($array_selected)+1;    // We tag the key $fieldid as "selected"
                    $warnings[]=$langs->trans("ExportFieldAutomaticallyAdded",$langs->transnoentitiesnoconv($fieldsarray[$fieldid]));
                }
            }
        }
	    //print_r($array_selected);
	    $_SESSION["export_selected_fields"]=$array_selected;

	    setEventMessage($warnings, 'warnings');
    }

}
if ($action=='unselectfield')
{
    if ($_GET["field"]=='all')
    {
		$array_selected=array();
		$_SESSION["export_selected_fields"]=$array_selected;
    }
    else
    {
	    unset($array_selected[$_GET["field"]]);
	    // Renumber fields of array_selected (from 1 to nb_elements)
	    asort($array_selected);
	    $i=0;
	    $array_selected_save=$array_selected;
	    foreach($array_selected as $code=>$value)
	    {
	        $i++;
	        $array_selected[$code]=$i;
	        //print "x $code x $i y<br>";
	    }
	    $_SESSION["export_selected_fields"]=$array_selected;
    }
}

if ($action=='downfield' || $action=='upfield')
{
    $pos=$array_selected[$_GET["field"]];
    if ($action=='downfield') $newpos=$pos+1;
    if ($action=='upfield') $newpos=$pos-1;
    // Recherche code avec qui switcher
    $newcode="";
    foreach($array_selected as $code=>$value)
    {
        if ($value == $newpos)
        {
            $newcode=$code;
            break;
        }
    }
    //print("Switch pos=$pos (code=".$_GET["field"].") and newpos=$newpos (code=$newcode)");
    if ($newcode)   // Si newcode trouve (protection contre resoumission de page)
    {
        $array_selected[$_GET["field"]]=$newpos;
        $array_selected[$newcode]=$pos;
        $_SESSION["export_selected_fields"]=$array_selected;
    }
}

if ($step == 1 || $action == 'cleanselect')
{
    $_SESSION["export_selected_fields"]=array();
    $_SESSION["export_filtered_fields"]=array();
    $array_selected=array();
    $array_filtervalue=array();
}

if ($action == 'builddoc')
{
    // Build export file
	$result=$objexport->build_file($user, GETPOST('model','alpha'), $datatoexport, $array_selected, $array_filtervalue);
	if ($result < 0)
	{
		setEventMessage($objexport->error, 'errors');
		$sqlusedforexport=$objexport->sqlusedforexport;
	}
	else
	{
		setEventMessage($langs->trans("FileSuccessfullyBuilt"));
	    $sqlusedforexport=$objexport->sqlusedforexport;
    }
}

// Delete file
if ($step == 5 && $action == 'confirm_deletefile' && $confirm == 'yes')
{
	$file = $upload_dir . "/" . GETPOST('file');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

	$ret=dol_delete_file($file);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	header('Location: '.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport);
	exit;
}

if ($action == 'deleteprof')
{
	if ($_GET["id"])
	{
		$objexport->fetch($_GET["id"]);
		$result=$objexport->delete($user);
	}
}

// TODO The export for filter is not yet implemented (old code created conflicts with step 2). We must use same way of working and same combo list of predefined export than step 2.
if ($action == 'add_export_model')
{
	if ($export_name)
	{
		asort($array_selected);

		// Set save string
		$hexa='';
		foreach($array_selected as $key=>$val)
		{
			if ($hexa) $hexa.=',';
			$hexa.=$key;
		}

		$hexafiltervalue='';
		if (! empty($array_filtervalue) && is_array($array_filtervalue))
		{
			foreach($array_filtervalue as $key=>$val)
			{
				if ($hexafiltervalue) $hexafiltervalue.=',';
				$hexafiltervalue.=$key.'='.$val;
			}
		}

	    $objexport->model_name = $export_name;
	    $objexport->datatoexport = $datatoexport;
	    $objexport->hexa = $hexa;
	    $objexport->hexafiltervalue = $hexafiltervalue;

	    $result = $objexport->create($user);
		if ($result >= 0)
		{
			setEventMessage($langs->trans("ExportModelSaved",$objexport->model_name));
		}
		else
		{
			$langs->load("errors");
			if ($objexport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				setEventMessage($langs->trans("ErrorExportDuplicateProfil"), 'errors');
			else
				setEventMessage($objexport->error, 'errors');
		}
	}
	else
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("ExportModelName")), 'errors');
	}
}

// Reload an predefined export model
if ($step == 2 && $action == 'select_model')
{
    $_SESSION["export_selected_fields"]=array();
    $_SESSION["export_filtered_fields"]=array();

    $array_selected=array();
    $array_filtervalue=array();

    $result = $objexport->fetch($exportmodelid);
    if ($result > 0)
    {
		$fieldsarray=explode(',',$objexport->hexa);
		$i=1;
		foreach($fieldsarray as $val)
		{
			$array_selected[$val]=$i;
			$i++;
		}
		$_SESSION["export_selected_fields"]=$array_selected;

		$fieldsarrayvalue=explode(',',$objexport->hexafiltervalue);
		$i=1;
		foreach($fieldsarrayvalue as $val)
		{
			$tmp=explode('=',$val);
			$array_filtervalue[$tmp[0]]=$tmp[1];
			$i++;
		}
		$_SESSION["export_filtered_fields"]=$array_filtervalue;
    }
}

// Get form with filters
if ($step == 4 && $action == 'submitFormField')
{
	// on boucle sur les champs selectionne pour recuperer la valeur
	if (is_array($objexport->array_export_TypeFields[0]))
	{
		$_SESSION["export_filtered_fields"]=array();
		foreach($objexport->array_export_TypeFields[0] as $code => $type)	// $code: s.fieldname $value: Text|Boolean|List:ccc
		{
			$newcode=(string) preg_replace('/\./','_',$code);
			//print 'xxx'.$code."=".$newcode."=".$type."=".$_POST[$newcode]."\n<br>";
			$filterqualified=1;
			if (! isset($_POST[$newcode]) || $_POST[$newcode] == '') $filterqualified=0;
			elseif (preg_match('/^List/',$type) && (is_numeric($_POST[$newcode]) && $_POST[$newcode] <= 0)) $filterqualified=0;
			if ($filterqualified)
			{
				//print 'Filter on '.$newcode.' type='.$type.' value='.$_POST[$newcode]."\n";
				$objexport->array_export_FilterValue[0][$code] = $_POST[$newcode];
			}
		}
		$array_filtervalue=(! empty($objexport->array_export_FilterValue[0])?$objexport->array_export_FilterValue[0]:'');
		$_SESSION["export_filtered_fields"]=$array_filtervalue;
	}
}


/*
 * View
 */

if ($step == 1 || ! $datatoexport)
{
    llxHeader('',$langs->trans("NewExport"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step")." 1";
    $hselected=$h;
    $h++;

    /*
    $head[$h][0] = '';
    $head[$h][1] = $langs->trans("Step")." 2";
    $h++;
    */

    dol_fiche_head($head, $hselected, $langs->trans("NewExport"));


    print '<table class="notopnoleftnoright" width="100%">';

    print $langs->trans("SelectExportDataSet").'<br>';

    // Affiche les modules d'exports
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    print '<td>'.$langs->trans("ExportableDatas").'</td>';
    print '<td>&nbsp;</td>';
    print '</tr>';
    $val=true;
    if (count($objexport->array_export_code))
    {
        foreach ($objexport->array_export_code as $key => $value)
        {
            $val=!$val;
            print '<tr '.$bc[$val].'><td nospan="nospan">';
	        //print img_object($objexport->array_export_module[$key]->getName(),$export->array_export_module[$key]->picto).' ';
            print $objexport->array_export_module[$key]->getName();
            print '</td><td>';
			$icon=preg_replace('/:.*$/','',$objexport->array_export_icon[$key]);
			$label=$objexport->array_export_label[$key];
            //print $value.'-'.$icon.'-'.$label."<br>";
			print img_object($objexport->array_export_module[$key]->getName(), $icon).' ';
            print $label;
            print '</td><td align="right">';
            if ($objexport->array_export_perms[$key])
            {
            	print '<a href="'.DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$objexport->array_export_code[$key].'">'.img_picto($langs->trans("NewExport"),'filenew').'</a>';
            }
            else
            {
            	print $langs->trans("NotEnoughPermissions");
            }
           	print '</td></tr>';
        }
    }
    else
    {
        print '<tr><td '.$bc[false].' colspan="3">'.$langs->trans("NoExportableData").'</td></tr>';
    }
    print '</table>';

    print '</table>';

    print '</div>';
}

if ($step == 2 && $datatoexport)
{
    llxHeader('',$langs->trans("NewExport"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');


    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step")." 1";
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." 2";
    $hselected=$h;
    $h++;

    dol_fiche_head($head, $hselected, $langs->trans("NewExport"));

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    //print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
    print $objexport->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de donnees a exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>';
	$icon=preg_replace('/:.*$/','',$objexport->array_export_icon[0]);
    $label=$objexport->array_export_label[0];
    //print $value.'-'.$icon.'-'.$label."<br>";
    print img_object($objexport->array_export_module[0]->getName(), $icon).' ';
    print $label;
    print '</td></tr>';

    print '</table>';
    print '<br>';

    // Combo list of export models
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="select_model">';
    print '<input type="hidden" name="step" value="2">';
    print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
    print '<table><tr><td colspan="2">';
    print $langs->trans("SelectExportFields").' ';
    $htmlother->select_export_model($exportmodelid,'exportmodelid',$datatoexport,1);
    print '<input type="submit" class="button" value="'.$langs->trans("Select").'">';
    print '</td></tr></table>';
    print '</form>';


    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
    print '<td>'.$langs->trans("ExportableFields").'</td>';
    print '<td width="100" align="center">';
    print '<a class="liste_titre" title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field=all">'.$langs->trans("All")."</a>";
    print '/';
    print '<a class="liste_titre" title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field=all">'.$langs->trans("None")."</a>";
    print '</td>';
    print '<td width="44%">'.$langs->trans("ExportedFields").'</td>';
    print '</tr>';

    // Champs exportables
    $fieldsarray=$objexport->array_export_fields[0];
    // Select request if all fields are selected
    $sqlmaxforexport=$objexport->build_sql(0, array(), array());

	//    $this->array_export_module[0]=$module;
	//    $this->array_export_code[0]=$module->export_code[$r];
	//    $this->array_export_label[0]=$module->export_label[$r];
	//    $this->array_export_sql[0]=$module->export_sql[$r];
	//    $this->array_export_fields[0]=$module->export_fields_array[$r];
	//    $this->array_export_entities[0]=$module->export_fields_entities[$r];
	//    $this->array_export_alias[0]=$module->export_fields_alias[$r];

    $var=true;
    $i = 0;

    foreach($fieldsarray as $code=>$label)
    {
        $var=!$var;
        print "<tr ".$bc[$var].">";

        $i++;

        $entity=(! empty($objexport->array_export_entities[0][$code])?$objexport->array_export_entities[0][$code]:$objexport->array_export_icon[0]);
        $entityicon=(! empty($entitytoicon[$entity])?$entitytoicon[$entity]:$entity);
        $entitylang=(! empty($entitytolang[$entity])?$entitytolang[$entity]:$entity);

        print '<td class="nowrap">';
        // If value of entityicon=entitylang='icon:Label'
        $tmparray=explode(':',$entityicon);
        if (count($tmparray) >=2)
        {
            $entityicon=$tmparray[0];
            $entitylang=$tmparray[1];
        }
        print img_object('',$entityicon).' '.$langs->trans($entitylang);
        print '</td>';
        $text=$langs->trans($label);
        $tablename=getablenamefromfield($code,$sqlmaxforexport);
        $htmltext ='<b>'.$langs->trans("Name").":</b> ".$text.'<br>';
        $htmltext.='<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./','',$code)."<br>";
		if (! empty($objexport->array_export_examplevalues[0][$code]))
		{
		    $htmltext.=$langs->trans("SourceExample").': <b>'.$objexport->array_export_examplevalues[0][$code].'</b><br>';
		}
        if (isset($array_selected[$code]) && $array_selected[$code])
        {
            // Selected fields
            print '<td>&nbsp;</td>';
            print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field='.$code.'">'.img_left('default', 0, 'style="max-width: 20px"').'</a></td>';
            print '<td>';
            //print $text.'-'.$htmltext."<br>";
            print $form->textwithpicto($text,$htmltext);
			//print ' ('.$code.')';
            print '</td>';
        }
        else
        {
        	// Fields not selected
            print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text,$htmltext);
			//print ' ('.$code.')';
            print '</td>';
            print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field='.$code.'">'.img_right('default', 0, 'style="max-width: 20px"').'</a></td>';
            print '<td>&nbsp;</td>';
        }

        print '</tr>';
    }

    print '</table>';

    print '</div>';

    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (count($array_selected))
	{
		// If filters exist
		if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0]))
		{
			print '<a class="butAction" href="export.php?step=3&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		}
		else
		{
			print '<a class="butAction" href="export.php?step=4&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SelectAtLeastOneField")).'">'.$langs->trans("NextStep").'</a>';
	}

    print '</div>';

}

if ($step == 3 && $datatoexport)
{
	llxHeader('',$langs->trans("NewExport"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

	/*
	 * Affichage onglets
	*/
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewExport"));

	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
	print $objexport->array_export_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	$icon=preg_replace('/:.*$/','',$objexport->array_export_icon[0]);
	$label=$objexport->array_export_label[0];
	//print $value.'-'.$icon.'-'.$label."<br>";
	print img_object($objexport->array_export_module[0]->getName(), $icon).' ';
	print $label;
	print '</td></tr>';

	// Nbre champs exportes
	print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
	$list='';
	foreach($array_selected as $code=>$value)
	{
		$list.=(! empty($list)?', ':'');
		$list.=(isset($objexport->array_export_fields[0][$code])?$langs->trans($objexport->array_export_fields[0][$code]):'');
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '<br>';

	// Combo list of export models
	print $langs->trans("SelectFilterFields").'<br>';


	// un formulaire en plus pour recuperer les filtres
	print '<form action="'.$_SERVER["PHP_SELF"].'?step=4&action=submitFormField&datatoexport='.$datatoexport.'" name="FilterField" method="post">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	//print '<td>'.$langs->trans("ExportableFields").'</td>';
	//print '<td align="center"></td>';
	print '<td>'.$langs->trans("ExportableFields").'</td>';
	print '<td width="25%">'.$langs->trans("FilteredFieldsValues").'</td>';
	print '</tr>';

	// Champs exportables
	$fieldsarray=$objexport->array_export_fields[0];
	// Champs filtrable
	$Typefieldsarray=$objexport->array_export_TypeFields[0];
	// valeur des filtres
	$ValueFiltersarray=(! empty($objexport->array_export_FilterValue[0])?$objexport->array_export_FilterValue[0]:'');
	// Select request if all fields are selected
	$sqlmaxforexport=$objexport->build_sql(0, array(), array());

	$var=true;
	$i = 0;
	// on boucle sur les champs
	foreach($fieldsarray as $code => $label)
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";

		$i++;
		$entity=(! empty($objexport->array_export_entities[0][$code])?$objexport->array_export_entities[0][$code]:$objexport->array_export_icon[0]);
		$entityicon=(! empty($entitytoicon[$entity])?$entitytoicon[$entity]:$entity);
		$entitylang=(! empty($entitytolang[$entity])?$entitytolang[$entity]:$entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		$tmparray=explode(':',$entityicon);
		if (count($tmparray) >=2)
		{
			$entityicon=$tmparray[0];
			$entitylang=$tmparray[1];
		}
		print img_object('',$entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		// Field name
		$labelName=(! empty($fieldsarray[$code])?$fieldsarray[$code]:'');
		$ValueFilter=(! empty($array_filtervalue[$code])?$array_filtervalue[$code]:'');
		$text=$langs->trans($labelName);

		$tablename=getablenamefromfield($code,$sqlmaxforexport);
		$htmltext ='<b>'.$langs->trans("Name").':</b> '.$text.'<br>';
		$htmltext.='<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./','',$code)."<br>";
		if (! empty($objexport->array_export_examplevalues[0][$code]))
		{
		    $htmltext.=$langs->trans("SourceExample").': <b>'.$objexport->array_export_examplevalues[0][$code].'</b><br>';
		}
		print '<td>';
		print $form->textwithpicto($text,$htmltext);
		print '</td>';

		// Filter value
		print '<td>';
		if (! empty($Typefieldsarray[$code]))	// Example: Text, List:c_country:label:rowid, Number, Boolean
		{
			$szInfoFiltre=$objexport->genDocFilter($Typefieldsarray[$code]);
			if ($szInfoFiltre)	// Is there an info help for this filter ?
			{
				$tmp=$objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
				print $form->textwithpicto($tmp, $szInfoFiltre);
			}
			else
			{
				print $objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
			}
		}
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '</div>';

	/*
	 * Barre d'action
	 */
	print '<div class="tabsAction">';
	// il n'est pas obligatoire de filtrer les champs
	print '<a class="butAction" href="javascript:FilterField.submit();">'.$langs->trans("NextStep").'</a>';
	print '</div>';

}

if ($step == 4 && $datatoexport)
{
    asort($array_selected);

    llxHeader('',$langs->trans("NewExport"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

    /*
     * Affichage onglets
     */
    $stepoffset=0;
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step")." 1";
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." 2";
    $h++;

    // If filters exist
    if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0]))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
    	$head[$h][1] = $langs->trans("Step")." 3";
    	$h++;
    	$stepoffset++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." ".(3+$stepoffset);
    $hselected=$h;
    $h++;

    dol_fiche_head($head, $hselected, $langs->trans("NewExport"));

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    //print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
    print $objexport->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de donnees a exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>';
	$icon=preg_replace('/:.*$/','',$objexport->array_export_icon[0]);
    print img_object($objexport->array_export_module[0]->getName(), $icon).' ';
    print $objexport->array_export_label[0];
    print '</td></tr>';

    // List of exported fields
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$value)
    {
        $list.=(! empty($list)?', ':'');
        $list.=$langs->trans($objexport->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td>';
    print '</tr>';

    // List of filtered fiels
    if (isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0]))
    {
    	print '<tr><td width="25%">'.$langs->trans("FilteredFields").'</td>';
    	$list='';
    	if (! empty($array_filtervalue))
    	{
    		foreach($array_filtervalue as $code=>$value)
    		{
    			if (isset($objexport->array_export_fields[0][$code]))
    			{
    				$list.=($list?', ':'');
    				if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/',$array_filtervalue[$code])) $list.=$langs->trans($objexport->array_export_fields[0][$code]).(isset($array_filtervalue[$code])?$array_filtervalue[$code]:'');
    				else $list.=$langs->trans($objexport->array_export_fields[0][$code])."='".(isset($array_filtervalue[$code])?$array_filtervalue[$code]:'')."'";
    			}
    		}
    	}
    	print '<td>'.(! empty($list)?$list:$langs->trans("None")).'</td>';
    	print '</tr>';
    }

    print '</table>';
    print '<br>';

    // Select request if all fields are selected
    $sqlmaxforexport=$objexport->build_sql(0, array(), array());

    print $langs->trans("ChooseFieldsOrdersAndTitle").'<br>';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Entities").'</td>';
    print '<td>'.$langs->trans("ExportedFields").'</td>';
    print '<td align="right" colspan="2">'.$langs->trans("Position").'</td>';
    //print '<td>&nbsp;</td>';
    //print '<td>'.$langs->trans("FieldsTitle").'</td>';
    print '</tr>';

    $var=true;
    foreach($array_selected as $code=>$value)
    {
        $var=!$var;
        print "<tr ".$bc[$var].">";

        $entity=(! empty($objexport->array_export_entities[0][$code])?$objexport->array_export_entities[0][$code]:$objexport->array_export_icon[0]);
        $entityicon=(! empty($entitytoicon[$entity])?$entitytoicon[$entity]:$entity);
        $entitylang=(! empty($entitytolang[$entity])?$entitytolang[$entity]:$entity);

        print '<td class="nowrap">';
        // If value of entityicon=entitylang='icon:Label'
        $tmparray=explode(':',$entityicon);
        if (count($tmparray) >=2)
        {
            $entityicon=$tmparray[0];
            $entitylang=$tmparray[1];
        }
        print img_object('',$entityicon).' '.$langs->trans($entitylang);
        print '</td>';

        print '<td>';
        $text=$langs->trans($objexport->array_export_fields[0][$code]);
        $tablename=getablenamefromfield($code,$sqlmaxforexport);
        $htmltext ='<b>'.$langs->trans("Name").":</b> ".$text.'<br>';
        $htmltext.='<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./','',$code)."<br>";
    	if (! empty($objexport->array_export_examplevalues[0][$code]))
		{
		    $htmltext.=$langs->trans("SourceExample").': <b>'.$objexport->array_export_examplevalues[0][$code].'</b><br>';
		}
        print $form->textwithpicto($text,$htmltext);
		//print ' ('.$code.')';
        print '</td>';

        print '<td align="right" width="100">';
        print $value.' ';
        print '</td><td align="center" width="20">';
        if ($value < count($array_selected)) print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=downfield&field='.$code.'">'.img_down().'</a>';
        if ($value > 1) print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=upfield&field='.$code.'">'.img_up().'</a>';
        print '</td>';

        //print '<td>&nbsp;</td>';
        //print '<td>'.$langs->trans($objexport->array_export_fields[0][$code]).'</td>';

        print '</tr>';
    }

    print '</table>';

    print '</div>';

    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (count($array_selected))
    {
        print '<a class="butAction" href="export.php?step='.($step + 1).'&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
    }

    print '</div>';


	// Area for profils export
	if (count($array_selected))
    {
		print '<br>';
        print $langs->trans("SaveExportModel");

		print '<form class="nocellnopadd" action="export.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="add_export_model">';
        print '<input type="hidden" name="step" value="'.$step.'">';
        print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
        print '<input type="hidden" name="hexa" value="'.$hexa.'">';

        print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ExportModelName").'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=false;
		print '<tr '.$bc[$var].'>';
		print '<td><input name="export_name" size="32" value=""></td><td align="right">';
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</td></tr>';

        // List of existing export profils
    	$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."export_model";
		$sql.= " WHERE type = '".$datatoexport."'";
		$sql.= " ORDER BY rowid";
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$var=false;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td>';
				print $obj->label;
				print '</td><td align="right">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=deleteprof&id='.$obj->rowid.'">';
				print img_delete();
				print '</a>';
				print '</tr>';
				$i++;
			}
		}
		else {
			dol_print_error($this->db);
		}

        print '</table>';
        print '</form>';
    }
}

if ($step == 5 && $datatoexport)
{
	asort($array_selected);

    llxHeader('',$langs->trans("NewExport"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

    /*
     * Affichage onglets
     */
    $h = 0;
    $stepoffset=0;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
    $head[$h][1] = $langs->trans("Step")." 1";
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." 2";
    $h++;

    // si le filtrage est parametre pour l'export ou pas
    if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0]))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
    	$head[$h][1] = $langs->trans("Step")." 3";
    	$h++;
    	$stepoffset++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." ".(3+$stepoffset);
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=5&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." ".(4+$stepoffset);
    $hselected=$h;
    $h++;

    dol_fiche_head($head, $hselected, $langs->trans("NewExport"));

    /*
     * Confirmation suppression fichier
     */
    if ($action == 'remove_file')
    {
    	print $form->formconfirm($_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport.'&file='.urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);

    }

    print '<table width="100%" class="border">';

    // Module
    print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
    print '<td>';
    //print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
    print $objexport->array_export_module[0]->getName();
    print '</td></tr>';

    // Lot de donnees a exporter
    print '<tr><td width="25%">'.$langs->trans("DatasetToExport").'</td>';
    print '<td>';
	$icon=preg_replace('/:.*$/','',$objexport->array_export_icon[0]);
    print img_object($objexport->array_export_module[0]->getName(), $icon).' ';
    print $objexport->array_export_label[0];
    print '</td></tr>';

    // List of exported fields
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$label)
    {
        $list.=(! empty($list)?', ':'');
        $list.=$langs->trans($objexport->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td></tr>';

    // List of filtered fiels
    if (isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0]))
    {
    	print '<tr><td width="25%">'.$langs->trans("FilteredFields").'</td>';
    	$list='';
    	if (! empty($array_filtervalue))
    	{
    		foreach($array_filtervalue as $code=>$value)
    		{
    			if (isset($objexport->array_export_fields[0][$code]))
    			{
    				$list.=($list?', ':'');
    				if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/',$array_filtervalue[$code])) $list.=$langs->trans($objexport->array_export_fields[0][$code]).(isset($array_filtervalue[$code])?$array_filtervalue[$code]:'');
    				else $list.=$langs->trans($objexport->array_export_fields[0][$code])."='".(isset($array_filtervalue[$code])?$array_filtervalue[$code]:'')."'";
    			}
    		}
    	}
    	print '<td>'.(! empty($list)?$list:$langs->trans("None")).'</td>';
    	print '</tr>';
    }

    print '</table>';
    print '<br>';

    print $langs->trans("NowClickToGenerateToBuildExportFile").'<br>';

    // Liste des formats d'exports disponibles
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
    print '<td>'.$langs->trans("LibraryUsed").'</td>';
    print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
    print '</tr>'."\n";

    $liste=$objmodelexport->liste_modeles($db);
    $listeall=$liste;
    foreach($listeall as $key => $val)
    {
    	if (preg_match('/__\(Disabled\)__/',$listeall[$key]))
    	{
    		$listeall[$key]=preg_replace('/__\(Disabled\)__/','('.$langs->transnoentitiesnoconv("Disabled").')',$listeall[$key]);
    		unset($liste[$key]);
    	}

        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="16">'.img_picto_common($key,$objmodelexport->getPictoForKey($key)).'</td>';
	    $text=$objmodelexport->getDriverDescForKey($key);
	    $label=$listeall[$key];
	    print '<td>'.$form->textwithpicto($label,$text).'</td>';
        print '<td>'.$objmodelexport->getLibLabelForKey($key).'</td><td align="right">'.$objmodelexport->getLibVersionForKey($key).'</td></tr>'."\n";
    }
    print '</table>';

    print '</div>';

    print '<table width="100%">';

    if ($sqlusedforexport && $user->admin)
    {
    	print '<tr><td>';
    	print info_admin($langs->trans("SQLUsedForExport").':<br> '.$sqlusedforexport);
    	print '</td></tr>';
    }
	print '</table>';

    print '<table width="100%"><tr><td width="50%">';

    if (! is_dir($conf->export->dir_temp)) dol_mkdir($conf->export->dir_temp);

    // Affiche liste des documents
    // NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
    $formfile->show_documents('export','',$upload_dir,$_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport,$liste,1,(! empty($_POST['model'])?$_POST['model']:'csv'),1,1);

    print '</td><td width="50%">&nbsp;</td></tr>';
    print '</table>';

}

print '<br>';

llxFooter();

$db->close();

exit;	// don't know why but apache hangs with php 5.3.10-1ubuntu3.12 and apache 2.2.2 if i remove this exit or replace with return


/**
 * 	Return table name of an alias. For this, we look for the "tablename as alias" in sql string.
 *
 * 	@param	string	$code				Alias.Fieldname
 * 	@param	string	$sqlmaxforexport	SQL request to parse
 * 	@return	string						Table name of field
 */
function getablenamefromfield($code,$sqlmaxforexport)
{
	$newsql=$sqlmaxforexport;
	$newsql=preg_replace('/^(.*) FROM /i','',$newsql);
	$newsql=preg_replace('/WHERE (.*)$/i','',$newsql);	// We must keep the ' ' before WHERE
	$alias=preg_replace('/\.(.*)$/i','',$code);
	//print $newsql.' '.$alias;
	$regexstring='/([a-zA-Z_]+) as '.$alias.'[, \)]/i';
	if (preg_match($regexstring,$newsql,$reg))
	{
		return $reg[1];
	}
	else return '';
}
