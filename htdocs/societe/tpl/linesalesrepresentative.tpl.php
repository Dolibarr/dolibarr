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
print '<tr><td>';
print '<table width="100%" class="nobordernopadding"><tr><td>';
print $langs->trans('SalesRepresentatives');
print '</td><td align="right">';
if ($user->rights->societe->creer && $user->rights->societe->client->voir)
{
	print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$object->id.'">'.img_edit('',1).'</a>';
}
else
{
	print '&nbsp;';
}
print '</td></tr></table>';
print '</td>';
print '<td colspan="3">';

$listsalesrepresentatives=$object->getSalesRepresentatives($user);
$nbofsalesrepresentative=count($listsalesrepresentatives);
if ($nbofsalesrepresentative > 0)
{
	$userstatic=new User($db);
	$i=0;
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
		$i++;
		if ($i < $nbofsalesrepresentative)
		{
			print ' ';
			if ($i >= 3)   // We print only number
			{
				$userstatic->id=0;
				$userstatic->login='';
				$userstatic->lastname='';
				$userstatic->firstname='';
				$userstatic->statut=0;
				$userstatic->photo='';
				$userstatic->email='';
				$userstatic->entity=0;
				print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$object->id.'">';
				print $userstatic->getNomUrl(-1, 'nolink', 0, 1);
				print '+'.($nbofsalesrepresentative - $i);
				print '</a>';
				break;
			}
		}
	}
}
else print '<span class="opacitymedium">'.$langs->trans("NoSalesRepresentativeAffected").'</span>';
print '</td></tr>';

