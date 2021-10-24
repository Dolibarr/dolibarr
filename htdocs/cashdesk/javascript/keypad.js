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

function closekeypad(keypadname)
{
	document.getElementById('keypad'+keypadname).style.display='none';
	document.getElementById('closekeypad'+keypadname).style.display='none';
	document.getElementById('openkeypad'+keypadname).style.display='inline-block';
}
function openkeypad(keypadname)
{
	document.getElementById('keypad'+keypadname).style.display='inline-block';
	document.getElementById('closekeypad'+keypadname).style.display='inline-block';
	document.getElementById('openkeypad'+keypadname).style.display='none';
}
function addvalue(keypadname, formname, valueToAdd)
{
	myform=document.forms[formname];
	if (myform.elements[keypadname].value=="0")
		myform.elements[keypadname].value="";
	myform.elements[keypadname].value+=valueToAdd;
	modif();
}
