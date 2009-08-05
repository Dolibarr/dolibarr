{* Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Auguria SARL         <info@auguria.org>
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
    	<div class="titre">{$langs->trans('Books')}</div>
	</td>
</tr>
</table>

<form action="liste.php?canvas=livre" method="post" name="formulaire">

<table class="liste" width="100%">
 <tr class="liste_titre">
  <td class="liste_titre">{$langs->trans('Case')}
  </td>

  <td class="liste_titre">{$langs->trans('Ref')}
  <a href="liste.php?sortfield=p.ref&amp;sortorder=asc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre">{$langs->trans('Title')}
  <a href="liste.php?sortfield=p.label&amp;sortorder=asc&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
  </a>
  <a href="liste.php?sortfield=p.ref&amp;sortorder=desc&amp;begin=&amp;envente=&amp;canvas=livre&amp;fourn_id=&amp;snom=&amp;sref=">
  <img src="{$url_root}/theme/{$theme}/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  </a>
  </td>

  <td class="liste_titre" align="center">Casier</td>
  <td class="liste_titre" align="center">Entrepot</td>
  <td class="liste_titre" align="center">Ventes</td>
  <td class="liste_titre">Stock</td>
  <td class="liste_titre" align="center">Pages</td>
  <td class="liste_titre" align="right">Prix</td>
  <td class="liste_titre" align="right">Valorisation
  </td>
  <td class="liste_titre" align="right">Action
  </td>

</tr>

<tr class="liste_titre">
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre"><input class="flat" type="text" name="sref" value=""></td>
 <td class="liste_titre"><input class="flat" type="text" name="snom" value=""></td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre">&nbsp;</td>
 <td class="liste_titre" align="right">
 	<input type="image" class="liste_titre" name="button_search" src="{$url_root}/theme/{$theme}/img/search.png" alt="Rechercher">
 	<input type="image" class="liste_titre" name="button_removefilter" src="{$url_root}/theme/{$theme}/img/searchclear.png" alt="Supprimer filtre">
 	</td>
</tr>

{section name=mysec loop=$datas}
{strip}
   <tr class="{cycle values="pair,impair"}">
      <td>$datas</td>
      <td><a href="fiche.php?id={$datas[mysec].id}">{$datas[mysec].ref}</a></td>
      <td>{$datas[mysec].titre}</td>
      <td align="center">{$datas[mysec].casier}</td>
      <td align="center">{$datas[mysec].entrepot}</td>
      <td>{$datas[mysec].ventes}</td>
      <td>{$datas[mysec].stock}</td>
      <td align="center">{$datas[mysec].pages}</td>
      <td align="right">{$datas[mysec].prix}</td>
      <td align="right">{$datas[mysec].valo}</td>
   </tr>
{/strip}
{/section}

</table>
</form>

<!-- END SMARTY TEMPLATE -->