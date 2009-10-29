{* Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>
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
 *
 * $Id$
 *}
 
<!-- BEGIN SMARTY TEMPLATE -->

<table class="noborder" width="100%">
 <tr class="liste_titre">

   <td>{$langs->trans('Name')}</td>
   <td align="left">{$langs->trans('Description')}</td>
   <td align="center">{$langs->trans('Status')}</td>

{section name=mc loop=$entities}
{strip}
   <tr class="{cycle values="impair,pair"}">
      <td>{$entities[mc].label}</td>
      <td align="left">&nbsp;</td>
      <td align="center">
      
      {if $entities[mc].active}
      {$img_on}
      {else}
      {$img_off}
      {/if}
      
      </td>
   </tr>
{/strip}
{/section}

</tr></table>

<!-- END SMARTY TEMPLATE -->