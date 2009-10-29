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
   <td align="left">{$langs->trans('Town')}</td>
   <td align="left">{$langs->trans('Country')}</td>
   <td align="left">{$langs->trans('Currency')}</td>
   <td align="center">{$langs->trans('Status')}</td>

{section name=mc loop=$entities}
{strip}
   <tr class="{cycle values="impair,pair"}">
      <td>{$entities[mc].label}</td>
      <td align="left">{$entities[mc].details.MAIN_INFO_SOCIETE_VILLE}</td>
      <td align="left">{$entities[mc].details.MAIN_INFO_SOCIETE_PAYS}</td>
      <td align="left">{$entities[mc].details.MAIN_MONNAIE}</td>
      <td align="center">
      
      {if $entities[mc].active}
      <a href="{$smarty.server.SCRIPT_NAME}?action=disable&amp;id={$entities[mc].id}">{$img_on}</a>
      {else}
      <a href="{$smarty.server.SCRIPT_NAME}?action=enable&amp;id={$entities[mc].id}">{$img_off}</a>
      {/if}
      
      </td>
   </tr>
{/strip}
{/section}

</tr></table>

<div class="tabsAction">
<a class="butAction" href="{$smarty.server.SCRIPT_NAME}?action=create">{$langs->trans('AddEntity')}</a>
</div>

<!-- END SMARTY TEMPLATE -->