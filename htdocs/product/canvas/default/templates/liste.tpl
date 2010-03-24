{* Copyright (C) 2009-2010 Regis Houssin <regis@dolibarr.fr>
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

<table width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;">
<tr>
	<td class="nobordernopadding" width="40" align="left" valign="middle">
		{$title_picto}
	</td>
	<td class="nobordernopadding" valign="middle">
    	<div class="titre">{$title_text}</div>
	</td>
</tr>
</table>

<form action="liste.php?canvas=default" method="post" name="formulaire">

<table class="liste" width="100%">

<!-- FIELDS TITLE -->

 <tr class="liste_titre">
 	{section name=field loop=$fieldlist}
 	{strip}
 	
 	{if $fieldlist[field].sortfield}
 		<td class="liste_titre" align="{$fieldlist[field].align}">{$fieldlist[field].title}
 			<a href="liste.php?sortfield={$fieldlist[field].sortfield}&amp;sortorder=asc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
 				<img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
 			</a>
  			<a href="liste.php?sortfield={$fieldlist[field].sortfield}&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  				<img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  			</a>
  		</td>
  	{else}
  		<td class="liste_titre" align="{$fieldlist[field].align}">{$fieldlist[field].title}</td>
  	{/if}
  	
  	{/strip}
  	{/section}
 </tr>
 
 <!-- FIELDS SEARCH -->
 
 <tr class="liste_titre">
 	{section name=searchfield loop=$fieldlist}
 	{strip}
 	
   	{if $fieldlist[searchfield].search}
  		<td class="liste_titre" align="{$fieldlist[searchfield].align}"><input class="flat" type="text" name="s{$fieldlist[searchfield].name}" value=""></td>
  	{elseif $smarty.section.search.last}
  		<td class="liste_titre" align="right">
  			<input type="image" class="liste_titre" name="button_search" src="{$url_root}/theme/{$theme}/img/search.png" alt="{$langs->trans('Search')}">
  			<input type="image" class="liste_titre" name="button_removefilter" src="{$url_root}/theme/{$theme}/img/searchclear.png" alt="{$langs->trans('RemoveFilter')}">
  		</td>
  	{else}
  		<td class="liste_titre">&nbsp;</td>
  	{/if}
  	
  	{/strip}
  	{/section}
 </tr>

<!-- FIELDS DATA -->

{foreach name=prodline item=line from=$datas}
{strip}
   <tr class="{cycle values="pair,impair"}">
   		{foreach name=valueline key=key item=value from=$line}
   			{foreach name=fieldline item=field from=$fieldlist}
   				{if $field.name == $key}
   					<td align="{$field.align}">{$value}</td>
   				{/if}
   			{/foreach}
   		{/foreach}
   </tr>
{/strip}
{/foreach}

</table>
</form>

<!-- END SMARTY TEMPLATE -->