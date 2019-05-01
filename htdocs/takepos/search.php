<?php
/* Copyright (C) 2018	JC Prieto			<prietojc@gmail.com>
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

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->load('takepos@takepos');
$langs->loadLangs(array("bills", "cashdesk"));

$place = GETPOST('place', 'int');
$action = GETPOST('action', 'alpha');
$facid= GETPOST('facid', 'int');	//V20

/*
 * View
 */

?>
<script>
/*
function InputBarcode(){
	pageproducts=0;
	
	alert("key: "+k+"tecto: "+$('#barcode').val());
	$.getJSON('./ajax.php?action=search&term='+$('#barcode').val(), function(data) {
		//if (data.length)>1) return;
		$('#barcode').val('');
		$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+data[0]['rowid'], function() {
			$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
		});
	});
}
*/

</script>
<?php

$form=new Form($db);

print '<table>';

if ($action=="diner"){
	print '<tr>';
	print '<td>' . $langs->trans('Diners') . '</td>';
	print "</tr>\n";
	print '<tr>';
	print '<td><input type="text" id="keyvalue" name="keyvalue" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Number').'></td>';
	print '<td><button type="button"  onclick="Search2Diner()">OK</button>';
	print "</tr>\n";
}
if ($action=="ticket"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchTicket').'</td>';
	print '<td><button class="search" type="button"  onclick="TicketList();">Listado</button></td>';
	
	print "</tr>\n";
	print '<tr>';
	print '<td><input type="text" id="keyvalue" name="keyvalue" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Number').'></td>';
	print '<td><button class="search" type="button"  onclick="Search2Ticket();">OK</button>';
	print "</tr>\n";
}
if ($action=="customerVAT"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchCustomer') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'phone\');">Telf.</button>&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'\');">Nombre</button></td>';
	print '<td><button class="search" align="right" type="button"  onclick="NewCustomer();">Nuevo</button></td>';
	print "</tr>";
	print '<tr>';
	print '<td><input type="text" id="keyvalue" name="keyvalue" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Profid1').'></td>';
	print '<td align="right"><button class="search" type="button"  onclick="Search2Customer(\'VAT\');">OK</button>';
	print '</td>';
	
	print "</tr>\n";
}
if ($action=="customerphone"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchCustomer') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'VAT\');">DNI</button>&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'\');">Nombre</button></td>';
	print '<td><button class="search" align="right" type="button"  onclick="NewCustomer();">Nuevo</button></td>';
	print "</tr>";
	print '<tr>';
	print '<td><input type="text" id="keyvalue" name="keyvalue" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Phone').'></td>';
	print '<td align="right"><button class="search" type="button"  onclick="Search2Customer(\'phone\');">OK</button>';
	print '</td>';
	
	print "</tr>\n";
}
if ($action=="customer"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchCustomer') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'VAT\');">DNI</button>&nbsp;';
	print '<button class="search" align="right" type="button"  onclick="SearchCustomer(\'phone\');">Telf.</button></td>';
	print '<td align="right"><button class="search"  type="button"  onclick="NewCustomer();">Nuevo</button></td>';
	print "</tr>";
	print '<tr>';
	print '<td style="padding-top: 20px;">'.$form->select_thirdparty_list('','keyvalue','',1,0,0,array(),'',0,0,'maxwidth300').'</td>';
	print '<td align="right"><button class="search" type="button"  onclick="Search2Customer(\'\');">OK</button>';
	print '</td>';

	print "</tr>\n";
}
if ($action=="product"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchProduct') . '</td>';
	print '<td><button class="search" type="button"  onclick="SearchBarcode();">Codigo Barras</button></td>';
	print "</tr>\n";
	print '<tr>';
	print '<td><input type="text" id="search" name="search" onkeyup="Search2(event);" style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Name').'></td>';
	print "</tr>\n";
}
if ($action=="barcode"){
	print '<tr>';
	print '<td>' . $langs->trans('SearchBarcode') . '</td>';
	print '<td><button class="search" type="button"  onclick="SearchProduct();">Producto</button></td>';
	print "</tr>\n";
	print '<tr>';
	print '<td><input type="text" id="barcode" name="barcode" onkeyup="Search2Barcode(event);"  style="width:80%;font-size: 150%;" placeholder='.$langs->trans('Barcode').'></td>';
	print "</tr>\n";
}
print '</table>';
?>