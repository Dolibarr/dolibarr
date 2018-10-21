<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Sale representative
print '<tr><td class="titlefield">';
print $langs->trans('SalesRepresentatives');
print '</td>';
print '<td>';

$listsalesrepresentatives=$object->getSalesRepresentatives($user);
$nbofsalesrepresentative=count($listsalesrepresentatives);
if ($nbofsalesrepresentative > 0)
{
	$userstatic=new User($db);
	foreach($listsalesrepresentatives as $val)
	{
		$userstatic->id=$val['id'];
		$userstatic->login=$val['login'];
		$userstatic->lastname=$val['lastname'];
		$userstatic->firstname=$val['firstname'];
		$userstatic->statut=$val['statut'];
		$userstatic->photo=$val['photo'];
		$userstatic->email=$val['email'];
		$userstatic->entity=$val['entity'];
		print $userstatic->getNomUrl(-1);
		print ' ';
	}
}
else print '<span class="opacitymedium">'.$langs->trans("NoSalesRepresentativeAffected").'</span>';
print '</td></tr>';

