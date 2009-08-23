<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *		\version    $Id$
 *		\author		Put author name here
 *		\remarks	Put here some comments
 */
require("./pre.inc.php");
//require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");

require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("bills");
$langs->load("other");

// Get parameters
$myparam = isset($_GET["myparam"])?$_GET["myparam"]:'';

// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

llxHeader('','','');

if ($_REQUEST["action"] == 'import')
{

	$account = $_REQUEST['account'];	

	if (isset($_REQUEST[separatorTAB])){		
                $delimiter = "\t";
	} else if (isset($_REQUEST[separator]) && trim($_REQUEST[separator]) != ""){
		$delimiter = trim($_REQUEST[separator]);
	} else{
		print ('<font color="red">DEBE ELEGIR EL DELIMITADOR DE CAMPO!</font><br><br>');
		print ('<input type="button" value="Volver" onclick="history.back()"');
		exit;
	}

	
	$file = fopen($_FILES['uploadFile']['tmp_name'], "r");

	if (isset($_REQUEST['omitFirst'])){
		//salteamos la primer linea.
		fgets($file);
	}

	$db->begin();
	$line = "";
	$count = 0;
	$fail = false;
	$html = "<table class='notopnoleftnoright' style='margin:20px'><tr class='liste_titre'><td>#</td><td>Registro</td><td>Estado</td></tr>";

	
	while (($line = fgets($file))!= ""){
		
			
		$line = trim($line);		
		if ($line == ""){
			continue;
		}

		$html .= "<tr class='".(($count%2==0)?"pair":"impair")."'><td style='padding:5px'><b>".($count+1)."</b></td><td style='padding:5px'>";

		$data = split($delimiter,$line);

		$html .= $line."</td><td style='padding:5px'>";

		
		
		$data[0] = str_replace("/","-",$data[0]);

		$datePart = split("-",$data[0]);

		$dateop = dol_mktime(12,0,0,$datePart[0],$datePart[1],$datePart[2]);
		
		$dateop2 = $datePart[2]."-".$datePart[0]."-".$datePart[1];

		$operation=$_REQUEST['operation'];

		$num_chq=split(" ",trim($data[1]));
		$num_chq=$num_chq[count($num_chq)-1];

		$label=$data[1]." - ".$data[3];
		$cat1=NULL;

		


		$tmpAmount = trim($data[2]);
		if ($tmpAmount{0} == "="){
			$tmpAmount = substr($tmpAmount,1);
			
			if (strpos($tmpAmount,"/") != FALSE){
				$amountPart = split("/",$tmpAmount);
				$amount = (float)$amountPart[0]/(float)$amountPart[1];
			} else{
				$amount = $tmpAmount;
			}			
		}else{
			$amount= str_replace(",","@",$data[2]);	
			$amount= str_replace(".",",",$amount);
			$amount= str_replace("@",".",$amount); 
		}
		
		
		$acct=new Account($db,$account);

		$exist = $db->query("Select rowid from llx_bank where datev ='".$dateop2."' AND label = '".$label."' AND amount = ".$amount." AND num_chq = '".$num_chq."'");
	
		
		if ($db->num_rows($exist) == 0){
			$insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);		
			if ($insertid <= 0){		
				//dol_print_error($db,$acct->error);
				$db->rollback();
				$html .= "<font color='red'><b>ERROR</b></font></td></tr>";
				$fail = true;
				break;
			}

			$db->query("update llx_bank set num_releve = '".date(Ymd)."' where rowid = ".$insertid);

			
			$html .= "<font color='green'><b>OK</b></font></</td></tr>";
		}else{
				$html .= "<font color='blue'><b>DUPLICADO</b></font></td></tr>";
		}

		
		$count++;
	} 
	$db->commit();
	$html .= "</table>";

	print ($html);
	if ($fail){
		dol_print_error($db,$acct->error);
	}
	
		
	print ('<br><br><input type="button" value="Volver" onclick="history.back()"');
	exit;

}





/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/





print_fiche_titre("Importaci&oacute;n de Archivos");



$accounts = array();

$resql=$db->query("Select * from llx_bank_account");
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$accounts[] = $obj;
			}
			$i++;
		}
	}
}


?>
	<script type="text/javascript">
		function submitForm(){

			var cboAccount = window.document.getElementById('account');
			var cboTipo = window.document.getElementsByName('operation')[0];
			var uploadFile= window.document.getElementById('uploadFile');
			var action= window.document.getElementById('action');
                        var chkDelimiter = window.document.getElementById('separatorTAB');
                        var txtDelimiter = window.document.getElementById('separator');

			if (cboTipo.value == ""){
				alert ("Debe seleccionar un tipo de operacion!");
				cboTipo.focus();
				return;
			}

			if (uploadFile.value == ""){
				alert ("Debe seleccionar un archivo a importar");
				uploadFile.focus();
				return;
			}

                        if (!chkDelimiter.checked && txtDelimiter.value == ""){
                            alert ("Debe ingresar un delimitador");
                            txtDelimiter.focus();
                            return;
                        }


			if (!confirm('Esta seguro que desea importar el archivo seleccionado a la cuenta '+cboAccount.options[cboAccount.selectedIndex].innerHTML+'?')){
				return;
			}

			var frm = window.document.getElementById('importForm');
			action.value = 'import';
			frm.submit();

		}
	</script>
<?php

$form=new Form($db);



print '<form method="post" action="'.DOL_URL_ROOT.'/MCCImport/index.php" id="importForm" enctype="multipart/form-data">';




?>

<input type="hidden" name="action" id="action"/>
<table border="0" class="notopnoleftnoright">
<tr><td valign="top" class="notopnoleft">
	Seleccione la cuenta destino:
	</td>
	<td valign="top" class="notopnoleft">
	<select name="account" id="account">
		<?php
		foreach ($accounts as $a){
		?>
			<option value="<?php print($a->rowid); ?>"><?php print($a->label); ?></option>
		<?php
		}
		?>

	</select>
	<td valign="top" class="notopnoleft">
</tr>
<tr>
<td valign="top" class="notopnoleft">	
	Tipo de Transacci&oacute;n:
</td>
<td valign="top" class="notopnoleft" nowrap="true">
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>
			<?php $form->select_types_paiements('CHQ','operation','1,2',2,1); ?> 
		</td>
		<td style="padding-left:5px;">
			<?php print $form->textwithhelp('',"Todas las transacciones ser&aacute;n cargadas con este tipo.",3); ?>
		</td>
	</tr>
	</table>
</td>
</tr>
<tr>
<td valign="top" class="notopnoleft">	
	Seleccione el archivo a importar:
</td>
<td valign="top" class="notopnoleft">
	<input type="file" name="uploadFile" id="uploadFile"/>
</td>
</tr>
<tr>
<td valign="top" class="notopnoleft">	
	Omitir primer l&iacute;nea:
</td>
<td valign="top" class="notopnoleft">	
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>
			<input type="checkbox" name="omitFirst" id="omitFirst" checked/>
		</td>
		<td style="padding-left:5px;">
			<?php print $form->textwithhelp('',"Elija esta opci&oacute;n cuando la primer l&iacute;nea del archivo deba ser omitida debido a que contiene el encabezado en vez de datos.",3); ?>
		</td>
	</tr>
	</table>

</td>
</tr>
<tr>
<td valign="top" class="notopnoleft">	
	Delimitador de campo:
</td>
<td valign="top" class="notopnoleft">	
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>
			<input type="checkbox" name="separatorTAB" id="separatorTAB" checked onchange="window.document.getElementById('separator').value = '';"/>&nbsp;TAB.
		</td>
		<td>
			&nbsp;Otro: &nbsp;<input type="text" name="separator" id="separator" onkeydown="window.document.getElementById('separatorTAB').checked = false;" /> 
		</td>
		<td style="padding-left:5px;">
			<?php print $form->textwithhelp('',"Seleccione el separador de campos utilizado en el archivo. Si el caracter es TAB, utilice el checkbox; sino ingreselo en el campo de texto.",3); ?>
		</td>
	</tr>
	</table>

</td>

</tr>
<tr>

<td valign="top" class="notopnoleft" colspan="2" style="text-align:center">

	<input type="button" value="Importar" onclick="submitForm();"/>
	</td></tr></table>
</form>
<?php



// End of page
$db->close();
llxFooter('$Date$ - $Revision$');
?>
