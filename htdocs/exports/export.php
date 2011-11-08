<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/exports/export.php
 *       \ingroup    export
 *       \brief      Pages of export Wizard
 */

require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/exports/class/export.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php');

$langs->load("exports");

// Everybody should be able to go on this page
//if (! $user->admin)
//  accessforbidden();

$entitytoicon=array(
	'invoice'=>'bill','invoice_line'=>'bill',
	'order'=>'order' ,'order_line'=>'order',
	'propal'=>'propal', 'propal_line'=>'propal',
	'intervention'=>'intervention' ,'inter_line'=>'intervention',
	'member'=>'user' ,'member_type'=>'group','subscription'=>'payment',
	'tax'=>'generic' ,'tax_type'=>'generic',
	'account'=>'account',
	'payment'=>'payment',
	'product'=>'product','stock'=>'generic','warehouse'=>'stock',
	'category'=>'category',
	'other'=>'generic',
	);
$entitytolang=array(		// Translation code
	'user'=>'User',
	'company'=>'Company','contact'=>'Contact',
	'invoice'=>'Bill','invoice_line'=>'InvoiceLine',
	'order'=>'Order','order_line'=>'OrderLine',
    'propal'=>'Proposal','propal_line'=>'ProposalLine',
	'intervention'=>'Intervention' ,'inter_line'=>'InterLine',
	'member'=>'Member','member_type'=>'MemberType','subscription'=>'Subscription',
	'tax'=>'SocialContribution','tax_type'=>'DictionnarySocialContributions',
	'account'=>'BankTransactions',
	'payment'=>'Payment',
	'product'=>'Product','stock'=>'Stock','warehouse'=>'Warehouse',
	'category'=>'Category',
	'other'=>'Other'
	);

$array_selected=isset($_SESSION["export_selected_fields"])?$_SESSION["export_selected_fields"]:array();
$datatoexport=isset($_GET["datatoexport"])? $_GET["datatoexport"] : (isset($_POST["datatoexport"])?$_POST["datatoexport"]:'');
$action=isset($_GET["action"]) ? $_GET["action"] : (isset($_POST["action"])?$_POST["action"]:'');
$step=isset($_GET["step"])? $_GET["step"] : (isset($_POST["step"])?$_POST["step"]:1);
$export_name=isset($_POST["export_name"])? $_POST["export_name"] : '';
$hexa=isset($_POST["hexa"])? $_POST["hexa"] : '';
$exportmodelid=isset($_POST["exportmodelid"])? $_POST["exportmodelid"] : '';

$objexport=new Export($db);
$objexport->load_arrays($user,$datatoexport);

$objmodelexport=new ModeleExports();
$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$sqlusedforexport='';


/*
 * Actions
 */

if ($action=='selectfield')
{
    if ($_GET["field"]=='all')
    {
		$fieldsarray=$objexport->array_export_fields[0];
		foreach($fieldsarray as $key=>$val)
		{
			if (! empty($array_selected[$key])) continue;		// If already selected, select next
			$array_selected[$key]=count($array_selected)+1;
		    //print_r($array_selected);
		    $_SESSION["export_selected_fields"]=$array_selected;
		}
    }
    else
    {
		$array_selected[$_GET["field"]]=count($array_selected)+1;
	    //print_r($array_selected);
	    $_SESSION["export_selected_fields"]=$array_selected;
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
    $array_selected=array();
}

if ($action == 'builddoc')
{
    // Build export file
	$result=$objexport->build_file($user, $_POST['model'], $datatoexport, $array_selected);
	if ($result < 0)
	{
	    $mesg='<div class="error">'.$objexport->error.'</div>';
	}
	else
	{
	    $mesg='<div class="ok">'.$langs->trans("FileSuccessfullyBuilt").'</div>';
	    $sqlusedforexport=$objexport->sqlusedforexport;
    }
}

if ($action == 'deleteprof')
{
	if ($_GET["id"])
	{
		$objexport->fetch($_GET["id"]);
		$result=$objexport->delete($user);
	}
}

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

	    $objexport->model_name = $export_name;
	    $objexport->datatoexport = $datatoexport;
	    $objexport->hexa = $hexa;

	    $result = $objexport->create($user);
		if ($result >= 0)
		{
		    $mesg='<div class="ok">'.$langs->trans("ExportModelSaved",$objexport->model_name).'</div>';
		}
		else
		{
			$langs->load("errors");
			if ($objexport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$mesg='<div class="error">'.$langs->trans("ErrorExportDuplicateProfil").'</div>';
			}
			else $mesg='<div class="error">'.$objexport->error.'</div>';
		}
	}
	else
	{
	    $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("ExportModelName")).'</div>';
	}
}

if ($step == 2 && $action == 'select_model')
{
    $_SESSION["export_selected_fields"]=array();
    $array_selected=array();
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
			$icon=$objexport->array_export_icon[$key];
			$label=$objexport->array_export_label[$key];
            //print $value.'-'.$icon.'-'.$label."<br>";
			print img_object($objexport->array_export_module[$key]->getName(),$icon).' ';
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

    if ($mesg) print $mesg;

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
    $icon=$objexport->array_export_icon[0];
    $label=$objexport->array_export_label[0];
    //print $value.'-'.$icon.'-'.$label."<br>";
    print img_object($objexport->array_export_module[0]->getName(),$icon).' ';
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
    print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field=all">'.$langs->trans("All")."</a>";
    print '/';
    print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field=all">'.$langs->trans("None")."</a>";
    print '</td>';
    print '<td width="44%">'.$langs->trans("ExportedFields").'</td>';
    print '</tr>';

    // Champs exportables
    $fieldsarray=$objexport->array_export_fields[0];
    // Select request if all fields are selected
    $sqlmaxforexport=$objexport->build_sql(0,array());

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
        print "<tr $bc[$var]>";

        $i++;

        $entity=(! empty($objexport->array_export_entities[0][$code])?$objexport->array_export_entities[0][$code]:$objexport->array_export_icon[0]);
        $entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
        $entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

        print '<td nowrap="nowrap">';
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
        if ((isset($array_selected[$code]) && $array_selected[$code]) || $modelchoice == 1)
        {
            // Selected fields
            print '<td>&nbsp;</td>';
            print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field='.$code.'">'.img_left().'</a></td>';
            print '<td>';
            //print $text.'-'.$htmltext."<br>";
            print $form->textwithpicto($text,$htmltext);
			//print ' ('.$code.')';
            print '</td>';
            $bit=1;
        }
        else
        {
        	// Fields not selected
            print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text,$htmltext);
			//print ' ('.$code.')';
            print '</td>';
            print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field='.$code.'">'.img_right().'</a></td>';
            print '<td>&nbsp;</td>';
            $bit=0;
        }

        print '</tr>';
        $save_select.=$bit;
    }

    print '</table>';

    print '</div>';

    if ($mesg) print $mesg;

    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (count($array_selected))
	{
		print '<a class="butAction" href="export.php?step=3&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SelectAtLeastOneField")).'">'.$langs->trans("NextStep").'</a>';
	}

    print '</div>';

}

if ($step == 3 && $datatoexport)
{
    asort($array_selected);

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
    print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_icon[0]).' ';
    print $objexport->array_export_label[0];
    print '</td></tr>';

    // Nbre champs exportes
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$value)
    {
        $list.=($list?', ':'');
        $list.=$langs->trans($objexport->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td></tr>';

    print '</table>';
    print '<br>';

    // Select request if all fields are selected
    $sqlmaxforexport=$objexport->build_sql(0,array());

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
        print "<tr $bc[$var]>";

        $entity=(! empty($objexport->array_export_entities[0][$code])?$objexport->array_export_entities[0][$code]:$objexport->array_export_icon[0]);
        $entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
        $entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

        print '<td nowrap="nowrap">';
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
        print $form->textwithpicto($text,$htmltext);
		//print ' ('.$code.')';
        print '</td>';

        print '<td align="right" width="100">';
        print $value.' ';
        print '</td><td align="center" width="20">';
        if ($value < count($array_selected)) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoexport='.$datatoexport.'&action=downfield&field='.$code.'">'.img_down().'</a>';
        if ($value > 1) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoexport='.$datatoexport.'&action=upfield&field='.$code.'">'.img_up().'</a>';
        print '</td>';

        //print '<td>&nbsp;</td>';
        //print '<td>'.$langs->trans($objexport->array_export_fields[0][$code]).'</td>';

        print '</tr>';
    }

    print '</table>';


    print '</div>';

    if ($mesg) print $mesg;

    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if (count($array_selected))
    {
        print '<a class="butAction" href="export.php?step=4&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
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

if ($step == 4 && $datatoexport)
{
    asort($array_selected);

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
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&datatoexport='.$datatoexport;
    $head[$h][1] = $langs->trans("Step")." 4";
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
    print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_icon[0]).' ';
    print $objexport->array_export_label[0];
    print '</td></tr>';

    // Nbre champs exportes
    print '<tr><td width="25%">'.$langs->trans("ExportedFields").'</td>';
    $list='';
    foreach($array_selected as $code=>$label)
    {
        $list.=($list?', ':'');
        $list.=$langs->trans($objexport->array_export_fields[0][$code]);
    }
    print '<td>'.$list.'</td></tr>';

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
    foreach($liste as $key => $val)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="16">'.img_picto_common($key,$objmodelexport->getPicto($key)).'</td>';
	    $text=$objmodelexport->getDriverDesc($key);
    	print '<td>'.$form->textwithpicto($objmodelexport->getDriverLabel($key),$text).'</td>';
        print '<td>'.$objmodelexport->getLibLabel($key).'</td><td align="right">'.$objmodelexport->getLibVersion($key).'</td></tr>'."\n";
    }
    print '</table>';

    print '</div>';

    print '<table width="100%">';
    if ($mesg)
    {
    	print '<tr><td colspan="2">';
    	print $mesg;
    	print '</td></tr>';
    }
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
    $formfile->show_documents('export','',$conf->export->dir_temp.'/'.$user->id,$_SERVER["PHP_SELF"].'?step=4&datatoexport='.$datatoexport,$liste,1,(! empty($_POST['model'])?$_POST['model']:'csv'),1,1);

    print '</td><td width="50%">&nbsp;</td></tr>';
    print '</table>';
}


print '<br>';


$db->close();

llxFooter();


/**
 * 	Return table name of an alias. For this, we look for the "tablename as alias" in sql string.
 *
 * 	@param		code				Alias.Fieldname
 * 	@param		sqlmaxforexport		SQL request to parse
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

?>
