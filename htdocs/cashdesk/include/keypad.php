<?php
/* Copyright (C) 2014	Charles-FR BENKE		<charles.fr@benke.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Return a string to output a keypad
 *
 * @param	string		$keypadname		Key pad name
 * @param 	string		$formname		Form name
 * @return	string						HTML code to show a js keypad.
 */
function genkeypad($keypadname, $formname)
{
	global $conf;

	if (empty($conf->global->CASHDESK_SHOW_KEYPAD)) return '';

	// défine the font size of button
	$btnsize = 32;
	$sz = '<input type="button" id="closekeypad'.$keypadname.'" value="X" style="display:none;" onclick="closekeypad(\''.$keypadname.'\')"/>'."\n";
	$sz .= '<input type="button" value="..." id="openkeypad'.$keypadname.'" onclick="openkeypad(\''.$keypadname.'\')"/><br>'."\n";
	$sz .= '<div id="keypad'.$keypadname.'" style="position:absolute;z-index:90;display:none; background:#AAA; vertical-align:top;">'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 7 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',7);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 8 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',8);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 9 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',9);"/><br/>'."\n";

	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 4 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',4);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 5 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',5);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 6 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',6);"/><br/>'."\n";

	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 1 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',1);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 2 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',2);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 3 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',3);"/><br/>'."\n";

	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value=" 0 " onclick="addvalue(\''.$keypadname.'\',\''.$formname.'\',0);"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value="&larr;" ';
	$sz .= ' onclick="document.getElementById(\''.$keypadname.'\').value=document.getElementById(\''.$keypadname.'\').value.substr(0,document.getElementById(\''.$keypadname.'\').value.length-1);modif();"/>'."\n";
	$sz .= '<input type="button" style="font-size:'.$btnsize.'px;" value="CE" onclick="document.getElementById(\''.$keypadname.'\').value=0;modif();"/>'."\n";
	$sz .= '</div>';
	return $sz;
}
