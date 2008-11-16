<?php
/* Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/viewfiles.lib.php
 *	\brief      Set of function to view file content
 *	\version 	$Id$
 */


/**
 *
 */
function make_alpha_from_numbers($number)
{
	$numeric = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if($number<strlen($numeric))
	{
		return $numeric[$number];
	}
	else
	{
		$dev_by = floor($number/strlen($numeric));
		return "" . make_alpha_from_numbers($dev_by-1) . make_alpha_from_numbers($number-($dev_by*strlen($numeric)));
	}
}

/**
 *    \brief      Affiche le contenu d'un fichier CSV sous forme de tableau
 *    \param      file_to_include     Fichier CSV a afficher
 *    \param      max_rows            Nombre max de lignes a afficher (0 = illimit�)  	
 */
function viewCsvFileContent($file_to_include='',$max_rows=0)
{
	$fic = fopen($file_to_include, 'rb');
	$count = 0;
	
	print '<table border="1">';
	for ($ligne = fgetcsv($fic, 1024); (!feof($fic) && (($max_rows > 0)?($count<=$max_rows):1==1)); $ligne = fgetcsv($fic, 1024))
	{
		print '<tr>';
		$j = sizeof($ligne);
		for ($i = 0; $i < $j; $i++)
		{
			print '<td>'.$ligne[$i].'</td>';
		}
		print '</tr>';
		$count++;
	}
	print '</table>';
}

/**
 *    \brief      Affiche le contenu d'un fichier Excel (avec les feuilles de calcul) sous forme de tableau
 *    \param      file_to_include     Fichier Excel a afficher
 *    \param      max_rows            Nombre max de lignes a afficher (0 = illimite)
 *    \param      max_cols            Nombre max de colonnes a afficher (0 = illimite)  	
 */
function viewExcelFileContent($file_to_include='',$max_rows=0,$max_cols=0)
{
	$debug = 0;  	  //1 for on 0 for off
	$force_nobr = 0;  //Force the info in cells not to wrap unless stated explicitly (newline)
	
	require_once(PHPEXCELREADER.'excelreader.php');
	$data = new Spreadsheet_Excel_Reader();
	$data->setOutputEncoding('CPa25a');
	$data->read($file_to_include);
	error_reporting(E_ALL ^ E_NOTICE);

	echo "<script language='Javascript'>
		var sheet_HTML = Array();\n";
	for($sheet=0;$sheet<count($data->sheets);$sheet++)
	{
		$table_output[$sheet] .= "<TABLE CLASS='table_body'>
			<TR>
				<TD>&nbsp;</TD>";
		for($i=0;$i < $data->sheets[$sheet]['numCols'] && (($i < $max_cols) || ($max_cols == 0));$i++)
		{
			$table_output[$sheet] .= "<TD CLASS='table_sub_heading' ALIGN=CENTER>" . make_alpha_from_numbers($i) . "</TD>";
		}
		for($row=1;$row<=$data->sheets[$sheet]['numRows']&&($row<=$max_rows||$max_rows==0);$row++)
		{
			$table_output[$sheet] .= "<TR><TD CLASS='table_sub_heading'>" . $row . "</TD>";
			for($col=1;$col<=$data->sheets[$sheet]['numCols']&&($col<=$max_cols||$max_cols==0);$col++)
			{
				if($data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'] >=1 && $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'] >=1)
				{
					$this_cell_colspan = " COLSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];
					$this_cell_rowspan = " ROWSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];
					for($i=1;$i<$data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];$i++)
					{
						$data->sheets[$sheet]['cellsInfo'][$row][$col+$i]['dontprint']=1;
					}
					for($i=1;$i<$data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];$i++)
					{
						for($j=0;$j<$data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];$j++)
						{
							$data->sheets[$sheet]['cellsInfo'][$row+$i][$col+$j]['dontprint']=1;
						}
					}
				}
				else if($data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'] >=1)
				{
					$this_cell_colspan = " COLSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];
					$this_cell_rowspan = "";
					for($i=1;$i<$data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];$i++)
					{
						$data->sheets[$sheet]['cellsInfo'][$row][$col+$i]['dontprint']=1;
					}
				}
				else if($data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'] >=1)
				{
					$this_cell_colspan = "";
					$this_cell_rowspan = " ROWSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];
					for($i=1;$i<$data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];$i++)
					{
						$data->sheets[$sheet]['cellsInfo'][$row+$i][$col]['dontprint']=1;
					}
				}
				else
				{
					$this_cell_colspan = "";
					$this_cell_rowspan = "";
				}
				if(!($data->sheets[$sheet]['cellsInfo'][$row][$col]['dontprint']))
				{
					$table_output[$sheet] .= "<TD CLASS='table_data' $this_cell_colspan $this_cell_rowspan>&nbsp;";
					if($force_nobr)
					{
						$table_output[$sheet] .= "<NOBR>";
					}
					$table_output[$sheet] .= nl2br(htmlentities($data->sheets[$sheet]['cells'][$row][$col]));
					if($force_nobr)
					{
						$table_output[$sheet] .= "</NOBR>";
					}
					$table_output[$sheet] .= "</TD>";
				}
			}
			$table_output[$sheet] .= "</TR>";
		}
		$table_output[$sheet] .= "</TABLE>";
		$table_output[$sheet] = str_replace("\n","",$table_output[$sheet]);
		$table_output[$sheet] = str_replace("\r","",$table_output[$sheet]);
		$table_output[$sheet] = str_replace("\t"," ",$table_output[$sheet]);
		if($debug)
		{
			$debug_output = print_r($data->sheets[$sheet],true);
			$debug_output = str_replace("\n","\\n",$debug_output);
			$debug_output = str_replace("\r","\\r",$debug_output);
			$table_output[$sheet] .= "<PRE>$debug_output</PRE>";
		}
		echo "sheet_HTML[$sheet] = \"$table_output[$sheet]\";\n";
	}
	echo "
		function change_tabs(sheet)
		{
			//alert('sheet_tab_' + sheet);
			for(i=0;i<", count($data->sheets) , ";i++)
			{
			document.getElementById('sheet_tab_' + i).className = 'tab_base';
			}
			document.getElementById('table_loader_div').innerHTML=sheet_HTML[sheet];
			document.getElementById('sheet_tab_' + sheet).className = 'tab_loaded';
		}
		</SCRIPT>";

	echo "
<TABLE CLASS='table_body' NAME='tab_table'>
<TR>";
	for($sheet=0;$sheet<count($data->sheets);$sheet++)
	{
		echo "<TD CLASS='tab_base' ID='sheet_tab_$sheet' ALIGN=CENTER
		ONMOUSEDOWN=\"change_tabs($sheet);\">", $data->boundsheets[$sheet]['name'] , "</TD>";
	}

	echo 
	"<TR>";
	echo "</TABLE>
<DIV ID=table_loader_div></DIV>
<SCRIPT LANGUAGE='JavaScript'>
change_tabs(0);
</SCRIPT>";
}

?>
