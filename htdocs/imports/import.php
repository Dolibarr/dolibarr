<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/imports/import.php
 *      \ingroup    import
 *      \brief      Page d'edition d'un import
 *      \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/imports/import.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/import/modules_import.php');

$langs->load("exports");


if (! $user->societe_id == 0)
accessforbidden();

$entitytoicon=array(
	'invoice'=>'bill','invoice_line'=>'bill',
	'order'=>'order' ,'order_line'=>'order',
	'intervention'=>'intervention' ,'inter_line'=>'intervention',
	'member'=>'user' ,'member_type'=>'group','subscription'=>'payment',
	'tax'=>'generic' ,'tax_type'=>'generic',
	'account'=>'account',
	'payment'=>'payment',
	'product'=>'product','stock'=>'generic','warehouse'=>'stock',
	'category'=>'generic',
	'other'=>'generic'
);
$entitytolang=array(		// Translation code
	'user'=>'User',
	'company'=>'Company','contact'=>'Contact',
	'invoice'=>'Bill','invoice_line'=>'InvoiceLine',
	'order'=>'Order','order_line'=>'OrderLine',
	'intervention'=>'Intervention' ,'inter_line'=>'InterLine',
	'member'=>'Member','member_type'=>'MemberType','subscription'=>'Subscription',
	'tax'=>'SocialContribution','tax_type'=>'DictionnarySocialContributions',
	'account'=>'BankTransactions',
	'payment'=>'Payment',
	'product'=>'Product','stock'=>'Stock','warehouse'=>'Warehouse',
	'category'=>'Category',
	'other'=>'Other'
);

$array_selected=isset($_SESSION["import_selected_fields"])?$_SESSION["import_selected_fields"]:array();
$datatoimport=isset($_GET["datatoimport"])? $_GET["datatoimport"] : (isset($_POST["datatoimport"])?$_POST["datatoimport"]:'');
$action=isset($_GET["action"]) ? $_GET["action"] : (isset($_POST["action"])?$_POST["action"]:'');
$step=isset($_GET["step"])? $_GET["step"] : (isset($_POST["step"])?$_POST["step"]:1);
$import_name=isset($_POST["import_name"])? $_POST["import_name"] : '';
$hexa=isset($_POST["hexa"])? $_POST["hexa"] : '';
$importmodelid=isset($_POST["importmodelid"])? $_POST["importmodelid"] : '';

$objimport=new Import($db);
$objimport->load_arrays($user,$datatoimport);

$objmodelimport=new ModeleImports();
$html = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$sqlusedforimport='';


/*
 * Actions
 */

if ($action=='selectfield')
{
	if ($_GET["field"]=='all')
	{
		$fieldsarray=$objimport->array_import_alias[0];
		foreach($fieldsarray as $key=>$val)
		{
			if (! empty($array_selected[$key])) continue;		// If already selected, select next
			$array_selected[$key]=sizeof($array_selected)+1;
			//print_r($array_selected);
			$_SESSION["import_selected_fields"]=$array_selected;
		}
	}
	else
	{
		$array_selected[$_GET["field"]]=sizeof($array_selected)+1;
		//print_r($array_selected);
		$_SESSION["import_selected_fields"]=$array_selected;
	}

}
if ($action=='unselectfield')
{
	if ($_GET["field"]=='all')
	{
		$array_selected=array();
		$_SESSION["import_selected_fields"]=$array_selected;
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
		$_SESSION["import_selected_fields"]=$array_selected;
	}
}
if ($action=='downfield' || $action=='upfield')
{
	$pos=$array_selected[$_GET["field"]];
	if ($action=='downfield') $newpos=$pos+1;
	if ($action=='upfield') $newpos=$pos-1;
	// Recherche code avec qui switch�
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
	if ($newcode)   // Si newcode trouv� (prtoection contre resoumission de page
	{
		$array_selected[$_GET["field"]]=$newpos;
		$array_selected[$newcode]=$pos;
		$_SESSION["import_selected_fields"]=$array_selected;
	}
}

if ($step == 1 || $action == 'cleanselect')
{
	$_SESSION["import_selected_fields"]=array();
	$array_selected=array();
}

if ($action == 'builddoc')
{
	// Build import file
	$result=$objimport->build_file($user, $_POST['model'], $datatoimport, $array_selected);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$objimport->error.'</div>';
	}
	else
	{
		$mesg='<div class="ok">'.$langs->trans("FileSuccessfullyBuilt").'</div>';
		$sqlusedforimport=$objimport->sqlusedforimport;
	}
}

if ($action == 'deleteprof')
{
	if ($_GET["id"])
	{
		$objimport->fetch($_GET["id"]);
		$result=$objimport->delete($user);
	}
}

if ($action == 'add_import_model')
{
	if ($import_name)
	{
		asort($array_selected);

		// Set save string
		$hexa='';
		foreach($array_selected as $key=>$val)
		{
			if ($hexa) $hexa.=',';
			$hexa.=$key;
		}

		$objimport->model_name = $import_name;
		$objimport->datatoimport = $datatoimport;
		$objimport->hexa = $hexa;

		$result = $objimport->create($user);
		if ($result >= 0)
		{
			$mesg='<div class="ok">'.$langs->trans("ImportModelSaved",$objimport->model_name).'</div>';
		}
		else
		{
			$langs->load("errors");
			if ($objimport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$mesg='<div class="error">'.$langs->trans("ErrorImportDuplicateProfil").'</div>';
			}
			else $mesg='<div class="error">'.$objimport->error.'</div>';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("ImportModelName")).'</div>';
	}
}

if ($step == 2 && $action == 'select_model')
{
	$_SESSION["import_selected_fields"]=array();
	$array_selected=array();
	$result = $objimport->fetch($importmodelid);
	if ($result > 0)
	{
		$fieldsarray=split(',',$objimport->hexa);
		$i=1;
		foreach($fieldsarray as $val)
		{
			$array_selected[$val]=$i;
			$i++;
		}
		$_SESSION["import_selected_fields"]=$array_selected;
	}
}



/*
 * Affichage Pages des Etapes
 */

$objmodelimport=new ModeleImports();


if ($step == 1 || ! $datatoimport)
{
	llxHeader('',$langs->trans("NewImport"));

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$hselected=$h;
	$h++;

	/*
	 $head[$h][0] = '';
	 $head[$h][1] = $langs->trans("Step")." 2";
	 $h++;
	 */

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));


	print '<table class="notopnoleftnoright" width="100%">';

	print $langs->trans("SelectImportDataSet").'<br>';

	// Affiche les modules d'imports
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	print '<td>'.$langs->trans("ImportableDatas").'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';
	$val=true;
	if (sizeof($objimport->array_import_code))
	{
		foreach ($objimport->array_import_code as $key => $value)
		{
			$val=!$val;
			print '<tr '.$bc[$val].'><td nospan="nospan">';
			//print img_object($objimport->array_import_module[$key]->getName(),$import->array_import_module[$key]->picto).' ';
			print $objimport->array_import_module[$key]->getName();
			print '</td><td>';
			//print $value;
			print img_object($objimport->array_import_module[$key]->getName(),$objimport->array_import_icon[$key]).' ';
			print $objimport->array_import_label[$key];
			print '</td><td align="right">';
			if ($objimport->array_import_perms[$key])
			{
				print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$objimport->array_import_code[$key].'">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
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
		print '<tr><td '.$bc[false].' colspan="3">'.$langs->trans("NoImportableData").'</td></tr>';
	}
	print '</table>';

	print '</table>';

	print '</div>';

	if ($mesg) print $mesg;

}

if ($step == 2 && $datatoimport)
{
	llxHeader('',$langs->trans("NewImport"));

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));


	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	print '</table>';
	print '<br>';



	print '<form name="userfile" action="index.php" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	$var=true;

	// Add help informations
	print '<tr class="liste_titre"><td colspan="2">';
	print $langs->trans("FileMustHaveOneOfFollowingFormat");
	print '</td></tr>';
	$liste=$objmodelimport->liste_modeles($db);
	foreach($liste as $key)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
        print '<td width="20">'.img_picto_common($key,$objmodelimport->getPicto($key)).'</td>';
        print '<td>'.$objmodelimport->getDriverLabel($key).'</td>';
		//print '<td>'.$objmodelimport->getLibLabel($key).'</td><td>'.$objmodelimport->getLibVersion($key).'</td>';
		print '</tr>';
	}

	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FileWithDataToImport").'</td></tr>';

	// Input file name box
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">';
	print '<input type="file"   name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
	print '<input type="submit" class="button" value="'.$langs->trans("Upload").'" name="sendit">';
	//print ' &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><br>';

	print "</tr>\n";
	print '</table></form>';

	if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
	{
		$imp = new DolibarrImport($db);
		print "eee".$conf->import->dir_temp;
		create_ext_dir($conf->import->dir_temp);
		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $imp->upload_dir . "/" . $_FILES['userfile']['name'],1) > 0)
		{

			$imp->ImportClients($imp->upload_dir . "/" . $_FILES['userfile']['name']);

			print "Imports : ".$imp->nb_import."<br>";
			print "Imports corrects : ".$imp->nb_import_ok."<br>";
			print "Imports erreurs : ".$imp->nb_import_ko."<br>";

		}
		else
		{
			$mesg = "Files was not read";
		}
	}

	// List deroulante des modeles d'import
	/*		print '<form action="import.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="select_model">';
	print '<input type="hidden" name="step" value="2">';
	print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
	print '<table><tr><td>';
	print $langs->trans("SelectImportFields");
	print '</td><td>';
	$htmlother->select_import_model($importmodelid,'importmodelid',$datatoimport,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Select").'">';
	print '</td></tr></table>';
	print '</form>';


	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	print '<td>'.$langs->trans("ImportableFields").'</td>';
	print '<td width="12" align="middle">';
	print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoimport='.$datatoimport.'&action=selectfield&field=all">'.$langs->trans("All")."</a>";
	print '/';
	print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoimport='.$datatoimport.'&action=unselectfield&field=all">'.$langs->trans("None")."</a>";
	print '</td>';
	print '<td width="44%">'.$langs->trans("ImportedFields").'</td>';
	print '</tr>';

	// Champs importables
	$fieldsarray=$objimport->array_import_fields[0];

	#    $this->array_import_module[0]=$module;
	#    $this->array_import_code[0]=$module->import_code[$r];
	#    $this->array_import_label[0]=$module->import_label[$r];
	#    $this->array_import_sql[0]=$module->import_sql[$r];
	#    $this->array_import_fields[0]=$module->import_fields_array[$r];
	#    $this->array_import_entities[0]=$module->import_fields_entities[$r];
	#    $this->array_import_alias[0]=$module->import_fields_alias[$r];

	$var=true;
	$i = 0;

	foreach($fieldsarray as $code=>$label)
	{
	$var=!$var;
	print "<tr $bc[$var]>";

	$i++;

	$entity=$objimport->array_import_entities[0][$code];
	$entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
	$entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

	print '<td nowrap="nowrap">'.img_object('',$entityicon).' '.$langs->trans($entitylang).'</td>';
	if ((isset($array_selected[$code]) && $array_selected[$code]) || $modelchoice == 1)
	{
	// Selected fields
	print '<td>&nbsp;</td>';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoimport='.$datatoimport.'&action=unselectfield&field='.$code.'">'.img_left().'</a></td>';
	print '<td>'.$langs->trans($label).' ('.$code.')</td>';
	$bit=1;
	}
	else
	{
	// Fields not selected
	print '<td>'.$langs->trans($label).' ('.$code.')</td>';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?step=2&datatoimport='.$datatoimport.'&action=selectfield&field='.$code.'">'.img_right().'</a></td>';
	print '<td>&nbsp;</td>';
	$bit=0;
	}

	print '</tr>';
	$save_select.=$bit;
	}

	print '</table>';
	*/

	print '</div>';

	if ($mesg) print $mesg;

	/*
	 * Barre d'action
	 *
	 */
	print '<div class="tabsAction">';

	if (sizeof($array_selected))
	{
		print '<a class="butAction" href="import.php?step=3&datatoimport='.$datatoimport.'">'.$langs->trans("NextStep").'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';

}

if ($step == 3 && $datatoimport)
{
	asort($array_selected);

	llxHeader('',$langs->trans("NewImport"));

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=3&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));

	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donn�es � importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	// Nbre champs import�s
	print '<tr><td width="25%">'.$langs->trans("ImportedFields").'</td>';
	$list='';
	foreach($array_selected as $code=>$value)
	{
		$list.=($list?',':'');
		$list.=$langs->trans($objimport->array_import_fields[0][$code]);
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '<br>';

	print $langs->trans("ChooseFieldsOrdersAndTitle").'<br>';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	print '<td>'.$langs->trans("ImportedFields").'</td>';
	print '<td align="right" colspan="2">'.$langs->trans("Position").'</td>';
	print '<td>&nbsp;</td>';
	print '<td>'.$langs->trans("FieldsTitle").'</td>';
	print '</tr>';

	$var=true;
	foreach($array_selected as $code=>$value)
	{
		$var=!$var;
		print "<tr $bc[$var]>";

		$entity=$objimport->array_import_entities[0][$code];
		$entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
		$entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

		print '<td>'.img_object('',$entityicon).' '.$langs->trans($entitylang).'</td>';

		print '<td>'.$langs->trans($objimport->array_import_fields[0][$code]).' ('.$code.')</td>';

		print '<td align="right" width="100">';
		print $value.' ';
		print '</td><td align="center" width="20">';
		if ($value < sizeof($array_selected)) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoimport='.$datatoimport.'&action=downfield&field='.$code.'">'.img_down().'</a>';
		if ($value > 1) print '<a href="'.$_SERVER["PHP_SELF"].'?step=3&datatoimport='.$datatoimport.'&action=upfield&field='.$code.'">'.img_up().'</a>';
		print '</td>';

		print '<td>&nbsp;</td>';

		print '<td>'.$langs->trans($objimport->array_import_fields[0][$code]).'</td>';

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

	if (sizeof($array_selected))
	{
		print '<a class="butAction" href="import.php?step=4&datatoimport='.$datatoimport.'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';


	// Area for profils import
	if (sizeof($array_selected))
	{
		print '<br>';
		print $langs->trans("SaveImportModel");

		print '<form class="nocellnopadd" action="import.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_import_model">';
		print '<input type="hidden" name="step" value="'.$step.'">';
		print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
		print '<input type="hidden" name="hexa" value="'.$hexa.'">';

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ImportModelName").'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=false;
		print '<tr '.$bc[$var].'>';
		print '<td><input name="import_name" size="32" value=""></td><td align="right">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '</td></tr>';

		// List of existing import profils
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."import_model";
		$sql.= " WHERE type = '".$datatoimport."'";
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
				print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoimport='.$datatoimport.'&action=deleteprof&id='.$obj->rowid.'">';
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

if ($step == 4 && $datatoimport)
{
	asort($array_selected);

	llxHeader('',$langs->trans("NewImport"));

	/*
	 * Affichage onglets
	 */
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=3&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=4&datatoimport='.$datatoimport;
	$head[$h][1] = $langs->trans("Step")." 4";
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("NewImport"));

	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td width="25%">'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	// Nbre champs importes
	print '<tr><td width="25%">'.$langs->trans("ImportedFields").'</td>';
	$list='';
	foreach($array_selected as $code=>$label)
	{
		$list.=($list?',':'');
		$list.=$langs->trans($objimport->array_import_fields[0][$code]);
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '<br>';

	print $langs->trans("NowClickToGenerateToBuildImportFile").'<br>';

	// Liste des formats d'imports disponibles
	$var=true;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("AvailableFormats").'</td>';
	print '<td>'.$langs->trans("LibraryUsed").'</td>';
	print '<td>'.$langs->trans("LibraryVersion").'</td>';
	print '</tr>';

	$liste=$objmodelimport->liste_modeles($db);
	foreach($liste as $key)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'><td>'.$objmodelimport->getDriverLabel($key).'</td><td>'.$objmodelimport->getLibLabel($key).'</td><td>'.$objmodelimport->getLibVersion($key).'</td></tr>';
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
	if ($sqlusedforimport && $user->admin)
	{
		print '<tr><td>';
		print info_admin($langs->trans("SQLUsedForImport").':<br> '.$sqlusedforimport);
		print '</td></tr>';
	}
	print '</table>';

	print '<table width="100%"><tr><td width="50%">';

	if (! is_dir($conf->import->dir_temp)) create_exdir($conf->import->dir_temp);

	// Affiche liste des documents
	// NB: La fonction show_documents rescanne les modules qd genallowed=1
	$formfile->show_documents('import','',$conf->import->dir_temp.'/'.$user->id,$_SERVER["PHP_SELF"].'?step=4&datatoimport='.$datatoimport,$liste,1,(! empty($_POST['model'])?$_POST['model']:'csv'),'',1);

	print '</td><td width="50%">&nbsp;</td></tr>';
	print '</table>';

	// If external library PHPEXCELREADER is available
	// and defined by PHPEXCELREADER constant.
	if (file_exists(PHPEXCELREADER.'excelreader.php'))
	{
		// Test d'affichage du tableau excel et csv
		//print '<table width="100%"><tr><td>';
		//require_once(DOL_DOCUMENT_ROOT.'/lib/viewfiles.lib.php');
		//viewExcelFileContent($conf->import->dir_temp.'/1/import_member_1.xls',5,3);
		//viewCsvFileContent($conf->import->dir_temp.'/1/import_member_1.csv',5);
		//print '</td></tr></table>';
	}
}


print '<br>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
