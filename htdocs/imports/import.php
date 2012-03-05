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
 *      \file       htdocs/imports/import.php
 *      \ingroup    import
 *      \brief      Pages of import Wizard
 */

require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/imports/class/import.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/import.lib.php");

$langs->load("exports");
$langs->load("errors");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'import',$origin_id,'');

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

$datatoimport		= GETPOST('datatoimport');
$format				= GETPOST('format');
$filetoimport		= GETPOST('filetoimport');
$action				= GETPOST('action');
$step				= (GETPOST('step') ? GETPOST('step') : 1);
$import_name		= GETPOST('import_name');
$hexa				= GETPOST('hexa');
$importmodelid		= GETPOST('importmodelid');
$excludefirstline	= (GETPOST('excludefirstline') ? GETPOST('excludefirstline') : 0);

$objimport=new Import($db);
$objimport->load_arrays($user,($step==1?'':$datatoimport));

$objmodelimport=new ModeleImports();

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);

// Init $array_match_file_to_database from _SESSION
$serialized_array_match_file_to_database=isset($_SESSION["dol_array_match_file_to_database"])?$_SESSION["dol_array_match_file_to_database"]:'';
$array_match_file_to_database=array();
$fieldsarray=explode(',',$serialized_array_match_file_to_database);
foreach($fieldsarray as $elem)
{
	$tabelem=explode('=',$elem,2);
	$key=$tabelem[0];
	$val=$tabelem[1];
	if ($key && $val)
	{
		$array_match_file_to_database[$key]=$val;
	}
}


/*
 * Actions
 */

/*
if ($action=='downfield' || $action=='upfield')
{
	$pos=$array_match_file_to_database[$_GET["field"]];
	if ($action=='downfield') $newpos=$pos+1;
	if ($action=='upfield') $newpos=$pos-1;
	// Recherche code avec qui switcher
	$newcode="";
	foreach($array_match_file_to_database as $code=>$value)
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
		$array_match_file_to_database[$_GET["field"]]=$newpos;
		$array_match_file_to_database[$newcode]=$pos;
		$_SESSION["dol_array_match_file_to_database"]=$serialized_array_match_file_to_database;
	}
}
*/
if ($action == 'builddoc')
{
	// Build import file
	$result=$objimport->build_file($user, $_POST['model'], $datatoimport, $array_match_file_to_database);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$objimport->error.'</div>';
	}
	else
	{
		$mesg='<div class="ok">'.$langs->trans("FileSuccessfullyBuilt").'</div>';
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

// Save import config to database
if ($action == 'add_import_model')
{
	if ($import_name)
	{
		// Set save string
		$hexa='';
		foreach($array_match_file_to_database as $key=>$val)
		{
			if ($hexa) $hexa.=',';
			$hexa.=$key.'='.$val;
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

if ($step == 3 && $datatoimport)
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
	{
		dol_mkdir($conf->import->dir_temp);
		$nowyearmonth=dol_print_date(dol_now(),'%Y%m%d%H%M%S');

		$fullpath=$conf->import->dir_temp . "/" . $nowyearmonth . '-'.$_FILES['userfile']['name'];
		if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath,1) > 0)
		{
			dol_syslog("File ".$fullpath." was added for import");
		}
		else
		{
			$langs->load("errors");
			$mesg = $langs->trans("ErrorFailedToSaveFile");
		}
	}
}

if ($step == 4 && $action == 'select_model')
{
	// Reinit match arrays
	$_SESSION["dol_array_match_file_to_database"]='';
	$serialized_array_match_file_to_database='';
	$array_match_file_to_database=array();

	// Load model from $importmodelid and set $array_match_file_to_database
	// and $_SESSION["dol_array_match_file_to_database"]
	$result = $objimport->fetch($importmodelid);
	if ($result > 0)
	{
		$serialized_array_match_file_to_database=$objimport->hexa;
		$fieldsarray=explode(',',$serialized_array_match_file_to_database);
		foreach($fieldsarray as $elem)
		{
			$tabelem=explode('=',$elem);
			$key=$tabelem[0];
			$val=$tabelem[1];
			if ($key && $val)
			{
				$array_match_file_to_database[$key]=$val;
			}
		}
		$_SESSION["dol_array_match_file_to_database"]=$serialized_array_match_file_to_database;
	}
}

if ($action == 'saveorder')
{
	// Enregistrement de la position des champs
	dol_syslog("boxorder=".$_GET['boxorder']." datatoimport=".$_GET["datatoimport"], LOG_DEBUG);
	$part=explode(':',$_GET['boxorder']);
	$colonne=$part[0];
	$list=$part[1];
	dol_syslog('column='.$colonne.' list='.$list);

	// Init targets fields array
	$fieldstarget=$objimport->array_import_fields[0];

	// Reinit match arrays. We redefine array_match_file_to_database
	$serialized_array_match_file_to_database='';
	$array_match_file_to_database=array();
	$fieldsarray=explode(',',$list);
	$pos=0;
	foreach($fieldsarray as $fieldnb)	// For each elem in list. fieldnb start from 1 to ...
	{
		// Get name of database fields at position $pos and put it into $namefield
		$posbis=0;$namefield='';
		foreach($fieldstarget as $key => $val)	// key:   val:
		{
			//dol_syslog('AjaxImport key='.$key.' val='.$val);
			if ($posbis < $pos)
			{
				$posbis++;
				continue;
			}
			// We found the key of targets that is at position pos
			$namefield=$key;
			//dol_syslog('AjaxImport Field name found for file field nb '.$fieldnb.'='.$namefield);

			break;
		}

		if ($fieldnb && $namefield)
		{
			$array_match_file_to_database[$fieldnb]=$namefield;
			if ($serialized_array_match_file_to_database) $serialized_array_match_file_to_database.=',';
			$serialized_array_match_file_to_database.=($fieldnb.'='.$namefield);
		}

		$pos++;
	}

	// We save new matching in session
	$_SESSION["dol_array_match_file_to_database"]=$serialized_array_match_file_to_database;
	dol_syslog('dol_array_match_file_to_database='.$serialized_array_match_file_to_database);
}




/*
 * View
 */


// STEP 1: Page to select dataset to import
if ($step == 1 || ! $datatoimport)
{
	// Clean saved file-database matching
	$serialized_array_match_file_to_database='';
	$array_match_file_to_database=array();
	$_SESSION["dol_array_match_file_to_database"]='';

	$parm='';
	if ($excludefirstline) $param.='&excludefirstline=1';

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param, 1);

	dol_fiche_head($head, 'step1', $langs->trans("NewImport"));


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
	if (count($objimport->array_import_code))
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
				print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=2&datatoimport='.$objimport->array_import_code[$key].$param.'">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
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

    dol_fiche_end();

	if ($mesg) print $mesg;

}


// STEP 2: Page to select input format file
if ($step == 2 && $datatoimport)
{
	$param='&datatoimport='.$datatoimport;
	if ($excludefirstline) $param.='&excludefirstline=1';

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param,2);

	dol_fiche_head($head, 'step2', $langs->trans("NewImport"));


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
	print '<br>'."\n";


	print '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

	print $langs->trans("ChooseFormatOfFileToImport",img_picto('','filenew')).'<br>';
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	$filetoimport='';
	$var=true;

	// Add format informations and link to download example
	print '<tr class="liste_titre"><td colspan="6">';
	print $langs->trans("FileMustHaveOneOfFollowingFormat");
	print '</td></tr>';
	$liste=$objmodelimport->liste_modeles($db);
	foreach($liste as $key)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td width="16">'.img_picto_common($key,$objmodelimport->getPicto($key)).'</td>';
    	$text=$objmodelimport->getDriverDesc($key);
    	print '<td>'.$form->textwithpicto($objmodelimport->getDriverLabel($key),$text).'</td>';
		print '<td align="center"><a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$key.$param.'" target="_blank">'.$langs->trans("DownloadEmptyExample").'</a></td>';
		// Action button
		print '<td align="right">';
		print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=3&format='.$key.$param.'">'.img_picto($langs->trans("SelectFormat"),'filenew').'</a>';
		print '</td>';
		print '</tr>';
	}

	print '</table></form>';

    dol_fiche_end();

	if ($mesg) print $mesg;
}


// STEP 3: Page to select file
if ($step == 3 && $datatoimport)
{
	$param='&datatoimport='.$datatoimport.'&format='.$format;
	if ($excludefirstline) $param.='&excludefirstline=1';

	$liste=$objmodelimport->liste_modeles($db);

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param,3);

	dol_fiche_head($head, 'step3', $langs->trans("NewImport"));


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

	print '</table><br>';
	print '<b>'.$langs->trans("InformationOnSourceFile").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnSourceFile").'</b></td></tr>';

	// Source file format
	print '<tr><td width="25%">'.$langs->trans("SourceFileFormat").'</td>';
	print '<td>';
    $text=$objmodelimport->getDriverDesc($format);
    print $form->textwithpicto($objmodelimport->getDriverLabel($format),$text);
    print '</td><td align="right" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/imports/emptyexample.php?format='.$format.$param.'" target="_blank">'.$langs->trans("DownloadEmptyExample").'</a>';

	print '</td></tr>';

	print '</table>';
	print '<br>'."\n";


	print '<form name="userfile" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" METHOD="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

	$filetoimport='';
	$var=true;

	print '<tr><td colspan="6">'.$langs->trans("ChooseFileToImport",img_picto('','filenew')).'</td></tr>';

	print '<tr class="liste_titre"><td colspan="6">'.$langs->trans("FileWithDataToImport").'</td></tr>';

	// Input file name box
	$var=false;
	print '<tr '.$bc[$var].'><td colspan="6">';
	print '<input type="file"   name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
	print '<input type="submit" class="button" value="'.$langs->trans("AddFile").'" name="sendit">';
	print '<input type="hidden" value="'.$step.'" name="step">';
	print '<input type="hidden" value="'.$format.'" name="format">';
	print '<input type="hidden" value="'.$excludefirstline.'" name="excludefirstline">';
	print '<input type="hidden" value="'.$datatoimport.'" name="datatoimport">';
	print "</tr>\n";

	// Search available imports
	$filearray=dol_dir_list($conf->import->dir_temp,'files',0,'','','name',SORT_DESC);
	if (count($filearray) > 0)
	{
		$dir=$conf->import->dir_temp;

		// Search available files to import
		$i=0;
		foreach ($filearray as $key => $val)
		{
		    $file=$val['name'];

			// readdir return value in ISO and we want UTF8 in memory
			if (! utf8_check($file)) $file=utf8_encode($file);

			if (preg_match('/^\./',$file)) continue;

			$modulepart='import';
			$urlsource=$_SERVER["PHP_SELF"].'?step='.$step.$param.'&filetoimport='.urlencode($filetoimport);
			$relativepath=$file;
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td width="16">'.img_mime($file).'</td>';
			print '<td>';
			$modulepart='import';
			//$relativepath=$filetoimport;
    		print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=3'.$param.'" target="_blank">';
    		print $file;
    		print '</a>';
			print '</td>';
			// Affiche taille fichier
			print '<td align="right">'.dol_print_size(dol_filesize($dir.'/'.$file)).'</td>';
			// Affiche date fichier
			print '<td align="right">'.dol_print_date(dol_filemtime($dir.'/'.$file),'dayhour').'</td>';
			// Del button
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&step=3'.$param.'&modulepart='.$modulepart.'&file='.urlencode($relativepath);
			print '&urlsource='.urlencode($urlsource);
			print '">'.img_delete().'</a></td>';
			// Action button
			print '<td align="right">';
			print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=4'.$param.'&filetoimport='.urlencode($relativepath).'">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
			print '</td>';
			print '</tr>';
		}
	}

	print '</table></form>';


    dol_fiche_end();

	if ($mesg) print $mesg;
}


// STEP 4: Page to make matching between source file and database fields
if ($step == 4 && $datatoimport)
{
	$model=$format;
	$liste=$objmodelimport->liste_modeles($db);

	// Create classe to use for import
	$dir = DOL_DOCUMENT_ROOT . "/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once($dir.$file);
	$obj = new $classname($db,$datatoimport);

	// Load source fields in input file
	$fieldssource=array();
	$result=$obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport,$langs);
	if ($result >= 0)
	{
		// Read first line
		$arrayrecord=$obj->import_read_record();
		// Put into array fieldssource starting with 1.
		$i=1;
		foreach($arrayrecord as $key => $val)
		{
			$fieldssource[$i]['example1']=dol_trunc($val['val'],24);
			$i++;
		}
		$obj->import_close_file();
	}

	// Load targets fields in database
	$fieldstarget=$objimport->array_import_fields[0];

	$maxpos=max(count($fieldssource),count($fieldstarget));

	//var_dump($array_match_file_to_database);

	// Is it a first time in page (if yes, we must initialize array_match_file_to_database)
	if (count($array_match_file_to_database) == 0)
	{
		// This is first input in screen, we need to define
		// $array_match_file_to_database
		// $serialized_array_match_file_to_database
		// $_SESSION["dol_array_match_file_to_database"]
		$pos=1;
		$num=count($fieldssource);
		while ($pos <= $num)
		{
			if ($num >= 1 && $pos <= $num)
			{
				$posbis=1;
				foreach($fieldstarget as $key => $val)
				{
					if ($posbis < $pos)
					{
						$posbis++;
						continue;
					}
					// We found the key of targets that is at position pos
					$array_match_file_to_database[$pos]=$key;
					if ($serialized_array_match_file_to_database) $serialized_array_match_file_to_database.=',';
					$serialized_array_match_file_to_database.=($pos.'='.$key);
					break;
				}
			}
			$pos++;
		}
		// Save the match array in session. We now will use the array in session.
		$_SESSION["dol_array_match_file_to_database"]=$serialized_array_match_file_to_database;
	}
	$array_match_database_to_file=array_flip($array_match_file_to_database);

	//print $serialized_array_match_file_to_database;
	//print $_SESSION["dol_array_match_file_to_database"];
	//var_dump($array_match_file_to_database);exit;

	// Now $array_match_file_to_database contains  fieldnb(1,2,3...)=>fielddatabase(key in $array_match_file_to_database)

	$param='&format='.$format.'&datatoimport='.$datatoimport.'&filetoimport='.urlencode($filetoimport);
	if ($excludefirstline) $param.='&excludefirstline=1';

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param,4);

	dol_fiche_head($head, 'step4', $langs->trans("NewImport"));

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

	print '</table><br>';
	print '<b>'.$langs->trans("InformationOnSourceFile").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnSourceFile").'</b></td></tr>';

	// Source file format
	print '<tr><td width="25%">'.$langs->trans("SourceFileFormat").'</td>';
	print '<td>';
    $text=$objmodelimport->getDriverDesc($format);
    print $form->textwithpicto($objmodelimport->getDriverLabel($format),$text);
	print '</td></tr>';

	// File to import
	print '<tr><td width="25%">'.$langs->trans("FileToImport").'</td>';
	print '<td>';
	$modulepart='import';
	$relativepath=GETPOST('filetoimport');
    print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank">';
    print $filetoimport;
    print '</a>';
	print '</td></tr>';

	print '</table>';
	print '<br>'."\n";


    // List of source fields
    print '<!-- List of source fields -->'."\n";
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="select_model">';
    print '<input type="hidden" name="step" value="4">';
    print '<input type="hidden" name="format" value="'.$format.'">';
    print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
    print '<input type="hidden" name="filetoimport" value="'.$filetoimport.'">';
    print '<input type="hidden" name="excludefirstline" value="'.$excludefirstline.'">';
    print '<table><tr><td colspan="2">';
    print $langs->trans("SelectImportFields",img_picto('','uparrow','')).' ';
    $htmlother->select_import_model($importmodelid,'importmodelid',$datatoimport,1);
    print '<input type="submit" class="button" value="'.$langs->trans("Select").'">';
    print '</td></tr></table>';
    print '</form>';

	// Title of array with fields
	print '<table class="nobordernopadding" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("FieldsInSourceFile").'</td>';
	print '<td>'.$langs->trans("FieldsInTargetDatabase").'</td>';
	print '</tr>';

	//var_dump($array_match_file_to_database);

	print '<tr valign="top"><td width="50%">';

	$fieldsplaced=array();
	$valforsourcefieldnb=array();
	$listofkeys=array();
	foreach($array_match_file_to_database as $key => $val)
	{
		$listofkeys[$key]=1;
	}

	print "\n<!-- Box left container -->\n";
	print '<div id="left" class="connectedSortable">'."\n";

	// List of source fields
	$var=true;
	$lefti=1;
	foreach ($array_match_file_to_database as $key => $val)
	{
		$var=!$var;
		show_elem($fieldssource,$key,$val,$var);		// key is field number in source file
		//print '> '.$lefti.'-'.$key.'-'.$val;
		$listofkeys[$key]=1;
		$fieldsplaced[$key]=1;
		$valforsourcefieldnb[$lefti]=$key;
		$lefti++;

		if ($lefti > count($fieldstarget)) break;	// Other fields are in the not imported area
	}
	//var_dump($valforsourcefieldnb);

	// Complete source fields from count($fieldssource)+1 to count($fieldstarget)
	$more=1;
	$num=count($fieldssource);
	while ($lefti <= $num)
	{
		$var=!$var;
		$newkey=getnewkey($fieldssource,$listofkeys);
		show_elem($fieldssource,$newkey,'',$var);	// key start after field number in source file
		//print '> '.$lefti.'-'.$newkey;
		$listofkeys[$key]=1;
		$lefti++;
		$more++;
	}

	print "</div>\n";
	print "<!-- End box container -->\n";


	print '</td><td width="50%">';

	// List of targets fields
	$i = 0;
	$var=true;
	$mandatoryfieldshavesource=true;
	print '<table width="100%" class="nobordernopadding">';
	foreach($fieldstarget as $code=>$label)
	{
		$var=!$var;
		print '<tr '.$bc[$var].' height="20">';

		$i++;

		$entity=(! empty($objimport->array_import_entities[0][$code])?$objimport->array_import_entities[0][$code]:$objimport->array_import_icon[0]);
		$tablealias=preg_replace('/(\..*)$/i','',$code);
		$tablename=$objimport->array_import_tables[0][$tablealias];
		$entityicon=$entitytoicon[$entity]?$entitytoicon[$entity]:$entity;
		$entitylang=$entitytolang[$entity]?$entitytolang[$entity]:$entity;

		print '<td nowrap="nowrap" style="font-weight: normal">=>'.img_object('',$entityicon).' '.$langs->trans($entitylang).'</td>';
		print '<td style="font-weight: normal">';
		$newlabel=preg_replace('/\*$/','',$label);
		$text=$langs->trans($newlabel);
		$more='';
		if (preg_match('/\*$/',$label))
		{
			$text='<span class="fieldrequired">'.$text.'</span>';
			$more=((! empty($valforsourcefieldnb[$i]) && $valforsourcefieldnb[$i] <= count($fieldssource)) ? '' : img_warning($langs->trans("FieldNeedSource")));
			if ($mandatoryfieldshavesource) $mandatoryfieldshavesource=(! empty($valforsourcefieldnb[$i]) && ($valforsourcefieldnb[$i] <= count($fieldssource)));
			//print 'xx'.($i).'-'.$valforsourcefieldnb[$i].'-'.$mandatoryfieldshavesource;
		}
		print $text;
		print '</td>';
		// Info field
		print '<td style="font-weight: normal" align="right">';
		$filecolumn=$array_match_database_to_file[$code];
		// Source field info
		$htmltext ='<b><u>'.$langs->trans("FieldSource").'</u></b><br>';
		if ($filecolumn > count($fieldssource)) $htmltext.=$langs->trans("DataComeFromNoWhere").'<br>';
		else
		{
			if (empty($objimport->array_import_convertvalue[0][$code]))	// If source file does not need convertion
			{
				$filecolumntoshow=$filecolumn;
				$htmltext.=$langs->trans("DataComeFromFileFieldNb",$filecolumntoshow).'<br>';
			}
			else
			{
				if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromref')    $htmltext.=$langs->trans("DataComeFromIdFoundFromRef",$filecolumn,$langs->transnoentitiesnoconv($entitylang)).'<br>';
				if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromcodeid') $htmltext.=$langs->trans("DataComeFromIdFoundFromCodeId",$filecolumn,$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$code]['dict'])).'<br>';
			}
		}
		$htmltext.=$langs->trans("SourceRequired").': <b>'.yn(preg_match('/\*$/',$label)).'</b><br>';
		$example=$objimport->array_import_examplevalues[0][$code];

		if (empty($objimport->array_import_convertvalue[0][$code]))	// If source file does not need convertion
		{
		    if ($example) $htmltext.=$langs->trans("SourceExample").': <b>'.$example.'</b><br>';
		}
		else
		{
		    if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromref')    $htmltext.=$langs->trans("SourceExample").': <b>'.$langs->transnoentitiesnoconv("ExampleAnyRefFoundIntoElement",$entitylang).($example?' ('.$langs->transnoentitiesnoconv("Example").': '.$example.')':'').'</b><br>';
		    if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromcodeid') $htmltext.=$langs->trans("SourceExample").': <b>'.$langs->trans("ExampleAnyCodeOrIdFoundIntoDictionnary",$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$code]['dict'])).($example?' ('.$langs->transnoentitiesnoconv("Example").': '.$example.')':'').'</b><br>';
		}
		$htmltext.='<br>';
		// Target field info
		$htmltext.='<b><u>'.$langs->trans("FieldTarget").'</u></b><br>';
		if (empty($objimport->array_import_convertvalue[0][$code]))	// If source file does not need convertion
		{
			$htmltext.=$langs->trans("DataIsInsertedInto").'<br>';
		}
		else
		{
			if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromref')    $htmltext.=$langs->trans("DataIDSourceIsInsertedInto").'<br>';
			if ($objimport->array_import_convertvalue[0][$code]['rule']=='fetchidfromcodeid') $htmltext.=$langs->trans("DataCodeIDSourceIsInsertedInto").'<br>';
		}
		$htmltext.=$langs->trans("FieldTitle").": <b>".$langs->trans($newlabel)."</b><br>";
		$htmltext.=$langs->trans("Table")." -> ".$langs->trans("Field").': <b>'.$tablename." -> ".preg_replace('/^.*\./','',$code)."</b><br>";
		print $form->textwithpicto($more,$htmltext);
		print '</td>';

		print '</tr>';
		$save_select.=$bit;
	}
	print '</table>';

	print '</td></tr>';

	// List of not imported fields
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("NotImportedFields").'</td></tr>';

	print '<tr valign="top"><td width="50%">';

	print "\n<!-- Box ignore container -->\n";
	print '<div id="right" class="connectedSortable">'."\n";

	$nbofnotimportedfields=0;
	foreach ($fieldssource as $key => $val)
	{
		if (empty($fieldsplaced[$key]))
		{
			//$var=!$var;
			$nbofnotimportedfields++;
			show_elem($fieldssource,$key,'',$var,'nostyle');
			//print '> '.$lefti.'-'.$key;
			$listofkeys[$key]=1;
			$lefti++;
		}
	}

	// Print one more empty field
	$newkey=getnewkey($fieldssource,$listofkeys);
	show_elem($fieldssource,$newkey,'',$var,'nostyle');
	//print '> '.$lefti.'-'.$newkey;
	$listofkeys[$newkey]=1;
	$nbofnotimportedfields++;

	print "</div>\n";
	print "<!-- End box ignore container -->\n";

	print '</td>';
	print '<td width="50%">';
	$i=0;
	while ($i < $nbofnotimportedfields)
	{
		// Print empty cells
		show_elem('','','none',$var,'nostyle');
		$i++;
	}
	print '</td></tr>';

	print '</table>';

    dol_fiche_end();


	if ($conf->use_javascript_ajax)
	{
        print '<script type="text/javascript" language="javascript">';
        print 'jQuery(function() {
                    jQuery("#left, #right").sortable({
                        /* placeholder: \'ui-state-highlight\', */
                        handle: \'.boxhandle\',
                        revert: \'invalid\',
                        items: \'.box\',
                        containment: \'.fiche\',
                        connectWith: \'.connectedSortable\',
                        stop: function(event, ui) {
                            updateOrder();
                        }
                    });
                });
        ';
        print "\n";
        print 'function updateOrder(){'."\n";
        print 'var left_list = cleanSerialize(jQuery("#left").sortable("serialize" ));'."\n";
        //print 'var right_list = cleanSerialize(jQuery("#right").sortable("serialize" ));'."\n";
        print 'var boxorder = \'A:\' + left_list;'."\n";
        //print 'var boxorder = \'A:\' + left_list + \'-B:\' + right_list;'."\n";
        //print 'alert(\'boxorder=\' + boxorder);';
        //print 'var userid = \''.$user->id.'\';'."\n";
        //print 'var datatoimport = "'.$datatoimport.'";'."\n";
        // print 'jQuery.ajax({ url: "ajaximport.php?step=4&boxorder=" + boxorder + "&userid=" + userid + "&datatoimport=" + datatoimport,
        //                    async: false
        //        });'."\n";
        // Now reload page
        print 'var newlocation= \''.$_SERVER["PHP_SELF"].'?step=4'.$param.'&action=saveorder&boxorder=\' + boxorder;'."\n";
        //print 'alert(newlocation);';
        print 'window.location.href=newlocation;'."\n";
        print '}'."\n";
        print '</script>'."\n";
	}


	if ($mesg) print $mesg;

	/*
	 * Barre d'action
	 */
	print '<div class="tabsAction">';

	if (count($array_match_file_to_database))
	{
		if ($mandatoryfieldshavesource)
		{
			print '<a class="butAction" href="import.php?step=5'.$param.'&filetoimport='.urlencode($filetoimport).'">'.$langs->trans("NextStep").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryFieldHaveNoSource")).'">'.$langs->trans("NextStep").'</a>';
		}
	}

	print '</div>';


	// Area for profils import
	if (count($array_match_file_to_database))
	{
		print '<br>'."\n";
		print '<!-- Area to add new import profile -->'."\n";
		print $langs->trans("SaveImportModel");

		print '<form class="nocellnopadd" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add_import_model">';
		print '<input type="hidden" name="step" value="'.$step.'">';
		print '<input type="hidden" name="format" value="'.$format.'">';
		print '<input type="hidden" name="datatoimport" value="'.$datatoimport.'">';
    	print '<input type="hidden" name="filetoimport" value="'.$filetoimport.'">';
		print '<input type="hidden" name="hexa" value="'.$hexa.'">';

		print '<table summary="selectofimportprofil" class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ImportModelName").'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=false;
		print '<tr '.$bc[$var].'>';
		print '<td><input name="import_name" size="48" value=""></td><td align="right">';
		print '<input type="submit" class="button" value="'.$langs->trans("SaveImportProfile").'">';
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
				print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.$param.'&action=deleteprof&id='.$obj->rowid.'&filetoimport='.urlencode($filetoimport).'">';
				print img_delete();
				print '</a>';
				print '</tr>';
				$i++;
			}
		}
		else {
			dol_print_error($db);
		}

		print '</table>';
		print '</form>';
	}

}


// STEP 5: Summary of choices and launch simulation
if ($step == 5 && $datatoimport)
{
	$model=$format;
	$liste=$objmodelimport->liste_modeles($db);

	// Create classe to use for import
	$dir = DOL_DOCUMENT_ROOT . "/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once($dir.$file);
	$obj = new $classname($db,$datatoimport);

	// Load source fields in input file
	$fieldssource=array();
	$result=$obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport,$langs);
	if ($result >= 0)
	{
		// Read first line
		$arrayrecord=$obj->import_read_record();
		// Put into array fieldssource starting with 1.
		$i=1;
		foreach($arrayrecord as $key => $val)
		{
			$fieldssource[$i]['example1']=dol_trunc($val['val'],24);
			$i++;
		}
		$obj->import_close_file();
	}

	$nboflines=dol_count_nb_of_line($conf->import->dir_temp.'/'.$filetoimport);

	$param='&format='.$format.'&datatoimport='.$datatoimport.'&filetoimport='.urlencode($filetoimport).'&nboflines='.$nboflines;
	$param2='&format='.$format.'&datatoimport='.$datatoimport.'&filetoimport='.urlencode($filetoimport).'&nboflines='.$nboflines;
	if ($excludefirstline) $param.='&excludefirstline=1';

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param,5);

	dol_fiche_head($head, 'step5', $langs->trans("NewImport"));


	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	print '</table><br>';
	print '<b>'.$langs->trans("InformationOnSourceFile").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnSourceFile").'</b></td></tr>';

	// Source file format
	print '<tr><td width="25%">'.$langs->trans("SourceFileFormat").'</td>';
	print '<td>';
    $text=$objmodelimport->getDriverDesc($format);
    print $form->textwithpicto($objmodelimport->getDriverLabel($format),$text);
	print '</td></tr>';

	// File to import
	print '<tr><td>'.$langs->trans("FileToImport").'</td>';
	print '<td>';
	$modulepart='import';
	$relativepath=GETPOST('filetoimport');
    print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank">';
    print $filetoimport;
    print '</a>';
    print '</td></tr>';

	// Nb of fields
	print '<tr><td>';
	print $langs->trans("NbOfSourceLines");
	print '</td><td>';
	print $nboflines;
	print '</td></tr>';

	// Checkbox do not import first line
	print '<tr><td>';
	print $langs->trans("Option");
	print '</td><td>';
	print '<input type="checkbox" name="excludefirstline" value="1"';
	print ($excludefirstline?' checked="checked"':'');
	print ' onClick="javascript: window.location=\''.$_SERVER["PHP_SELF"].'?leftmenu=import&excludefirstline='.($excludefirstline?'0':'1').'&step=5'.$param2.'\';">';
	print ' '.$langs->trans("DoNotImportFirstLine");
	print '</td></tr>';

	print '</table>';

	print '<br>';

	print '<b>'.$langs->trans("InformationOnTargetTables").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnTargetTables").'</b></td></tr>';

	// Tables imported
	print '<tr><td width="25%">';
	print $langs->trans("TablesTarget");
	print '</td><td>';
	$listtables=array();
	$sort_array_match_file_to_database=$array_match_file_to_database;
	foreach($array_match_file_to_database as $code=>$label)
	{
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) continue;
		//print $code.'-'.$label;
		$alias=preg_replace('/(\..*)$/i','',$label);
		$listtables[$alias]=$objimport->array_import_tables[0][$alias];
	}
	if (count($listtables))
	{
		$newval='';
		//ksort($listtables);
		foreach ($listtables as $val)
		{
			if ($newval) print ', ';
			$newval=$val;
			// Link to Dolibarr wiki pages
			/*$helppagename='EN:Table_'.$newval;
			if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
			{
				// Get helpbaseurl, helppage and mode from helppagename and langs
				$arrayres=getHelpParamFor($helppagename,$langs);
				$helpbaseurl=$arrayres['helpbaseurl'];
				$helppage=$arrayres['helppage'];
				$mode=$arrayres['mode'];
				$newval.=' <a href="'.sprintf($helpbaseurl,$helppage).'">'.img_picto($langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage'),DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).'</a>';
			}*/
			print $newval;
		}
	}
	else print $langs->trans("Error");
	print '</td></tr>';

	// Fields imported
	print '<tr><td>';
	print $langs->trans("FieldsTarget").'</td><td>';
	$listfields=array();
	$i=0;
	//print 'fieldsource='.$fieldssource;
	$sort_array_match_file_to_database=$array_match_file_to_database;
	ksort($sort_array_match_file_to_database);
	//var_dump($sort_array_match_file_to_database);
	foreach($sort_array_match_file_to_database as $code=>$label)
	{
		$i++;
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) continue;
		//print $code.'-'.$label;
		$alias=preg_replace('/(\..*)$/i','',$label);
		$listfields[$i]=$langs->trans("Field").' '.$code.'->'.$label;
	}
	print count($listfields)?(join(', ',$listfields)):$langs->trans("Error");
	print '</td></tr>';

	print '</table>';

    dol_fiche_end();


    if (GETPOST('action') != 'launchsimu')
    {
        // Show import id
        print $langs->trans("NowClickToTestTheImport",$langs->transnoentitiesnoconv("RunSimulateImportFile")).'<br>';
        print '<br>';

        // Actions
        print '<center>';
        if ($user->rights->import->run)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import&step=5&action=launchsimu'.$param.'">'.$langs->trans("RunSimulateImportFile").'</a>';
        }
        else
        {
            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("RunSimulateImportFile").'</a>';
        }
        print '</center>';
    }
    else
    {
        // Launch import
        $arrayoferrors=array();
        $arrayofwarnings=array();
        $maxnboferrors=empty($conf->global->IMPORT_MAX_NB_OF_ERRORS)?50:$conf->global->IMPORT_MAX_NB_OF_ERRORS;
        $maxnbofwarnings=empty($conf->global->IMPORT_MAX_NB_OF_WARNINGS)?50:$conf->global->IMPORT_MAX_NB_OF_WARNINGS;
        $nboferrors=0;
        $nbofwarnings=0;

        $importid=dol_print_date(dol_now(),'%Y%m%d%H%M%S');

        //var_dump($array_match_file_to_database);

        $db->begin();

        // Open input file
        $nbok=0;
        $pathfile=$conf->import->dir_temp.'/'.$filetoimport;
        $result=$obj->import_open_file($pathfile,$langs);
        if ($result > 0)
        {
            $sourcelinenb=0;
            // Loop on each input file record
            while ($sourcelinenb < $nboflines)
            {
                $sourcelinenb++;
                // Read line and stor it into $arrayrecord
                $arrayrecord=$obj->import_read_record();
                if ($excludefirstline && $sourcelinenb == 1) continue;

                //
                $result=$obj->import_insert($arrayrecord,$array_match_file_to_database,$objimport,count($fieldssource),$importid);

                if (count($obj->errors))   $arrayoferrors[$sourcelinenb]=$obj->errors;
                if (count($obj->warnings)) $arrayofwarnings[$sourcelinenb]=$obj->warnings;
                if (! count($obj->errors) && ! count($obj->warnings)) $nbok++;
            }
            // Close file
            $obj->import_close_file();
        }
        else
        {
            print $langs->trans("ErrorFailedToOpenFile",$pathfile);
        }

        $db->rollback();    // We force rollback because this was just a simulation.

        // Show OK
        if (! count($arrayoferrors) && ! count($arrayofwarnings)) print img_picto($langs->trans("OK"),'tick').' <b>'.$langs->trans("NoError").'</b><br><br>';
        else print $langs->trans("NbOfLinesOK",$nbok).'</b><br><br>';

        // Show Errors
        //var_dump($arrayoferrors);
        if (count($arrayoferrors))
        {
            print img_error().' <b>'.$langs->trans("ErrorsOnXLines",count($arrayoferrors)).'</b><br>';
            print '<table width="100%" class="border"><tr><td>';
            foreach ($arrayoferrors as $key => $val)
            {
                $nboferrors++;
                if ($nboferrors > $maxnboferrors)
                {
                    print $langs->trans("TooMuchErrors",(count($arrayoferrors)-$nboferrors))."<br>";
                    break;
                }
                print '* '.$langs->trans("Line").' '.$key.'<br>';
                foreach($val as $i => $err)
                {
                    print ' &nbsp; &nbsp; > '.$err['lib'].'<br>';
                }
            }
            print '</td></tr></table>';
            print '<br>';
        }

        // Show Warnings
        //var_dump($arrayoferrors);
        if (count($arrayofwarnings))
        {
            print img_warning().' <b>'.$langs->trans("WarningsOnXLines",count($arrayofwarnings)).'</b><br>';
            print '<table width="100%" class="border"><tr><td>';
            foreach ($arrayofwarnings as $key => $val)
            {
                $nbofwarnings++;
                if ($nbofwarnings > $maxnbofwarnings)
                {
                    print $langs->trans("TooMuchWarnings",(count($arrayofwarnings)-$nbofwarnings))."<br>";
                    break;
                }
                print ' * '.$langs->trans("Line").' '.$key.'<br>';
                foreach($val as $i => $err)
                {
                    print ' &nbsp; &nbsp; > '.$err['lib'].'<br>';
                }
            }
            print '</td></tr></table>';
            print '<br>';
        }

        // Show import id
        $importid=dol_print_date(dol_now(),'%Y%m%d%H%M%S');

        print '<center>';
        print $langs->trans("NowClickToRunTheImport",$langs->transnoentitiesnoconv("RunImportFile")).'<br>';
        print $langs->trans("DataLoadedWithId",$importid).'<br>';
        print '</center>';

        print '<br>';

        // Actions
        print '<center>';
        if ($user->rights->import->run)
        {
            if (empty($nboferrors))
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import&step=6&importid='.$importid.$param.'">'.$langs->trans("RunImportFile").'</a>';
            }
            else
            {
                //print '<a class="butAction" href="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import&step=5&action=launchsimu'.$param.'">'.$langs->trans("RunSimulateImportFile").'</a>';

                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("CorrectErrorBeforeRunningImport")).'">'.$langs->trans("RunImportFile").'</a>';
            }
        }
        else
        {
            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("RunSimulateImportFile").'</a>';

            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("RunImportFile").'</a>';
        }
        print '</center>';
    }

    if ($mesg) print $mesg;
}


// STEP 6: Real import
if ($step == 6 && $datatoimport)
{
	$model=$format;
	$liste=$objmodelimport->liste_modeles($db);
	$importid=$_REQUEST["importid"];


	// Create classe to use for import
	$dir = DOL_DOCUMENT_ROOT . "/core/modules/import/";
	$file = "import_".$model.".modules.php";
	$classname = "Import".ucfirst($model);
	require_once($dir.$file);
	$obj = new $classname($db,$datatoimport);

	// Load source fields in input file
	$fieldssource=array();
	$result=$obj->import_open_file($conf->import->dir_temp.'/'.$filetoimport,$langs);
	if ($result >= 0)
	{
		// Read first line
		$arrayrecord=$obj->import_read_record();
		// Put into array fieldssource starting with 1.
		$i=1;
		foreach($arrayrecord as $key => $val)
		{
			$fieldssource[$i]['example1']=dol_trunc($val['val'],24);
			$i++;
		}
		$obj->import_close_file();
	}

	$nboflines=(! empty($_GET["nboflines"])?$_GET["nboflines"]:dol_count_nb_of_line($conf->import->dir_temp.'/'.$filetoimport));

	$param='&format='.$format.'&datatoimport='.$datatoimport.'&filetoimport='.urlencode($filetoimport).'&nboflines='.$nboflines;
	if ($excludefirstline) $param.='&excludefirstline=1';

	llxHeader('',$langs->trans("NewImport"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

    $head = import_prepare_head($param,6);

	dol_fiche_head($head, 'step6', $langs->trans("NewImport"));


	print '<table width="100%" class="border">';

	// Module
	print '<tr><td width="25%">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_module[0]->picto).' ';
	print $objimport->array_import_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a importer
	print '<tr><td>'.$langs->trans("DatasetToImport").'</td>';
	print '<td>';
	print img_object($objimport->array_import_module[0]->getName(),$objimport->array_import_icon[0]).' ';
	print $objimport->array_import_label[0];
	print '</td></tr>';

	print '</table><br>';
	print '<b>'.$langs->trans("InformationOnSourceFile").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnSourceFile").'</b></td></tr>';

	// Source file format
	print '<tr><td width="25%">'.$langs->trans("SourceFileFormat").'</td>';
	print '<td>';
    $text=$objmodelimport->getDriverDesc($format);
    print $form->textwithpicto($objmodelimport->getDriverLabel($format),$text);
	print '</td></tr>';

	// File to import
	print '<tr><td>'.$langs->trans("FileToImport").'</td>';
	print '<td>';
	$modulepart='import';
    $relativepath=GETPOST('filetoimport');
    print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'&step=4'.$param.'" target="_blank">';
    print $filetoimport;
    print '</a>';
	print '</td></tr>';

	// Nb of fields
	print '<tr><td>';
	print $langs->trans("NbOfSourceLines");
	print '</td><td>';
	print $nboflines;
	print '</td></tr>';

	// Checkbox do not import first line
	print '<tr><td>';
	print $langs->trans("Option");
	print '</td><td>';
	print '<input type="checkbox" name="excludefirstline" value="1" disabled="disabled"';
	print ($excludefirstline?' checked="checked"':'');
	print '>';
	print ' '.$langs->trans("DoNotImportFirstLine");
	print '</td></tr>';

	print '</table>';

	print '<br>';

	print '<b>'.$langs->trans("InformationOnTargetTables").'</b>';
	print '<table width="100%" class="border">';
	//print '<tr><td colspan="2"><b>'.$langs->trans("InformationOnTargetTables").'</b></td></tr>';

	// Tables imported
	print '<tr><td width="25%">';
	print $langs->trans("TablesTarget");
	print '</td><td>';
	$listtables=array();
	foreach($array_match_file_to_database as $code=>$label)
	{
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) continue;
		//print $code.'-'.$label;
		$alias=preg_replace('/(\..*)$/i','',$label);
		$listtables[$alias]=$objimport->array_import_tables[0][$alias];
	}
	if (count($listtables))
	{
		$newval='';
		foreach ($listtables as $val)
		{
			if ($newval) print ', ';
			$newval=$val;
			// Link to Dolibarr wiki pages
			/*$helppagename='EN:Table_'.$newval;
			if ($helppagename && empty($conf->global->MAIN_HELP_DISABLELINK))
			{
				// Get helpbaseurl, helppage and mode from helppagename and langs
				$arrayres=getHelpParamFor($helppagename,$langs);
				$helpbaseurl=$arrayres['helpbaseurl'];
				$helppage=$arrayres['helppage'];
				$mode=$arrayres['mode'];
				$newval.=' <a href="'.sprintf($helpbaseurl,$helppage).'">'.img_picto($langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage': 'GoToHelpPage'),DOL_URL_ROOT.'/theme/common/helpdoc.png','',1).'</a>';
			}*/
			print $newval;
		}
	}
	else print $langs->trans("Error");
	print '</td></tr>';

	// Fields imported
	print '<tr><td>';
	print $langs->trans("FieldsTarget").'</td><td>';
	$listfields=array();
	$i=0;
	$sort_array_match_file_to_database=$array_match_file_to_database;
	ksort($sort_array_match_file_to_database);
	//var_dump($sort_array_match_file_to_database);
	foreach($sort_array_match_file_to_database as $code=>$label)
	{
		$i++;
		//var_dump($fieldssource);
		if ($code > count($fieldssource)) continue;
		//print $code.'-'.$label;
		$alias=preg_replace('/(\..*)$/i','',$label);
		$listfields[$i]=$langs->trans("Field").' '.$code.'->'.$label;
	}
	print count($listfields)?(join(', ',$listfields)):$langs->trans("Error");
	print '</td></tr>';

	print '</table>';

	// Launch import
	$arrayoferrors=array();
	$arrayofwarnings=array();
	$maxnboferrors=empty($conf->global->IMPORT_MAX_NB_OF_ERRORS)?50:$conf->global->IMPORT_MAX_NB_OF_ERRORS;
	$maxnbofwarnings=empty($conf->global->IMPORT_MAX_NB_OF_WARNINGS)?50:$conf->global->IMPORT_MAX_NB_OF_WARNINGS;
	$nboferrors=0;
	$nbofwarnings=0;

	$importid=dol_print_date(dol_now(),'%Y%m%d%H%M%S');

	//var_dump($array_match_file_to_database);

	$db->begin();

	// Open input file
	$nbok=0;
	$pathfile=$conf->import->dir_temp.'/'.$filetoimport;
	$result=$obj->import_open_file($pathfile,$langs);
	if ($result > 0)
	{
		$sourcelinenb=0;
		while ($sourcelinenb < $nboflines)
		{
			$sourcelinenb++;
			$arrayrecord=$obj->import_read_record();
			if ($excludefirstline && $sourcelinenb == 1) continue;

			$result=$obj->import_insert($arrayrecord,$array_match_file_to_database,$objimport,count($fieldssource),$importid);
			if (count($obj->errors))   $arrayoferrors[$sourcelinenb]=$obj->errors;
			if (count($obj->warnings))	$arrayofwarnings[$sourcelinenb]=$obj->warnings;
			if (! count($obj->errors) && ! count($obj->warnings)) $nbok++;
		}
		// Close file
		$obj->import_close_file();
	}
	else
	{
		print $langs->trans("ErrorFailedToOpenFile",$pathfile);
	}

	if (count($arrayoferrors) > 0) $db->rollback();	// We force rollback because this was errors.
	else  $db->commit();	// We can commit if no errors.

    dol_fiche_end();


	// Show result
	print '<center>';
	print '<br>';
	print $langs->trans("NbOfLinesImported",$nbok).'</b><br><br>';
	print $langs->trans("FileWasImported",$importid).'<br>';
	print $langs->trans("YouCanUseImportIdToFindRecord",$importid).'<br>';
	print '</center>';

	if ($mesg) print $mesg;
}



print '<br>';


llxFooter();

$db->close();


/**
 * Function to put the movable box of a source field
 *
 * @param	array	$fieldssource	List of source fields
 * @param	int		$pos			Pos
 * @param	string	$key			Key
 * @param	boolean	$var			Line style (odd or not)
 * @param	int		$nostyle		Hide style
 * @return	void
 */
function show_elem($fieldssource,$pos,$key,$var,$nostyle='')
{
	global $langs,$bc;

	print "\n\n<!-- Box ".$pos." start -->\n";
	print '<div class="box" style="padding: 0px 0px 0px 0px;" id="boxto_'.$pos.'">'."\n";

	print '<table summary="boxtable'.$pos.'" width="100%" class="nobordernopadding">'."\n";
	if ($pos && $pos > count($fieldssource))	// No fields
	{
		print '<tr '.($nostyle?'':$bc[$var]).' height="20">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print img_picto(($pos>0?$langs->trans("MoveField",$pos):''),'uparrow','class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("NoFields");
		print '</td>';
		print '</tr>';
	}
	elseif ($key == 'none')	// Empty line
	{
		print '<tr '.($nostyle?'':$bc[$var]).' height="20">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
		print '<td style="font-weight: normal">';
		print '&nbsp;';
		print '</td>';
		print '</tr>';
	}
	else	// Print field of source file
	{
		print '<tr '.($nostyle?'':$bc[$var]).' height="20">';
		print '<td class="nocellnopadding" width="16" style="font-weight: normal">';
		// The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
		print img_picto($langs->trans("MoveField",$pos),'uparrow','class="boxhandle" style="cursor:move;"');
		print '</td>';
		print '<td style="font-weight: normal">';
		print $langs->trans("Field").' '.$pos;
		$example=$fieldssource[$pos]['example1'];
		if ($example)
		{
		    if (! utf8_check($example)) $example=utf8_encode($example);
		    print ' (<i>'.$example.'</i>)';
		}
		print '</td>';
		print '</tr>';
	}

	print "</table>\n";

	print "</div>\n";
	print "<!-- Box end -->\n\n";
}


/**
 * Return not used field number
 *
 * @param 	array	&$fieldssource	Array of field source
 * @param	array	&$listofkey		Array of keys
 * @return	void
 */
function getnewkey(&$fieldssource,&$listofkey)
{
	$i=count($fieldssource)+1;
	// Max number of key
	$maxkey=0;
	foreach($listofkey as $key=>$val)
	{
		$maxkey=max($maxkey,$key);
	}
	// Found next empty key
	while($i <= $maxkey)
	{
		if (empty($listofkey[$i])) break;
		else $i++;
	}

	$listofkey[$i]=1;
	return $i;
}
?>
